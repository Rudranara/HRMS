<?php
include("db_connection.php");
// Handle selected filters
$selected_office = $_GET['office'] ?? '';
$selected_employee = $_GET['employee_id'] ?? '';

// Fetch distinct offices
$offices = $conn->query("SELECT DISTINCT office FROM employees")->fetch_all(MYSQLI_ASSOC);

// Fetch employees based on selected filters
$employee_query = "SELECT * FROM employees WHERE 1=1";
$params = [];
$types = "";

if (!empty($selected_office)) {
    $employee_query .= " AND office = ?";
    $params[] = $selected_office;
    $types .= "s";
}

if (!empty($selected_employee)) {
    $employee_query .= " AND id = ?";
    $params[] = $selected_employee;
    $types .= "i";
}

$stmt = $conn->prepare($employee_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$employees = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// CSV export logic
if (isset($_GET['download']) && $_GET['download'] == 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=leave_summary.csv');
    $output = fopen("php://output", "w");
    fputcsv($output, ['Employee Name', 'Office', 'Sick Leave', 'Casual Leave', 'Paid Leave', 'Other Leave', 'Total Leave', 'Pending', 'Approved', 'Rejected']);

    foreach ($employees as $emp) {
        // Fetch leave summary for each employee
        $leave_stmt = $conn->prepare("
            SELECT status, COUNT(*) as count FROM leave_requests 
            WHERE employee_id = ? 
            GROUP BY status
        ");
        $leave_stmt->bind_param("i", $emp['id']);
        $leave_stmt->execute();
        $result = $leave_stmt->get_result();

        $pending = $approved = $rejected = 0;
        while ($row = $result->fetch_assoc()) {
            if ($row['status'] == 'Pending') $pending = $row['count'];
            if ($row['status'] == 'Approved') $approved = $row['count'];
            if ($row['status'] == 'Rejected') $rejected = $row['count'];
        }

        fputcsv($output, [
            $emp['name'],
            $emp['office'],
            $emp['sick_leave'],
            $emp['casual_leave'],
            $emp['paid_leave'],
            $emp['other_leave'],
            $emp['total_leave'],
            $pending,
            $approved,
            $rejected
        ]);
    }
    fclose($output);
    exit;
}
?>
<?php
include("header.php"); ?>
<style>
.leave-summary-page {
    padding-top: 2rem;
    padding-bottom: 2.5rem;
}

.leave-summary-nav {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.9rem;
    margin-bottom: 1.5rem;
}

.leave-summary-tab {
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

.leave-summary-tab:hover {
    color: #0f172a;
    border-color: #cfd8e3;
    transform: translateY(-1px);
}

.leave-summary-tab.is-active {
    background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
    border-color: #111827;
    color: #ffffff;
    box-shadow: 0 24px 50px rgba(15, 23, 42, 0.18);
}

.leave-summary-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.25rem;
}

.leave-summary-title {
    margin: 0;
    color: #0f172a;
    font-size: 1.45rem;
    font-weight: 800;
    letter-spacing: -0.03em;
}

.leave-summary-filter-card,
.leave-summary-table-card {
    border: 1px solid #e5eaf1;
    border-radius: 26px;
    background: #ffffff;
    box-shadow: 0 28px 60px rgba(15, 23, 42, 0.07);
}

.leave-summary-filter-card {
    padding: 1.25rem;
    margin-bottom: 1.5rem;
}

.leave-summary-filter-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.3fr) minmax(0, 1.3fr) auto auto;
    gap: 0.85rem;
    align-items: end;
}

.leave-summary-field {
    min-width: 0;
}

.leave-summary-field label {
    display: block;
    margin-bottom: 0.45rem;
    color: #64748b;
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.09em;
    text-transform: uppercase;
}

.leave-summary-field .form-control {
    min-height: 44px;
    border: 1px solid #d7deea;
    border-radius: 14px;
    padding: 0.65rem 0.85rem;
    color: #0f172a;
    background: #f8fafc;
    box-shadow: none;
    font-size: 0.92rem;
}

.leave-summary-field .form-control:focus {
    border-color: #9aa8bc;
    background: #ffffff;
    box-shadow: 0 0 0 0.2rem rgba(148, 163, 184, 0.16);
}

.leave-summary-filter-btn,
.leave-summary-download-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 48px;
    min-width: 130px;
    padding: 0.78rem 1.35rem;
    border-radius: 14px;
    border: none;
    font-size: 0.77rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    text-decoration: none;
    white-space: nowrap;
}

.leave-summary-filter-btn {
    background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
    color: #ffffff;
    box-shadow: 0 18px 32px rgba(15, 23, 42, 0.18);
}

.leave-summary-download-btn {
    min-width: 150px;
    background: linear-gradient(135deg, #dff5e6 0%, #c8ebd5 100%);
    color: #21543a;
    border: 1px solid #b9dec8;
}

.leave-summary-table-card {
    overflow: hidden;
}

.leave-summary-table-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.35rem 1.5rem 0;
}

