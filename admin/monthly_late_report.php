<?php
include("header.php");

/* ===============================
   FILTER SECTION
================================ */

$current_month = date('m');
$current_year  = date('Y');

$selected_month = $_GET['month'] ?? $current_month;
$selected_year  = $_GET['year'] ?? $current_year;
$selected_employee_id = trim($_GET['name'] ?? '');

$start_date = "{$selected_year}-{$selected_month}-01";
$end_date   = date("Y-m-t 23:59:59", strtotime($start_date));

$employee_names = [];
$employee_result = $conn->query("SELECT id, name FROM employees WHERE name IS NOT NULL AND name != '' ORDER BY name ASC");

if ($employee_result) {
    while ($employee_row = $employee_result->fetch_assoc()) {
        $employee_names[] = $employee_row;
    }
}

/* ===============================
   FETCH LATE RECORDS
================================ */

$late_query = "
    SELECT 
        e.name,
        e.employee_id AS emp_code,
        e.office,
        e.punchin_time AS expected_time,
        DATE(a.punch_in_time) AS late_date,
        TIME(a.punch_in_time) AS punch_time
    FROM attendance a
    INNER JOIN employees e ON e.id = a.employee_id
    WHERE TIME(a.punch_in_time) > e.punchin_time
      AND a.punch_in_time BETWEEN ? AND ?
      AND a.status = 'Present'
";

if ($selected_employee_id !== '') {
    $late_query .= " AND e.id = ?";
}

$late_query .= " ORDER BY e.name, a.punch_in_time ASC";

$stmt = $conn->prepare($late_query);

if ($selected_employee_id !== '') {
    $selected_employee_filter = (int) $selected_employee_id;
    $stmt->bind_param("ssi", $start_date, $end_date, $selected_employee_filter);
} else {
    $stmt->bind_param("ss", $start_date, $end_date);
}

$stmt->execute();
$result = $stmt->get_result();

/* ===============================
   COUNT LATE PER EMPLOYEE
================================ */

$count_query = "
    SELECT 
        e.id,
        e.name,
        e.employee_id,
        COUNT(*) AS late_count
    FROM attendance a
    INNER JOIN employees e ON e.id = a.employee_id
    WHERE TIME(a.punch_in_time) > e.punchin_time
      AND a.punch_in_time BETWEEN ? AND ?
      AND a.status = 'Present'
";

if ($selected_employee_id !== '') {
    $count_query .= " AND e.id = ?";
}

$count_query .= " GROUP BY e.id";

$count_stmt = $conn->prepare($count_query);

if ($selected_employee_id !== '') {
    $selected_employee_filter = (int) $selected_employee_id;
    $count_stmt->bind_param("ssi", $start_date, $end_date, $selected_employee_filter);
} else {
    $count_stmt->bind_param("ss", $start_date, $end_date);
}

$count_stmt->execute();
$count_result = $count_stmt->get_result();
?>

<style>
.late-report-page {
    padding-top: 2rem;
    padding-bottom: 2.5rem;
}

.late-report-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.4rem;
}

.late-report-title {
    margin: 0;
    color: #0f172a;
    font-size: 1.45rem;
    font-weight: 800;
    letter-spacing: -0.03em;
}

.late-report-filter-card,
.late-report-section-card {
    border: 1px solid #e5eaf1;
    border-radius: 26px;
    background: #ffffff;
    box-shadow: 0 28px 60px rgba(15, 23, 42, 0.07);
}

.late-report-filter-card {
    padding: 1.25rem;
    margin-bottom: 1.5rem;
}

.late-report-filter-grid {
    display: grid;
    grid-template-columns: minmax(180px, 1fr) minmax(180px, 1fr) minmax(220px, 1.2fr) auto;
    gap: 0.85rem;
    align-items: end;
}

.late-report-field {
    min-width: 0;
}

.late-report-field label {
    display: block;
    margin-bottom: 0.45rem;
    color: #64748b;
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.09em;
    text-transform: uppercase;
}

.late-report-field .form-control {
    min-height: 44px;
    border: 1px solid #d7deea;
    border-radius: 14px;
    padding: 0.65rem 0.85rem;
    color: #0f172a;
    background: #f8fafc;
    box-shadow: none;
    font-size: 0.92rem;
}

.late-report-field .form-control:focus {
    border-color: #9aa8bc;
    background: #ffffff;
    box-shadow: 0 0 0 0.2rem rgba(148, 163, 184, 0.16);
}

