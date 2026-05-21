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
        $mail->Password = 'nrpijuzegnqhnetn'; // App-specific password
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
            echo "<div class='alert alert-danger'>Only {$employee_leaves[$leave_column]} $leave_type(s) are available. Approval failed.</div>";
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

$query = "SELECT lr.*, e.name AS employee_name 
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
                window.location.href = 'manage_leave.php';
            }, 2000);
        </script>";
    } else {
        echo "<div class='alert alert-danger'>Failed to delete leave request. Please try again.</div>";
    }
    $stmt->close();
}
?>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-6 mb-4 d-flex align-items-center">
            <h6 class="mb-0">Manage Leave Requests</h6>
        </div>
        <div class="col-12 mb-3">
            <form method="GET"  class="row gx-2">
                <div class="col-md-3">
                    <label>Select Year</label>
                    <select name="year" class="form-control">
        <option value="">Select Year</option>
        <?php for ($y = 2022; $y <= date('Y'); $y++): ?>
            <option value="<?= $y ?>" <?= (isset($_GET['year']) && $_GET['year'] == $y) ? 'selected' : '' ?>><?= $y ?></option>
        <?php endfor; ?>
    </select>
                </div>
                <div class="col-md-3">
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
                <div class="col-md-3 d-flex align-items-end mt-4">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
                <div class="col-md-3 d-flex align-items-end mt-4">
                <input type="text" id="searchInput" class="form-control mb-3" placeholder="Search by employee, type, or status...">
                </div>
            </form>
        </div>
       
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0">
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
                                                <button class="btn btn-success btn-sm" 
                                                        onclick="openApproveModal(<?= $row['id'] ?>, '<?= $row['employee_name'] ?>', <?= $row['employee_id'] ?>, '<?= $row['leave_type'] ?>', '<?= $row['start_date'] ?>', '<?= $row['end_date'] ?>')">
                                                    Approve
                                                </button>
                                                <a href="javascript:void(0);" 
                                                   class="btn btn-danger btn-sm" 
                                                   onclick="openRejectModal(<?= $row['id'] ?>)">Reject</a>
                                            <?php else: ?>
                                                
                                            <?php endif; ?>
                                            <a href="javascript:void(0);" 
                                               class="btn btn-primary btn-sm" 
                                               onclick="openViewModal(<?= $row['id'] ?>)">View</a>
                                               <a href="manage_leave.php?delete_id=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this leave application?');">Delete</a>
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
<div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
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
                    <p><strong>Start Date:</strong> <span id="approveModalStartDate"></span></p>
                    <p><strong>End Date:</strong> <span id="approveModalEndDate"></span></p>
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
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
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
<div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewModalLabel">Leave Request Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Leave Type:</strong> <span id="viewLeaveType"></span></p>
                <p><strong>Reason:</strong> <span id="viewLeaveReason"></span></p>
                <p><strong>Start Date:</strong> <span id="viewStartDate"></span></p>
                <p><strong>End Date:</strong> <span id="viewEndDate"></span></p>
                <p><strong>Apply Date:</strong> <span id="viewleave_apply_date"></span></p>
                <p><strong>Supporting Document:</strong> <a id="viewDocument" href="#" target="_blank">View</a></p>
                <p><strong>Status:</strong> <span id="viewStatus"></span></p>
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
    function openApproveModal(id, name, employeeId, leaveType, startDate, endDate) {
        // Fill modal inputs and show modal
        document.getElementById('approveLeaveId').value = id;
        document.getElementById('approveEmployeeId').value = employeeId;
        document.getElementById('approveLeaveType').value = leaveType;
        document.getElementById('approveStartDate').value = startDate;
        document.getElementById('approveEndDate').value = endDate;

        document.getElementById('approveEmployeeName').innerText = name;
        document.getElementById('approveModalLeaveType').innerText = leaveType;
        document.getElementById('approveModalStartDate').innerText = startDate;
        document.getElementById('approveModalEndDate').innerText = endDate;

        // Fetch available leaves via AJAX
        fetch(`fetch_employee_leaves.php?employee_id=${employeeId}`)
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
        // Fetch leave request details using AJAX
        fetch(`fetch_leave_details.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('viewLeaveType').innerText = data.leave_type;
                document.getElementById('viewLeaveReason').innerText = data.leave_reason;
                document.getElementById('viewStartDate').innerText = data.start_date;
                document.getElementById('viewEndDate').innerText = data.end_date;
                document.getElementById('viewleave_apply_date').innerText = data.leave_apply_date;
                document.getElementById('viewDocument').href = data.supporting_document;
                document.getElementById('viewStatus').innerText = data.status;

                var viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
                viewModal.show();
            });
    }
</script>
<?php include("footer.php"); ?>
