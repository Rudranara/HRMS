<?php
include 'header.php';
require 'db_connection.php';
// Fetch employees
$employee_result = $conn->query("SELECT id, name FROM employees WHERE status = 'Active' ORDER BY name");
$employees = $employee_result->fetch_all(MYSQLI_ASSOC);
// Filters
$selected_employee = $_GET['employee'] ?? '';
$selected_year = $_GET['year'] ?? date('Y');
// Fetch total salary all time and for selected year
$total_all_time = 0;
$total_selected_year = 0;
$monthly_salaries = array_fill(1, 12, 0);
$months = [
    1 => "January", 2 => "February", 3 => "March", 4 => "April",
    5 => "May", 6 => "June", 7 => "July", 8 => "August",
    9 => "September", 10 => "October", 11 => "November", 12 => "December"
];
if (!empty($selected_employee)) {
    $stmt = $conn->prepare("SELECT year, month, net_salary FROM salary WHERE employee_id = ? ORDER BY year, month");
    $stmt->bind_param("i", $selected_employee);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $total_all_time += $row['net_salary'];
        if ($row['year'] == $selected_year) {
            $monthly_salaries[(int)$row['month']] += $row['net_salary'];
            $total_selected_year += $row['net_salary'];
        }
    }
}
?>
<style>
.salary-yearly-page {
    padding-top: 2rem;
    padding-bottom: 2.5rem;
}

.salary-yearly-nav {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.9rem;
    margin-bottom: 1.5rem;
}

.salary-yearly-tab {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 54px;
    padding: 0.95rem 1.15rem;
    border: 1px solid #dde5ef;
    border-radius: 18px;
    background: linear-gradient(180deg, #ffffff 0%, #f7f9fc 100%);
    color: #334155;
    font-size: 0.84rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    text-decoration: none;
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
    transition: all 0.2s ease;
}

.salary-yearly-tab:hover {
    color: #0f172a;
    border-color: #cfd8e3;
    transform: translateY(-1px);
}

.salary-yearly-tab.is-active {
    background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
    border-color: #111827;
    color: #ffffff;
    box-shadow: 0 24px 50px rgba(15, 23, 42, 0.18);
}

.salary-yearly-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.25rem;
}

.salary-yearly-title {
    margin: 0;
    color: #0f172a;
    font-size: 1.45rem;
    font-weight: 800;
    letter-spacing: -0.03em;
}

.salary-yearly-filter-card,
.salary-yearly-summary-card,
.salary-yearly-chart-card {
    border: 1px solid #e5eaf1;
    border-radius: 26px;
    background: #ffffff;
    box-shadow: 0 28px 60px rgba(15, 23, 42, 0.07);
}

.salary-yearly-filter-card {
    padding: 1.25rem;
    margin-bottom: 1.5rem;
}

.salary-yearly-filter-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.55fr) minmax(190px, 1.05fr) auto;
    gap: 0.85rem;
    align-items: end;
}

.salary-yearly-field {
    min-width: 0;
}

.salary-yearly-field label {
    display: block;
    margin-bottom: 0.45rem;
    color: #64748b;
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.09em;
    text-transform: uppercase;
}

.salary-yearly-field .form-control {
    min-height: 44px;
    border: 1px solid #d7deea;
    border-radius: 14px;
    padding: 0.65rem 0.85rem;
    color: #0f172a;
    background: #f8fafc;
    box-shadow: none;
    font-size: 0.92rem;
}

.salary-yearly-field .form-control:focus {
    border-color: #9aa8bc;
    background: #ffffff;
    box-shadow: 0 0 0 0.2rem rgba(148, 163, 184, 0.16);
}

