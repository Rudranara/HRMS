<?php
include("header2.php");
require_once 'get_attendance_stats3.php';
// Filters
$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$selected_month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$selected_office = isset($_GET['office']) ? $_GET['office'] : '';
$selected_employee = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : 0;
// Offices
$offices_query = $conn->query("SELECT office_name, state_name FROM offices ORDER BY office_name ASC");
$offices = $offices_query->fetch_all(MYSQLI_ASSOC);
// Employees (based on selected office)
$emp_query = "SELECT id, name, office FROM employees WHERE 1=1";
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
// Get working days
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
    if ($day_of_week < 7 && !in_array($date, $holiday_dates)) {
        $total_working_days++;
    }
}
// Fetch salary records
$salary_query = "SELECT s.*, e.name, e.office FROM salary s JOIN employees e ON s.employee_id = e.id WHERE s.year = ? AND s.month = ?";
$params = [$selected_year, $selected_month];
$types = "ii";
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
$stmt = $conn->prepare($salary_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
  <div class="container-fluid container-fluid-main">
    <h5 class="mb-3">Edit Generated Payrolls (<?= $selected_year ?> - <?= date('F', mktime(0, 0, 0, $selected_month, 10)) ?>)</h5>
    <form method="GET" class="row g-3 mb-4">
        <div class="col-md-3">
            <label>Office</label>
            <select name="office" class="form-control" onchange="this.form.submit()">
                <option value="">All Offices</option>
                <?php foreach ($offices as $office):
                    $val = urlencode($office['office_name']) . "_" . urlencode($office['state_name']);
                ?>
                    <option value="<?= $val ?>" <?= $val == $selected_office ? 'selected' : '' ?>>
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
            <button type="submit" class="btn btn-primary w-100">Filter</button>
        </div>
    </form>

    <form method="POST" action="update_salary_summary">
        <input type="hidden" name="year" value="<?= $selected_year ?>">
        <input type="hidden" name="month" value="<?= $selected_month ?>">
        <input type="hidden" name="working_days" value="<?= $total_working_days ?>">
        <div class="table-responsive">
            <table class="table table-bordered table-striped text-center">
            <thead>
    <tr>
        <th>Name</th>
        <th>W/D</th>
        <th>P/D</th>
        <th>A/D</th>
        <th>L/D</th>
        <th>Comp Days</th>
        <th>Basic</th>
        <th>Conveyance</th>
        <th>Special Allow.</th>
        <th>Perf. Bonus</th>
        <th>Medical Allow.</th>
        <th>Washing Allow.</th>
        <th>Canteen Allow.</th>
        <th>Other Allow.</th>
        <th>Gross Salary</th>
        <th>EPF (Emp)</th>
        <th>ESIC (Emp)</th>
        <th>Retention Bonus</th>
        <th>Leave Encash.</th>
        <th>Gratuity</th>
        <th>Total CTC</th>
        <th>EPF (Emp)</th>
        <th>Prof. Tax</th>
        <th>Income Tax</th>
        <th>Insurance</th>
        <th>Advance</th>
        <th>Other Deduct.</th>
        <th>Total Deduct.</th>
        <th>Net Salary</th>
    </tr>
</thead>
<tbody>
<?php foreach ($records as $row):
    $id = $row['employee_id'];
    $present = $row['present_days'];
    $absent = $row['absent_days'];
    $leave = $row['leave_days'];
    $basic = $row['basic'];
    
    // Allowances
    $conveyance = $row['conveyance'];
    $special_allowance = $row['special_allowance'];
    $performance_bonus = $row['performance_bonus'];
    $medical_allowance = $row['medical_allowance'];
    $washing_allowance = $row['washing_allowance'];
    $canteen_allowance = $row['canteen_allowance'];
    $other_allowances = $row['other_allowances'];
    $gross_salary = $row['gross_salary'];
    
    // Employer Contributions
    $epf_employer = $row['epf_employer'];
    $esic_employer = $row['esic_employer'];
    $retention_bonus = $row['retention_bonus'];
    $leave_encashment = $row['leave_encashment'];
    $gratuity = $row['gratuity'];
    $total_ctc = $row['total_ctc'];

    // Deductions
    $epf_employee = $row['epf_employee'];
    $professional_tax = $row['professional_tax'];
    $income_tax = $row['income_tax'];
    $insurance_premium = $row['insurance_premium'];
    $advance = $row['advance'];
    $other_deductions = $row['other_deductions'];
    $total_deductions = $row['total_deductions'];
    $net_salary = $row['net_salary'];  
?>
<tr>
    <td><?= htmlspecialchars($row['name']) ?></td>
    <td><?= $total_working_days ?></td>
    <td><input type="number" name="present_days[<?= $id ?>]" class="form-control present" value="<?= $present ?>"></td>
    <td><input type="number" name="absent_days[<?= $id ?>]" class="form-control absent" value="<?= $absent ?>"></td>
    <td><input type="number" name="leave_days[<?= $id ?>]" class="form-control leave" value="<?= $leave ?>"></td>
    <td><input type="number" name="comp_days[<?= $id ?>]" class="form-control comp_days" value="0" readonly></td>

    <td><input type="number" name="basic[<?= $id ?>]" class="form-control basic" value="<?= $basic ?>"></td>
    <td><input type="number" name="conveyance[<?= $id ?>]" class="form-control conveyance" value="<?= $conveyance ?>"></td>
    <td><input type="number" name="special_allowance[<?= $id ?>]" class="form-control special" value="<?= $special_allowance ?>"></td>
    <td><input type="number" name="performance_bonus[<?= $id ?>]" class="form-control performance" value="<?= $performance_bonus ?>"></td>
    <td><input type="number" name="medical_allowance[<?= $id ?>]" class="form-control medical" value="<?= $medical_allowance ?>"></td>
    <td><input type="number" name="washing_allowance[<?= $id ?>]" class="form-control washing" value="<?= $washing_allowance ?>"></td>
    <td><input type="number" name="canteen_allowance[<?= $id ?>]" class="form-control canteen" value="<?= $canteen_allowance ?>"></td>
    <td><input type="number" name="other_allowances[<?= $id ?>]" class="form-control other_allow" value="<?= $other_allowances ?>"></td>
    <td><input type="number" name="gross_salary[<?= $id ?>]" class="form-control gross" value="<?= $gross_salary ?>" readonly></td>
    <td><input type="number" name="epf_employer[<?= $id ?>]" class="form-control epf_employer" value="<?= $epf_employer ?>"></td>
    <td><input type="number" name="esic_employer[<?= $id ?>]" class="form-control esic_employer" value="<?= $esic_employer ?>"></td>
    <td><input type="number" name="retention_bonus[<?= $id ?>]" class="form-control retention" value="<?= $retention_bonus ?>"></td>
    <td><input type="number" name="leave_encashment[<?= $id ?>]" class="form-control leave_encash" value="<?= $leave_encashment ?>"></td>
    <td><input type="number" name="gratuity[<?= $id ?>]" class="form-control gratuity" value="<?= $gratuity ?>"></td>
    <td><input type="number" name="total_ctc[<?= $id ?>]" class="form-control total_ctc" value="<?= $total_ctc ?>" readonly></td>
    <td><input type="number" name="epf_employee[<?= $id ?>]" class="form-control epf_employee" value="<?= $epf_employee ?>"></td>
    <td><input type="number" name="professional_tax[<?= $id ?>]" class="form-control ptax" value="<?= $professional_tax ?>"></td>
    <td><input type="number" name="income_tax[<?= $id ?>]" class="form-control income_tax" value="<?= $income_tax ?>"></td>
    <td><input type="number" name="insurance_premium[<?= $id ?>]" class="form-control insurance" value="<?= $insurance_premium ?>"></td>
    <td><input type="number" name="advance[<?= $id ?>]" class="form-control advance" value="<?= $advance ?>"></td>
    <td><input type="number" name="other_deductions[<?= $id ?>]" class="form-control other_deduct" value="<?= $other_deductions ?>"></td>

    <td><input type="number" name="total_deductions[<?= $id ?>]" class="form-control total_deduct" value="<?= $total_deductions ?>" readonly></td>
    <td><input type="number" name="net_salary[<?= $id ?>]" class="form-control net_salary" value="<?= $net_salary ?>" readonly></td>
    
    <input type="hidden" name="employee_ids[]" value="<?= $id ?>">
</tr>
<?php endforeach; ?>
</tbody>
            </table>
        </div>
        <div class="text-end mt-3">
            <button type="submit" class="btn btn-success">Update Salary Summary</button>
        </div>
    </form>
</div>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const totalWorkingDays = <?= json_encode($total_working_days) ?>;

    // For each row
    document.querySelectorAll("input.present").forEach(input => {
        input.addEventListener("input", function () {
            const row = input.closest("tr");
            const presentDays = parseFloat(input.value) || 0;

            // Get all editable salary fields from the row
            const fields = {
                basic: row.querySelector("input.basic"),
                conveyance: row.querySelector("input.conveyance"),
                special: row.querySelector("input.special"),
                performance: row.querySelector("input.performance"),
                medical: row.querySelector("input.medical"),
                washing: row.querySelector("input.washing"),
                canteen: row.querySelector("input.canteen"),
                other_allow: row.querySelector("input.other_allow")
            };

            // Calculate per-day & recalculated values
            let gross = 0;
            for (const key in fields) {
                const original = parseFloat(fields[key].defaultValue) || 0;
                const perDay = totalWorkingDays > 0 ? (original / totalWorkingDays) : 0;
                const calculated = presentDays * perDay;
                fields[key].value = calculated.toFixed(2);
                gross += calculated;
            }

            // Update Gross Salary
            const grossField = row.querySelector("input.gross");
            grossField.value = gross.toFixed(2);

            // Employer Contributions
            const epfEmp = row.querySelector("input.epf_employer");
            const esicEmp = row.querySelector("input.esic_employer");
            const retention = row.querySelector("input.retention");
            const leaveEncash = row.querySelector("input.leave_encash");
            const gratuity = row.querySelector("input.gratuity");

            const epfVal = parseFloat(epfEmp.value) || 0;
            const esicVal = parseFloat(esicEmp.value) || 0;
            const retentionVal = parseFloat(retention.value) || 0;
            const leaveEncashVal = parseFloat(leaveEncash.value) || 0;
            const gratuityVal = parseFloat(gratuity.value) || 0;

            const totalCTC = gross + epfVal + esicVal + retentionVal + leaveEncashVal + gratuityVal;
            row.querySelector("input.total_ctc").value = totalCTC.toFixed(2);

            // Deductions
            const epfEmpD = parseFloat(row.querySelector("input.epf_employee").value) || 0;
            const ptax = parseFloat(row.querySelector("input.ptax").value) || 0;
            const itax = parseFloat(row.querySelector("input.income_tax").value) || 0;
            const insurance = parseFloat(row.querySelector("input.insurance").value) || 0;
            const advance = parseFloat(row.querySelector("input.advance").value) || 0;
            const otherDeduct = parseFloat(row.querySelector("input.other_deduct").value) || 0;

            const totalDeduct = epfEmpD + ptax + itax + insurance + advance + otherDeduct;
            row.querySelector("input.total_deduct").value = totalDeduct.toFixed(2);

            // Net Salary
            const netSalary = gross - totalDeduct;
            row.querySelector("input.net_salary").value = netSalary.toFixed(2);
        });
    });
});
</script>



<?php include("footer.php"); ?>