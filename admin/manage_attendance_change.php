<?php
include("header.php");
require 'db_connection.php';
require_once '../includes/attendance_change_request_helper.php';

if (!isset($_SESSION['admin_id'])) {
    echo "<div class='alert alert-danger'>You must be logged in to access this page.</div>";
    exit;
}

ensureAttendanceChangeRequestTable($conn);

$flashMessage = '';
$flashType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['action_type'])) {
    $requestId = (int) $_POST['request_id'];
    $actionType = $_POST['action_type'];
    $rejectReason = trim($_POST['reject_reason'] ?? '');
    $decisionAt = date('Y-m-d H:i:s');
    $approvedById = (int) ($_SESSION['admin_id'] ?? 0);
    $approvedByName = $_SESSION['admin_name'] ?? 'Admin';
    $approvedByType = $_SESSION['admin_roll'] ?? 'Admin';

    $requestStmt = $conn->prepare("
        SELECT acr.*, e.office
        FROM attendance_change_requests acr
        JOIN employees e ON acr.employee_id = e.id
        WHERE acr.id = ?
        LIMIT 1
    ");
    $requestStmt->bind_param("i", $requestId);
    $requestStmt->execute();
    $requestResult = $requestStmt->get_result();
    $request = $requestResult->fetch_assoc();
    $requestStmt->close();

    if (!$request) {
        $flashMessage = "Request not found.";
        $flashType = 'danger';
    } elseif ($request['status'] !== 'Pending') {
        $flashMessage = "Only pending requests can be processed.";
        $flashType = 'warning';
    } else {
        if ($actionType === 'approve') {
            $attendanceDate = $request['attendance_date'];
            $requestedStatus = $request['requested_status'] ?? 'Present';
            $punchInDateTime = $attendanceDate . ' ' . $request['requested_punch_in'];
            $punchOutDateTime = $attendanceDate . ' ' . $request['requested_punch_out'];
            $workingHours = $requestedStatus === 'Present'
                ? attendanceDecimalHours($punchInDateTime, $punchOutDateTime)
                : 0.0;

            if ($requestedStatus === 'Present' && $workingHours <= 0) {
                $flashMessage = "Invalid punch in and punch out time.";
                $flashType = 'danger';
            } else {
                $conn->begin_transaction();

                try {
                    $attendance = fetchAttendanceForDate($conn, (int) $request['employee_id'], $attendanceDate);

                    if ($attendance) {
                        $updateAttendance = $conn->prepare("
                            UPDATE attendance
                            SET punch_in_time = ?, punch_out_time = ?, working_hours = ?, office = ?, status = ?
                            WHERE id = ?
                        ");
                        $finalPunchIn = $requestedStatus === 'Present' ? $punchInDateTime : ($attendanceDate . ' 00:00:00');
                        $finalPunchOut = $requestedStatus === 'Present' ? $punchOutDateTime : ($attendanceDate . ' 00:00:00');
                        $updateAttendance->bind_param(
                            "ssdssi",
                            $finalPunchIn,
                            $finalPunchOut,
                            $workingHours,
                            $request['office'],
                            $requestedStatus,
                            $attendance['id']
                        );
                        $updateAttendance->execute();
                        $updateAttendance->close();
                    } else {
                        $insertAttendance = $conn->prepare("
                            INSERT INTO attendance (employee_id, punch_in_time, punch_out_time, working_hours, office, status)
                            VALUES (?, ?, ?, ?, ?, ?)
                        ");
                        $finalPunchIn = $requestedStatus === 'Present' ? $punchInDateTime : ($attendanceDate . ' 00:00:00');
                        $finalPunchOut = $requestedStatus === 'Present' ? $punchOutDateTime : ($attendanceDate . ' 00:00:00');
                        $insertAttendance->bind_param(
                            "issdss",
                            $request['employee_id'],
                            $finalPunchIn,
                            $finalPunchOut,
                            $workingHours,
                            $request['office']
                            ,
                            $requestedStatus
                        );
                        $insertAttendance->execute();
                        $insertAttendance->close();
                    }

                    $approveStmt = $conn->prepare("
                        UPDATE attendance_change_requests
                        SET status = 'Approved',
                            approved_rejected_at = ?,
                            approved_by_id = ?,
                            approved_by_name = ?,
                            approved_by_type = ?,
                            reject_reason = NULL
                        WHERE id = ?
                    ");
                    $approveStmt->bind_param(
                        "sissi",
                        $decisionAt,
                        $approvedById,
                        $approvedByName,
                        $approvedByType,
                        $requestId
                    );
                    $approveStmt->execute();
                    $approveStmt->close();

                    $conn->commit();
                    $flashMessage = "Request approved and attendance updated successfully.";
                    $flashType = 'success';
                } catch (Throwable $e) {
                    $conn->rollback();
                    $flashMessage = "Approval failed. Please try again.";
                    $flashType = 'danger';
                }
            }
        } elseif ($actionType === 'reject') {
            $rejectStmt = $conn->prepare("
                UPDATE attendance_change_requests
                SET status = 'Rejected',
                    approved_rejected_at = ?,
                    approved_by_id = ?,
                    approved_by_name = ?,
                    approved_by_type = ?,
                    reject_reason = ?
                WHERE id = ?
            ");
            $rejectStmt->bind_param(
                "sisssi",
                $decisionAt,
                $approvedById,
                $approvedByName,
                $approvedByType,
                $rejectReason,
                $requestId
            );

            if ($rejectStmt->execute()) {
                $flashMessage = "Request rejected. Attendance remains unchanged.";
                $flashType = 'success';
            } else {
                $flashMessage = "Failed to reject request.";
                $flashType = 'danger';
            }
            $rejectStmt->close();
        }
    }
}

$statusFilter = $_GET['status'] ?? '';

$query = "
    SELECT acr.*, e.name, e.employee_id AS employee_code
    FROM attendance_change_requests acr
    JOIN employees e ON acr.employee_id = e.id
";

if (in_array($statusFilter, ['Pending', 'Approved', 'Rejected'], true)) {
    $query .= " WHERE acr.status = ?";
}

$query .= " ORDER BY acr.applied_at DESC, acr.id DESC";

$stmt = $conn->prepare($query);
if (in_array($statusFilter, ['Pending', 'Approved', 'Rejected'], true)) {
    $stmt->bind_param("s", $statusFilter);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<style>
.change-shell {
    padding-bottom: 1.5rem;
}

.change-filter-card,
.change-table-card,
.change-modal .modal-content {
    border: 1px solid rgba(87, 96, 108, 0.12);
    border-radius: 22px;
    box-shadow: 0 14px 34px rgba(31, 41, 55, 0.06);
    background: #fff;
}

.change-filter-card {
    padding: 1.25rem;
    margin-bottom: 1.5rem;
}

.change-title {
    margin: 0;
    color: #111827;
    font-size: 1.05rem;
    font-weight: 800;
}

.change-meta {
    color: #94a3b8;
    font-size: 0.78rem;
    margin-top: 0.3rem;
}

.change-filter-card .form-control,
.change-filter-card .form-select,
.change-modal .form-control,
.change-modal .form-select,
.change-modal textarea {
    min-height: 50px;
    border-radius: 14px;
    border: 1px solid #d8dee7;
    color: #374151;
}

.change-filter-card .form-control:focus,
.change-filter-card .form-select:focus,
.change-modal .form-control:focus,
.change-modal .form-select:focus,
.change-modal textarea:focus {
    border-color: #aab7c9;
    box-shadow: 0 0 0 0.2rem rgba(55, 65, 81, 0.08);
}

.change-filter-card label,
.change-modal .form-label {
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #6b7280;
    margin-bottom: 0.55rem;
}

.change-btn {
    min-height: 44px;
    border-radius: 14px;
    padding-left: 1rem;
    padding-right: 1rem;
    box-shadow: 0 10px 24px rgba(31, 41, 55, 0.10);
}

.change-btn-primary {
    background: linear-gradient(180deg, #2b2c31 0%, #1f2024 100%);
    color: #fff !important;
    border: 1px solid #2b2c31;
}

.change-btn-primary:hover {
    background: linear-gradient(180deg, #32343a 0%, #23242a 100%);
    color: #fff !important;
    border-color: #32343a;
}

.change-btn-secondary {
    background: linear-gradient(135deg, #161616 0%, #2d2d2d 100%);
    color: #fff !important;
    border: 1px solid #161616;
}

.change-btn-secondary:hover {
    background: linear-gradient(135deg, #101010 0%, #242424 100%);
    color: #fff !important;
}

.change-btn-danger {
    background: #fbe6e5;
    color: #c24141 !important;
    border: 1px solid #f4c9c7;
}

.change-btn-danger:hover {
    background: #f7d8d6;
    color: #a93232 !important;
}

.change-table-card {
    overflow: hidden;
}

.change-table-card .card-body {
    padding: 0;
}

.change-table-title {
    padding: 1.2rem 1.25rem 0.85rem;
}

.change-table-title h6 {
    margin: 0;
    font-size: 1rem;
    color: #111827;
}

.change-table-title p {
    margin: 0.3rem 0 0;
    color: #94a3b8;
    font-size: 0.8rem;
}

.change-table {
    margin-bottom: 0;
}

.change-table thead th {
    border-bottom: 1px solid #e8edf5;
    background: #f8fafc;
    color: #64748b;
    font-size: 0.74rem;
    font-weight: 800;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    padding-top: 1rem;
    padding-bottom: 1rem;
    white-space: nowrap;
}

.change-table tbody td {
    padding-top: 1rem;
    padding-bottom: 1rem;
    border-color: #eef2f7;
    vertical-align: top;
}

.change-table tbody tr:hover {
    background: #fbfcfe;
}

.change-table .text-xs {
    color: #6b7280 !important;
}

.change-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 88px;
    padding: 0.42rem 0.7rem;
    border-radius: 999px;
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    border: 1px solid transparent;
}

.change-badge-approved {
    background: #e8f7ef;
    border-color: #cfe9da;
    color: #1f8f57;
}

.change-badge-rejected {
    background: #fdf2f2;
    border-color: #f3d6d6;
    color: #991b1b;
}

.change-badge-pending {
    background: #eef2f7;
    border-color: #d9e1ea;
    color: #334155;
}

.change-modal .modal-header,
.change-modal .modal-footer {
    border-color: #eef2f7;
}

.change-modal .modal-header,
.change-modal .modal-body,
.change-modal .modal-footer {
    padding-left: 1.25rem;
    padding-right: 1.25rem;
}

.change-flash {
    border-radius: 16px;
    border: 1px solid transparent;
    box-shadow: 0 10px 24px rgba(31, 41, 55, 0.06);
}

.change-flash.alert-success {
    background: #e8f7ef;
    border-color: #cfe9da;
    color: #1f8f57;
}

.change-flash.alert-danger {
    background: #fdf2f2;
    border-color: #f3d6d6;
    color: #991b1b;
}

.change-flash.alert-warning {
    background: #fff7ed;
    border-color: #f8dcc2;
    color: #b45309;
}

@media (max-width: 991.98px) {
    .change-filter-card,
    .change-table-card,
    .change-modal .modal-content {
        border-radius: 18px;
    }
}
</style>

<div class="container-fluid container-fluid-main change-shell py-4">
    <?php if ($flashMessage !== ''): ?>
                <div class="alert change-flash alert-<?= $flashType ?>"><?= htmlspecialchars($flashMessage) ?></div>
    <?php endif; ?>

    <div class="row">
                <div class="col-12">
                        <div class="change-filter-card">
                                <div class="row g-3 align-items-end">
                                        <div class="col-lg-8 col-md-6">
                                                <div class="change-title">Attendance Change Requests</div>
                                                <div class="change-meta">Review, approve, or reject attendance corrections in a cleaner request management view.</div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 text-lg-end">
                                                <form method="GET" class="d-inline-block w-100">
                                                        <label for="status" class="form-label">Filter Status</label>
                                                        <select name="status" id="status" class="form-control" onchange="this.form.submit()">
                                                                <option value="">All Requests</option>
                                                                <option value="Pending" <?= $statusFilter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                                <option value="Approved" <?= $statusFilter === 'Approved' ? 'selected' : '' ?>>Approved</option>
                                                                <option value="Rejected" <?= $statusFilter === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                                                        </select>
                                                </form>
                                        </div>
                                </div>
                        </div>
        </div>

        <div class="col-12">
                        <div class="card change-table-card mb-4">
                                <div class="change-table-title">
                                        <h6>Attendance Change Request Queue</h6>
                                        <p>See current vs requested attendance details, reasons, and approval decisions in one place.</p>
                                </div>
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="table-responsive p-0">
                                                <table class="table change-table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Date</th>
                                    <th>Requested Status</th>
                                    <th>Current</th>
                                    <th>Requested</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->num_rows > 0): ?>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($row['name']) ?></strong><br>
                                                <span class="text-xs"><?= htmlspecialchars($row['employee_code']) ?></span>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($row['attendance_date']) ?><br>
                                                <span class="text-xs"><?= htmlspecialchars($row['applied_at']) ?></span>
                                            </td>
                                            <td><?= htmlspecialchars($row['requested_status'] ?? 'Present') ?></td>
                                            <td>
                                                <span class="text-xs">Status: <?= htmlspecialchars($row['current_status'] ?? 'N/A') ?></span><br>
                                                <span class="text-xs">In: <?= htmlspecialchars($row['current_punch_in_time'] ?? 'N/A') ?></span><br>
                                                <span class="text-xs">Out: <?= htmlspecialchars($row['current_punch_out_time'] ?? 'N/A') ?></span>
                                            </td>
                                            <td>
                                                <span class="text-xs">In: <?= htmlspecialchars($row['requested_punch_in']) ?></span><br>
                                                <span class="text-xs">Out: <?= htmlspecialchars($row['requested_punch_out']) ?></span>
                                            </td>
                                            <td><span class="text-xs"><?= nl2br(htmlspecialchars($row['reason'] ?: 'N/A')) ?></span></td>
                                            <td>
                                                <?php if ($row['status'] === 'Approved'): ?>
                                                    <span class="change-badge change-badge-approved">Approved</span>
                                                <?php elseif ($row['status'] === 'Rejected'): ?>
                                                    <span class="change-badge change-badge-rejected">Rejected</span>
                                                    <?php if (!empty($row['reject_reason'])): ?>
                                                        <div class="text-xs mt-2"><?= htmlspecialchars($row['reject_reason']) ?></div>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="change-badge change-badge-pending">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($row['status'] === 'Pending'): ?>
                                                    <div class="d-flex gap-2 align-items-center flex-wrap">
                                                        <form method="POST" class="mb-0">
                                                            <input type="hidden" name="request_id" value="<?= (int) $row['id'] ?>">
                                                            <input type="hidden" name="action_type" value="approve">
                                                            <button type="submit" class="btn change-btn change-btn-secondary btn-sm mb-0">Approve</button>
                                                        </form>
                                                        <button
                                                            type="button"
                                                            class="btn change-btn change-btn-danger btn-sm mb-0"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#rejectRequestModal"
                                                            data-request-id="<?= (int) $row['id'] ?>"
                                                            data-employee-name="<?= htmlspecialchars($row['name'], ENT_QUOTES) ?>"
                                                            data-attendance-date="<?= htmlspecialchars($row['attendance_date'], ENT_QUOTES) ?>"
                                                        >
                                                            Reject
                                                        </button>
                                                    </div>
                                                <?php else: ?>
                                                    <?php if ($row['status'] === 'Approved'): ?>
                                                        <span class="text-xs text-success">
                                                            Approved by <?= htmlspecialchars($row['approved_by_name'] ?? 'Admin') ?>
                                                        </span>
                                                    <?php elseif ($row['status'] === 'Rejected'): ?>
                                                        <span class="text-xs text-danger">
                                                            Rejected by <?= htmlspecialchars($row['approved_by_name'] ?? 'Admin') ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-xs text-muted">Processed</span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No attendance change requests found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade change-modal" id="rejectRequestModal" tabindex="-1" aria-labelledby="rejectRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="rejectRequestModalLabel">Reject Attendance Change Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="request_id" id="reject_request_id">
                    <input type="hidden" name="action_type" value="reject">

                    <p class="text-sm mb-3">
                        <strong>Employee:</strong> <span id="reject_employee_name"></span><br>
                        <strong>Date:</strong> <span id="reject_attendance_date"></span>
                    </p>

                    <label for="reject_reason" class="form-label">Reason for Rejection</label>
                    <textarea
                        name="reject_reason"
                        id="reject_reason"
                        class="form-control"
                        rows="4"
                        placeholder="Enter rejection reason"
                        required
                    ></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn change-btn btn-outline-secondary mb-0" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn change-btn change-btn-danger mb-0">Reject Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const rejectRequestModal = document.getElementById('rejectRequestModal');

    rejectRequestModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const requestId = button.getAttribute('data-request-id');
        const employeeName = button.getAttribute('data-employee-name');
        const attendanceDate = button.getAttribute('data-attendance-date');

        document.getElementById('reject_request_id').value = requestId;
        document.getElementById('reject_employee_name').textContent = employeeName;
        document.getElementById('reject_attendance_date').textContent = attendanceDate;
        document.getElementById('reject_reason').value = '';
    });
</script>

<?php
$stmt->close();
include("footer.php");
?>
