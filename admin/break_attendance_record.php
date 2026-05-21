<?php
include("header.php");
require 'db_connection.php';

$selected_office = isset($_GET['office']) ? trim(urldecode($_GET['office'])) : '';

$employees_stmt = $conn->prepare("SELECT id, name FROM employees");
$employees_stmt->execute();
$employees_result = $employees_stmt->get_result();

$current_date = date('Y-m-d');
$current_month = date('m');
$current_year = date('Y');
$selected_employee = isset($_GET['employee_id']) ? $_GET['employee_id'] : '';
$selected_month = isset($_GET['month']) ? $_GET['month'] : $current_month;
$selected_year = isset($_GET['year']) ? $_GET['year'] : $current_year;
$filter_start = "{$selected_year}-{$selected_month}-01";
$filter_end = date("Y-m-t 23:59:59", strtotime($filter_start));

$attendance_query = "    
    SELECT a.*, e.name AS employee_name, e.employee_id AS emp_id,
           e.punchin_time, e.punchout_time, e.office
    FROM break_attendance a
    INNER JOIN employees e ON a.employee_id = e.id
    WHERE a.punch_out_time BETWEEN ? AND ? 
";
if ($selected_employee) {
    $attendance_query .= " AND a.employee_id = ?";
}
if ($selected_office) {
    $attendance_query .= " AND e.office = ?";
}
$attendance_query .= " ORDER BY a.punch_in_time DESC";

$attendance_stmt = $conn->prepare($attendance_query);

if ($selected_employee && $selected_office) {
    $attendance_stmt->bind_param("ssis", $filter_start, $filter_end, $selected_employee, $selected_office);
} elseif ($selected_employee) {
    $attendance_stmt->bind_param("ssi", $filter_start, $filter_end, $selected_employee);
} elseif ($selected_office) {
    $attendance_stmt->bind_param("sss", $filter_start, $filter_end, $selected_office);
} else {
    $attendance_stmt->bind_param("ss", $filter_start, $filter_end);
}

$attendance_stmt->execute();
$attendance_result = $attendance_stmt->get_result();

$offices_query = $conn->query("SELECT office_name, state_name  FROM offices ORDER BY office_name ASC");
$offices = $offices_query->fetch_all(MYSQLI_ASSOC);
?>
<style>
.break-record-shell {
  padding-bottom: 1.5rem;
}

.break-record-muted {
  color: #6b7280;
}

.break-filter-card,
.break-table-card {
  border: 1px solid rgba(87, 96, 108, 0.12);
  border-radius: 22px;
  box-shadow: 0 14px 34px rgba(31, 41, 55, 0.06);
  background: #fff;
}

.break-filter-card {
  padding: 1rem 1.1rem;
  margin-bottom: 1.15rem;
}

.break-filter-card label {
  font-size: 0.74rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: #6b7280;
  margin-bottom: 0.4rem;
}

.break-filter-card .form-control {
  min-height: 44px;
  border-radius: 14px;
  border: 1px solid #d8dee7;
  color: #374151;
}

.break-filter-card .form-control:focus {
  border-color: #aab7c9;
  box-shadow: 0 0 0 0.2rem rgba(55, 65, 81, 0.08);
}

.break-title {
  margin: 0;
  color: #111827;
  font-size: 0.98rem;
  font-weight: 800;
}

.break-meta {
  color: #94a3b8;
  font-size: 0.74rem;
  margin-top: 0.2rem;
  line-height: 1.5;
}

.break-btn {
  min-height: 44px;
  border-radius: 14px;
  padding-left: 1rem;
  padding-right: 1rem;
  box-shadow: 0 10px 24px rgba(31, 41, 55, 0.10);
}

.break-filter-form-row {
  display: flex;
  align-items: flex-end;
  gap: 0.8rem;
  flex-wrap: nowrap;
}

.break-filter-intro {
  flex: 0 0 23%;
  min-width: 220px;
  padding-right: 0.2rem;
}

.break-filter-field {
  flex: 1 1 0;
  min-width: 0;
}

.break-filter-field-office {
  flex-basis: 16%;
}

.break-filter-field-employee {
  flex-basis: 18%;
}

.break-filter-field-month {
  flex-basis: 15%;
}

.break-filter-field-year {
  flex: 0 0 7%;
  min-width: 92px;
}

.break-action-group {
  display: flex;
  align-items: end;
  justify-content: end;
  gap: 0.75rem;
  height: auto;
  width: auto;
  flex: 0 0 auto;
}

.break-action-group .break-btn {
  min-width: 108px;
  width: auto;
  white-space: nowrap;
}

