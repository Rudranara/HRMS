<?php
include("header.php");
// Database connection (Assuming $conn is already available)
// Check if a salary ID is provided

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('Salary ID is required.');
}
$salary_id = $_GET['id'];
// Fetch salary record by ID
$stmt = $conn->prepare("
    SELECT * FROM salary WHERE id = ?
");
$stmt->bind_param('i', $salary_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Salary record not found.');
}
$salary = $result->fetch_assoc();
// Fetch employee details
$employee_id = $salary['employee_id'];
$employee_stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
$employee_stmt->bind_param('i', $employee_id);
$employee_stmt->execute();
$employee = $employee_stmt->get_result()->fetch_assoc();
$salary_type = $employee['salary_type']; // 'Monthly' or 'Daily'
// Update salary record
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    $gross_salary = $basic + $da + $hra + $conveyance + $special_allowance + $performance_bonus + $medical_allowance + $washing_allowance + $canteen_allowance + $other_allowances;
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
    $total_deductions = $epf_employee + $professional_tax + $income_tax + $insurance_premium + $advance + $other_deductions;
    $net_salary = $gross_salary - $total_deductions;

    // Update the salary in the database
    $update_stmt = $conn->prepare("
        UPDATE salary SET 
            basic = ?, da = ?, hra = ?, conveyance = ?, special_allowance = ?, performance_bonus = ?, 
            medical_allowance = ?, washing_allowance = ?, canteen_allowance = ?, other_allowances = ?, 
            gross_salary = ?, epf_employer = ?, esic_employer = ?, retention_bonus = ?, gratuity = ?, 
            leave_encashment = ?, total_retentions = ?, total_ctc = ?, epf_employee = ?, 
            professional_tax = ?, income_tax = ?, insurance_premium = ?, advance = ?, 
            other_deductions = ?, total_deductions = ?, net_salary = ?
        WHERE id = ?
    ");
    $update_stmt->bind_param(
        'ddddddddddddddddddddddddddi', 
        $basic, $da, $hra, $conveyance, $special_allowance, $performance_bonus, 
        $medical_allowance, $washing_allowance, $canteen_allowance, $other_allowances, 
        $gross_salary, $epf_employer, $esic_employer, $retention_bonus, $gratuity, 
        $leave_encashment, $total_retentions, $total_ctc, $epf_employee, 
        $professional_tax, $income_tax, $insurance_premium, $advance, 
        $other_deductions, $total_deductions, $net_salary, $salary_id
    );

    if ($update_stmt->execute()) {
        echo "<script>alert('Salary updated successfully!'); window.location.href = 'manage_salary';</script>";
    } else {
        echo "<script>alert('Error updating salary.');</script>";
    }
}
// Fetch attendance details for the selected month
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
// Extract punch-in and punch-out times for comparison
$punchin_time = date('H:i:s', strtotime($employee['punchin_time'])); // Normalize to time
$punchout_time = date('H:i:s', strtotime($employee['punchout_time'])); // Normalize to time
foreach ($attendance as $record) {
    // Parse punch-in and punch-out records
    $punch_in_time = date('H:i:s', strtotime($record['punch_in_time'])); // Extract time only
    $punch_out_time = date('H:i:s', strtotime($record['punch_out_time'])); // Extract time only
    // Increment counters based on attendance status
    if ($record['status'] == 'Present') $total_present++;
    if ($record['status'] == 'Absent') $total_absent++;
    if ($record['status'] == 'On Leave') $total_on_leave++;
    // Check late arrivals
    if ($punch_in_time > $punchin_time) $total_late++;
    // Check early departures
    if ($punch_out_time < $punchout_time) $total_early++;
    // Accumulate total working hours
    $total_working_hours += $record['working_hours'];
}
?>
<div class="container">
    <h3>Salary Calculation for <?= htmlspecialchars($employee['name']) ?> (<?= $salary_type ?>)</h3>
    <div class="mb-4">
        <form id="filter-form">
            <div class="row">
                <div class="col-md-4">
                    <label for="year">Select Year:</label>
                    <select id="year" class="form-control">
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
                    <select id="month" class="form-control">
                        <?php
                        for ($i = 1; $i <= 12; $i++) {
                            $monthName = date('F', mktime(0, 0, 0, $i, 10));
                            $selected = $i == date('m') ? 'selected' : '';
                            echo "<option value='$i' $selected>$monthName</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4 mt-4">
                    <button type="button" id="filter-btn" class="btn btn-primary">Filter</button>
                </div>
            </div>
        </form>
    </div>
    <form method="POST">
      
    <div id="attendance-details">
       
       </div>
        <!-- Salary Calculation Fields -->
        <div class="mb-4">
    <h5>Salary Structure</h5>
    <!-- Basic Information -->

    <div class="row mb-3">
    <div class="col-md-6">
        <label for="year">Select Year</label>
        <select name="year" class="form-control" required>
            <?php 
            $selectedYear = $salary['year'] ?? date('Y'); // Default to current year if not set
            for ($y = date('Y') - 5; $y <= date('Y'); $y++): ?>
                <option value="<?= $y ?>" <?= $y == $selectedYear ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </div>
    <div class="col-md-6">
        <label for="month">Select Month</label>
        <select name="month" class="form-control" required>
            <?php 
            $selectedMonth = $salary['month'] ?? date('m'); // Default to current month if not set
            for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= $m ?>" <?= $m == $selectedMonth ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
            <?php endfor; ?>
        </select>
    </div>
</div>

    <div class="row">
        <div class="col-md-6">
            <label for="basic">Basic Salary</label>
            <input type="number" name="basic" id="basic" class="form-control calculate" value="<?php echo $salary['basic']; ?>" required>
        </div>
        <div class="col-md-6">
            <label for="da">Dearness Allowance (DA)</label>
            <input type="number" name="da" id="da" class="form-control calculate" value="<?php echo $salary['da']; ?>">
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-md-6">
            <label for="hra">House Rent Allowance (HRA)</label>
            <input type="number" name="hra" id="hra" class="form-control calculate" value="<?php echo $salary['hra']; ?>">
        </div>
        <div class="col-md-6">
            <label for="conveyance">Conveyance Allowance</label>
            <input type="number" name="conveyance" id="conveyance" class="form-control calculate" value="<?php echo $salary['conveyance']; ?>">
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-md-6">
            <label for="special_allowance">Special Allowance</label>
            <input type="number" name="special_allowance" id="special_allowance" class="form-control calculate" value="<?php echo $salary['special_allowance']; ?>">
        </div>
        <div class="col-md-6">
            <label for="performance_bonus">Performance Bonus</label>
            <input type="number" name="performance_bonus" id="performance_bonus" class="form-control calculate" value="<?php echo $salary['performance_bonus']; ?>">
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-md-6">
            <label for="medical_allowance">Medical Allowance</label>
            <input type="number" name="medical_allowance" id="medical_allowance" class="form-control calculate" value="<?php echo $salary['medical_allowance']; ?>">
        </div>
        <div class="col-md-6">
            <label for="washing_allowance">Washing Allowance</label>
            <input type="number" name="washing_allowance" id="washing_allowance" class="form-control calculate" value="<?php echo $salary['washing_allowance']; ?>">
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-md-6">
            <label for="canteen_allowance">Canteen Allowance</label>
            <input type="number" name="canteen_allowance" id="canteen_allowance" class="form-control calculate" value="<?php echo $salary['canteen_allowance']; ?>">
        </div>
        <div class="col-md-6">
            <label for="other_allowances">Other Allowances</label>
            <input type="number" name="other_allowances" id="other_allowances" class="form-control calculate" value="<?php echo $salary['other_allowances']; ?>">
        </div>
    </div>
    <!-- Gross Salary -->
    <div class="row mt-3">
        <div class="col-md-12">
            <label for="gross_salary">Gross Salary</label>
            <input type="number" name="gross_salary" id="gross_salary" class="form-control" value="<?php echo $salary['gross_salary']; ?>" readonly>
        </div>
    </div>
    <!-- Retentions -->
    <h5 class="mt-4">Employer's Contributions</h5>
    <div class="row">
        <div class="col-md-6">
            <label for="epf_employer">EPF Employer</label>
            <input type="number" name="epf_employer" id="epf_employer" class="form-control calculate" value="<?php echo $salary['epf_employer']; ?>">
        </div>
        <div class="col-md-6">
            <label for="esic_employer">ESIC Employer</label>
            <input type="number" name="esic_employer" id="esic_employer" class="form-control calculate" value="<?php echo $salary['esic_employer']; ?>">
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-md-6">
            <label for="retention_bonus">Retention Bonus</label>
            <input type="number" name="retention_bonus" id="retention_bonus" class="form-control calculate" value="<?php echo $salary['retention_bonus']; ?>">
        </div>
        <div class="col-md-6">
            <label for="gratuity">Gratuity</label>
            <input type="number" name="gratuity" id="gratuity" class="form-control calculate" value="<?php echo $salary['gratuity']; ?>">
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-md-6">
            <label for="leave_encashment">Leave Encashment</label>
            <input type="number" name="leave_encashment" id="leave_encashment" class="form-control calculate" value="<?php echo $salary['leave_encashment']; ?>">
        </div>
        <div class="col-md-6">
            <label for="total_ctc">Total Retentions (CTC)</label>
            <input type="number" name="total_ctc" id="total_ctc" class="form-control" value="<?php echo $salary['total_ctc']; ?>" readonly>
        </div>
    </div>
    <!-- Deductions -->
    <h5 class="mt-4">Deductions</h5>
    <div class="row">
        <div class="col-md-6">
            <label for="epf_employee">Employee Provident Fund (EPF)</label>
            <input type="number" name="epf_employee" id="epf_employee" class="form-control calculate" value="<?php echo $salary['epf_employee']; ?>">
        </div>
        <div class="col-md-6">
            <label for="professional_tax">Professional Tax (PT)</label>
            <input type="number" name="professional_tax" id="professional_tax" class="form-control calculate" value="<?php echo $salary['professional_tax']; ?>">
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-md-6">
            <label for="income_tax">Income Tax (TDS)</label>
            <input type="number" name="income_tax" id="income_tax" class="form-control calculate" value="<?php echo $salary['income_tax']; ?>">
        </div>
        <div class="col-md-6">
            <label for="insurance_premium">Insurance Premium</label>
            <input type="number" name="insurance_premium" id="insurance_premium" class="form-control calculate" value="<?php echo $salary['insurance_premium']; ?>">
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-md-6">
            <label for="advance">Advance</label>
            <input type="number" name="advance" id="advance" class="form-control calculate" value="<?php echo $salary['advance']; ?>">
        </div>
        <div class="col-md-6">
            <label for="other_deductions">Other Deductions</label>
            <input type="number" name="other_deductions" id="other_deductions" class="form-control calculate" value="<?php echo $salary['other_deductions']; ?>">
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-md-6">
            <label for="total_deductions">Total Deductions</label>
            <input type="number" name="total_deductions" id="total_deductions" class="form-control" value="<?php echo $salary['total_deductions']; ?>" readonly>
        </div>
        <div class="col-md-6">
            <label for="net_salary">Net Salary (In-Hand)</label>
            <input type="number" name="net_salary" id="net_salary" class="form-control" value="<?php echo $salary['net_salary']; ?>" readonly>
        </div>
    </div>
</div>
        <button type="submit" class="btn btn-primary">Generate Payroll</button>
    </form>
</div>
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
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function () {
    // Load attendance details on page load
    fetchAttendance();

    // Handle filter button click
    $('#filter-btn').on('click', function () {
        fetchAttendance();
    });

    function fetchAttendance() {
        const employeeId = <?= $employee_id ?>;
        const year = $('#year').val();
        const month = $('#month').val();

        $.ajax({
            url: 'fetch_attendance',
            type: 'GET',
            data: {
                id: employeeId,
                year: year,
                month: month
            },
            success: function (response) {
                $('#attendance-details').html(response);
            },
            error: function () {
                $('#attendance-details').html('<div class="alert alert-danger">Failed to fetch attendance details.</div>');
            }
        });
    }
});
</script>
<?php include("footer.php"); ?>

