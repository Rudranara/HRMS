<?php
include("header.php");
// Get filter inputs
$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$selected_month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$selected_office = isset($_GET['office']) ? $_GET['office'] : '';
$decoded_office = $selected_office !== '' ? urldecode($selected_office) : '';
// Fetch offices from the database
$offices_query = $conn->query("SELECT office_name, state_name FROM offices ORDER BY office_name ASC");
$offices = $offices_query->fetch_all(MYSQLI_ASSOC);
// Fetch holidays
$holiday_query = $conn->prepare("
    SELECT start_date FROM events 
    WHERE YEAR(start_date) = ? AND MONTH(start_date) = ?
");
$holiday_query->bind_param("ii", $selected_year, $selected_month);
$holiday_query->execute();
$holidays = $holiday_query->get_result()->fetch_all(MYSQLI_ASSOC);
$holiday_dates = array_column($holidays, 'start_date');
// Calculate total working days (excluding weekends and holidays)
$total_days_in_month = cal_days_in_month(CAL_GREGORIAN, $selected_month, $selected_year);
$total_working_days = 0;
for ($day = 1; $day <= $total_days_in_month; $day++) {
    $date = sprintf('%04d-%02d-%02d', $selected_year, $selected_month, $day);
    $day_of_week = date('N', strtotime($date)); // 1 (Monday) to 7 (Sunday)

    if ($day_of_week < 7 && !in_array($date, $holiday_dates)) {
        $total_working_days++;
    }
}
// Fetch employees based on the selected office
$employee_query = "SELECT id, name, office, punchin_time, punchout_time FROM employees";

if ($decoded_office !== '') {
    $employee_query .= " WHERE office = ?";
}
$employees_stmt = $conn->prepare($employee_query);
if ($decoded_office !== '') {
    $employees_stmt->bind_param("s", $decoded_office);
}
$employees_stmt->execute();
$employees_result = $employees_stmt->get_result();
$employees = $employees_result->fetch_all(MYSQLI_ASSOC);
$employees_stmt->close();
?>

<style>
.attendance-summary-page {
    background:
        radial-gradient(circle at top right, rgba(15, 23, 42, 0.05), transparent 24%),
        linear-gradient(180deg, #f6f7f9 0%, #f2f4f7 100%);
}

.attendance-summary-tabs {
    margin-bottom: 1.1rem;
}

.attendance-summary-tab-row {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.9rem;
}

.attendance-summary-tab {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 52px;
    padding: 0.8rem 1.15rem;
    border-radius: 20px;
    border: 1px solid #d7e0eb;
    background: #ffffff;
    color: #27466a;
    font-size: 0.88rem;
    font-weight: 800;
    letter-spacing: 0.02em;
    text-transform: uppercase;
    text-decoration: none;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
    transition: all 0.18s ease;
}

.attendance-summary-tab.is-active {
    color: #ffffff;
    border-color: #1c2432;
    background: linear-gradient(135deg, #172030 0%, #222c3d 100%);
    box-shadow: 0 16px 30px rgba(17, 24, 39, 0.18);
}

.attendance-summary-tab:hover {
    color: #16324f;
    border-color: #c6d3e0;
    background: #f8fafc;
}

.attendance-summary-tab.is-active:hover {
    color: #ffffff;
    background: linear-gradient(135deg, #172030 0%, #222c3d 100%);
}

.attendance-summary-title {
    margin: 0 0 1rem;
    color: #111827;
    font-size: 1.55rem;
    font-weight: 800;
    letter-spacing: -0.02em;
}

.attendance-summary-filter-card,
.attendance-summary-table-card {
    border: 1px solid rgba(107, 114, 128, 0.14);
    border-radius: 24px;
    background: rgba(255, 255, 255, 0.96);
    box-shadow: 0 18px 42px rgba(15, 23, 42, 0.07);
}

.attendance-summary-filter-card {
    margin-bottom: 1.2rem;
    padding: 1.05rem;
    background: linear-gradient(180deg, #fafbfc 0%, #f7f9fb 100%);
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.9), 0 14px 32px rgba(15, 23, 42, 0.05);
}

.attendance-summary-page label {
    display: block;
    margin-bottom: 0.45rem;
    color: #6b7280;
    font-size: 0.76rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
}

.attendance-summary-page .form-control,
.attendance-summary-page select.form-control {
    min-height: 44px;
    border: 1px solid #d8dee7;
    border-radius: 14px;
    background: #ffffff;
    box-shadow: none;
    color: #1f2937;
    padding: 0.65rem 0.9rem;
}

.attendance-summary-page .form-control:focus,
.attendance-summary-page select.form-control:focus {
    border-color: #16324f;
    box-shadow: 0 0 0 0.18rem rgba(22, 50, 79, 0.12);
}

.attendance-summary-filter-btn,
.attendance-summary-download-btn {
    min-height: 44px;
    border-radius: 14px;
    font-size: 0.82rem;
    font-weight: 700;
    box-shadow: none;
}

.attendance-summary-filter-btn {
    background: linear-gradient(135deg, #171717 0%, #2f2f2f 100%) !important;
    border: 1px solid #171717 !important;
    color: #ffffff !important;
}

.attendance-summary-filter-btn:hover {
    background: linear-gradient(135deg, #111111 0%, #252525 100%) !important;
    color: #ffffff !important;
}

.attendance-summary-download-btn {
    background: #e8f7f1 !important;
    border: 1px solid #c7e8dd !important;
    color: #2f6f62 !important;
}

.attendance-summary-download-btn:hover {
    background: #dff2ea !important;
    color: #24584f !important;
}

.attendance-summary-table-card {
    overflow: hidden;
}

.attendance-summary-table-card .card-body {
    padding: 0;
}

.attendance-summary-table-wrap {
    padding: 0 1.1rem 1.1rem;
}

.attendance-summary-table {
    margin-bottom: 0;
    table-layout: fixed;
    width: 100%;
}

.attendance-summary-table thead th {
    border-bottom: 1px solid #e8edf3;
    background: #f8fafc;
    color: #6b7280;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    padding: 0.8rem 0.55rem;
    white-space: normal;
    line-height: 1.25;
}

.attendance-summary-table tbody td {
    padding: 0.8rem 0.55rem;
    border-bottom: 1px solid #eef2f7;
    color: #1f2937;
    vertical-align: middle;
    font-size: 0.82rem;
    white-space: normal;
    line-height: 1.35;
    word-break: break-word;
}

.attendance-summary-table tbody tr:last-child td {
    border-bottom: none;
}

.attendance-summary-table tbody tr:hover {
    background: #fbfcfe;
}

@media (max-width: 991.98px) {
    .attendance-summary-filter-card .row {
        --bs-gutter-x: 0.85rem;
        --bs-gutter-y: 0.85rem;
    }
}

@media (max-width: 767.98px) {
    .attendance-summary-tab-row {
        grid-template-columns: 1fr;
    }

    .attendance-summary-filter-card,
    .attendance-summary-table-card {
        border-radius: 20px;
    }

    .attendance-summary-filter-card {
        padding: 0.9rem;
    }

    .attendance-summary-table-wrap {
        padding: 0 0.85rem 0.95rem;
    }
}
</style>

<div class="container-fluid py-4 attendance-summary-page">
    <div class="row">
        <div class="col-12 attendance-summary-tabs">
            <div class="attendance-summary-tab-row">
                <a href="all_employees_attendance" class="attendance-summary-tab is-active">Attendance Summary</a>
                <a href="employee_yearly_attendance" class="attendance-summary-tab">Yearly Summary</a>
                <a href="leave_summary" class="attendance-summary-tab">Leave Summary</a>
                <a href="yearly_salary_summary" class="attendance-summary-tab">Salary Summary</a>
            </div>
        </div>
        <div class="col-12">
            <h6 class="attendance-summary-title">Employees Attendance Summary</h6>
        </div>
    </div>
    <div class="attendance-summary-filter-card">
    <form method="GET" class="mb-0">
        <div class="row align-items-end">
            <div class="col-md-4">
                <label>Select Office</label>
                <select name="office" id="office" class="form-control">
                    <option value="">All Offices</option>
                    <?php foreach ($offices as $office):
                        $office_value = urlencode($office['office_name']) . "_" . urlencode($office['state_name']);
                    ?>
                        <option value="<?= $office_value ?>" <?= $selected_office == $office_value ? 'selected' : '' ?>>
                            <?= htmlspecialchars($office['office_name']) ?> (<?= htmlspecialchars($office['state_name']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label>Select Year</label>
                <select name="year" class="form-control">
                    <?php for ($y = date('Y') - 5; $y <= date('Y'); $y++): ?>
                        <option value="<?= $y ?>" <?= $y == $selected_year ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label>Select Month</label>
                <select name="month" class="form-control">
                    <?php
                    $months = [
                        1 => 'January',
                        2 => 'February',
                        3 => 'March',
                        4 => 'April',
                        5 => 'May',
                        6 => 'June',
                        7 => 'July',
                        8 => 'August',
                        9 => 'September',
                        10 => 'October',
                        11 => 'November',
                        12 => 'December'
                    ];
                    foreach ($months as $key => $value): ?>
                        <option value="<?= $key ?>" <?= $key == $selected_month ? 'selected' : '' ?>><?= $value ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end mt-4">
                <button type="submit" class="btn attendance-summary-filter-btn w-100">Filter</button>
            </div>
            <div class="col-md-2 d-flex align-items-end mt-4">
                <a href="download_attendance?year=<?= $selected_year ?>&month=<?= $selected_month ?>&office=<?= htmlspecialchars($selected_office) ?>" class="btn attendance-summary-download-btn w-100">Download CSV</a>
            </div>

        </div>
    </form>
    </div>
    <div class="col-12">
        <div class="card attendance-summary-table-card mb-4">
            <div class="card-body px-0 pt-0 pb-2">
                <div class="table-responsive attendance-summary-table-wrap">
                    <!-- Summary Table -->
                    <table class="table align-items-center mb-0 attendance-summary-table">
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Employee Name</th>
                                <th>Office</th>
                                <th>Total Present</th>
                                <th>Total Absent</th>
                                <th>Total On Leave</th>
                                <th>Total Late Punch-ins</th>
                                <th>Total Early Punch-outs</th>
                                <th>Total Working Hours</th>
                                <th>Total Break Hours</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employees as $employee):
                                $employee_id = $employee['id'];
                                $punchin_time = date('H:i:s', strtotime($employee['punchin_time']));
                                $punchout_time = date('H:i:s', strtotime($employee['punchout_time']));

                                // Fetch attendance for this employee
                                $stmt = $conn->prepare("
                    SELECT punch_in_time, punch_out_time, status, working_hours, break_hours 
                    FROM attendance 
                    WHERE employee_id = ? AND YEAR(punch_in_time) = ? AND MONTH(punch_in_time) = ?
                ");
                                $stmt->bind_param("iii", $employee_id, $selected_year, $selected_month);
                                $stmt->execute();
                                $attendance_records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                                $stmt->close();

                                // Initialize stats
                                $total_present = 0;
                                $total_absent = 0;
                                $total_on_leave = 0;
                                $total_late = 0;
                                $total_early = 0;
                                $total_working_hours = 0;
                                $total_break_hours = 0;

                                foreach ($attendance_records as $record) {
                                    $punch_in_time = date('H:i:s', strtotime($record['punch_in_time']));
                                    $punch_out_time = date('H:i:s', strtotime($record['punch_out_time']));

                                    if ($record['status'] == 'Present') {
                                        $total_present++;
                                        if ($punchin_time && $punch_in_time > $punchin_time) $total_late++;
                                        if ($punchout_time && $punch_out_time < $punchout_time) $total_early++;
                                        $total_working_hours += $record['working_hours'];
                                        $total_break_hours += $record['break_hours'];
                                    } elseif ($record['status'] == 'Absent') {
                                        $total_absent++;
                                    } elseif ($record['status'] == 'On_Leave') {
                                        $total_on_leave++;
                                    }
                                }
                            ?>
                                <tr>
                                    <td><?= $employee_id ?></td>
                                    <td><?= $employee['name'] ?></td>
                                    <td><?= $employee['office'] ?></td>
                                    <td><?= $total_present ?></td>
                                    <td><?= $total_absent ?></td>
                                    <td><?= $total_on_leave ?></td>
                                    <td><?= $total_late ?></td>
                                    <td><?= $total_early ?></td>
                                    <td><?= $total_working_hours ?> hrs</td>
                                    <td><?= $total_break_hours ?> hrs</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="mt-3">

                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
</div>
<!-- End Navbar -->
<?php include("footer.php") ?>