.leave-summary-table-title {
    margin: 0;
    color: #111827;
    font-size: 1rem;
    font-weight: 800;
    letter-spacing: -0.02em;
}

.leave-summary-table-wrap {
    overflow-x: auto;
    padding: 0 1.5rem 1.5rem;
}

.leave-summary-table {
    width: 100%;
    margin-bottom: 0;
}

.leave-summary-table thead th {
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

.leave-summary-table tbody td {
    padding: 1rem 0.85rem;
    border-bottom: 1px solid #eef2f7;
    color: #1f2937;
    font-size: 0.92rem;
    vertical-align: middle;
    white-space: nowrap;
}

.leave-summary-table tbody tr:last-child td {
    border-bottom: none;
}

.leave-summary-table tbody tr:hover {
    background: #f8fafc;
}

.leave-summary-table tbody td:first-child {
    color: #0f172a;
    font-weight: 700;
}

@media (max-width: 991.98px) {
    .leave-summary-nav,
    .leave-summary-filter-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 767.98px) {
    .leave-summary-page {
        padding-top: 1.25rem;
    }

    .leave-summary-header,
    .leave-summary-table-head {
        flex-direction: column;
        align-items: flex-start;
    }

    .leave-summary-nav,
    .leave-summary-filter-grid {
        grid-template-columns: 1fr;
    }

    .leave-summary-filter-card,
    .leave-summary-table-wrap {
        padding-left: 1rem;
        padding-right: 1rem;
    }

    .leave-summary-table-head {
        padding-left: 1rem;
        padding-right: 1rem;
    }
}
</style>

<div class="container-fluid leave-summary-page">
    <div class="leave-summary-nav">
        <a href="all_employees_attendance" class="leave-summary-tab">Attendance Summary</a>
        <a href="employee_yearly_attendance" class="leave-summary-tab">Yearly Summary</a>
        <a href="leave_summary" class="leave-summary-tab is-active">Leave Summary</a>
        <a href="yearly_salary_summary" class="leave-summary-tab">Salary Summary</a>
    </div>

    <div class="leave-summary-header">
        <h6 class="leave-summary-title">Employees Attendance Summary</h6>
    </div>

    <form method="GET" class="leave-summary-filter-card">
    <div class="leave-summary-filter-grid">
        <div class="leave-summary-field">
            <label>Select Office</label>
            <select name="office" class="form-control" onchange="this.form.submit()">
                <option value="">All Offices</option>
                <?php foreach ($offices as $office): ?>
                    <option value="<?= htmlspecialchars($office['office']) ?>" <?= ($selected_office == $office['office']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($office['office']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="leave-summary-field">
            <label>Select Employee</label>
            <select name="employee_id" class="form-control">
                <option value="">All Employees</option>
                <?php foreach ($employees as $emp): ?>
                    <option value="<?= $emp['id'] ?>" <?= ($selected_employee == $emp['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($emp['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <button type="submit" class="leave-summary-filter-btn">Filter</button>
        </div>

        <div>
            <a href="?download=csv<?= $selected_office ? '&office=' . urlencode($selected_office) : '' ?><?= $selected_employee ? '&employee_id=' . $selected_employee : '' ?>" class="leave-summary-download-btn">Download CSV</a>
        </div>
    </div>
</form>

    <div class="leave-summary-table-card">
        <div class="leave-summary-table-head">
            <h6 class="leave-summary-table-title">Leave Allocation And Request Summary</h6>
        </div>
        <div class="leave-summary-table-wrap">
                    <!-- Summary Table -->
                    <table class="table align-items-center leave-summary-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Office</th>
                <th>Sick</th>
                <th>Casual</th>
                <th>Paid</th>
                <th>Other</th>
                <th>Total</th>
                <th>Pending</th>
                <th>Approved</th>
                <th>Rejected</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($employees as $emp): ?>
            <?php
            $leave_stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM leave_requests WHERE employee_id = ? GROUP BY status");
            $leave_stmt->bind_param("i", $emp['id']);
            $leave_stmt->execute();
            $result = $leave_stmt->get_result();

            $pending = $approved = $rejected = 0;
            while ($row = $result->fetch_assoc()) {
                if ($row['status'] == 'Pending') $pending = $row['count'];
                if ($row['status'] == 'Approved') $approved = $row['count'];
                if ($row['status'] == 'Rejected') $rejected = $row['count'];
            }
            ?>
            <tr>
                <td><?= htmlspecialchars($emp['name']) ?></td>
                <td><?= htmlspecialchars($emp['office']) ?></td>
                <td><?= $emp['sick_leave'] ?></td>
                <td><?= $emp['casual_leave'] ?></td>
                <td><?= $emp['paid_leave'] ?></td>
                <td><?= $emp['other_leave'] ?></td>
                <td><?= $emp['total_leave'] ?></td>
                <td><?= $pending ?></td>
                <td><?= $approved ?></td>
                <td><?= $rejected ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
    </table>
    </div>
</div>
</div>
<!-- End Navbar -->
<?php include("footer.php") ?>
