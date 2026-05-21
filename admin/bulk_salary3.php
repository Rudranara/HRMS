<?php
include("header.php");
require_once 'get_attendance_stats3.php';

// Get filter inputs
$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$selected_month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$selected_office = isset($_GET['office']) ? $_GET['office'] : '';

// Fetch offices
$offices_query = $conn->query("SELECT office_name, state_name FROM offices ORDER BY office_name ASC");
$offices = $offices_query->fetch_all(MYSQLI_ASSOC);

// Get holidays
$holiday_query = $conn->prepare("SELECT start_date FROM events WHERE YEAR(start_date) = ? AND MONTH(start_date) = ?");
$holiday_query->bind_param("ii", $selected_year, $selected_month);
$holiday_query->execute();
$holidays = $holiday_query->get_result()->fetch_all(MYSQLI_ASSOC);
$holiday_dates = array_column($holidays, 'start_date');

// Calculate working days (excluding weekends & holidays)
$total_days = cal_days_in_month(CAL_GREGORIAN, $selected_month, $selected_year);
$total_working_days = 0;
for ($d = 1; $d <= $total_days; $d++) {
    $date = sprintf('%04d-%02d-%02d', $selected_year, $selected_month, $d);
    $day_of_week = date('N', strtotime($date));
    if ($day_of_week < 7 && !in_array($date, $holiday_dates)) {
        $total_working_days++;
    }
}

// Fetch employees
$employee_query = "SELECT id, name, office FROM employees";
if (!empty($selected_office)) {
    $employee_query .= " WHERE office = ?";
}
$emp_stmt = $conn->prepare($employee_query);
if (!empty($selected_office)) {
    $emp_stmt->bind_param("s", $selected_office);
}
$emp_stmt->execute();
$employees = $emp_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$emp_stmt->close();
?>

<div class="container mt-4">
    <h5 class="mb-3">Employee Salary Summary (<?= $selected_year ?> - <?= date('F', mktime(0, 0, 0, $selected_month, 10)) ?>)</h5>

    <form method="GET" class="row g-3 mb-4">
        <div class="col-md-4">
            <label>Office</label>
            <select name="office" class="form-control">
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
                    <option value="<?= $m ?>" <?= $selected_month == $m ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-primary">Filter</button>
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <a href="download_attendance.php?year=<?= $selected_year ?>&month=<?= $selected_month ?>&office=<?= urlencode($selected_office) ?>" class="btn btn-success">Download CSV</a>
        </div>
    </form>


