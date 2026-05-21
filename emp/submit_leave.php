<?php
session_start();
require 'db_connection.php';
date_default_timezone_set('Asia/Kolkata');


if (!isset($_SESSION['employee_logged_in']) || $_SESSION['employee_logged_in'] !== true) {
  header("Location: ../index");
  exit;
}
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
// Load PHPMailer via Composer autoload
require 'vendor/autoload.php';
// Check if the employee is logged in
if (!isset($_SESSION['employee_id'])) {
    echo "<div class='alert alert-danger'>You must be logged in to apply for leave.</div>";
    exit;
}
$employee_id = $_SESSION['employee_id'];
// Fetch the logged-in employee's manager
$query = $conn->prepare("SELECT manager FROM employees WHERE id = ?");
$query->bind_param("s", $employee_id);
$query->execute();
$query->bind_result($manager);
$query->fetch();
$query->close();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leave_type = trim($_POST['leave_type']);
    $extra_time_from = $_POST['extra_time_from'] ?? null;
    $extra_time_to = $_POST['extra_time_to'] ?? null;
    $leave_reason = trim($_POST['leave_reason']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $supporting_document_path = null;
    // Validation
    if (empty($leave_type) || empty($leave_reason) || empty($start_date) || empty($end_date)) {
        echo "<div class='alert alert-danger'>All fields are required except the supporting document.</div>";
    } elseif (strtotime($end_date) < strtotime($start_date)) {
        echo "<div class='alert alert-danger'>End date cannot be earlier than the start date.</div>";
    } elseif ($leave_type === 'compensatory_leave' && (empty($extra_time_from) || empty($extra_time_to))) {
        echo "<div class='alert alert-danger'>For compensatory leave, you must provide the extra work period.</div>";
    } else {
        $checkLeave = $conn->prepare("\n            SELECT id\n            FROM leave_requests\n            WHERE employee_id = ?\n              AND TRIM(LOWER(status)) IN ('pending', 'approved')\n              AND start_date <= ?\n              AND end_date >= ?\n            LIMIT 1\n        ");
        $checkLeave->bind_param("iss", $employee_id, $end_date, $start_date);
        $checkLeave->execute();
        $checkLeave->store_result();

        if ($checkLeave->num_rows > 0) {
            $checkLeave->close();
            http_response_code(409);
            echo "<div class='alert alert-danger'>You have already applied leave for the selected date(s).</div>";
            return;
        }

        $checkLeave->close();

        // Handle document upload
        if (!empty($_FILES['supporting_document']['name'])) {
            $target_dir = "../uploads/leave_documents/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }
            $document_name = time() . '_' . basename($_FILES['supporting_document']['name']);
            $supporting_document_path = $target_dir . $document_name;

            if (!move_uploaded_file($_FILES['supporting_document']['tmp_name'], $supporting_document_path)) {
                echo "<div class='alert alert-warning'>Failed to upload supporting document. Proceeding without it.</div>";
                $supporting_document_path = null;
            }
        }
        // Insert leave application
   // Insert leave application
   $leave_apply_date = date('Y-m-d H:i:s');
$stmt = $conn->prepare("
INSERT INTO leave_requests 
(employee_id, leave_type, extra_time_from, extra_time_to, leave_reason, start_date, end_date, supporting_document, manager, status, leave_apply_date, leave_approve_reject_date)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?, NULL)
");
$stmt->bind_param(
    "ssssssssss", 
    $employee_id, 
    $leave_type, 
    $extra_time_from, 
    $extra_time_to, 
    $leave_reason, 
    $start_date, 
    $end_date, 
    $supporting_document_path, 
    $manager,
    $leave_apply_date
);

if ($stmt->execute()) {
    echo "<div class='alert alert-success'>Leave application submitted successfully! Pending manager approval.</div>
    <script>
        setTimeout(function() {
            location.replace(document.referrer); // Redirect to previous page
        }, 15000);
    </script>";
    

            // Get employee details
            $query = $conn->prepare("SELECT name, email FROM employees WHERE id = ?");
            $query->bind_param("s", $employee_id);
            $query->execute();
            $query->bind_result($employee_name, $employee_email);
            $query->fetch();
            $query->close();
  // Get manager details (name and email) using manager ID
$query = $conn->prepare("SELECT name, email FROM employees WHERE id = ?");
$query->bind_param("s", $manager); // 'manager' is the manager's ID
$query->execute();
$query->bind_result($manager_name, $manager_email);
$query->fetch();
$query->close();

            // Send email notification
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'amaresh.sahoo101@gmail.com';
                $mail->Password = 'hwzfavtumiqhcwtu'; // Use an app-specific password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                // Common email content
                $message = "
                    <h3>Leave Request Details</h3>
                    <p><strong>Employee Name:</strong> $employee_name</p>
                    <p><strong>Leave Type:</strong> $leave_type</p>
                    <p><strong>Reason:</strong> $leave_reason</p>
                    <p><strong>Start Date:</strong> $start_date</p>
                    <p><strong>End Date:</strong> $end_date</p>
                    " . ($leave_type === 'compensatory_leave' ? "<p><strong>Extra Time From:</strong> $extra_time_from</p><p><strong>Extra Time To:</strong> $extra_time_to</p>" : "") . "
                    <p><strong>Status:</strong> Pending</p>
                ";
                // Email to manager
                $mail->setFrom('amaresh.sahoo101@gmail.com', 'Leave Management');
                $mail->addAddress($manager_email);
                $mail->isHTML(true);
                $mail->Subject = "New Leave Request from $employee_name";
                $mail->Body = $message;
                $mail->send();
                // Email to employee
                $mail->clearAddresses();
                $mail->addAddress($employee_email);
                $mail->Subject = "Leave Application Submitted Successfully";
                $mail->Body = "<p>Your leave application has been submitted and is pending approval from your manager, $manager_name.</p>" . $message;
                $mail->send();
            } catch (Exception $e) {
                // echo "<div class='alert alert-danger' style='position: fixed; top: 10px; right: 10px; z-index: 1000;'>Failed to send email notification. Error: {$mail->ErrorInfo}</div>";
            }
        } else {
            echo "<div class='alert alert-danger'>Failed to submit leave application. Please try again later.</div>";
            
        }
        $stmt->close();
    }
}
?>