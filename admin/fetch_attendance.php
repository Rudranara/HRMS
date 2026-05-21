<?php
include("db_connection.php");
// Get input values from the AJAX request
$employee_id = $_GET['id'] ?? null;
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('m');

if (!$employee_id) {
    echo "<div class='alert alert-danger'>Invalid employee ID.</div>";
    exit;
}
// Fetch attendance records for the selected month and year
$stmt = $conn->prepare("SELECT * FROM attendance WHERE employee_id = ? AND YEAR(punch_in_time) = ? AND MONTH(punch_in_time) = ?");
$stmt->bind_param("iss", $employee_id, $year, $month);
$stmt->execute();
$attendance = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch holidays from the `events` table
$holiday_query = $conn->prepare("SELECT start_date FROM events WHERE YEAR(start_date) = ? AND MONTH(start_date) = ?");
$holiday_query->bind_param("ii", $year, $month);
$holiday_query->execute();
$holidays = $holiday_query->get_result()->fetch_all(MYSQLI_ASSOC);
$holiday_dates = array_column($holidays, 'start_date');

// Calculate total working days (excluding weekends and holidays)
$total_days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$total_working_days = 0;

for ($day = 1; $day <= $total_days_in_month; $day++) {
    $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
    $day_of_week = date('N', strtotime($date)); // 1 (Mon) to 7 (Sun)
    if ($day_of_week < 7 && !in_array($date, $holiday_dates)) {
        $total_working_days++;
    }
}

// Initialize counters
$total_present = $total_absent = $total_on_leave = $total_late = $total_early = $total_working_hours = 0;

// Fetch employee details
$stmt = $conn->prepare("SELECT punchin_time, punchout_time, net_salary, working_hours,sick_leave, casual_leave, paid_leave,other_leave, total_leave FROM employees WHERE id = ?");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$employee = $stmt->get_result()->fetch_assoc();
$punchin_time = date('H:i:s', strtotime($employee['punchin_time']));
$punchout_time = date('H:i:s', strtotime($employee['punchout_time']));
$net_salary = $employee['net_salary'];
$working_hours_per_day = $employee['working_hours'];
$sick_leave = $employee['sick_leave'];
$casual_leave = $employee['casual_leave'];
$paid_leave = $employee['paid_leave'];
$other_leave = $employee['other_leave'];
$total_leave = $employee['total_leave'];