.salary-yearly-filter-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 48px;
    min-width: 140px;
    padding: 0.78rem 1.35rem;
    border-radius: 14px;
    border: none;
    background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
    color: #ffffff;
    box-shadow: 0 18px 32px rgba(15, 23, 42, 0.18);
    font-size: 0.77rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.salary-yearly-summary-card {
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.salary-yearly-metrics {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
    margin-bottom: 1.4rem;
}

.salary-yearly-metric {
    border: 1px solid #e7edf5;
    border-radius: 20px;
    background: linear-gradient(180deg, #fbfdff 0%, #f8fafc 100%);
    padding: 1.15rem 1.2rem;
}

.salary-yearly-metric-label {
    display: block;
    margin-bottom: 0.45rem;
    color: #64748b;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.salary-yearly-metric-value {
    margin: 0;
    color: #0f172a;
    font-size: 1.35rem;
    font-weight: 800;
    letter-spacing: -0.03em;
}

.salary-yearly-breakdown-title,
.salary-yearly-chart-title {
    margin: 0 0 1rem;
    color: #111827;
    font-size: 1rem;
    font-weight: 800;
    letter-spacing: -0.02em;
}

.salary-yearly-table-wrap {
    overflow-x: auto;
}

.salary-yearly-table {
    width: 100%;
    margin-bottom: 0;
}

.salary-yearly-table thead th {
    border-bottom: 1px solid #e8edf3;
    background: #f8fafc;
    color: #64748b;
    font-size: 0.73rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    padding: 0.95rem 0.85rem;
    white-space: nowrap;
}

.salary-yearly-table tbody td {
    padding: 1rem 0.85rem;
    border-bottom: 1px solid #eef2f7;
    color: #1f2937;
    font-size: 0.92rem;
    vertical-align: middle;
    white-space: nowrap;
}

.salary-yearly-table tbody tr:last-child td {
    border-bottom: none;
}

.salary-yearly-table tbody tr:hover {
    background: #f8fafc;
}

.salary-yearly-chart-card {
    padding: 1.4rem 1.5rem 1.2rem;
}

.salary-yearly-chart-wrap {
    position: relative;
    min-height: 320px;
}

@media (max-width: 991.98px) {
    .salary-yearly-nav,
    .salary-yearly-filter-grid,
    .salary-yearly-metrics {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 767.98px) {
    .salary-yearly-page {
        padding-top: 1.25rem;
    }

    .salary-yearly-header,
    .salary-yearly-nav,
    .salary-yearly-filter-grid,
    .salary-yearly-metrics {
        grid-template-columns: 1fr;
        flex-direction: column;
        align-items: flex-start;
    }

    .salary-yearly-filter-card,
    .salary-yearly-summary-card,
    .salary-yearly-chart-card {
        padding-left: 1rem;
        padding-right: 1rem;
    }

    .salary-yearly-chart-wrap {
        min-height: 280px;
    }
}
</style>

<div class="container-fluid salary-yearly-page">
    <div class="salary-yearly-nav">
        <a href="all_employees_attendance" class="salary-yearly-tab">Attendance Summary</a>
        <a href="employee_yearly_attendance" class="salary-yearly-tab">Yearly Summary</a>
        <a href="leave_summary" class="salary-yearly-tab">Leave Summary</a>
        <a href="yearly_salary_summary" class="salary-yearly-tab is-active">Salary Summary</a>
    </div>

    <div class="salary-yearly-header">
        <h6 class="salary-yearly-title">Yearly Salary Summary</h6>
    </div>

    <form method="GET" class="salary-yearly-filter-card">
        <div class="salary-yearly-filter-grid">
            <div class="salary-yearly-field">
            <label>Select Employee</label>
            <select name="employee" class="form-control" required>
                <option value="">-- Select Employee --</option>
                <?php foreach ($employees as $emp): ?>
                    <option value="<?= $emp['id'] ?>" <?= ($emp['id'] == $selected_employee) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($emp['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            </div>
            <div class="salary-yearly-field">
            <label>Select Year</label>
            <select name="year" class="form-control" required>
                <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                    <option value="<?= $y ?>" <?= ($y == $selected_year) ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
            </div>
            <div>
                <button class="salary-yearly-filter-btn">View Report</button>
            </div>

        </div>
    </form>

            <?php if (!empty($selected_employee)): ?>
        <div class="salary-yearly-summary-card">
            <div class="salary-yearly-metrics">
                <div class="salary-yearly-metric">
                    <span class="salary-yearly-metric-label">Total Salary Generated (All Time)</span>
                    <h5 class="salary-yearly-metric-value">₹<?= number_format($total_all_time, 2) ?></h5>
                </div>
                <div class="salary-yearly-metric">
                    <span class="salary-yearly-metric-label">Total Salary In <?= $selected_year ?></span>
                    <h6 class="salary-yearly-metric-value">₹<?= number_format($total_selected_year, 2) ?></h6>
                </div>
            </div>
            
            <div class="salary-yearly-table-wrap">
            <h6 class="salary-yearly-breakdown-title">Month-wise Breakdown (<?= $selected_year ?>)</h6>
                    <!-- Summary Table -->
                    <table class="table align-items-center salary-yearly-table">

                    <thead>
                    <tr>
                        <th>Month</th>
                        <th>Net Salary (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($monthly_salaries as $month_num => $amount):
                    ?>
                        <tr>
                            <td><?= $months[$month_num] ?></td>
                            <td><?= number_format($amount, 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            </div>
            <?php endif; ?>

            <div class="salary-yearly-chart-card">
                <h6 class="salary-yearly-chart-title">Monthly Net Salary (Bar Chart)</h6>
                <div class="salary-yearly-chart-wrap">
                    <canvas id="barChart"></canvas>
                </div>
            </div>
    </div>
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const monthlyData = <?= json_encode(array_values($monthly_salaries)) ?>;
const monthLabels = <?= json_encode(array_values($months)) ?>;

new Chart(document.getElementById("barChart"), {
    type: 'bar',
    data: {
        labels: monthLabels,
        datasets: [{
            label: "Net Salary (₹)",
            data: monthlyData,
            backgroundColor: 'rgba(29, 78, 216, 0.72)',
            borderRadius: 10,
            borderWidth: 0,
            maxBarThickness: 34
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            title: { display: false }
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { color: '#64748b' }
            },
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(148, 163, 184, 0.18)' },
                ticks: { color: '#64748b' }
            }
        }
    }
});
</script>

<?php include 'footer.php'; ?>

