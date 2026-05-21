<?php
include("header.php");

if (!isset($_SESSION['employee_id'])) {
    echo "Access Denied! Please log in.";
    exit;
}

$employee_id = (int) $_SESSION['employee_id'];

$employee_stmt = $conn->prepare("
    SELECT working_hours, office, punchin_time, punchout_time
    FROM employees
    WHERE id = ?
");
$employee_stmt->bind_param("i", $employee_id);
$employee_stmt->execute();
$employee = $employee_stmt->get_result()->fetch_assoc();
$employee_stmt->close();

if (!$employee) {
    echo "<div class='alert alert-danger'>Employee not found.</div>";
    exit;
}

$working_hours = (float) ($employee['working_hours'] ?? 0);
$employee_office = (string) ($employee['office'] ?? '');
$punchin_time = $employee['punchin_time'] ?? null;
$punchout_time = $employee['punchout_time'] ?? null;

$office_name = '';
$state_name = '';
if ($employee_office !== '' && strpos($employee_office, '_') !== false) {
    [$office_name, $state_name] = explode('_', $employee_office, 2);
}

$office_details = null;
if ($office_name !== '' && $state_name !== '') {
    $office_stmt = $conn->prepare("
        SELECT office_name, state_name, mobile_number1, mobile_number2, expiry_date
        FROM offices
        WHERE office_name = ? AND state_name = ?
    ");
    $office_stmt->bind_param("ss", $office_name, $state_name);
    $office_stmt->execute();
    $office_details = $office_stmt->get_result()->fetch_assoc();
    $office_stmt->close();
}

$months = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June',
    7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

$year = isset($_POST['year']) ? (int) $_POST['year'] : (int) date('Y');
$month = isset($_POST['month']) ? (int) $_POST['month'] : (int) date('m');

$holiday_query = $conn->prepare("
    SELECT start_date
    FROM events
    WHERE YEAR(start_date) = ? AND MONTH(start_date) = ?
");
$holiday_query->bind_param("ii", $year, $month);
$holiday_query->execute();
$holidays = $holiday_query->get_result()->fetch_all(MYSQLI_ASSOC);
$holiday_dates = array_column($holidays, 'start_date');

$total_days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$total_working_days = 0;
for ($day = 1; $day <= $total_days_in_month; $day++) {
    $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
    $day_of_week = (int) date('N', strtotime($date));
    if ($day_of_week < 7 && !in_array($date, $holiday_dates, true)) {
        $total_working_days++;
    }
}

$total_possible_hours = $total_working_days * $working_hours;

$attendance_stmt = $conn->prepare("
    SELECT *
    FROM attendance
    WHERE employee_id = ? AND YEAR(punch_in_time) = ? AND MONTH(punch_in_time) = ?
");
$attendance_stmt->bind_param("iii", $employee_id, $year, $month);
$attendance_stmt->execute();
$attendance = $attendance_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$attendance_stmt->close();

$birthdays_query = $conn->prepare("
    SELECT name, DATE_FORMAT(dob, '%d %M') AS birthday
    FROM employees
    WHERE MONTH(dob) = ? AND DAY(dob) >= DAY(NOW())
");
$birthdays_query->bind_param("i", $month);
$birthdays_query->execute();
$birthdays = $birthdays_query->get_result()->fetch_all(MYSQLI_ASSOC);

$anniversary_query = $conn->prepare("
    SELECT name, DATE_FORMAT(anniversary, '%d %M') AS anniversaryday
    FROM employees
    WHERE MONTH(anniversary) = ? AND DAY(anniversary) >= DAY(NOW())
");
$anniversary_query->bind_param("i", $month);
$anniversary_query->execute();
$anniversaries = $anniversary_query->get_result()->fetch_all(MYSQLI_ASSOC);

$today = date('Y-m-d');

$upcoming_birthdays_query = $conn->prepare("
    SELECT name, DATE_FORMAT(dob, '%d %M') AS birthday
    FROM employees
    WHERE DATE_FORMAT(dob, '%m-%d') BETWEEN DATE_FORMAT(?, '%m-%d')
    AND DATE_FORMAT(DATE_ADD(?, INTERVAL 60 DAY), '%m-%d')
    ORDER BY DATE_FORMAT(dob, '%m-%d') ASC
");
$upcoming_birthdays_query->bind_param("ss", $today, $today);
$upcoming_birthdays_query->execute();
$upcoming_birthdays = $upcoming_birthdays_query->get_result()->fetch_all(MYSQLI_ASSOC);

$upcoming_anniversaries_query = $conn->prepare("
    SELECT name, DATE_FORMAT(anniversary, '%d %M') AS anniversaryday
    FROM employees
    WHERE DATE_FORMAT(anniversary, '%m-%d') BETWEEN DATE_FORMAT(?, '%m-%d')
    AND DATE_FORMAT(DATE_ADD(?, INTERVAL 60 DAY), '%m-%d')
    ORDER BY DATE_FORMAT(anniversary, '%m-%d') ASC
");
$upcoming_anniversaries_query->bind_param("ss", $today, $today);
$upcoming_anniversaries_query->execute();
$upcoming_anniversaries = $upcoming_anniversaries_query->get_result()->fetch_all(MYSQLI_ASSOC);

$total_present = 0;
$total_absent = 0;
$total_on_leave = 0;
$total_late = 0;
$total_early = 0;
$total_working_hours = 0.0;

foreach ($attendance as $record) {
    $status = $record['status'] ?? '';
    $punch_in_value = !empty($record['punch_in_time']) ? date('H:i:s', strtotime($record['punch_in_time'])) : null;
    $punch_out_value = !empty($record['punch_out_time']) ? date('H:i:s', strtotime($record['punch_out_time'])) : null;

    if ($status === 'Present') {
        $total_present++;
    } elseif ($status === 'Absent') {
        $total_absent++;
    } elseif ($status === 'On Leave' || $status === 'On_Leave') {
        $total_on_leave++;
    }

    if ($punchin_time && $punch_in_value && $punch_in_value > $punchin_time) {
        $total_late++;
    }

    if ($punchout_time && $punch_out_value && $punch_out_value < $punchout_time) {
        $total_early++;
    }

    $total_working_hours += (float) ($record['working_hours'] ?? 0);
}

$total_present_percentage = $total_working_days > 0 ? round(($total_present / $total_working_days) * 100) : 0;
$total_absent_percentage = $total_working_days > 0 ? round(($total_absent / $total_working_days) * 100) : 0;
$total_on_leave_percentage = $total_working_days > 0 ? round(($total_on_leave / $total_working_days) * 100) : 0;
$total_late_percentage = $total_working_days > 0 ? round(($total_late / $total_working_days) * 100) : 0;
$total_early_percentage = $total_working_days > 0 ? round(($total_early / $total_working_days) * 100) : 0;
$total_working_hours_percentage = $total_possible_hours > 0 ? round(($total_working_hours / $total_possible_hours) * 100) : 0;

$formatted_working_hours = number_format($total_working_hours, 1);
$formatted_possible_hours = number_format($total_possible_hours, 1);

$monthly_highlights = [
    ['label' => 'Working Hours / Day', 'value' => number_format($working_hours, 1)],
    ['label' => 'Attendance Entries', 'value' => count($attendance)],
    ['label' => 'Month Target', 'value' => $formatted_possible_hours]
];
?>

<style>
:root {
  --emp-panel-bg: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  --emp-panel-border: rgba(87, 96, 108, 0.12);
  --emp-panel-shadow: 0 18px 38px rgba(31, 41, 55, 0.06);
}

.emp-dashboard-shell {
  padding-bottom: 1.75rem;
}

.emp-filter-card,
.emp-stat-card,
.emp-panel,
.emp-id-panel {
  background: var(--emp-panel-bg);
  border: 1px solid var(--emp-panel-border);
  border-radius: 24px;
  box-shadow: var(--emp-panel-shadow);
}

.emp-filter-card {
  padding: 1.25rem;
  margin-bottom: 1.5rem;
}

.emp-filter-card .form-label {
  font-size: 0.78rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #6b7280;
}

.emp-filter-card .form-select {
  min-height: 52px;
  border-radius: 14px;
  border: 1px solid #d8dee7;
  color: #374151;
}

.emp-stat-card {
  padding: 1.15rem;
  height: 100%;
}

.emp-stat-icon {
  width: 54px;
  height: 54px;
  border-radius: 18px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  margin-bottom: 0.95rem;
}

.emp-stat-icon.slate { background: #edf2f8; color: #334155; }
.emp-stat-icon.blue { background: #e8f0fb; color: #295ea8; }
.emp-stat-icon.red { background: #fdeceb; color: #d54a46; }
.emp-stat-icon.amber { background: #fff3da; color: #b7791f; }
.emp-stat-icon.purple { background: #f2ebff; color: #7450d6; }
.emp-stat-icon.green { background: #e7f7ef; color: #1f8f57; }

.emp-stat-label {
  font-size: 0.9rem;
  color: #6b7280;
  margin-bottom: 0.35rem;
}

.emp-stat-value {
  font-size: 2rem;
  line-height: 1;
  font-weight: 800;
  color: #111827;
  margin-bottom: 0.45rem;
}

.emp-stat-footnote {
  color: #94a3b8;
  font-size: 0.8rem;
}

.emp-panel,
.emp-id-panel {
  padding: 1.25rem;
}

.emp-panel-title h5,
.emp-id-panel h5 {
  margin: 0;
  color: #1f2937;
}

.emp-panel-subtitle {
  color: #6b7280;
  font-size: 0.9rem;
  margin-top: 0.2rem;
}

.emp-hours-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.1fr) minmax(220px, 0.9fr);
  gap: 1.2rem;
  align-items: stretch;
}

.emp-hours-number {
  font-size: 2.35rem;
  font-weight: 800;
  color: #111827;
  margin-bottom: 0.35rem;
}

.emp-hours-target {
  color: #64748b;
  margin-bottom: 1rem;
}

.emp-hours-side {
  border-radius: 20px;
  background: linear-gradient(135deg, #f8fafc 0%, #eef2f6 100%);
  border: 1px solid #e7edf4;
  padding: 1rem;
}

.emp-hours-side .emp-mini-card {
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.emp-progress {
  height: 12px;
  background: #e8edf3;
  border-radius: 999px;
  overflow: hidden;
}

.emp-progress > span {
  display: block;
  height: 100%;
  background: linear-gradient(90deg, #111827 0%, #415164 100%);
  border-radius: inherit;
}

.emp-mini-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.95rem;
  margin-top: 1rem;
}

.emp-mini-card {
  border-radius: 18px;
  background: #f8fafc;
  border: 1px solid #e7edf3;
  padding: 0.95rem;
}

.emp-mini-card span {
  display: block;
  color: #94a3b8;
  font-size: 0.74rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.emp-mini-card strong {
  display: block;
  margin-top: 0.35rem;
  color: #111827;
  font-size: 1.3rem;
}

.emp-highlight-carousel {
  margin-top: 1.1rem;
  border-radius: 22px;
  background: linear-gradient(135deg, #f7f9fc 0%, #eef2f6 100%);
  border: 1px solid #e5ebf2;
  padding: 1.15rem;
}

.emp-highlight-empty,
.emp-highlight-item {
  min-height: 160px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  text-align: center;
}

/* equalize small cards row */
.emp-small-row > .col-md-6 {
  display: flex;
}
.emp-small-row .emp-panel {
  display: flex;
  flex-direction: column;
  justify-content: center;
  flex: 1 1 auto;
}

.emp-highlight-icon {
  width: 56px;
  height: 56px;
  border-radius: 18px;
  background: #fff1d6;
  color: #b7791f;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  margin-bottom: 1rem;
}

.emp-id-panel {
  height: 100%;
}

.emp-id-stage {
  display: flex;
  justify-content: center;
  padding-top: 0.9rem;
}

.emp-id-card {
  width: 100%;
  max-width: 300px;
  min-height: 390px;
  border-radius: 18px;
  overflow: hidden;
  background: #ffffff;
  border: 1px solid rgba(15, 23, 42, 0.06);
  box-shadow: 0 10px 30px rgba(31,41,55,0.08);
  display: flex;
  flex-direction: column;
  align-items: center;
  padding-bottom: 18px;
}

/* topbar removed */

.emp-id-logo {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 1rem 1rem 0.3rem;
}

.emp-id-logo img {
  max-width: 138px;
  max-height: 44px;
  object-fit: contain;
}

.emp-id-photo {
  width: 96px;
  height: 96px;
  margin: -38px auto 10px;
  border-radius: 50%;
  overflow: hidden;
  border: 6px solid #fff;
  box-shadow: 0 8px 20px rgba(31,41,55,0.12);
  background: #fff;
}

.emp-id-photo img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.emp-id-name {
  text-align: center;
  font-size: 1.4rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0.2rem 0 0.15rem;
}

.emp-id-role {
  text-align: center;
  font-size: 0.9rem;
  color: #2d7a2f;
  font-weight: 700;
}

.emp-id-code {
  text-align: center;
  font-size: 1.15rem;
  font-weight: 800;
  color: #111827;
  margin: 0.5rem 0 0.8rem;
}

.emp-id-meta {
  display: flex;
  gap: 0.75rem;
  padding: 0 1rem;
  margin-bottom: 0.75rem;
}

.emp-id-meta-card {
  border-radius: 12px;
  background: #f7fafc;
  border: 1px solid rgba(15,23,42,0.04);
  text-align: center;
  padding: 0.6rem 0.75rem;
  min-width: 110px;
}

.emp-id-meta-card span {
  display: block;
  font-size: 0.72rem;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.emp-id-meta-card strong {
  display: block;
  margin-top: 0.25rem;
  color: #1f2937;
  font-size: 0.95rem;
}

.emp-id-company {
  margin: 0 1rem 0.6rem;
  padding: 0.75rem 1rem;
  border-radius: 12px;
  background: linear-gradient(135deg,#f3f7fa 0%, #ffffff 100%);
  border: 1px solid rgba(15,23,42,0.03);
  text-align: center;
}

.emp-id-company strong {
  display: block;
  color: #1f2937;
  font-size: 1rem;
}

.emp-id-company span {
  display: block;
  color: #64748b;
  font-size: 0.82rem;
  margin-top: 0.18rem;
}

.emp-id-address {
  text-align: center;
  color: #6b7280;
  font-size: 0.82rem;
  line-height: 1.5;
  padding: 0 1.1rem 1.1rem;
}

/* Lanyard + holder styles */
.emp-id-card {
  margin-top: 8px;
  border-radius: 12px;
  overflow: hidden;
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  border: 1px solid #dbe3ec;
  box-shadow: 0 14px 30px rgba(31,41,55,0.06);
  display: flex;
  flex-direction: column;
  align-items: center;
  padding-bottom: 18px;
}
.emp-id-inner { display: block; }
.emp-id-logo img { max-width: 140px; }
.emp-id-photo { margin-top: 6px; }
.emp-id-meta { margin-top: 8px; }
.emp-id-company { margin-top: 8px; }

/* framed badge decorations */
.id-frame { position: relative; display: flex; justify-content: center; align-items: flex-start; padding-top: 18px; }
.id-frame .emp-id-card { border-radius: 10px; border: 6px solid #0b0b0b; box-shadow: 0 18px 40px rgba(0,0,0,0.08); z-index: 2; background: #fff; padding-bottom:18px; }
.id-clip-top { /* removed - kept for possible reuse */ display:none; }
.id-handle { position:absolute; width:10px; height:56px; background:#0b0b0b; top:50%; transform: translateY(-50%); border-radius:6px; z-index:3; }
.id-handle-left { left: -6px; }
.id-handle-right { right: -6px; }

@media (max-width: 1199.98px) {
  .emp-id-panel {
    margin-top: 0;
  }
}

@media (max-width: 991.98px) {
  .emp-hours-grid,
  .emp-mini-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 767.98px), (max-width: 991.98px) and (hover: none) and (pointer: coarse) {
  .emp-dashboard-shell {
    padding-bottom: 1rem;
  }

  .container-fluid-main.emp-dashboard-shell {
    overflow-x: clip;
  }

  .container-fluid-main.emp-dashboard-shell.py-4 {
    padding-top: 0.8rem !important;
    padding-left: 0.35rem !important;
    padding-right: 0.35rem !important;
  }

  .emp-filter-card,
  .emp-stat-card,
  .emp-panel,
  .emp-id-panel {
    border-radius: 18px;
  }

  .emp-filter-card {
    padding: 0.85rem;
    margin-bottom: 0.85rem;
  }

  .emp-filter-card .row {
    --bs-gutter-x: 0.6rem;
    --bs-gutter-y: 0.65rem;
    align-items: flex-end;
  }

  .emp-filter-card .row > [class*="col-"] {
    display: flex;
    flex-direction: column;
  }

  .emp-filter-card .row > [class*="col-"] > * {
    width: 100%;
  }

  .emp-filter-card .form-label {
    font-size: 0.72rem;
    margin-bottom: 0.42rem;
  }

  .emp-filter-card .form-select,
  .emp-filter-card .btn {
    min-height: 44px;
    font-size: 0.92rem;
  }

  .emp-filter-card .btn {
    border-radius: 13px;
    font-size: 0.84rem;
    font-weight: 700;
  }

  .emp-filter-col-year {
    flex: 0 0 35%;
    max-width: 35%;
  }

  .emp-filter-col-month {
    flex: 0 0 40%;
    max-width: 40%;
  }

  .emp-filter-col-action {
    flex: 0 0 25%;
    max-width: 25%;
    justify-content: flex-end;
  }

  .emp-filter-col-action .btn {
    margin-top: auto;
  }

  .emp-stat-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.38rem;
    margin-right: 0;
    margin-left: 0;
    margin-top: 0;
    padding-bottom: 0;
    overflow: visible;
    align-items: stretch;
    --bs-gutter-x: 0;
    --bs-gutter-y: 0;
  }

  .emp-stat-grid > [class*="col-"] {
    padding-left: 0;
    padding-right: 0;
    margin-top: 0;
  }

  .emp-stat-col {
    flex: initial;
    max-width: 100%;
    width: 100%;
    min-width: 0;
  }

  .emp-stat-card {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    justify-content: flex-start;
    padding: 0.76rem;
    min-height: 100%;
    border-radius: 18px;
    width: 100%;
    margin: 0;
  }

  .emp-stat-icon {
    width: 38px;
    height: 38px;
    border-radius: 13px;
    margin-bottom: 0.62rem;
    font-size: 0.88rem;
  }

  .emp-stat-value {
    font-size: 1.28rem;
    margin-bottom: 0.24rem;
  }

  .emp-stat-label {
    font-size: 0.74rem;
    margin-bottom: 0.2rem;
    min-height: 2.1em;
    line-height: 1.3;
  }

  .emp-stat-footnote {
    display: block;
    font-size: 0.68rem;
    line-height: 1.28;
    margin-top: auto;
    min-height: 2.55em;
  }

  .emp-small-row {
    margin-top: 0.7rem !important;
    --bs-gutter-y: 0.7rem;
  }

  .emp-panel,
  .emp-id-panel {
    padding: 0.9rem;
  }

  .emp-small-row > [class*="col-"] {
    display: flex;
  }

  .emp-small-row .emp-panel,
  .emp-id-panel {
    width: 100%;
  }

  .emp-panel-title h5,
  .emp-id-panel h5 {
    font-size: 1rem;
  }

  .emp-hours-number {
    font-size: 1.42rem;
    margin-bottom: 0.2rem;
  }

  .emp-hours-target,
  .emp-panel-subtitle {
    font-size: 0.76rem;
  }

  .emp-panel-title {
    margin-bottom: 0.2rem;
  }

  .emp-panel .py-3 {
    padding-top: 0.65rem !important;
    padding-bottom: 0.35rem !important;
  }

  .emp-progress {
    height: 8px;
  }

  .emp-highlight-carousel {
    margin-top: 0.5rem;
    padding: 0.65rem;
    border-radius: 14px;
  }

  .emp-highlight-empty,
  .emp-highlight-item {
    min-height: 102px;
    padding: 0.15rem;
  }

  .emp-highlight-item h5,
  .emp-highlight-empty h6 {
    font-size: 0.86rem;
    margin-bottom: 0.2rem;
  }

  .emp-highlight-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    font-size: 0.9rem;
    margin-bottom: 0.55rem;
  }

  .carousel-control-prev,
  .carousel-control-next {
    width: 28px;
  }

  .emp-id-stage {
    padding-top: 0.15rem;
  }

  .id-frame {
    padding-top: 0.25rem;
  }

  .id-handle {
    display: none;
  }

  .id-frame .emp-id-card,
  .emp-id-card {
    max-width: 272px;
    min-height: 0;
    border-width: 1px;
    border-radius: 16px;
    box-shadow: 0 12px 26px rgba(15, 23, 42, 0.07);
    padding-bottom: 10px;
  }

  .emp-id-inner {
    width: 100%;
  }

  .emp-id-logo {
    padding: 0.7rem 0.8rem 0.2rem;
  }

  .emp-id-logo img {
    max-width: 112px;
    max-height: 34px;
  }

  .emp-id-photo {
    width: 64px;
    height: 64px;
    margin-top: 2px;
    margin-bottom: 0.5rem;
  }

  .emp-id-name {
    font-size: 0.92rem;
    margin: 0.1rem 0;
  }

  .emp-id-role {
    font-size: 0.72rem;
  }

  .emp-id-code {
    font-size: 0.84rem;
    margin: 0.35rem 0 0.55rem;
  }

  .emp-id-meta {
    flex-direction: column;
    gap: 0.38rem;
    width: 100%;
    padding: 0 0.8rem;
    margin-bottom: 0.55rem;
  }

  .emp-id-meta-card {
    width: 100%;
    min-width: 0;
    padding: 0.48rem 0.65rem;
    border-radius: 10px;
  }

  .emp-id-meta-card span {
    font-size: 0.62rem;
  }

  .emp-id-meta-card strong {
    font-size: 0.82rem;
  }

  .emp-id-company {
    margin: 0 0.8rem 0.45rem;
    padding: 0.55rem 0.7rem;
    border-radius: 10px;
  }

  .emp-id-company strong {
    font-size: 0.86rem;
  }

  .emp-id-company span,
  .emp-id-address {
    font-size: 0.68rem;
    line-height: 1.4;
  }

  .emp-id-address {
    padding: 0 0.9rem 0.7rem;
  }
}

@media (max-width: 575.98px) {
  .emp-filter-card,
  .emp-panel,
  .emp-id-panel,
  .emp-stat-card {
    border-radius: 16px;
  }

  .emp-filter-col-year {
    flex-basis: 34%;
    max-width: 34%;
  }

  .emp-filter-col-month {
    flex-basis: 41%;
    max-width: 41%;
  }

  .emp-filter-col-action {
    flex-basis: 25%;
    max-width: 25%;
  }

  .emp-stat-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .emp-stat-card {
    padding: 0.78rem;
  }

  .emp-filter-card .btn {
    width: 100%;
  }

  .emp-id-card {
    padding-bottom: 12px;
  }
}

@media (max-width: 360px) {
  .emp-filter-card .row {
    display: flex;
    flex-wrap: nowrap;
    gap: 0.34rem;
    align-items: end;
  }

  .emp-filter-col-year,
  .emp-filter-col-month {
    max-width: none;
    flex: 1 1 0;
    min-width: 0;
  }

  .emp-filter-col-action {
    grid-column: auto;
    max-width: 84px;
    flex: 0 0 84px;
    min-width: 84px;
  }

  .emp-stat-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.32rem;
  }

  .emp-filter-card {
    padding: 0.72rem;
  }

  .emp-filter-card .form-label {
    font-size: 0.68rem;
    margin-bottom: 0.28rem;
  }

  .emp-filter-card .form-select,
  .emp-filter-card .btn {
    min-height: 38px;
    font-size: 0.82rem;
  }

  .emp-filter-card .btn {
    border-radius: 12px;
    padding-left: 0.45rem;
    padding-right: 0.45rem;
  }

  .emp-stat-card {
    padding: 0.64rem;
    border-radius: 14px;
  }

  .emp-stat-icon {
    width: 34px;
    height: 34px;
    border-radius: 12px;
    margin-bottom: 0.48rem;
    font-size: 0.8rem;
  }

  .emp-stat-label {
    font-size: 0.7rem;
    line-height: 1.25;
    min-height: 1.9em;
  }

  .emp-stat-value {
    font-size: 1.18rem;
    margin-bottom: 0.18rem;
  }

  .emp-stat-footnote {
    font-size: 0.64rem;
    line-height: 1.22;
    min-height: 2.3em;
  }

  .emp-panel,
  .emp-id-panel {
    padding: 0.8rem;
  }
}

@media (max-width: 320px) {
  .emp-filter-card .row {
    display: grid;
    grid-template-columns: 1fr;
  }

  .emp-filter-col-year,
  .emp-filter-col-month,
  .emp-filter-col-action {
    max-width: 100%;
    min-width: 0;
    flex-basis: 100%;
  }

  .emp-filter-col-action {
    flex: 1 1 100%;
  }

  .emp-stat-grid {
    grid-template-columns: 1fr;
  }
}
</style>

<div class="container-fluid container-fluid-main emp-dashboard-shell py-4">
  <form method="POST" class="emp-filter-card">
    <div class="row g-3 align-items-end">
      <div class="col-md-5 emp-filter-col-year">
        <label for="year" class="form-label">Select Year</label>
        <select name="year" id="year" class="form-select">
          <?php for ($i = (int) date('Y'); $i >= 2000; $i--): ?>
            <option value="<?= $i ?>" <?= $i === $year ? 'selected' : '' ?>><?= $i ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="col-md-5 emp-filter-col-month">
        <label for="month" class="form-label">Select Month</label>
        <select name="month" id="month" class="form-select">
          <?php foreach ($months as $key => $value): ?>
            <option value="<?= $key ?>" <?= $key === $month ? 'selected' : '' ?>><?= $value ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2 emp-filter-col-action">
        <button type="submit" class="btn bg-gradient-dark w-100 mb-0">Filter</button>
      </div>
    </div>
  </form>

  <div class="row g-4">
    <div class="col-xl-8">
      <div class="row g-4 emp-stat-grid">
        <div class="col-md-6 col-xl-4 emp-stat-col">
          <div class="emp-stat-card">
            <div class="emp-stat-icon slate"><i class="bi bi-calendar3"></i></div>
            <div class="emp-stat-label">Total Working Days</div>
            <div class="emp-stat-value"><?= $total_working_days ?></div>
            <div class="emp-stat-footnote">Excludes holidays and Sundays</div>
          </div>
        </div>
        <div class="col-md-6 col-xl-4 emp-stat-col">
          <div class="emp-stat-card">
            <div class="emp-stat-icon blue"><i class="bi bi-person-check-fill"></i></div>
            <div class="emp-stat-label">Days Present</div>
            <div class="emp-stat-value"><?= $total_present ?></div>
            <div class="emp-stat-footnote"><?= $total_present_percentage ?>% attendance rate</div>
          </div>
        </div>
        <div class="col-md-6 col-xl-4 emp-stat-col">
          <div class="emp-stat-card">
            <div class="emp-stat-icon red"><i class="bi bi-person-x-fill"></i></div>
            <div class="emp-stat-label">Days Absent</div>
            <div class="emp-stat-value"><?= $total_absent ?></div>
            <div class="emp-stat-footnote"><?= $total_absent_percentage ?>% of working days</div>
          </div>
        </div>
        <div class="col-md-6 col-xl-4 emp-stat-col">
          <div class="emp-stat-card">
            <div class="emp-stat-icon amber"><i class="bi bi-calendar2-heart-fill"></i></div>
            <div class="emp-stat-label">Days On Leave</div>
            <div class="emp-stat-value"><?= $total_on_leave ?></div>
            <div class="emp-stat-footnote"><?= $total_on_leave_percentage ?>% of working days</div>
          </div>
        </div>
        <div class="col-md-6 col-xl-4 emp-stat-col">
          <div class="emp-stat-card">
            <div class="emp-stat-icon purple"><i class="bi bi-alarm-fill"></i></div>
            <div class="emp-stat-label">Late Punch-Ins</div>
            <div class="emp-stat-value"><?= $total_late ?></div>
            <div class="emp-stat-footnote"><?= $total_late_percentage ?>% of working days</div>
          </div>
        </div>
        <div class="col-md-6 col-xl-4 emp-stat-col">
          <div class="emp-stat-card">
            <div class="emp-stat-icon green"><i class="bi bi-box-arrow-right"></i></div>
            <div class="emp-stat-label">Early Punch-Outs</div>
            <div class="emp-stat-value"><?= $total_early ?></div>
            <div class="emp-stat-footnote"><?= $total_early_percentage ?>% of working days</div>
          </div>
        </div>
      </div>

      <div class="row g-3 mt-3 emp-small-row">
        <div class="col-md-6">
          <div class="emp-panel">
            <div class="emp-panel-title">
              <div>
                
                <div class="emp-panel-subtitle">Total Working Hours</div>
              </div>
            </div>
            <div class="py-3">
              <div class="emp-hours-number"><?= $formatted_working_hours ?> hrs</div>
              <div class="emp-hours-target">Out of <?= $formatted_possible_hours ?> hrs target</div>
              <div class="emp-progress mt-2">
                <span style="width: <?= min(100, $total_working_hours_percentage) ?>%;"></span>
              </div>
              <div class="emp-stat-footnote mt-2"><?= $total_working_hours_percentage ?>% completed</div>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="emp-panel">
            <div class="emp-panel-title">
              <div>
                
                <div class="emp-panel-subtitle">Upcoming Birthday</div>
              </div>
            </div>
            <div class="emp-highlight-carousel mt-2">
              <?php if (empty($birthdays) && empty($anniversaries)) : ?>
                <div class="emp-highlight-empty">
                  <div class="emp-highlight-icon"><i class="bi bi-stars"></i></div>
                  <h6 class="mb-1">No celebrations this month</h6>
                  <p class="emp-panel-subtitle mb-0">Birthdays and anniversaries will appear here automatically.</p>
                </div>
              <?php else : ?>
                <div id="birthdayAnniversarySlider" class="carousel slide" data-bs-ride="carousel">
                  <div class="carousel-inner">
                    <?php $index = 0; ?>
                    <?php foreach ($birthdays as $birthday) : ?>
                      <div class="carousel-item <?= $index === 0 ? 'active' : ''; ?>">
                        <div class="emp-highlight-item">
                          <div class="emp-highlight-icon"><i class="bi bi-cake2-fill"></i></div>
                          <h5 class="mb-2"><?= htmlspecialchars($birthday['name']) ?></h5>
                          <p class="emp-panel-subtitle mb-0">Birthday on <?= htmlspecialchars($birthday['birthday']) ?></p>
                        </div>
                      </div>
                      <?php $index++; ?>
                    <?php endforeach; ?>
                    <?php foreach ($anniversaries as $anniversary) : ?>
                      <div class="carousel-item <?= $index === 0 ? 'active' : ''; ?>">
                        <div class="emp-highlight-item">
                          <div class="emp-highlight-icon"><i class="bi bi-balloon-heart-fill"></i></div>
                          <h5 class="mb-2"><?= htmlspecialchars($anniversary['name']) ?></h5>
                          <p class="emp-panel-subtitle mb-0">Anniversary on <?= htmlspecialchars($anniversary['anniversaryday']) ?></p>
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
      </div>

    </div>

    <div class="col-xl-4">
      <div class="emp-id-panel">
        <div class="emp-panel-title">
          <h5 class="fw-bold">Employee ID Card</h5>
          <div class="emp-panel-subtitle"></div>
        </div>
        <div class="emp-id-stage">
          <div class="id-frame">
            <!-- lanyard and top clip removed -->
            <div class="id-handle id-handle-left"></div>
            <div class="id-handle id-handle-right"></div>
            <div class="emp-id-card">
              <div class="emp-id-inner">
                <div class="emp-id-logo" style="margin-top:6px;">
                  <?php if (!empty($org['logo']) && file_exists("../uploads/org/" . $org['logo'])): ?>
                    <img src="../uploads/org/<?= htmlspecialchars($org['logo']) ?>" alt="logo">
                  <?php else: ?>
                    <img src="assets/img/att logo.png" alt="logo">
                  <?php endif; ?>
                </div>
                <div class="emp-id-photo">
                  <img src="<?= htmlspecialchars($employee_photo) ?>" alt="<?= htmlspecialchars($employee_name) ?>">
                </div>
                <div class="emp-id-name"><?= htmlspecialchars($employee_name) ?></div>
                <div class="emp-id-role"><?= htmlspecialchars($employee_designation) ?></div>
                <div class="emp-id-code"><?= htmlspecialchars($employee_unique_id) ?></div>
                <div class="emp-id-meta">
                  <div class="emp-id-meta-card">
                    <span>Status</span>
                    <strong>Active</strong>
                  </div>
                  <div class="emp-id-meta-card">
                    <span>Valid Till</span>
                    <strong><?= htmlspecialchars($office_details['expiry_date'] ?? 'N/A') ?></strong>
                    </div>
                  </div>
                </div>
                <div class="emp-id-company">
                  <strong><?= htmlspecialchars($org['name'] ?? 'My Attendance') ?></strong>
                  
                  <span>Ph: <?= htmlspecialchars($org['phone'] ?? '') ?></span>
                </div>
                <div class="emp-id-address">
                  <?= htmlspecialchars($org['address'] ?? '') ?>
                </div>
              </div>
          </div>
        </div>
      </div>
    </div>

    
  </div>
</div>

<?php if ($total_working_hours >= $total_possible_hours && $total_working_hours > 0) : ?>
  <script>
    document.addEventListener("DOMContentLoaded", function () {
      showCongratulationsPopup();
    });
  </script>
<?php endif; ?>

<?php include("footer.php"); ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function showCongratulationsPopup() {
  Swal.fire({
    title: "Great work!",
    text: "You have completed your total working hours for this month.",
    icon: "success",
    showConfirmButton: false,
    timer: 3500
  });
}
</script>
