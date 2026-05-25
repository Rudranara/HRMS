<?php
include("header.php");


/* ===============================
   MONTH & YEAR SELECTION
================================ */

$current_month = date('m');
$current_year  = date('Y');

$selected_month = $_GET['month'] ?? $current_month;
$selected_year  = $_GET['year'] ?? $current_year;

$start_date = "{$selected_year}-{$selected_month}-01 00:00:00";
$end_date   = date("Y-m-t 23:59:59", strtotime($start_date));

/* ===============================
   CALCULATE WORKING DAYS
   (Monday to Saturday)
================================ */

$total_days = cal_days_in_month(CAL_GREGORIAN, $selected_month, $selected_year);
$total_working_days = 0;

$holiday_query = $conn->prepare("\n    SELECT start_date\n    FROM events\n    WHERE YEAR(start_date) = ? AND MONTH(start_date) = ?\n");
$holiday_query->bind_param("ii", $selected_year, $selected_month);
$holiday_query->execute();
$holidays = $holiday_query->get_result()->fetch_all(MYSQLI_ASSOC);
$holiday_dates = array_column($holidays, 'start_date');

for ($d = 1; $d <= $total_days; $d++) {
    $date = sprintf('%04d-%02d-%02d', $selected_year, $selected_month, $d);
    $day_of_week = date('N', strtotime($date)); // 1 (Mon) - 7 (Sun)

    if ($day_of_week < 7 && !in_array($date, $holiday_dates, true)) {
        $total_working_days++;
    }
}

/* ===============================
   COMPANY DAILY HOURS
================================ */

$daily_working_hours = 9; 
$expected_monthly_hours = $total_working_days * $daily_working_hours;

/* ===============================
   FETCH EMPLOYEE WORKING HOURS
================================ */

$stmt = $conn->prepare("
    SELECT 
        e.id,
        e.name,
        e.employee_id,
        e.office,
        COALESCE(SUM(a.working_hours), 0) AS actual_hours
    FROM employees e
    LEFT JOIN attendance a 
        ON a.employee_id = e.id
        AND a.status = 'Present'
        AND a.punch_in_time BETWEEN ? AND ?
    WHERE e.status = 'Active'
    GROUP BY e.id
    ORDER BY e.name ASC
");

$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

/* ===============================
   GRAND TOTAL CALCULATION
================================ */

$grand_total_actual = 0;
?>

<style>
.working-hours-page {
    width: 100%;
    max-width: 1640px;
    margin: 0 auto;
    padding-top: 2rem;
    padding-bottom: 2.5rem;
}

.working-hours-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.4rem;
}

.working-hours-title {
    margin: 0;
    color: #0f172a;
    font-size: 1.45rem;
    font-weight: 800;
    letter-spacing: -0.03em;
}

.working-hours-filter-card,
.working-hours-summary-card,
.working-hours-table-card {
    width: 100%;
    border: 1px solid #e5eaf1;
    border-radius: 26px;
    background: #ffffff;
    box-shadow: 0 28px 60px rgba(15, 23, 42, 0.07);
    box-sizing: border-box;
}

.working-hours-filter-card {
    padding: 1.25rem;
    margin-bottom: 1.5rem;
}

.working-hours-filter-grid {
    display: grid;
    grid-template-columns: minmax(180px, 1fr) minmax(180px, 1fr) auto;
    gap: 0.85rem;
    align-items: end;
}

.working-hours-field {
    min-width: 0;
}

.working-hours-field label {
    display: block;
    margin-bottom: 0.45rem;
    color: #64748b;
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.09em;
    text-transform: uppercase;
}

.working-hours-field .form-control {
    min-height: 44px;
    border: 1px solid #d7deea;
    border-radius: 14px;
    padding: 0.65rem 0.85rem;
    color: #0f172a;
    background: #f8fafc;
    box-shadow: none;
    font-size: 0.92rem;
}

.working-hours-field .form-control:focus {
    border-color: #9aa8bc;
    background: #ffffff;
    box-shadow: 0 0 0 0.2rem rgba(148, 163, 184, 0.16);
}

