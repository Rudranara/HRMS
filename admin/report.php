<?php
include("header.php");
// Check if the admin is logged in
require 'db_connection.php';

// Handle date filters
$current_date = date('Y-m-d');
$current_month_start = date('Y-m-01');
$last_month_start = date('Y-m-01', strtotime('-1 month'));
$last_month_end = date('Y-m-t', strtotime('-1 month'));
$current_year_start = date('Y-01-01');
$last_year_start = date('Y-01-01', strtotime('-1 year'));
$last_year_end = date('Y-12-31', strtotime('-1 year'));

// Default filter: Current month up to today
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

// Fetch Attendance Data for all employees
$attendance_query = "
    SELECT a.*, e.name AS employee_name,  e.employee_id,  e.photo
    FROM attendance a
    INNER JOIN employees e ON a.employee_id = e.id
    WHERE DATE(a.punch_out_time) >= ? AND DATE(a.punch_out_time) <= ?
    ORDER BY a.punch_in_time DESC
";
$attendance_stmt = $conn->prepare($attendance_query);
$attendance_stmt->bind_param("ss", $filter_start, $filter_end);
$attendance_stmt->execute();
$attendance_result = $attendance_stmt->get_result();

// Fetch Total Working Hours for all employees
$total_hours_query = "
    SELECT 
        e.name AS employee_name, 
        e.employee_id, 
        e.photo, 
        SUM(a.working_hours) AS total_working_hours 
    FROM attendance a
    INNER JOIN employees e ON a.employee_id = e.id
    WHERE DATE(a.punch_out_time) >= ? AND DATE(a.punch_out_time) <= ?
    GROUP BY e.id
";
$total_hours_stmt = $conn->prepare($total_hours_query);
$total_hours_stmt->bind_param("ss", $filter_start, $filter_end);
$total_hours_stmt->execute();
$total_hours_result = $total_hours_stmt->get_result();
?>
<div class="container py-4">
    <div class="row">
        <div class="col-md-12">
            <h4>Admin Attendance Report</h4>
            <div class="filters mb-3">
                <a href="report?filter=today" class="btn btn-sm btn-primary">Today</a>
                <a href="report?filter=this_month" class="btn btn-sm btn-primary">This Month</a>
                <a href="report?filter=last_month" class="btn btn-sm btn-primary">Last Month</a>
                <a href="report?filter=current_year" class="btn btn-sm btn-primary">Current Year</a>
                <a href="report?filter=last_year" class="btn btn-sm btn-primary">Last Year</a>
            </div>

            <!-- Total Working Hours Report -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6>Attendance Report (From <?= date('d M Y', strtotime($filter_start)) ?> to <?= date('d M Y', strtotime($filter_end)) ?>)</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th>Employee Name</th>
                                    <th>Total Working Hours</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $total_hours_result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex px-2 py-1">
                                                <div>
                                                    <img src="<?= htmlspecialchars($row['photo']) ?>" class="avatar avatar-sm me-3" alt="user1">
                                                </div>
                                                <div class="d-flex flex-column justify-content-center">
                                                    <h6 class="mb-0 text-sm"><?= htmlspecialchars($row['employee_name']) ?></h6>
                                                    <p class="text-xs text-secondary mb-0"><?= htmlspecialchars($row['employee_id']) ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($row['total_working_hours']) ?> hrs</td>
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
<?php include("footer.php"); ?>
