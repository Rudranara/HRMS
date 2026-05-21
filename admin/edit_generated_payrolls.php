<?php
include("header2.php");
require_once 'get_attendance_stats3.php';

if (!function_exists('ensureSalaryPayrollColumns')) {
    function ensureSalaryPayrollColumns(mysqli $conn): void
    {
        $requiredColumns = [
            'esic_employee' => "DECIMAL(10,2) NOT NULL DEFAULT 0.00",
            'gmc_employer' => "DECIMAL(10,2) NOT NULL DEFAULT 0.00",
        ];

        foreach ($requiredColumns as $columnName => $definition) {
            $escapedColumn = $conn->real_escape_string($columnName);
            $columnExists = false;
            $result = $conn->query("SHOW COLUMNS FROM salary LIKE '{$escapedColumn}'");

            if ($result instanceof mysqli_result) {
                $columnExists = $result->num_rows > 0;
                $result->free();
            }

            if (!$columnExists) {
                $conn->query("ALTER TABLE salary ADD COLUMN {$columnName} {$definition}");
            }
        }
    }
}

ensureSalaryPayrollColumns($conn);

$selected_salary_ids = isset($_GET['selected_ids']) ? (array) $_GET['selected_ids'] : (isset($_GET['selected_ids[]']) ? (array) $_GET['selected_ids[]'] : []);
$selected_salary_ids = array_values(array_filter(array_map('intval', $selected_salary_ids)));
$selected_year = isset($_GET['year']) ? (int) $_GET['year'] : date('Y');
$selected_month = isset($_GET['month']) ? (int) $_GET['month'] : 0;
$selected_office = isset($_GET['office']) ? trim(urldecode($_GET['office'])) : '';
$selected_employee = isset($_GET['employee_id']) ? (int) $_GET['employee_id'] : 0;

if (!empty($selected_salary_ids)) {
    $selected_salary_placeholders = implode(',', array_fill(0, count($selected_salary_ids), '?'));
    $selected_salary_types = str_repeat('i', count($selected_salary_ids));
    $selected_salary_stmt = $conn->prepare("SELECT year, month FROM salary WHERE id IN ($selected_salary_placeholders) ORDER BY year DESC, month DESC LIMIT 1");
    $selected_salary_stmt->bind_param($selected_salary_types, ...$selected_salary_ids);
    $selected_salary_stmt->execute();
    $selected_salary_period = $selected_salary_stmt->get_result()->fetch_assoc();
    $selected_salary_stmt->close();

    if ($selected_salary_period) {
        $selected_year = (int) $selected_salary_period['year'];
        $selected_month = (int) $selected_salary_period['month'];
    }
}

if ($selected_month < 1 || $selected_month > 12) {
    $selected_month = (int) date('m');
}

$offices_query = $conn->query("SELECT office_name, state_name FROM offices ORDER BY office_name ASC");
$offices = $offices_query->fetch_all(MYSQLI_ASSOC);

$emp_query = "SELECT id, name, office FROM employees WHERE status = 'Active' AND 1=1";
$emp_params = [];
$emp_types = "";
if (!empty($selected_office)) {
    $emp_query .= " AND office = ?";
    $emp_params[] = $selected_office;
    $emp_types .= "s";
}
$emp_stmt = $conn->prepare($emp_query);
if (!empty($emp_params)) {
    $emp_stmt->bind_param($emp_types, ...$emp_params);
}
$emp_stmt->execute();
$employees = $emp_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$emp_stmt->close();

$total_days = cal_days_in_month(CAL_GREGORIAN, $selected_month, $selected_year);
$holiday_query = $conn->prepare("SELECT start_date FROM events WHERE YEAR(start_date) = ? AND MONTH(start_date) = ?");
$holiday_query->bind_param("ii", $selected_year, $selected_month);
$holiday_query->execute();
$holidays = $holiday_query->get_result()->fetch_all(MYSQLI_ASSOC);
$holiday_dates = array_column($holidays, 'start_date');

$total_working_days = 0;
for ($d = 1; $d <= $total_days; $d++) {
    $date = sprintf('%04d-%02d-%02d', $selected_year, $selected_month, $d);
    $day_of_week = date('N', strtotime($date));
    if ($day_of_week < 7 && !in_array($date, $holiday_dates, true)) {
        $total_working_days++;
    }
}

