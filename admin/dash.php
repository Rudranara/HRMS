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
    WHERE YEAR(punch_in_time) = ? AND MONTH(punch_in_time) = ?
");
$stmt->bind_param("ii", $year, $month);
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
    <!-- End Navbar -->
    <div class="container-fluid py-4">
      <div class="row">
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
        <div class="col-lg-6 col-12">
          <div class="row">
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
                      <p class="text-white text-sm text-end font-weight-bolder mt-auto mb-0">2024</p>
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
                      <?= $total_present ?>
                      </h5>
                      <span class="text-white text-sm">Days Present </span>
                    </div>
                    <div class="col-4">
                      <div class="dropstart text-end mb-6">
                      </div>
                      <p class="text-white text-sm text-end font-weight-bolder mt-auto mb-0"><?= $total_present_percentage ?>%</p>
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
                      <?= $total_absent ?>
                      </h5>
                      <span class="text-white text-sm">Days Absent</span>
                    </div>
                    <div class="col-4">
                      <div class="dropdown text-end mb-6">
               
                      </div>
                      <p class="text-white text-sm text-end font-weight-bolder mt-auto mb-0"><?= $total_absent_percentage ?>%</p>
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
                      <?= $total_on_leave ?> 
                      </h5>
                      <span class="text-white text-sm">Days On Leave</span>
                    </div>
                    <div class="col-4">
                      <div class="dropstart text-end mb-6">
                     
                      </div>
                      <p class="text-white text-sm text-end font-weight-bolder mt-auto mb-0"><?= $total_on_leave_percentage ?>%</p>
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
                      <p class="text-white text-sm text-end font-weight-bolder mt-auto mb-0"> <?= $total_late_percentage ?>%</p>
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
                      <p class="text-white text-sm text-end font-weight-bolder mt-auto mb-0"> <?= $total_early_percentage ?>%</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-lg-12 col-md-6 col-12 mt-4 ">
              <div class="card">
                <span class="mask bg-dark opacity-10 border-radius-lg"></span>
                <div class="card-body p-3 position-relative">
                  <div class="row">
                    <div class="col-8 text-start">
                    <img class='w-15 me-3 mb-0' src='assets/img/logos/working-hours.png' alt='logo'>  
                      <h5 class="text-white font-weight-bolder mb-0 mt-3">
                      <?= $total_working_hours ?> hrs 
                      </h5>
                      <span class="text-white text-sm">Total Working Hours</span>
                    </div>
                    <div class="col-4">
                      <div class="dropstart text-end mb-6">
                     
                      </div>
                      <p class="text-white text-sm text-end font-weight-bolder mt-auto mb-0"> <?= $total_working_hours_percentage ?>%</p>
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
                  <h6>EMPLOYEE ID CARD</h6>
                
                </div>
                <div class="col-lg-6 col-5 my-auto text-end">
                  <div class="dropdown float-lg-end pe-4">
                   
                  </div>
                </div>
              </div>
            </div>
            <div class="card-body px-0 pb-2">
             

            <div class="id-card-tag"></div>
	<div class="id-card-tag-strip"></div>
	<div class="id-card-hook"></div>
	<div class="id-card-holder">
		<div class="id-card">
			<div class="header">
				<img src="assets/img/logos/greenwey_logo.png">
			</div>
			<div class="photo">
				<img src="assets/img/team6.png">
			</div>
      <div class="name">
			<h2><?= htmlspecialchars($admin_name) ?></h2>
      </div>
			<div class="qr-code">
				
			</div>
      <div class="emp_id">
			<h3><?= htmlspecialchars($admin_roll) ?></h3>
      <h3><?= htmlspecialchars($employee_id) ?></h3>
      </div>
			<hr>
      <div class="address">
			<p><strong>Plot No.167</strong> , Saheed Nagar  <p>
			<p>Bhubaneswar, Odisha, India. <strong>751007,</strong></p>
			<p>Ph: +91 7978120920, 7978120920</p>
      </div>

		</div>
	</div>
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


      <?php include ("footer.php") ?>

