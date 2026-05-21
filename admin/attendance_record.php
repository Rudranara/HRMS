<?php
include("header.php");
require 'db_connection.php';

function ensureAutoPunchoutColumn(mysqli $conn): void
{
    $check = $conn->query("SHOW COLUMNS FROM employees LIKE 'disable_auto_punchout'");
    if ($check && $check->num_rows === 0) {
        $conn->query("ALTER TABLE employees ADD COLUMN disable_auto_punchout TINYINT(1) NOT NULL DEFAULT 0");
    }
}

ensureAutoPunchoutColumn($conn);

$selected_office = isset($_GET['office']) ? $_GET['office'] : '';
$decoded_office = $selected_office !== '' ? urldecode($selected_office) : '';
// Handle filters
$current_date = date('Y-m-d');
$current_month = date('m');
$current_year = date('Y');
$selected_employee = isset($_GET['employee_id']) ? $_GET['employee_id'] : '';
$selected_month = isset($_GET['month']) ? $_GET['month'] : $current_month;
$selected_year = isset($_GET['year']) ? $_GET['year'] : $current_year;
// Filter dates for the selected month and year
// Filter dates for the selected month and year
$filter_start = "{$selected_year}-{$selected_month}-01";
$filter_end = date("Y-m-t 23:59:59", strtotime($filter_start)); // Add 23:59:59 to include the last day's data

// Fetch employees for the dropdown based on the selected office
$employees_query = "SELECT id, name FROM employees WHERE 1=1";
$employee_params = [];
$employee_types = "";

if ($decoded_office !== '') {
    $employees_query .= " AND office = ?";
    $employee_params[] = $decoded_office;
    $employee_types .= "s";
}

$employees_query .= " ORDER BY name ASC";
$employees_stmt = $conn->prepare($employees_query);
if (!empty($employee_params)) {
    $employees_stmt->bind_param($employee_types, ...$employee_params);
}
$employees_stmt->execute();
$employees_result = $employees_stmt->get_result();

$attendance_query = "    
    SELECT a.*, e.name AS employee_name, e.employee_id AS emp_id,
           e.punchin_time, e.punchout_time, e.office, a.break_hours
    FROM attendance a
    INNER JOIN employees e ON a.employee_id = e.id
    WHERE (
        (COALESCE(e.disable_auto_punchout, 0) = 1 AND a.punch_in_time BETWEEN ? AND ?)
        OR
        (COALESCE(e.disable_auto_punchout, 0) = 0 AND a.punch_out_time BETWEEN ? AND ?)
    )
";
$attendance_params = [$filter_start, $filter_end, $filter_start, $filter_end];
$attendance_types = "ssss";

if ($selected_employee) {
    $attendance_query .= " AND a.employee_id = ?";
    $attendance_params[] = $selected_employee;
    $attendance_types .= "i";
}
if ($decoded_office !== '') {
    $attendance_query .= " AND e.office = ?";
    $attendance_params[] = $decoded_office;
    $attendance_types .= "s";
}
$attendance_query .= " ORDER BY a.punch_in_time DESC";
$attendance_stmt = $conn->prepare($attendance_query);
$attendance_stmt->bind_param($attendance_types, ...$attendance_params);
$attendance_stmt->execute();
$attendance_result = $attendance_stmt->get_result();
// Fetch offices from the database
$offices_query = $conn->query("SELECT office_name, state_name  FROM offices ORDER BY office_name ASC");
$offices = $offices_query->fetch_all(MYSQLI_ASSOC);
?>
<style>
.record-shell {
  padding-bottom: 1.5rem;
}

.record-muted {
  color: #6b7280;
}

.record-filter-card,
.record-table-card {
  border: 1px solid rgba(87, 96, 108, 0.12);
  border-radius: 22px;
  box-shadow: 0 14px 34px rgba(31, 41, 55, 0.06);
  background: #fff;
}

.record-filter-card {
  padding: 1.25rem;
  margin-bottom: 1.5rem;
}

.record-filter-card .form-label,
.record-filter-card label {
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: #6b7280;
  margin-bottom: 0.55rem;
}

