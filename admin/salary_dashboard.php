<?php
include 'header.php';
require 'db_connection.php';

$selected_office = $_GET['office'] ?? '';
$selected_year = $_GET['year'] ?? date('Y');
$selected_month = $_GET['month'] ?? '';

$office_condition = "";
if (!empty($selected_office)) {
    $office_condition = " AND e.office = '$selected_office'";
}

$prev_year = $selected_year - 1;
$prev_month = (!empty($selected_month) && $selected_month != 1) ? $selected_month - 1 : 12;
$prev_month_year = (!empty($selected_month) && $selected_month != 1) ? $selected_year : $selected_year - 1;

$offices = $conn->query("SELECT office_name, state_name FROM offices ORDER BY office_name ASC")->fetch_all(MYSQLI_ASSOC);

function getSalaryData($year, $month, $conn, $office_condition) {
    $month_condition = (!empty($month)) ? "AND s.month = '$month'" : "";
    $query = "
        SELECT 
            SUM(s.basic) AS total_basic,
            SUM(s.net_salary) AS total_net,
            SUM(s.total_deductions) AS alltotal_deduction
        FROM salary s
        JOIN employees e ON s.employee_id = e.id
        WHERE s.year = '$year' $month_condition $office_condition
    ";
    return $conn->query($query)->fetch_assoc();
}

$current_data = getSalaryData($selected_year, $selected_month, $conn, $office_condition);
$previous_month_data = (!empty($selected_month)) ? getSalaryData($prev_month_year, $prev_month, $conn, $office_condition) : ['total_net' => 0];
$previous_year_data = getSalaryData($prev_year, $selected_month, $conn, $office_condition);

// Get monthly salary for the year
$monthly_data = [];
for ($m = 1; $m <= 12; $m++) {
    $result = getSalaryData($selected_year, $m, $conn, $office_condition);
    $monthly_data[] = $result['total_net'] ?? 0;
}
?>