.late-report-filter-btn {
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

.late-report-section-card {
    overflow: hidden;
    margin-bottom: 1.5rem;
}

.late-report-section-head {
    padding: 1.3rem 1.5rem 0;
}

.late-report-section-title {
    margin: 0;
    color: #111827;
    font-size: 1rem;
    font-weight: 800;
    letter-spacing: -0.02em;
}

.late-report-table-wrap {
    overflow-x: auto;
    padding: 0 1.5rem 1.5rem;
}

.late-report-table {
    width: 100%;
    margin-bottom: 0;
}

.late-report-table thead th {
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

.late-report-table tbody td {
    padding: 1rem 0.95rem;
    border-bottom: 1px solid #eef2f7;
    color: #1f2937;
    font-size: 0.92rem;
    vertical-align: middle;
}

.late-report-table tbody tr:last-child td {
    border-bottom: none;
}

.late-report-table tbody tr:hover {
    background: #f8fafc;
}

.late-report-person-name {
    margin: 0;
    color: #0f172a;
    font-size: 0.95rem;
    font-weight: 700;
}

.late-report-person-meta {
    margin: 0.22rem 0 0;
    color: #64748b;
    font-size: 0.82rem;
}

.late-report-office-badge,
.late-report-time-badge,
.late-report-count-badge,
.late-report-empty-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.late-report-office-badge {
    margin-top: 0.5rem;
    padding: 0.34rem 0.7rem;
    background: #eff3f8;
    color: #334155;
}

.late-report-count-badge,
.late-report-time-badge.badge-late {
    padding: 0.45rem 0.8rem;
    background: #fee2e2;
    color: #b42318;
}

.late-report-time-badge.badge-expected,
.late-report-empty-badge {
    padding: 0.45rem 0.8rem;
    background: #dff5e6;
    color: #21543a;
}

.late-report-empty {
    padding: 2.2rem 1.5rem;
    text-align: center;
}

@media (max-width: 767.98px) {
    .late-report-page {
        padding-top: 1.25rem;
    }

    .late-report-filter-grid {
        grid-template-columns: 1fr;
    }

    .late-report-filter-card,
    .late-report-table-wrap,
    .late-report-section-head {
        padding-left: 1rem;
        padding-right: 1rem;
    }
}
</style>

<div class="container-fluid late-report-page">

    <div class="row">
        <div class="col-12">
            <div class="late-report-header">
                <h5 class="late-report-title">Monthly Late Punch-In Report</h5>
            </div>
        </div>
    </div>

    <!-- FILTER -->
    <div class="late-report-filter-card">
            <form method="GET" class="late-report-filter-grid">

                <div class="late-report-field">
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

                <div class="late-report-field">
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

                <div class="late-report-field">
                    <label>Employee Name</label>
                    <select name="name" class="form-control">
                        <option value="">All Employees</option>
                        <?php foreach ($employee_names as $employee_name): ?>
                            <option value="<?= htmlspecialchars($employee_name['id']) ?>"
                                <?= $selected_employee_id === (string) $employee_name['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($employee_name['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <button type="submit" class="late-report-filter-btn">
                        Filter
                    </button>
                </div>

            </form>
    </div>

    <!-- DETAILED LATE LIST -->
    <div class="late-report-section-card" style="margin-bottom:0;">
        <div class="late-report-section-head">
            <h6 class="late-report-section-title">Detailed Late Records</h6>
        </div>
            <div class="late-report-table-wrap">

                <?php if ($result->num_rows > 0): ?>
                    <table class="table align-items-center late-report-table">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Date</th>
                                <th>Expected Time</th>
                                <th>Actual Punch</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex flex-column justify-content-center">
                                                <h6 class="late-report-person-name">
                                                    <?= htmlspecialchars($row['name']) ?>
                                                </h6>
                                                <p class="late-report-person-meta">
                                                    <?= htmlspecialchars($row['emp_code']) ?>
                                                </p>
                                                <span class="late-report-office-badge">
                                                    <?= htmlspecialchars($row['office']) ?>
                                                </span>
                                        </div>
                                    </td>

                                    <td>
                                        <p class="late-report-person-meta mb-0">
                                            <?= date('d M Y', strtotime($row['late_date'])) ?>
                                        </p>
                                    </td>

                                    <td>
                                        <span class="late-report-time-badge badge-expected">
                                            <?= $row['expected_time'] ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="late-report-time-badge badge-late">
                                            <?= $row['punch_time'] ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="late-report-empty">
                        <span class="late-report-empty-badge">
                            No late punch-ins found
                        </span>
                    </div>
                <?php endif; ?>

    </div>

</div>

<?php include("footer.php"); ?>
