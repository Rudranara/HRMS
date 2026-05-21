<?php
include("header.php");
require 'db_connection.php';
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
// Fetch the logged-in employee's manager and leave balances
$query = $conn->prepare("SELECT manager, sick_leave, casual_leave, paid_leave, other_leave, total_leave FROM employees WHERE id = ?");
$query->bind_param("s", $employee_id);
$query->execute();
$query->bind_result($manager, $sick_leave, $casual_leave, $paid_leave, $other_leave, $total_leave);
$query->fetch();
$query->close();

$sick_leave = (float) ($sick_leave ?? 0);
$casual_leave = (float) ($casual_leave ?? 0);
$paid_leave = (float) ($paid_leave ?? 0);
$other_leave = (float) ($other_leave ?? 0);
$total_leave = (float) ($total_leave ?? 0);
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


    // CHECK IF LEAVE ALREADY APPLIED FOR SAME / OVERLAP DATE
    // ----------------------------------------------------
    
    $checkLeave = $conn->prepare("
        SELECT id
        FROM leave_requests
        WHERE employee_id = ?
          AND LOWER(status) IN ('pending', 'approved')
          AND start_date <= ?
          AND end_date >= ?
        LIMIT 1
    ");


    
    $checkLeave->bind_param(
        "iss",   // i = employee_id (integer)
        $employee_id,
        $end_date,
        $start_date
    );


    $checkLeave->execute();
    $checkLeave->store_result();

    if ($checkLeave->num_rows > 0) {
        $checkLeave->close();

        http_response_code(409); // Conflict
        echo "<div class='alert alert-danger'>
                ❌ You have already applied leave for the selected date(s).
              </div>";
        die(); // HARD STOP
    }


    $checkLeave->close();

        
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
                // echo "<div class='alert alert-danger'>Failed to send email notification. Error: {$mail->ErrorInfo}</div>";
            }
        } else {
            echo "<div class='alert alert-danger'>Failed to submit leave application. Please try again later.</div>";
            
        }
        $stmt->close();
    }
}
?>

<style>
.apply-leave-page {
    padding-top: 0.95rem !important;
    padding-bottom: 1.2rem !important;
}

