<?php
include 'header.php';
require 'db_connection.php';

// Fetch offices
$offices_query = $conn->query("SELECT office_name, state_name FROM offices ORDER BY office_name ASC");
$offices = $offices_query->fetch_all(MYSQLI_ASSOC);

// Handle filters
$selected_office = $_GET['office'] ?? '';
$selected_year = $_GET['year'] ?? date('Y');
$selected_month = $_GET['month'] ?? '';

// Filter Conditions
$conditions = "WHERE e.status = 'Active'";
if (!empty($selected_office)) {
    $conditions .= " AND e.office = '" . $conn->real_escape_string($selected_office) . "'";
}
$conditions .= " AND s.year = '" . $conn->real_escape_string($selected_year) . "'";
if (!empty($selected_month)) {
    $conditions .= " AND s.month = '" . $conn->real_escape_string($selected_month) . "'";
}

// Fetch salary summary data
$query = "
    SELECT 
        e.name AS employee_name,
        e.office,
        s.year,
        s.month,
        s.basic,
        s.total_deductions,
        s.net_salary
    FROM salary s
    JOIN employees e ON s.employee_id = e.id
    $conditions
    ORDER BY e.name ASC
";

$result = $conn->query($query);
?>

<div class="container mt-4">
    <h4>Salary Summary</h4>

    <form method="GET" class="row g-3 mb-3">
        <div class="col-md-4">
            <label for="office">Select Office</label>
            <select name="office" id="office" class="form-control">
                <option value="">All Offices</option>
                <?php foreach ($offices as $office):
                    $office_val = urlencode($office['office_name'] . "_" . $office['state_name']);
                    ?>
                    <option value="<?= $office_val ?>" <?= ($selected_office == $office_val) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($office['office_name']) ?> (<?= htmlspecialchars($office['state_name']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label for="year">Select Year</label>
            <select name="year" class="form-control">
                <?php
                for ($y = date('Y'); $y >= 2020; $y--) {
                    echo "<option value='$y' " . ($selected_year == $y ? 'selected' : '') . ">$y</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-md-3">
            <label for="month">Select Month</label>
            <select name="month" class="form-control">
                <option value="">All Months</option>
                <?php
                for ($m = 1; $m <= 12; $m++) {
                    $month = str_pad($m, 2, '0', STR_PAD_LEFT);
                    echo "<option value='$month' " . ($selected_month == $month ? 'selected' : '') . ">" . date("F", mktime(0, 0, 0, $m, 10)) . "</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-md-2 align-self-end">
            <button class="btn btn-primary w-100">Filter</button>
        </div>
    </form>

    <div class="mb-3">
        <button class="btn btn-success" onclick="downloadCSV()">Download CSV</button>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered" id="salaryTable">
            <thead class="table-dark">
                <tr>
                    <th>Employee Name</th>
                    <th>Office</th>
                    <th>Year</th>
                    <th>Month</th>
                    <th>Basic Salary</th>
                    <th>Total Deductions</th>
                    <th>Net Salary</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): 
                    while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['employee_name']) ?></td>
                            <td><?= htmlspecialchars($row['office']) ?></td>
                            <td><?= $row['year'] ?></td>
                            <td><?= date("F", mktime(0, 0, 0, $row['month'], 10)) ?></td>
                            <td><?= number_format($row['basic'], 2) ?></td>
                            <td><?= number_format($row['total_deductions'], 2) ?></td>
                            <td><?= number_format($row['net_salary'], 2) ?></td>
                        </tr>
                    <?php endwhile;
                else: ?>
                    <tr>
                        <td colspan="7" class="text-center">No salary data found for the selected filters.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function downloadCSV() {
    let table = document.getElementById("salaryTable");
    let rows = table.querySelectorAll("tr");
    let csv = [];

    for (let row of rows) {
        let cols = row.querySelectorAll("th, td");
        let rowData = [];
        for (let col of cols) {
            rowData.push('"' + col.innerText.replace(/"/g, '""') + '"');
        }
        csv.push(rowData.join(","));
    }

    let csvContent = csv.join("\n");
    let blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
    let url = URL.createObjectURL(blob);
    let link = document.createElement("a");

    link.setAttribute("href", url);
    link.setAttribute("download", "salary_summary.csv");
    link.click();
}
</script>

<?php include 'footer.php'; ?>
