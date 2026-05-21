<?php
include("header.php");
// Fetch distinct offices
$offices_stmt = $conn->query("SELECT DISTINCT office FROM employees ORDER BY office ASC");
$offices = $offices_stmt->fetch_all(MYSQLI_ASSOC);
// Get selected office from POST
$selected_office = $_POST['office'] ?? '';
// Fetch employees by selected office
if (!empty($selected_office)) {
    $stmt = $conn->prepare("SELECT * FROM employees WHERE status = 'Active' AND office = ?");
    $stmt->bind_param("s", $selected_office);
} else {
    $stmt = $conn->prepare("SELECT * FROM employees WHERE status = 'Active'");
}
$stmt->execute();
$employees = $stmt->get_result();
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generate_payroll'])) {
    // Fetch selected employee IDs
    $employee_ids = $_POST['employee_ids'] ?? [];
    $year = isset($_POST['year']) ? $_POST['year'] : '';
    $month = isset($_POST['month']) ? $_POST['month'] : '';
    if (empty($employee_ids)) {
        echo "<div class='alert alert-danger'>No employees selected.</div>";
    } else {
        $payrollAlreadyExists = false;
        $employeesWithPayroll = [];
        foreach ($employee_ids as $employee_id) {
            // Check if payroll is already generated for the employee
            $stmt = $conn->prepare("SELECT id FROM salary WHERE employee_id = ? AND year = ? AND month = ?");
            $stmt->bind_param("iss", $employee_id, $year, $month);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $payrollAlreadyExists = true;
                $employee = $conn->prepare("SELECT name FROM employees WHERE id = ?");
                $employee->bind_param("i", $employee_id);
                $employee->execute();
                $employeeName = $employee->get_result()->fetch_assoc()['name'];
                $employeesWithPayroll[] = $employeeName;
                continue;
            }
            // Fetch employee details
            $stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
            $stmt->bind_param("i", $employee_id);
            $stmt->execute();
            $employee = $stmt->get_result()->fetch_assoc();
            if (!$employee) {
                continue; // Skip invalid employees
            }

            require_once 'get_attendance_stats2.php'; // Include the file that contains your function
            $attendanceData = get_attendance_stats($conn, $employee_id, $year, $month);
            // Salary calculations
            $office = $employee['office'];
            $basic = $employee['basic'];
            $da = $employee['da'];
            $hra = $employee['hra'];
            $conveyance = $employee['conveyance'];
            $special_allowance = $employee['special_allowance'];
            $performance_bonus = $employee['performance_bonus'];
            $medical_allowance = $employee['medical_allowance'];
            $washing_allowance = $employee['washing_allowance'];
            $canteen_allowance = $employee['canteen_allowance'];
            $other_allowances = $employee['other_allowances'];
            $gross_salary = $basic + $da + $hra + $conveyance + $special_allowance + $performance_bonus +
                $medical_allowance + $washing_allowance + $canteen_allowance + $other_allowances;
            $epf_employer = $employee['epf_employer'];
            $esic_employer = $employee['esic_employer'];
            $retention_bonus = $employee['retention_bonus'];
            $gratuity = $employee['gratuity'];
            $leave_encashment = $employee['leave_encashment'];
            $total_retentions = $epf_employer + $esic_employer + $retention_bonus + $gratuity + $leave_encashment;
            $total_ctc = $gross_salary + $total_retentions;
            $epf_employee = $employee['epf_employee'];
            $professional_tax = $employee['professional_tax'];
            $income_tax = $employee['income_tax'];
            $insurance_premium = $employee['insurance_premium'];
            $advance = $employee['advance'];
            $other_deductions = $employee['other_deductions'];
            $total_working_days = $attendanceData['total_working_days'];
            $working_days = $attendanceData['working_days'];
            $absent_days = $attendanceData['absent_days'];
            $leave_days = $attendanceData['leave_days'];
            $present_days = $attendanceData['present_days'];
            $on_leave_days = $attendanceData['on_leave_days'];
            $late_days = $attendanceData['late_days'];
            $early_out_days = $attendanceData['early_out_days'];
            $total_working_hours = $attendanceData['total_working_hours'];
            $expected_working_hours = $attendanceData['expected_working_hours'];
            $per_day_salary = $attendanceData['per_day_salary'];
            $hourly_salary = $attendanceData['hourly_salary'];
            $calculated_salary = $attendanceData['calculated_salary'];
            $difference_type = $attendanceData['difference_type'];
            $ot_or_time_lost_amount = $attendanceData['ot_or_time_lost_amount'];
            $leave_approved = $attendanceData['leave_approved'];
            $leave_pending = $attendanceData['leave_pending'];
            $leave_rejected = $attendanceData['leave_rejected'];
            $total_deductions = $epf_employee + $professional_tax + $income_tax + $insurance_premium + $advance + $other_deductions;
            $net_salary = $gross_salary - $total_deductions;
            // Insert salary data
            $stmt = $conn->prepare("
                INSERT INTO salary (
                    employee_id, year, month, office, basic, da, hra, conveyance, special_allowance, 
                    performance_bonus, medical_allowance, washing_allowance, canteen_allowance, other_allowances, 
                    gross_salary, epf_employer, esic_employer, retention_bonus, gratuity, leave_encashment, 
                    total_retentions, total_ctc, epf_employee, professional_tax, income_tax, 
                    insurance_premium, advance, other_deductions, total_deductions, net_salary, total_working_days, working_days, absent_days, leave_days, present_days, on_leave_days,
late_days, early_out_days, total_working_hours, expected_working_hours,per_day_salary, hourly_salary, calculated_salary, difference_type, ot_or_time_lost_amount,leave_approved, leave_pending, leave_rejected

                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )
            ");
            $stmt->bind_param(
                "isssdddddddddddddddddddddddddddddddddddddddsdddd",
                $employee_id,$year,$month,$office, $basic, $da,$hra, $conveyance,$special_allowance,$performance_bonus,$medical_allowance,$washing_allowance,$canteen_allowance,$other_allowances,$gross_salary,$epf_employer,$esic_employer, $retention_bonus, $gratuity, $leave_encashment, $total_retentions, $total_ctc, $epf_employee, $professional_tax, $income_tax, $insurance_premium, $advance, $other_deductions, $total_deductions,$net_salary,
                $total_working_days, $working_days,$absent_days, $leave_days, $present_days, $on_leave_days,
                $late_days, $early_out_days, $total_working_hours,$expected_working_hours,$per_day_salary,
                $hourly_salary, $calculated_salary, $difference_type, $ot_or_time_lost_amount,$leave_approved,$leave_pending, $leave_rejected
            );
            $stmt->execute();
        }
        if ($payrollAlreadyExists) {
            $employeesList = implode(", ", $employeesWithPayroll);
            echo "<div class='alert alert-danger'>Payroll already generated for the following employees for the selected month: $employeesList.</div>";
        } else {
            echo "
            <div class='alert alert-success' style='position: fixed; top: 10px; right: 10px; z-index: 1000;'>
                Payroll generated successfully for selected employees.
            </div>
            <script>
                setTimeout(function() {
                    window.location.href = 'manage_salary.php';
                }, 2000);
            </script>
            ";
        }
    }
}
?>
<div class="container">
    <h3>Generate Payroll in Bulk</h3>
    <div class="row">
        <form method="POST" action="">
            <div class="col-md-6">
                <label for="office">Select Office:</label>
                <select name="office" class="form-control" onchange="this.form.submit()">
                    <option value="">All Offices</option>
                    <?php foreach ($offices as $office): ?>
                        <option value="<?= $office['office']; ?>" <?= $selected_office == $office['office'] ? 'selected' : '' ?>>
                            <?= $office['office']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
        <div class="col-md-4">
            <form method="POST" action="">
                <label for="year">Select Year:</label>
                <select name="year" class="form-control">
                    <?php
                    $currentYear = date('Y');
                    for ($i = $currentYear - 5; $i <= $currentYear + 5; $i++) {
                        $selected = $i == $currentYear ? 'selected' : '';
                        echo "<option value='$i' $selected>$i</option>";
                    }
                    ?>
                </select>
        </div>
        <div class="col-md-4">
            <label for="month">Select Month:</label>
            <select name="month" class="form-control">
                <?php
                for ($i = 1; $i <= 12; $i++) {
                    $monthName = date('F', mktime(0, 0, 0, $i, 10));
                    $selected = $i == date('m') ? 'selected' : '';
                    echo "<option value='$i' $selected>$monthName</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-md-4 d-flex align-items-end mt-4">
            <label class="checkbox style-h">
                <input type="checkbox" id="select-all" />
                <div class="checkbox__checkmark"></div>
                <div class="checkbox__body">Select All</div>
            </label>
        </div>
    </div>
    <div class="my-4">
        <h5>Select Employees</h5>
        <div class="row">
            <?php while ($employee = $employees->fetch_assoc()) { ?>
                <div class="col-md-3 mt-2">
                    <label class="checkbox style-h">
                        <input type="checkbox" name="employee_ids[]" value="<?= $employee['id']; ?>">
                        <div class="checkbox__checkmark"></div>
                        <div class="checkbox__body"><?= $employee['name']; ?></div>
                    </label>
                </div>
            <?php } ?>
        </div>
    </div>
    <button type="submit" name="generate_payroll" class="btn btn-primary">Generate Payroll</button>
    </form>
</div>

<script>
    // Select All checkbox logic
    document.getElementById('select-all').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll("input[name='employee_ids[]']");
        checkboxes.forEach(cb => cb.checked = this.checked);
    });
</script>

<?php include("footer.php"); ?>