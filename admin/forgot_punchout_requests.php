<?php
require 'header.php';
require 'db_connection.php';
date_default_timezone_set('Asia/Kolkata');

$current_month = date('m');
$current_year = date('Y');

$selected_month = $_GET['month'] ?? $current_month;
$selected_year = $_GET['year'] ?? $current_year;
$selected_employee_id = trim($_GET['name'] ?? '');

$start_date = "{$selected_year}-{$selected_month}-01 00:00:00";
$end_date = date('Y-m-t 23:59:59', strtotime("{$selected_year}-{$selected_month}-01"));
$show_reset = $selected_employee_id !== ''
    || $selected_month !== $current_month
    || $selected_year !== $current_year;

$employee_names = [];
$employee_result = $conn->query("SELECT id, name FROM employees WHERE name IS NOT NULL AND name != '' ORDER BY name ASC");

if ($employee_result) {
    while ($employee_row = $employee_result->fetch_assoc()) {
        $employee_names[] = $employee_row;
    }
}

/*
  Forgot Punch-Out Conditions:
  - punched in
  - punch_out_time is NULL
  - status = Present
  - today only
*/
$query = "
    SELECT 
    a.id,
    a.employee_id,
    a.punch_in_time,
    a.punch_out_time,
    a.office,
    a.location_in,
    a.selfie_in,
    e.name AS employee_name,
    e.employee_id AS emp_code
FROM attendance a
JOIN employees e ON e.id = a.employee_id
WHERE 
    a.is_auto_punchout = 1
    AND a.status = 'Absent'
    AND DATE(a.punch_in_time) < CURDATE()
    AND a.punch_in_time BETWEEN ? AND ?
";

if ($selected_employee_id !== '') {
    $query .= " AND a.employee_id = ?";
}

$query .= " ORDER BY a.punch_in_time DESC";

$stmt = $conn->prepare($query);

if ($selected_employee_id !== '') {
    $employee_filter = (int) $selected_employee_id;
    $stmt->bind_param("ssi", $start_date, $end_date, $employee_filter);
} else {
    $stmt->bind_param("ss", $start_date, $end_date);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<style>
.forgot-punchout-page {
    padding-top: 2rem;
    padding-bottom: 2.5rem;
}

.forgot-punchout-header {
    display: flex;
    align-items: flex-start;
    justify-content: flex-start;
    gap: 1rem;
    margin-bottom: 1rem;
}

.forgot-punchout-title {
    margin: 0;
    color: #0f172a;
    font-size: 1.45rem;
    font-weight: 800;
    letter-spacing: -0.03em;
}

.forgot-punchout-subtitle {
    margin: 0.35rem 0 0;
    color: #64748b;
    font-size: 0.92rem;
}

.forgot-punchout-filter-card {
    display: flex;
    align-items: end;
    gap: 0.85rem;
    margin-bottom: 0.55rem;
    padding: 1.2rem 1.25rem;
    border: 1px solid #e5eaf1;
    border-radius: 22px;
    background: #ffffff;
    box-shadow: 0 24px 54px rgba(15, 23, 42, 0.06);
}

.forgot-punchout-filter-field {
    min-width: 0;
    max-width: 240px;
}

.forgot-punchout-filter-field label {
    display: block;
    margin-bottom: 0.45rem;
    color: #64748b;
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.09em;
    text-transform: uppercase;
}

.forgot-punchout-filter-field .form-control {
    min-height: 44px;
    border: 1px solid #d7deea;
    border-radius: 14px;
    padding: 0.65rem 0.85rem;
    color: #0f172a;
    background: #f8fafc;
    box-shadow: none;
    font-size: 0.92rem;
}

.forgot-punchout-filter-field .form-control:focus {
    border-color: #9aa8bc;
    background: #ffffff;
    box-shadow: 0 0 0 0.2rem rgba(148, 163, 184, 0.16);
}

.forgot-punchout-filter-btn,
.forgot-punchout-reset-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 48px;
    padding: 0.78rem 1.2rem;
    border-radius: 14px;
    font-size: 0.77rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    text-decoration: none;
    white-space: nowrap;
}