.working-hours-filter-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 48px;
    min-width: 128px;
    padding: 0.78rem 1.3rem;
    border: none;
    border-radius: 14px;
    background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
    color: #ffffff;
    box-shadow: 0 18px 32px rgba(15, 23, 42, 0.18);
    font-size: 0.77rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.working-hours-summary-card {
    padding: 1.3rem;
    margin-bottom: 0.08rem;
}

.working-hours-summary-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1rem;
}

.working-hours-stat {
    border: 1px solid #e8edf3;
    border-radius: 20px;
    background: linear-gradient(180deg, #fbfdff 0%, #f8fafc 100%);
    padding: 1rem 1.1rem;
}

.working-hours-stat-label {
    display: block;
    margin-bottom: 0.55rem;
    color: #64748b;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.working-hours-stat-value {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 42px;
    padding: 0.5rem 0.85rem;
    border-radius: 14px;
    font-size: 0.95rem;
    font-weight: 800;
    letter-spacing: -0.02em;
}

.working-hours-stat-value.value-dark {
    background: #eff3f8;
    color: #334155;
}

.working-hours-stat-value.value-warning {
    background: #fff1cf;
    color: #9a6700;
}

.working-hours-stat-value.value-info {
    background: #eaf2ff;
    color: #275ea8;
}

.working-hours-table-card {
    overflow: hidden;
    margin-top: 0.75rem;
}

.working-hours-table-head {
    padding: 1.3rem 1.5rem 0;
}

.working-hours-table-title {
    margin: 0;
    color: #111827;
    font-size: 1rem;
    font-weight: 800;
    letter-spacing: -0.02em;
}

.working-hours-table-wrap {
    overflow-x: auto;
    padding: 0 1.5rem 1.5rem;
}

.working-hours-table {
    width: 100%;
    margin-bottom: 0;
}

.working-hours-table thead th {
    border-bottom: 1px solid #e8edf3;
    background: #f8fafc;
    color: #64748b;
    font-size: 0.73rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    padding: 1rem 0.95rem;
    white-space: nowrap;
}

.working-hours-table tbody td,
.working-hours-table tfoot th,
.working-hours-table tfoot td {
    padding: 1rem 0.95rem;
    border-bottom: 1px solid #eef2f7;
    color: #1f2937;
    font-size: 0.92rem;
    vertical-align: middle;
}

.working-hours-table tbody tr:last-child td {
    border-bottom: none;
}

.working-hours-table tbody tr:hover {
    background: #f8fafc;
}

.working-hours-person-name {
    margin: 0;
    color: #0f172a;
    font-size: 0.95rem;
    font-weight: 700;
}

.working-hours-person-meta {
    margin: 0.22rem 0 0;
    color: #64748b;
    font-size: 0.82rem;
}

.working-hours-office-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.38rem 0.72rem;
    border-radius: 999px;
    background: #eff3f8;
    color: #334155;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.working-hours-diff-positive {
    color: #15803d;
    font-weight: 800;
}

.working-hours-diff-negative {
    color: #b42318;
    font-weight: 800;
}

.working-hours-total-row th,
.working-hours-total-row td {
    background: #f8fafc;
}

.working-hours-total-value {
    color: #275ea8;
    font-weight: 800;
}

@media (max-width: 991.98px) {
    .working-hours-summary-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 767.98px) {
    .working-hours-page {
        max-width: 100%;
        padding-top: 1.25rem;
    }

    .working-hours-filter-grid {
        grid-template-columns: 1fr;
    }

    .working-hours-filter-card,
    .working-hours-table-wrap,
    .working-hours-table-head {
        padding-left: 1rem;
        padding-right: 1rem;
    }
}
</style>

