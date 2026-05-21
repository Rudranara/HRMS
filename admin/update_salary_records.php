<?php
require 'header.php'; // DB connection

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

    $employee_ids = $_POST['employee_ids'] ?? [];
    $year = isset($_POST['year']) ? (int) $_POST['year'] : 0;
    $month = isset($_POST['month']) ? (int) $_POST['month'] : 0;
    $payrollUpdated = [];
    $payrollNotFound = [];

    foreach ($employee_ids as $emp_id) {
        $emp_id = (int) $emp_id;
        $salary_record_id = isset($_POST['salary_record_ids'][$emp_id]) ? (int) $_POST['salary_record_ids'][$emp_id] : 0;

        if ($salary_record_id > 0) {
            $stmt = $conn->prepare("SELECT id FROM salary WHERE id = ? AND employee_id = ? AND year = ? AND month = ?");
            $stmt->bind_param("iiii", $salary_record_id, $emp_id, $year, $month);
        } else {
            $stmt = $conn->prepare("SELECT id FROM salary WHERE employee_id = ? AND year = ? AND month = ?");
            $stmt->bind_param("iii", $emp_id, $year, $month);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $payrollNotFound[] = $emp_id;
            continue;
        }
        $salary_row = $result->fetch_assoc();
        $target_salary_id = (int) $salary_row['id'];
        $stmt->close();

        $present_days = (int) ($_POST['present_days'][$emp_id] ?? 0);
        $absent_days = (int) ($_POST['absent_days'][$emp_id] ?? 0);
        $leave_days = (int) ($_POST['leave_days'][$emp_id] ?? 0);
        $comp_days = (int) ($_POST['comp_days'][$emp_id] ?? 0);

        $components = [
            'basic', 'da', 'hra', 'conveyance', 'special_allowance', 'performance_bonus',
            'medical_allowance', 'washing_allowance', 'canteen_allowance', 'other_allowances',
            'gross_salary', 'epf_employer', 'esic_employer', 'gmc', 'retention_bonus', 'leave_encashment',
            'gratuity', 'total_ctc', 'epf_employee', 'esic_employee', 'income_tax',
            'insurance_premium', 'other_deductions', 'total_deductions', 'net_salary'
        ];

        $updates = "";
        $params = [];
        $types = "";

        foreach ($components as $comp) {
            $calculated = $_POST["calculated_$comp"][$emp_id] ?? 0;
            $value = round((float) $calculated, 2);
            $column = $comp === 'gmc' ? 'gmc_employer' : $comp;

            $updates .= "$column = ?, ";
            $params[] = $value;
            $types .= "d";
        }

        $professional_tax = round((float) ($_POST['professional_tax'][$emp_id] ?? 0), 2);
        $advance = round((float) ($_POST['advance'][$emp_id] ?? 0), 2);
        $office = $_POST['office'][$emp_id] ?? '';
        $total_working_days = round((float) ($_POST['total_working_days'][$emp_id] ?? 0), 2);
        $working_days = round((float) ($_POST['working_days'][$emp_id] ?? 0), 2);
        $on_leave_days = (int) ($_POST['on_leave_days'][$emp_id] ?? 0);
        $late_days = (int) ($_POST['late_days'][$emp_id] ?? 0);
        $early_out_days = (int) ($_POST['early_out_days'][$emp_id] ?? 0);
        $total_working_hours = round((float) ($_POST['total_working_hours'][$emp_id] ?? 0), 2);
        $expected_working_hours = round((float) ($_POST['expected_working_hours'][$emp_id] ?? 0), 2);
        $per_day_salary = round((float) ($_POST['per_day_basic'][$emp_id] ?? 0), 2);
        $hourly_salary = round((float) ($_POST['hourly_salary'][$emp_id] ?? 0), 2);
        $calculated_salary = round((float) ($_POST['calculated_basic'][$emp_id] ?? 0), 2);
        $difference_type = $_POST['difference_type'][$emp_id] ?? '';
        $ot_or_time_lost_amount = round((float) ($_POST['ot_or_time_lost_amount'][$emp_id] ?? 0), 2);
        $leave_approved = (int) ($_POST['leave_approved'][$emp_id] ?? 0);
        $leave_pending = (int) ($_POST['leave_pending'][$emp_id] ?? 0);
        $leave_rejected = (int) ($_POST['leave_rejected'][$emp_id] ?? 0);
        $total_retentions = round((float) ($_POST['calculated_total_retentions'][$emp_id] ?? 0), 2);

        $updates .= "professional_tax = ?, advance = ?, office = ?, total_retentions = ?, total_working_days = ?, working_days = ?, present_days = ?, absent_days = ?, leave_days = ?, on_leave_days = ?, comp_days = ?, late_days = ?, early_out_days = ?, total_working_hours = ?, expected_working_hours = ?, per_day_salary = ?, hourly_salary = ?, calculated_salary = ?, difference_type = ?, ot_or_time_lost_amount = ?, leave_approved = ?, leave_pending = ?, leave_rejected = ?";
        $params[] = $professional_tax;
        $params[] = $advance;
        $params[] = $office;
        $params[] = $total_retentions;
        $params[] = $total_working_days;
        $params[] = $working_days;
        $params[] = $present_days;
        $params[] = $absent_days;
        $params[] = $leave_days;
        $params[] = $on_leave_days;
        $params[] = $comp_days;
        $params[] = $late_days;
        $params[] = $early_out_days;
        $params[] = $total_working_hours;
        $params[] = $expected_working_hours;
        $params[] = $per_day_salary;
        $params[] = $hourly_salary;
        $params[] = $calculated_salary;
        $params[] = $difference_type;
        $params[] = $ot_or_time_lost_amount;
        $params[] = $leave_approved;
        $params[] = $leave_pending;
        $params[] = $leave_rejected;
        $types .= "ddsdddiiiiiiidddddsdiii";

        $params[] = $target_salary_id;
        $types .= "i";

        $query = "UPDATE salary SET $updates WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->close();

        $payrollUpdated[] = $emp_id;
    }

    if (!empty($payrollUpdated)) {
        echo "
        <div class='alert alert-success' style='position: fixed; top: 10px; right: 10px; z-index: 1000;'>
            Payroll updated successfully for Selected Employee.
        </div>
        <script>
            setTimeout(function() {
                window.location.href = 'manage_salary';
            }, 2000);
        </script>
        ";
    }

    if (!empty($payrollNotFound)) {
        echo "<div class='alert alert-warning'>No payroll record found for employee IDs: " . implode(', ', $payrollNotFound) . ".</div>
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