.forgot-punchout-filter-btn {
    border: none;
    background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
    color: #ffffff;
    box-shadow: 0 18px 32px rgba(15, 23, 42, 0.18);
}

.forgot-punchout-reset-btn {
    border: 1px solid #d7deea;
    background: #ffffff;
    color: #334155;
}

.forgot-punchout-card {
    border: 1px solid #e5eaf1;
    border-radius: 26px;
    background: #ffffff;
    box-shadow: 0 28px 60px rgba(15, 23, 42, 0.07);
    overflow: hidden;
}

.forgot-punchout-table-wrap {
    overflow-x: auto;
    padding: 0 1.5rem 1.5rem;
}

.forgot-punchout-table {
    width: 100%;
    margin-bottom: 0;
}

.forgot-punchout-table thead th {
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

.forgot-punchout-table tbody td {
    padding: 1rem 0.95rem;
    border-bottom: 1px solid #eef2f7;
    color: #1f2937;
    font-size: 0.92rem;
    vertical-align: middle;
}

.forgot-punchout-table tbody tr:last-child td {
    border-bottom: none;
}

.forgot-punchout-table tbody tr:hover {
    background: #f8fafc;
}

.forgot-punchout-person,
.forgot-punchout-punch {
    display: flex;
    align-items: center;
    gap: 0.9rem;
}

.forgot-punchout-avatar {
    flex: 0 0 auto;
    width: 46px;
    height: 46px;
    border-radius: 14px;
    object-fit: cover;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
}

.forgot-punchout-person-name,
.forgot-punchout-punch-date {
    margin: 0;
    color: #0f172a;
    font-size: 0.95rem;
    font-weight: 700;
}

.forgot-punchout-person-code,
.forgot-punchout-punch-time {
    margin: 0.18rem 0 0;
    color: #64748b;
    font-size: 0.82rem;
}

.forgot-punchout-location {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    margin-left: 0.55rem;
    border-radius: 10px;
    background: #eef2f7;
    transition: background 0.2s ease;
}

.forgot-punchout-location:hover {
    background: #e2e8f0;
}

.forgot-punchout-location img {
    width: 16px;
    height: 16px;
}

.forgot-punchout-office {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.5rem 0.85rem;
    border-radius: 999px;
    background: #eff3f8;
    color: #334155;
    font-size: 0.75rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.forgot-punchout-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.6rem;
}

.forgot-punchout-action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 40px;
    padding: 0.7rem 1rem;
    border-radius: 12px;
    font-size: 0.76rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    text-decoration: none;
}

