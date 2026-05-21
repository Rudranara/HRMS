<?php
include("header.php");
// Check if the employee is logged in
if (!isset($_SESSION['employee_id'])) {
    echo "<div class='alert alert-danger'>You must be logged in to manage your leaves.</div>";
    exit;
}
$employee_id = $_SESSION['employee_id']; // Get employee ID from session
require 'db_connection.php';
// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int) $_POST['delete_id'];
    $delete_leave_type = trim($_POST['delete_leave_type'] ?? '');
    $delete_start_date = $_POST['delete_start_date'] ?? '';
    $delete_end_date = $_POST['delete_end_date'] ?? '';
    $stmt = $conn->prepare("DELETE FROM leave_requests WHERE id = ? AND employee_id = ? AND leave_type = ? AND start_date = ? AND end_date = ? AND LOWER(status) != 'approved' LIMIT 1");
    $stmt->bind_param("iisss", $delete_id, $employee_id, $delete_leave_type, $delete_start_date, $delete_end_date);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo "<div class='alert alert-success'>Leave application deleted successfully!</div>";
        } else {
            echo "<div class='alert alert-warning'>Approved leave requests cannot be deleted.</div>";
        }
    } else {
        echo "<div class='alert alert-danger'>Failed to delete leave application. Please try again.</div>";
    }

    $stmt->close();
}
// Fetch employee's leave applications
$stmt = $conn->prepare("SELECT leave_requests.id AS leave_request_id, leave_requests.* FROM leave_requests WHERE employee_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<style>
    :root {
        --manage-leave-shell-bg: linear-gradient(180deg, #f8fafc 0%, #eef3f8 100%);
        --manage-leave-shell-border: rgba(148, 163, 184, 0.18);
        --manage-leave-shell-shadow: 0 24px 56px rgba(15, 23, 42, 0.08);
    }

    .manage-leave-page {
        padding-top: 0.95rem !important;
        padding-bottom: 1.2rem !important;
    }

    .manage-leave-shell-wrap {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }

    .manage-leave-title {
        margin: 0;
        color: #0f172a;
        font-size: 1.15rem;
        font-weight: 800;
        letter-spacing: -0.02em;
    }

    .manage-leave-header-row {
        align-items: center;
    }

    .manage-leave-title-col {
        display: flex;
        align-items: center;
    }

    .manage-leave-action-col {
        text-align: right;
    }

    .manage-leave-actions {
        display: inline-flex;
        flex-wrap: wrap;
        gap: 0.55rem;
        justify-content: flex-end;
    }

    .manage-leave-cta {
        min-height: 46px;
        padding: 0.75rem 1rem;
        border-radius: 16px;
        border: 0;
        box-shadow: 0 16px 28px rgba(15, 23, 42, 0.12);
        font-size: 0.82rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .manage-leave-cta.manage-leave-navy {
        background: linear-gradient(135deg, #123b76 0%, #1f4c8f 100%) !important;
        color: #ffffff !important;
    }

    .manage-leave-card {
        border: 1px solid var(--manage-leave-shell-border);
        border-radius: 28px;
        background: var(--manage-leave-shell-bg);
        box-shadow: var(--manage-leave-shell-shadow);
        overflow: hidden;
    }

    .manage-leave-shell {
        background: #ffffff;
    }

    .manage-leave-wrap {
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
    }

    .manage-leave-table {
        margin-bottom: 0;
        min-width: 860px;
    }

    .manage-leave-table thead th {
        padding: 1rem;
        border-bottom: 1px solid #eef2f7;
        background: #f8fafc;
        color: #64748b;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .manage-leave-table tbody td {
        padding: 1rem;
        border-bottom: 1px solid #eef2f7;
        vertical-align: middle;
        color: #1f2937;
        font-size: 0.88rem;
    }

    .manage-leave-table tbody tr:hover {
        background: #fbfdff;
    }

    .manage-leave-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .manage-leave-status {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        padding: 0.52rem 0.82rem;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        border: 1px solid transparent;
        box-shadow: none;
    }

    .manage-leave-status.status-approved {
        background: #ecfdf3;
        color: #15803d;
        border-color: #bbf7d0;
    }

    .manage-leave-status.status-pending {
        background: #e8f0ff;
        color: #1d4ed8;
        border-color: #bfd4ff;
    }

    .manage-leave-status.status-rejected {
        background: #fff1f2;
        color: #dc2626;
        border-color: #fecdd3;
    }

    .manage-leave-action {
        min-height: 36px;
        padding: 0.58rem 0.78rem;
        border-radius: 12px;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        box-shadow: none !important;
    }

    .manage-leave-action.btn-primary {
        background: #e9f2ff;
        border-color: #c7dafc;
        color: #1d4f91;
    }

    .manage-leave-action.btn-primary:hover,
    .manage-leave-action.btn-primary:focus {
        background: #dce9ff;
        border-color: #b5cffd;
        color: #153d74;
    }

    .manage-leave-action.btn-danger {
        background: #fff1f2;
        border-color: #fecdd3;
        color: #c24153;
    }

    .manage-leave-action.btn-danger:hover,
    .manage-leave-action.btn-danger:focus {
        background: #ffe4e8;
        border-color: #fda4af;
        color: #9f1239;
    }

    .manage-leave-modal .modal-content {
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 24px;
        box-shadow: 0 26px 60px rgba(15, 23, 42, 0.16);
        overflow: hidden;
    }

    .manage-leave-modal .modal-header,
    .manage-leave-modal .modal-footer {
        background: #ffffff;
        border-color: #eef2f7;
    }

    .manage-leave-modal .modal-body {
        background: #f8fafc;
    }

    .manage-leave-modal .modal-title {
        color: #0f172a;
        font-size: 1rem;
        font-weight: 800;
    }

    .manage-leave-modal .modal-body p {
        margin-bottom: 0.85rem;
        color: #334155;
        line-height: 1.6;
    }

    @media (max-width: 767.98px) {
        .manage-leave-shell-wrap {
            padding-left: 0.35rem !important;
            padding-right: 0.35rem !important;
        }

        .manage-leave-page {
            padding-top: 0.6rem !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            padding-bottom: 0.85rem !important;
            --bs-gutter-x: 0;
        }

        .manage-leave-page > .col-12 {
            padding-left: 0;
            padding-right: 0;
        }

        .manage-leave-header-row {
            flex-wrap: nowrap;
            align-items: center;
            min-width: 0;
        }

        .manage-leave-title-col {
            flex: 1 1 auto;
            max-width: calc(100% - 230px);
            width: calc(100% - 230px);
            margin-bottom: 0.85rem !important;
            padding-right: 0.45rem;
            min-width: 0;
        }

        .manage-leave-action-col {
            flex: 0 0 230px;
            max-width: 230px;
            width: 230px;
            margin-bottom: 0.85rem !important;
            text-align: right !important;
        }

        .manage-leave-actions {
            width: 100%;
            gap: 0.35rem;
            justify-content: flex-end;
            flex-wrap: nowrap;
        }

        .manage-leave-title {
            font-size: 0.92rem;
            line-height: 1.18;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .manage-leave-cta {
            min-height: 38px;
            padding: 0.5rem 0.58rem;
            border-radius: 12px;
            font-size: 0.58rem;
            letter-spacing: 0.03em;
            box-shadow: none;
        }

        .manage-leave-card {
            border-radius: 22px;
        }

        .manage-leave-table thead th,
        .manage-leave-table tbody td {
            padding: 0.82rem 0.78rem;
        }
    }

    @media (max-width: 420px) {
        .manage-leave-title-col {
            max-width: calc(100% - 210px);
            width: calc(100% - 210px);
        }

        .manage-leave-action-col {
            flex: 0 0 210px;
            max-width: 210px;
            width: 210px;
        }

        .manage-leave-title {
            font-size: 0.84rem;
            white-space: normal;
            overflow: visible;
            text-overflow: clip;
            line-height: 1.18;
        }

        .manage-leave-cta {
            min-height: 36px;
            padding: 0.46rem 0.52rem;
            font-size: 0.54rem;
            letter-spacing: 0.02em;
            border-radius: 11px;
        }
    }
</style>
<div class="container-fluid py-4 manage-leave-shell-wrap">
      <div class="row manage-leave-page">
      <div class="col-12">
        <div class="row manage-leave-header-row">
            <div class="col-6 mb-4 manage-leave-title-col">
                <h6 class="mb-0 manage-leave-title">Manage Employees</h6>
            </div>
            <div class="col-6 mb-4 manage-leave-action-col">
                <div class="manage-leave-actions">
                    <a href="approve_leave" class="btn mb-0 manage-leave-cta manage-leave-navy">Approve Leave</a>
                    <a href="apply_leave" class="btn bg-gradient-dark mb-0 manage-leave-cta">Apply Leave</a>
                </div>
            </div>
        </div>
      </div>
        <div class="col-12">
          <div class="card mb-4 manage-leave-card">           
            <div class="card-body px-0 pt-0 pb-2 manage-leave-shell">
              <div class="table-responsive p-0 manage-leave-wrap">
                <table class="table align-items-center mb-0 manage-leave-table">
                    <thead>
                        <tr>
                            <th>Leave Type</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Apply Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <?php $status_class = 'status-' . strtolower(str_replace(' ', '-', trim($row['status']))); ?>
                            <tr>
                                <td><?= htmlspecialchars($row['leave_type']) ?></td>
                                <td><?= $row['start_date'] ?></td>
                                <td><?= $row['end_date'] ?></td>
                                <td><?= $row['leave_apply_date'] ?></td>
                                <td><span class="manage-leave-status <?= htmlspecialchars($status_class) ?>"><?= $row['status'] ?></span></td>

                                <td>
                                    <?php if ($row['status'] === 'Rejected'): ?>
                                        <a href="javascript:void(0);"
                                           class="btn btn-primary btn-sm manage-leave-action"
                                           onclick="openViewModal(<?= (int) $row['leave_request_id'] ?>)">View Reject Reason</a>
                                    <?php endif; ?>

                                    <?php if (strtolower($row['status']) !== 'approved'): ?>
                                        <button type="button"
                                                class="btn btn-danger btn-sm manage-leave-action"
                                                onclick="deleteLeaveRequest(<?= (int) $row['leave_request_id'] ?>, <?= htmlspecialchars(json_encode($row['leave_type']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($row['start_date']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($row['end_date']), ENT_QUOTES, 'UTF-8') ?>)">Delete</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                </div>
            </div>
          </div>
        </div>
      </div>
    </div>
<!-- End Navbar -->

<div class="modal fade manage-leave-modal" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewModalLabel">Leave Request Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
             
                <p><strong>Leave Reason:</strong> <span id="viewLeaveReason"></span></p>
                <p><strong>Reject Reason:</strong> <span id="viewRejectReason"></span></p>
                <p><strong>Rejected By:</strong> <span id="viewApprovedByName"></span> As a <span id="viewApprovedByType"></span></p>
                <p><strong>Rejected On:</strong> <span id="viewLeaveApproveRejectDate"></span></p>
                <p><strong>Supporting Document:</strong> <a id="viewDocument" href="#" target="_blank">View</a></p>
                <p><strong>Status:</strong> <span id="viewStatus"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<form method="POST" id="deleteLeaveForm" style="display: none;">
    <input type="hidden" name="delete_id" id="deleteLeaveId" value="">
    <input type="hidden" name="delete_leave_type" id="deleteLeaveType" value="">
    <input type="hidden" name="delete_start_date" id="deleteLeaveStartDate" value="">
    <input type="hidden" name="delete_end_date" id="deleteLeaveEndDate" value="">
</form>
<script>
      function deleteLeaveRequest(id, leaveType, startDate, endDate) {
        if (!confirm('Are you sure you want to delete this leave request?')) {
            return;
        }

        document.getElementById('deleteLeaveId').value = id;
        document.getElementById('deleteLeaveType').value = leaveType;
        document.getElementById('deleteLeaveStartDate').value = startDate;
        document.getElementById('deleteLeaveEndDate').value = endDate;
        document.getElementById('deleteLeaveForm').submit();
    }

      function openViewModal(id) {
        // Fetch leave request details using AJAX
        fetch(`fetch_leave_details?id=${id}`)
            .then(response => response.json())
            .then(data => {
                
                document.getElementById('viewLeaveReason').innerText = data.leave_reason;
                document.getElementById('viewRejectReason').innerText = data.reject_reason;

                document.getElementById('viewApprovedByName').innerText = data.approved_by_name;
                document.getElementById('viewApprovedByType').innerText = data.approved_by_type;
                document.getElementById('viewLeaveApproveRejectDate').innerText = data.leave_approve_reject_date;

                document.getElementById('viewDocument').href = data.supporting_document;

                
                document.getElementById('viewStatus').innerText = data.status;

                var viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
                viewModal.show();
            });
    }
</script>
<?php include("footer.php") ?>

