<?php
include("header.php");
// Database connection (Assuming $conn is already available)
// Get the employee ID from the URL
$employee_id = $_GET['id'] ?? null;
if (!$employee_id) {
    echo "<div class='alert alert-danger'>Invalid employee ID.</div>";
    exit;
}
// Fetch employee details
$stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$employee = $stmt->get_result()->fetch_assoc();
if (!$employee) {
    echo "<div class='alert alert-danger'>Employee not found.</div>";
    exit;
}
$salary_type = $employee['salary_type']; // 'Monthly' or 'Daily'
// Handle form submission for generating payroll
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $year = $_POST['year'];
    $month = $_POST['month'];
    // Check if payroll is already generated for this month
    $stmt = $conn->prepare("SELECT * FROM salary WHERE employee_id = ? AND year = ? AND month = ?");
    $stmt->bind_param("iss", $employee_id, $year, $month);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo "<div class='alert alert-danger'>Payroll already generated for this month.</div>";
    } else {
        // Calculate salary details

        $office = $_POST['office'];
        $basic = $_POST['basic'];
        $da = $_POST['da'];
        $hra = $_POST['hra'];
        $conveyance = $_POST['conveyance'];
        $special_allowance = $_POST['special_allowance'];
        $performance_bonus = $_POST['performance_bonus'];
        $medical_allowance = $_POST['medical_allowance'];
        $washing_allowance = $_POST['washing_allowance'];
        $canteen_allowance = $_POST['canteen_allowance'];
        $other_allowances = $_POST['other_allowances'];
        $gross_salary = $basic + $da + $hra + $conveyance + $special_allowance + $performance_bonus +
            $medical_allowance + $washing_allowance + $canteen_allowance + $other_allowances;

        $epf_employer = $_POST['epf_employer'];
        $esic_employer = $_POST['esic_employer'];
        $retention_bonus = $_POST['retention_bonus'];
        $gratuity = $_POST['gratuity'];
        $leave_encashment = $_POST['leave_encashment'];
        $total_retentions = $epf_employer + $esic_employer + $retention_bonus + $gratuity + $leave_encashment;
        $total_ctc = $gross_salary + $total_retentions;
        $epf_employee = $_POST['epf_employee'];
        $professional_tax = $_POST['professional_tax'];
        $income_tax = $_POST['income_tax'];
        $insurance_premium = $_POST['insurance_premium'];
        $advance = $_POST['advance'];
        $other_deductions = $_POST['other_deductions'];

        $total_working_days = $_POST['total_working_days'];
        $working_days = $_POST['working_days'];
        $absent_days = $_POST['absent_days'];
        $leave_days = $_POST['leave_days'];
        $present_days = $_POST['present_days'];
        $on_leave_days = $_POST['on_leave_days'];
        $late_days = $_POST['late_days'];
        $early_out_days = $_POST['early_out_days'];
        $total_working_hours = $_POST['total_working_hours'];
        $expected_working_hours = $_POST['expected_working_hours'];
        $per_day_salary = $_POST['per_day_salary'];
        $hourly_salary = $_POST['hourly_salary'];
        $calculated_salary = $_POST['calculated_salary'];
        $difference_type = $_POST['difference_type'];
        $ot_or_time_lost_amount = $_POST['ot_or_time_lost_amount'];
        $leave_approved = $_POST['leave_approved'];
        $leave_pending = $_POST['leave_pending'];
        $leave_rejected = $_POST['leave_rejected'];

        $total_deductions = $epf_employee + $professional_tax + $income_tax + $insurance_premium + $advance + $other_deductions;
        $net_salary = $gross_salary - $total_deductions;
        // Save payroll data
        $stmt = $conn->prepare("
            INSERT INTO salary (
                employee_id, year, month, office, basic, da, hra, conveyance, special_allowance, 
                performance_bonus, medical_allowance, washing_allowance, canteen_allowance, other_allowances, 
                gross_salary, epf_employer, esic_employer, retention_bonus, gratuity, leave_encashment, 
                total_retentions, total_ctc, epf_employee, professional_tax, income_tax, 
                insurance_premium, advance, other_deductions, total_deductions, net_salary, total_working_days, working_days,absent_days, leave_days, present_days,on_leave_days,late_days,early_out_days,total_working_hours,expected_working_hours,per_day_salary,hourly_salary,calculated_salary,difference_type,ot_or_time_lost_amount,leave_approved,leave_pending,leave_rejected  
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ");
        $stmt->bind_param(
            "isssdddddddddddddddddddddddddddddddddddddddsdddd",
            $employee_id,
            $year,
            $month,
            $office,
            $basic,
            $da,
            $hra,
            $conveyance,
            $special_allowance,
            $performance_bonus,
            $medical_allowance,
            $washing_allowance,
            $canteen_allowance,
            $other_allowances,
            $gross_salary,
            $epf_employer,
            $esic_employer,
            $retention_bonus,
            $gratuity,
            $leave_encashment,
            $total_retentions,
            $total_ctc,
            $epf_employee,
            $professional_tax,
            $income_tax,
            $insurance_premium,
            $advance,
            $other_deductions,
            $total_deductions,
            $net_salary,
            $total_working_days,
            $working_days,
            $absent_days,
            $leave_days,
            $present_days,
            $on_leave_days,
            $late_days,
            $early_out_days,
            $total_working_hours,
            $expected_working_hours,
            $per_day_salary,
            $hourly_salary,
            $calculated_salary,
            $difference_type,
            $ot_or_time_lost_amount,
            $leave_approved,
            $leave_pending,
            $leave_rejected,
        );
        $stmt->execute();

        echo "
    <div class='alert alert-success' style='position: fixed; top: 10px; right: 10px; z-index: 1000;'>
        Payroll generated successfully.
    </div>
    <script>
        // Wait for 3 seconds and then redirect
        setTimeout(function() {
            window.location.href = 'manage_salary';
        }, 2000);
    </script>
    ";
    }
}
$year = $_POST['year'] ?? date('Y');
$month = $_POST['month'] ?? date('m');
$stmt = $conn->prepare("SELECT * FROM attendance WHERE employee_id = ? AND YEAR(punch_in_time) = ? AND MONTH(punch_in_time) = ?");
$stmt->bind_param("iss", $employee_id, $year, $month);
$stmt->execute();
$attendance = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
// Calculate attendance stats
// Initialize attendance stats
$total_present = 0;
$total_absent = 0;
$total_on_leave = 0;
$total_late = 0;
$total_early = 0;
$total_working_hours = 0;
?>
<div class="container-fluid container-fluid-main">
    <h4>Salary Calculation for <?= htmlspecialchars($employee['name']) ?> (<?= $salary_type ?>)</h4>

    <!-- Salary Calculation Fields -->
    <div class="mb-4">
        <!-- Basic Information -->
        <form method="POST">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="year">Select Year</label>
                    <select name="year" class="form-control" required>
                        <?php for ($y = date('Y') - 5; $y <= date('Y'); $y++): ?>
                            <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="month">Select Month</label>
                    <select name="month" class="form-control" required>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= $m == $month ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-6">
                    <label for="total_working_days">Working Days</label>
                    <input readonly type="text"  class="form-control" name="total_working_days" id="total_working_days">
                </div>
                <div class="col-md-6">
                <label for="present_days">Present Days</label>
                <input readonly type="text"  class="form-control" name="present_days" id="present_days">
                </div>
                <input readonly type="hidden"  class="form-control" name="working_days" id="working_days">
            
                  <div class="col-md-6">
                <label for="absent_days">Absent Days</label>
                <input readonly type="text"  class="form-control" name="absent_days" id="absent_days">
                </div>
                  <div class="col-md-6">
                <label for="abc">Leave Days</label>
                <input readonly type="text"  class="form-control" name="leave_days" id="leave_days">
                </div>
               
                  
                <input readonly type="hidden"  class="form-control" name="on_leave_days" id="on_leave_days">
            
                  <div class="col-md-6">
                <label for="abc">Late Days</label>
                <input readonly type="text"  class="form-control" name="late_days" id="late_days">
                </div>
                  <div class="col-md-6">
                <label for="abc">Early Out Days</label>
                <input readonly type="text"  class="form-control" name="early_out_days" id="early_out_days">
                </div>
                  <div class="col-md-6">
                <label for="abc">Total Working Hours</label>
                <input readonly type="text"  class="form-control" name="total_working_hours" id="total_working_hours">
                </div>
                  <div class="col-md-6">
                <label for="abc">Expected Working Hours</label>
                <input readonly type="text"  class="form-control" name="expected_working_hours" id="expected_working_hours">
                </div>
                  <div class="col-md-6">
                <label for="abc">Per Day Salary</label>
                <input readonly type="text"  class="form-control" name="per_day_salary" id="per_day_salary">
                </div>
                  <div class="col-md-6">
                <label for="abc">Hourly Salary</label>
                <input readonly type="text"  class="form-control" name="hourly_salary" id="hourly_salary">
                </div>
                  <div class="col-md-6">
                <label for="abc">Calculated Salary</label>
                <input readonly type="text"  class="form-control" name="calculated_salary" id="calculated_salary">
                </div>
                  <div class="col-md-6">
                <label for="abc">Difference Type</label>
                <input readonly type="text"  class="form-control" name="difference_type" id="difference_type">
                </div>
                  <div class="col-md-6">
                <label for="abc">OT Or Time Lost Amount</label>
                <input readonly type="text"  class="form-control" name="ot_or_time_lost_amount" id="ot_or_time_lost_amount">
                </div>
                  <div class="col-md-6">
                <label for="abc">Leave Approved</label>
                <input readonly type="text"  class="form-control" name="leave_approved" id="leave_approved">
                </div>
                  <div class="col-md-6">
                <label for="abc">Leave Pending</label>
                <input readonly type="text"  class="form-control" name="leave_pending" id="leave_pending">
                </div>
                  <div class="col-md-6">
                <label for="abc">Leave Rejected</label>
                <input readonly type="text"  class="form-control" name="leave_rejected" id="leave_rejected">
                </div>

            </div>
                <div class="row">
                    <input type="hidden" value="<?= htmlspecialchars($employee['office']) ?>" name="office" id="office" class="form-control calculate">
                    <div class="col-md-6">
                        <label for="basic">Basic Salary</label>
                        <input type="number" name="basic" id="basic" class="form-control calculate" value="<?= htmlspecialchars($employee['basic']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="da">Dearness Allowance (DA)</label>
                        <input type="number" value="<?= htmlspecialchars($employee['da']) ?>" name="da" id="da" class="form-control calculate">
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <label for="hra">House Rent Allowance (HRA)</label>
                        <input type="number" value="<?= htmlspecialchars($employee['hra']) ?>" name="hra" id="hra" class="form-control calculate">
                    </div>
                    <div class="col-md-6">
                        <label for="conveyance">Conveyance Allowance</label>
                        <input type="number" value="<?= htmlspecialchars($employee['conveyance']) ?>" name="conveyance" id="conveyance" class="form-control calculate">
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <label for="special_allowance">Special Allowance</label>
                        <input type="number" value="<?= htmlspecialchars($employee['special_allowance']) ?>" name="special_allowance" id="special_allowance" class="form-control calculate">
                    </div>
                    <div class="col-md-6">
                        <label for="performance_bonus">Performance Bonus</label>
                        <input type="number" value="<?= htmlspecialchars($employee['performance_bonus']) ?>" name="performance_bonus" id="performance_bonus" class="form-control calculate">
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <label for="medical_allowance">Medical Allowance</label>
                        <input type="number" value="<?= htmlspecialchars($employee['medical_allowance']) ?>" name="medical_allowance" id="medical_allowance" class="form-control calculate">
                    </div>
                    <div class="col-md-6">
                        <label for="washing_allowance">Washing Allowance</label>
                        <input type="number" value="<?= htmlspecialchars($employee['washing_allowance']) ?>" name="washing_allowance" id="washing_allowance" class="form-control calculate">
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <label for="canteen_allowance">Canteen Allowance</label>
                        <input type="number" value="<?= htmlspecialchars($employee['canteen_allowance']) ?>" name="canteen_allowance" id="canteen_allowance" class="form-control calculate">
                    </div>
                    <div class="col-md-6">
                        <label for="other_allowances">Other Allowances</label>
                        <input type="number" value="<?= htmlspecialchars($employee['other_allowances']) ?>" name="other_allowances" id="other_allowances" class="form-control calculate">
                    </div>
                </div>
                <!-- Gross Salary -->
                <div class="row mt-3">
                    <div class="col-md-12">
                        <label for="gross_salary">Gross Salary</label>
                        <input type="number" value="<?= htmlspecialchars($employee['gross_salary']) ?>" name="gross_salary" id="gross_salary" class="form-control" readonly>
                    </div>
                </div>
                <!-- Retentions -->
                <h5 class="mt-4">Employer's Contributions</h5>
                <div class="row">
                    <div class="col-md-6">
                        <label for="epf_employer">EPF Employer</label>
                        <input type="number" value="<?= htmlspecialchars($employee['epf_employer']) ?>" name="epf_employer" id="epf_employer" class="form-control calculate">
                    </div>
                    <div class="col-md-6">
                        <label for="esic_employer">ESIC Employer</label>
                        <input type="number" value="<?= htmlspecialchars($employee['esic_employer']) ?>" name="esic_employer" id="esic_employer" class="form-control calculate">
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <label for="retention_bonus">Retention Bonus</label>
                        <input type="number" value="<?= htmlspecialchars($employee['retention_bonus']) ?>" name="retention_bonus" id="retention_bonus" class="form-control calculate">
                    </div>
                    <div class="col-md-6">
                        <label for="gratuity">Gratuity</label>
                        <input type="number" value="<?= htmlspecialchars($employee['gratuity']) ?>" name="gratuity" id="gratuity" class="form-control calculate">
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <label for="leave_encashment">Leave Encashment</label>
                        <input type="number" value="<?= htmlspecialchars($employee['leave_encashment']) ?>" name="leave_encashment" id="leave_encashment" class="form-control calculate">
                    </div>
                    <div class="col-md-6">
                        <label for="total_ctc">Total Retentions (CTC)</label>
                        <input type="number" value="<?= htmlspecialchars($employee['total_ctc']) ?>" name="total_ctc" id="total_ctc" class="form-control" readonly>
                    </div>
                </div>
                <!-- Deductions -->
                <h5 class="mt-4">Deductions</h5>
                <div class="row">
                    <div class="col-md-6">
                        <label for="epf_employee">Employee Provident Fund (EPF)</label>
                        <input type="number" value="<?= htmlspecialchars($employee['epf_employee']) ?>" name="epf_employee" id="epf_employee" class="form-control calculate">
                    </div>
                    <div class="col-md-6">
                        <label for="professional_tax">Professional Tax (PT)</label>
                        <input type="number" value="<?= htmlspecialchars($employee['professional_tax']) ?>" name="professional_tax" id="professional_tax" class="form-control calculate">
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <label for="income_tax">Income Tax (TDS)</label>
                        <input type="number" value="<?= htmlspecialchars($employee['income_tax']) ?>" name="income_tax" id="income_tax" class="form-control calculate">
                    </div>
                    <div class="col-md-6">
                        <label for="insurance_premium">Insurance Premium</label>
                        <input type="number" value="<?= htmlspecialchars($employee['insurance_premium']) ?>" name="insurance_premium" id="insurance_premium" class="form-control calculate">
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <label for="advance">Advance</label>
                        <input type="number" value="<?= htmlspecialchars($employee['advance']) ?>" name="advance" id="advance" class="form-control calculate">
                    </div>
                    <div class="col-md-6">
                        <label for="other_deductions">Other Deductions</label>
                        <input type="number" value="<?= htmlspecialchars($employee['other_deductions']) ?>" name="other_deductions" id="other_deductions" class="form-control calculate">
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <label for="total_deductions">Total Deductions</label>
                        <input type="number" value="<?= htmlspecialchars($employee['total_deductions']) ?>" name="total_deductions" id="total_deductions" class="form-control" readonly>
                    </div>
                    <div class="col-md-6">
                        <label for="net_salary">Net Salary (In-Hand)</label>
                        <input type="number" value="<?= htmlspecialchars($employee['net_salary']) ?>" name="net_salary" id="net_salary" class="form-control" readonly>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Generate Payroll</button>
        </form>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function fetchAttendanceStats() {
            var year = $('select[name="year"]').val();
            var month = $('select[name="month"]').val();
            var employee_id = <?= $employee_id ?>;

            $.ajax({
                url: 'get_attendance_stats',
                method: 'POST',
                data: {
                    employee_id,
                    year,
                    month
                },
                dataType: 'json',
                success: function(res) {
                    if (res.error) {
                        alert(res.error);
                        return;
                    }

                    // Attendance & work stats
                    $('#total_working_days').val(res.total_working_days);
                    $('#working_days').val(res.working_days);
                    $('#absent_days').val(res.absent_days);
                    $('#leave_days').val(res.leave_days);
                    $('#present_days').val(res.present);
                    $('#on_leave_days').val(res.on_leave);
                    $('#late_days').val(res.late);
                    $('#early_out_days').val(res.early);
                    $('#total_working_hours').val(parseFloat(res.total_working_hours).toFixed(2));

                    $('#expected_working_hours').val(res.expected_working_hours);
                    // Salary
                    $('#per_day_salary').val(res.per_day_salary);
                    $('#hourly_salary').val(res.hourly_salary);
                    $('#calculated_salary').val(res.calculated_salary);
                    $('#difference_type').val(res.difference_type);
                    $('#ot_or_time_lost_amount').val(res.ot_or_time_lost_amount);

                    // Leave Summary
                    $('#leave_approved').val(res.leave_summary.approved);
                    $('#leave_pending').val(res.leave_summary.pending);
                    $('#leave_rejected').val(res.leave_summary.rejected);
                }
            });
        }

        // Trigger on filter change
        $('select[name="year"], select[name="month"]').change(fetchAttendanceStats);
        // Trigger on page load
        $(document).ready(fetchAttendanceStats);
    </script>
    <script>
        document.querySelectorAll('.calculate').forEach(input => {
            input.addEventListener('input', calculateSalary);
        });

        function calculateSalary() {
            // Fetch all input values
            const basic = parseFloat(document.getElementById('basic').value) || 0;
            const da = parseFloat(document.getElementById('da').value) || 0;
            const hra = parseFloat(document.getElementById('hra').value) || 0;
            const conveyance = parseFloat(document.getElementById('conveyance').value) || 0;
            const special_allowance = parseFloat(document.getElementById('special_allowance').value) || 0;
            const performance_bonus = parseFloat(document.getElementById('performance_bonus').value) || 0;
            const medical_allowance = parseFloat(document.getElementById('medical_allowance').value) || 0;
            const washing_allowance = parseFloat(document.getElementById('washing_allowance').value) || 0;
            const canteen_allowance = parseFloat(document.getElementById('canteen_allowance').value) || 0;
            const other_allowances = parseFloat(document.getElementById('other_allowances').value) || 0;
            // Gross Salary Calculation
            const gross_salary = basic + da + hra + conveyance + special_allowance +
                performance_bonus + medical_allowance + washing_allowance +
                canteen_allowance + other_allowances;
            document.getElementById('gross_salary').value = gross_salary.toFixed(2);
            // Retentions
            const epf_employer = parseFloat(document.getElementById('epf_employer').value) || 0;
            const esic_employer = parseFloat(document.getElementById('esic_employer').value) || 0;
            const retention_bonus = parseFloat(document.getElementById('retention_bonus').value) || 0;
            const gratuity = parseFloat(document.getElementById('gratuity').value) || 0;
            const leave_encashment = parseFloat(document.getElementById('leave_encashment').value) || 0;

            const total_ctc = epf_employer + esic_employer + retention_bonus + gratuity + leave_encashment;
            document.getElementById('total_ctc').value = total_ctc.toFixed(2);

            // Deductions
            const epf_employee = parseFloat(document.getElementById('epf_employee').value) || 0;
            const professional_tax = parseFloat(document.getElementById('professional_tax').value) || 0;
            const income_tax = parseFloat(document.getElementById('income_tax').value) || 0;
            const insurance_premium = parseFloat(document.getElementById('insurance_premium').value) || 0;
            const advance = parseFloat(document.getElementById('advance').value) || 0;
            const other_deductions = parseFloat(document.getElementById('other_deductions').value) || 0;

            const total_deductions = epf_employee + professional_tax + income_tax + insurance_premium + advance + other_deductions;
            document.getElementById('total_deductions').value = total_deductions.toFixed(2);

            // Net Salary Calculation
            const net_salary = gross_salary - total_deductions;
            document.getElementById('net_salary').value = net_salary.toFixed(2);
        }
    </script>
    <?php include("footer.php"); ?>