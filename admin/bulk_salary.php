<?php
include("header2.php");
require_once 'get_attendance_stats3.php';

$bulkSalaryImportSessionKey = 'bulk_salary_xls_import';
$bulkSalaryFlashKey = 'bulk_salary_xls_flash';
$bulkSalaryFlash = $_SESSION[$bulkSalaryFlashKey] ?? null;
unset($_SESSION[$bulkSalaryFlashKey]);
// Get filter inputs
$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$selected_month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$selected_office = isset($_GET['office']) ? trim(urldecode($_GET['office'])) : '';
$selected_employee = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : 0;

// Fetch offices
$offices_query = $conn->query("SELECT office_name, state_name FROM offices ORDER BY office_name ASC");
$offices = $offices_query->fetch_all(MYSQLI_ASSOC);
// Get holidays
$holiday_query = $conn->prepare("SELECT start_date FROM events WHERE YEAR(start_date) = ? AND MONTH(start_date) = ?");
$holiday_query->bind_param("ii", $selected_year, $selected_month);
$holiday_query->execute();
$holidays = $holiday_query->get_result()->fetch_all(MYSQLI_ASSOC);
$holiday_dates = array_column($holidays, 'start_date');
// Calculate working days
$total_days = cal_days_in_month(CAL_GREGORIAN, $selected_month, $selected_year);
$total_working_days = 0;
for ($d = 1; $d <= $total_days; $d++) {
    $date = sprintf('%04d-%02d-%02d', $selected_year, $selected_month, $d);
    $day_of_week = date('N', strtotime($date));
    if ($day_of_week < 7 && !in_array($date, $holiday_dates)) {
        $total_working_days++;
    }
}
// ✅ Build dynamic query based on filters
$employee_query = "SELECT id, employee_id, name, office FROM employees WHERE  status = 'Active'  AND  1=1";
$params = [];
$types = "";
if (!empty($selected_office)) {
    $employee_query .= " AND office = ?";
    $params[] = $selected_office;
    $types .= "s";
}
if (!empty($selected_employee)) {
    $employee_query .= " AND id = ?";
    $params[] = $selected_employee;
    $types .= "i";
}
// ✅ Prepare and execute query
$emp_stmt = $conn->prepare($employee_query);
if (!empty($params)) {
    $emp_stmt->bind_param($types, ...$params);
}
$emp_stmt->execute();
$employees = $emp_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$emp_stmt->close();

$bulkSalaryImportedRows = [];
if (!empty($_SESSION[$bulkSalaryImportSessionKey])) {
    $importPayload = $_SESSION[$bulkSalaryImportSessionKey];
    $matchesCurrentFilter =
        (int) ($importPayload['year'] ?? 0) === $selected_year &&
        (int) ($importPayload['month'] ?? 0) === $selected_month &&
        (string) ($importPayload['office'] ?? '') === $selected_office &&
        (int) ($importPayload['employee_id'] ?? 0) === $selected_employee;

    if ($matchesCurrentFilter) {
        $bulkSalaryImportedRows = is_array($importPayload['rows'] ?? null) ? $importPayload['rows'] : [];
        unset($_SESSION[$bulkSalaryImportSessionKey]);
    }
}
?>

