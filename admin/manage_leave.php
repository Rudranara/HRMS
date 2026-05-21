<?php
include("header.php");

if (!isset($_SESSION['admin_id'])) {
    echo "<div class='alert alert-danger'>You must be logged in to access this page.</div>";
    exit;
}
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor2/autoload.php';
// Function to send email notification
function sendLeaveNotification($employee_email, $employee_name, $leave_type, $leave_reason, $start_date, $end_date, $status, $reject_reason = null)
{
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'amaresh.sahoo101@gmail.com';
        $mail->Password = 'hwzfavtumiqhcwtu'; // App-specific password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom('amaresh.sahoo101@gmail.com', 'Leave Management');
        $mail->addAddress($employee_email);
        $mail->isHTML(true);
        $mail->Subject = "Your Leave Request Has Been $status";
        $message = "
            <h3>Leave Request Update</h3>
            <p><strong>Employee Name:</strong> $employee_name</p>
            <p><strong>Leave Type:</strong> $leave_type</p>
            <p><strong>Reason:</strong> $leave_reason</p>
            <p><strong>Start Date:</strong> $start_date</p>
            <p><strong>End Date:</strong> $end_date</p>
            <p><strong>Status:</strong> $status</p>";

        if ($status === 'Rejected' && !empty($reject_reason)) {
            $message .= "<p><strong>Rejection Reason:</strong> $reject_reason</p>";
        }
        $mail->Body = $message;
        $mail->send();
        $mail->clearAddresses(); // Clean up addresses
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>Failed to send email notification. Error: {$mail->ErrorInfo}</div>";
    }
}