$salary_query = "SELECT s.*, e.name, e.employee_id AS employee_code, e.office, e.include_pt, e.include_epf, e.include_pf_ceiling
    FROM salary s
    JOIN employees e ON e.id = s.employee_id
    WHERE 1=1";

$params = [];
$types = "";

if (empty($selected_salary_ids)) {
    $salary_query .= " AND s.year = ? AND s.month = ?";
    $params[] = $selected_year;
    $params[] = $selected_month;
    $types .= "ii";
}

if (!empty($selected_office)) {
    $salary_query .= " AND e.office = ?";
    $params[] = $selected_office;
    $types .= "s";
}
if (!empty($selected_employee)) {
    $salary_query .= " AND s.employee_id = ?";
    $params[] = $selected_employee;
    $types .= "i";
}
if (!empty($selected_salary_ids)) {
    $placeholders = implode(',', array_fill(0, count($selected_salary_ids), '?'));
    $salary_query .= " AND s.id IN ($placeholders)";
    foreach ($selected_salary_ids as $salary_id) {
        $params[] = $salary_id;
        $types .= "i";
    }
}

$stmt = $conn->prepare($salary_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<style>
.edit-payroll-page {
    background:
        radial-gradient(circle at top right, rgba(15, 23, 42, 0.05), transparent 24%),
        linear-gradient(180deg, #f6f7f9 0%, #f2f4f7 100%);
}

.edit-payroll-title {
    margin: 0 0 1rem;
    color: #111827;
    font-size: 1.55rem;
    font-weight: 800;
    letter-spacing: -0.02em;
}

.edit-payroll-note,
.edit-payroll-filter-card,
.edit-payroll-table-card {
    border: 1px solid rgba(107, 114, 128, 0.14);
    border-radius: 24px;
    background: rgba(255, 255, 255, 0.96);
    box-shadow: 0 18px 42px rgba(15, 23, 42, 0.07);
}

.edit-payroll-note {
    margin-bottom: 1rem;
    padding: 0.95rem 1.1rem;
    background: #eef6ff;
    border-color: #d6e6fb;
    color: #36506b;
    font-size: 0.82rem;
    font-weight: 600;
}

.edit-payroll-filter-card {
    margin-bottom: 1.2rem;
    padding: 1.05rem;
    background: linear-gradient(180deg, #fafbfc 0%, #f7f9fb 100%);
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.9), 0 14px 32px rgba(15, 23, 42, 0.05);
}

.edit-payroll-page label {
    display: block;
    margin-bottom: 0.45rem;
    color: #6b7280;
    font-size: 0.76rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
}

.edit-payroll-page .form-control,
.edit-payroll-page select.form-control {
    min-height: 44px;
    border: 1px solid #d8dee7;
    border-radius: 14px;
    background: #ffffff;
    box-shadow: none;
    color: #1f2937;
    padding: 0.65rem 0.9rem;
}

.edit-payroll-page .form-control:focus,
.edit-payroll-page select.form-control:focus {
    border-color: #16324f;
    box-shadow: 0 0 0 0.18rem rgba(22, 50, 79, 0.12);
}

.edit-payroll-filter-btn,
.edit-payroll-save-btn {
    min-height: 44px;
    border-radius: 14px;
    font-size: 0.82rem;
    font-weight: 700;
    box-shadow: none;
}

.edit-payroll-filter-btn {
    background: linear-gradient(135deg, #171717 0%, #2f2f2f 100%) !important;
    border: 1px solid #171717 !important;
    color: #ffffff !important;
}

.edit-payroll-filter-btn:hover {
    background: linear-gradient(135deg, #111111 0%, #252525 100%) !important;
    color: #ffffff !important;
}

.edit-payroll-save-btn {
    background: #16324f !important;
    border: 1px solid #16324f !important;
    color: #ffffff !important;
    padding: 0.7rem 1.1rem;
}

.edit-payroll-save-btn:hover {
    background: #10263c !important;
    border-color: #10263c !important;
    color: #ffffff !important;
}

.edit-payroll-table-card {
    overflow: hidden;
}

.scroll-wrapper {
    max-height: 500px;
    overflow: hidden;
    position: relative;
    border-radius: 24px;
    box-shadow: none;
}

.table-scroll-container {
    overflow: auto;
    max-height: 500px;
    border: 0;
    border-radius: 0;
    background: #ffffff;
}

.salary-table {
    width: max-content;
    min-width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    text-transform: uppercase;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    font-size: 12px;
}

.salary-table thead th {
    position: sticky;
    top: 0;
    z-index: 9;
    background: #f8fafc;
    color: #6b7280;
    border-bottom: 1px solid #e5ebf2;
    border-right: 1px solid #edf1f5;
    padding: 0.95rem 0.75rem;
    white-space: nowrap;
    text-align: center;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.05em;
}

.salary-table thead th:nth-child(1),
.salary-table tbody td:nth-child(1) {
    position: sticky;
    left: 0;
    z-index: 10;
    min-width: 220px;
}

.salary-table tbody td:nth-child(1) {
    background: #ffffff;
}

.salary-table thead th:nth-child(1) {
    z-index: 12;
    background: #f8fafc;
}

.salary-table td {
    background-color: #fdfdfd;
    padding: 0.8rem 0.7rem;
    border-bottom: 1px solid #eef2f7;
    border-right: 1px solid #f3f5f8;
    white-space: nowrap;
    vertical-align: top;
}

.salary-table thead th:nth-child(n+2),
.salary-table tbody td:nth-child(n+2) {
    min-width: 118px;
    width: 118px;
    max-width: 118px;
    padding-left: 6px;
    padding-right: 6px;
}

.salary-table thead th:nth-child(n+7) {
    white-space: normal;
    line-height: 1.15;
    word-break: break-word;
}

.salary-table tbody tr:hover td {
    background-color: #fbfcfe;
}

.salary-table tbody tr:hover td:nth-child(1) {
    background-color: #fbfcfe;
}

.salary-table input[type="text"],
.salary-table input[type="decimal"],
.salary-table input[type="number"] {
    width: 100%;
    min-width: 88px;
    padding: 0.55rem 0.65rem;
    font-size: 12px;
    border: 1px solid #d8dee7;
    border-radius: 10px;
    background: #ffffff;
    box-shadow: none;
}

.salary-table input:focus {
    background: #ffffff;
    border-color: #16324f;
    box-shadow: 0 0 0 0.16rem rgba(22, 50, 79, 0.1);
    outline: none;
}

.salary-table input:hover {
    background: #f8fafc;
    border-color: #bfd0e0;
}

.actual-value {
    display: block;
    margin-bottom: 6px;
    font-size: 10px;
    line-height: 1.25;
    color: #6b7280;
    text-transform: none;
    white-space: normal;
    font-weight: 600;
}

.actual-value-placeholder {
    visibility: hidden;
}

.cell-text-value {
    display: inline-block;
    line-height: 38px;
}

.row-hidden-fields {
    display: none;
}

.table-scroll-container::-webkit-scrollbar {
    height: 10px;
    width: 10px;
}

.table-scroll-container::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 999px;
}

.table-scroll-container::-webkit-scrollbar-thumb {
    background: #c7d0dc;
    border-radius: 999px;
}

.table-scroll-container::-webkit-scrollbar-thumb:hover {
    background: #aeb8c6;
}

.edit-payroll-actionbar {
    display: flex;
    justify-content: flex-end;
    padding-top: 1rem;
}

@media (max-width: 1366px) {
    .scroll-wrapper,
    .table-scroll-container {
        max-height: 430px;
    }
}

@media (max-width: 991.98px) {
    .scroll-wrapper,
    .table-scroll-container {
        max-height: 380px;
    }

    .edit-payroll-filter-card .row {
        --bs-gutter-x: 0.85rem;
        --bs-gutter-y: 0.85rem;
    }
}

@media (max-width: 767.98px) {
    .edit-payroll-note,
    .edit-payroll-filter-card,
    .edit-payroll-table-card {
        border-radius: 20px;
    }

    .edit-payroll-filter-card {
        padding: 0.9rem;
    }
}
</style>

<div class="container-fluid container-fluid-main edit-payroll-page py-4">
    <h5 class="edit-payroll-title">Edit Generated Payrolls (<?= $selected_year ?> - <?= date('F', mktime(0, 0, 0, $selected_month, 10)) ?>)</h5>
    <div class="edit-payroll-note">
        W/D - Working Day, P/D - Present Day, A/D - Absent Day, L/D - Leave Day, C/D - Compoff Day
    </div>

    <div class="edit-payroll-filter-card">
    <form method="GET" class="row g-3 mb-4 mb-md-0 align-items-end">
        <div class="col-md-3">
            <label>Office</label>
            <select name="office" class="form-control" onchange="this.form.submit()">
                <option value="">All Offices</option>
                <?php foreach ($offices as $office):
                    $val = $office['office_name'] . "_" . $office['state_name'];
                ?>
                    <option value="<?= htmlspecialchars($val) ?>" <?= $val == $selected_office ? 'selected' : '' ?>>
                        <?= htmlspecialchars($office['office_name']) ?> (<?= htmlspecialchars($office['state_name']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label>Employee</label>
            <select name="employee_id" class="form-control">
                <option value="0">All Employees</option>
                <?php foreach ($employees as $emp): ?>
                    <option value="<?= $emp['id'] ?>" <?= $selected_employee == $emp['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($emp['name']) ?> (<?= htmlspecialchars($emp['office']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label>Year</label>
            <select name="year" class="form-control">
                <?php for ($y = date('Y') - 5; $y <= date('Y'); $y++): ?>
                    <option value="<?= $y ?>" <?= $selected_year == $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label>Month</label>
            <select name="month" class="form-control">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= $selected_month == $m ? 'selected' : '' ?>>
                        <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn edit-payroll-filter-btn w-100">Filter</button>
        </div>
    </form>
    </div>

    <form method="post" action="update_salary_records">
        <input type="hidden" name="year" value="<?= $selected_year ?>">
        <input type="hidden" name="month" value="<?= $selected_month ?>">
        <input type="hidden" name="working_days" value="<?= $total_working_days ?>">

        <div class="edit-payroll-table-card">
        <div class="scroll-wrapper">
            <div class="table-scroll-container">
                <table class="table align-items-center mb-0 salary-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>W/D</th>
                            <th>P/D</th>
                            <th>A/D</th>
                            <th>L/D</th>
                            <th>C/D</th>
                            <th>Basic Salary</th>
                            <th>DA</th>
                            <th>HRA</th>
                            <th>Conveyance</th>
                            <th>Special Allowance</th>
                            <th>Bonus Advance</th>
                            <th>Medical Allowance</th>
                            <th>Washing Allowance</th>
                            <th>Canteen Allowance</th>
                            <th>Other Allowances</th>
                            <th>Gross Salary</th>
                            <th>EPF (Employer)</th>
                            <th>ESIC (Employer)</th>
                            <th>GMC</th>
                            <th>Retention Bonus</th>
                            <th>Leave Encashment</th>
                            <th>Gratuity</th>
                            <th>Total CTC</th>
                            <th>EPF (Employee)</th>
                            <th>ESIC (Employee)</th>
                            <th>Professional Tax</th>
                            <th>Advance</th>
                            <th>Income Tax</th>
                            <th>Insurance Premium</th>
                            <th>Other Deductions</th>
                            <th>Total Deductions</th>
                            <th>Net Salary</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        function perDayCalc($amount, $days)
                        {
                            return $days > 0 ? round($amount / $days, 2) : 0;
                        }

                        $salary_components = [
                            'basic', 'da', 'hra', 'conveyance', 'special_allowance', 'performance_bonus',
                            'medical_allowance', 'washing_allowance', 'canteen_allowance', 'other_allowances',
                            'gross_salary', 'epf_employer', 'esic_employer', 'gmc', 'retention_bonus', 'leave_encashment',
                            'gratuity', 'total_ctc', 'epf_employee', 'esic_employee', 'income_tax', 'insurance_premium',
                            'other_deductions', 'total_deductions', 'net_salary'
                        ];

                        $earning_fields = [
                            'basic', 'da', 'hra', 'conveyance', 'special_allowance', 'performance_bonus',
                            'medical_allowance', 'washing_allowance', 'canteen_allowance', 'other_allowances'
                        ];

                        $deduction_fields = [
                            'epf_employee', 'esic_employee', 'income_tax', 'insurance_premium', 'other_deductions'
                        ];

                        foreach ($records as $data):
                            $id = $data['employee_id'];
                            $row_working_days = isset($data['total_working_days']) && (float) $data['total_working_days'] > 0
                                ? (float) $data['total_working_days']
                                : ((isset($data['working_days']) && (float) $data['working_days'] > 0)
                                    ? (float) $data['working_days']
                                    : (float) $total_working_days);
                            $present = $data['present_days'] ?? $total_working_days;
                            $payable_days = $data['payable_days'] ?? $present;
                            $absent = $data['absent_days'] ?? 0;
                            $leave = $data['leave_days'] ?? 0;
                            $advance = $data['advance'] ?? 0;
                            $include_epf = !empty($data['include_epf']) ? 1 : 0;
                            $include_pf_ceiling = !empty($data['include_pf_ceiling']) ? 1 : 0;
                            $include_pt = !empty($data['include_pt']) ? 1 : 0;
                            $professional_tax = $include_pt ? (float) ($data['professional_tax'] ?? 0) : 0;
                            $available_leave = max(0, (float) ($data['leave_approved'] ?? 0));
                            $leave_approved = (float) ($data['leave_approved'] ?? 0);
                            $leave_pending = (int) ($data['leave_pending'] ?? 0);
                            $leave_rejected = (int) ($data['leave_rejected'] ?? 0);
                            $on_leave_days = (int) ($data['on_leave_days'] ?? 0);
                            $late_days = (int) ($data['late_days'] ?? 0);
                            $early_out_days = (int) ($data['early_out_days'] ?? 0);
                            $total_working_hours = (float) ($data['total_working_hours'] ?? 0);
                            $expected_working_hours = (float) ($data['expected_working_hours'] ?? 0);
                            $hourly_salary = (float) ($data['hourly_salary'] ?? 0);
                            $difference_type = (string) ($data['difference_type'] ?? '');
                            $ot_or_time_lost_amount = (float) ($data['ot_or_time_lost_amount'] ?? 0);
                            $total_retentions = (float) ($data['total_retentions'] ?? 0);
                            $row_office = (string) ($data['office'] ?? '');

                            foreach ($salary_components as $component) {
                                if ($component === 'gmc') {
                                    $$component = $data['gmc_employer'] ?? $data['gmc'] ?? 0;
                                } else {
                                    $$component = $data[$component] ?? 0;
                                }
                                ${"per_day_" . $component} = perDayCalc($$component, $row_working_days);
                                ${"calculated_" . $component} = round((float) $$component, 2);
                            }
                        ?>
                            <tr data-working-days="<?= htmlspecialchars((string) $row_working_days) ?>">
                                <td>
                                    <div class="actual-value actual-value-placeholder">Placeholder</div>
                                    <a href="emp_profile?employee_id=<?= urlencode($data['employee_code']) ?>"><?= htmlspecialchars($data['name']) ?></a>
                                    <div class="row-hidden-fields">
                                        <?php foreach ($salary_components as $comp): ?>
                                            <input type="hidden" name="<?= $comp ?>[<?= $id ?>]" class="form-control <?= $comp ?>" value="<?= $$comp ?>">
                                            <input type="hidden" name="per_day_<?= $comp ?>[<?= $id ?>]" class="form-control per_day_<?= $comp ?>" value="<?= ${"per_day_" . $comp} ?>">
                                        <?php endforeach; ?>

                                        <input type="hidden" class="include_epf" value="<?= $include_epf ?>">
                                        <input type="hidden" class="include_pf_ceiling" value="<?= $include_pf_ceiling ?>">
                                        <input type="hidden" class="leave_approved_days" value="<?= htmlspecialchars((string) $leave_approved) ?>">
                                        <input type="hidden" class="payable_days" value="<?= htmlspecialchars((string) $payable_days) ?>">
                                        <input type="hidden" name="professional_tax[<?= $id ?>]" class="form-control professional_tax" value="<?= $professional_tax ?>">
                                        <input type="hidden" class="include_pt" value="<?= $include_pt ?>">
                                        <input type="hidden" name="employee_ids[]" value="<?= $id ?>">
                                        <input type="hidden" name="salary_record_ids[<?= $id ?>]" value="<?= (int) $data['id'] ?>">
                                        <input type="hidden" name="office[<?= $id ?>]" value="<?= htmlspecialchars($row_office) ?>">
                                        <input type="hidden" name="total_working_days[<?= $id ?>]" value="<?= htmlspecialchars((string) $row_working_days) ?>">
                                        <input type="hidden" name="working_days[<?= $id ?>]" value="<?= htmlspecialchars((string) $row_working_days) ?>">
                                        <input type="hidden" name="on_leave_days[<?= $id ?>]" value="<?= $on_leave_days ?>">
                                        <input type="hidden" name="late_days[<?= $id ?>]" value="<?= $late_days ?>">
                                        <input type="hidden" name="early_out_days[<?= $id ?>]" value="<?= $early_out_days ?>">
                                        <input type="hidden" name="total_working_hours[<?= $id ?>]" value="<?= htmlspecialchars((string) $total_working_hours) ?>">
                                        <input type="hidden" name="expected_working_hours[<?= $id ?>]" value="<?= htmlspecialchars((string) $expected_working_hours) ?>">
                                        <input type="hidden" name="hourly_salary[<?= $id ?>]" value="<?= htmlspecialchars((string) $hourly_salary) ?>">
                                        <input type="hidden" name="difference_type[<?= $id ?>]" value="<?= htmlspecialchars($difference_type) ?>">
                                        <input type="hidden" name="ot_or_time_lost_amount[<?= $id ?>]" value="<?= htmlspecialchars((string) $ot_or_time_lost_amount) ?>">
                                        <input type="hidden" name="leave_approved[<?= $id ?>]" value="<?= htmlspecialchars((string) $leave_approved) ?>">
                                        <input type="hidden" name="leave_pending[<?= $id ?>]" value="<?= $leave_pending ?>">
                                        <input type="hidden" name="leave_rejected[<?= $id ?>]" value="<?= $leave_rejected ?>">
                                        <input type="hidden" name="calculated_total_retentions[<?= $id ?>]" value="<?= htmlspecialchars((string) $total_retentions) ?>">
                                    </div>
                                </td>
                                <td>
                                    <div class="actual-value actual-value-placeholder">Placeholder</div>
                                    <span class="cell-text-value"><?= number_format($row_working_days, 0) ?></span>
                                </td>

                                <td>
                                    <div class="actual-value actual-value-placeholder">Placeholder</div>
                                    <input type="number" name="present_days[<?= $id ?>]" class="form-control present" value="<?= $present ?>">
                                </td>
                                <td>
                                    <div class="actual-value actual-value-placeholder">Placeholder</div>
                                    <input type="number" name="absent_days[<?= $id ?>]" class="form-control absent" value="<?= $absent ?>">
                                </td>
                                <td>
                                    <div class="actual-value">Avl Leave: <?= number_format($available_leave, 0) ?></div>
                                    <input type="number" name="leave_days[<?= $id ?>]" class="form-control leave" value="<?= $leave ?>">
                                </td>
                                <td>
                                    <div class="actual-value actual-value-placeholder">Placeholder</div>
                                    <input type="number" name="comp_days[<?= $id ?>]" class="form-control comp_days" value="<?= $data['comp_days'] ?? 0 ?>">
                                </td>

                                <?php foreach ([
                                    'basic', 'da', 'hra', 'conveyance', 'special_allowance', 'performance_bonus',
                                    'medical_allowance', 'washing_allowance', 'canteen_allowance', 'other_allowances',
                                    'gross_salary', 'epf_employer', 'esic_employer', 'gmc', 'retention_bonus',
                                    'leave_encashment', 'gratuity', 'total_ctc'
                                ] as $comp): ?>
                                    <td>
                                        <div class="actual-value">Actual: <?= number_format((float) $$comp, 2) ?></div>
                                        <input type="number" step="0.01"
                                            name="calculated_<?= $comp ?>[<?= $id ?>]"
                                            class="form-control calculated_<?= $comp ?> calculated-field <?= in_array($comp, $earning_fields, true) ? 'earning_field' : '' ?>"
                                            value="<?= ${"calculated_" . $comp} ?>">
                                    </td>
                                <?php endforeach; ?>

                                <td>
                                    <div class="actual-value">Actual: <?= number_format((float) $epf_employee, 2) ?></div>
                                    <input type="number" step="0.01"
                                        name="calculated_epf_employee[<?= $id ?>]"
                                        class="form-control calculated_epf_employee calculated-field deduction_field"
                                        value="<?= $calculated_epf_employee ?>">
                                </td>
                                <td>
                                    <div class="actual-value">Actual: <?= number_format((float) $esic_employee, 2) ?></div>
                                    <input type="number" step="0.01"
                                        name="calculated_esic_employee[<?= $id ?>]"
                                        class="form-control calculated_esic_employee calculated-field deduction_field"
                                        value="<?= $calculated_esic_employee ?>">
                                </td>
                                <td>
                                    <div class="actual-value">Actual: <?= number_format((float) $professional_tax, 2) ?></div>
                                    <input type="text" class="form-control professional_tax" value="<?= $professional_tax ?>" readonly>
                                </td>
                                <td>
                                    <div class="actual-value actual-value-placeholder">Placeholder</div>
                                    <input type="number" step="0.01"
                                        name="advance[<?= $id ?>]"
                                        class="form-control advance_input"
                                        data-id="<?= $id ?>"
                                        value="<?= $advance ?>">
                                </td>
                                <?php foreach (['income_tax', 'insurance_premium', 'other_deductions', 'total_deductions', 'net_salary'] as $comp): ?>
                                    <td>
                                        <div class="actual-value">Actual: <?= number_format((float) $$comp, 2) ?></div>
                                        <input type="number" step="0.01"
                                            name="calculated_<?= $comp ?>[<?= $id ?>]"
                                            class="form-control calculated_<?= $comp ?> calculated-field <?= in_array($comp, $deduction_fields, true) ? 'deduction_field' : '' ?>"
                                            value="<?= ${"calculated_" . $comp} ?>">
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        </div>

        <div class="edit-payroll-actionbar mt-3">
            <button type="submit" class="btn edit-payroll-save-btn">Update Payroll Records</button>
        </div>
    </form>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const rows = document.querySelectorAll("tbody tr");
    const excludedFromCalc = ["professional_tax", "epf_employer", "epf_employee"];

    rows.forEach(row => {
        const totalDays = parseFloat(row.dataset.workingDays || "0") || <?= $total_working_days ?>;
        const inputs = {};
        const presentInput = row.querySelector(".present");
        const absentInput = row.querySelector(".absent");
        const leaveInput = row.querySelector(".leave");
        const compDaysInput = row.querySelector(".comp_days");
        const advanceInput = row.querySelector(".advance_input");

        row.querySelectorAll("input").forEach(input => {
            const classes = input.className.trim().split(/\s+/);
            classes.forEach(cls => {
                if (!inputs[cls]) {
                    inputs[cls] = input;
                }
            });
        });

        function recalculateAllByAttendance() {
            const present = parseFloat(presentInput.value) || 0;
            const approvedLeaveDays = parseFloat(inputs["leave_approved_days"]?.value) || 0;
            const payableDays = present + approvedLeaveDays;
            if (inputs["payable_days"]) {
                inputs["payable_days"].value = payableDays.toFixed(2);
            }
            const compDays = present - totalDays;
            compDaysInput.value = compDays > 0 ? compDays.toFixed(0) : 0;

            for (const key in inputs) {
                if (key.startsWith("per_day_") || key.startsWith("calculated_")) continue;
                if (excludedFromCalc.includes(key)) continue;

                const original = parseFloat(inputs[key]?.value) || 0;
                const perDay = totalDays > 0 ? original / totalDays : 0;
                let calculated = payableDays * perDay;

                if (calculated > original) {
                    calculated = original;
                }

                const perDayField = inputs["per_day_" + key];
                const calculatedField = inputs["calculated_" + key];

                if (perDayField) perDayField.value = perDay.toFixed(2);
                if (calculatedField) calculatedField.value = calculated.toFixed(2);
            }

            updateGrossAndNet();
        }

        function recalculateEpfFields(useManualGross = false) {
            const grossField = inputs["calculated_gross_salary"];
            let gross = 0;

            row.querySelectorAll(".earning_field").forEach(input => {
                gross += parseFloat(input.value) || 0;
            });

            if (useManualGross && grossField) {
                gross = parseFloat(grossField.value) || 0;
            }

            const includeEPF = parseInt(inputs["include_epf"]?.value || "0", 10) === 1;
            const includePFCeiling = parseInt(inputs["include_pf_ceiling"]?.value || "0", 10) === 1;
            const basic = parseFloat(inputs["calculated_basic"]?.value) || 0;
            const hra = parseFloat(inputs["calculated_hra"]?.value) || 0;
            const performanceBonus = parseFloat(inputs["calculated_performance_bonus"]?.value) || 0;
            const washingAllowance = parseFloat(inputs["calculated_washing_allowance"]?.value) || 0;
            const pfCeilingBase = Math.max(0, gross - hra - performanceBonus - washingAllowance);
            const epfEmployeeBase = includePFCeiling ? Math.min(pfCeilingBase, 15000) : basic;
            const epfEmployer = includeEPF ? basic * 0.13 : 0;
            const epfEmployee = includeEPF ? epfEmployeeBase * 0.12 : 0;

            if (inputs["calculated_epf_employer"]) {
                inputs["calculated_epf_employer"].value = epfEmployer.toFixed(2);
            }

            if (inputs["calculated_epf_employee"]) {
                inputs["calculated_epf_employee"].value = epfEmployee.toFixed(2);
            }

            return gross;
        }

        function updateGrossAndNet(useManualGross = false) {
            let gross = 0;
            let deductions = 0;
            const grossField = inputs["calculated_gross_salary"];

            row.querySelectorAll(".earning_field").forEach(input => {
                gross += parseFloat(input.value) || 0;
            });

            if (useManualGross && grossField) {
                gross = parseFloat(grossField.value) || 0;
            }

            gross = recalculateEpfFields(useManualGross);

            let pt = 0;
            const includePT = parseInt(inputs["include_pt"]?.value || "0", 10) === 1;

            if (includePT) {
                if (gross <= 13305) {
                    pt = 0;
                } else if (gross > 13305 && gross < 25000) {
                    pt = 125;
                } else {
                    pt = 200;
                }
            }

            row.querySelectorAll(".professional_tax").forEach(field => {
                field.value = pt;
            });

            row.querySelectorAll(".deduction_field").forEach(input => {
                deductions += parseFloat(input.value) || 0;
            });

            const advance = parseFloat(advanceInput?.value) || 0;
            deductions += pt + advance;

            const net = gross - deductions;

            if (grossField && !useManualGross) {
                grossField.value = gross.toFixed(2);
            }

            if (inputs["calculated_total_deductions"]) {
                inputs["calculated_total_deductions"].value = deductions.toFixed(2);
            }

            if (inputs["calculated_net_salary"]) {
                inputs["calculated_net_salary"].value = net.toFixed(2);
            }
        }

        presentInput.addEventListener("input", recalculateAllByAttendance);
        absentInput.addEventListener("input", recalculateAllByAttendance);
        leaveInput.addEventListener("input", recalculateAllByAttendance);

        if (advanceInput) {
            advanceInput.addEventListener("input", updateGrossAndNet);
        }

        row.querySelectorAll(".earning_field").forEach(input => {
            input.addEventListener("input", updateGrossAndNet);
        });

        if (inputs["calculated_gross_salary"]) {
            inputs["calculated_gross_salary"].addEventListener("input", function () {
                updateGrossAndNet(true);
            });
        }

        ["calculated_basic", "calculated_hra", "calculated_performance_bonus", "calculated_washing_allowance"].forEach(key => {
            if (inputs[key]) {
                inputs[key].addEventListener("input", function () {
                    recalculateEpfFields(false);
                    updateGrossAndNet();
                });
            }
        });

        row.querySelectorAll(".deduction_field").forEach(input => {
            input.addEventListener("input", updateGrossAndNet);
        });
    });
});
</script>

<?php include("footer.php"); ?>