<style>
.bulk-salary-page {
    background:
        radial-gradient(circle at top right, rgba(15, 23, 42, 0.05), transparent 24%),
        linear-gradient(180deg, #f6f7f9 0%, #f2f4f7 100%);
}

.bulk-salary-title {
    margin: 0 0 1rem;
    color: #111827;
    font-size: 1.55rem;
    font-weight: 800;
    letter-spacing: -0.02em;
}

.bulk-salary-note,
.bulk-salary-filter-card,
.bulk-salary-table-card {
    border: 1px solid rgba(107, 114, 128, 0.14);
    border-radius: 24px;
    background: rgba(255, 255, 255, 0.96);
    box-shadow: 0 18px 42px rgba(15, 23, 42, 0.07);
}

.bulk-salary-note {
    margin-bottom: 1rem;
    padding: 0.95rem 1.1rem;
    background: #eef6ff;
    border-color: #d6e6fb;
    color: #36506b;
    font-size: 0.82rem;
    font-weight: 600;
}

.bulk-salary-filter-card {
    margin-bottom: 1.2rem;
    padding: 1.05rem;
    background: linear-gradient(180deg, #fafbfc 0%, #f7f9fb 100%);
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.9), 0 14px 32px rgba(15, 23, 42, 0.05);
}

.bulk-salary-page label {
    display: block;
    margin-bottom: 0.45rem;
    color: #6b7280;
    font-size: 0.76rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
}

.bulk-salary-page .form-control,
.bulk-salary-page select.form-control {
    min-height: 44px;
    border: 1px solid #d8dee7;
    border-radius: 14px;
    background: #ffffff;
    box-shadow: none;
    color: #1f2937;
    padding: 0.65rem 0.9rem;
}

.bulk-salary-page .form-control:focus,
.bulk-salary-page select.form-control:focus {
    border-color: #16324f;
    box-shadow: 0 0 0 0.18rem rgba(22, 50, 79, 0.12);
}

.bulk-salary-filter-btn,
.bulk-salary-save-btn {
    min-height: 44px;
    border-radius: 14px;
    font-size: 0.82rem;
    font-weight: 700;
    box-shadow: none;
}

.bulk-salary-filter-btn {
    background: linear-gradient(135deg, #171717 0%, #2f2f2f 100%) !important;
    border: 1px solid #171717 !important;
    color: #ffffff !important;
}

.bulk-salary-filter-btn:hover {
    background: linear-gradient(135deg, #111111 0%, #252525 100%) !important;
    color: #ffffff !important;
}

.bulk-salary-save-btn {
    background: #16324f !important;
    border: 1px solid #16324f !important;
    color: #ffffff !important;
    padding: 0.7rem 1.1rem;
}

.bulk-salary-save-btn:hover {
    background: #10263c !important;
    border-color: #10263c !important;
    color: #ffffff !important;
}

.bulk-salary-sheet-btn {
    min-height: 44px;
    border-radius: 14px;
    font-size: 0.82rem;
    font-weight: 700;
    box-shadow: none;
    padding: 0.7rem 1rem;
    border: 1px solid #0f172a !important;
    background: linear-gradient(135deg, #0f172a 0%, #16324f 100%) !important;
    color: #ffffff !important;
}

.bulk-salary-sheet-btn:hover {
    border-color: #0b1220 !important;
    background: linear-gradient(135deg, #0b1220 0%, #122b44 100%) !important;
    color: #ffffff !important;
}

.bulk-salary-filter-actions {
    display: flex;
    justify-content: flex-end;
    width: 100%;
}

.bulk-salary-filter-row {
    row-gap: 0.85rem;
}

@media (min-width: 1200px) {
    .bulk-salary-filter-office,
    .bulk-salary-filter-employee {
        width: 24%;
    }

    .bulk-salary-filter-year,
    .bulk-salary-filter-month {
        width: 14%;
    }

    .bulk-salary-filter-submit,
    .bulk-salary-filter-sheet {
        width: 12%;
    }

    .bulk-salary-filter-row > [class*="bulk-salary-filter-"] {
        flex: 0 0 auto;
    }

    .bulk-salary-filter-row .bulk-salary-filter-btn,
    .bulk-salary-filter-row .bulk-salary-sheet-btn {
        min-width: 0;
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }
}

.bulk-salary-modal .modal-content {
    border: 1px solid rgba(107, 114, 128, 0.14);
    border-radius: 26px;
    box-shadow: 0 18px 42px rgba(15, 23, 42, 0.14);
}

.bulk-salary-modal .modal-header {
    padding: 1.2rem 1.3rem 0.8rem;
    border-bottom: 0;
}

.bulk-salary-modal .modal-title {
    color: #111827;
    font-size: 1.7rem;
    font-weight: 800;
    letter-spacing: -0.02em;
    text-transform: none;
}

.bulk-salary-modal .modal-body {
    padding: 0 1rem 1rem;
}

.bulk-salary-modal-card {
    border: 1px solid #e2e8f0;
    border-radius: 22px;
    background: linear-gradient(180deg, #fcfdff 0%, #f7f9fc 100%);
    box-shadow: 0 14px 34px rgba(15, 23, 42, 0.06);
    padding: 1rem 1.1rem;
}

.bulk-salary-modal-card + .bulk-salary-modal-card {
    margin-top: 1rem;
}

.bulk-salary-modal-label {
    margin-bottom: 1rem;
    color: #7b8797;
    font-size: 0.74rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.bulk-salary-modal-upload-label {
    display: block;
    margin-bottom: 0.75rem;
    color: #374151;
    font-size: 1rem;
    font-weight: 700;
    text-transform: none;
    letter-spacing: normal;
}

.bulk-salary-modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    margin-top: 1rem;
}

.bulk-salary-modal-close-btn {
    min-width: 84px;
    min-height: 40px;
    border: 1px solid #0f172a !important;
    border-radius: 10px;
    background: #0f172a !important;
    color: #ffffff !important;
    font-weight: 700;
}

.bulk-salary-modal-upload-btn {
    min-width: 88px;
    min-height: 40px;
    border-radius: 10px;
    background: #16324f !important;
    border: 1px solid #16324f !important;
    color: #ffffff !important;
    font-weight: 700;
}

.bulk-salary-modal-alert {
    margin-bottom: 1rem;
    border-radius: 16px;
    border: 1px solid #d8dee7;
    font-weight: 600;
}

.bulk-salary-table-card {
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
    z-index: 11;
    min-width: 46px;
    width: 46px;
    text-align: center;
}

.salary-table thead th:nth-child(2),
.salary-table tbody td:nth-child(2) {
    position: sticky;
    left: 46px;
    z-index: 10;
    min-width: 220px;
}

.salary-table tbody td:nth-child(1),
.salary-table tbody td:nth-child(2) {
    background: #ffffff;
}

.salary-table thead th:nth-child(1),
.salary-table thead th:nth-child(2) {
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

.salary-table thead th:nth-child(n+4),
.salary-table tbody td:nth-child(n+4) {
    min-width: 118px;
    width: 118px;
    max-width: 118px;
    padding-left: 6px;
    padding-right: 6px;
}

.salary-table thead th:nth-child(n+8) {
    white-space: normal;
    line-height: 1.2;
    word-break: break-word;
}

.salary-table tbody tr:hover td {
    background-color: #fbfcfe;
}

.salary-table tbody tr:hover td:nth-child(1),
.salary-table tbody tr:hover td:nth-child(2) {
    background-color: #fbfcfe;
}

.salary-table a {
    color: #16324f;
    font-weight: 700;
    text-decoration: none;
}

.salary-table a:hover {
    color: #0f2438;
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

.bulk-salary-actionbar {
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

    .bulk-salary-filter-card .row {
        --bs-gutter-x: 0.85rem;
        --bs-gutter-y: 0.85rem;
    }
}

@media (max-width: 767.98px) {
    .bulk-salary-note,
    .bulk-salary-filter-card,
    .bulk-salary-table-card {
        border-radius: 20px;
    }

    .bulk-salary-filter-card {
        padding: 0.9rem;
    }
}
</style>

  <div class="container-fluid container-fluid-main bulk-salary-page py-4">
  <h5 class="bulk-salary-title">Employee Salary Summary (<?= $selected_year ?> - <?= date('F', mktime(0, 0, 0, $selected_month, 10)) ?>)</h5>
    <div class="bulk-salary-note">
        W/D - Working Day, P/D - Present Day, A/D - Absent Day, L/D - Leave Day, C/D - Compoff Day
    </div>
    <div class="bulk-salary-filter-card">
    <form method="GET" class="row g-3 mb-4 mb-md-0 align-items-end bulk-salary-filter-row">
        <div class="col-md-6 col-xl bulk-salary-filter-office">
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
        <div class="col-md-6 col-xl bulk-salary-filter-employee">
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
        <div class="col-sm-6 col-md-3 col-xl bulk-salary-filter-year">
            <label>Year</label>
            <select name="year" class="form-control">
                <?php for ($y = date('Y') - 5; $y <= date('Y'); $y++): ?>
                    <option value="<?= $y ?>" <?= $selected_year == $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-sm-6 col-md-3 col-xl bulk-salary-filter-month">
            <label>Month</label>
            <select name="month" class="form-control">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= $selected_month == $m ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-sm-6 col-md-3 col-xl d-flex align-items-end bulk-salary-filter-submit">
            <button type="submit" class="btn bulk-salary-filter-btn w-100">Filter</button>
        </div>
        <div class="col-sm-6 col-md-3 col-xl d-flex align-items-end bulk-salary-filter-sheet">
            <div class="bulk-salary-filter-actions">
                <button type="button" class="btn bulk-salary-sheet-btn w-100" data-bs-toggle="modal" data-bs-target="#bulkSalarySheetModal">
                    <i class="bi bi-file-earmark-excel-fill"></i> Payroll XLS
                </button>
            </div>
        </div>
    </form>
    </div>

    <div class="modal fade bulk-salary-modal" id="bulkSalarySheetModal" tabindex="-1" aria-labelledby="bulkSalarySheetModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bulkSalarySheetModalLabel">Bulk Payroll Sheet</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if (!empty($bulkSalaryFlash['message'])): ?>
                        <div class="alert bulk-salary-modal-alert alert-<?= htmlspecialchars($bulkSalaryFlash['type'] ?? 'info') ?>">
                            <?= htmlspecialchars($bulkSalaryFlash['message']) ?>
                        </div>
                    <?php endif; ?>

                    <div class="bulk-salary-modal-card">
                        <div class="bulk-salary-modal-label">Download Payroll</div>
                        <a href="download_bulk_salary_xls?<?= http_build_query(['year' => $selected_year, 'month' => $selected_month, 'office' => $selected_office, 'employee_id' => $selected_employee]) ?>" class="btn bulk-salary-sheet-btn">
                            Download XLS Format
                        </a>
                    </div>

                    <div class="bulk-salary-modal-card">
                        <div class="bulk-salary-modal-label">Upload File</div>
                        <form method="POST" action="upload_bulk_salary_xls" enctype="multipart/form-data">
                            <input type="hidden" name="year" value="<?= $selected_year ?>">
                            <input type="hidden" name="month" value="<?= $selected_month ?>">
                            <input type="hidden" name="office" value="<?= htmlspecialchars($selected_office) ?>">
                            <input type="hidden" name="employee_id" value="<?= $selected_employee ?>">
                            <label class="bulk-salary-modal-upload-label" for="bulk_salary_xls">Upload XLS/XLSX File:</label>
                            <input type="file" id="bulk_salary_xls" name="bulk_salary_xls" class="form-control" accept=".xls,.xlsx,.xml" required>
                            <div class="bulk-salary-modal-actions">
                                <button type="button" class="btn bulk-salary-modal-close-btn" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn bulk-salary-modal-upload-btn">Upload</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <form method="POST" action="save_salary_summary">
    <input type="hidden" name="year" value="<?= $selected_year ?>">
    <input type="hidden" name="month" value="<?= $selected_month ?>">
    <input type="hidden" name="working_days" value="<?= $total_working_days ?>">

    <div class="bulk-salary-table-card">
    <div class="scroll-wrapper">
        <div class="table-scroll-container">
            <table class="table align-items-center mb-0 salary-table">
                <thead>
                     <tr>
    <th>
        <input type="checkbox" id="select_all">
    </th>
    <th>Name</th>
    <th>W/D</th>
    <th>P/D</th>
    <th>A/D</th>
    <th>L/D</th>
    <th>C/D</th>

    <!-- Earnings -->
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

    <!-- Employer Contributions -->
    <th>EPF (Employer)</th>
    <th>ESIC (Employer)</th>
    <th>GMC</th>
    <th>Retention Bonus</th>
    <th>Leave Encashment</th>
    <th>Gratuity</th>
    <th>Total CTC</th>

    <!-- Deductions -->
    <th>EPF (Employee)</th>
    <th>ESIC (Employee)</th>
    <th>Professional Tax</th>
    <th>Advance</th>
    <th>Income Tax</th>
    <th>Insurance Premium</th>
    <th>Other Deductions</th>
    <th>Total Deductions</th>
   
    <!-- Net -->
    <th>Net Salary</th>
</tr>
                </thead>
                <tbody>
                    <?php
                    function perDayCalc($amount, $days) {
                        return $days > 0 ? round($amount / $days, 2) : 0;
                    }

                    foreach ($employees as $emp):
                        $id = $emp['id'];
                        $stats = get_attendance_stats($conn, $id, $selected_year, $selected_month, $total_working_days);

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

                        foreach ($salary_components as $component) {
                            $$component = $stats[$component] ?? 0;
                            ${"per_day_" . $component} = perDayCalc($$component, $total_working_days);
                            ${"calculated_" . $component} = round(${"per_day_" . $component} * ($stats['payable_days'] ?? $stats['present_days'] ?? $total_working_days), 2);
                        }

                        $include_epf = !empty($stats['include_epf']) ? 1 : 0;
                        $include_pf_ceiling = !empty($stats['include_pf_ceiling']) ? 1 : 0;
                        $include_pt = !empty($stats['include_pt']) ? 1 : 0;
                        $professional_tax = $include_pt ? ($stats['professional_tax'] ?? 0) : 0;
                        $advance = $stats['advance'] ?? 0;
                        $present = $stats['present_days'] ?? $total_working_days;
                        $payable_days = $stats['payable_days'] ?? $present;
                        $absent = $stats['absent_days'] ?? 0;
                        $leave = $stats['leave_days'] ?? 0;
                        //$total_leave = $stats['total_leave'] ?? 0;
                        $total_leave_assigned = $stats['total_leave'] ?? 0;
                        $leave_approved = $stats['leave_approved'] ?? 0;

                        // `total_leave` is already the employee's remaining leave balance.
                        $available_leave = max(0, (float) $total_leave_assigned);

                        $comp_days = max(0, (float) ($stats['comp_days'] ?? ($present - $total_working_days)));
                        $importedRow = $bulkSalaryImportedRows[(string) $emp['employee_id']] ?? null;
                        $hasImportedOverride = is_array($importedRow);

                        if ($hasImportedOverride) {
                            $present = isset($importedRow['present_days']) ? (float) $importedRow['present_days'] : $present;
                            $absent = isset($importedRow['absent_days']) ? (float) $importedRow['absent_days'] : $absent;
                            $leave = isset($importedRow['leave_days']) ? (float) $importedRow['leave_days'] : $leave;
                            $comp_days = isset($importedRow['comp_days']) ? (float) $importedRow['comp_days'] : $comp_days;
                            $advance = isset($importedRow['advance']) ? (float) $importedRow['advance'] : $advance;
                            $professional_tax = isset($importedRow['professional_tax']) ? (float) $importedRow['professional_tax'] : $professional_tax;

                            foreach ([
                                'basic', 'da', 'hra', 'conveyance', 'special_allowance', 'performance_bonus',
                                'medical_allowance', 'washing_allowance', 'canteen_allowance', 'other_allowances',
                                'gross_salary', 'epf_employer', 'esic_employer', 'gmc', 'retention_bonus',
                                'leave_encashment', 'gratuity', 'total_ctc', 'epf_employee', 'esic_employee',
                                'income_tax', 'insurance_premium', 'other_deductions', 'total_deductions', 'net_salary'
                            ] as $importField) {
                                if (array_key_exists($importField, $importedRow)) {
                                    ${"calculated_" . $importField} = (float) $importedRow[$importField];
                                }
                            }
                        }
                    ?>
                    <tr data-imported="<?= $hasImportedOverride ? '1' : '0' ?>">
                        <td>
                            <input type="checkbox"
                                name="selected_employees[]"
                                value="<?= $id ?>"
                                class="employee_checkbox"
                                <?= $hasImportedOverride ? 'checked' : '' ?>>
                        </td>
                        <td><a href="emp_profile?employee_id=<?= $emp['employee_id'] ?>"><?= $emp['name'] ?></a></td>
                        <td><?= $total_working_days ?></td>

                        <?php foreach ($salary_components as $comp): ?>
                            <input type="hidden" name="<?= $comp ?>[<?= $id ?>]" class="form-control <?= $comp ?>" value="<?= $$comp ?>">
                            <input type="hidden" name="per_day_<?= $comp ?>[<?= $id ?>]" class="form-control per_day_<?= $comp ?>" value="<?= ${"per_day_" . $comp} ?>">
                        <?php endforeach; ?>

                        <input type="hidden" class="include_epf" value="<?= $include_epf ?>">
                        <input type="hidden" class="include_pf_ceiling" value="<?= $include_pf_ceiling ?>">
                        <input type="hidden" class="leave_approved_days" value="<?= (float) $leave_approved ?>">
                        <input type="hidden" class="payable_days" value="<?= (float) $payable_days ?>">
                        <input type="hidden" name="professional_tax[<?= $id ?>]" class="form-control professional_tax" value="<?= $professional_tax ?>">
                        <input type="hidden" class="include_pt" value="<?= $include_pt ?>">

                        <!-- Attendance Inputs -->
                        <td><input type="number" name="present_days[<?= $id ?>]" class="form-control present" value="<?= $present ?>"></td>
                        <td><input type="number" name="absent_days[<?= $id ?>]" class="form-control absent" value="<?= $absent ?>"></td>
                        <td>
                            <div class="actual-value">Avl Leave: <?= number_format($available_leave, 0) ?></div>
                            <input type="number" name="leave_days[<?= $id ?>]" class="form-control leave" value="<?= $leave ?>">
                        </td>
                        <td><input type="number" name="comp_days[<?= $id ?>]" class="form-control comp_days" value="<?= number_format((float) $comp_days, 0, '.', '') ?>"></td>

                        <!-- Earnings -->
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
                                    class="form-control calculated_<?= $comp ?> calculated-field 
                                           <?= in_array($comp, $earning_fields) ? 'earning_field' : '' ?>"
                                    value="<?= ${"calculated_" . $comp} ?>">
                            </td>
                        <?php endforeach; ?>

                        <!-- Deductions -->
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
                                    class="form-control calculated_<?= $comp ?> calculated-field 
                                           <?= in_array($comp, $deduction_fields) ? 'deduction_field' : '' ?>"
                                    value="<?= ${"calculated_" . $comp} ?>">
                            </td>
                        <?php endforeach; ?>
                        
                        <input type="hidden" name="employee_ids[]" value="<?= $id ?>">
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    </div>

    <div class="bulk-salary-actionbar mt-3">
        <button type="submit" class="btn bulk-salary-save-btn">Save Salary Summary</button>
    </div>
</form>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const totalDays = <?= $total_working_days ?>;
    const rows = document.querySelectorAll("tbody tr");

    const excludedFromCalc = ['professional_tax', 'epf_employer', 'epf_employee'];

    rows.forEach(row => {
        const inputs = {};
        const hasImportedOverrides = row.dataset.imported === "1";

        // Attendance inputs
        const presentInput = row.querySelector(".present");
        const absentInput = row.querySelector(".absent");
        const leaveInput = row.querySelector(".leave");
        const compDaysInput = row.querySelector(".comp_days");
        const advanceInput = row.querySelector(".advance_input");

        const allInputs = row.querySelectorAll("input");

        // Group inputs by class name for easier access
        allInputs.forEach(input => {
            const classes = input.className.trim().split(/\s+/);
            classes.forEach(cls => {
                if (!inputs[cls]) {
                    inputs[cls] = input;
                }
            });
        });

        function recalculateAllByAttendance() {
            const present = parseFloat(presentInput.value) || 0;
            const absent = parseFloat(absentInput.value) || 0;
            const leave = parseFloat(leaveInput.value) || 0;
            const approvedLeaveDays = parseFloat(inputs['leave_approved_days']?.value) || 0;
            const payableDays = present + approvedLeaveDays;
            if (inputs['payable_days']) {
                inputs['payable_days'].value = payableDays.toFixed(2);
            }

            const compDays = present - totalDays;
            compDaysInput.value = compDays > 0 ? compDays.toFixed(0) : 0;

            for (const key in inputs) {
                if (key.startsWith('per_day_') || key.startsWith('calculated_')) continue;
                if (excludedFromCalc.includes(key)) continue;

                const original = parseFloat(inputs[key]?.value) || 0;
                const perDay = totalDays > 0 ? original / totalDays : 0;
                let calculated = payableDays * perDay;

                if (calculated > original) {
                    calculated = original;
                }

                const perDayField = inputs['per_day_' + key];
                const calculatedField = inputs['calculated_' + key];

                if (perDayField) perDayField.value = perDay.toFixed(2);
                if (calculatedField) calculatedField.value = calculated.toFixed(2);
            }

            updateGrossAndNet();
        }

        function recalculateEpfFields(useManualGross = false) {
            const grossField = inputs['calculated_gross_salary'];
            let gross = 0;

            row.querySelectorAll(".earning_field").forEach(input => {
                gross += parseFloat(input.value) || 0;
            });

            if (useManualGross && grossField) {
                gross = parseFloat(grossField.value) || 0;
            }

            gross = recalculateEpfFields(useManualGross);

            return gross;
        }

        function updateGrossAndNet(useManualGross = false) {
            let gross = 0;
            let deductions = 0;
            const grossField = inputs['calculated_gross_salary'];

            // Sum up all earnings
            row.querySelectorAll(".earning_field").forEach(input => {
                gross += parseFloat(input.value) || 0;
            });

            if (useManualGross && grossField) {
                gross = parseFloat(grossField.value) || 0;
            }

            // ✅ Calculate PT based on gross salary
            const includeEPF = parseInt(inputs['include_epf']?.value || "0", 10) === 1;
            const includePFCeiling = parseInt(inputs['include_pf_ceiling']?.value || "0", 10) === 1;
            const basic = parseFloat(inputs['calculated_basic']?.value) || 0;
            const hra = parseFloat(inputs['calculated_hra']?.value) || 0;
            const performanceBonus = parseFloat(inputs['calculated_performance_bonus']?.value) || 0;
            const washingAllowance = parseFloat(inputs['calculated_washing_allowance']?.value) || 0;
            const pfCeilingBase = Math.max(0, gross - hra - performanceBonus - washingAllowance);
            const epfEmployeeBase = includePFCeiling ? Math.min(pfCeilingBase, 15000) : basic;
            const epfEmployer = includeEPF ? basic * 0.13 : 0;
            const epfEmployee = includeEPF ? epfEmployeeBase * 0.12 : 0;

            if (inputs['calculated_epf_employer']) {
                inputs['calculated_epf_employer'].value = epfEmployer.toFixed(2);
            }

            if (inputs['calculated_epf_employee']) {
                inputs['calculated_epf_employee'].value = epfEmployee.toFixed(2);
            }

            let pt = 0;
            const includePT = parseInt(inputs['include_pt']?.value || "0", 10) === 1;

            if (includePT) {
                if (gross <= 13305) {
                    pt = 0;
                } else if (gross > 13305 && gross < 25000) {
                    pt = 125;
                } else {
                    pt = 200;
                }
            }

            // ✅ Update PT field (hidden + visible)
            const ptFields = row.querySelectorAll(".professional_tax");
            ptFields.forEach(field => {
                field.value = pt;
            });

            // Sum up all deductions
            row.querySelectorAll(".deduction_field").forEach(input => {
                deductions += parseFloat(input.value) || 0;
            });

            const advance = parseFloat(advanceInput?.value) || 0;
            
            // Include PT and Advance under total deductions
            deductions += pt + advance;

            const net = gross - deductions;

            // Update calculated gross, deductions, and net fields
            if (grossField && !useManualGross) {
                grossField.value = gross.toFixed(2);
            }

            if (inputs['calculated_total_deductions']) {
                inputs['calculated_total_deductions'].value = deductions.toFixed(2);
            }

            if (inputs['calculated_net_salary']) {
                inputs['calculated_net_salary'].value = net.toFixed(2);
            }
        }

        if (!hasImportedOverrides) {
            recalculateAllByAttendance();
        }

        // Recalculate when attendance changes
        presentInput.addEventListener("input", recalculateAllByAttendance);
        absentInput.addEventListener("input", recalculateAllByAttendance);
        leaveInput.addEventListener("input", recalculateAllByAttendance);

        // Recalculate when advance is changed
        if (advanceInput) {
            advanceInput.addEventListener("input", updateGrossAndNet);
        }

        // Watch earning fields for manual edit
        row.querySelectorAll(".earning_field").forEach(input => {
            input.addEventListener("input", updateGrossAndNet);
        });

        if (inputs['calculated_gross_salary']) {
            inputs['calculated_gross_salary'].addEventListener("input", function () {
                updateGrossAndNet(true);
            });
        }

        ['calculated_basic', 'calculated_hra', 'calculated_performance_bonus', 'calculated_washing_allowance'].forEach(key => {
            if (inputs[key]) {
                inputs[key].addEventListener("input", function () {
                    recalculateEpfFields(false);
                    updateGrossAndNet();
                });
            }
        });

        // Watch deduction fields for manual edit
        row.querySelectorAll(".deduction_field").forEach(input => {
            input.addEventListener("input", updateGrossAndNet);
        });
    });
});
</script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    <?php if (!empty($bulkSalaryFlash['message'])): ?>
    const bulkSalarySheetModalElement = document.getElementById("bulkSalarySheetModal");
    if (bulkSalarySheetModalElement && window.bootstrap && typeof window.bootstrap.Modal === "function") {
        window.bootstrap.Modal.getOrCreateInstance(bulkSalarySheetModalElement).show();
    }
    <?php endif; ?>
});
</script>
<script>
document.addEventListener("DOMContentLoaded", function () {

    const selectAll = document.getElementById("select_all");

    document.querySelectorAll(".employee_checkbox").forEach(cb => {
        toggleRow(cb);

        cb.addEventListener("change", function () {
            toggleRow(this);
        });
    });

    if (selectAll) {
        selectAll.addEventListener("change", function () {
            document.querySelectorAll(".employee_checkbox").forEach(cb => {
                cb.checked = this.checked;
                toggleRow(cb);
            });
        });
    }

    function toggleRow(checkbox) {
        const row = checkbox.closest("tr");

        row.querySelectorAll("input:not(.employee_checkbox)").forEach(input => {
            input.disabled = !checkbox.checked;
        });
    }
});
</script>

<?php include("footer.php"); ?>