.break-action-group .break-btn-today {
  min-width: 132px;
}

.break-btn-primary {
  background: linear-gradient(180deg, #2b2c31 0%, #1f2024 100%);
  color: #fff !important;
  border: 1px solid #2b2c31;
  box-shadow: 0 10px 24px rgba(24, 24, 27, 0.22);
}

.break-btn-primary:hover {
  background: linear-gradient(180deg, #32343a 0%, #23242a 100%);
  color: #fff !important;
  border-color: #32343a;
}

.break-btn-secondary {
  background: #08285c;
  color: #fff !important;
  border: 1px solid #08285c;
}

.break-btn-secondary:hover {
  background: #061f47;
  color: #fff !important;
}

.break-table-card {
  overflow: hidden;
}

.break-table-card .card-body {
  padding: 0;
}

.break-table-title {
  padding: 1.2rem 1.25rem 0.85rem;
}

.break-table-title h6 {
  margin: 0;
  font-size: 1rem;
  color: #111827;
}

.break-table-title p {
  margin: 0.3rem 0 0;
  color: #94a3b8;
  font-size: 0.8rem;
}

.break-table {
  margin-bottom: 0;
}

.break-table thead th {
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

.break-table tbody td,
.break-table tfoot th,
.break-table tfoot td {
  padding-top: 1rem;
  padding-bottom: 1rem;
  border-color: #eef2f7;
  vertical-align: middle;
  text-align: center;
}

.break-table tbody tr:hover {
  background: #fbfcfe;
}

.break-table .avatar,
.break-table img.avatar-sm {
  border-radius: 14px;
  object-fit: cover;
  border: 1px solid #e5e7eb;
}

.break-table h6 {
  color: #111827;
  font-weight: 700;
}

.break-table td .d-flex {
  justify-content: center;
}

.break-table td .d-flex.flex-column,
.break-table td .d-flex .d-flex.flex-column {
  align-items: center;
  text-align: center;
}

.break-table td .text-xs,
.break-table td p {
  text-align: center;
}

.break-table .text-xs {
  color: #6b7280 !important;
}

.break-table .text-primary,
.break-table .text-success {
  color: #334155 !important;
}

.break-table .text-danger {
  color: #7c2d12 !important;
}

.break-row-btn {
  min-width: 3.2rem;
  border: 1px solid #d9e0e8;
  border-radius: 12px;
  box-shadow: 0 10px 18px rgba(31, 41, 55, 0.08);
}

.break-row-btn-edit {
  background: #08285c;
  border-color: #08285c;
  color: #fff;
}

.break-row-btn-edit:hover {
  background: #061f47;
  border-color: #061f47;
  color: #fff;
}

.break-row-btn-delete {
  background: linear-gradient(180deg, #2b2c31 0%, #1f2024 100%);
  border-color: #2b2c31;
  color: #fff;
}

.break-row-btn-delete:hover {
  background: linear-gradient(180deg, #32343a 0%, #23242a 100%);
  border-color: #32343a;
  color: #fff;
}

.break-status-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 104px;
  padding: 0.42rem 0.7rem;
  border-radius: 999px;
  font-size: 0.68rem;
  font-weight: 800;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  border: 1px solid transparent;
}

.break-status-taken {
  background: #e8f7ef;
  border-color: #cfe9da;
  color: #1f8f57;
}

.break-status-not-taken {
  background: #fdf2f2;
  border-color: #f3d6d6;
  color: #991b1b;
}

.break-status-weekoff,
.break-status-holiday {
  background: #f8fafc;
  border-color: #dbe3ed;
  color: #475569;
}

.break-status-leave {
  background: #f5f3ff;
  border-color: #e4ddff;
  color: #5b3dbb;
}

.break-delete-bar {
  padding: 1rem 1.25rem 1.25rem;
  border-top: 1px solid #eef2f7;
  background: linear-gradient(180deg, rgba(248, 250, 252, 0.35) 0%, rgba(255, 255, 255, 1) 100%);
}

.break-checkbox-cell .checkbox__checkmark,
.checkboxes__item .checkbox__checkmark {
  background-color: #1f2937 !important;
  box-shadow: 0 8px 16px rgba(31, 41, 55, 0.14);
}

.break-checkbox-cell {
  width: 64px;
  min-width: 64px;
  text-align: center;
  vertical-align: middle;
  padding-left: 0 !important;
  padding-right: 0 !important;
}

.break-checkbox-cell .checkboxes__item {
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0;
  width: 100%;
}

.break-checkbox-cell .checkbox {
  margin: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
}

.break-location-icon,
.break-gps-icon {
  width: 18px;
  height: 18px;
}

.break-empty {
  padding: 1.5rem 1.25rem 2rem;
  color: #6b7280;
}

.break-table tfoot th,
.break-table tfoot td {
  background: #f8fafc;
  font-weight: 700;
  color: #334155;
}

.break-filter-card .btn:focus,
.break-row-btn:focus,
.break-btn:focus {
  box-shadow: 0 0 0 0.2rem rgba(8, 40, 92, 0.18) !important;
}

@media (max-width: 991.98px) {
  .break-filter-card,
  .break-table-card {
    border-radius: 18px;
  }

  .break-filter-form-row {
    flex-wrap: wrap;
  }

  .break-filter-intro,
  .break-filter-field,
  .break-filter-field-office,
  .break-filter-field-employee,
  .break-filter-field-month,
  .break-filter-field-year {
    flex: 1 1 calc(50% - 0.8rem);
    min-width: 220px;
  }

  .break-action-group {
    flex: 1 1 100%;
    width: 100%;
    justify-content: stretch;
    flex-wrap: wrap;
  }

  .break-action-group .break-btn {
    width: 100%;
    min-width: 0;
  }
}

@media (max-width: 575.98px) {
  .break-filter-intro,
  .break-filter-field,
  .break-filter-field-office,
  .break-filter-field-employee,
  .break-filter-field-month,
  .break-filter-field-year,
  .break-action-group {
    flex: 1 1 100%;
    min-width: 100%;
  }
}
</style>

<div class="container-fluid container-fluid-main break-record-shell py-4">
    <div class="row">
        <div class="col-12">
            <div class="break-filter-card">
                <form method="GET" action="break_attendance_record">
                    <div class="break-filter-form-row">
                      <div class="break-filter-intro">
                            <div class="break-title">Admin Break Report</div>
                            <div class="break-meta">Break attendance reporting with a cleaner, dashboard-aligned interface.</div>
                        </div>

                      <div class="break-filter-field break-filter-field-office">
                            <label for="office">Select Office</label>
                            <select name="office" id="office" class="form-control">
                                <option value="">All Offices</option>
                                <?php
                                foreach ($offices as $office):
                                    $office_value = $office['office_name'] . "_" . $office['state_name'];
                                ?>
                                    <option value="<?= htmlspecialchars($office_value) ?>" <?= $selected_office == $office_value ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($office['office_name']) ?> (<?= htmlspecialchars($office['state_name']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="break-filter-field break-filter-field-employee">
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

                        <div class="break-filter-field break-filter-field-month">
                            <label for="month">Select Month</label>
                            <select name="month" id="month" class="form-control">
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>" <?= $selected_month == $m ? 'selected' : '' ?>>
                                        <?= date("F", mktime(0, 0, 0, $m, 10)) ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="break-filter-field break-filter-field-year">
                            <label for="year">Select Year</label>
                            <select name="year" id="year" class="form-control">
                                <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                    <option value="<?= $y ?>" <?= $selected_year == $y ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="break-action-group ms-auto">
                          <button type="submit" class="btn break-btn break-btn-primary mb-0">Filter</button>
                          <a href="download_break_attendance_csv?employee_id=<?= urlencode($selected_employee) ?>&month=<?= urlencode($selected_month) ?>&year=<?= urlencode($selected_year) ?>&office=<?= urlencode($selected_office) ?>" class="btn break-btn break-btn-secondary mb-0"><i class="bi bi-cloud-arrow-down-fill"></i> CSV</a>
                          <a href="manage_break_attendance" class="btn break-btn break-btn-primary break-btn-today mb-0">Today Record</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-12">
            <div class="card break-table-card mb-4">
                <div class="break-table-title">
                    <h6>Break Report (<?= date('d M Y', strtotime($filter_start)) ?> - <?= date('d M Y', strtotime($filter_end)) ?>)</h6>
                    <p>Break start and end records with duration, status, locations, and record management actions.</p>
                </div>
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="table-responsive p-0">
                        <?php if ($attendance_result->num_rows > 0): ?>
                            <form method="POST" action="break_delete_multiple">
                                <table class="table break-table align-items-center mb-0">
                                    <thead>
                                        <tr>
                                            <th class="break-checkbox-cell">
                                                <div class="checkboxes__item">
                                                    <label class="checkbox style-h">
                                                        <input type="checkbox" id="select_all">
                                                        <div class="checkbox__checkmark"></div>
                                                    </label>
                                                </div>
                                            </th>
                                            <th>Employee Name</th>
                                            <th>Start Time</th>
                                            <th>End Time</th>
                                            <th>Break Hours</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $total_working_hours = 0; ?>
                                        <?php while ($row = $attendance_result->fetch_assoc()): ?>
                                            <?php
                                            $hours = floor($row['working_hours']);
                                            $minutes = round(($row['working_hours'] - $hours) * 60);
                                            $formatted_working_hours = sprintf("%02d:%02d", $hours, $minutes);
                                            ?>
                                            <tr>
                                                <td class="break-checkbox-cell">
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
                                                                <?= date('H:i:s', strtotime($row['punch_in_time'])) ?>
                                                                <?php if ($row['location_in']): ?>
                                                                    <a onclick="viewLocation(<?= explode(',', $row['location_in'])[0] ?>, <?= explode(',', $row['location_in'])[1] ?>)">
                                                                        <img src="assets/img/location.png" class="break-location-icon" alt="">
                                                                    </a>
                                                                <?php else: ?>
                                                                    <img src="assets/img/no-gps.png" class="break-gps-icon" alt="">
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
                                                            <h6 class="mb-0 text-sm">
                                                                <?= date('Y-m-d', strtotime($row['punch_out_time'])) ?><br>
                                                                <?php
                                                                $expected_punchout_time = strtotime("$record_date " . $row['punchout_time']);
                                                                $actual_punchout_time = strtotime($row['punch_out_time']);
                                                                ?>
                                                                <?= date('H:i:s', strtotime($row['punch_out_time'])) ?>
                                                                <?php if ($row['is_auto_punchout'] == 1): ?>
                                                                    <img src="assets/img/logos/auto.png" alt="" style="height: 20px;width:20px">
                                                                <?php endif; ?>
                                                                <?php if ($row['location_out']): ?>
                                                                    <a onclick="viewLocation(<?= explode(',', $row['location_out'])[0] ?>, <?= explode(',', $row['location_out'])[1] ?>)">
                                                                        <img src="assets/img/location.png" class="break-location-icon" alt="">
                                                                    </a>
                                                                <?php else: ?>
                                                                    <img src="assets/img/no-gps.png" class="break-gps-icon" alt="">
                                                                <?php endif; ?>
                                                            </h6>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="<?php 
                                                    if ($row['working_hours'] >= 2) {
                                                        echo 'text-primary blink';
                                                    } elseif ($row['working_hours'] <= 1) {
                                                        echo 'text-success';
                                                    } else {
                                                        echo 'text-danger';
                                                    }
                                                ?>">
                                                    <?= $formatted_working_hours ?>
                                                </td>
                                                <td class="align-middle text-center text-sm">
                                                    <?php if ($row['status'] == 'Present') : ?>
                                                        <span class="break-status-badge break-status-taken">Break Taken</span>
                                                    <?php elseif ($row['status'] == 'Absent') : ?>
                                                        <span class="break-status-badge break-status-not-taken">Break Not Taken</span>
                                                    <?php elseif ($row['status'] == 'Weekly Off') : ?>
                                                        <span class="break-status-badge break-status-weekoff"><?= ucfirst($row['status']) ?></span>
                                                    <?php elseif ($row['status'] == 'On Leave') : ?>
                                                        <span class="break-status-badge break-status-leave"><?= ucfirst($row['status']) ?></span>
                                                    <?php elseif ($row['status'] == 'Holiday') : ?>
                                                        <span class="break-status-badge break-status-holiday"><?= ucfirst($row['status']) ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="edit_break_attendance?id=<?= $row['id'] ?>" class="btn break-row-btn break-row-btn-edit btn-sm"><i class="bi bi-pencil-square"></i></a>
                                                    <a href="delete_break_attendance?id=<?= $row['id'] ?>" class="btn break-row-btn break-row-btn-delete btn-sm" onclick="return confirm('Are you sure you want to delete this attendance record?');"><i class="bi bi-trash-fill"></i></a>
                                                </td>
                                            </tr>
                                            <?php $total_working_hours += (float) $row['working_hours']; ?>
                                        <?php endwhile; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="4" class="text-end">Total Working Hours:</th>
                                            <th><?= number_format($total_working_hours, 2) ?> hrs</th>
                                            <th colspan="2"></th>
                                        </tr>
                                    </tfoot>
                                </table>
                                <div class="break-delete-bar">
                                    <button type="submit" class="btn break-btn break-btn-primary mb-0" onclick="return confirm('Are you sure you want to delete the selected records?');">
                                        Delete Selected
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="break-empty">No attendance records found for the selected filters.</div>
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