.forgot-punchout-action-btn.btn-success {
    background: linear-gradient(135deg, #dff5e6 0%, #c8ebd5 100%);
    border: 1px solid #b9dec8;
    color: #21543a;
}

.forgot-punchout-action-btn.btn-danger {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    border: 1px solid #f7b4b4;
    color: #9f1d1d;
}

.forgot-punchout-empty {
    padding: 2.2rem 1.5rem;
    text-align: center;
}

.forgot-punchout-empty-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 46px;
    padding: 0.7rem 1.1rem;
    border-radius: 14px;
    background: linear-gradient(135deg, #dff5e6 0%, #c8ebd5 100%);
    color: #21543a;
    border: 1px solid #b9dec8;
    font-size: 0.8rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

@media (max-width: 767.98px) {
    .forgot-punchout-page {
        padding-top: 1.25rem;
    }

    .forgot-punchout-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .forgot-punchout-filter-card {
        flex-direction: column;
        align-items: stretch;
        padding: 1rem;
    }

    .forgot-punchout-filter-field {
        max-width: none;
    }

    .forgot-punchout-table-wrap {
        padding-left: 1rem;
        padding-right: 1rem;
    }
}
</style>

<div class="container-fluid forgot-punchout-page">
    <div class="row">
        <div class="col-12 mb-4">
            <div class="forgot-punchout-header">
                <div>
                    <h6 class="forgot-punchout-title">Forgot Punch-Out Requests</h6>
                    <p class="forgot-punchout-subtitle">Review auto punch-out records and take the required attendance action.</p>
                </div>
            </div>

            <form method="GET" class="forgot-punchout-filter-card">
                <div class="forgot-punchout-filter-field">
                    <label for="month">Select Month</label>
                    <select id="month" name="month" class="form-control">
                        <?php for ($m = 1; $m <= 12; $m++):
                            $month_value = str_pad($m, 2, '0', STR_PAD_LEFT);
                        ?>
                            <option value="<?= $month_value ?>" <?= $selected_month === $month_value ? 'selected' : '' ?>>
                                <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="forgot-punchout-filter-field">
                    <label for="year">Select Year</label>
                    <select id="year" name="year" class="form-control">
                        <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                            <option value="<?= $y ?>" <?= (string) $selected_year === (string) $y ? 'selected' : '' ?>>
                                <?= $y ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="forgot-punchout-filter-field">
                    <label for="name">Employee Name</label>
                    <select id="name" name="name" class="form-control">
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
                    <button type="submit" class="forgot-punchout-filter-btn">Filter</button>
                </div>
                <?php if ($show_reset): ?>
                    <div>
                        <a href="forgot_punchout_requests" class="forgot-punchout-reset-btn">Reset</a>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <div class="col-12">
            <div class="forgot-punchout-card mb-4">
                <div class="forgot-punchout-table-wrap">

                        <?php if ($result->num_rows > 0): ?>
                            <table class="table align-items-center forgot-punchout-table">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Punch In</th>
                                        <th>Office</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>

                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="forgot-punchout-person">
                                                <div class="d-flex flex-column justify-content-center">
                                                    <h6 class="forgot-punchout-person-name">
                                                        <?= htmlspecialchars($row['employee_name']) ?>
                                                    </h6>
                                                    <p class="forgot-punchout-person-code">
                                                        <?= htmlspecialchars($row['emp_code']) ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </td>

                                        <td>
                                            <div class="forgot-punchout-punch">
                                                <div>
                                                    <?php if ($row['selfie_in']): ?>
                                                        <img src="<?= htmlspecialchars($row['selfie_in']) ?>"
                                                             class="forgot-punchout-avatar">
                                                    <?php else: ?>
                                                        <img src="assets/img/user-account (1).png" class="forgot-punchout-avatar">
                                                    <?php endif; ?>
                                                </div>
                                                <div class="d-flex flex-column justify-content-center">
                                                    <h6 class="forgot-punchout-punch-date">
                                                        <?= date('d M Y', strtotime($row['punch_in_time'])) ?>
                                                    </h6>
                                                    <p class="forgot-punchout-punch-time">
                                                        <?= date('H:i:s', strtotime($row['punch_in_time'])) ?>

                                                        <?php if ($row['location_in']): ?>
                                                            <a class="forgot-punchout-location" onclick="viewLocation(
                                                                <?= explode(',', $row['location_in'])[0] ?>,
                                                                <?= explode(',', $row['location_in'])[1] ?>
                                                            )">
                                                                <img src="assets/img/location.png">
                                                            </a>
                                                        <?php endif; ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </td>

                                        <td>
                                            <span class="forgot-punchout-office">
                                                <?= htmlspecialchars($row['office']) ?>
                                            </span>
                                        </td>

                                        <td>
                                            <div class="forgot-punchout-actions">
                                            <a href="approve_forgot_punchout?id=<?= $row['id'] ?>&action=present"
                                               class="btn btn-success btn-sm forgot-punchout-action-btn">
                                                Mark Present
                                            </a>

                                            <a href="approve_forgot_punchout?id=<?= $row['id'] ?>&action=absent"
                                               class="btn btn-danger btn-sm forgot-punchout-action-btn"
                                               onclick="return confirm('Mark this employee Absent?')">
                                                Mark Absent
                                            </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>

                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="forgot-punchout-empty">
                                <span class="forgot-punchout-empty-badge">
                                    No forgot punch-out requests
                                </span>
                            </div>
                        <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
function viewLocation(lat, lng) {
    window.open(`https://www.google.com/maps?q=${lat},${lng}`, '_blank');
}
</script>

<?php include('footer.php'); ?>
