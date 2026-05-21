<?php
session_start();
require 'db_connection.php';
require_once 'get_attendance_stats3.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

$selected_year = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');
$selected_month = isset($_GET['month']) ? (int) $_GET['month'] : (int) date('m');
$selected_office = isset($_GET['office']) ? trim(urldecode((string) $_GET['office'])) : '';
$selected_employee = isset($_GET['employee_id']) ? (int) $_GET['employee_id'] : 0;

$holiday_query = $conn->prepare("SELECT start_date FROM events WHERE YEAR(start_date) = ? AND MONTH(start_date) = ?");
$holiday_query->bind_param('ii', $selected_year, $selected_month);
$holiday_query->execute();
$holidays = $holiday_query->get_result()->fetch_all(MYSQLI_ASSOC);
$holiday_dates = array_column($holidays, 'start_date');

$total_days = cal_days_in_month(CAL_GREGORIAN, $selected_month, $selected_year);
$total_working_days = 0;
for ($day = 1; $day <= $total_days; $day++) {
    $date = sprintf('%04d-%02d-%02d', $selected_year, $selected_month, $day);
    $day_of_week = date('N', strtotime($date));
    if ($day_of_week < 7 && !in_array($date, $holiday_dates, true)) {
        $total_working_days++;
    }
}

$employee_query = "SELECT id, employee_id, name, office FROM employees WHERE status = 'Active' AND 1=1";
$params = [];
$types = '';

if ($selected_office !== '') {
    $employee_query .= ' AND office = ?';
    $params[] = $selected_office;
    $types .= 's';
}

if ($selected_employee > 0) {
    $employee_query .= ' AND id = ?';
    $params[] = $selected_employee;
    $types .= 'i';
}

$employee_query .= ' ORDER BY name ASC';
$employee_stmt = $conn->prepare($employee_query);
if (!empty($params)) {
    $employee_stmt->bind_param($types, ...$params);
}
$employee_stmt->execute();
$employees = $employee_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$employee_stmt->close();

function perDayCalc(float $amount, int $days): float
{
    return $days > 0 ? round($amount / $days, 2) : 0.0;
}

