<?php
include("header.php");
require 'db_connection.php';
require_once '../includes/attendance_change_request_helper.php';

if (!isset($_SESSION['employee_id'])) {
    echo "<div class='alert alert-danger'>You must be logged in to apply for attendance change.</div>";
    exit;
}

ensureAttendanceChangeRequestTable($conn);

$employee_id = (int) $_SESSION['employee_id'];
$message = '';
$messageType = 'success';
$selected_date = $_POST['attendance_date'] ?? date('Y-m-d');
$selected_status = $_POST['requested_status'] ?? 'Present';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $attendance_date = trim($_POST['attendance_date'] ?? '');
    $requested_status = trim($_POST['requested_status'] ?? 'Present');
    $requested_punch_in = trim($_POST['requested_punch_in'] ?? '');
    $requested_punch_out = trim($_POST['requested_punch_out'] ?? '');
    $reason = trim($_POST['reason'] ?? '');

    if ($attendance_date === '' || $requested_status === '') {
        $message = "Date and requested status are required.";
        $messageType = 'danger';
    } elseif ($attendance_date > date('Y-m-d')) {
        $message = "You cannot apply for a future date.";
        $messageType = 'danger';
    } elseif ($requested_status === 'Present' && ($requested_punch_in === '' || $requested_punch_out === '')) {
        $message = "Punch in time and punch out time are required for Present status.";
        $messageType = 'danger';
    } elseif ($requested_status === 'Present' && strtotime($attendance_date . ' ' . $requested_punch_out) <= strtotime($attendance_date . ' ' . $requested_punch_in)) {
        $message = "Punch out time must be later than punch in time.";
        $messageType = 'danger';
    } else {
        $pendingCheck = $conn->prepare("
            SELECT id
            FROM attendance_change_requests
            WHERE employee_id = ?
              AND attendance_date = ?
              AND status = 'Pending'
            LIMIT 1
        ");
        $pendingCheck->bind_param("is", $employee_id, $attendance_date);
        $pendingCheck->execute();
        $pendingCheck->store_result();

        if ($pendingCheck->num_rows > 0) {
            $message = "A pending request already exists for this date.";
            $messageType = 'warning';
        } else {
            $currentAttendance = fetchAttendanceForDate($conn, $employee_id, $attendance_date);
            $current_status = $currentAttendance['status'] ?? 'Absent';
            $current_punch_in_time = $currentAttendance['punch_in_time'] ?? null;
            $current_punch_out_time = $currentAttendance['punch_out_time'] ?? null;
            $applied_at = date('Y-m-d H:i:s');
            $store_punch_in = $requested_status === 'Present' ? $requested_punch_in : '00:00:00';
            $store_punch_out = $requested_status === 'Present' ? $requested_punch_out : '00:00:00';

            $insert = $conn->prepare("
                INSERT INTO attendance_change_requests (
                    employee_id, attendance_date, requested_status, requested_punch_in, requested_punch_out,
                    current_status, current_punch_in_time, current_punch_out_time,
                    reason, status, applied_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?)
            ");
            $insert->bind_param(
                "isssssssss",
                $employee_id,
                $attendance_date,
                $requested_status,
                $store_punch_in,
                $store_punch_out,
                $current_status,
                $current_punch_in_time,
                $current_punch_out_time,
                $reason,
                $applied_at
            );

            if ($insert->execute()) {
                $message = "Attendance change request submitted successfully.";
                $messageType = 'success';
                $selected_date = date('Y-m-d');
            } else {
                $message = "Failed to submit the request. Please try again.";
                $messageType = 'danger';
            }
            $insert->close();
        }

        $pendingCheck->close();
    }
}

$attendancePreview = fetchAttendanceForDate($conn, $employee_id, $selected_date);
?>

<style>
    :root {
        --attendance-change-shell: linear-gradient(180deg, #f8fbff 0%, #eef4fb 100%);
        --attendance-change-border: rgba(148, 163, 184, 0.18);
        --attendance-change-shadow: 0 24px 56px rgba(15, 23, 42, 0.08);
    }

    .attendance-change-page {
        padding-top: 0.95rem !important;
        padding-bottom: 1.2rem !important;
    }

    .attendance-change-alert {
        border-radius: 18px;
        border: 1px solid rgba(148, 163, 184, 0.16);
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
        padding: 0.95rem 1rem;
        font-weight: 600;
    }

    .attendance-change-card {
        border: 1px solid var(--attendance-change-border);
        border-radius: 28px;
        background: var(--attendance-change-shell);
        box-shadow: var(--attendance-change-shadow);
        overflow: hidden;
    }

    .attendance-change-card .card-header {
        padding: 1.2rem 1.25rem 0.35rem;
        border-bottom: 0;
        background: transparent;
    }

    .attendance-change-card .card-body {
        padding: 1.15rem 1.25rem 1.25rem;
    }

    .attendance-change-form-shell {
        padding: 1.1rem;
        border-radius: 22px;
        background: rgba(255, 255, 255, 0.9);
    }

    .attendance-change-title {
        margin: 0;
        color: #0f172a;
        font-size: 1.15rem;
        font-weight: 800;
        letter-spacing: -0.02em;
    }

    .attendance-change-subtitle {
        margin-top: 0.3rem;
        color: #64748b;
        font-size: 0.92rem;
        line-height: 1.55;
    }

    .attendance-change-form .row {
        --bs-gutter-x: 0.9rem;
        --bs-gutter-y: 0.4rem;
        margin: 0;
    }

    .attendance-change-form .form-label {
        margin-bottom: 0.45rem;
        color: #64748b;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .attendance-change-form .form-control,
    .attendance-change-form .form-select {
        min-height: 48px;
        border-radius: 14px;
        border: 1px solid #d8e0ea;
        color: #334155;
        box-shadow: none;
        background-color: #ffffff;
        padding: 0.78rem 0.9rem;
    }

    .attendance-change-form .form-control:focus,
    .attendance-change-form .form-select:focus {
        border-color: #94a3b8;
        box-shadow: 0 0 0 4px rgba(148, 163, 184, 0.16);
    }

    .attendance-change-form textarea.form-control {
        min-height: 128px;
        resize: vertical;
        padding-top: 0.85rem;
    }

    .attendance-change-form .btn {
        min-height: 48px;
        border-radius: 15px;
        font-size: 0.76rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        padding-left: 1.05rem;
        padding-right: 1.05rem;
        box-shadow: none !important;
    }

    .attendance-change-form .bg-gradient-dark {
        background: linear-gradient(135deg, #123b76 0%, #1f4c8f 100%) !important;
        box-shadow: 0 16px 28px rgba(18, 59, 118, 0.18) !important;
    }

    .attendance-change-form .btn-outline-secondary {
        border-color: #0f172a;
        color: #ffffff;
        background: #0f172a;
    }

    .attendance-change-form .btn-outline-secondary:hover,
    .attendance-change-form .btn-outline-secondary:focus {
        border-color: #111827;
        color: #ffffff;
        background: #111827;
    }

    .attendance-change-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        margin-top: 0.2rem;
    }

    @media (max-width: 767.98px) {
        .attendance-change-page {
            padding-top: 0.6rem !important;
            padding-left: 0.3rem !important;
            padding-right: 0.3rem !important;
            padding-bottom: 0.8rem !important;
        }

        .attendance-change-card {
            border-radius: 22px;
        }

        .attendance-change-card .card-header {
            padding: 0.95rem 0.95rem 0.25rem;
        }

        .attendance-change-card .card-body {
            padding: 0.95rem;
        }

        .attendance-change-form-shell {
            padding: 0.95rem;
            border-radius: 18px;
        }

        .attendance-change-title {
            font-size: 1rem;
        }

        .attendance-change-subtitle {
            font-size: 0.84rem;
            line-height: 1.45;
        }

        .attendance-change-form .form-control,
        .attendance-change-form .form-select,
        .attendance-change-form .btn {
            min-height: 44px;
        }

        .attendance-change-form .form-label {
            font-size: 0.66rem;
            margin-bottom: 0.34rem;
        }

        .attendance-change-form textarea.form-control {
            min-height: 108px;
        }

        .attendance-change-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
        }

        .attendance-change-actions .btn {
            width: 100%;
            margin: 0 !important;
            min-height: 40px;
            padding-left: 0.7rem;
            padding-right: 0.7rem;
            border-radius: 14px;
            font-size: 0.68rem;
            letter-spacing: 0.04em;
        }
    }
</style>

<div class="container-fluid py-4 attendance-change-page">
    <?php if ($message !== ''): ?>
        <div class="alert alert-<?= $messageType ?> attendance-change-alert"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12 mb-4">
            <div class="card attendance-change-card">
                <div class="card-header pb-0">
                    <h6 class="attendance-change-title">Apply Attendance Change</h6>
                </div>
                <div class="card-body">
                    <div class="attendance-change-form-shell">
                    <form method="POST" class="attendance-change-form">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="attendance_date" class="form-label">Attendance Date</label>
                                <input type="date" class="form-control" id="attendance_date" name="attendance_date" max="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($selected_date) ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="requested_status" class="form-label">Request To Change Status</label>
                                <select class="form-control" id="requested_status" name="requested_status" required>
                                    <option value="Present" <?= $selected_status === 'Present' ? 'selected' : '' ?>>Present</option>
                                    <option value="Weekly Off" <?= $selected_status === 'Weekly Off' ? 'selected' : '' ?>>Weekly Off</option>
                                    <option value="Holiday" <?= $selected_status === 'Holiday' ? 'selected' : '' ?>>Holiday</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="requested_punch_in" class="form-label">Requested Punch In</label>
                                <input type="time" class="form-control" id="requested_punch_in" name="requested_punch_in">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="requested_punch_out" class="form-label">Requested Punch Out</label>
                                <input type="time" class="form-control" id="requested_punch_out" name="requested_punch_out">
                            </div>
                            <div class="col-12 mb-3">
                                <label for="reason" class="form-label">Reason</label>
                                <textarea class="form-control" id="reason" name="reason" rows="4" placeholder="Optional note for admin"></textarea>
                            </div>
                            <div class="col-12">
                                <div class="attendance-change-actions">
                                    <button type="submit" class="btn bg-gradient-dark mb-0">Submit Request</button>
                                    <a href="manage_attendance_change" class="btn btn-outline-secondary mb-0">My Requests</a>
                                </div>
                            </div>
                        </div>
                    </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const attendanceDateInput = document.getElementById('attendance_date');
    const requestedStatusInput = document.getElementById('requested_status');
    const requestedPunchInInput = document.getElementById('requested_punch_in');
    const requestedPunchOutInput = document.getElementById('requested_punch_out');

    function toggleTimeFields() {
        const isPresent = requestedStatusInput.value === 'Present';
        requestedPunchInInput.required = isPresent;
        requestedPunchOutInput.required = isPresent;
        requestedPunchInInput.disabled = !isPresent;
        requestedPunchOutInput.disabled = !isPresent;
        if (!isPresent) {
            requestedPunchInInput.value = '';
            requestedPunchOutInput.value = '';
        }
    }

    requestedStatusInput.addEventListener('change', toggleTimeFields);
    toggleTimeFields();
</script>

<?php include("footer.php"); ?>