$stmt = $conn->prepare("
SELECT status, COUNT(*) AS count 
FROM leave_requests 
WHERE employee_id = ? 
AND YEAR(leave_apply_date) = ? 
AND MONTH(leave_apply_date) = ? 
GROUP BY status
");
$stmt->bind_param("iii", $employee_id, $year, $month);
$stmt->execute();
$result = $stmt->get_result();

// Reset counters
$total_approved = $total_pending = $total_rejected = 0;

while ($row = $result->fetch_assoc()) {
if ($row['status'] === 'Approved') {
    $total_approved = $row['count'];
} elseif ($row['status'] === 'Pending') {
    $total_pending = $row['count'];
} elseif ($row['status'] === 'Rejected') {
    $total_rejected = $row['count'];
}
}

// Calculate dynamic hourly salary
$per_day_salary = $total_working_days > 0 ? ($net_salary / $total_working_days) : 0;
$hourly_salary = $working_hours_per_day > 0 ? ($per_day_salary / $working_hours_per_day) : 0;

// Process attendance records
foreach ($attendance as $record) {
    $punch_in_time = date('H:i:s', strtotime($record['punch_in_time']));
    $punch_out_time = date('H:i:s', strtotime($record['punch_out_time']));
    $daily_hours = $record['working_hours'];

    if ($record['status'] == 'Present') $total_present++;
    if ($record['status'] == 'Absent') $total_absent++;
    if ($record['status'] == 'On Leave') $total_on_leave++;
    if ($punch_in_time > $punchin_time) $total_late++;
    if ($punch_out_time < $punchout_time) $total_early++;

    $total_working_hours += $daily_hours;
}
// Expected working hours
$expected_working_hours = $working_hours_per_day * $total_working_days;
// Overtime or Time Lost calculation
$working_hour_difference = $total_working_hours - $expected_working_hours;
$ot_or_time_lost_amount = $working_hour_difference * $hourly_salary;
// Determine type
if ($working_hour_difference > 0) {
    $difference_type = "Overtime";
} elseif ($working_hour_difference < 0) {
    $difference_type = "Time Lost";
} else {
    $difference_type = "Exact Hours";
    $ot_or_time_lost_amount = 0;
}
// Final salary (based on worked hours)
$calculated_salary = $total_working_hours * $hourly_salary;
echo "
  <div class='card mt-4'>
                <div class='card-header pb-0 p-3'>
                  <div class='row'>
                    <div class='col-6 d-flex align-items-center'>
                      <h6 class='mb-0'>Attendance Details</h6>
                    </div>
                    <div class='col-6 text-end'>
                      <a class='btn bg-gradient-dark mb-0' href='attendance_details'><i class='fas fa-plus'></i>Report</a>
                    </div>
                  </div>
                </div>
                <div class='card-body p-3'>
                  <div class='row mt-3'>
                    <div class='col-md-6 mb-md-0 mb-4'>
                      <div class='card card-body border card-plain border-radius-lg d-flex align-items-center flex-row'>
                        <img class='w-10 me-3 mb-0' src='assets/img/logos/mark-on-the-calendar.png' alt='logo'>
                       <h6 class='mb-0'>Total Present: $total_present/ $total_working_days</h6>
                        <i class='fas fa-pencil-alt ms-auto text-dark cursor-pointer' data-bs-toggle='tooltip' data-bs-placement='top' aria-label='Edit Card' data-bs-original-title='Edit Card'></i>
                      </div>
                    </div>
                    <div class='col-md-6'>
                      <div class='card card-body border card-plain border-radius-lg d-flex align-items-center flex-row'>
                        <img class='w-10 me-3 mb-0' src='assets/img/logos/remove.png' alt='logo'>
                       <h6 class='mb-0'>Absent: $total_absent</h6>
                        <i class='fas fa-pencil-alt ms-auto text-dark cursor-pointer' data-bs-toggle='tooltip' data-bs-placement='top' aria-label='Edit Card' data-bs-original-title='Edit Card'></i>
                      </div>
                    </div>
                  </div>
                  <div class='row mt-3'>
                    <div class='col-md-6 mb-md-0 mb-4'>
                      <div class='card card-body border card-plain border-radius-lg d-flex align-items-center flex-row'>
                        <img class='w-10 me-3 mb-0' src='assets/img/logos/leave.png' alt='logo'>
                         <h6 class='mb-0'>On Leave: $total_on_leave</h6>
                        <i class='fas fa-pencil-alt ms-auto text-dark cursor-pointer' data-bs-toggle='tooltip' data-bs-placement='top' aria-label='Edit Card' data-bs-original-title='Edit Card'></i>
                      </div>
                    </div>
                    <div class='col-md-6'>
                      <div class='card card-body border card-plain border-radius-lg d-flex align-items-center flex-row'>
                        <img class='w-10 me-3 mb-0' src='assets/img/logos/tortoise.png' alt='logo'>
                     <h6 class='mb-0'>Late Punch-ins: $total_late</h6>
                        <i class='fas fa-pencil-alt ms-auto text-dark cursor-pointer' data-bs-toggle='tooltip' data-bs-placement='top' aria-label='Edit Card' data-bs-original-title='Edit Card'></i>
                      </div>
                    </div>
                  </div>
                  <div class='row mt-3'>
                    <div class='col-md-6 mb-md-0 mb-4'>
                      <div class='card card-body border card-plain border-radius-lg d-flex align-items-center flex-row'>
                        <img class='w-10 me-3 mb-0' src='assets/img/logos/running.png' alt='logo'>
                       <h6 class='mb-0'>Early Punch-outs: $total_early</h6>
                        <i class='fas fa-pencil-alt ms-auto text-dark cursor-pointer' data-bs-toggle='tooltip' data-bs-placement='top' aria-label='Edit Card' data-bs-original-title='Edit Card'></i>
                      </div>
                    </div>
                    
                    <div class='col-md-6'>
                      <div class='card card-body border card-plain border-radius-lg d-flex align-items-center flex-row'>
                        <img class='w-10 me-3 mb-0' src='assets/img/logos/working-time.png' alt='logo'>
                        <h6 class='mb-0'>Total Working Hours: $total_working_hours / $expected_working_hours</h6>
                        <i class='fas fa-pencil-alt ms-auto text-dark cursor-pointer' data-bs-toggle='tooltip' data-bs-placement='top' aria-label='Edit Card' data-bs-original-title='Edit Card'></i>
                      </div>
                    </div>
                  </div>

                   <div class='row mt-3'>
                    <div class='col-md-6 mb-md-0 mb-4'>
                      <div class='card card-body border card-plain border-radius-lg d-flex align-items-center flex-row'>
                        <img class='w-10 me-3 mb-0' src='assets/img/logos/wages.png' alt='logo'>
                     <h6 class='mb-0'>$difference_type: " . abs($working_hour_difference) . " hour(s)</h6>
                        <i class='fas fa-pencil-alt ms-auto text-dark cursor-pointer' data-bs-toggle='tooltip' data-bs-placement='top' aria-label='Edit Card' data-bs-original-title='Edit Card'></i>
                      </div>
                    </div>
                
                    <div class='col-md-6 mb-md-0 mb-4'>
                      <div class='card card-body border card-plain border-radius-lg d-flex align-items-center flex-row'>
                        <img class='w-10 me-3 mb-0' src='assets/img/logos/wages.png' alt='logo'>
                      <h6 class='mb-0'>$difference_type Amount: ₹" . number_format(abs($ot_or_time_lost_amount), 2) . "</h6>
                        <i class='fas fa-pencil-alt ms-auto text-dark cursor-pointer' data-bs-toggle='tooltip' data-bs-placement='top' aria-label='Edit Card' data-bs-original-title='Edit Card'></i>
                      </div>
                    </div>
   </div>
  <div class='row mt-3'>
                    <div class='col-md-6 mb-md-0 mb-4'>
                      <div class='card card-body border card-plain border-radius-lg d-flex align-items-center flex-row'>
                        <img class='w-10 me-3 mb-0' src='assets/img/logos/wages.png' alt='logo'>
                        <h6 class='mb-0'>Calculated Salary: ₹" . number_format(abs($calculated_salary), 2) . "  </h6>
                        <i class='fas fa-pencil-alt ms-auto text-dark cursor-pointer' data-bs-toggle='tooltip' data-bs-placement='top' aria-label='Edit Card' data-bs-original-title='Edit Card'></i>
                      </div>
                    </div>
                    <div class='col-md-6'>
                      <div class='card card-body border card-plain border-radius-lg d-flex align-items-center flex-row'>
                        <img class='w-10 me-3 mb-0' src='assets/img/logos/wages2.png' alt='logo'>
                        <h6 class='mb-0'>Actual Salary: $net_salary</h6>
                        <i class='fas fa-pencil-alt ms-auto text-dark cursor-pointer' data-bs-toggle='tooltip' data-bs-placement='top' aria-label='Edit Card' data-bs-original-title='Edit Card'></i>
                      </div>
                    </div>
                 <h6 class='mb-0'>Leaves in " . date("F Y", strtotime("$year-$month-01")) . "</h6>
                    <div class='row mt-3'>
                       <div class='col-md-12'>
                      <div class='card card-body border card-plain border-radius-lg d-flex align-items-center flex-row'>

                         <div class='row mt-3'>
                       <div class='col-md-3'>                     
                        <h6 class='mb-0'>Sick Leave: $sick_leave</h6>
                        </div>
                         <div class='col-md-3'>                     
                        <h6 class='mb-0'>Casual_leave: $casual_leave</h6>
                        </div>
                           <div class='col-md-3'>                     
                        <h6 class='mb-0'>Paid Leave: $paid_leave</h6>
                        </div>
                           <div class='col-md-3'>                     
                        <h6 class='mb-0'>Other Leave: $other_leave</h6>
                        </div>
                           <div class='col-md-3'>                     
                        <h6 class='mb-0'>Total Leave: $total_leave</h6>
                        </div>
                          <div class='col-md-3'>                     
                        <h6 class='mb-0'>Approved Leaves: $total_approved</h6>
                        </div>
                          <div class='col-md-3'>                     
                        <h6 class='mb-0'>Pending Leaves: $total_pending</h6>
                        </div>
                          <div class='col-md-3'>                     
                        <h6 class='mb-0'>Rejected Leaves: $total_rejected</h6>
                        </div>
                         
                        </div>
                        <i class='fas fa-pencil-alt ms-auto text-dark cursor-pointer' data-bs-toggle='tooltip' data-bs-placement='top' aria-label='Edit Card' data-bs-original-title='Edit Card'></i>
                      </div>
                    </div>
                    </div>
                  </div>
                </div>
              </div>
";
?>