<style>
.salary-dashboard-page {
    background:
        radial-gradient(circle at top right, rgba(15, 23, 42, 0.05), transparent 24%),
        linear-gradient(180deg, #f6f7f9 0%, #f2f4f7 100%);
}

.salary-dashboard-tabs {
    margin-bottom: 1.1rem;
}

.salary-dashboard-tab-row {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.9rem;
}

.salary-dashboard-tab {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 52px;
    padding: 0.8rem 1.15rem;
    border-radius: 20px;
    border: 1px solid #d7e0eb;
    background: #ffffff;
    color: #27466a;
    font-size: 0.88rem;
    font-weight: 800;
    letter-spacing: 0.02em;
    text-transform: uppercase;
    text-decoration: none;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
    transition: all 0.18s ease;
}

.salary-dashboard-tab:hover {
    color: #16324f;
    border-color: #c6d3e0;
    background: #f8fafc;
}

.salary-dashboard-tab.is-active {
    color: #ffffff;
    border-color: #1c2432;
    background: linear-gradient(135deg, #172030 0%, #222c3d 100%);
    box-shadow: 0 16px 30px rgba(17, 24, 39, 0.18);
}

.salary-dashboard-tab.is-active:hover {
    color: #ffffff;
    background: linear-gradient(135deg, #172030 0%, #222c3d 100%);
}

.salary-dashboard-title {
    margin: 0 0 1rem;
    color: #111827;
    font-size: 1.6rem;
    font-weight: 800;
    letter-spacing: -0.02em;
}

.salary-dashboard-filter-card,
.salary-dashboard-stat,
.salary-dashboard-chart-card {
    border: 1px solid rgba(107, 114, 128, 0.14);
    border-radius: 24px;
    background: rgba(255, 255, 255, 0.96);
    box-shadow: 0 18px 42px rgba(15, 23, 42, 0.07);
}

.salary-dashboard-filter-card {
    margin-bottom: 1.2rem;
    padding: 1.05rem;
    background: linear-gradient(180deg, #fafbfc 0%, #f7f9fb 100%);
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.9), 0 14px 32px rgba(15, 23, 42, 0.05);
}

.salary-dashboard-page label {
    display: block;
    margin-bottom: 0.45rem;
    color: #6b7280;
    font-size: 0.76rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
}

.salary-dashboard-page .form-control,
.salary-dashboard-page select.form-control {
    min-height: 44px;
    border: 1px solid #d8dee7;
    border-radius: 14px;
    background: #ffffff;
    box-shadow: none;
    color: #1f2937;
    padding: 0.65rem 0.9rem;
}

.salary-dashboard-page .form-control:focus,
.salary-dashboard-page select.form-control:focus {
    border-color: #16324f;
    box-shadow: 0 0 0 0.18rem rgba(22, 50, 79, 0.12);
}

.salary-dashboard-apply {
    min-height: 44px;
    border-radius: 14px;
    font-size: 0.82rem;
    font-weight: 700;
    background: linear-gradient(135deg, #171717 0%, #2f2f2f 100%) !important;
    border: 1px solid #171717 !important;
    color: #ffffff !important;
    box-shadow: none;
}

.salary-dashboard-apply:hover {
    background: linear-gradient(135deg, #111111 0%, #252525 100%) !important;
    color: #ffffff !important;
}

.salary-dashboard-stats {
    margin-bottom: 1.3rem;
}

.salary-dashboard-stat {
    height: 100%;
    padding: 1.1rem 1.15rem;
}

.salary-dashboard-stat-label {
    display: block;
    margin-bottom: 0.45rem;
    color: #6b7280;
    font-size: 0.74rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
}

.salary-dashboard-stat-value {
    color: #111827;
    font-size: 1.2rem;
    font-weight: 800;
    line-height: 1.2;
}

.salary-dashboard-stat-value--split {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    font-size: 1rem;
}

.salary-dashboard-stat-primary {
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

.salary-dashboard-stat-success {
    background: linear-gradient(180deg, #f5fbf7 0%, #edf7f1 100%);
}

.salary-dashboard-stat-warning {
    background: linear-gradient(180deg, #fffaf0 0%, #fff4de 100%);
}

.salary-dashboard-stat-dark {
    background: linear-gradient(180deg, #f8fafc 0%, #eef2f6 100%);
}

.salary-dashboard-divider {
    border: 0;
    border-top: 1px solid #e5ebf2;
    margin: 0 0 1.35rem;
}

.salary-dashboard-chart-card {
    padding: 1rem 1.1rem 1.15rem;
    height: 100%;
}

.salary-dashboard-chart-title {
    margin: 0 0 0.85rem;
    color: #111827;
    font-size: 0.95rem;
    font-weight: 800;
}

.salary-dashboard-chart-wrap {
    position: relative;
    min-height: 320px;
}

@media (max-width: 991.98px) {
    .salary-dashboard-filter-card .row {
        --bs-gutter-x: 0.85rem;
        --bs-gutter-y: 0.85rem;
    }
}

@media (max-width: 767.98px) {
    .salary-dashboard-tab-row {
        grid-template-columns: 1fr;
    }

    .salary-dashboard-filter-card,
    .salary-dashboard-stat,
    .salary-dashboard-chart-card {
        border-radius: 20px;
    }

    .salary-dashboard-filter-card {
        padding: 0.9rem;
    }

    .salary-dashboard-chart-wrap {
        min-height: 260px;
    }
}
</style>

<div class="container-fluid container-fluid-main salary-dashboard-page py-4">
    <div class="salary-dashboard-tabs">
        <div class="salary-dashboard-tab-row">
            <a href="all_employees_attendance" class="salary-dashboard-tab">Attendance Summary</a>
            <a href="employee_yearly_attendance" class="salary-dashboard-tab">Yearly Summary</a>
            <a href="leave_summary" class="salary-dashboard-tab">Leave Summary</a>
            <a href="yearly_salary_summary" class="salary-dashboard-tab is-active">Salary Summary</a>
        </div>
    </div>
    <h4 class="salary-dashboard-title">Salary Dashboard</h4>
    <div class="salary-dashboard-filter-card">
    <form method="GET" class="row mb-0 align-items-end">
        <div class="col-md-4">
            <label>Office</label>
            <select name="office" class="form-control">
                <option value="">All Offices</option>
                <?php foreach ($offices as $office): 
                    $value = $office['office_name'] . "_" . $office['state_name'];
                ?>
                    <option value="<?= $value ?>" <?= ($selected_office == $value) ? 'selected' : '' ?>>
                        <?= $office['office_name'] ?> (<?= $office['state_name'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label>Year</label>
            <select name="year" class="form-control">
                <?php for ($y = 2022; $y <= date('Y'); $y++): ?>
                    <option value="<?= $y ?>" <?= ($selected_year == $y) ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label>Month</label>
            <select name="month" class="form-control">
                <option value="">All</option>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= ($selected_month == $m) ? 'selected' : '' ?>>
                        <?= date("F", mktime(0, 0, 0, $m, 1)) ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label>&nbsp;</label>
            <button class="btn salary-dashboard-apply w-100">Apply</button>
        </div>
    </form>
    </div>
    <div class="row salary-dashboard-stats g-3">
        <div class="col-md-3">
            <div class="salary-dashboard-stat salary-dashboard-stat-primary">
                <span class="salary-dashboard-stat-label">Net Salary (Current)</span>
                <div class="salary-dashboard-stat-value">₹<?= number_format($current_data['total_net'] ?? 0) ?></div>
            </div>
        </div>
        <?php if (!empty($selected_month)): ?>
        <div class="col-md-3">
            <div class="salary-dashboard-stat salary-dashboard-stat-success">
                <span class="salary-dashboard-stat-label">Last Month</span>
                <div class="salary-dashboard-stat-value">₹<?= number_format($previous_month_data['total_net'] ?? 0) ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="salary-dashboard-stat salary-dashboard-stat-warning">
                <span class="salary-dashboard-stat-label">Last Year (Same Month)</span>
                <div class="salary-dashboard-stat-value">₹<?= number_format($previous_year_data['total_net'] ?? 0) ?></div>
            </div>
        </div>
        <?php endif; ?>
        <div class="col-md-<?= empty($selected_month) ? '9' : '3' ?>">
            <div class="salary-dashboard-stat salary-dashboard-stat-dark">
                <span class="salary-dashboard-stat-label">Basic And Deductions</span>
                <div class="salary-dashboard-stat-value salary-dashboard-stat-value--split">
                    <span>Basic: ₹<?= number_format($current_data['total_basic'] ?? 0) ?></span>
                    <span>Deductions: ₹<?= number_format($current_data['alltotal_deduction'] ?? 0) ?></span>
                </div>
            </div>
        </div>
    </div>
    <hr class="salary-dashboard-divider">
    <div class="row g-3">
        <div class="col-md-6">
            <div class="salary-dashboard-chart-card">
                <h6 class="salary-dashboard-chart-title">Current Breakdown</h6>
                <div class="salary-dashboard-chart-wrap">
                    <canvas id="pieChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="salary-dashboard-chart-card">
                <h6 class="salary-dashboard-chart-title">Monthly Net Salary</h6>
                <div class="salary-dashboard-chart-wrap">
                    <canvas id="barChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const pieCtx = document.getElementById('pieChart').getContext('2d');
const barCtx = document.getElementById('barChart').getContext('2d');
// Pie chart: Current month breakdown
new Chart(pieCtx, {
    type: 'pie',
    data: {
        labels: ['Basic', 'Deductions', 'Net'],
        datasets: [{
            label: 'Breakdown',
            data: [<?= $current_data['total_basic'] ?? 0 ?>, <?= $current_data['alltotal_deduction'] ?? 0 ?>, <?= $current_data['total_net'] ?? 0 ?>],
            backgroundColor: ['#9fd8c2', '#f0b8b5', '#9fb9d8'],
            borderColor: ['#7dbfa5', '#dd9791', '#7f9fc2'],
            borderWidth: 1
        }]
    },
    options: {
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
// Bar chart: Monthly net salary across the year
new Chart(barCtx, {
    type: 'bar',
    data: {
        labels: [<?= implode(',', array_map(fn($m) => "'" . date('M', mktime(0,0,0,$m,1)) . "'", range(1,12))) ?>],
        datasets: [{
            label: 'Net Salary (<?= $selected_year ?>)',
            data: <?= json_encode($monthly_data) ?>,
            backgroundColor: 'rgba(22, 50, 79, 0.72)',
            borderRadius: 8,
            maxBarThickness: 34
        }]
    },
    options: {
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    color: '#6b7280'
                }
            },
            y: {
                beginAtZero: true,
                grid: {
                    color: '#e8edf3'
                },
                ticks: {
                    color: '#6b7280'
                }
            }
        }
    }
});
</script>
<?php include 'footer.php'; ?>