.leave-balance-card {
    border: 1px solid rgba(87, 96, 108, 0.12);
    border-radius: 26px;
    box-shadow: 0 22px 52px rgba(15, 23, 42, 0.08);
    background: linear-gradient(180deg, #ffffff 0%, #f7fbff 100%);
    overflow: hidden;
}

.leave-balance-kicker {
    display: block;
    margin-bottom: 0.4rem;
    color: #6b7280;
    font-size: 0.74rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.leave-balance-total {
    margin: 0;
    color: #111827;
    font-size: 2rem;
    font-weight: 800;
    line-height: 1;
}

.leave-balance-copy {
    margin: 0.55rem 0 0;
    color: #6b7280;
    font-size: 0.92rem;
}

.leave-balance-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.9rem;
}

.leave-balance-item {
    padding: 0.95rem 1rem;
    border: 1px solid #e6ebf2;
    border-radius: 18px;
    background: #ffffff;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.9);
}

.leave-balance-item-label {
    display: block;
    margin-bottom: 0.35rem;
    color: #6b7280;
    font-size: 0.76rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
}

.leave-balance-item-value {
    color: #111827;
    font-size: 1.2rem;
    font-weight: 800;
}

.apply-leave-card {
    border: 1px solid rgba(87, 96, 108, 0.12);
    border-radius: 26px;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    box-shadow: 0 22px 52px rgba(15, 23, 42, 0.08);
    overflow: hidden;
}

.apply-leave-card .card-header {
    padding: 1.25rem 1.35rem 0.5rem !important;
    border-bottom: 0;
    background: transparent;
}

.apply-leave-title {
    margin: 0;
    color: #0f172a;
    font-size: 1.06rem;
    font-weight: 800;
    letter-spacing: -0.02em;
}

.apply-leave-form-wrap {
    margin: 0 1rem 1rem;
    padding: 1.2rem;
    border: 1px solid #e2e8f0;
    border-radius: 22px;
    background: #ffffff;
}

.apply-leave-form-wrap .row {
    --bs-gutter-x: 1rem;
    --bs-gutter-y: 0.2rem;
}

.apply-leave-form-wrap .form-label {
    margin-bottom: 0.55rem;
    color: #475569;
    font-size: 0.74rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.apply-leave-form-wrap .form-control {
    min-height: 48px;
    border-radius: 16px;
    border: 1px solid #d9e2ec;
    background: #ffffff;
    color: #0f172a;
    font-size: 0.92rem;
    font-weight: 500;
    box-shadow: none;
    padding: 0.82rem 0.95rem;
}

.apply-leave-form-wrap textarea.form-control {
    min-height: 120px;
    resize: vertical;
}

.apply-leave-form-wrap .form-control:focus {
    border-color: #94a3b8;
    box-shadow: 0 0 0 4px rgba(148, 163, 184, 0.16);
}

.apply-leave-submit {
    min-height: 46px;
    padding: 0.75rem 1.1rem;
    border: 0;
    border-radius: 16px;
    background: linear-gradient(135deg, #111827 0%, #1f2937 100%) !important;
    color: #ffffff !important;
    font-size: 0.8rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    box-shadow: 0 16px 28px rgba(15, 23, 42, 0.12);
}

@media (max-width: 991.98px) {
    .leave-balance-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 575.98px) {
    .apply-leave-page {
        padding-top: 0.6rem !important;
        padding-left: 0.3rem !important;
        padding-right: 0.3rem !important;
        padding-bottom: 0.85rem !important;
    }

    .leave-balance-grid {
        grid-template-columns: 1fr;
    }

    .leave-balance-total {
        font-size: 1.45rem;
        margin-bottom: 0.15rem;
    }

    .leave-balance-card,
    .apply-leave-card {
        border-radius: 22px;
    }

    .leave-balance-card .card-body {
        padding: 1rem !important;
    }

    .leave-balance-kicker {
        margin-bottom: 0.25rem;
        font-size: 0.68rem;
    }

    .leave-balance-grid {
        gap: 0.55rem;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .leave-balance-item {
        padding: 0.7rem 0.78rem;
        border-radius: 14px;
    }

    .leave-balance-item-label {
        margin-bottom: 0.22rem;
        font-size: 0.68rem;
    }

    .leave-balance-item-value {
        font-size: 1rem;
    }

    .apply-leave-card .card-header {
        padding: 1.05rem 1rem 0.4rem !important;
    }

    .apply-leave-title {
        font-size: 0.98rem;
    }

    .apply-leave-form-wrap {
        margin: 0 0.85rem 0.85rem;
        padding: 1rem;
        border-radius: 18px;
    }

    .apply-leave-form-wrap .form-control,
    .apply-leave-submit {
        min-height: 42px;
        border-radius: 14px;
        font-size: 0.78rem;
    }

    .apply-leave-form-wrap textarea.form-control {
        min-height: 108px;
    }
}
</style>


<div class="container-fluid py-4 apply-leave-page">
    <div class="col-md-12 mb-lg-0 mb-4">
        <div id="leaveResponseMessage" class="mb-3"></div>

        <div class="card leave-balance-card mb-4">
            <div class="card-body p-4">
                <div class="row g-3 align-items-center">
                    <div class="col-lg-4">
                        <span class="leave-balance-kicker">Available Leave</span>
                        <h4 class="leave-balance-total"><?= number_format($total_leave, 0) ?> Days</h4>
                    </div>
                    <div class="col-lg-8">
                        <div class="leave-balance-grid">
                            <div class="leave-balance-item">
                                <span class="leave-balance-item-label">Sick Leave</span>
                                <span class="leave-balance-item-value"><?= number_format($sick_leave, 0) ?></span>
                            </div>
                            <div class="leave-balance-item">
                                <span class="leave-balance-item-label">Casual Leave</span>
                                <span class="leave-balance-item-value"><?= number_format($casual_leave, 0) ?></span>
                            </div>
                            <div class="leave-balance-item">
                                <span class="leave-balance-item-label">Paid Leave</span>
                                <span class="leave-balance-item-value"><?= number_format($paid_leave, 0) ?></span>
                            </div>
                            <div class="leave-balance-item">
                                <span class="leave-balance-item-label">Other Leave</span>
                                <span class="leave-balance-item-value"><?= number_format($other_leave, 0) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4 apply-leave-card">
            <div class="card-header pb-0 p-3">
                <h6 class="mb-0 apply-leave-title">Apply for Leave</h6>
            </div>
            <div class="apply-leave-form-wrap">
                <form id="leaveForm"  enctype="multipart/form-data" oninput="toggleCompensatoryFields()">
                    <div class="row">
                        <div class="col-md-6 mt-4">
                            <label for="leave_type" class="form-label">Leave Type</label>


                            <?php
// Fetch active leave types from database
$leave_types_result = $conn->query("SELECT type_name FROM leave_types WHERE is_enabled = 1");
?>

                           <select class="form-control" name="leave_type" id="leave_type" required>
    <option value="">Select Leave Type</option>
    <?php while ($row = $leave_types_result->fetch_assoc()): ?>
        <option value="<?= htmlspecialchars($row['type_name']) ?>">
            <?= ucwords(str_replace('_', ' ', $row['type_name'])) ?>
        </option>
    <?php endwhile; ?>
</select>

                        </div>

                        <!-- Compensatory Leave Date Fields (Hidden by Default) -->
                        <div class="col-md-6 mt-4" id="extra_time_from_field" style="display: none;">
                            <label for="extra_time_from" class="form-label">Extra Work Period (Start Date)</label>
                            <input class="form-control" type="date" name="extra_time_from" id="extra_time_from">
                        </div>
                        <div class="col-md-6 mt-4" id="extra_time_to_field" style="display: none;">
                            <label for="extra_time_to" class="form-label">Extra Work Period (End Date)</label>
                            <input class="form-control" type="date" name="extra_time_to" id="extra_time_to">
                        </div>

                        <div class="col-md-6 mt-4">
                            <label for="leave_reason" class="form-label">Reason for Leave</label>
                            <textarea class="form-control" name="leave_reason" id="leave_reason" rows="3" required></textarea>
                        </div>
                        <div class="col-md-6 mt-4">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input class="form-control" type="date" name="start_date" id="start_date" required>
                        </div>
                        <div class="col-md-6 mt-4">
                            <label for="end_date" class="form-label">End Date</label>
                            <input class="form-control" type="date" name="end_date" id="end_date" required>
                        </div>
                        <div class="col-md-6 mt-4">
                            <label for="supporting_document" class="form-label">Supporting Document (Optional)</label>
                            <input class="form-control" type="file" name="supporting_document" id="supporting_document" accept="application/pdf, image/*">
                        </div>
                       
                            <input class="form-control" type="hidden" name="manager" id="manager" value="<?= htmlspecialchars($manager) ?>" readonly>
                        
                        <div class="col-md-12 mt-4">
                            <button class="btn bg-gradient-dark mb-0 apply-leave-submit" type="submit">Apply Leave</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<div id="loader-overlay" style="
    display: none;
    position: fixed;
    z-index: 9999;
    background-color: rgba(0,0,0,0.5);
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    text-align: center;
    color: white;
    font-size: 24px;
    padding-top: 20%;
    transition: all 0.30s ease;
">
    <div class="spinner-border text-light" role="status" style="width: 3rem; height: 3rem;"></div>
    <div>Submitting, please wait...</div>
</div>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const form = document.getElementById("leaveForm");
    const responseContainer = document.getElementById("leaveResponseMessage");
    const loader = document.getElementById("loader-overlay");

    function renderLeaveResponse(html) {
        responseContainer.innerHTML = html;

        responseContainer.querySelectorAll("script").forEach(function(oldScript) {
            const newScript = document.createElement("script");

            if (oldScript.src) {
                newScript.src = oldScript.src;
            } else {
                newScript.textContent = oldScript.textContent;
            }

            document.body.appendChild(newScript);
            document.body.removeChild(newScript);
            oldScript.remove();
        });

        responseContainer.scrollIntoView({ behavior: "smooth", block: "start" });
    }

    form.addEventListener("submit", function(event) {
        event.preventDefault(); // Stop default form submission
        loader.style.display = "block"; // Show loader
        responseContainer.innerHTML = "";

        const formData = new FormData(form);

        fetch("submit_leave", {
            method: "POST",
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            loader.style.display = "none"; // Hide loader after processing
            renderLeaveResponse(data);
        })
        .catch(error => {
            loader.style.display = "none";
            responseContainer.innerHTML = '<div class="alert alert-danger">An error occurred. Please try again.</div>';
            responseContainer.scrollIntoView({ behavior: "smooth", block: "start" });
            console.error("Error:", error);
        });
    });
});
</script>

<script>
    function toggleCompensatoryFields() {
        const leaveType = document.getElementById('leave_type').value;
        const extraTimeFromField = document.getElementById('extra_time_from_field');
        const extraTimeToField = document.getElementById('extra_time_to_field');

        if (leaveType === 'compensatory_leave') {
            extraTimeFromField.style.display = 'block';
            extraTimeToField.style.display = 'block';
        } else {
            extraTimeFromField.style.display = 'none';
            extraTimeToField.style.display = 'none';
        }
    }
    // Initial check on page load
    document.addEventListener("DOMContentLoaded", toggleCompensatoryFields);
</script>



<?php include("footer.php"); ?>
