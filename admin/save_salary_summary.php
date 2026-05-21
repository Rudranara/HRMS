<?php
require 'header.php'; // DB connection
require_once '../includes/advance_salary_request_helper.php';

if (!function_exists('postedPayrollAmount')) {
    function postedPayrollAmount(array $post, string $calculatedKey, string $originalKey, $empId): float
    {
        if (isset($post[$calculatedKey][$empId]) && $post[$calculatedKey][$empId] !== '') {
            return round((float) $post[$calculatedKey][$empId], 2);
        }

        if (isset($post[$originalKey][$empId]) && $post[$originalKey][$empId] !== '') {
            return round((float) $post[$originalKey][$empId], 2);
        }

        return 0.0;
    }
}

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ensureSalaryPayrollColumns($conn);

    if (empty($_POST['selected_employees'])) {
        die("No employees selected.");
    }
    
    $year = $_POST['year'];
    $month = $_POST['month'];
    $total_working_days = $_POST['working_days'];
   // $employee_ids = $_POST['employee_ids'];
    if (empty($_POST['selected_employees'])) {
        die("No employees selected.");
    }

    $employee_ids = $_POST['selected_employees'];
    $payrollAlreadyExists = false;
    $employeesWithPayroll = [];
    foreach ($employee_ids as $emp_id) {
        // Check if payroll already exists
        $stmt = $conn->prepare("SELECT id FROM salary WHERE employee_id = ? AND year = ? AND month = ?");
        $stmt->bind_param("iii", $emp_id, $year, $month);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $empStmt = $conn->prepare("SELECT name FROM employees WHERE id = ?");
            $empStmt->bind_param("i", $emp_id);
            $empStmt->execute();
            $empName = $empStmt->get_result()->fetch_assoc()['name'];
            $employeesWithPayroll[] = $empName;
            $payrollAlreadyExists = true;
            continue;
        }
        // Attendance basics
        $present_days = (int) ($_POST['present_days'][$emp_id] ?? 0);
        $absent_days = (int) ($_POST['absent_days'][$emp_id] ?? 0);
        $leave_days = (int) ($_POST['leave_days'][$emp_id] ?? 0);
        $comp_days = (int) ($_POST['comp_days'][$emp_id] ?? 0);
        $working_days = (int) $total_working_days;
        $expected_present = $total_working_days - ($absent_days + $leave_days);
        // Core salary inputs
        //$basic = $_POST['calculated_basic'][$emp_id] ?? 0;



        $original_basic = $_POST['basic'][$emp_id] ?? 0;
        $calculated_basic = $_POST['calculated_basic'][$emp_id] ?? 0;

        $basic = ($calculated_basic > $original_basic) 
            ? $original_basic 
            : $calculated_basic;

        $net_salary = (float) ($_POST['calculated_net_salary'][$emp_id] ?? 0);
        $per_day_salary = (float) ($_POST['per_day_basic'][$emp_id] ?? 0);
        $calculated_salary = (float) ($_POST['calculated_basic'][$emp_id] ?? 0);
        $per_day_net = (float) ($_POST['per_day_net_salary'][$emp_id] ?? 0);
        $calculated_net = (float) ($_POST['calculated_net_salary'][$emp_id] ?? 0);
        // Earnings & allowances
        $da = postedPayrollAmount($_POST, 'calculated_da', 'da', $emp_id);
        $hra = postedPayrollAmount($_POST, 'calculated_hra', 'hra', $emp_id);
        $conveyance = postedPayrollAmount($_POST, 'calculated_conveyance', 'conveyance', $emp_id);
        $special_allowance = postedPayrollAmount($_POST, 'calculated_special_allowance', 'special_allowance', $emp_id);
        $performance_bonus = postedPayrollAmount($_POST, 'calculated_performance_bonus', 'performance_bonus', $emp_id);
        $medical_allowance = postedPayrollAmount($_POST, 'calculated_medical_allowance', 'medical_allowance', $emp_id);
        $washing_allowance = postedPayrollAmount($_POST, 'calculated_washing_allowance', 'washing_allowance', $emp_id);
        $canteen_allowance = postedPayrollAmount($_POST, 'calculated_canteen_allowance', 'canteen_allowance', $emp_id);
        $other_allowances = postedPayrollAmount($_POST, 'calculated_other_allowances', 'other_allowances', $emp_id);

        $gross_salary = (float) ($_POST['calculated_gross_salary'][$emp_id] ?? 0);
        // Employer contributions
        $epf_employer = (float) ($_POST['calculated_epf_employer'][$emp_id] ?? 0);
        $esic_employer = (float) ($_POST['calculated_esic_employer'][$emp_id] ?? 0);
        $gmc_employer = (float) ($_POST['calculated_gmc'][$emp_id] ?? 0);
        $retention_bonus = (float) ($_POST['calculated_retention_bonus'][$emp_id] ?? 0);
        $leave_encashment = (float) ($_POST['calculated_leave_encashment'][$emp_id] ?? 0);
        $gratuity = (float) ($_POST['calculated_gratuity'][$emp_id] ?? 0);
        $total_retentions = (float) ($_POST['calculated_total_retentions'][$emp_id] ?? 0);
        $total_ctc = (float) ($_POST['calculated_total_ctc'][$emp_id] ?? 0);
        // Deductions
        $epf_employee = (float) ($_POST['calculated_epf_employee'][$emp_id] ?? 0);
        $esic_employee = (float) ($_POST['calculated_esic_employee'][$emp_id] ?? 0);
        $professional_tax = (float) ($_POST['professional_tax'][$emp_id] ?? 0);
        $income_tax = (float) ($_POST['calculated_income_tax'][$emp_id] ?? 0);
        $insurance_premium = (float) ($_POST['calculated_insurance_premium'][$emp_id] ?? 0);
        $advance = (float) ($_POST['advance'][$emp_id] ?? 0);

        $other_deductions = (float) ($_POST['calculated_other_deductions'][$emp_id] ?? 0);
        $total_deductions = (float) ($_POST['calculated_total_deductions'][$emp_id] ?? 0);
        // Other values
        $office = $_POST['office'][$emp_id] ?? '';
        $on_leave_days = (int) ($_POST['on_leave_days'][$emp_id] ?? 0);
        $late_days = (int) ($_POST['late_days'][$emp_id] ?? 0);
        $early_out_days = (int) ($_POST['early_out_days'][$emp_id] ?? 0);
        $total_working_hours = (float) ($_POST['total_working_hours'][$emp_id] ?? 0);
        $expected_working_hours = (float) ($_POST['expected_working_hours'][$emp_id] ?? 0);
        $hourly_salary = (float) ($_POST['hourly_salary'][$emp_id] ?? 0);
        $difference_type = $_POST['difference_type'][$emp_id] ?? '';
        $ot_or_time_lost_amount = (float) ($_POST['ot_or_time_lost_amount'][$emp_id] ?? 0);
        $leave_approved = (int) ($_POST['leave_approved'][$emp_id] ?? 0);
        $leave_pending = (int) ($_POST['leave_pending'][$emp_id] ?? 0);
        $leave_rejected = (int) ($_POST['leave_rejected'][$emp_id] ?? 0);
        // Insert query
        $query = "INSERT INTO salary (
            employee_id, year, month, office, basic, da, hra, conveyance, special_allowance, 
            performance_bonus, medical_allowance, washing_allowance, canteen_allowance, other_allowances, 
            gross_salary, epf_employer, esic_employer, gmc_employer, retention_bonus, gratuity, leave_encashment, 
            total_retentions, total_ctc, epf_employee, esic_employee, professional_tax, income_tax, 
            insurance_premium, advance, other_deductions, total_deductions, net_salary, total_working_days, 
            working_days, absent_days, leave_days, present_days, on_leave_days, comp_days,
            late_days, early_out_days, total_working_hours, expected_working_hours,
            per_day_salary, hourly_salary, calculated_salary, difference_type, ot_or_time_lost_amount,
            leave_approved, leave_pending, leave_rejected
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )";
        $stmt = $conn->prepare($query);
        $bindTypes = 'iiis' . str_repeat('d', 28) . str_repeat('i', 9) . str_repeat('d', 5) . 'sdiii';
        $stmt->bind_param(
            $bindTypes,
            $emp_id, $year, $month, $office, $basic, $da, $hra, $conveyance, $special_allowance,
            $performance_bonus, $medical_allowance, $washing_allowance, $canteen_allowance, $other_allowances,
            $gross_salary, $epf_employer, $esic_employer, $gmc_employer, $retention_bonus, $gratuity, $leave_encashment,
            $total_retentions, $total_ctc, $epf_employee, $esic_employee, $professional_tax, $income_tax,
            $insurance_premium, $advance, $other_deductions, $total_deductions, $net_salary,
            $total_working_days, $working_days, $absent_days, $leave_days, $present_days, $on_leave_days, $comp_days,
            $late_days, $early_out_days, $total_working_hours, $expected_working_hours,
            $per_day_salary, $hourly_salary, $calculated_salary, $difference_type, $ot_or_time_lost_amount,
            $leave_approved, $leave_pending, $leave_rejected
        );
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            markAdvanceSalaryRequestsApplied($conn, (int) $emp_id, (int) $year, (int) $month, (int) $stmt->insert_id);
        }
    }
    // Response
    if ($payrollAlreadyExists) {
        $employeesList = implode(", ", $employeesWithPayroll);
        echo "<div class='alert alert-danger'>Payroll already generated for the following employees: $employeesList.</div>
         <script>
            setTimeout(function() {
                  window.location.href = 'manage_salary';
            }, 2000);
        </script>";
    } else {
        echo "
        <div class='alert alert-success' style='position: fixed; top: 10px; right: 10px; z-index: 1000;'>
            Payroll generated successfully for selected employees.
        </div>
        <script>
            setTimeout(function() {
                window.location.href = 'manage_salary';
            }, 2000);
        </script>";
    }
} else {
    echo "Invalid request.";
}
?>
