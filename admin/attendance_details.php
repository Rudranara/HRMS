<?php
include("header.php");
// Database connection (assuming $conn is already available)
// Get the employee ID from the URL
$employee_id = $_GET['id'] ?? null;
if (!$employee_id) {
    echo "<div class='alert alert-danger'>Invalid employee ID.</div>";
    exit;
}
// Fetch employee details (including punch-in and punch-out times)
$stmt = $conn->prepare("SELECT id,name, working_hours, break_time,  punchin_time, punchout_time FROM employees WHERE id = ?");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$employee = $stmt->get_result()->fetch_assoc();
$stmt->close();

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
$year = $_POST['year'] ?? date('Y'); // Default to the current year
$month = $_POST['month'] ?? date('m'); // Default to the current month

// Fetch holidays from the `events` table
$holiday_query = $conn->prepare("
    SELECT start_date FROM events 
    WHERE YEAR(start_date) = ? AND MONTH(start_date) = ?
");
$holiday_query->bind_param("ii", $year, $month);
$holiday_query->execute();
$holidays = $holiday_query->get_result()->fetch_all(MYSQLI_ASSOC);
$holiday_dates = array_column($holidays, 'start_date');
// Calculate total working days (excluding weekends and holidays)
$total_days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$total_working_days = 0;

for ($day = 1; $day <= $total_days_in_month; $day++) {
    $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
    $day_of_week = date('N', strtotime($date)); // 1 (Monday) to 7 (Sunday)

    // Count only weekdays (Monday to Friday) that are NOT holidays
    if ($day_of_week < 7 && !in_array($date, $holiday_dates)) {
        $total_working_days++;
    }
}
// Define working hours per day (already in hours)
$working_hours_per_day = (float) $employee['working_hours']; 

// Convert break time from minutes to hours
$break_hours_per_day = (float) $employee['break_time'] / 60; // Convert minutes to hours

// Calculate total possible working hours
$total_possible_hours = $total_working_days * $working_hours_per_day;

// Calculate total possible break hours
$total_possible_break_hours = $total_working_days * $break_hours_per_day;


// Fetch attendance records for the selected month
$stmt = $conn->prepare("
    SELECT punch_in_time, punch_out_time, status, working_hours, break_hours 
    FROM attendance 
    WHERE employee_id = ? AND YEAR(punch_in_time) = ? AND MONTH(punch_in_time) = ?
");
$stmt->bind_param("iii", $employee_id, $year, $month);
$stmt->execute();
$attendance = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
// Initialize attendance stats
$total_present = 0;
$total_absent = 0;
$total_on_leave = 0;
$total_late = 0;
$total_early = 0;
$total_working_hours = 0;
$total_break_hours = 0;
// Employee's default punch-in and punch-out time
$punchin_time = isset($employee['punchin_time']) ? date('H:i:s', strtotime($employee['punchin_time'])) : null;
$punchout_time = isset($employee['punchout_time']) ? date('H:i:s', strtotime($employee['punchout_time'])) : null;
// Calculate attendance stats
foreach ($attendance as $record) {
  $punch_in_time = date('H:i:s', strtotime($record['punch_in_time']));
  $punch_out_time = date('H:i:s', strtotime($record['punch_out_time']));

  // Count attendance types
  if ($record['status'] == 'Present') {
      $total_present++;

      // Count late arrivals and early departures
      if ($punchin_time && $punch_in_time > $punchin_time) $total_late++;
      if ($punchout_time && $punch_out_time < $punchout_time) $total_early++;

      // Accumulate total working hours
      $total_working_hours += $record['working_hours'];
      $total_break_hours += $record['break_hours'];
  } elseif ($record['status'] == 'Absent') {
      $total_absent++;
  } elseif ($record['status'] == 'On_Leave') {
      $total_on_leave++;
  }
}
// Calculate percentages (Avoid division by zero)
$total_present_percentage = $total_working_days > 0 ? round(($total_present / $total_working_days) * 100) : 0;
$total_absent_percentage = $total_working_days > 0 ? round(($total_absent / $total_working_days) * 100) : 0;
$total_on_leave_percentage = $total_working_days > 0 ? round(($total_on_leave / $total_working_days) * 100) : 0;
$total_late_percentage = $total_working_days > 0 ? round(($total_late / $total_working_days) * 100) : 0;
$total_early_percentage = $total_working_days > 0 ? round(($total_early / $total_working_days) * 100) : 0;
$total_working_hours_percentage = $total_possible_hours > 0 ? round(($total_working_hours / $total_possible_hours) * 100) : 0;
$total_break_hours_percentage = $total_possible_break_hours > 0 ? round(($total_break_hours / $total_possible_break_hours) * 100) : 0;

// Fetch total break hours for the selected month
$stmt = $conn->prepare("
    SELECT SUM(working_hours) AS total_break_hours 
    FROM break_attendance 
    WHERE employee_id = ? AND YEAR(punch_in_time) = ? AND MONTH(punch_in_time) = ?
");
$stmt->bind_param("iii", $employee_id, $year, $month);
$stmt->execute();
$break_result = $stmt->get_result()->fetch_assoc();
$stmt->close();
$total_break_hours = $break_result['total_break_hours'] ?? 0; // Default to 0 if no data
?>
<div class="container">
    <h3>Attendance Report for <?= htmlspecialchars($employee['name']) ?></h3>
           <!-- Salary Calculation Fields -->
           <div class="mb-4">
    <!-- Basic Information -->
      <form method="POST">
        <div class="row mb-3">
            <div class="col-md-5">
                <label for="year">Select Year</label>
                <select name="year" id="year" class="form-control">
            <?php
            $currentYear = date('Y');
            for ($y = $currentYear; $y >= $currentYear - 10; $y--) {
                $selected = ($year == $y) ? 'selected' : '';
                echo "<option value='$y' $selected>$y</option>";
            }
            ?>
        </select>
            </div>
            <div class="col-md-5">
                <label for="month">Select Month</label>
                <select name="month" id="month" class="form-control">
            <?php
            foreach ($months as $key => $value) {
                $selected = ($month == $key) ? 'selected' : '';
                echo "<option value='$key' $selected>$value</option>";
            }
            ?>
        </select>
            </div>
        <div class="col-md-2 mt-4">
        <button type="submit" class="btn btn-primary">Filter</button>
        </div>
        </div>
</div>
    </form>
    <div class="col-lg-12 col-12 mt-4 mt-lg-0">
  <div class="card shadow h-100">
    <div class="card-header pb-0 p-3">
      <h6 class="mb-0">Monthly Report Of <?= $months[(int)$month] ?> <?= $year ?></h6>
    </div>
    <div class="card-body pb-0 p-3">
      <ul class="list-group">
        <li class="list-group-item border-0 d-flex align-items-center px-0 mb-0">
          <div class="w-100">
            <div class="d-flex mb-2">
              <span class="me-2 text-sm font-weight-bold text-dark">Total Present: <?= $total_present ?></span>
              <span class="ms-auto text-sm font-weight-bold"><?= round(($total_present / $total_working_days) * 100) ?>%</span>
            </div>
            <div>
              <div class="progress progress-md">
                <div class="progress-bar bg-primary" style="width: <?= round(($total_present / $total_working_days) * 100) ?>%" role="progressbar" aria-valuenow="<?= round(($total_present / $total_working_days) * 100) ?>" aria-valuemin="0" aria-valuemax="100"></div>
              </div>
            </div>
          </div>
        </li>
        <li class="list-group-item border-0 d-flex align-items-center px-0 mb-2">
          <div class="w-100">
            <div class="d-flex mb-2">
              <span class="me-2 text-sm font-weight-bold text-dark">Total Absent: <?= $total_absent ?></span>
              <span class="ms-auto text-sm font-weight-bold"><?= round(($total_absent / $total_working_days) * 100) ?>%</span>
            </div>
            <div>
              <div class="progress progress-md">
                <div class="progress-bar bg-danger" style="width: <?= round(($total_absent / $total_working_days) * 100) ?>%" role="progressbar" aria-valuenow="<?= round(($total_absent / $total_working_days) * 100) ?>" aria-valuemin="0" aria-valuemax="100"></div>
              </div>
            </div>
          </div>
        </li>
        <li class="list-group-item border-0 d-flex align-items-center px-0 mb-2">
          <div class="w-100">
            <div class="d-flex mb-2">
              <span class="me-2 text-sm font-weight-bold text-dark">Total On Leave: <?= $total_on_leave ?></span>
              <span class="ms-auto text-sm font-weight-bold"><?= round(($total_on_leave / $total_working_days) * 100) ?>%</span>
            </div>
            <div>
              <div class="progress progress-md">
                <div class="progress-bar bg-warning" style="width: <?= round(($total_on_leave / $total_working_days) * 100) ?>%" role="progressbar" aria-valuenow="<?= round(($total_on_leave / $total_working_days) * 100) ?>" aria-valuemin="0" aria-valuemax="100"></div>
              </div>
            </div>
          </div>
        </li>
        <li class="list-group-item border-0 d-flex align-items-center px-0 mb-2">
          <div class="w-100">
            <div class="d-flex mb-2">
              <span class="me-2 text-sm font-weight-bold text-dark">Total Late Punch-ins: <?= $total_late ?></span>
              <span class="ms-auto text-sm font-weight-bold"><?= round(($total_late / $total_working_days) * 100) ?>%</span>
            </div>
            <div>
              <div class="progress progress-md">
                <div class="progress-bar bg-info" style="width: <?= round(($total_late / $total_working_days) * 100) ?>%" role="progressbar" aria-valuenow="<?= round(($total_late / $total_working_days) * 100) ?>" aria-valuemin="0" aria-valuemax="100"></div>
              </div>
            </div>
          </div>
        </li>
        <li class="list-group-item border-0 d-flex align-items-center px-0 mb-2">
          <div class="w-100">
            <div class="d-flex mb-2">
              <span class="me-2 text-sm font-weight-bold text-dark">Total Early Punch-outs: <?= $total_early ?></span>
              <span class="ms-auto text-sm font-weight-bold"><?= round(($total_early / $total_working_days) * 100) ?>%</span>
            </div>
            <div>
              <div class="progress progress-md">
                <div class="progress-bar bg-secondary" style="width: <?= round(($total_early / $total_working_days) * 100) ?>%" role="progressbar" aria-valuenow="<?= round(($total_early / $total_working_days) * 100) ?>" aria-valuemin="0" aria-valuemax="100"></div>
              </div>
            </div>
          </div>
        </li>
        <li class="list-group-item border-0 d-flex align-items-center px-0 mb-2">
          <div class="w-100">
            <div class="d-flex mb-2">
              <span class="me-2 text-sm font-weight-bold text-dark">Total Working Hours: <?= $total_working_hours ?></span>
              <span class="ms-auto text-sm font-weight-bold"><?= round(($total_working_hours / $total_possible_hours) * 100) ?>%</span>
            </div>
            <div>
              <div class="progress progress-md">
                <div class="progress-bar bg-success" style="width: <?= round(($total_working_hours / $total_possible_hours) * 100) ?>%" role="progressbar" aria-valuenow="<?= round(($total_working_hours / $total_possible_hours) * 100) ?>" aria-valuemin="0" aria-valuemax="100"></div>
              </div>
             
            </div>
          </div>
        </li>

      
        <li class="list-group-item border-0 d-flex align-items-center px-0 mb-2">
    <div class="w-100">
        <div class="d-flex mb-2">
            <span class="me-2 text-sm font-weight-bold text-dark">Total Break Hours: <?= $total_break_hours ?></span>
            <span class="ms-auto text-sm font-weight-bold"><?= $total_possible_hours > 0 ? round(($total_break_hours / $total_possible_hours) * 100) : 0 ?>%</span>
        </div>
        <div>
            <div class="progress progress-md">
                <div class="progress-bar bg-warning" style="width: <?= $total_possible_hours > 0 ? round(($total_break_hours / $total_possible_hours) * 100) : 0 ?>%" 
                    role="progressbar" 
                    aria-valuenow="<?= $total_possible_hours > 0 ? round(($total_break_hours / $total_possible_hours) * 100) : 0 ?>" 
                    aria-valuemin="0" 
                    aria-valuemax="100">
                </div>
            </div>
        </div>
    </div>
</li>

      </ul>
    </div>
    <div class="card-footer pt-0 p-3 d-flex align-items-center">
      <div class="w-60">
        <p class="text-sm">
          <?php 
            if ($total_present / $total_working_days >= 0.9) {
                echo "Excellent attendance! Keep up the good work.";
            } elseif ($total_present / $total_working_days >= 0.75) {
                echo "Good attendance! A slight improvement could make it even better.";
            } elseif ($total_present / $total_working_days >= 0.5) {
                echo "Average attendance. More consistency is required.";
            } else {
                echo "Poor attendance. Immediate improvement is necessary.";
            }
          ?>
        </p>
      </div>
      <div class="w-40 text-end">
        <a class="btn btn-dark mb-0 text-end" href="salary_calculation?id=<?= htmlspecialchars($employee['id']) ?>">Calculate Salary</a>
      </div>
    </div>
  </div>
</div>
<?php include("footer.php"); ?>

