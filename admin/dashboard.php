<?php
include("header.php");

$months = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June',
    7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

$year = isset($_POST['year']) ? (int) $_POST['year'] : (int) date('Y');
$month = isset($_POST['month']) ? (int) $_POST['month'] : (int) date('m');
$selected_office = isset($_POST['office']) ? trim(urldecode($_POST['office'])) : '';

$total_days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$total_working_days = 0;

$holiday_query = $conn->prepare("
    SELECT start_date
    FROM events
    WHERE YEAR(start_date) = ? AND MONTH(start_date) = ?
");
$holiday_query->bind_param("ii", $year, $month);
$holiday_query->execute();
$holidays = $holiday_query->get_result()->fetch_all(MYSQLI_ASSOC);
$holiday_dates = array_column($holidays, 'start_date');

for ($day = 1; $day <= $total_days_in_month; $day++) {
    $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
    $day_of_week = (int) date('N', strtotime($date));
    if ($day_of_week < 7 && !in_array($date, $holiday_dates, true)) {
        $total_working_days++;
    }
}

if (!empty($selected_office)) {
    $total_employees_query = $conn->prepare("
        SELECT COUNT(*) AS total_employees
        FROM employees
        WHERE status = 'Active' AND office = ?
    ");
    $total_employees_query->bind_param("s", $selected_office);
    $total_employees_query->execute();
    $total_employees = (int) $total_employees_query->get_result()->fetch_assoc()['total_employees'];
} else {
    $total_employees_query = $conn->query("
        SELECT COUNT(*) AS total_employees
        FROM employees
        WHERE status = 'Active'
    ");
    $total_employees = (int) $total_employees_query->fetch_assoc()['total_employees'];
}

$today_date = date('Y-m-d');
$today = $today_date;

if (!empty($selected_office)) {
    $today_attendance_query = $conn->prepare("
        SELECT a.status
        FROM attendance a
        JOIN employees e ON a.employee_id = e.id
        WHERE DATE(a.punch_in_time) = ? AND e.office = ?
    ");
    $today_attendance_query->bind_param("ss", $today_date, $selected_office);
} else {
    $today_attendance_query = $conn->prepare("
        SELECT status
        FROM attendance
        WHERE DATE(punch_in_time) = ?
    ");
    $today_attendance_query->bind_param("s", $today_date);
}
$today_attendance_query->execute();
$today_attendance = $today_attendance_query->get_result()->fetch_all(MYSQLI_ASSOC);

$today_present_count = count(array_filter($today_attendance, fn($attendance_row) => $attendance_row['status'] === 'Present'));
$employees_absent = max(0, $total_employees - $today_present_count);

if (!empty($selected_office)) {
    $attendance_query = $conn->prepare("
        SELECT a.*
        FROM attendance a
        JOIN employees e ON a.employee_id = e.id
        WHERE YEAR(a.punch_in_time) = ? AND MONTH(a.punch_in_time) = ? AND e.office = ?
    ");
    $attendance_query->bind_param("iis", $year, $month, $selected_office);
} else {
    $attendance_query = $conn->prepare("
        SELECT *
        FROM attendance
        WHERE YEAR(punch_in_time) = ? AND MONTH(punch_in_time) = ?
    ");
    $attendance_query->bind_param("ii", $year, $month);
}
$attendance_query->execute();
$attendance = $attendance_query->get_result()->fetch_all(MYSQLI_ASSOC);

if (!empty($selected_office)) {
    $pending_leave_query = $conn->prepare("
        SELECT COUNT(*) AS pending_leaves
        FROM leave_requests lr
        JOIN employees e ON lr.employee_id = e.id
        WHERE lr.status = 'Pending' AND e.office = ?
    ");
    $pending_leave_query->bind_param("s", $selected_office);
    $pending_leave_query->execute();
    $total_pending_leaves = (int) $pending_leave_query->get_result()->fetch_assoc()['pending_leaves'];
} else {
    $pending_leave_query = $conn->query("
        SELECT COUNT(*) AS pending_leaves
        FROM leave_requests
        WHERE status = 'Pending'
    ");
    $total_pending_leaves = (int) $pending_leave_query->fetch_assoc()['pending_leaves'];
}

if (!empty($selected_office)) {
    $salary_query = $conn->prepare("
        SELECT SUM(s.net_salary) AS total_salary
        FROM salary s
        JOIN employees e ON s.employee_id = e.id
        WHERE s.year = ? AND s.month = ? AND e.office = ?
    ");
    $salary_query->bind_param("iis", $year, $month, $selected_office);
} else {
    $salary_query = $conn->prepare("
        SELECT SUM(net_salary) AS total_salary
        FROM salary
        WHERE year = ? AND month = ?
    ");
    $salary_query->bind_param("ii", $year, $month);
}
$salary_query->execute();
$total_salary_processed = (float) ($salary_query->get_result()->fetch_assoc()['total_salary'] ?? 0);

if (!empty($selected_office)) {
    $birthdays_query = $conn->prepare("
        SELECT name, DATE_FORMAT(dob, '%d %M') AS birthday
        FROM employees
        WHERE MONTH(dob) = ? AND office = ?
        ORDER BY DAY(dob) ASC
    ");
    $birthdays_query->bind_param("is", $month, $selected_office);
} else {
    $birthdays_query = $conn->prepare("
        SELECT name, DATE_FORMAT(dob, '%d %M') AS birthday
        FROM employees
        WHERE MONTH(dob) = ?
        ORDER BY DAY(dob) ASC
    ");
    $birthdays_query->bind_param("i", $month);
}
$birthdays_query->execute();
$birthdays = $birthdays_query->get_result()->fetch_all(MYSQLI_ASSOC);

if (!empty($selected_office)) {
    $anniversaries_query = $conn->prepare("
        SELECT name, DATE_FORMAT(anniversary, '%d %M') AS anniversaryday
        FROM employees
        WHERE MONTH(anniversary) = ? AND office = ?
        ORDER BY DAY(anniversary) ASC
    ");
    $anniversaries_query->bind_param("is", $month, $selected_office);
} else {
    $anniversaries_query = $conn->prepare("
        SELECT name, DATE_FORMAT(anniversary, '%d %M') AS anniversaryday
        FROM employees
        WHERE MONTH(anniversary) = ?
        ORDER BY DAY(anniversary) ASC
    ");
    $anniversaries_query->bind_param("i", $month);
}
$anniversaries_query->execute();
$anniversaries = $anniversaries_query->get_result()->fetch_all(MYSQLI_ASSOC);

if (!empty($selected_office)) {
    $upcoming_birthdays_query = $conn->prepare("
        SELECT name, DATE_FORMAT(dob, '%d %M') AS birthday
        FROM employees
        WHERE DATE_FORMAT(dob, '%m-%d') BETWEEN DATE_FORMAT(?, '%m-%d')
        AND DATE_FORMAT(DATE_ADD(?, INTERVAL 60 DAY), '%m-%d')
        AND office = ?
        ORDER BY DATE_FORMAT(dob, '%m-%d') ASC
    ");
    $upcoming_birthdays_query->bind_param("sss", $today, $today, $selected_office);
} else {
    $upcoming_birthdays_query = $conn->prepare("
        SELECT name, DATE_FORMAT(dob, '%d %M') AS birthday
        FROM employees
        WHERE DATE_FORMAT(dob, '%m-%d') BETWEEN DATE_FORMAT(?, '%m-%d')
        AND DATE_FORMAT(DATE_ADD(?, INTERVAL 60 DAY), '%m-%d')
        ORDER BY DATE_FORMAT(dob, '%m-%d') ASC
    ");
    $upcoming_birthdays_query->bind_param("ss", $today, $today);
}
$upcoming_birthdays_query->execute();
$upcoming_birthdays = $upcoming_birthdays_query->get_result()->fetch_all(MYSQLI_ASSOC);

if (!empty($selected_office)) {
    $upcoming_anniversaries_query = $conn->prepare("
        SELECT name, DATE_FORMAT(anniversary, '%d %M') AS anniversaryday
        FROM employees
        WHERE DATE_FORMAT(anniversary, '%m-%d') BETWEEN DATE_FORMAT(?, '%m-%d')
        AND DATE_FORMAT(DATE_ADD(?, INTERVAL 60 DAY), '%m-%d')
        AND office = ?
        ORDER BY DATE_FORMAT(anniversary, '%m-%d') ASC
    ");
    $upcoming_anniversaries_query->bind_param("sss", $today, $today, $selected_office);
} else {
    $upcoming_anniversaries_query = $conn->prepare("
        SELECT name, DATE_FORMAT(anniversary, '%d %M') AS anniversaryday
        FROM employees
        WHERE DATE_FORMAT(anniversary, '%m-%d') BETWEEN DATE_FORMAT(?, '%m-%d')
        AND DATE_FORMAT(DATE_ADD(?, INTERVAL 60 DAY), '%m-%d')
        ORDER BY DATE_FORMAT(anniversary, '%m-%d') ASC
    ");
    $upcoming_anniversaries_query->bind_param("ss", $today, $today);
}
$upcoming_anniversaries_query->execute();
$upcoming_anniversaries = $upcoming_anniversaries_query->get_result()->fetch_all(MYSQLI_ASSOC);

$total_possible_hours = $total_working_days * 8;
$total_present = 0;
$total_absent = 0;
$total_on_leave = 0;
$total_late = 0;
$total_early = 0;
$total_working_hours = 0;
$punchin_time = "09:30:00";
$punchout_time = "18:30:00";

foreach ($attendance as $record) {
    if ($record['status'] === 'Present') {
        $total_present++;
        $punch_in_time = date('H:i:s', strtotime($record['punch_in_time']));
        $punch_out_time = date('H:i:s', strtotime($record['punch_out_time']));

        if ($punch_in_time > $punchin_time) {
            $total_late++;
        }

        if (!empty($record['punch_out_time']) && $punch_out_time < $punchout_time) {
            $total_early++;
        }

        $total_working_hours += (float) $record['working_hours'];
    } elseif ($record['status'] === 'Absent') {
        $total_absent++;
    } elseif ($record['status'] === 'On Leave') {
        $total_on_leave++;
    }
}

$offices_query = $conn->query("SELECT office_name, state_name FROM offices ORDER BY office_name ASC");
$offices = $offices_query->fetch_all(MYSQLI_ASSOC);

$attendance_rate = $total_employees > 0 ? round(($today_present_count / $total_employees) * 100) : 0;
$formatted_working_hours = number_format((float) $total_working_hours, 1);
$formatted_salary_processed = number_format($total_salary_processed, 2);
$selected_office_label = 'All Offices';

foreach ($offices as $office) {
    $office_value = $office['office_name'] . "_" . $office['state_name'];
    if ($selected_office === $office_value) {
        $selected_office_label = $office['office_name'] . ' (' . $office['state_name'] . ')';
        break;
    }
}

$upcoming_celebrations = [];
foreach ($upcoming_birthdays as $birthday) {
    $upcoming_celebrations[] = [
        'type' => 'Birthday',
        'name' => $birthday['name'],
        'date' => $birthday['birthday'],
        'badge_class' => 'bg-gradient-info'
    ];
}
foreach ($upcoming_anniversaries as $anniversary) {
    $upcoming_celebrations[] = [
        'type' => 'Anniversary',
        'name' => $anniversary['name'],
        'date' => $anniversary['anniversaryday'],
        'badge_class' => 'bg-gradient-success'
    ];
}
?>

<style>
.dashboard-shell {
  padding-bottom: 1.5rem;
}

.dashboard-muted {
  color: #6b7280;
}

.filter-card,
.panel-card,
.metric-card {
  border: 1px solid rgba(87, 96, 108, 0.12);
  border-radius: 22px;
  box-shadow: 0 14px 34px rgba(31, 41, 55, 0.06);
  background: #fff;
}

.filter-card {
  padding: 1.25rem;
  margin-bottom: 1.5rem;
}

.filter-card .form-label {
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: #6b7280;
}

.filter-card .form-select {
  min-height: 50px;
  border-radius: 14px;
  border: 1px solid #d8dee7;
  color: #374151;
}

.metric-card {
  padding: 1.15rem;
  height: 100%;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.metric-card.metric-action {
  cursor: pointer;
}

.metric-card.metric-action:hover {
  transform: translateY(-2px);
  box-shadow: 0 18px 38px rgba(31, 41, 55, 0.09);
}

.metric-icon {
  width: 52px;
  height: 52px;
  border-radius: 16px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  margin-bottom: 1rem;
}

.metric-icon.metric-slate { background: #eef2f7; color: #374151; }
.metric-icon.metric-blue { background: #e7f0fb; color: #275ea8; }
.metric-icon.metric-red { background: #fcebea; color: #d14343; }
.metric-icon.metric-amber { background: #fff4dd; color: #b7791f; }
.metric-icon.metric-green { background: #e8f7ef; color: #1f8f57; }
.metric-icon.metric-purple { background: #f2ecff; color: #6d4bc3; }

.metric-label {
  color: #6b7280;
  font-size: 0.85rem;
  margin-bottom: 0.35rem;
}

.metric-value {
  color: #111827;
  font-size: 1.85rem;
  font-weight: 800;
  line-height: 1.1;
  margin-bottom: 0.45rem;
}

.metric-footnote {
  color: #94a3b8;
  font-size: 0.78rem;
}

.panel-card {
  padding: 1.25rem;
  height: 100%;
}

.panel-title {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1rem;
}

.panel-title h5 {
  margin: 0;
  color: #1f2937;
}

.mini-stat-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.mini-stat {
  border-radius: 16px;
  background: #f8fafc;
  border: 1px solid #ebeff5;
  padding: 0.9rem;
}

.mini-stat span {
  display: block;
  color: #6b7280;
  font-size: 0.78rem;
}

.mini-stat strong {
  display: block;
  color: #111827;
  font-size: 1.15rem;
  margin-top: 0.25rem;
}

.event-card {
  border-radius: 18px;
  background: linear-gradient(135deg, #f7f9fc 0%, #eef2f6 100%);
  border: 1px solid #e6ebf2;
  padding: 1.1rem;
}

.celebration-list .list-group-item {
  border: 0;
  border-bottom: 1px solid #eef2f7;
  padding-left: 0;
  padding-right: 0;
}

.celebration-list .list-group-item:last-child {
  border-bottom: 0;
}

.celebration-icon {
  width: 38px;
  height: 38px;
  border-radius: 12px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #f3f5f8;
  color: #4b5563;
  margin-right: 0.75rem;
}

.attendance-chart-wrap {
  position: relative;
  min-height: 330px;
}

.employee-list-modal-dialog {
  max-width: 860px;
}

.employee-list-modal {
  border: 1px solid rgba(87, 96, 108, 0.12);
  border-radius: 26px;
  overflow: hidden;
  box-shadow: 0 30px 70px rgba(15, 23, 42, 0.18);
}

.employee-list-modal .modal-header {
  display: flex;
  justify-content: space-between;
  padding: 1.35rem 1.5rem 1rem;
  border-bottom: 1px solid #e9eef5;
  align-items: flex-start;
}

.employee-list-modal-title {
  margin: 0;
  color: #111827;
  font-size: 1.55rem;
  font-weight: 800;
  letter-spacing: -0.03em;
}

.employee-list-modal-subtitle {
  margin: 0.35rem 0 0;
  color: #6b7280;
  font-size: 0.92rem;
}

.employee-list-modal-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-left: auto;
}

.employee-list-export-btn,
.employee-list-close-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 46px;
  padding: 0.78rem 1.25rem;
  border-radius: 14px;
  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  text-decoration: none;
  white-space: nowrap;
}

.employee-list-export-btn {
  border: 1px solid #b9dec8;
  background: linear-gradient(135deg, #dff5e6 0%, #c8ebd5 100%);
  color: #21543a;
}

.employee-list-close-btn {
  border: 1px solid #d4dbe6;
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  color: #475569;
}

.employee-list-modal .btn-close {
  margin: 0 0 0 0.25rem;
}

.employee-list-modal .modal-body {
  padding: 1.25rem 1.5rem 1.5rem;
}

.employee-list-label {
  display: block;
  margin-bottom: 0.7rem;
  color: #475569;
  font-size: 0.76rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.employee-list-collection {
  margin: 0;
  border: 1px solid #e6ebf2;
  border-radius: 18px;
  overflow: hidden;
}

.employee-list-collection .list-group-item {
  border: 0;
  border-bottom: 1px solid #edf1f5;
  padding: 1rem 1.1rem;
}

.employee-list-collection .list-group-item:last-child {
  border-bottom: 0;
}

.employee-list-item {
  background: #ffffff;
  overflow-x: auto;
}

.employee-list-row {
  display: inline-block;
  min-width: max-content;
}

.employee-list-line {
  display: inline-block;
  color: #111827;
  font-size: 0.96rem;
  font-weight: 700;
  line-height: 1.5;
  white-space: nowrap !important;
}

.employee-list-placeholder,
.employee-list-empty {
  text-align: center;
  color: #64748b;
  font-weight: 600;
  background: #ffffff;
}

.employee-list-empty {
  color: #b42318;
}

/* Improvements for Attendance Summary and Birthday card visuals */
/* Attendance Summary: vertically center and balance mini-stats */
/* Attendance Summary: vertically align header near top and enlarge mini-stats */
#attendanceSummaryPanel {
  display: flex;
  flex-direction: column;
  justify-content: flex-start; /* keep heading near top */
}
#attendanceSummaryPanel .mini-stat-grid {
  display: flex;
  gap: 1rem;
  justify-content: center;
  margin-bottom: 0.75rem;
}
#attendanceSummaryPanel .mini-stat {
  padding: 1.15rem 1.25rem;
  text-align: center;
  flex: 0 0 32%;
  box-sizing: border-box;
}
#attendanceSummaryPanel .mini-stat span {
  display: block;
  font-size: 0.9rem;
  color: #6b7280;
}
#attendanceSummaryPanel .mini-stat strong {
  font-size: 1.6rem;
  color: #111827;
  margin-top: 0.35rem;
}

/* Birthday panel: ensure centered column layout and consistent icon sizing */
#birthdayPanel .event-card {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 180px;
  padding: 1.25rem;
  background: linear-gradient(135deg, #f7f9fc 0%, #eef2f6 100%);
}

#birthdayPanel .carousel-inner,
#birthdayPanel .carousel-item {
  width: 100%;
}

#birthdayPanel .carousel-item .text-center {
  padding: 0.75rem 0.5rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

#birthdayPanel .metric-icon {
  width: 56px;
  height: 56px;
  border-radius: 14px;
  font-size: 1.25rem;
  margin-bottom: 0.6rem;
}

@media (max-width: 767.98px) {
  #attendanceSummaryPanel {
    justify-content: flex-start;
  }
  #attendanceSummaryPanel .mini-stat-grid {
    flex-direction: column;
    align-items: stretch;
  }
  #attendanceSummaryPanel .mini-stat { flex: none; }

  .employee-list-modal .modal-header,
  .employee-list-modal .modal-body,
  .employee-list-modal .modal-footer {
    padding-left: 1rem;
    padding-right: 1rem;
  }

  .employee-list-modal-actions {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
  }
}

@media (max-width: 991.98px) {
  .mini-stat-grid {
    grid-template-columns: 1fr;
  }
}
</style>

<div class="container-fluid container-fluid-main dashboard-shell py-4">
  <form method="POST" class="filter-card">
    <div class="row g-3 align-items-end">
      <div class="col-lg-4 col-md-6">
        <label for="office" class="form-label">Select Site</label>
        <select name="office" id="office" class="form-select">
          <option value="">All Offices</option>
          <?php foreach ($offices as $office): ?>
            <?php $office_value = $office['office_name'] . "_" . $office['state_name']; ?>
            <option value="<?= htmlspecialchars($office_value) ?>" <?= $selected_office === $office_value ? 'selected' : '' ?>>
              <?= htmlspecialchars($office['office_name']) ?> (<?= htmlspecialchars($office['state_name']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-lg-3 col-md-6">
        <label for="year" class="form-label">Select Year</label>
        <select name="year" id="year" class="form-select">
          <?php for ($i = (int) date('Y'); $i >= 2000; $i--): ?>
            <option value="<?= $i ?>" <?= $i === $year ? 'selected' : '' ?>><?= $i ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="col-lg-3 col-md-6">
        <label for="month" class="form-label">Select Month</label>
        <select name="month" id="month" class="form-select">
          <?php foreach ($months as $key => $value): ?>
            <option value="<?= $key ?>" <?= $key === $month ? 'selected' : '' ?>><?= $value ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-lg-2 col-md-6">
        <button type="submit" class="btn bg-gradient-dark w-100 mb-0">Apply Filters</button>
      </div>
    </div>
  </form>

  <div class="row g-4">
    <div class="col-xl-8">
      <div class="row g-4">
        <div class="col-md-6 col-xl-4">
          <div class="metric-card metric-action" onclick="redirectToManageEmployee()">
            <div class="metric-icon metric-slate"><i class="bi bi-people-fill"></i></div>
            <div class="metric-label">Total Active Employees</div>
            <div class="metric-value"><?= $total_employees; ?></div>
            <div class="metric-footnote">Across <?= htmlspecialchars($selected_office_label) ?></div>
          </div>
        </div>
        <div class="col-md-6 col-xl-4">
          <div class="metric-card metric-action" onclick="fetchEmployeeList('present')">
            <div class="metric-icon metric-blue"><i class="bi bi-person-check-fill"></i></div>
            <div class="metric-label">Present Today</div>
            <div class="metric-value"><?= $today_present_count; ?></div>
            <div class="metric-footnote"><?= $attendance_rate; ?>% of active team</div>
          </div>
        </div>
        <div class="col-md-6 col-xl-4">
          <div class="metric-card metric-action" onclick="fetchEmployeeList('absent')">
            <div class="metric-icon metric-red"><i class="bi bi-person-x-fill"></i></div>
            <div class="metric-label">Absent Today</div>
            <div class="metric-value"><?= $employees_absent; ?></div>
            <div class="metric-footnote">Needs attendance follow-up</div>
          </div>
        </div>
        <div class="col-md-6 col-xl-4">
          <div class="metric-card metric-action" onclick="redirectToManageLeave()">
            <div class="metric-icon metric-amber"><i class="bi bi-calendar2-week-fill"></i></div>
            <div class="metric-label">Pending Leave Requests</div>
            <div class="metric-value"><?= $total_pending_leaves; ?></div>
            <div class="metric-footnote">Awaiting review and approval</div>
          </div>
        </div>
        <div class="col-md-6 col-xl-4">
          <div class="metric-card metric-action" onclick="redirectToLateReport()">
            <div class="metric-icon metric-purple"><i class="bi bi-clock-history"></i></div>
            <div class="metric-label">Late Punch-Ins</div>
            <div class="metric-value"><?= $total_late; ?></div>
            <div class="metric-footnote">For <?= htmlspecialchars($months[$month]) ?></div>
          </div>
        </div>
        <div class="col-md-6 col-xl-4">
          <div class="metric-card">
            <div class="metric-icon metric-green"><i class="bi bi-box-arrow-right"></i></div>
            <div class="metric-label">Early Punch-Outs</div>
            <div class="metric-value"><?= $total_early; ?></div>
            <div class="metric-footnote">Potential productivity risk</div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="metric-card metric-action" onclick="redirectToManageSalary()">
            <div class="metric-icon metric-slate"><i class="bi bi-currency-rupee"></i></div>
            <div class="metric-label">Salary Processed</div>
            <div class="metric-value">Rs <?= $formatted_salary_processed; ?></div>
            <div class="metric-footnote">Processed in <?= htmlspecialchars($months[$month]) ?> <?= htmlspecialchars((string) $year) ?></div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="metric-card metric-action" onclick="window.location.href='monthly_working_hours?month=<?= $month ?>&year=<?= $year ?>'">
            <div class="metric-icon metric-blue"><i class="bi bi-hourglass-split"></i></div>
            <div class="metric-label">Total Working Hours</div>
            <div class="metric-value"><?= $formatted_working_hours; ?></div>
            <div class="metric-footnote"><?= $total_working_days; ?> working days in this month</div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-4 d-flex flex-column">
      <div id="attendanceSummaryPanel" class="panel-card mb-4">
        <div class="panel-title" style="padding-bottom:0.5rem;">
          <div>
            <h5 class="fw-bold">Attendance Summary</h5>
            <div class="dashboard-muted small">A quick pulse for the selected month</div>
          </div>
        </div>
        <div class="mini-stat-grid">
          <div class="mini-stat">
            <span>Working Days</span>
            <strong><?= $total_working_days; ?></strong>
          </div>
          <div class="mini-stat">
            <span>On Leave</span>
            <strong><?= $total_on_leave; ?></strong>
          </div>
          <div class="mini-stat">
            <span>Possible Hours</span>
            <strong><?= $total_possible_hours; ?></strong>
          </div>
        </div>
      </div>

      <div id="birthdayPanel" class="panel-card h-100">
        <div class="panel-title" style="padding-bottom:0.5rem;">
          <div style="display:flex;gap:0.5rem;align-items:center;justify-content:center;">
            <span class="badge bg-light text-dark">Birthday</span>
            <span class="badge bg-light text-dark">Anniversary</span>
          </div>
        </div>
        <div class="event-card">
          <?php if (empty($birthdays) && empty($anniversaries)) : ?>
            <div class="text-center py-3">
              <div class="metric-icon metric-slate mx-auto"><i class="bi bi-stars"></i></div>
              <h6 class="mb-1">No celebrations this month</h6>
              <p class="dashboard-muted mb-0 small">Birthdays and anniversaries will appear here automatically.</p>
            </div>
          <?php else : ?>
            <div id="birthdayAnniversarySlider" class="carousel slide" data-bs-ride="carousel">
              <div class="carousel-inner">
                <?php $index = 0; ?>
                <?php foreach ($birthdays as $birthday) : ?>
                  <div class="carousel-item <?= $index === 0 ? 'active' : ''; ?>">
                    <div class="text-center py-3">
                      <div class="metric-icon metric-amber mx-auto"><i class="bi bi-cake2-fill"></i></div>
                      <h6 class="mb-1"><?= htmlspecialchars($birthday['name']); ?></h6>
                      <p class="dashboard-muted mb-0">Birthday on <?= htmlspecialchars($birthday['birthday']); ?></p>
                    </div>
                  </div>
                  <?php $index++; ?>
                <?php endforeach; ?>
                <?php foreach ($anniversaries as $anniversary) : ?>
                  <div class="carousel-item <?= $index === 0 ? 'active' : ''; ?>">
                    <div class="text-center py-3">
                      <div class="metric-icon metric-green mx-auto"><i class="bi bi-balloon-heart-fill"></i></div>
                      <h6 class="mb-1"><?= htmlspecialchars($anniversary['name']); ?></h6>
                      <p class="dashboard-muted mb-0">Anniversary on <?= htmlspecialchars($anniversary['anniversaryday']); ?></p>
                    </div>
                  </div>
                  <?php $index++; ?>
                <?php endforeach; ?>
              </div>
              <button class="carousel-control-prev" type="button" data-bs-target="#birthdayAnniversarySlider" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
              </button>
              <button class="carousel-control-next" type="button" data-bs-target="#birthdayAnniversarySlider" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
              </button>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-xl-8">
      <div class="panel-card">
        <div class="panel-title">
          <div>
            <h5 class="fw-bold">Employee Attendance Report</h5>
            <div class="dashboard-muted small">Monthly present, absent and leave distribution</div>
          </div>
        </div>
        <div class="attendance-chart-wrap">
          <canvas id="attendanceChart"></canvas>
        </div>
      </div>
    </div>

    <div class="col-xl-4">
      <div class="panel-card h-100">
        <div class="panel-title">
          <div>
            <h5 class="fw-bold">Upcoming Celebrations</h5>
            <div class="dashboard-muted small">Birthdays and anniversaries in the next 60 days</div>
          </div>
        </div>
        <div class="celebration-list">
          <?php if (empty($upcoming_celebrations)) : ?>
            <div class="text-center py-4">
              <div class="metric-icon metric-slate mx-auto"><i class="bi bi-calendar2-heart"></i></div>
              <h6 class="mb-1">No upcoming events</h6>
              <p class="dashboard-muted mb-0 small">The next celebration will show here when available.</p>
            </div>
          <?php else : ?>
            <ul class="list-group list-group-flush">
              <?php foreach ($upcoming_celebrations as $event) : ?>
                <li class="list-group-item d-flex align-items-center justify-content-between">
                  <div class="d-flex align-items-center">
                    <span class="celebration-icon">
                      <i class="bi <?= $event['type'] === 'Birthday' ? 'bi-cake2-fill' : 'bi-balloon-heart-fill'; ?>"></i>
                    </span>
                    <div>
                      <div class="fw-semibold text-dark"><?= htmlspecialchars($event['name']); ?></div>
                      <div class="small dashboard-muted"><?= htmlspecialchars($event['type']); ?></div>
                    </div>
                  </div>
                  <span class="badge <?= $event['badge_class']; ?>"><?= htmlspecialchars($event['date']); ?></span>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="employeeListModal" tabindex="-1" aria-labelledby="employeeListModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered employee-list-modal-dialog">
    <div class="modal-content employee-list-modal">
      <div class="modal-header">
        <div>
          <h5 class="modal-title employee-list-modal-title" id="employeeListModalLabel">Employee List</h5>
          <p class="employee-list-modal-subtitle">Review the employee attendance list and export it when needed.</p>
        </div>
        <div class="employee-list-modal-actions">
        <form id="exportForm" action="export_employees_csv" method="post" target="_blank" class="d-inline">
          <input type="hidden" name="type" id="exportType" value="present">
          <input type="hidden" name="office" id="exportOffice" value="<?= htmlspecialchars($selected_office) ?>">
          <button type="submit" class="btn mb-0 employee-list-export-btn">Export to CSV</button>
        </form>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
      </div>
      <div class="modal-body">
        <label class="employee-list-label">Employees</label>
        <ul id="employeeList" class="list-group employee-list-collection"></ul>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn mb-0 employee-list-close-btn" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
const ctx = document.getElementById('attendanceChart').getContext('2d');
new Chart(ctx, {
  type: 'bar',
  data: {
    labels: ['Present', 'Absent', 'On Leave'],
    datasets: [{
      label: 'Attendance Overview',
      data: [<?= $total_present; ?>, <?= $total_absent; ?>, <?= $total_on_leave; ?>],
      backgroundColor: ['#5b8def', '#ef6a6a', '#f0b45a'],
      borderRadius: 12,
      borderSkipped: false,
      maxBarThickness: 52
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        display: true,
        labels: {
          boxWidth: 12,
          color: '#475569',
          usePointStyle: true,
          pointStyle: 'circle'
        }
      }
    },
    scales: {
      x: {
        grid: {
          display: false
        },
        ticks: {
          color: '#64748b'
        }
      },
      y: {
        beginAtZero: true,
        grid: {
          color: 'rgba(148, 163, 184, 0.18)'
        },
        ticks: {
          color: '#64748b',
          precision: 0
        }
      }
    }
  }
});

function fetchEmployeeList(type) {
  const modalTitle = type === 'present' ? 'Present Employee List' : 'Absent Employee List';
  const office = document.getElementById('office').value;
  $('#employeeListModal .modal-title').text(modalTitle);
  $('#exportType').val(type);
  $('#exportOffice').val(office);
  $('#employeeList').html("<li class='list-group-item employee-list-placeholder'>Loading...</li>");

  const modal = new bootstrap.Modal(document.getElementById('employeeListModal'));
  modal.show();

  $.ajax({
    url: 'fetch_employees',
    type: 'POST',
    cache: false,
    data: {
      type: type,
      office: office,
      _: Date.now()
    },
    success: function(response) {
      $('#employeeList').html(response);
    },
    error: function() {
      $('#employeeList').html("<li class='list-group-item employee-list-empty'>Failed to load data.</li>");
    }
  });
}

function redirectToManageEmployee() {
  window.location.href = 'manage_employee';
}

function redirectToManageLeave() {
  window.location.href = 'manage_leave';
}

function redirectToManageSalary() {
  window.location.href = 'manage_salary';
}

function redirectToLateReport() {
  const year = document.getElementById('year').value;
  const month = document.getElementById('month').value;
  const office = document.getElementById('office').value;
  window.location.href = `monthly_late_report?year=${year}&month=${month}&office=${encodeURIComponent(office)}`;
}

function equalizePanels() {
  const a = document.getElementById('attendanceSummaryPanel');
  const b = document.getElementById('birthdayPanel');
  if (!a || !b) return;
  // reset
  a.style.minHeight = '';
  b.style.minHeight = '';
  // only apply on wide screens
  if (window.innerWidth < 768) return;
  const ha = a.getBoundingClientRect().height;
  const hb = b.getBoundingClientRect().height;
  const maxH = Math.max(ha, hb);
  a.style.minHeight = Math.ceil(maxH) + 'px';
  b.style.minHeight = Math.ceil(maxH) + 'px';
}

window.addEventListener('load', equalizePanels);
window.addEventListener('resize', function(){
  clearTimeout(window._equalizeTimer);
  window._equalizeTimer = setTimeout(equalizePanels, 120);
});
</script>

<?php include("footer.php"); ?>
