<?php
include("header.php");

// punch if the employee is logged in
if (!isset($_SESSION['employee_id'])) {
    echo "<div class='alert alert-danger'>You must be logged in to view reports.</div>";
    exit;
}

$employee_id = $_SESSION['employee_id']; // Get employee ID from session
require 'db_connection.php';

// Handle date filters
$current_date = date('Y-m-d');
$current_month_start = date('Y-m-01');
$last_month_start = date('Y-m-01', strtotime('-1 month'));
$last_month_end = date('Y-m-t', strtotime('-1 month'));
$current_year_start = date('Y-01-01');
$last_year_start = date('Y-01-01', strtotime('-1 year'));
$last_year_end = date('Y-12-31', strtotime('-1 year'));

// Default filter
$filter_start = $current_month_start; 
$filter_end = $current_date; 

if (isset($_GET['filter'])) {
    switch ($_GET['filter']) {
        case 'today':
            $filter_start = $current_date;
            $filter_end = $current_date;
            break;
        case 'this_month':
            $filter_start = $current_month_start;
            $filter_end = $current_date;
            break;
        case 'last_month':
            $filter_start = $last_month_start;
            $filter_end = $last_month_end;
            break;
        case 'current_year':
            $filter_start = $current_year_start;
            $filter_end = $current_date;
            break;
        case 'last_year':
            $filter_start = $last_year_start;
            $filter_end = $last_year_end;
            break;
    }
}

// Fetch Attendance Data
$attendance_stmt = $conn->prepare("SELECT punch_out_time, punch_in_time, punch_out_time, working_hours FROM attendance WHERE employee_id = ? AND punch_out_time BETWEEN ? AND ?");
$attendance_stmt->bind_param("sss", $employee_id, $filter_start, $filter_end);
$attendance_stmt->execute();
$attendance_result = $attendance_stmt->get_result();

// Fetch Total Working Hours
$total_hours_stmt = $conn->prepare("SELECT SUM(working_hours) AS total_working_hours FROM attendance WHERE employee_id = ? AND punch_out_time BETWEEN ? AND ?");
$total_hours_stmt->bind_param("sss", $employee_id, $filter_start, $filter_end);
$total_hours_stmt->execute();
$total_hours_result = $total_hours_stmt->get_result();
$total_hours = $total_hours_result->fetch_assoc()['total_working_hours'] ?? 0;


?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-12">
            <h4>Employee Report</h4>
            <div class="filters mb-3">
                <a href="report?filter=today" class="btn btn-sm btn-primary">Today</a>
                <a href="report?filter=this_month" class="btn btn-sm btn-primary">This Month</a>
                <a href="report?filter=last_month" class="btn btn-sm btn-primary">Last Month</a>
                <a href="report?filter=current_year" class="btn btn-sm btn-primary">Current Year</a>
                <a href="report?filter=last_year" class="btn btn-sm btn-primary">Last Year</a>
            </div>
            
            <!-- Attendance Report -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6>Attendance Report (<?= date('d M Y', strtotime($filter_start)) ?> - <?= date('d M Y', strtotime($filter_end)) ?>)</h6>
                </div>
                <div class="card-body">
                    <?php if ($attendance_result->num_rows > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>punch-In</th>
                                    <th>punch-Out</th>
                                    <th>Working Hours</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $attendance_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['date']) ?></td>
                                        <td><?= htmlspecialchars($row['punch_in_time']) ?></td>
                                        <td><?= htmlspecialchars($row['punch_out_time']) ?></td>
                                        <td><?= htmlspecialchars($row['working_hours']) ?> hrs</td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No attendance records found for the selected period.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Total Working Hours -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6>Total Working Hours</h6>
                </div>
                <div class="card-body">
                    <p>Total Working Hours for the selected period: <strong><?= $total_hours ?> hrs</strong></p>
                </div>
            </div>

            <!-- Salary Report -->
        
        </div>
    </div>
</div>

<?php include("footer.php"); ?>
