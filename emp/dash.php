<?php
// Start session and include database connection
session_start();
include("db_connection.php"); // Replace with your actual DB connection file
include("header.php"); // Replace with your header file

// Ensure the employee is logged in
if (!isset($_SESSION['employee_id'])) {
    echo "Access Denied! Please log in.";
    exit;
}

// Fetch logged-in employee ID
$employee_id = $_SESSION['employee_id'];

// Fetch employee details
$stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$employee = $stmt->get_result()->fetch_assoc();

if (!$employee) {
    echo "<div class='alert alert-danger'>Employee not found.</div>";
    exit;
}

// Initialize months array
$months = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June',
    7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

// Handle filter inputs
$year = isset($_POST['year']) ? $_POST['year'] : date('Y'); // Default to the current year
$month = isset($_POST['month']) ? $_POST['month'] : date('m'); // Default to the current month

// Calculate total working days in the selected month
$total_days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$total_working_days = 0;

// Count working days (Monday to Friday)
for ($day = 1; $day <= $total_days_in_month; $day++) {
    $date = $year . '-' . $month . '-' . $day;
    $day_of_week = date('N', strtotime($date)); // 1 (Monday) to 7 (Sunday)
    if ($day_of_week < 6) { // Monday to Friday
        $total_working_days++;
    }
}

// Calculate total possible working hours
$total_possible_hours = $total_working_days * 8; // Assuming 8 working hours per day

// Fetch attendance records for the selected month and year
$stmt = $conn->prepare("
    SELECT * FROM attendance 
    WHERE employee_id = ? AND YEAR(punch_in_time) = ? AND MONTH(punch_in_time) = ?
");
$stmt->bind_param("iii", $employee_id, $year, $month);
$stmt->execute();
$attendance = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Initialize attendance stats
$total_present = 0;
$total_absent = 0;
$total_on_leave = 0;
$total_late = 0;
$total_early = 0;
$total_working_hours = 0;

// Define company punch-in and punch-out times
$punchin_time = "09:00:00"; // Example standard punch-in time
$punchout_time = "17:00:00"; // Example standard punch-out time

// Calculate attendance statistics
foreach ($attendance as $record) {
    $punch_in_time = date('H:i:s', strtotime($record['punch_in_time']));
    $punch_out_time = date('H:i:s', strtotime($record['punch_out_time']));

    // Increment counters based on attendance status
    if ($record['status'] == 'Present') $total_present++;
    if ($record['status'] == 'Absent') $total_absent++;
    if ($record['status'] == 'On_Leave') $total_on_leave++;

    // Check late arrivals and early departures
    if ($punchin_time && $punch_in_time > $punchin_time) $total_late++;
    if ($punchout_time && $punch_out_time < $punchout_time) $total_early++;

    // Accumulate total working hours
    $total_working_hours += $record['working_hours'];
}

// Avoid division by zero for percentages
$total_present_percentage = $total_working_days > 0 ? round(($total_present / $total_working_days) * 100) : 0;
$total_absent_percentage = $total_working_days > 0 ? round(($total_absent / $total_working_days) * 100) : 0;
$total_on_leave_percentage = $total_working_days > 0 ? round(($total_on_leave / $total_working_days) * 100) : 0;
$total_late_percentage = $total_working_days > 0 ? round(($total_late / $total_working_days) * 100) : 0;
$total_early_percentage = $total_working_days > 0 ? round(($total_early / $total_working_days) * 100) : 0;
$total_working_hours_percentage = $total_possible_hours > 0 ? round(($total_working_hours / $total_possible_hours) * 100) : 0;
?>

<div class="container mt-5">
    <h2 class="text-center">Employee Dashboard</h2>

    <!-- Filter Form -->
    <form method="POST" class="row g-3 align-items-end mb-4">
        <div class="col-md-4">
            <label for="year" class="form-label">Select Year</label>
            <select name="year" id="year" class="form-control">
                <?php for ($i = date('Y'); $i >= 2000; $i--): ?>
                    <option value="<?= $i ?>" <?= $i == $year ? 'selected' : '' ?>><?= $i ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label for="month" class="form-label">Select Month</label>
            <select name="month" id="month" class="form-control">
                <?php foreach ($months as $key => $value): ?>
                    <option value="<?= $key ?>" <?= $key == $month ? 'selected' : '' ?>><?= $value ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
        </div>
    </form>

    <!-- Dashboard Metrics -->
    <div class="row text-center">
        <div class="col-md-4 mb-3">
            <div class="card border-primary">
                <div class="card-body">
                    <h5 class="card-title">Total Working Days</h5>
                    <p class="card-text"><?= $total_working_days ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-success">
                <div class="card-body">
                    <h5 class="card-title">Days Present</h5>
                    <p class="card-text"><?= $total_present ?> (<?= $total_present_percentage ?>%)</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-danger">
                <div class="card-body">
                    <h5 class="card-title">Days Absent</h5>
                    <p class="card-text"><?= $total_absent ?> (<?= $total_absent_percentage ?>%)</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-warning">
                <div class="card-body">
                    <h5 class="card-title">Days On Leave</h5>
                    <p class="card-text"><?= $total_on_leave ?> (<?= $total_on_leave_percentage ?>%)</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-secondary">
                <div class="card-body">
                    <h5 class="card-title">Late Punch-Ins</h5>
                    <p class="card-text"><?= $total_late ?> (<?= $total_late_percentage ?>%)</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-secondary">
                <div class="card-body">
                    <h5 class="card-title">Early Punch-Outs</h5>
                    <p class="card-text"><?= $total_early ?> (<?= $total_early_percentage ?>%)</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-info">
                <div class="card-body">
                    <h5 class="card-title">Total Working Hours</h5>
                    <p class="card-text"><?= $total_working_hours ?> hrs (<?= $total_working_hours_percentage ?>%)</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Records Table -->
    <h3 class="mt-5">Attendance Records for <?= $months[$month] ?>, <?= $year ?></h3>
    <table class="table table-striped table-bordered mt-3">
        <thead>
            <tr>
                <th>Date</th>
                <th>Punch In</th>
                <th>Punch Out</th>
                <th>Status</th>
                <th>Working Hours</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($attendance)): ?>
                <tr>
                    <td colspan="5" class="text-center">No attendance records found for the selected month.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($attendance as $record): ?>
                    <tr>
                        <td><?= date('d M Y', strtotime($record['punch_in_time'])) ?></td>
                        <td><?= $record['punch_in_time'] ? date('H:i:s', strtotime($record['punch_in_time'])) : '-' ?></td>
                        <td><?= $record['punch_out_time'] ? date('H:i:s', strtotime($record['punch_out_time'])) : '-' ?></td>
                        <td><?= ucfirst($record['status']) ?></td>
                        <td><?= $record['working_hours'] ?> hrs</td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