.record-filter-card .form-control,
.record-filter-card .form-select {
  min-height: 44px;
  border-radius: 14px;
  border: 1px solid #d8dee7;
  color: #374151;
  padding-top: 0.55rem;
  padding-bottom: 0.55rem;
}

.record-filter-card .form-control:focus,
.record-filter-card .form-select:focus {
  border-color: #aab7c9;
  box-shadow: 0 0 0 0.2rem rgba(55, 65, 81, 0.08);
}

.record-title {
  margin: 0;
  color: #111827;
  font-size: 1.05rem;
  font-weight: 800;
}

.record-meta {
  color: #94a3b8;
  font-size: 0.78rem;
  margin-top: 0.3rem;
}

.record-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 48px;
  border-radius: 14px;
  padding-left: 1.2rem;
  padding-right: 1.2rem;
  min-width: 112px;
  line-height: 1.2;
  text-align: center;
  white-space: nowrap;
  box-shadow: 0 10px 24px rgba(31, 41, 55, 0.10);
}

.record-btn-primary {
  background: linear-gradient(180deg, #2b2c31 0%, #1f2024 100%);
  color: #fff !important;
  border: 1px solid #2b2c31;
  box-shadow: 0 10px 24px rgba(24, 24, 27, 0.22);
}

.record-btn-primary:hover {
  background: linear-gradient(180deg, #32343a 0%, #23242a 100%);
  color: #fff !important;
  border-color: #32343a;
}

.record-btn-secondary {
  background: #08285c;
  color: #fff !important;
  border: 1px solid #08285c;
}

.record-btn-secondary:hover {
  background: #061f47;
  color: #fff !important;
}

.record-table-card {
  overflow: hidden;
}

.record-table-card .card-body {
  padding: 0;
}

.record-table-title {
  padding: 1.2rem 1.25rem 0.85rem;
}

.record-table-title h6 {
  margin: 0;
  font-size: 1rem;
  color: #111827;
}

.record-table-title p {
  margin: 0.3rem 0 0;
  color: #94a3b8;
  font-size: 0.8rem;
}

.record-table {
  margin-bottom: 0;
}

.record-table thead th {
  border-bottom: 1px solid #e8edf5;
  background: #f8fafc;
  color: #64748b;
  font-size: 0.74rem;
  font-weight: 800;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  padding-top: 1rem;
  padding-bottom: 1rem;
  white-space: nowrap;
  text-align: center;
}

.record-table tbody td,
.record-table tfoot th,
.record-table tfoot td {
  padding-top: 1rem;
  padding-bottom: 1rem;
  border-color: #eef2f7;
  vertical-align: middle;
  text-align: center;
}

.record-table tbody tr:hover {
  background: #fbfcfe;
}

.record-table .avatar,
.record-table img.avatar-sm {
  border-radius: 14px;
  object-fit: cover;
  border: 1px solid #e5e7eb;
}

.record-table h6 {
  color: #111827;
  font-weight: 700;
}

.record-table td .d-flex {
  justify-content: center;
}

.record-table td .d-flex.flex-column,
.record-table td .d-flex .d-flex.flex-column {
  align-items: center;
  text-align: center;
}

.record-table td .text-xs,
.record-table td p {
  text-align: center;
}

.record-table .text-xs {
  color: #6b7280 !important;
}

.record-table .text-primary,
.record-table .text-success {
  color: #334155 !important;
}

.record-table .text-danger {
  color: #7c2d12 !important;
}

.record-row-btn {
  min-width: 3.2rem;
  border: 1px solid #d9e0e8;
  border-radius: 12px;
  box-shadow: 0 10px 18px rgba(31, 41, 55, 0.08);
}

.record-row-btn-edit {
  background: #08285c;
  border-color: #08285c;
  color: #fff;
}

.record-row-btn-edit:hover {
  background: #061f47;
  border-color: #061f47;
  color: #fff;
}

.record-row-btn-delete {
  background: linear-gradient(180deg, #2b2c31 0%, #1f2024 100%);
  border-color: #2b2c31;
  color: #fff;
}

.record-row-btn-delete:hover {
  background: linear-gradient(180deg, #32343a 0%, #23242a 100%);
  border-color: #32343a;
  color: #fff;
}

.record-status-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 86px;
  padding: 0.42rem 0.7rem;
  border-radius: 999px;
  font-size: 0.68rem;
  font-weight: 800;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  border: 1px solid transparent;
}

.record-status-present {
  background: #e8f7ef;
  border-color: #cfe9da;
  color: #1f8f57;
}

.record-status-absent {
  background: #fdf2f2;
  border-color: #f3d6d6;
  color: #991b1b;
}

.record-status-weekoff,
.record-status-holiday {
  background: #f8fafc;
  border-color: #dbe3ed;
  color: #475569;
}

.record-status-leave {
  background: #f5f3ff;
  border-color: #e4ddff;
  color: #5b3dbb;
}

.record-delete-bar {
  padding: 1rem 1.25rem 1.25rem;
  border-top: 1px solid #eef2f7;
  background: linear-gradient(180deg, rgba(248, 250, 252, 0.35) 0%, rgba(255, 255, 255, 1) 100%);
}

.record-checkbox-cell .checkbox__checkmark,
.checkboxes__item .checkbox__checkmark {
  background-color: #1f2937 !important;
  box-shadow: 0 8px 16px rgba(31, 41, 55, 0.14);
}

.record-checkbox-cell {
  width: 64px;
  min-width: 64px;
  text-align: center;
  vertical-align: middle;
  padding-left: 0 !important;
  padding-right: 0 !important;
}

.record-checkbox-cell .checkboxes__item {
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0;
  width: 100%;
}

.record-checkbox-cell .checkbox {
  margin: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
}

.record-location-icon,
.record-gps-icon {
  width: 18px;
  height: 18px;
}

.record-empty {
  padding: 1.5rem 1.25rem 2rem;
  color: #6b7280;
}

.record-table tfoot th,
.record-table tfoot td {
  background: #f8fafc;
  font-weight: 700;
  color: #334155;
}

.record-filter-card .btn:focus,
.record-row-btn:focus,
.record-btn:focus {
  box-shadow: 0 0 0 0.2rem rgba(8, 40, 92, 0.18) !important;
}

.record-filter-grid {
  display: grid;
  grid-template-columns: 1.7fr 1.7fr 1.05fr 0.8fr repeat(4, minmax(118px, 0.9fr));
  gap: 0.9rem;
  align-items: end;
}

.record-filter-item {
  min-width: 0;
}

.record-filter-item .record-btn {
  width: 100%;
  min-width: 0;
}

@media (max-width: 991.98px) {
  .record-filter-card,
  .record-table-card {
    border-radius: 18px;
  }

  .record-filter-grid {
    grid-template-columns: 1fr;
  }
}
</style>

<div class="container-fluid container-fluid-main record-shell py-4">
    <div class="row">
        <div class="col-12">
            <div class="record-filter-card">
                <form method="GET" action="attendance_record">
                  <div class="record-filter-grid">
                    <div class="record-filter-item">
                            <label for="office">Select Office</label>
                            <select name="office" id="office" class="form-control">
                                <option value="">All Offices</option>
                                <?php
                                $selected_office = isset($_GET['office']) ? $_GET['office'] : '';
                                foreach ($offices as $office):
                                    $office_value = urlencode($office['office_name']) . "_" . urlencode($office['state_name']);
                                ?>
                                    <option value="<?= $office_value ?>" <?= $selected_office == $office_value ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($office['office_name']) ?> (<?= htmlspecialchars($office['state_name']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                          <div class="record-filter-item">
                            <label for="employee_id">Select Employee</label>
                            <select name="employee_id" id="employee_id" class="form-control">
                                <option value="">All Employees</option>
                                <?php while ($employee = $employees_result->fetch_assoc()): ?>
                                    <option value="<?= $employee['id'] ?>" <?= $selected_employee == $employee['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($employee['name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                          <div class="record-filter-item">
                            <label for="month">Select Month</label>
                            <select name="month" id="month" class="form-control">
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>" <?= $selected_month == $m ? 'selected' : '' ?>>
                                        <?= date("F", mktime(0, 0, 0, $m, 10)) ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="record-filter-item">
                          <label for="year">Select Year</label>
                          <select name="year" id="year" class="form-control">
                            <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                              <option value="<?= $y ?>" <?= $selected_year == $y ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                          </select>
                        </div>

                        <div class="record-filter-item">
                          <button type="submit" class="btn record-btn record-btn-primary mb-0">Filter</button>
                        </div>

                        <div class="record-filter-item">
                          <a href="download_attendance_csv?employee_id=<?= $selected_employee ?>&month=<?= $selected_month ?>&year=<?= $selected_year ?>&office=<?= $selected_office ?>" class="btn record-btn record-btn-secondary mb-0"><i class="bi bi-cloud-arrow-down-fill"></i> CSV</a>
                        </div>

                        <div class="record-filter-item">
                          <a href="download_attendance_excel?employee_id=<?= $selected_employee ?>&month=<?= $selected_month ?>&year=<?= $selected_year ?>&office=<?= $selected_office ?>" class="btn record-btn record-btn-secondary mb-0"><i class="bi bi-file-earmark-excel-fill"></i> Monthly XLS</a>
                        </div>

                        <div class="record-filter-item">
                          <a href="all_employees_attendance" class="btn record-btn record-btn-secondary mb-0">Attendance Summary</a>
                        </div>
                      </div>
                    </form>
                  </div>
                </div>

                <div class="col-12">
                  <div class="card record-table-card mb-4">
                    <div class="record-table-title">
                      <h6>Attendance Report (<?= date('d M Y', strtotime($filter_start)) ?> - <?= date('d M Y', strtotime($filter_end)) ?>)</h6>
                      <p>Monthly attendance report with punch times, working hours, break hours, and actual hours.</p>
                    </div>
                    <div class="card-body px-0 pt-0 pb-2">
                      <div class="table-responsive p-0">
                        <?php if ($attendance_result->num_rows > 0): ?>
                          <form method="POST" action="delete_multiple">
                            <table class="table record-table align-items-center mb-0">
                              <thead>
                                <tr>
                                  <th class="record-checkbox-cell">
                                    <div class="checkboxes__item">
                                      <label class="checkbox style-h">
                                        <input type="checkbox" id="select_all">
                                        <div class="checkbox__checkmark"></div>
                                      </label>
                                    </div>
                                  </th>
                                  <th>Employee Name</th>
                                  <th>Punch In</th>
                                  <th>Punch Out</th>
                                  <th>W Hrs</th>
                                  <th>B Hrs</th>
                                  <th>A Hrs</th>
                                  <th>Status</th>
                                  <th>Action</th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php $total_working_hours = 0; ?>
                                <?php $total_break_hours = 0; ?>
                                <?php while ($row = $attendance_result->fetch_assoc()): ?>
                                  <?php
                                  $hours = floor($row['working_hours']);
                                  $minutes = round(($row['working_hours'] - $hours) * 60);
                                  $formatted_working_hours = sprintf("%02d:%02d", $hours, $minutes);

                                  $break_hours = floor($row['break_hours']);
                                  $break_minutes = round(($row['break_hours'] - $break_hours) * 60);
                                  $formatted_break_hours = sprintf("%02d:%02d", $break_hours, $break_minutes);

                                  $actual_hours = floor($row['working_hours'] - $row['break_hours']);
                                  $actual_minutes = round((($row['working_hours'] - $row['break_hours']) - $actual_hours) * 60);
                                  $formatted_actual_working_time = sprintf("%02d:%02d", $actual_hours, $actual_minutes);
                                  ?>
                                  <tr>
                                    <td class="record-checkbox-cell">
                                      <div class="checkboxes__item">
                                        <label class="checkbox style-h">
                                          <input type="checkbox" name="ids[]" value="<?= $row['id'] ?>">
                                          <div class="checkbox__checkmark"></div>
                                        </label>
                                      </div>
                                    </td>
                                    <td>
                                      <div class="d-flex px-2 py-1">
                                        <div class="d-flex flex-column justify-content-center">
                                          <h6 class="mb-0 text-sm"><?= htmlspecialchars($row['employee_name']) ?></h6>
                                          <p class="text-xs text-secondary mb-0"><?= htmlspecialchars($row['emp_id']) ?></p>
                                        </div>
                                      </div>
                                    </td>
                                    <td>
                                      <div class="d-flex px-2 py-1">
                                        <div>
                                          <?php if ($row['selfie_in']): ?>
                                            <img src="<?= htmlspecialchars($row['selfie_in']) ?>" class="avatar avatar-sm me-3" alt="user1">
                                          <?php else: ?>
                                            <img src="assets/img/user-account (1).png" alt="">
                                          <?php endif; ?>
                                        </div>
                                        <div class="d-flex flex-column justify-content-center">
                                          <h6 class="mb-0 text-sm">
                                            <?= date('Y-m-d', strtotime($row['punch_in_time'])) ?><br>
                                            <?php
                                            $record_date = date('Y-m-d', strtotime($row['punch_in_time']));
                                            $expected_punchin_time = strtotime("$record_date " . $row['punchin_time']);
                                            $actual_punchin_time = strtotime($row['punch_in_time']);
                                            ?>
                                            <?php if (!in_array($row['status'], ['Absent', 'Weekly Off', 'Holiday', 'On Leave']) && $actual_punchin_time > $expected_punchin_time): ?>
                                              <img src="assets/img/logos/tortoise.png" alt="" style="height: 20px;width:20px">
                                            <?php endif; ?>
                                            <?= date('H:i:s', strtotime($row['punch_in_time'])) ?>
                                            <?php if ($row['location_in']): ?>
                                              <a onclick="viewLocation(<?= explode(',', $row['location_in'])[0] ?>, <?= explode(',', $row['location_in'])[1] ?>)">
                                                <img src="assets/img/location.png" class="record-location-icon" alt="">
                                              </a>
                                            <?php else: ?>
                                              <img src="assets/img/no-gps.png" class="record-gps-icon" alt="">
                                            <?php endif; ?>
                                          </h6>
                                        </div>
                                      </div>
                                    </td>
                                    <td>
                                      <div class="d-flex px-2 py-1">
                                        <div>
                                          <?php if ($row['selfie_out']): ?>
                                            <img src="<?= htmlspecialchars($row['selfie_out']) ?>" class="avatar avatar-sm me-3" alt="user1">
                                          <?php else: ?>
                                            <img src="assets/img/user-account (1).png" alt="">
                                          <?php endif; ?>
                                        </div>
                                        <div class="d-flex flex-column justify-content-center">
                                          <?php if ($row['punch_out_time']): ?>
                                            <h6 class="mb-0 text-sm">
                                              <?= date('Y-m-d', strtotime($row['punch_out_time'])) ?><br>
                                              <?php
                                              $expected_punchout_time = strtotime("$record_date " . $row['punchout_time']);
                                              $actual_punchout_time = strtotime($row['punch_out_time']);
                                              ?>
                                              <?php if (!in_array($row['status'], ['Absent', 'Weekly Off', 'Holiday', 'On Leave']) && $actual_punchout_time < $expected_punchout_time): ?>
                                                <img src="assets/img/logos/rabbit.png" alt="" style="height: 20px;width:20px">
                                              <?php endif; ?>
                                              <?= date('H:i:s', strtotime($row['punch_out_time'])) ?>
                                              <?php if ($row['is_auto_punchout'] == 1): ?>
                                                <img src="assets/img/logos/auto.png" alt="" style="height: 20px;width:20px">
                                              <?php endif; ?>
                                              <?php if ($row['location_out']): ?>
                                                <a onclick="viewLocation(<?= explode(',', $row['location_out'])[0] ?>, <?= explode(',', $row['location_out'])[1] ?>)">
                                                  <img src="assets/img/location.png" class="record-location-icon" alt="">
                                                </a>
                                              <?php else: ?>
                                                <img src="assets/img/no-gps.png" class="record-gps-icon" alt="">
                                              <?php endif; ?>
                                            </h6>
                                          <?php else: ?>
                                            Not Punched<br>Out Yet
                                          <?php endif; ?>
                                        </div>
                                      </div>
                                    </td>
                                    <td class="<?php
                                      if ($row['working_hours'] >= 10) {
                                        echo 'text-primary blink';
                                      } elseif ($row['working_hours'] >= 9) {
                                        echo 'text-success';
                                      } else {
                                        echo 'text-danger';
                                      }
                                    ?>">
                                      <?= $formatted_working_hours ?>
                                    </td>
                                    <td class="<?php
                                      if ($row['break_hours'] >= 10) {
                                        echo 'text-primary blink';
                                      } elseif ($row['break_hours'] >= 9) {
                                        echo 'text-success';
                                      } else {
                                        echo 'text-danger';
                                      }
                                    ?>">
                                      <?= $formatted_break_hours ?>
                                    </td>
                                    <td class="<?php
                                      if ($actual_hours >= 10) {
                                        echo 'text-primary blink';
                                      } elseif ($actual_hours >= 9) {
                                        echo 'text-success';
                                      } else {
                                        echo 'text-danger';
                                      }
                                    ?>">
                                      <?= $formatted_actual_working_time ?>
                                    </td>
                                    <td class="align-middle text-center text-sm">
                                      <?php if ($row['status'] == 'Present') : ?>
                                        <span class="record-status-badge record-status-present"><?= ucfirst($row['status']) ?></span>
                                      <?php elseif ($row['status'] == 'Absent') : ?>
                                        <span class="record-status-badge record-status-absent"><?= ucfirst($row['status']) ?></span>
                                      <?php elseif ($row['status'] == 'Weekly Off') : ?>
                                        <span class="record-status-badge record-status-weekoff"><?= ucfirst($row['status']) ?></span>
                                      <?php elseif ($row['status'] == 'On Leave') : ?>
                                        <span class="record-status-badge record-status-leave"><?= ucfirst($row['status']) ?></span>
                                      <?php elseif ($row['status'] == 'Holiday') : ?>
                                        <span class="record-status-badge record-status-holiday"><?= ucfirst($row['status']) ?></span>
                                      <?php endif; ?>
                                    </td>
                                    <td>
                                      <a href="edit_attendance?id=<?= $row['id'] ?>" class="btn record-row-btn record-row-btn-edit btn-sm"><i class="bi bi-pencil-square"></i></a>
                                      <a href="delete_attendance?id=<?= $row['id'] ?>" class="btn record-row-btn record-row-btn-delete btn-sm" onclick="return confirm('Are you sure you want to delete this attendance record?');"><i class="bi bi-trash-fill"></i></a>
                                    </td>
                                  </tr>
                                  <?php $total_working_hours += (float) $row['working_hours']; ?>
                                  <?php $total_break_hours += (float) $row['break_hours']; ?>
                                <?php endwhile; ?>
                              </tbody>
                              <tfoot>
                                <tr>
                                  <th colspan="4" class="text-end">Total Working Hours:</th>
                                  <th><?= number_format($total_working_hours, 2) ?> hrs</th>
                                  <th><?= number_format($total_break_hours, 2) ?> hrs</th>
                                  <th colspan="3"></th>
                                </tr>
                              </tfoot>
                            </table>
                            <div class="record-delete-bar">
                              <button type="submit" class="btn record-btn record-btn-primary mb-0" onclick="return confirm('Are you sure you want to delete the selected records?');">
                                Delete Selected
                              </button>
                            </div>
                          </form>
                        <?php else: ?>
                          <div class="record-empty">No attendance records found for the selected filters.</div>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            </div>
            <script>
              function viewLocation(lat, long) {
                const url = `https://www.google.com/maps?q=${lat},${long}`;
                window.open(url, '_blank');
              }

              function redirectToSite() {
                var site = document.getElementById('site').value;
                if (site) {
                  window.location.href = site;
                }
              }
            </script>
            <script>
              document.getElementById('select_all').addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('input[name="ids[]"]');
                for (const checkbox of checkboxes) {
                  checkbox.checked = this.checked;
                }
              });
            </script>
            <?php include("footer.php"); ?>
