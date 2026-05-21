<?php
require 'header.php'; // DB connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $year = $_POST['year'];
    $month = $_POST['month'];
    $total_working_days = $_POST['working_days'];
    $employee_ids = $_POST['employee_ids'];
    $notFoundEmployees = [];

    foreach ($employee_ids as $emp_id) {
        // Check if payroll exists
        $stmt = $conn->prepare("SELECT id FROM salary WHERE employee_id = ? AND year = ? AND month = ?");
        $stmt->bind_param("iss", $emp_id, $year, $month);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            // No existing record
            $empStmt = $conn->prepare("SELECT name FROM employees WHERE id = ?");
            $empStmt->bind_param("i", $emp_id);
            $empStmt->execute();
            $empName = $empStmt->get_result()->fetch_assoc()['name'];
            $notFoundEmployees[] = $empName;
            continue;
        }

        // Fetch and prepare updated values
        $basic = $_POST['basic'][$emp_id] ?? 0;
        $net_salary = $_POST['net_salary'][$emp_id] ?? 0;
        $present_days = $_POST['present_days'][$emp_id] ?? 0;
        $absent_days = $_POST['absent_days'][$emp_id] ?? 0;
        $leave_days = $_POST['leave_days'][$emp_id] ?? 0;
        $per_day_salary = $_POST['per_day_basic_salary'][$emp_id] ?? 0;
        $calculated_salary = $_POST['calculated_basic_salary'][$emp_id] ?? 0;
        $per_day_net = $_POST['per_day_net_salary'][$emp_id] ?? 0;
        $calculated_net = $_POST['calculated_net_salary'][$emp_id] ?? 0;

        $expected_present = $total_working_days - ($absent_days + $leave_days);
        $comp_days = max(0, $present_days - $expected_present);

        // Dummy/default values
        $office = '';
        $da = $hra = $conveyance = $special_allowance = 0;
        $performance_bonus = $medical_allowance = $washing_allowance = 0;
        $canteen_allowance = $other_allowances = 0;
        $gross_salary = $basic + $da + $hra + $conveyance + $special_allowance + $performance_bonus + $medical_allowance + $washing_allowance + $canteen_allowance + $other_allowances;
        $epf_employer = $esic_employer = $retention_bonus = $gratuity = $leave_encashment = 0;
        $total_retentions = $total_ctc = 0;
        $epf_employee = $professional_tax = $income_tax = $insurance_premium = $advance = $other_deductions = 0;
        $total_deductions = 0;
        $working_days = $total_working_days;
        $on_leave_days = $late_days = $early_out_days = 0;
        $total_working_hours = $expected_working_hours = 0;
        $hourly_salary = 0;
        $difference_type = '';
        $ot_or_time_lost_amount = 0;
        $leave_approved = $leave_pending = $leave_rejected = 0;

        $updateQuery = "UPDATE salary SET 
            office = ?, basic = ?, da = ?, hra = ?, conveyance = ?, special_allowance = ?, 
            performance_bonus = ?, medical_allowance = ?, washing_allowance = ?, canteen_allowance = ?, 
            other_allowances = ?, gross_salary = ?, epf_employer = ?, esic_employer = ?, retention_bonus = ?, 
            gratuity = ?, leave_encashment = ?, total_retentions = ?, total_ctc = ?, epf_employee = ?, 
            professional_tax = ?, income_tax = ?, insurance_premium = ?, advance = ?, other_deductions = ?, 
            total_deductions = ?, net_salary = ?, total_working_days = ?, working_days = ?, absent_days = ?, 
            leave_days = ?, present_days = ?, on_leave_days = ?, late_days = ?, early_out_days = ?, 
            total_working_hours = ?, expected_working_hours = ?, per_day_salary = ?, hourly_salary = ?, 
            calculated_salary = ?, difference_type = ?, ot_or_time_lost_amount = ?, leave_approved = ?, 
            leave_pending = ?, leave_rejected = ?
            WHERE employee_id = ? AND year = ? AND month = ?";

        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param(
            'sddddddddddddddddddddddddddiiiiiiddddsdiiiisiiii',
            $office, $basic, $da, $hra, $conveyance, $special_allowance,
            $performance_bonus, $medical_allowance, $washing_allowance, $canteen_allowance,
            $other_allowances, $gross_salary, $epf_employer, $esic_employer, $retention_bonus,
            $gratuity, $leave_encashment, $total_retentions, $total_ctc, $epf_employee,
            $professional_tax, $income_tax, $insurance_premium, $advance, $other_deductions,
            $total_deductions, $net_salary, $total_working_days, $working_days, $absent_days,
            $leave_days, $present_days, $on_leave_days, $late_days, $early_out_days,
            $total_working_hours, $expected_working_hours, $per_day_salary, $hourly_salary,
            $calculated_salary, $difference_type, $ot_or_time_lost_amount, $leave_approved,
            $leave_pending, $leave_rejected,
            $emp_id, $year, $month
        );
        

        $stmt->execute();
    }

    // Show result messages
    if (!empty($notFoundEmployees)) {
        $empNames = implode(", ", $notFoundEmployees);
        echo "<div class='alert alert-warning'>No existing payroll found for the following employees: $empNames.</div>";
    } else {
        echo "
        <div class='alert alert-success' style='position: fixed; top: 10px; right: 10px; z-index: 1000;'>
            Payroll updated successfully for selected employees.
        </div>
        <script>
            setTimeout(function() {
                window.location.href = 'salary_summary';
            }, 2000);
        </script>
        ";
    }

} else {
    echo "Invalid request.";
}
?>