<form method="POST" action="save_salary_summary.php"> 
    <input type="hidden" name="year" value="<?= $selected_year ?>">
    <input type="hidden" name="month" value="<?= $selected_month ?>">
    <input type="hidden" name="working_days" value="<?= $total_working_days ?>">

    <div class="table-responsive">
        <table class="table table-bordered text-center">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>W/D</th>
                    <th>Basic</th>
                    <th>DA</th>
                    <th>HRA</th>
                    <th>Conveyance</th>
                    <th>Special Allowance</th>
                    <th>Performance Bonus</th>
                    <th>Medical</th>
                    <th>Washing</th>
                    <th>Canteen</th>
                    <th>Other Allowances</th>
                    <th>Gross</th>
                    <th>EPF Emp.</th>
                    <th>ESIC Emp.</th>
                    <th>Retention Bonus</th>
                    <th>Gratuity</th>
                    <th>Leave Encash</th>
                    <th>Total Ret.</th>
                    <th>Total CTC</th>
                    <th>EPF Emp.</th>
                    <th>Prof. Tax</th>
                    <th>Income Tax</th>
                    <th>Insurance</th>
                    <th>Advance</th>
                    <th>Other Deduct.</th>
                    <th>Total Deduct.</th>
                    <th>Present</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($employees as $emp): 
                $id = $emp['id'];
                $stats = get_attendance_stats($conn, $id, $selected_year, $selected_month, $total_working_days);
                $basic = $stats['basic'] ?? 0;
                $da = $stats['da'] ?? 0;
                $hra = $stats['hra'] ?? 0;
                $present = $stats['present_days'] ?? $total_working_days;
                $absent = $total_working_days - $present;
                $per_day_salary = $total_working_days > 0 ? round($basic / $total_working_days, 2) : 0;
                $calculated_salary = $per_day_salary * $present;
            ?>
                <tr data-id="<?= $id ?>">
                    <td><?= htmlspecialchars($emp['name']) ?></td>
                    <td><?= $total_working_days ?></td>
                    <td><input name="basic[<?= $id ?>]" class="form-control comp basic" type="number" value="<?= $basic ?>"></td>
                    <td><input name="da[<?= $id ?>]" class="form-control comp da" type="number" value="<?= $da ?>"></td>
                    <td><input name="hra[<?= $id ?>]" class="form-control comp hra" type="number" value="<?= $hra ?>"></td>
                    <td><input name="conveyance[<?= $id ?>]" class="form-control comp conveyance" type="number" value="1500"></td>
                    <td><input name="special_allowance[<?= $id ?>]" class="form-control comp special" type="number" value="1200"></td>
                    <td><input name="performance_bonus[<?= $id ?>]" class="form-control comp perf_bonus" type="number" value="2500"></td>
                    <td><input name="medical_allowance[<?= $id ?>]" class="form-control comp medical" type="number" value="1000"></td>
                    <td><input name="washing_allowance[<?= $id ?>]" class="form-control comp washing" type="number" value="800"></td>
                    <td><input name="canteen_allowance[<?= $id ?>]" class="form-control comp canteen" type="number" value="600"></td>
                    <td><input name="other_allowances[<?= $id ?>]" class="form-control comp other" type="number" value="400"></td>

                    <td><input name="gross_salary[<?= $id ?>]" class="form-control total gross" type="text" readonly></td>
                    
                    <td><input name="epf_employer[<?= $id ?>]" class="form-control comp epf_emp" type="number" value="1800"></td>
                    <td><input name="esic_employer[<?= $id ?>]" class="form-control comp esic_emp" type="number" value="500"></td>
                    <td><input name="retention_bonus[<?= $id ?>]" class="form-control comp retention" type="number" value="1000"></td>
                    <td><input name="gratuity[<?= $id ?>]" class="form-control comp gratuity" type="number" value="1500"></td>
                    <td><input name="leave_encashment[<?= $id ?>]" class="form-control comp leave_encash" type="number" value="1200"></td>
                    <td><input name="total_retentions[<?= $id ?>]" class="form-control total retention_total" type="text" readonly></td>
                    <td><input name="total_ctc[<?= $id ?>]" class="form-control total total_ctc" type="text" readonly></td>

                    <td><input name="epf_employee[<?= $id ?>]" class="form-control comp epf_employee" type="number" value="1800"></td>
                    <td><input name="professional_tax[<?= $id ?>]" class="form-control comp prof_tax" type="number" value="200"></td>
                    <td><input name="income_tax[<?= $id ?>]" class="form-control comp income_tax" type="number" value="1000"></td>
                    <td><input name="insurance_premium[<?= $id ?>]" class="form-control comp insurance" type="number" value="600"></td>
                    <td><input name="advance[<?= $id ?>]" class="form-control comp advance" type="number" value="1000"></td>
                    <td><input name="other_deductions[<?= $id ?>]" class="form-control comp other_deduct" type="number" value="400"></td>
                    <td><input name="total_deductions[<?= $id ?>]" class="form-control total total_deduct" type="text" readonly></td>
                    
                    <td><input name="present_days[<?= $id ?>]" class="form-control present" type="number" value="<?= $present ?>"></td>
                    <input type="hidden" name="employee_ids[]" value="<?= $id ?>">
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="text-end mt-3">
        <button type="submit" class="btn btn-success">Save Salary Summary</button>
    </div>
</form>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const workingDays = <?= $total_working_days ?>;
    
    function recalculateRow(row) {
        const presentDays = parseFloat(row.querySelector(".present").value) || 0;
        let gross = 0, retentions = 0, deductions = 0;

        row.querySelectorAll(".comp").forEach(input => {
            const fullValue = parseFloat(input.value) || 0;
            const dailyValue = workingDays ? (fullValue / workingDays) : 0;
            const adjusted = dailyValue * presentDays;
            input.setAttribute('data-full', fullValue);
            input.setAttribute('data-perday', dailyValue.toFixed(2));
            input.setAttribute('data-adjusted', adjusted.toFixed(2));
        });

        const grossFields = [".basic", ".da", ".hra", ".conveyance", ".special", ".perf_bonus", ".medical", ".washing", ".canteen", ".other"];
        grossFields.forEach(cls => {
            const input = row.querySelector(cls);
            gross += parseFloat(input?.getAttribute('data-adjusted') || 0);
        });
        row.querySelector(".gross").value = gross.toFixed(2);

        const retFields = [".epf_emp", ".esic_emp", ".retention", ".gratuity", ".leave_encash"];
        retFields.forEach(cls => {
            const input = row.querySelector(cls);
            retentions += parseFloat(input?.getAttribute('data-adjusted') || 0);
        });
        row.querySelector(".retention_total").value = retentions.toFixed(2);
        row.querySelector(".total_ctc").value = (gross + retentions).toFixed(2);

        const dedFields = [".epf_employee", ".prof_tax", ".income_tax", ".insurance", ".advance", ".other_deduct"];
        dedFields.forEach(cls => {
            const input = row.querySelector(cls);
            deductions += parseFloat(input?.getAttribute('data-adjusted') || 0);
        });
        row.querySelector(".total_deduct").value = deductions.toFixed(2);
    }

    document.querySelectorAll("tbody tr").forEach(row => {
        row.querySelectorAll(".present, .comp").forEach(input => {
            input.addEventListener("input", () => recalculateRow(row));
        });
        recalculateRow(row); // Initial calculation
    });
});
</script>

<?php include("footer.php"); ?>
