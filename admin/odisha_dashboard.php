<?php
include("header.php"); // Replace with your header file
// Initialize months array
$months = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June',
    7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];
// Handle filter inputs
$year = isset($_POST['year']) ? $_POST['year'] : date('Y'); // Default to the current year
$month = isset($_POST['month']) ? $_POST['month'] : date('m'); // Default to the current month

// Calculate total working days (excluding holidays and weekends)
$total_days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$total_working_days = 0;

// Fetch holidays from the events table for Odisha Office
$holiday_query = $conn->prepare("
    SELECT start_date FROM events WHERE YEAR(start_date) = ? AND MONTH(start_date) = ? AND office = 'Odisha Office'
");
$holiday_query->bind_param("ii", $year, $month);
$holiday_query->execute();
$holidays = $holiday_query->get_result()->fetch_all(MYSQLI_ASSOC);
$holiday_dates = array_column($holidays, 'start_date');

// Calculate working days
for ($day = 1; $day <= $total_days_in_month; $day++) {
    $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
    $day_of_week = date('N', strtotime($date)); // 1 (Monday) to 7 (Sunday)
    if ($day_of_week < 7 && !in_array($date, $holiday_dates)) {
        $total_working_days++;
    }
}

// Fetch total employees in Odisha Office
$total_employees_query = $conn->prepare("
    SELECT COUNT(*) AS total_employees FROM employees WHERE office = 'Odisha Office'
");
$total_employees_query->execute();
$total_employees = $total_employees_query->get_result()->fetch_assoc()['total_employees'];

// Fetch today's attendance for Odisha Office
$today_date = date('Y-m-d');
$today_attendance_query = $conn->prepare("
    SELECT status FROM attendance WHERE DATE(punch_in_time) = ? AND office = 'Odisha Office'
");
$today_attendance_query->bind_param("s", $today_date);
$today_attendance_query->execute();
$today_attendance = $today_attendance_query->get_result()->fetch_all(MYSQLI_ASSOC);
$employees_absent = $total_employees - count(array_filter($today_attendance, fn($a) => $a['status'] == 'Present'));

// Fetch attendance for the selected month and year for Odisha Office
$stmt = $conn->prepare("
    SELECT * FROM attendance 
    WHERE YEAR(punch_in_time) = ? AND MONTH(punch_in_time) = ? AND office = 'Odisha Office'
");
$stmt->bind_param("ii", $year, $month);
$stmt->execute();
$attendance = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch pending leave requests for Odisha Office
$pending_leave_query = $conn->prepare("
    SELECT COUNT(*) AS pending_leaves FROM leave_requests WHERE status = 'Pending' AND office = 'Odisha Office'
");
$pending_leave_query->execute();
$total_pending_leaves = $pending_leave_query->get_result()->fetch_assoc()['pending_leaves'];

// Fetch total salary processed for the current month for Odisha Office
$salary_query = $conn->prepare("
    SELECT SUM(net_salary) AS total_salary FROM salary 
    WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND office = 'Odisha Office'
");
$salary_query->bind_param("ii", $year, $month);
$salary_query->execute();
$total_salary_processed = $salary_query->get_result()->fetch_assoc()['total_salary'];

// Fetch upcoming birthdays for Odisha Office
$birthdays_query = $conn->prepare("
    SELECT name, DATE_FORMAT(dob, '%d %M') AS birthday FROM employees 
    WHERE MONTH(dob) = ? AND DAY(dob) >= DAY(NOW()) AND office = 'Odisha Office'
");
$birthdays_query->bind_param("i", $month);
$birthdays_query->execute();
$birthdays = $birthdays_query->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate total working hours
$total_possible_hours = $total_working_days * 8; // Assuming 8 working hours per day
$total_present = 0;
$total_absent = 0;
$total_on_leave = 0;
$total_late = 0;
$total_early = 0;
$total_working_hours = 0;
// Define company punch-in and punch-out times
$punchin_time = "09:30:00"; // Example standard punch-in time
$punchout_time = "18:30:00"; // Example standard punch-out time
foreach ($attendance as $record) {
    if ($record['status'] == 'Present') {
        $total_present++;
        $punch_in_time = date('H:i:s', strtotime($record['punch_in_time']));
        $punch_out_time = date('H:i:s', strtotime($record['punch_out_time']));
        if ($punch_in_time > $punchin_time) {
            $total_late++;
        }
        if ($punch_out_time < $punchout_time) {
            $total_early++;
        }
        $total_working_hours += $record['working_hours'];
    } elseif ($record['status'] == 'Absent') {
        $total_absent++;
    } elseif ($record['status'] == 'On Leave') {
        $total_on_leave++;
    }
}
// Calculate percentages
$total_present_percentage = $total_working_days > 0 ? round(($total_present / $total_working_days) * 100) : 0;
$total_absent_percentage = $total_working_days > 0 ? round(($total_absent / $total_working_days) * 100) : 0;
$total_working_hours_percentage = $total_possible_hours > 0 ? round(($total_working_hours / $total_possible_hours) * 100) : 0;
?>

    <!-- End Navbar -->
    <div class="container-fluid container-fluid-main">
      <div class="row">
      <form method="POST" class="row align-items-end mb-4">
      <div class="col-md-3 col-sm-4 col-4">
    <label for="site" class="form-label">Select Site</label>
    <select name="site" id="site" class="form-control" onchange="redirectToSite()">
        <option value="dashboard" >All Site</option>
        <option value="odisha_dashboard" selected>Odisha Site</option>
        <option value="ahmedabad_dashboard">Ahmedabad Site</option>
        <option value="delhi_dashboard">Delhi Site</option>
    </select>
</div>
        <div class="col-md-3 col-sm-4 col-4">
            <label for="year" class="form-label">Select Year</label>
            <select name="year" id="year" class="form-control">
                <?php for ($i = date('Y'); $i >= 2000; $i--): ?>
                    <option value="<?= $i ?>" <?= $i == $year ? 'selected' : '' ?>><?= $i ?></option>
                <?php endfor; ?>
                </select>
        </div>
        <div class="col-md-3 col-sm-4 col-4">
            <label for="month" class="form-label">Select Month</label>
            <select name="month" id="month" class="form-control">
                <?php foreach ($months as $key => $value): ?>
                    <option value="<?= $key ?>" <?= $key == $month ? 'selected' : '' ?>><?= $value ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 col-sm-4 col-4">
            <button type="submit" class="btn btn-primary  w-100">Filter</button>
        </div>
    </form>
        <div class="col-lg-6 col-12">
          <div class="row">
            <div class="col-lg-6 col-md-6 col-12">
              <div class="card">
                <span class="mask bg-dark opacity-10 border-radius-lg"></span>
                <div class="card-body p-3 position-relative">
                <div class="row">
                    <div class="col-8 text-start">
                    <img class='w-25 me-3 mb-0' src='assets/img/logos/teamwork.png' alt='logo'>  
                      <h5 class="text-white font-weight-bolder mb-0 mt-3">
                      <?= $total_employees; ?>
                      </h5>
                      <span class="text-white text-sm">Total Employee</span>
                    </div>
                    <div class="col-4">
                      <div class="dropdown text-end mb-6">
               
                      </div>
                      <p class="text-white text-sm text-end font-weight-bolder mt-auto mb-0"></p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-lg-6 col-md-6 col-12 mt-4 mt-md-0">
              <div class="card">
                <span class="mask bg-dark opacity-10 border-radius-lg"></span>
                <div class="card-body p-3 position-relative">
                <div class="row">
                    <div class="col-8 text-start">
                    <img class='w-25 me-3 mb-0' src='assets/img/logos/mark-on-the-calendar.png' alt='logo'>  
                      <h5 class="text-white font-weight-bolder mb-0 mt-3">
                      <?= count(array_filter($today_attendance, fn($a) => $a['status'] == 'Present')); ?>
                      </h5>
                      <span class="text-white text-sm">Employees Present Today</span>
                    </div>
                    <div class="col-4">
                      <div class="dropstart text-end mb-6">
                     
                      </div>
                      <p class="text-white text-sm text-end font-weight-bolder mt-auto mb-0"></p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="row mt-4">
            <div class="col-lg-6 col-md-6 col-12">
              <div class="card">
                <span class="mask bg-dark opacity-10 border-radius-lg"></span>
                <div class="card-body p-3 position-relative">
                  <div class="row">
                    <div class="col-8 text-start">
                    <img class='w-25 me-3 mb-0' src='assets/img/logos/remove.png' alt='logo'>  
                      <h5 class="text-white font-weight-bolder mb-0 mt-3">
                      <?= $employees_absent; ?>
                      </h5>
                      <span class="text-white text-sm">Employees Absent Today</span>
                    </div>
                    <div class="col-4">
                      <div class="dropdown text-end mb-6">
               
                      </div>
                      <p class="text-white text-sm text-end font-weight-bolder mt-auto mb-0"></p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-lg-6 col-md-6 col-12 mt-4 mt-md-0">
              <div class="card">
                <span class="mask bg-dark opacity-10 border-radius-lg"></span>
                <div class="card-body p-3 position-relative">
                  <div class="row">
                    <div class="col-8 text-start">
                    <img class='w-25 me-3 mb-0' src='assets/img/logos/leave.png' alt='logo'>  
                      <h5 class="text-white font-weight-bolder mb-0 mt-3">
                      <?= $total_pending_leaves; ?>
                      </h5>
                      <span class="text-white text-sm">Pending Leave Requests</span>
                    </div>
                    <div class="col-4">
                      <div class="dropstart text-end mb-6">
                     
                      </div>
                      <p class="text-white text-sm text-end font-weight-bolder mt-auto mb-0"></p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="row mt-4">
            <div class="col-lg-6 col-md-6 col-12">
              <div class="card">
                <span class="mask bg-dark opacity-10 border-radius-lg"></span>
                <div class="card-body p-3 position-relative">
                 <div class="row">
                    <div class="col-8 text-start">
                     
                         <img class='w-25 me-3 mb-0' src='assets/img/logos/working-time.png' alt='logo'>                     
                      <h5 class="text-white font-weight-bolder mb-0 mt-3">
                      <?= $total_working_days ?>
                      </h5>
                      <span class="text-white text-sm">Total Working Days</span>
                    </div>
                    <div class="col-4">
                      <div class="dropdown text-end mb-6">
                        
                      </div>
                      <p class="text-white text-sm text-end font-weight-bolder mt-auto mb-0"></p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-lg-6 col-md-6 col-12 mt-4 mt-md-0">
              <div class="card">
                <span class="mask bg-dark opacity-10 border-radius-lg"></span>
                <div class="card-body p-3 position-relative">
                  <div class="row">
                    <div class="col-8 text-start">
                    <img class='w-25 me-3 mb-0' src='assets/img/logos/birthday-cake.png' alt='logo'>  
                      <h6 class="text-white font-weight-bolder mb-0 mt-3">
                      <ul>
                        <?php foreach ($birthdays as $b) : ?>
                            <li><?= $b['name'] . " (" . $b['birthday'] . ")"; ?></li>
                        <?php endforeach; ?>
                    </ul>
                      </h6>
                      <span class="text-white text-sm">Upcoming Birthdays</span>
                    </div>
                    <div class="col-4">
                      <div class="dropstart text-end mb-6">
                     
                      </div>
                      <p class="text-white text-sm text-end font-weight-bolder mt-auto mb-0"></p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

               <div class="row mt-4">
            <div class="col-lg-6 col-md-6 col-12">
              <div class="card">
                <span class="mask bg-dark opacity-10 border-radius-lg"></span>
                <div class="card-body p-3 position-relative">
                  <div class="row">
                    <div class="col-8 text-start">
                     
                    <img class='w-25 me-3 mb-0' src='assets/img/logos/tortoise.png' alt='logo'>                   
                      <h5 class="text-white font-weight-bolder mb-0 mt-3">
                      <?= $total_late ?>
                      </h5>
                      <span class="text-white text-sm">Late Punch-Ins</span>
                    </div>
                    <div class="col-4">
                      <div class="dropdown text-end mb-6">
                        
                      </div>
                      <p class="text-white text-sm text-end font-weight-bolder mt-auto mb-0"></p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-lg-6 col-md-6 col-12 mt-4 mt-md-0">
              <div class="card">
                <span class="mask bg-dark opacity-10 border-radius-lg"></span>
                <div class="card-body p-3 position-relative">
                <div class="row">
                    <div class="col-8 text-start">
                    <img class='w-25 me-3 mb-0' src='assets/img/logos/running.png' alt='logo'>  
                      <h5 class="text-white font-weight-bolder mb-0 mt-3">
                      <?= $total_early ?>
                      </h5>
                      <span class="text-white text-sm">Early Punch-Outs</span>
                    </div>
                    <div class="col-4">
                      <div class="dropstart text-end mb-6">
                     
                      </div>
                      <p class="text-white text-sm text-end font-weight-bolder mt-auto mb-0"> </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
               <div class="row mt-4">
            <div class="col-lg-12 col-md-6 col-12">
              <div class="card">
                <span class="mask bg-dark opacity-10 border-radius-lg"></span>
                <div class="card-body p-3 position-relative">
                <div class="row">
                    <div class="col-8 text-start">
                    <img class='w-25 me-3 mb-0' src='assets/img/logos/money.png' alt='logo'>  
                      <h5 class="text-white font-weight-bolder mb-0 mt-3">
                      ₹<?= $total_salary_processed; ?>
                      </h5>
                      <span class="text-white text-sm">Total Salary Processed (This Month)</span>
                    </div>
                    <div class="col-4">
                      <div class="dropdown text-end mb-6">
               
                      </div>
                      <p class="text-white text-sm text-end font-weight-bolder mt-auto mb-0"></p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
       
          </div>
        </div>
        <div class="col-lg-6 col-12 mt-4 mt-lg-0">
          <div class="col-lg-12">
            <div class="row">
            <div class="row my-4">
        <div class="col-lg-12 col-md-6 mb-md-0 mb-4">
          <div class="card">
            <div class="card-header pb-0">
              <div class="row">
                <div class="col-lg-6 col-7">
                  <h6>Employee Attendance Chart Report</h6>
                
                </div>
                <div class="col-lg-6 col-5 my-auto text-end">
                  <div class="dropdown float-lg-end pe-4">
                   
                  </div>
                </div>
              </div>
            </div>
            <div class="card-body px-0 pb-2">
             

            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<canvas id="attendanceChart"></canvas>
<script>
    const ctx = document.getElementById('attendanceChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Present', 'Absent', 'On Leave'],
            datasets: [{
                label: 'Attendance Overview',
                data: [<?= $total_present; ?>, <?= $total_absent; ?>, <?= $total_on_leave; ?>],
                backgroundColor: ['#4caf50', '#f44336', '#ff9800']
            }]
        }
    });
</script>
            </div>
          </div>
        </div>
      </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row my-4">
        <div class="col-lg-12 col-md-6 mb-md-0 mb-4">
          <div class="card">
            <div class="card-header pb-0">
              <div class="row">
                <div class="col-lg-6 col-7">
                  <h6>RECENTLY PUNCHED IN EMPLOYEES</h6>
                
                </div>
                <div class="col-lg-6 col-5 my-auto text-end">
                  <div class="dropdown float-lg-end pe-4">
                   
                  </div>
                </div>
              </div>
            </div>
            <div class="card-body px-0 pb-2">
              <div class="table-responsive">
              <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Date</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Punch In</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Punch Out</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Status</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Working Hr</th>
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
                                        <td>
                                            <h6 class="mb-0 text-sm"><?= date('d M Y', strtotime($record['punch_in_time'])) ?></h6>
                                        </td>
                                        <td class="align-middle text-center">
                                            <span class="text-xs font-weight-bold"><?= $record['punch_in_time'] ? date('H:i:s', strtotime($record['punch_in_time'])) : '-' ?></span>
                                        </td>
                                        <td class="align-middle text-center">
                                            <span class="text-xs font-weight-bold"><?= $record['punch_out_time'] ? date('H:i:s', strtotime($record['punch_out_time'])) : '-' ?></span>
                                        </td>
                                        <td class="align-middle text-center">
                                            <span class="text-xs font-weight-bold"><?= ucfirst($record['status']) ?></span>
                                        </td>
                                        <td class="align-middle text-center">
                                            <span class="text-xs font-weight-bold"><?= $record['working_hours'] ?> hrs</span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                               
                            </tbody>
                        </table>
              </div>
            </div>
          </div>
        </div>
      </div>

<script>
   function redirectToSite() {
        var site = document.getElementById('site').value;
        if (site) {
            window.location.href = site; // Redirect to the selected site's page
        }
    }
</script>
      <?php include ("footer.php") ?>