<div class="container-fluid working-hours-page">

    <!-- PAGE TITLE -->
    <div class="row">
        <div class="col-12">
            <div class="working-hours-header">
            <h5 class="working-hours-title">
                Monthly Working Hours Report
                (<?= date("F Y", strtotime($start_date)) ?>)
            </h5>
            </div>
        </div>
    </div>

    <!-- FILTER -->
    <div class="working-hours-filter-card">
            <form method="GET" class="working-hours-filter-grid">

                <div class="working-hours-field">
                    <label>Select Month</label>
                    <select name="month" class="form-control">
                        <?php for ($m = 1; $m <= 12; $m++): 
                            $val = str_pad($m, 2, '0', STR_PAD_LEFT);
                        ?>
                            <option value="<?= $val ?>" 
                                <?= $selected_month == $val ? 'selected' : '' ?>>
                                <?= date("F", mktime(0,0,0,$m,10)) ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="working-hours-field">
                    <label>Select Year</label>
                    <select name="year" class="form-control">
                        <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                            <option value="<?= $y ?>" 
                                <?= $selected_year == $y ? 'selected' : '' ?>>
                                <?= $y ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div>
                    <button type="submit" class="working-hours-filter-btn">
                        Filter
                    </button>
                </div>

            </form>
    </div>

    <!-- SUMMARY CARD -->
    <div class="working-hours-summary-card">
        <div class="working-hours-summary-grid">

            <div class="working-hours-stat">
                <span class="working-hours-stat-label">Total Working Days</span>
                <span class="working-hours-stat-value value-dark">
                    <?= $total_working_days ?>
                </span>
            </div>

            <div class="working-hours-stat">
                <span class="working-hours-stat-label">Daily Working Hours</span>
                <span class="working-hours-stat-value value-warning">
                    <?= $daily_working_hours ?> hrs
                </span>
            </div>

            <div class="working-hours-stat">
                <span class="working-hours-stat-label">Expected Monthly Hours</span>
                <span class="working-hours-stat-value value-info">
                    <?= $expected_monthly_hours ?> hrs
                </span>
            </div>

        </div>
    </div>

    <!-- EMPLOYEE TABLE -->
    <div class="working-hours-table-card">
        <div class="working-hours-table-head">
            <h6 class="working-hours-table-title">Employee Monthly Working Hours</h6>
        </div>

            <div class="working-hours-table-wrap">

                <table class="table align-items-center working-hours-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Office</th>
                            <th class="text-center">Expected Hours</th>
                            <th class="text-center">Actual Hours</th>
                            <th class="text-center">Difference</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php while ($row = $result->fetch_assoc()): 
                        
                        $actual = round($row['actual_hours'], 2);
                        $difference = round($actual - $expected_monthly_hours, 2);
                        $grand_total_actual += $actual;
                    ?>

                        <tr>
                            <td>
                                <div class="d-flex flex-column justify-content-center">
                                        <h6 class="working-hours-person-name">
                                            <?= htmlspecialchars($row['name']) ?>
                                        </h6>
                                        <p class="working-hours-person-meta mb-0">
                                            <?= htmlspecialchars($row['employee_id']) ?>
                                        </p>
                                </div>
                            </td>

                            <td>
                                <span class="working-hours-office-badge">
                                    <?= htmlspecialchars($row['office']) ?>
                                </span>
                            </td>

                            <td class="text-center">
                                <?= $expected_monthly_hours ?> hrs
                            </td>

                            <td class="text-center">
                                <?= $actual ?> hrs
                            </td>

                            <td class="text-center">
                                <?php if ($difference >= 0): ?>
                                    <span class="working-hours-diff-positive">
                                        +<?= $difference ?> hrs
                                    </span>
                                <?php else: ?>
                                    <span class="working-hours-diff-negative">
                                        <?= $difference ?> hrs
                                    </span>
                                <?php endif; ?>
                            </td>

                        </tr>

                    <?php endwhile; ?>

                    </tbody>

                    <!-- GRAND TOTAL ROW -->
                    <tfoot>
                        <tr class="working-hours-total-row">
                            <th colspan="3" class="text-end">
                                Total Actual Hours (All Employees):
                            </th>
                            <th class="text-center working-hours-total-value">
                                <?= round($grand_total_actual,2) ?> hrs
                            </th>
                            <th></th>
                        </tr>
                    </tfoot>

                </table>

            </div>
    </div>

</div>

<?php include("footer.php"); ?>