function xmlText(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

function xmlNumberCell($value): string
{
    $number = is_numeric($value) ? (float) $value : 0.0;
    return '<Cell><Data ss:Type="Number">' . rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.') . '</Data></Cell>';
}

function xmlStringCell(string $value): string
{
    return '<Cell><Data ss:Type="String">' . xmlText($value) . '</Data></Cell>';
}

$filename = sprintf('bulk_salary_%04d_%02d.xls', $selected_year, $selected_month);
$month_name = date('F', mktime(0, 0, 0, $selected_month, 1));

$columns = [
    'Employee ID',
    'Employee Name',
    'Office',
    'Year',
    'Month',
    'Working Days',
    'Present Days',
    'Absent Days',
    'Leave Days',
    'Comp Days',
    'Basic Salary',
    'DA',
    'HRA',
    'Conveyance',
    'Special Allowance',
    'Bonus Advance',
    'Medical Allowance',
    'Washing Allowance',
    'Canteen Allowance',
    'Other Allowances',
    'Gross Salary',
    'EPF Employer',
    'ESIC Employer',
    'GMC',
    'Retention Bonus',
    'Leave Encashment',
    'Gratuity',
    'Total CTC',
    'EPF Employee',
    'ESIC Employee',
    'Professional Tax',
    'Advance',
    'Income Tax',
    'Insurance Premium',
    'Other Deductions',
    'Total Deductions',
    'Net Salary',
];

header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo "<?mso-application progid=\"Excel.Sheet\"?>\n";
echo "<Workbook xmlns=\"urn:schemas-microsoft-com:office:spreadsheet\" xmlns:o=\"urn:schemas-microsoft-com:office:office\" xmlns:x=\"urn:schemas-microsoft-com:office:excel\" xmlns:ss=\"urn:schemas-microsoft-com:office:spreadsheet\" xmlns:html=\"http://www.w3.org/TR/REC-html40\">\n";
echo "    <Styles>\n";
echo "        <Style ss:ID=\"header\">\n";
echo "            <Font ss:Bold=\"1\"/>\n";
echo "            <Alignment ss:Horizontal=\"Center\" ss:Vertical=\"Center\" ss:WrapText=\"1\"/>\n";
echo "            <Interior ss:Color=\"#EAF2FB\" ss:Pattern=\"Solid\"/>\n";
echo "            <Borders>\n";
echo "                <Border ss:Position=\"Bottom\" ss:LineStyle=\"Continuous\" ss:Weight=\"1\"/>\n";
echo "                <Border ss:Position=\"Left\" ss:LineStyle=\"Continuous\" ss:Weight=\"1\"/>\n";
echo "                <Border ss:Position=\"Right\" ss:LineStyle=\"Continuous\" ss:Weight=\"1\"/>\n";
echo "                <Border ss:Position=\"Top\" ss:LineStyle=\"Continuous\" ss:Weight=\"1\"/>\n";
echo "            </Borders>\n";
echo "        </Style>\n";
echo "    </Styles>\n";
echo "    <Worksheet ss:Name=\"Bulk Salary\">\n";
echo "        <Table>\n";
echo "            <Row>\n";
echo '                <Cell><Data ss:Type="String">' . xmlText('Bulk Salary Sheet - ' . $month_name . ' ' . $selected_year) . "</Data></Cell>\n";
echo "            </Row>\n";
echo "            <Row>\n";
foreach ($columns as $column) {
    echo '                <Cell ss:StyleID="header"><Data ss:Type="String">' . xmlText($column) . "</Data></Cell>\n";
}
echo "            </Row>\n";

foreach ($employees as $employee) {
    $stats = get_attendance_stats($conn, (int) $employee['id'], $selected_year, $selected_month, $total_working_days);
    $present = (float) ($stats['present_days'] ?? $total_working_days);
    $absent = (float) ($stats['absent_days'] ?? 0);
    $leave = (float) ($stats['leave_days'] ?? 0);
    $payable_days = (float) ($stats['payable_days'] ?? $present);
    $comp_days = max(0, (float) ($stats['comp_days'] ?? ($present - $total_working_days)));

    $salary_components = [
        'basic', 'da', 'hra', 'conveyance', 'special_allowance', 'performance_bonus',
        'medical_allowance', 'washing_allowance', 'canteen_allowance', 'other_allowances',
        'gross_salary', 'epf_employer', 'esic_employer', 'gmc', 'retention_bonus', 'leave_encashment',
        'gratuity', 'total_ctc', 'epf_employee', 'esic_employee', 'income_tax', 'insurance_premium',
        'other_deductions', 'total_deductions', 'net_salary'
    ];

    $calculated = [];
    foreach ($salary_components as $component) {
        $value = (float) ($stats[$component] ?? 0);
        $calculated[$component] = round(perDayCalc($value, $total_working_days) * $payable_days, 2);
    }

    $professional_tax = !empty($stats['include_pt']) ? (float) ($stats['professional_tax'] ?? 0) : 0.0;
    $advance = (float) ($stats['advance'] ?? 0);
    echo "            <Row>\n";
    echo '                ' . xmlStringCell((string) $employee['employee_id']) . "\n";
    echo '                ' . xmlStringCell((string) $employee['name']) . "\n";
    echo '                ' . xmlStringCell((string) $employee['office']) . "\n";
    echo '                ' . xmlNumberCell($selected_year) . "\n";
    echo '                ' . xmlNumberCell($selected_month) . "\n";
    echo '                ' . xmlNumberCell($total_working_days) . "\n";
    echo '                ' . xmlNumberCell($present) . "\n";
    echo '                ' . xmlNumberCell($absent) . "\n";
    echo '                ' . xmlNumberCell($leave) . "\n";
    echo '                ' . xmlNumberCell($comp_days) . "\n";
    echo '                ' . xmlNumberCell($calculated['basic']) . "\n";
    echo '                ' . xmlNumberCell($calculated['da']) . "\n";
    echo '                ' . xmlNumberCell($calculated['hra']) . "\n";
    echo '                ' . xmlNumberCell($calculated['conveyance']) . "\n";
    echo '                ' . xmlNumberCell($calculated['special_allowance']) . "\n";
    echo '                ' . xmlNumberCell($calculated['performance_bonus']) . "\n";
    echo '                ' . xmlNumberCell($calculated['medical_allowance']) . "\n";
    echo '                ' . xmlNumberCell($calculated['washing_allowance']) . "\n";
    echo '                ' . xmlNumberCell($calculated['canteen_allowance']) . "\n";
    echo '                ' . xmlNumberCell($calculated['other_allowances']) . "\n";
    echo '                ' . xmlNumberCell($calculated['gross_salary']) . "\n";
    echo '                ' . xmlNumberCell($calculated['epf_employer']) . "\n";
    echo '                ' . xmlNumberCell($calculated['esic_employer']) . "\n";
    echo '                ' . xmlNumberCell($calculated['gmc']) . "\n";
    echo '                ' . xmlNumberCell($calculated['retention_bonus']) . "\n";
    echo '                ' . xmlNumberCell($calculated['leave_encashment']) . "\n";
    echo '                ' . xmlNumberCell($calculated['gratuity']) . "\n";
    echo '                ' . xmlNumberCell($calculated['total_ctc']) . "\n";
    echo '                ' . xmlNumberCell($calculated['epf_employee']) . "\n";
    echo '                ' . xmlNumberCell($calculated['esic_employee']) . "\n";
    echo '                ' . xmlNumberCell($professional_tax) . "\n";
    echo '                ' . xmlNumberCell($advance) . "\n";
    echo '                ' . xmlNumberCell($calculated['income_tax']) . "\n";
    echo '                ' . xmlNumberCell($calculated['insurance_premium']) . "\n";
    echo '                ' . xmlNumberCell($calculated['other_deductions']) . "\n";
    echo '                ' . xmlNumberCell($calculated['total_deductions']) . "\n";
    echo '                ' . xmlNumberCell($calculated['net_salary']) . "\n";
    echo "            </Row>\n";
}
echo "        </Table>\n";
echo "    </Worksheet>\n";
echo "</Workbook>\n";
