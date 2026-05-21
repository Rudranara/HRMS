<?php
include("header.php");
if (!isset($_SESSION['employee_id'])) {
    echo "<div class='alert alert-danger'>You must be logged in to access this page.</div>";
    exit;
}

$decision_date = date('Y-m-d');

$employee_id = $_SESSION['employee_id'];
// Get the employee's role
$query = $conn->prepare("SELECT role FROM employees WHERE id = ?");
$query->bind_param("i", $employee_id);
$query->execute();
$query->bind_result($role);
$query->fetch();
$query->close();
if ($role !== 'Manager') {
    echo "<div class='alert alert-danger'>Access denied. You do not have permission to view this page.</div>";
    exit;
}
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
// Function to send email notification
function sendLeaveNotification($employee_email, $manager_email, $employee_name, $leave_type, $leave_reason, $start_date, $end_date, $status, $reject_reason = null)
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
    // $days_requested = $start_date->diff($end_date)->days + 1;

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
        
        $allowedLeaves = ['sick_leave', 'casual_leave', 'paid_leave', 'other_leave'];

        $leave_column = strtolower($leave_type);

        if (!in_array($leave_column, $allowedLeaves)) {
            exit('Invalid leave type');
        }

        $stmt = $conn->prepare("UPDATE employees SET $leave_column = $leave_column - ?, total_leave = total_leave - ? WHERE id = ?");
        $stmt->bind_param("iii", $actual_days, $actual_days, $employee_id);
        $stmt->execute();
        $stmt->close();
    }

    // Approve the leave
    $approver_id = $_SESSION['employee_id'];
    $approver_query = $conn->prepare("SELECT name FROM employees WHERE id = ?");
    $approver_query->bind_param("i", $approver_id);
    $approver_query->execute();
    $approver_query->bind_result($approver_name);
    $approver_query->fetch();
    $approver_query->close();
    $approver_type = 'employee';

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

    
    $stmt = $conn->prepare("
        SELECT 
            e.name AS employee_name,
            e.email AS employee_email,
            m.email AS manager_email,
            lr.leave_reason
        FROM leave_requests lr
        JOIN employees e ON lr.employee_id = e.id
        JOIN employees m ON lr.manager = m.id
        WHERE lr.id = ?
    ");


    $stmt->bind_param("i", $leave_id);
    $stmt->execute();
    $mailData = $stmt->get_result()->fetch_assoc();
    $stmt->close();


    sendLeaveNotification(
        $mailData['employee_email'],
        $mailData['manager_email'],
        $mailData['employee_name'],
        $leave_type,
        $mailData['leave_reason'],
        $start_date_str,
        $end_date_str,
        'Approved'
    );



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
    // $days_requested = $start_date->diff($end_date)->days + 1;
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

        $allowedLeaves = ['sick_leave', 'casual_leave', 'paid_leave', 'other_leave'];

        $leave_column = strtolower($leave_type);

        if (!in_array($leave_column, $allowedLeaves)) {
            exit('Invalid leave type');
        }


        if ($employee_leaves[$leave_column] < $actual_days) {
            if ($employee_leaves[$leave_column] < $actual_days) {
                // Show a popup with Approve Anyway button
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
    

    // Get approver details
    $approver_id = $_SESSION['employee_id'];
    $approver_query = $conn->prepare("SELECT name FROM employees WHERE id = ?");
    $approver_query->bind_param("i", $approver_id);
    $approver_query->execute();
    $approver_query->bind_result($approver_name);
    $approver_query->fetch();
    $approver_query->close();
    $approver_type = 'employee';

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
    $stmt = $conn->prepare("
        SELECT 
            e.name,
            e.email,
            m.email,
            lr.leave_reason
        FROM leave_requests lr
        JOIN employees e ON lr.employee_id = e.id
        JOIN employees m ON lr.manager = m.id
        WHERE lr.id = ?
    ");
    $stmt->bind_param("i", $leave_id);
    $stmt->execute();
    $stmt->bind_result(
        $employee_name,
        $employee_email,
        $manager_email,
        $leave_reason
    );
    $stmt->fetch();
    $stmt->close();


    // Send approval email
    sendLeaveNotification(
        $employee_email,
        $manager_email,
        $employee_name,
        $leave_type,
        $leave_reason,
        $start_date_str,
        $end_date_str,
        'Approved'
    );


    echo "<div class='alert alert-success' style='position: fixed; top: 10px; right: 10px; z-index: 1000;'>Leave request approved successfully! Actual days counted: $actual_days</div>
    <script>
        setTimeout(function() {
            location.replace(document.referrer); // Redirect to previous page
        }, 2000);
    </script>";
}
if (isset($_POST['action']) && isset($_POST['id'])) {
    $action = $_POST['action'];
    $leave_id = $_POST['id'];
    $status = ($action === 'approve') ? 'Approved' : 'Rejected';
    $reject_reason = $_POST['reject_reason'] ?? null;

    // Get approver details
    $approver_id = $_SESSION['employee_id'];
    $approver_query = $conn->prepare("SELECT name FROM employees WHERE id = ?");
    $approver_query->bind_param("i", $approver_id);
    $approver_query->execute();
    $approver_query->bind_result($approver_name);
    $approver_query->fetch();
    $approver_query->close();
    $approver_type = 'employee';

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
            SELECT 
                e.name,
                e.email,
                m.email,
                lr.leave_type,
                lr.leave_reason,
                lr.start_date,
                lr.end_date
            FROM leave_requests lr
            JOIN employees e ON lr.employee_id = e.id
            JOIN employees m ON lr.manager = m.id
            WHERE lr.id = ?
        ");
        $stmt->bind_param("i", $leave_id);
        $stmt->execute();
        $stmt->bind_result(
            $employee_name,
            $employee_email,
            $manager_email,
            $leave_type,
            $leave_reason,
            $start_date,
            $end_date
        );
        $stmt->fetch();
        $stmt->close();


        sendLeaveNotification(
            $employee_email,
            $manager_email,
            $employee_name,
            $leave_type,
            $leave_reason,
            $start_date,
            $end_date,
            $status,
            $reject_reason
        );



        
    } else {
        echo "<div class='alert alert-danger'>Failed to update leave status. Please try again.</div>";
    }
}

// Get selected year and month from GET parameters
$year = $_GET['year'] ?? '';
$month = $_GET['month'] ?? '';

// Start building the base query
$query = "SELECT lr.*, e.name AS employee_name 
          FROM leave_requests lr 
          JOIN employees e ON lr.employee_id = e.id 
          WHERE lr.manager = ?";

// Append filters conditionally
if (!empty($year)) {
    $query .= " AND YEAR(lr.leave_apply_date) = ?";
}
if (!empty($month)) {
    $query .= " AND MONTH(lr.leave_apply_date) = ?";
}

$query .= " ORDER BY lr.created_at DESC";

$stmt = $conn->prepare($query);

// Bind parameters based on conditions
if (!empty($year) && !empty($month)) {
    $stmt->bind_param("iii", $employee_id, $year, $month);
} elseif (!empty($year)) {
    $stmt->bind_param("ii", $employee_id, $year);
} elseif (!empty($month)) {
    $stmt->bind_param("ii", $employee_id, $month);
} else {
    $stmt->bind_param("i", $employee_id);
}

$stmt->execute();
$result = $stmt->get_result();


// Delete leave request
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM leave_requests WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
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
    :root {
        --approve-leave-shell-bg: linear-gradient(180deg, #f8fafc 0%, #eef3f8 100%);
        --approve-leave-shell-border: rgba(148, 163, 184, 0.18);
        --approve-leave-shell-shadow: 0 24px 56px rgba(15, 23, 42, 0.08);
    }

    .approve-leave-page {
        padding-top: 0.95rem !important;
        padding-bottom: 1.2rem !important;
    }

    .approve-leave-title {
        margin: 0;
        color: #0f172a;
        font-size: 1.15rem;
        font-weight: 800;
        letter-spacing: -0.02em;
    }

    .approve-leave-filter-card,
    .approve-leave-table-card {
        border: 1px solid var(--approve-leave-shell-border);
        border-radius: 28px;
        background: var(--approve-leave-shell-bg);
        box-shadow: var(--approve-leave-shell-shadow);
        overflow: hidden;
    }

    .approve-leave-filter-wrap,
    .approve-leave-table-shell {
        background: #ffffff;
    }

    .approve-leave-filter-wrap {
        padding: 1rem;
    }

    .approve-leave-filter-form .row {
        --bs-gutter-x: 0.8rem;
        --bs-gutter-y: 0.65rem;
        align-items: end;
    }

    .approve-leave-filter-form label {
        margin-bottom: 0.5rem;
        color: #475569;
        font-size: 0.74rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .approve-leave-filter-form .form-control {
        min-height: 46px;
        border-radius: 15px;
        border: 1px solid #d9e2ec;
        box-shadow: none;
        padding: 0.78rem 0.9rem;
    }

    .approve-leave-filter-form .form-control:focus {
        border-color: #94a3b8;
        box-shadow: 0 0 0 4px rgba(148, 163, 184, 0.16);
    }

    .approve-leave-filter-btn {
        min-height: 46px;
        width: 100%;
        border-radius: 15px;
        border: 0;
        background: linear-gradient(135deg, #123b76 0%, #1f4c8f 100%) !important;
        color: #ffffff !important;
        font-size: 0.76rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        box-shadow: none;
    }

    .approve-leave-search-input {
        margin-bottom: 0 !important;
    }

    .approve-leave-table-wrap {
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
    }

    .approve-leave-table {
        margin-bottom: 0;
        min-width: 980px;
    }

    .approve-leave-table thead th {
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

    .approve-leave-table tbody td {
        padding: 1rem;
        border-bottom: 1px solid #eef2f7;
        vertical-align: middle;
        color: #1f2937;
        font-size: 0.88rem;
    }

    .approve-leave-table tbody tr:hover {
        background: #fbfdff;
    }

    .approve-leave-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .approve-leave-status .badge {
        border-radius: 999px;
        padding: 0.52rem 0.82rem;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        border: 1px solid transparent;
        box-shadow: none;
    }

    .approve-leave-status .bg-gradient-primary,
    .approve-leave-status .bg-primary {
        background: #e8f0ff !important;
        color: #1d4ed8 !important;
        border-color: #bfd4ff;
    }

    .approve-leave-status .bg-gradient-danger {
        background: #fff1f2 !important;
        color: #dc2626 !important;
        border-color: #fecdd3;
    }

    .approve-leave-status .bg-gradient-warning {
        background: #fff7db !important;
        color: #b45309 !important;
        border-color: #f8df9c;
    }

    .approve-leave-action {
        min-height: 36px;
        padding: 0.58rem 0.78rem;
        border-radius: 12px;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        box-shadow: none !important;
    }

    .approve-leave-action.btn-success {
        background: #ecfdf3;
        border-color: #bbf7d0;
        color: #15803d;
    }

    .approve-leave-action.btn-success:hover,
    .approve-leave-action.btn-success:focus {
        background: #dcfce7;
        border-color: #86efac;
        color: #166534;
    }

    .approve-leave-action.btn-danger {
        background: #fff1f2;
        border-color: #fecdd3;
        color: #c24153;
    }

    .approve-leave-action.btn-danger:hover,
    .approve-leave-action.btn-danger:focus {
        background: #ffe4e8;
        border-color: #fda4af;
        color: #9f1239;
    }

    .approve-leave-action.btn-primary {
        background: #e9f2ff;
        border-color: #c7dafc;
        color: #1d4f91;
    }

    .approve-leave-action.btn-primary:hover,
    .approve-leave-action.btn-primary:focus {
        background: #dce9ff;
        border-color: #b5cffd;
        color: #153d74;
    }

    .approve-leave-modal .modal-content {
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 24px;
        box-shadow: 0 26px 60px rgba(15, 23, 42, 0.16);
        overflow: hidden;
    }

    .approve-leave-modal .modal-header,
    .approve-leave-modal .modal-footer {
        background: #ffffff;
        border-color: #eef2f7;
    }

    .approve-leave-modal .modal-body {
        background: #f8fafc;
    }

    .approve-leave-modal .modal-title {
        color: #0f172a;
        font-size: 1rem;
        font-weight: 800;
    }

    .approve-leave-modal .modal-body p,
    .approve-leave-modal .modal-body li,
    .approve-leave-modal .modal-body label {
        color: #334155;
        line-height: 1.6;
    }

    .approve-leave-modal .form-control {
        min-height: 44px;
        border-radius: 14px;
        border: 1px solid #d9e2ec;
        box-shadow: none;
    }

    .approve-leave-modal textarea.form-control {
        min-height: 108px;
    }

    .approve-leave-modal .btn-success {
        background: linear-gradient(135deg, #123b76 0%, #1f4c8f 100%) !important;
        border-color: #1f4c8f !important;
        color: #ffffff !important;
    }

    @media (max-width: 767.98px) {
        .approve-leave-shell-wrap {
            padding-left: 0.35rem !important;
            padding-right: 0.35rem !important;
        }

        .approve-leave-page {
            padding-top: 0.6rem !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            padding-bottom: 0.85rem !important;
            --bs-gutter-x: 0;
        }

        .approve-leave-page > .col-12 {
            padding-left: 0;
            padding-right: 0;
        }

        .approve-leave-title {
            font-size: 0.98rem;
            line-height: 1.25;
        }

        .approve-leave-filter-card,
        .approve-leave-table-card {
            width: 100%;
            margin-left: 0;
            margin-right: 0;
            border-radius: 22px;
        }

        .approve-leave-filter-wrap {
            padding: 0.82rem 0.78rem;
        }

        .approve-leave-filter-form .row {
            --bs-gutter-x: 0;
            --bs-gutter-y: 0.65rem;
        }

        .approve-leave-filter-form .row > [class*="col-"] {
            padding-left: 0;
            padding-right: 0;
        }

        .approve-leave-filter-form .col-md-3.d-flex.align-items-end.mt-4 {
            margin-top: 0 !important;
        }

        .approve-leave-filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.55rem;
        }

        .approve-leave-year-col,
        .approve-leave-month-col,
        .approve-leave-filter-btn-col {
            flex: 1 1 calc(33.333% - 0.37rem);
            max-width: calc(33.333% - 0.37rem);
        }

        .approve-leave-filter-btn-col {
            align-items: flex-end;
        }

        .approve-leave-search-col {
            flex: 1 1 100%;
            max-width: 100%;
            margin-top: 0 !important;
            align-items: stretch !important;
        }

        .approve-leave-search-input {
            width: 100%;
        }

        .approve-leave-filter-form .form-control,
        .approve-leave-filter-btn {
            min-height: 42px;
            border-radius: 14px;
            font-size: 0.76rem;
        }

        .approve-leave-table thead th,
        .approve-leave-table tbody td {
            padding: 0.82rem 0.78rem;
        }
    }
</style>
<div class="container-fluid py-4 approve-leave-shell-wrap">
    <div class="row approve-leave-page">
        <div class="col-12 mb-4 d-flex align-items-center">
            <h6 class="mb-0 approve-leave-title">Manage Leave Requests</h6>
        </div>

        <div class="col-12 mb-3">
            <div class="card approve-leave-filter-card">
                <div class="approve-leave-filter-wrap">
                    <form method="GET" class="approve-leave-filter-form">
                        <div class="row gx-2 approve-leave-filter-row">
                            <div class="col-md-3 approve-leave-year-col">
                                <label>Select Year</label>
                                <select name="year" class="form-control">
        <option value="">Select Year</option>
        <?php for ($y = 2022; $y <= date('Y'); $y++): ?>
            <option value="<?= $y ?>" <?= (isset($_GET['year']) && $_GET['year'] == $y) ? 'selected' : '' ?>><?= $y ?></option>
        <?php endfor; ?>
    </select>
                            </div>
                            <div class="col-md-3 approve-leave-month-col">
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
                            <div class="col-md-3 d-flex align-items-end mt-4 approve-leave-filter-btn-col">
                                <button type="submit" class="btn btn-primary approve-leave-filter-btn">Filter</button>
                            </div>
                            <div class="col-md-3 d-flex align-items-end mt-4 approve-leave-search-col">
                                <input type="text" id="approveLeaveSearchInput" class="form-control approve-leave-search-input" placeholder="Search by employee, type, or status...">
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card mb-4 approve-leave-table-card">
                <div class="card-body px-0 pt-0 pb-2 approve-leave-table-shell">
                    <div class="table-responsive p-0 approve-leave-table-wrap">
                        <table class="table align-items-center mb-0 approve-leave-table">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Leave Type</th>
                                    <th>Apply Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="approveLeaveTableBody">
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['employee_name']) ?></td>
                                        <td><?= htmlspecialchars($row['leave_type']) ?></td>
                                        <td><?= htmlspecialchars($row['leave_apply_date']) ?></td>

                                        <td class="align-middle text-center text-sm approve-leave-status">
                                            <?php if ($row['status'] == 'Pending') : ?>
                                                <span class="badge badge-sm bg-gradient-primary"><?= ucfirst($row['status']) ?></span>
                                            <?php elseif ($row['status'] == 'Rejected') : ?>
                                                <span class="badge badge-sm bg-gradient-danger"><?= ucfirst($row['status']) ?></span>
                                            <?php elseif ($row['status'] == 'Approved') : ?>
                                                <span class="badge badge-sm bg-primary"><?= ucfirst($row['status']) ?></span>
                                            <?php elseif ($row['status'] == 'On Leave') : ?>
                                                <span class="badge badge-sm bg-gradient-danger"><?= ucfirst($row['status']) ?></span>
                                            <?php elseif ($row['status'] == 'Holiday') : ?>
                                                <span class="badge badge-sm bg-gradient-warning"><?= ucfirst($row['status']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($row['status'] === 'Pending'): ?>
                                                <button class="btn btn-success btn-sm approve-leave-action"
                                                        onclick="openApproveModal(
                                                            <?= (int) $row['id'] ?>,
                                                            <?= htmlspecialchars(json_encode($row['employee_name']), ENT_QUOTES, 'UTF-8') ?>,
                                                            <?= (int) $row['employee_id'] ?>,
                                                            <?= htmlspecialchars(json_encode($row['leave_type']), ENT_QUOTES, 'UTF-8') ?>,
                                                            <?= htmlspecialchars(json_encode($row['extra_time_from']), ENT_QUOTES, 'UTF-8') ?>,
                                                            <?= htmlspecialchars(json_encode($row['extra_time_to']), ENT_QUOTES, 'UTF-8') ?>,
                                                            <?= htmlspecialchars(json_encode($row['start_date']), ENT_QUOTES, 'UTF-8') ?>,
                                                            <?= htmlspecialchars(json_encode($row['end_date']), ENT_QUOTES, 'UTF-8') ?>,
                                                            <?= htmlspecialchars(json_encode($row['leave_apply_date']), ENT_QUOTES, 'UTF-8') ?>,
                                                            <?= htmlspecialchars(json_encode($row['leave_reason']), ENT_QUOTES, 'UTF-8') ?>,
                                                            <?= htmlspecialchars(json_encode($row['supporting_document'] ?? ''), ENT_QUOTES, 'UTF-8') ?>

                                                        )">

                                                    View/Approve
                                                </button>
                                                <a href="javascript:void(0);"
                                                    class="btn btn-danger btn-sm approve-leave-action"
                                                    onclick="openRejectModal(<?= $row['id'] ?>)">Reject</a>
                                                    <a href="manage_leave?delete_id=<?= $row['id'] ?>"
                                                    class="btn btn-danger btn-sm approve-leave-action"
                                                    onclick="return confirm('Are you sure you want to delete this leave application?');">
                                                    Delete
                                                </a>
                                            <?php else: ?>
                                                <a href="javascript:void(0);"
                                                    class="btn btn-primary btn-sm approve-leave-action"
                                                    onclick="openViewModal(<?= $row['id'] ?>)">View</a>
                                                <a href="manage_leave?delete_id=<?= $row['id'] ?>"
                                                    class="btn btn-danger btn-sm approve-leave-action"
                                                    onclick="return confirm('Are you sure you want to delete this leave application?');">
                                                    Delete
                                                </a>
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
<!-- Approve Modal -->
<div class="modal fade approve-leave-modal" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
    <div class="modal-dialog">
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
                    

                    <p><strong>Employee:</strong> <span id="approveEmployeeName"></span></p>
                    <p><strong>Leave Type:</strong> <span id="approveModalLeaveType"></span></p>

                    <div id="extraWorkPeriodSection" style="display: none;">
    <h5 style="text-align: center;"><strong>Extra Work Period</strong></h5>
    <p><strong>Extra Time From:</strong> <span id="approveModalExtratimeFrom"></span></p>
    <p><strong>Extra Time To:</strong> <span id="approveModalExtratimeTo"></span></p>
</div>
                    <p><strong>Start Date:</strong> <span id="approveModalStartDate"></span></p>
                    <p><strong>End Date:</strong> <span id="approveModalEndDate"></span></p>

                    <p><strong>Apply Date:</strong> <span id="approveModalLeaveApplyDate"></span></p>
                    <p><strong>Leave Reason:</strong> <span id="approveModalLeaveReason"></span></p>

                    <div id="approveProofSection" style="display:none;">
                        <p><strong>Supporting Document:</strong>
                            <a id="approveProofLink" href="#" target="_blank"
                               class="btn btn-sm btn-outline-primary ms-2">
                                View Proof
                            </a>
                        </p>
                    </div>

                    <p><strong>Available Leaves:</strong> <span id="availableLeaves"></span></p>
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
<div class="modal fade approve-leave-modal" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
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
<!-- View Details Modal -->
<div class="modal fade approve-leave-modal" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewModalLabel">Leave Request Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
    <p><strong>Leave Type:</strong> <span id="viewLeaveType"></span></p>
    <p><strong>Reason:</strong> <span id="viewLeaveReason"></span></p>
    <p><strong>Apply Date:</strong> <span id="viewleave_apply_date"></span></p>
    <p><strong>Start Date:</strong> <span id="viewStartDate"></span></p>
    <p><strong>End Date:</strong> <span id="viewEndDate"></span></p>

    <!-- Extra Work Period Section -->
    <div id="viewExtraWorkPeriodSection" style="display: none;">
        <h5 style="text-align: center;"><strong>Extra Work Period</strong></h5>
        <p><strong>Extra Time From:</strong> <span id="viewExtraTimeFrom"></span></p>
        <p><strong>Extra Time To:</strong> <span id="viewExtraTimeTo"></span></p>
    </div>

    <p><strong>Approve/Reject Date:</strong> <span id="viewLeaveApproveRejectDate"></span></p>
    <p><strong>Actual Days:</strong> <span id="viewActualDays"></span></p>
    <p><strong>Approve/Reject By:</strong> <span id="viewApprovedByName"></span> As a (<span id="viewApprovedByType"></span>)</p>

    <p><strong>Supporting Document:</strong> <a id="viewDocument" href="#" target="_blank">View</a></p>
    <p><strong>Status:</strong> <span id="viewStatus"></span></p>

    <!-- Reject Reason Section -->
    <div id="viewRejectReasonSection" style="display: none;">
        <p><strong>Reject Reason:</strong> <span id="viewRejectReason"></span></p>
    </div>
</div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('approveLeaveSearchInput');
        const tableBody = document.getElementById('approveLeaveTableBody');

        if (!searchInput || !tableBody) {
            return;
        }

        const filterRows = function () {
            const value = searchInput.value.trim().toLowerCase();
            const rows = tableBody.querySelectorAll('tr');

            rows.forEach(function (row) {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(value) ? '' : 'none';
            });
        };

        searchInput.addEventListener('input', filterRows);
        searchInput.addEventListener('keyup', filterRows);
    });
</script>
<script>
function openApproveModal(id, name, employeeId, leaveType,extratimeFrom, extratimeTo,startDate, endDate, leaveApplyDate,leaveReason, proof)
 {
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

        // reset first
        document.getElementById("approveProofLink").href = "#";

        // ===== HANDLE SUPPORTING DOCUMENT =====
        if (proof && proof !== "NULL" && proof.trim() !== "") {
            document.getElementById("approveProofLink").href = proof;
            document.getElementById("approveProofSection").style.display = "block";
        } else {
            document.getElementById("approveProofSection").style.display = "none";
        }



    // Fetch available leaves via AJAX
    fetch(`fetch_employee_leaves?employee_id=${employeeId}`)
        .then(response => response.json())
        .then(data => {
            let formattedLeaves = `
                <ul>
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
    fetch(`fetch_leave_details2?id=${id}`)
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
