<?php
include("header.php");
require 'db_connection.php';
// Get filter inputs
$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$selected_employee = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : '';
// Fetch all employees
$employees_stmt = $conn->query("SELECT id, name FROM employees ORDER BY name ASC");
$employees = $employees_stmt->fetch_all(MYSQLI_ASSOC);

?>

<style>
.yearly-attendance-page {
    padding-top: 2rem;
    padding-bottom: 2.5rem;
}

.yearly-attendance-nav {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.9rem;
    margin-bottom: 1.5rem;
}

.yearly-attendance-tab {
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

.yearly-attendance-tab:hover {
    color: #0f172a;
    border-color: #cfd8e3;
    transform: translateY(-1px);
}

.yearly-attendance-tab.is-active {
    background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
    border-color: #111827;
    color: #ffffff;
    box-shadow: 0 24px 50px rgba(15, 23, 42, 0.18);
}

.yearly-attendance-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.25rem;
}

.yearly-attendance-title {
    margin: 0;
    color: #0f172a;
    font-size: 1.45rem;
    font-weight: 800;
    letter-spacing: -0.03em;
}

.yearly-attendance-filter-card,
.yearly-attendance-table-card {
    border: 1px solid #e5eaf1;
    border-radius: 26px;
    background: #ffffff;
    box-shadow: 0 28px 60px rgba(15, 23, 42, 0.07);
}

.yearly-attendance-filter-card {
    padding: 1.25rem;
    margin-bottom: 1.5rem;
}

.yearly-attendance-filter-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.55fr) minmax(190px, 1.05fr) auto auto;
    gap: 0.85rem;
    align-items: end;
}

.yearly-attendance-field label {
    display: block;
    margin-bottom: 0.45rem;
    color: #64748b;
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.09em;
    text-transform: uppercase;
}

.yearly-attendance-field .form-control {
    min-height: 44px;
    border: 1px solid #d7deea;
    border-radius: 14px;
    padding: 0.65rem 0.85rem;
    color: #0f172a;
    background: #f8fafc;
    box-shadow: none;
    font-size: 0.92rem;
}

.yearly-attendance-field .form-control:focus {
    border-color: #9aa8bc;
    background: #ffffff;
    box-shadow: 0 0 0 0.2rem rgba(148, 163, 184, 0.16);
}

.yearly-attendance-filter-btn,
.yearly-attendance-download-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 48px;
    padding: 0.78rem 1.35rem;
    min-width: 130px;
    border-radius: 14px;
    border: none;
    font-size: 0.77rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    text-decoration: none;
    white-space: nowrap;
}

.yearly-attendance-filter-btn {
    background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
    color: #ffffff;
    box-shadow: 0 18px 32px rgba(15, 23, 42, 0.18);
}

.yearly-attendance-download-btn {
    min-width: 150px;
    background: linear-gradient(135deg, #dff5e6 0%, #c8ebd5 100%);
    color: #21543a;
    border: 1px solid #b9dec8;
}

.yearly-attendance-table-card {
    overflow: hidden;
}

.yearly-attendance-table-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.35rem 1.5rem 0;
}

.yearly-attendance-table-title {
    margin: 0;
    color: #111827;
    font-size: 1rem;
    font-weight: 800;
    letter-spacing: -0.02em;
}

.yearly-attendance-table-wrap {
    overflow-x: auto;
    padding: 0 1.5rem 1.5rem;
}

.yearly-attendance-table {
    width: 100%;
    margin-bottom: 0;
}