// Force Approve leave request if user clicked "Approve Anyway"
if (isset($_POST['force_approve_leave'])) {
    $leave_id = $_POST['force_approve_leave'];
    $employee_id = $_POST['employee_id'];
    $leave_type = $_POST['leave_type'];
    $start_date = new DateTime($_POST['start_date']);
    $end_date = new DateTime($_POST['end_date']);
    $days_requested = $start_date->diff($end_date)->days + 1;

    // Fetch holidays and weekly offs again
    $stmt = $conn->prepare("
        SELECT start_date 
        FROM events 
        WHERE start_date BETWEEN ? AND ?
        AND (event_type = 'holiday' OR event_type = 'weekly_off')
    ");
    $start_date_str = $start_date->format('Y-m-d');
    $end_date_str = $end_date->format('Y-m-d');
    $stmt->bind_param("ss", $start_date_str, $end_date_str);
    $stmt->execute();
    $result = $stmt->get_result();
    $event_dates = [];
    while ($row = $result->fetch_assoc()) {
        $event_dates[] = $row['start_date'];
    }
    $stmt->close();

    // Recalculate actual_days
    $actual_days = 0;
    $period = new DatePeriod($start_date, new DateInterval('P1D'), (new DateTime($end_date_str))->modify('+1 day'));
    foreach ($period as $date) {
        if (!in_array($date->format('Y-m-d'), $event_dates)) {
            $actual_days++;
        }
    }

    // Update employee leaves (even if negative)
    if ($leave_type !== 'compensatory_leave' && $leave_type !== 'maternity_leave' && $leave_type !== 'paternity_leave') {
        $leave_column = strtolower($leave_type);
        $stmt = $conn->prepare("UPDATE employees SET $leave_column = $leave_column - ?, total_leave = total_leave - ? WHERE id = ?");
        $stmt->bind_param("iii", $actual_days, $actual_days, $employee_id);
        $stmt->execute();
        $stmt->close();
    }

    // Approve the leave
    $decision_date = date('Y-m-d');
    $approver_id = $_SESSION['admin_id'];
    $approver_query = $conn->prepare("SELECT name FROM admins WHERE id = ?");
    $approver_query->bind_param("i", $approver_id);
    $approver_query->execute();
    $approver_query->bind_result($approver_name);
    $approver_query->fetch();
    $approver_query->close();
    $approver_type = 'Admin';

    $stmt = $conn->prepare("
        UPDATE leave_requests 
        SET status = 'Approved', 
            actual_days = ?, 
            leave_approve_reject_date = ?, 
            approved_by_id = ?, 
            approved_by_name = ?, 
            approved_by_type = ?
        WHERE id = ?
    ");
    $stmt->bind_param("isissi", $actual_days, $decision_date, $approver_id, $approver_name, $approver_type, $leave_id);
    $stmt->execute();
    $stmt->close();

    // Send email notification
    $stmt = $conn->prepare("SELECT e.name, e.email, lr.leave_type, lr.reject_reason, lr.start_date, lr.end_date 
        FROM leave_requests lr
        JOIN employees e ON lr.employee_id = e.id
        WHERE lr.id = ?");
    $stmt->bind_param("i", $leave_id);
    $stmt->execute();
    $stmt->bind_result($employee_name, $employee_email, $leave_type, $leave_reason, $start_date, $end_date);
    $stmt->fetch();
    $stmt->close();
    sendLeaveNotification($employee_email, $employee_name, $leave_type, $leave_reason, $start_date, $end_date, 'Approved');

    echo "<div class='alert alert-success' style='position: fixed; top: 10px; right: 10px; z-index: 1000;'>Leave approved successfully even though leaves were insufficient! Actual days counted: $actual_days</div>
    <script>
        setTimeout(function() {
            location.replace(document.referrer);
        }, 2000);
    </script>";
    exit;
}

// Approve leave request
if (isset($_POST['approve_leave'])) {
    $leave_id = $_POST['leave_id'];
    $employee_id = $_POST['employee_id'];
    $leave_type = $_POST['leave_type'];
    $start_date = new DateTime($_POST['start_date']);
    $end_date = new DateTime($_POST['end_date']);
    $days_requested = $start_date->diff($end_date)->days + 1;
    // Get holidays and weekly offs within leave period
    $stmt = $conn->prepare("
        SELECT start_date 
        FROM events 
        WHERE start_date BETWEEN ? AND ?
        AND (event_type = 'holiday' OR event_type = 'weekly_off')
    ");
    $start_date_str = $start_date->format('Y-m-d');
    $end_date_str = $end_date->format('Y-m-d');
    $stmt->bind_param("ss", $start_date_str, $end_date_str);
    $stmt->execute();
    $result = $stmt->get_result();
    $event_dates = [];
    while ($row = $result->fetch_assoc()) {
        $event_dates[] = $row['start_date'];
    }
    $stmt->close();
    // Calculate actual working days excluding events
    $actual_days = 0;
    $period = new DatePeriod($start_date, new DateInterval('P1D'), $end_date->modify('+1 day'));
    foreach ($period as $date) {
        if (!in_array($date->format('Y-m-d'), $event_dates)) {
            $actual_days++;
        }
    }
    if ($leave_type !== 'compensatory_leave' && $leave_type !== 'maternity_leave' && $leave_type !== 'paternity_leave') {
        $stmt = $conn->prepare("SELECT sick_leave, casual_leave, paid_leave, other_leave, total_leave FROM employees WHERE id = ?");
        $stmt->bind_param("i", $employee_id);
        $stmt->execute();
        $employee_leaves = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $leave_column = strtolower($leave_type);
        if ($employee_leaves[$leave_column] < $actual_days) {
            echo "
    <div class='alert alert-danger' style='position: fixed; top: 10px; right: 10px; z-index: 1000;'>
        Only {$employee_leaves[$leave_column]} $leave_type(s) are available. Approval failed.<br>
        <button id='approveAnyway' class='btn btn-primary btn-sm mt-2'>Approve Anyway</button>
    </div>
    <script>
        document.getElementById('approveAnyway').addEventListener('click', function() {
            var form = document.createElement('form');
            form.method = 'post';
            form.action = '';

            var leaveId = document.createElement('input');
            leaveId.type = 'hidden';
            leaveId.name = 'force_approve_leave';
            leaveId.value = '{$leave_id}';
            form.appendChild(leaveId);

            var employeeId = document.createElement('input');
            employeeId.type = 'hidden';
            employeeId.name = 'employee_id';
            employeeId.value = '{$employee_id}';
            form.appendChild(employeeId);

            var leaveType = document.createElement('input');
            leaveType.type = 'hidden';
            leaveType.name = 'leave_type';
            leaveType.value = '{$leave_type}';
            form.appendChild(leaveType);

            var startDate = document.createElement('input');
            startDate.type = 'hidden';
            startDate.name = 'start_date';
            startDate.value = '{$start_date_str}';
            form.appendChild(startDate);

            var endDate = document.createElement('input');
            endDate.type = 'hidden';
            endDate.name = 'end_date';
            endDate.value = '{$end_date_str}';
            form.appendChild(endDate);

            document.body.appendChild(form);
            form.submit();
        });
    </script>
    ";
            exit;
        }
        $stmt = $conn->prepare("
            UPDATE employees 
            SET $leave_column = $leave_column - ?, total_leave = total_leave - ? 
            WHERE id = ?
        ");
        $stmt->bind_param("iii", $actual_days, $actual_days, $employee_id);
        $stmt->execute();
        $stmt->close();
    }
    $decision_date = date('Y-m-d'); // Get today's date
        // Get approver details
        $approver_id = $_SESSION['admin_id'];
        $approver_query = $conn->prepare("SELECT name FROM admins WHERE id = ?");
        $approver_query->bind_param("i", $approver_id);
        $approver_query->execute();
        $approver_query->bind_result($approver_name);
        $approver_query->fetch();
        $approver_query->close();
        $approver_type = 'Admin';

        $stmt = $conn->prepare("
        UPDATE leave_requests 
        SET status = 'Approved', 
            actual_days = ?, 
            leave_approve_reject_date = ?, 
            approved_by_id = ?, 
            approved_by_name = ?, 
            approved_by_type = ?
        WHERE id = ?
    ");
    $stmt->bind_param("isissi", $actual_days, $decision_date, $approver_id, $approver_name, $approver_type, $leave_id);
    $stmt->execute();
    $stmt->close();
     // Get employee details for email
     $stmt = $conn->prepare("SELECT e.name, e.email, lr.leave_type, lr.reject_reason, lr.start_date, lr.end_date 
     FROM leave_requests lr
     JOIN employees e ON lr.employee_id = e.id
     WHERE lr.id = ?");
$stmt->bind_param("i", $leave_id);
$stmt->execute();
$stmt->bind_result($employee_name, $employee_email, $leave_type, $leave_reason, $start_date, $end_date);
$stmt->fetch();
$stmt->close();
       // Send approval email
       sendLeaveNotification($employee_email, $employee_name, $leave_type, $leave_reason, $start_date, $end_date, 'Approved');

    echo "<div class='alert alert-success' style='position: fixed; top: 10px; right: 10px; z-index: 1000;'>Leave request approved successfully! Actual days counted: $actual_days</div>
    <script>
        setTimeout(function() {
            location.replace(document.referrer); // Redirect to previous page
        }, 2000);
    </script>";
}
// Reject leave request with reason
if (isset($_POST['action']) && isset($_POST['id'])) {
    $action = $_POST['action'];
    $leave_id = $_POST['id'];
    $status = ($action === 'approve') ? 'Approved' : 'Rejected';
    $reject_reason = $_POST['reject_reason'] ?? null;
    $decision_date = date('Y-m-d'); // Set current date


      // Get approver details
      $approver_id = $_SESSION['admin_id'];
      $approver_query = $conn->prepare("SELECT name FROM admins WHERE id = ?");
      $approver_query->bind_param("i", $approver_id);
      $approver_query->execute();
      $approver_query->bind_result($approver_name);
      $approver_query->fetch();
      $approver_query->close();
      $approver_type = 'Admin';

      $stmt = $conn->prepare("
      UPDATE leave_requests 
      SET status = ?, 
          reject_reason = ?, 
           leave_approve_reject_date = ?, 
          approved_by_id = ?, 
          approved_by_name = ?, 
          approved_by_type = ? 
      WHERE id = ?
  ");
  $stmt->bind_param("sssissi", $status, $reject_reason, $decision_date, $approver_id, $approver_name, $approver_type, $leave_id);
    
    if ($stmt->execute()) {
       
        echo "<div class='alert alert-success' style='position: fixed; top: 10px; right: 10px; z-index: 1000;'>Leave request has been $status.</div>
        <script>
            setTimeout(function() {
                location.replace(document.referrer); // Redirect to previous page
            }, 2000);
        </script>";

        // Get employee details for email
        $stmt = $conn->prepare("
            SELECT e.name, e.email, lr.leave_type, lr.reject_reason, lr.start_date, lr.end_date 
            FROM leave_requests lr
            JOIN employees e ON lr.employee_id = e.id
            WHERE lr.id = ?
        ");
        $stmt->bind_param("i", $leave_id);
        $stmt->execute();
        $stmt->bind_result($employee_name, $employee_email, $leave_type, $leave_reason, $start_date, $end_date);
        $stmt->fetch();
        $stmt->close();

        sendLeaveNotification($employee_email, $employee_name, $leave_type, $leave_reason, $start_date, $end_date, $status, $reject_reason);
    } else {
        echo "<div class='alert alert-danger'>Failed to update leave status. Please try again.</div>";
    }
}


$year = $_GET['year'] ?? '';
$month = $_GET['month'] ?? '';

$query = "SELECT lr.id AS leave_request_id, lr.*, e.name AS employee_name 
          FROM leave_requests lr 
          JOIN employees e ON lr.employee_id = e.id 
          WHERE 1";

if (!empty($year)) {
    $query .= " AND YEAR(lr.leave_apply_date) = ?";
}
if (!empty($month)) {
    $query .= " AND MONTH(lr.leave_apply_date) = ?";
}

$query .= " ORDER BY lr.created_at DESC";

$stmt = $conn->prepare($query);

// Bind parameters dynamically
if (!empty($year) && !empty($month)) {
    $stmt->bind_param("ii", $year, $month);
} elseif (!empty($year)) {
    $stmt->bind_param("i", $year);
} elseif (!empty($month)) {
    $stmt->bind_param("i", $month);
}

$stmt->execute();
$result = $stmt->get_result();

// Delete leave request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int) $_POST['delete_id'];
    $delete_employee_id = (int) ($_POST['delete_employee_id'] ?? 0);
    $delete_leave_type = trim($_POST['delete_leave_type'] ?? '');
    $delete_start_date = $_POST['delete_start_date'] ?? '';
    $delete_end_date = $_POST['delete_end_date'] ?? '';
    $stmt = $conn->prepare("DELETE FROM leave_requests WHERE id = ? AND employee_id = ? AND leave_type = ? AND start_date = ? AND end_date = ? LIMIT 1");
    $stmt->bind_param("iisss", $delete_id, $delete_employee_id, $delete_leave_type, $delete_start_date, $delete_end_date);
    if ($stmt->execute()) {
        echo "
        <div class='alert alert-success' style='position: fixed; top: 10px; right: 10px; z-index: 1000;'>
            Leave Request Deleted
        </div>
        <script>
            setTimeout(function() {
                window.location.href = 'manage_leave';
            }, 2000);
        </script>";
    } else {
        echo "<div class='alert alert-danger'>Failed to delete leave request. Please try again.</div>";
    }
    $stmt->close();
}
?>
<style>
.manage-leave-page {
    padding-top: 2rem;
    padding-bottom: 2.5rem;
}

.manage-leave-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.4rem;
}

.manage-leave-title {
    margin: 0;
    color: #0f172a;
    font-size: 1.45rem;
    font-weight: 800;
    letter-spacing: -0.03em;
}

.manage-leave-filter-card,
.manage-leave-table-card {
    border: 1px solid #e5eaf1;
    border-radius: 26px;
    background: #ffffff;
    box-shadow: 0 28px 60px rgba(15, 23, 42, 0.07);
}

.manage-leave-filter-card {
    padding: 1.25rem;
    margin-bottom: 1.5rem;
}

.manage-leave-filter-grid {
    display: grid;
    grid-template-columns: minmax(180px, 0.9fr) minmax(180px, 0.95fr) auto minmax(260px, 1.35fr);
    gap: 0.85rem;
    align-items: end;
}

.manage-leave-field {
    min-width: 0;
}

.manage-leave-field label {
    display: block;
    margin-bottom: 0.45rem;
    color: #64748b;
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.09em;
    text-transform: uppercase;
}

.manage-leave-field .form-control,
.manage-leave-field textarea.form-control,
.manage-leave-page .form-group .form-control {
    min-height: 44px;
    border: 1px solid #d7deea;
    border-radius: 14px;
    padding: 0.65rem 0.85rem;
    color: #0f172a;
    background: #f8fafc;
    box-shadow: none;
    font-size: 0.92rem;
}

.manage-leave-field .form-control:focus,
.manage-leave-page .form-group .form-control:focus {
    border-color: #9aa8bc;
    background: #ffffff;
    box-shadow: 0 0 0 0.2rem rgba(148, 163, 184, 0.16);
}

.manage-leave-filter-btn,
.manage-leave-action-btn,
.manage-leave-modal .btn,
.manage-leave-modal .btn-close {
    border-radius: 14px;
}

.manage-leave-filter-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 48px;
    min-width: 128px;
    padding: 0.78rem 1.3rem;
    border: none;
    background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
    color: #ffffff;
    box-shadow: 0 18px 32px rgba(15, 23, 42, 0.18);
    font-size: 0.77rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.manage-leave-search {
    position: relative;
}

.manage-leave-search .form-control {
    padding-right: 1rem;
}

.manage-leave-table-card {
    overflow: hidden;
}

.manage-leave-table-wrap {
    overflow-x: auto;
    padding: 0 1.5rem 1.5rem;
}

.manage-leave-table {
    width: 100%;
    margin-bottom: 0;
}

.manage-leave-table thead th {
    border-bottom: 1px solid #e8edf3;
    background: #f8fafc;
    color: #64748b;
    font-size: 0.73rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    padding: 1rem 0.95rem;
    white-space: nowrap;
}

.manage-leave-table tbody td {
    padding: 1rem 0.95rem;
    border-bottom: 1px solid #eef2f7;
    color: #1f2937;
    font-size: 0.92rem;
    vertical-align: middle;
}

.manage-leave-table tbody tr:last-child td {
    border-bottom: none;
}

.manage-leave-table tbody tr:hover {
    background: #f8fafc;
}

.manage-leave-status {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.42rem 0.8rem;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.manage-leave-status.status-pending {
    background: #eaf2ff;
    color: #275ea8;
}

.manage-leave-status.status-rejected,
.manage-leave-status.status-on-leave {
    background: #fee2e2;
    color: #b42318;
}

.manage-leave-status.status-approved {
    background: #dff5e6;
    color: #21543a;
}

.manage-leave-status.status-holiday {
    background: #fff1cf;
    color: #9a6700;
}

.manage-leave-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.55rem;
}

.manage-leave-action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 40px;
    padding: 0.68rem 0.95rem;
    border: none;
    font-size: 0.74rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    text-decoration: none;
}

.manage-leave-action-btn.btn-success {
    background: linear-gradient(135deg, #dff5e6 0%, #c8ebd5 100%);
    border: 1px solid #b9dec8;
    color: #21543a;
}

.manage-leave-action-btn.btn-danger {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    border: 1px solid #f7b4b4;
    color: #9f1d1d;
}

.manage-leave-action-btn.btn-primary {
    background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
    border: 1px solid #111827;
    color: #ffffff;
}

.manage-leave-modal .modal-dialog {
    max-width: 760px;
}

.manage-leave-modal .modal-content {
    border: 1px solid #e5eaf1;
    border-radius: 26px;
    overflow: hidden;
    box-shadow: 0 30px 70px rgba(15, 23, 42, 0.18);
}

.manage-leave-modal .modal-header,
.manage-leave-modal .modal-footer {
    padding: 1.15rem 1.35rem;
    border-color: #e9eef5;
}

.manage-leave-modal .modal-body {
    padding: 1.25rem 1.35rem 1.35rem;
}

.manage-leave-modal .modal-title {
    color: #0f172a;
    font-size: 1.2rem;
    font-weight: 800;
    letter-spacing: -0.02em;
}

.manage-leave-modal .btn-secondary {
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    border: 1px solid #d7deea;
    color: #475569;
}

.manage-leave-modal .btn-success {
    background: linear-gradient(135deg, #dff5e6 0%, #c8ebd5 100%);
    border: 1px solid #b9dec8;
    color: #21543a;
}

.manage-leave-modal .btn-danger {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    border: 1px solid #f7b4b4;
    color: #9f1d1d;
}

.manage-leave-modal .btn-primary {
    background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
    border: 1px solid #111827;
    color: #ffffff;
}

.manage-leave-detail-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.9rem;
}

.manage-leave-detail-item,
.manage-leave-section-card {
    border: 1px solid #e8edf3;
    border-radius: 18px;
    background: #f8fafc;
    padding: 0.95rem 1rem;
}

.manage-leave-detail-item strong,
.manage-leave-section-title,
.manage-leave-page .form-group label {
    display: block;
    margin-bottom: 0.4rem;
    color: #64748b;
    font-size: 0.7rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.manage-leave-detail-value {
    color: #0f172a;
    font-size: 0.95rem;
    font-weight: 700;
    line-height: 1.45;
    word-break: break-word;
}

.manage-leave-detail-wide {
    grid-column: 1 / -1;
}

.manage-leave-list {
    margin: 0;
    padding-left: 1rem;
    color: #1f2937;
}

.manage-leave-list li + li {
    margin-top: 0.35rem;
}

@media (max-width: 991.98px) {
    .manage-leave-filter-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 767.98px) {
    .manage-leave-page {
        padding-top: 1.25rem;
    }

    .manage-leave-filter-grid,
    .manage-leave-detail-grid {
        grid-template-columns: 1fr;
    }

    .manage-leave-filter-card,
    .manage-leave-table-wrap,
    .manage-leave-modal .modal-header,
    .manage-leave-modal .modal-body,
    .manage-leave-modal .modal-footer {
        padding-left: 1rem;
        padding-right: 1rem;
    }
}
</style>

<div class="container-fluid manage-leave-page">
    <div class="row">
        <div class="col-12">
            <div class="manage-leave-header">
                <h6 class="manage-leave-title">Manage Leave Requests</h6>
            </div>
        </div>
        <div class="col-12">
            <form method="GET" class="manage-leave-filter-card">
                <div class="manage-leave-filter-grid">
                <div class="manage-leave-field">
                    <label>Select Year</label>
                    <select name="year" class="form-control">
        <option value="">Select Year</option>
        <?php for ($y = 2022; $y <= date('Y'); $y++): ?>
            <option value="<?= $y ?>" <?= (isset($_GET['year']) && $_GET['year'] == $y) ? 'selected' : '' ?>><?= $y ?></option>
        <?php endfor; ?>
    </select>
                </div>
                <div class="manage-leave-field">
                    <label>Select Month</label>
                    <select name="month" class="form-control">
        <option value="">Select Month</option>
        <?php for ($m = 1; $m <= 12; $m++): ?>
            <option value="<?= $m ?>" <?= (isset($_GET['month']) && $_GET['month'] == $m) ? 'selected' : '' ?>>
                <?= date('F', mktime(0, 0, 0, $m, 10)) ?>
            </option>
        <?php endfor; ?>
    </select>
                </div>
                <div>
                    <button type="submit" class="manage-leave-filter-btn">Filter</button>
                </div>
                <div class="manage-leave-field manage-leave-search">
                    <label for="searchInput">Search Requests</label>
                    <input type="text" id="searchInput" class="form-control" placeholder="Search by employee, type, or status...">
                </div>
                </div>
            </form>
        </div>
       
        <div class="col-12">
            <div class="manage-leave-table-card mb-4">
                    <div class="manage-leave-table-wrap">
                        <table class="table align-items-center manage-leave-table">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Leave Type</th>
                                    <th>Apply Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['employee_name']) ?></td>
                                        <td><?= htmlspecialchars($row['leave_type']) ?></td>
                                        <td><?= htmlspecialchars($row['leave_apply_date']) ?></td>

                                        <td class="align-middle text-center text-sm">
                                            <?php if ($row['status'] == 'Pending') : ?>
                                                <span class="manage-leave-status status-pending"><?= ucfirst($row['status']) ?></span>
                                            <?php elseif ($row['status'] == 'Rejected') : ?>
                                                <span class="manage-leave-status status-rejected"><?= ucfirst($row['status']) ?></span>
                                            <?php elseif ($row['status'] == 'Approved') : ?>
                                                <span class="manage-leave-status status-approved"><?= ucfirst($row['status']) ?></span>
                                            <?php elseif ($row['status'] == 'On Leave') : ?>
                                                <span class="manage-leave-status status-on-leave"><?= ucfirst($row['status']) ?></span>
                                            <?php elseif ($row['status'] == 'Holiday') : ?>
                                                <span class="manage-leave-status status-holiday"><?= ucfirst($row['status']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="manage-leave-actions">
                                            <?php if ($row['status'] === 'Pending'): ?>
                                                <button class="btn btn-success btn-sm manage-leave-action-btn"
                                                    type="button"
                                                    onclick='openApproveModal(
                                                        <?= (int) $row['leave_request_id'] ?>,
                                                        <?= htmlspecialchars(json_encode($row['employee_name']), ENT_QUOTES, 'UTF-8') ?>,
                                                        <?= (int) $row['employee_id'] ?>,
                                                        <?= htmlspecialchars(json_encode($row['leave_type']), ENT_QUOTES, 'UTF-8') ?>,
                                                        <?= htmlspecialchars(json_encode($row['extra_time_from']), ENT_QUOTES, 'UTF-8') ?>,
                                                        <?= htmlspecialchars(json_encode($row['extra_time_to']), ENT_QUOTES, 'UTF-8') ?>,
                                                        <?= htmlspecialchars(json_encode($row['start_date']), ENT_QUOTES, 'UTF-8') ?>,
                                                        <?= htmlspecialchars(json_encode($row['end_date']), ENT_QUOTES, 'UTF-8') ?>,
                                                        <?= htmlspecialchars(json_encode($row['leave_apply_date']), ENT_QUOTES, 'UTF-8') ?>,
                                                        <?= htmlspecialchars(json_encode($row['leave_reason']), ENT_QUOTES, 'UTF-8') ?>
                                                    )'>
                                                    View/Approve
                                                </button>
                                                <a href="javascript:void(0);"
                                                    class="btn btn-danger btn-sm manage-leave-action-btn"
                                                    onclick="openRejectModal(<?= (int) $row['leave_request_id'] ?>)">Reject</a>
                                                    <button type="button"
                                                        class="btn btn-danger btn-sm manage-leave-action-btn"
                                                        onclick="deleteLeaveRequest(<?= (int) $row['leave_request_id'] ?>, <?= (int) $row['employee_id'] ?>, <?= htmlspecialchars(json_encode($row['leave_type']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($row['start_date']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($row['end_date']), ENT_QUOTES, 'UTF-8') ?>)">Delete</button>
                                            <?php else: ?>
                                                <a href="javascript:void(0);"
                                                    class="btn btn-primary btn-sm manage-leave-action-btn"
                                                    onclick="openViewModal(<?= (int) $row['leave_request_id'] ?>)">View</a>
                                                <button type="button"
                                                    class="btn btn-danger btn-sm manage-leave-action-btn"
                                                    onclick="deleteLeaveRequest(<?= (int) $row['leave_request_id'] ?>, <?= (int) $row['employee_id'] ?>, <?= htmlspecialchars(json_encode($row['leave_type']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($row['start_date']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($row['end_date']), ENT_QUOTES, 'UTF-8') ?>)">Delete</button>
                                            <?php endif; ?>
                                            </div>
                                        </td>

                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
            </div>
        </div>
    </div>
</div>
<!-- Approve Modal -->
<div class="modal fade manage-leave-modal" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="approveModalLabel">Approve Leave Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="leave_id" id="approveLeaveId">
                    <input type="hidden" name="employee_id" id="approveEmployeeId">
                    <input type="hidden" name="leave_type" id="approveLeaveType">
                    <input type="hidden" name="start_date" id="approveStartDate">
                    <input type="hidden" name="end_date" id="approveEndDate">

                    <div class="manage-leave-detail-grid">
                        <div class="manage-leave-detail-item"><strong>Employee</strong><div class="manage-leave-detail-value" id="approveEmployeeName"></div></div>
                        <div class="manage-leave-detail-item"><strong>Leave Type</strong><div class="manage-leave-detail-value" id="approveModalLeaveType"></div></div>
                        <div class="manage-leave-detail-item"><strong>Start Date</strong><div class="manage-leave-detail-value" id="approveModalStartDate"></div></div>
                        <div class="manage-leave-detail-item"><strong>End Date</strong><div class="manage-leave-detail-value" id="approveModalEndDate"></div></div>
                        <div class="manage-leave-detail-item"><strong>Apply Date</strong><div class="manage-leave-detail-value" id="approveModalLeaveApplyDate"></div></div>
                        <div id="extraWorkPeriodSection" class="manage-leave-section-card" style="display: none;">
                            <div class="manage-leave-section-title">Extra Work Period</div>
                            <div class="manage-leave-detail-value"><strong>Extra Time From:</strong> <span id="approveModalExtratimeFrom"></span></div>
                            <div class="manage-leave-detail-value" style="margin-top:0.45rem;"><strong>Extra Time To:</strong> <span id="approveModalExtratimeTo"></span></div>
                        </div>
                        <div class="manage-leave-section-card manage-leave-detail-wide">
                            <div class="manage-leave-section-title">Leave Reason</div>
                            <div class="manage-leave-detail-value" id="approveModalLeaveReason"></div>
                        </div>
                        <div class="manage-leave-section-card manage-leave-detail-wide">
                            <div class="manage-leave-section-title">Available Leaves</div>
                            <div class="manage-leave-detail-value" id="availableLeaves"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="approve_leave" class="btn btn-success">Approve</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Reject Modal -->
<div class="modal fade manage-leave-modal" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="rejectModalLabel">Reject Leave Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="rejectLeaveId">
                    <input type="hidden" name="action" value="reject">
                    <div class="form-group">
                        <label for="reject_reason">Reason for Rejection</label>
                        <textarea name="reject_reason" id="reject_reason" class="form-control" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-danger">Submit</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>
<form method="POST" id="deleteLeaveForm" style="display: none;">
    <input type="hidden" name="delete_id" id="deleteLeaveId" value="">
    <input type="hidden" name="delete_employee_id" id="deleteLeaveEmployeeId" value="">
    <input type="hidden" name="delete_leave_type" id="deleteLeaveType" value="">
    <input type="hidden" name="delete_start_date" id="deleteLeaveStartDate" value="">
    <input type="hidden" name="delete_end_date" id="deleteLeaveEndDate" value="">
</form>
<!-- View Details Modal -->
<div class="modal fade manage-leave-modal" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewModalLabel">Leave Request Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="manage-leave-detail-grid">
                    <div class="manage-leave-detail-item"><strong>Leave Type</strong><div class="manage-leave-detail-value" id="viewLeaveType"></div></div>
                    <div class="manage-leave-detail-item"><strong>Status</strong><div class="manage-leave-detail-value" id="viewStatus"></div></div>
                    <div class="manage-leave-detail-item"><strong>Apply Date</strong><div class="manage-leave-detail-value" id="viewleave_apply_date"></div></div>
                    <div class="manage-leave-detail-item"><strong>Approve/Reject Date</strong><div class="manage-leave-detail-value" id="viewLeaveApproveRejectDate"></div></div>
                    <div class="manage-leave-detail-item"><strong>Start Date</strong><div class="manage-leave-detail-value" id="viewStartDate"></div></div>
                    <div class="manage-leave-detail-item"><strong>End Date</strong><div class="manage-leave-detail-value" id="viewEndDate"></div></div>
                    <div class="manage-leave-detail-item"><strong>Actual Days</strong><div class="manage-leave-detail-value" id="viewActualDays"></div></div>
                    <div class="manage-leave-detail-item"><strong>Approve/Reject By</strong><div class="manage-leave-detail-value"><span id="viewApprovedByName"></span> As a (<span id="viewApprovedByType"></span>)</div></div>
                    <div id="viewExtraWorkPeriodSection" class="manage-leave-section-card" style="display: none;">
                        <div class="manage-leave-section-title">Extra Work Period</div>
                        <div class="manage-leave-detail-value"><strong>Extra Time From:</strong> <span id="viewExtraTimeFrom"></span></div>
                        <div class="manage-leave-detail-value" style="margin-top:0.45rem;"><strong>Extra Time To:</strong> <span id="viewExtraTimeTo"></span></div>
                    </div>
                    <div class="manage-leave-section-card manage-leave-detail-wide">
                        <div class="manage-leave-section-title">Reason</div>
                        <div class="manage-leave-detail-value" id="viewLeaveReason"></div>
                    </div>
                    <div class="manage-leave-section-card manage-leave-detail-wide">
                        <div class="manage-leave-section-title">Supporting Document</div>
                        <div class="manage-leave-detail-value"><a id="viewDocument" href="#" target="_blank">View</a></div>
                    </div>
                    <div id="viewRejectReasonSection" class="manage-leave-section-card manage-leave-detail-wide" style="display: none;">
                        <div class="manage-leave-section-title">Reject Reason</div>
                        <div class="manage-leave-detail-value" id="viewRejectReason"></div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('searchInput').addEventListener('keyup', function () {
        let value = this.value.toLowerCase();
        let rows = document.querySelectorAll('tbody tr');
        rows.forEach(row => {
            let text = row.innerText.toLowerCase();
            row.style.display = text.includes(value) ? '' : 'none';
        });
    });
</script>

<script>
function deleteLeaveRequest(id, employeeId, leaveType, startDate, endDate) {
    if (!confirm('Are you sure you want to delete this leave application?')) {
        return;
    }

    document.getElementById('deleteLeaveId').value = id;
    document.getElementById('deleteLeaveEmployeeId').value = employeeId;
    document.getElementById('deleteLeaveType').value = leaveType;
    document.getElementById('deleteLeaveStartDate').value = startDate;
    document.getElementById('deleteLeaveEndDate').value = endDate;
    document.getElementById('deleteLeaveForm').submit();
}

function openApproveModal(id, name, employeeId, leaveType, extratimeFrom, extratimeTo, startDate, endDate, leaveApplyDate, leaveReason) {
    // Fill hidden input fields
    document.getElementById('approveLeaveId').value = id;
    document.getElementById('approveEmployeeId').value = employeeId;
    document.getElementById('approveLeaveType').value = leaveType;
    document.getElementById('approveStartDate').value = startDate;
    document.getElementById('approveEndDate').value = endDate;

    // Fill visible data
    document.getElementById('approveEmployeeName').innerText = name;
    document.getElementById('approveModalLeaveType').innerText = leaveType;
    document.getElementById('approveModalStartDate').innerText = startDate;
    document.getElementById('approveModalEndDate').innerText = endDate;
    document.getElementById('approveModalLeaveApplyDate').innerText = leaveApplyDate;
    document.getElementById('approveModalLeaveReason').innerText = leaveReason;

    // Handle Extra Work Period fields
    if (leaveType === 'compensatory_leave') {
        document.getElementById('approveModalExtratimeFrom').innerText = extratimeFrom;
        document.getElementById('approveModalExtratimeTo').innerText = extratimeTo;
        document.getElementById('extraWorkPeriodSection').style.display = 'block';
    } else {
        document.getElementById('extraWorkPeriodSection').style.display = 'none';
    }

    // Fetch available leaves via AJAX
    fetch(`fetch_employee_leaves?employee_id=${employeeId}`)
        .then(response => response.json())
        .then(data => {
            let formattedLeaves = `
                <ul class="manage-leave-list">
                    <li><strong>Sick Leave:</strong> ${data.sick_leave}</li>
                    <li><strong>Casual Leave:</strong> ${data.casual_leave}</li>
                    <li><strong>Paid Leave:</strong> ${data.paid_leave}</li>
                    <li><strong>Other Leave:</strong> ${data.other_leave}</li>
                    <li><strong>Total Leave:</strong> ${data.total_leave}</li>
                </ul>
            `;
            document.getElementById('availableLeaves').innerHTML = formattedLeaves;
        })
        .catch(error => {
            console.error("Error fetching leave data:", error);
            document.getElementById('availableLeaves').innerText = "Error fetching leave data.";
        });

    // Show modal
    var approveModal = new bootstrap.Modal(document.getElementById('approveModal'));
    approveModal.show();
}

</script>
<script>
    function openRejectModal(id) {
        document.getElementById('rejectLeaveId').value = id;
        var rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'));
        rejectModal.show();
    }
    function openViewModal(id) {
    fetch(`fetch_leave_details?id=${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('viewLeaveType').innerText = data.leave_type;
            document.getElementById('viewLeaveReason').innerText = data.leave_reason;
            document.getElementById('viewStartDate').innerText = data.start_date;
            document.getElementById('viewEndDate').innerText = data.end_date;
            document.getElementById('viewleave_apply_date').innerText = data.leave_apply_date;
            document.getElementById('viewLeaveApproveRejectDate').innerText = data.leave_approve_reject_date;
            document.getElementById('viewActualDays').innerText = data.actual_days;
            document.getElementById('viewApprovedByName').innerText = data.approved_by_name;
            document.getElementById('viewApprovedByType').innerText = data.approved_by_type;
            document.getElementById('viewDocument').href = data.supporting_document;
            document.getElementById('viewStatus').innerText = data.status;

            // Handle Extra Work Period visibility
            if (data.leave_type === 'compensatory_leave') {
                document.getElementById('viewExtraWorkPeriodSection').style.display = 'block';
                document.getElementById('viewExtraTimeFrom').innerText = data.extra_time_from || '-';
                document.getElementById('viewExtraTimeTo').innerText = data.extra_time_to || '-';
            } else {
                document.getElementById('viewExtraWorkPeriodSection').style.display = 'none';
            }

            // Handle Reject Reason visibility
            if (data.status === 'Rejected') {
                document.getElementById('viewRejectReasonSection').style.display = 'block';
                document.getElementById('viewRejectReason').innerText = data.reject_reason || '-';
            } else {
                document.getElementById('viewRejectReasonSection').style.display = 'none';
            }

            var viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
            viewModal.show();
        });
}

</script>
<?php include("footer.php"); ?>