.yearly-attendance-table thead th {
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

.yearly-attendance-table tbody td {
    padding: 1rem 0.85rem;
    border-bottom: 1px solid #eef2f7;
    color: #1f2937;
    font-size: 0.92rem;
    vertical-align: middle;
    white-space: nowrap;
}

.yearly-attendance-table tbody tr:last-child td {
    border-bottom: none;
}

.yearly-attendance-table tbody tr:hover {
    background: #f8fafc;
}

.yearly-attendance-table tbody td:first-child {
    color: #0f172a;
    font-weight: 700;
}

@media (max-width: 1199.98px) {
    .yearly-attendance-filter-grid {
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    }
}

@media (max-width: 991.98px) {
    .yearly-attendance-nav {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 767.98px) {
    .yearly-attendance-page {
        padding-top: 1.25rem;
    }

    .yearly-attendance-header,
    .yearly-attendance-table-head {
        flex-direction: column;
        align-items: flex-start;
    }

    .yearly-attendance-nav,
    .yearly-attendance-filter-grid {
        grid-template-columns: 1fr;
    }

    .yearly-attendance-filter-card,
    .yearly-attendance-table-wrap {
        padding-left: 1rem;
        padding-right: 1rem;
    }

    .yearly-attendance-table-head {
        padding-left: 1rem;
        padding-right: 1rem;
    }
}
</style>

<div class="container-fluid yearly-attendance-page">
   
    <div class="yearly-attendance-nav">
        <a href="all_employees_attendance" class="yearly-attendance-tab">Attendance Summary</a>
        <a href="employee_yearly_attendance" class="yearly-attendance-tab is-active">Yearly Summary</a>
        <a href="leave_summary" class="yearly-attendance-tab">Leave Summary</a>
        <a href="yearly_salary_summary" class="yearly-attendance-tab">Salary Summary</a>
    </div>

    <div class="yearly-attendance-header">
        <h6 class="yearly-attendance-title">Employee Yearly Attendance Summary</h6>
    </div>

    <form method="GET" class="yearly-attendance-filter-card">
        <div class="yearly-attendance-filter-grid">
            <div class="yearly-attendance-field">
                <label for="employee_id">Select Employee</label>
                <select name="employee_id" id="employee_id" class="form-control">
                    <option value="">All Employees</option>
                    <?php foreach ($employees as $employee): ?>
                        <option value="<?= $employee['id'] ?>" <?= $selected_employee == $employee['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($employee['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="yearly-attendance-field">
                <label for="year">Select Year</label>
                <select name="year" class="form-control">
                    <?php for ($y = date('Y') - 5; $y <= date('Y'); $y++): ?>
                        <option value="<?= $y ?>" <?= $y == $selected_year ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
          
            <div>
                <button type="submit" class="yearly-attendance-filter-btn">Filter</button>
            </div>

            <div>
                <?php if (!empty($selected_employee)): ?>
                    <a href="download_yearly_attendance?year=<?= $selected_year ?>&employee_id=<?= $selected_employee ?>" 
                       class="yearly-attendance-download-btn">Download CSV</a>
                <?php endif; ?>
            </div>
        </div>

    </form>
 

    <?php if (!empty($selected_employee)): ?>
        <div class="yearly-attendance-table-card">
            <div class="yearly-attendance-table-head">
                <h6 class="yearly-attendance-table-title">Monthly Attendance Breakdown</h6>
            </div>
            <div class="yearly-attendance-table-wrap">
                    <!-- Summary Table -->
                    <table class="table align-items-center yearly-attendance-table">
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Total Present</th>
                    <th>Total Absent</th>
                    <th>Total On Leave</th>
                    <th>Total Late Punch-ins</th>
                    <th>Total Early Punch-outs</th>
                    <th>Total Working Hours</th>
                    <th>Total Break Hours</th>
                </tr>
            </thead>
            <tbody>
                <?php
                for ($month = 1; $month <= 12; $month++):
                    $start_date = "{$selected_year}-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
                    $end_date = date("Y-m-t 23:59:59", strtotime($start_date));

                    $stmt = $conn->prepare("
                        SELECT 
                            COUNT(*) AS total_present,
                            SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) AS total_absent,
                            SUM(CASE WHEN status = 'On_Leave' THEN 1 ELSE 0 END) AS total_on_leave,
                            SUM(CASE WHEN punch_in_time > '09:00:00' THEN 1 ELSE 0 END) AS total_late,
                            SUM(CASE WHEN punch_out_time < '18:00:00' THEN 1 ELSE 0 END) AS total_early,
                            SUM(working_hours) AS total_working_hours,
                            SUM(break_hours) AS total_break_hours
                        FROM attendance
                        WHERE employee_id = ? AND punch_out_time BETWEEN ? AND ?
                    ");
                    $stmt->bind_param("iss", $selected_employee, $start_date, $end_date);
                    $stmt->execute();
                    $result = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                ?>
                <tr>
                    <td><?= date("F", strtotime($start_date)) ?></td>
                    <td><?= $result['total_present'] ?? 0 ?></td>
                    <td><?= $result['total_absent'] ?? 0 ?></td>
                    <td><?= $result['total_on_leave'] ?? 0 ?></td>
                    <td><?= $result['total_late'] ?? 0 ?></td>
                    <td><?= $result['total_early'] ?? 0 ?></td>
                    <td><?= $result['total_working_hours'] ?? 0 ?></td>
                    <td><?= $result['total_break_hours'] ?? 0 ?></td>
                </tr>
                <?php endfor; ?>
            </tbody>
            </table>
            </div>
        </div>
    <?php endif; ?>
            </div>
<!-- End Navbar -->
<?php include("footer.php") ?>