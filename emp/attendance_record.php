<?php
include("header.php");
require 'db_connection.php';

// Make sure the user is logged in
if (!isset($_SESSION['employee_id'])) {
    echo "<div class='alert alert-danger'>Access denied. Please login.</div>";
    exit;
}

$employee_id = $_SESSION['employee_id'];

// Check if current user is a Manager
$stmt = $conn->prepare("SELECT role FROM employees WHERE id = ?");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$stmt->bind_result($role);
$stmt->fetch();
$stmt->close();

if ($role !== 'Manager') {
    echo "<div class='alert alert-danger'>Access denied. Only managers can view attendance of their team.</div>";
    exit;
}

// Fetch all direct-report employees for this manager (for dropdown if needed)
$employees_stmt = $conn->prepare("SELECT id, name FROM employees WHERE manager = ?");
$employees_stmt->bind_param("i", $employee_id);
$employees_stmt->execute();
$employees_result = $employees_stmt->get_result();

// Filters
$current_date = date('Y-m-d');
$current_month = date('m');
$current_year = date('Y');

$selected_employee = isset($_GET['employee_id']) ? $_GET['employee_id'] : '';
$selected_month = isset($_GET['month']) ? $_GET['month'] : $current_month;
$selected_year = isset($_GET['year']) ? $_GET['year'] : $current_year;

$filter_start = "{$selected_year}-{$selected_month}-01";
$filter_end = date("Y-m-t 23:59:59", strtotime($filter_start));

// Build query to show only the manager's employees' attendance
$attendance_query = "    
    SELECT a.*, e.name AS employee_name, e.employee_id AS emp_id, e.punchin_time, e.punchout_time, e.office, a.break_hours
    FROM attendance a
    INNER JOIN employees e ON a.employee_id = e.id
    WHERE a.punch_in_time BETWEEN ? AND ? AND e.manager = ?
";

if ($selected_employee) {
    $attendance_query .= " AND a.employee_id = ?";
}

$attendance_query .= " ORDER BY a.punch_in_time DESC";

if ($selected_employee) {
    $attendance_stmt = $conn->prepare($attendance_query);
    $attendance_stmt->bind_param("ssii", $filter_start, $filter_end, $employee_id, $selected_employee);
} else {
    $attendance_stmt = $conn->prepare($attendance_query);
    $attendance_stmt->bind_param("ssi", $filter_start, $filter_end, $employee_id);
}

$attendance_stmt->execute();
$attendance_result = $attendance_stmt->get_result();
?>

<style>
    :root {
        --attendance-record-shell: linear-gradient(180deg, #f8fafc 0%, #eef3f8 100%);
        --attendance-record-border: rgba(148, 163, 184, 0.18);
        --attendance-record-shadow: 0 24px 56px rgba(15, 23, 42, 0.08);
    }

    .attendance-record-page {
        padding-top: 0.95rem !important;
        padding-bottom: 1.2rem !important;
    }

    .attendance-record-heading,
    .attendance-record-subheading {
        color: #0f172a;
        font-weight: 800;
        letter-spacing: -0.02em;
        margin: 0;
    }

    .attendance-record-heading {
        font-size: 1.15rem;
    }

    .attendance-record-subheading {
        font-size: 1rem;
        margin-bottom: 1rem;
    }

    .attendance-record-title-col {
        width: 100%;
    }

    .attendance-record-filter-card,
    .attendance-record-table-card {
        border: 1px solid var(--attendance-record-border);
        border-radius: 28px;
        background: var(--attendance-record-shell);
        box-shadow: var(--attendance-record-shadow);
        overflow: hidden;
    }

    .attendance-record-filter-card {
        padding: 1.1rem;
    }

    .attendance-record-filter-card .row {
        --bs-gutter-x: 0.9rem;
        --bs-gutter-y: 0.8rem;
        margin: 0;
    }

    .attendance-record-filter-card label {
        margin-bottom: 0.45rem;
        color: #64748b;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .attendance-record-filter-card .form-control {
        min-height: 48px;
        border-radius: 14px;
        border: 1px solid #d8e0ea;
        color: #334155;
        box-shadow: none;
    }

    .attendance-record-filter-card .btn {
        min-height: 48px;
        width: 100%;
        border: 0;
        border-radius: 15px;
        font-size: 0.82rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .attendance-record-filter-card .btn-primary {
        background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
        box-shadow: 0 16px 28px rgba(15, 23, 42, 0.12);
    }

    .attendance-record-filter-card .btn-dark {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        box-shadow: 0 16px 28px rgba(15, 23, 42, 0.12);
    }

    .attendance-record-table-card {
        padding: 0;
    }

    .attendance-record-table-shell {
        border-top: 1px solid #eef2f7;
        background: #ffffff;
    }

    .attendance-record-table-wrap {
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
    }

    .attendance-record-table {
        margin-bottom: 0;
        min-width: 1180px;
    }

    .attendance-record-table thead th {
        padding: 1rem 1rem;
        border-bottom: 1px solid #eef2f7;
        background: #f8fafc;
        color: #64748b;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        white-space: nowrap;
        vertical-align: middle;
    }

    .attendance-record-table tbody td,
    .attendance-record-table tfoot th,
    .attendance-record-table tfoot td {
        padding: 1rem;
        border-bottom: 1px solid #eef2f7;
        vertical-align: middle;
        color: #1f2937;
    }

    .attendance-record-table tbody tr:hover {
        background: #fbfdff;
    }

    .attendance-record-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .attendance-record-table tfoot th,
    .attendance-record-table tfoot td {
        background: #f8fafc;
        font-weight: 800;
    }

    .attendance-record-entry {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .attendance-record-entry .avatar,
    .attendance-record-entry img:not(.attendance-record-inline-icon) {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        object-fit: cover;
        box-shadow: 0 10px 22px rgba(15, 23, 42, 0.08);
    }

    .attendance-record-person {
        color: #0f172a;
        font-size: 0.92rem;
        font-weight: 700;
        line-height: 1.4;
        margin: 0;
    }

    .attendance-record-meta {
        color: #64748b;
        font-size: 0.76rem;
        font-weight: 600;
        margin: 0;
    }

    .attendance-record-inline-icon {
        width: 18px !important;
        height: 18px !important;
        vertical-align: text-bottom;
        object-fit: contain;
        box-shadow: none !important;
        border-radius: 0 !important;
    }

    .attendance-record-hours {
        font-size: 0.94rem;
        font-weight: 800;
    }

    .attendance-record-status {
        text-align: center;
    }

    .attendance-record-status .badge {
        border-radius: 999px;
        padding: 0.52rem 0.82rem;
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        border: 1px solid transparent;
        box-shadow: none;
    }

    .attendance-record-status .bg-gradient-success {
        background: #e7f8ef !important;
        color: #16a34a !important;
        border-color: #bfe8cd;
    }

    .attendance-record-status .bg-gradient-danger {
        background: #fff1f2 !important;
        color: #dc2626 !important;
        border-color: #fecdd3;
    }

    .attendance-record-status .bg-gradient-dark {
        background: #eef2f7 !important;
        color: #334155 !important;
        border-color: #d9e1ec;
    }

    .attendance-record-status .bg-gradient-warning {
        background: #fff7db !important;
        color: #b45309 !important;
        border-color: #f8df9c;
    }

    .attendance-record-action-cell .btn {
        min-width: 38px;
        min-height: 38px;
        border-radius: 12px;
        border: 0;
        box-shadow: none;
    }

    .attendance-record-action-cell .btn-warning {
        background: #fff4d8;
        color: #b45309;
        border: 1px solid #f7dfac;
    }

    .attendance-record-action-cell .btn-danger {
        background: #fff1f2;
        color: #dc2626;
        border: 1px solid #fecdd3;
    }

    .attendance-record-delete-selected {
        margin: 1rem;
        min-height: 46px;
        padding: 0.72rem 1rem;
        border-radius: 16px;
        border: 1px solid #fecdd3;
        background: linear-gradient(180deg, #fff1f2 0%, #ffe4e6 100%);
        color: #dc2626;
        font-weight: 800;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }

    .attendance-record-empty {
        margin: 0;
        padding: 1.25rem;
        color: #64748b;
        font-weight: 600;
    }

    @media (max-width: 767.98px) {
        .attendance-record-page {
            padding-top: 0.6rem !important;
            padding-left: 0.3rem !important;
            padding-right: 0.3rem !important;
            padding-bottom: 0.8rem !important;
        }

        .attendance-record-heading,
        .attendance-record-subheading {
            font-size: 0.92rem;
            line-height: 1.35;
        }

        .attendance-record-title-col,
        .attendance-record-filter-col,
        .attendance-record-report-col {
            flex: 0 0 100%;
            max-width: 100%;
            width: 100%;
        }

        .attendance-record-title-col {
            margin-bottom: 0.7rem !important;
        }

        .attendance-record-filter-col {
            margin-bottom: 0.75rem !important;
        }

        .attendance-record-subheading {
            margin-bottom: 0.75rem;
        }

        .attendance-record-filter-card,
        .attendance-record-table-card {
            border-radius: 22px;
        }

        .attendance-record-filter-card {
            padding: 0.9rem;
        }

        .attendance-record-filter-card .row {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.6rem;
            align-items: end;
            --bs-gutter-x: 0;
            --bs-gutter-y: 0;
        }

        .attendance-record-filter-card .row > [class*="col-"] {
            max-width: none;
            width: 100%;
            padding-left: 0;
            padding-right: 0;
            min-width: 0;
        }

        .attendance-record-filter-card label {
            margin-bottom: 0.32rem;
            font-size: 0.64rem;
        }

        .attendance-record-filter-card .form-control,
        .attendance-record-filter-card .btn {
            min-height: 44px;
            font-size: 0.82rem;
        }

        .attendance-record-filter-card .btn {
            padding-left: 0.55rem;
            padding-right: 0.55rem;
        }

        .attendance-record-table thead th,
        .attendance-record-table tbody td,
        .attendance-record-table tfoot th,
        .attendance-record-table tfoot td {
            padding: 0.85rem 0.8rem;
        }

        .attendance-record-entry .avatar,
        .attendance-record-entry img:not(.attendance-record-inline-icon) {
            width: 36px;
            height: 36px;
            border-radius: 12px;
        }

        .attendance-record-person {
            font-size: 0.84rem;
        }
    }
</style>

<div class="container-fluid py-4 attendance-record-page">
    <div class="row">
        <div class="col-6 mb-4 d-flex align-items-center attendance-record-title-col">
            <h6 class="mb-0 attendance-record-heading">Admin Attendance Report</h6>
        </div>
        <div class="col-12 mb-4 text-end attendance-record-filter-col">
            <form method="GET" action="attendance_record" class="mb-3 attendance-record-filter-card">
                <div class="row">

                    <div class="col-md-2">
                        <label for="office">Select Office</label>
                        <select name="office" id="office" class="form-control">
                            <option value="">All Offices</option>
                            <?php
                            $selected_office = isset($_GET['office']) ? $_GET['office'] : ''; // Get selected office from URL
                            foreach ($offices as $office):
                                $office_value = urlencode($office['office_name']) . "_" . urlencode($office['state_name']);
                            ?>
                                <option value="<?= $office_value ?>" <?= $selected_office == $office_value ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($office['office_name']) ?> (<?= htmlspecialchars($office['state_name']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>

                    </div>

                    <div class="col-md-2">
                        <label for="employee_id">Select Employee</label>
                        <select name="employee_id" id="employee_id" class="form-control">
                            <option value="">All Employees</option>
                            <?php while ($employee = $employees_result->fetch_assoc()): ?>
                                <option value="<?= $employee['id'] ?>" <?= $selected_employee == $employee['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($employee['name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="month">Select Month</label>
                        <select name="month" id="month" class="form-control">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>" <?= $selected_month == $m ? 'selected' : '' ?>>
                                    <?= date("F", mktime(0, 0, 0, $m, 10)) ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="year">Select Year</label>
                        <select name="year" id="year" class="form-control">
                            <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                <option value="<?= $y ?>" <?= $selected_year == $y ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary mb-0">Filter</button>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <a href="download_attendance_csv?employee_id=<?= $selected_employee ?>&month=<?= $selected_month ?>&year=<?= $selected_year ?>&office=<?= $selected_office ?>" class="btn btn-dark mb-0"><i class="bi bi-cloud-arrow-down-fill"></i> CSV</a>
                    </div>

                </div>
            </form>
        </div>
        <div class="col-12 attendance-record-report-col">
            <h6 class="attendance-record-subheading">Attendance Report (<?= date('d M Y', strtotime($filter_start)) ?> - <?= date('d M Y', strtotime($filter_end)) ?>)</h6>
            <div class="card mb-4 attendance-record-table-card">
                <div class="card-body px-0 pt-0 pb-2 attendance-record-table-shell">
                    <div class="table-responsive p-0 attendance-record-table-wrap">
                        <?php if ($attendance_result->num_rows > 0): ?>
                            <form method="POST" action="delete_multiple">
                                <table class="table align-items-center mb-0 attendance-record-table">
                                    <thead>
                                        <tr>
                                            <th>
                                                <div class="checkboxes__item">
                                                    <label class="checkbox style-h">
                                                        <input type="checkbox" id="select_all">
                                                        <div class="checkbox__checkmark" style="background-color: #7f0000;"></div>

                                                    </label>
                                                </div>
                                            </th>
                                            <th>Employee Name</th>
                                            <th>Punch In</th>
                                            <th>Punch Out</th>
                                            <th>W Hrs</th>
                                            <th>B Hrs</th>
                                            <th>A Hrs</th>
                                            <th>Status</th>
                                            <th>Action</th>

                                        </tr>
                                        
                                    </thead>
                                    <tbody>
                                        <?php $total_working_hours = 0;
                                        $total_break_hours = 0; ?>
                                        <?php while ($row = $attendance_result->fetch_assoc()): ?>
                                            <?php
// Convert working hours (decimal) to HH:MM format
$hours = floor($row['working_hours']); // Extract hours
$minutes = round(($row['working_hours'] - $hours) * 60); // Convert decimal to minutes
$formatted_working_hours = sprintf("%02d:%02d", $hours, $minutes);

// Convert break hours (decimal) to HH:MM format
$break_hours = floor($row['break_hours']); // Extract hours
$break_minutes = round(($row['break_hours'] - $break_hours) * 60); // Convert decimal to minutes
$formatted_break_hours = sprintf("%02d:%02d", $break_hours, $break_minutes);

// Calculate actual working time (working_hours - break_hours)
$actual_hours = floor($row['working_hours'] - $row['break_hours']); // Extract hours
$actual_minutes = round((($row['working_hours'] - $row['break_hours']) - $actual_hours) * 60); // Convert decimal to minutes
$formatted_actual_working_time = sprintf("%02d:%02d", $actual_hours, $actual_minutes);
?>
                                            <tr>
                                                <td>
                                                    <div class="checkboxes__item">
                                                        <label class="checkbox style-h">
                                                            <input type="checkbox" name="ids[]" value="<?= $row['id'] ?>">
                                                            <div class="checkbox__checkmark" style="background-color: #7f0000;"></div>

                                                        </label>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex px-2 py-1 attendance-record-entry">
                                                        <div class="d-flex flex-column justify-content-center">
                                                            <h6 class="mb-0 text-sm attendance-record-person"><?= htmlspecialchars($row['employee_name']) ?></h6>
                                                            <p class="text-xs text-secondary mb-0 attendance-record-meta"><?= htmlspecialchars($row['emp_id']) ?></p>
                                                        </div>
                                                    </div>
                                                </td>
                                               
                                                <td>
                                                    <div class="d-flex px-2 py-1 attendance-record-entry">
                                                        <div>
                                                            <?php if ($row['selfie_in']): ?>
                                                                <img src="<?= htmlspecialchars($row['selfie_in']) ?>" class="avatar avatar-sm me-3" alt="user1">
                                                            <?php else: ?>
                                                                <img src="assets/img/user-account (1).png" alt="">
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="d-flex flex-column justify-content-center">
                                                            <h6 class="mb-0 text-sm">
                                                                <?= date('Y-m-d', strtotime($row['punch_in_time'])) ?><br>
                                                                <!-- Late Punch-In -->
                                                                <?php
                                                                $record_date = date('Y-m-d', strtotime($row['punch_in_time']));
                                                                $expected_punchin_time = strtotime("$record_date " . $row['punchin_time']);
                                                                $actual_punchin_time = strtotime($row['punch_in_time']);
                                                                ?>
                                                                <?php if (
                                                                    !in_array($row['status'], ['Absent', 'Weekly Off', 'Holiday', 'On Leave'])
                                                                    && $actual_punchin_time > $expected_punchin_time
                                                                ): ?>
                                                                    <img src="assets/img/logos/tortoise.png" alt="" class="attendance-record-inline-icon" style="height: 20px;width:20px">
                                                                <?php endif; ?>
                                                                <?= date('H:i:s', strtotime($row['punch_in_time'])) ?>
                                                                <?php if ($row['location_in']): ?>
                                                                    <a onclick="viewLocation(<?= explode(',', $row['location_in'])[0] ?>, <?= explode(',', $row['location_in'])[1] ?>)">
                                                                        <img src="assets/img/location.png" alt="" class="attendance-record-inline-icon" style="height: 20px;width:20px">
                                                                    </a>
                                                                <?php else: ?>
                                                                    <img src="assets/img/no-gps.png" alt="" class="attendance-record-inline-icon">
                                                                <?php endif; ?>
                                                            </h6>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex px-2 py-1 attendance-record-entry">
                                                        <div>
                                                            <?php if ($row['selfie_out']): ?>
                                                                <img src="<?= htmlspecialchars($row['selfie_out']) ?>" class="avatar avatar-sm me-3" alt="user1">
                                                            <?php else: ?>
                                                                <img src="assets/img/user-account (1).png" alt="">
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="d-flex flex-column justify-content-center">
                                                            <h6 class="mb-0 text-sm">
                                                                <?= date('Y-m-d', strtotime($row['punch_out_time'])) ?><br>
                                                                <!-- Early Punch-Out -->
                                                                <?php
                                                                $expected_punchout_time = strtotime("$record_date " . $row['punchout_time']);
                                                                $actual_punchout_time = strtotime($row['punch_out_time']);
                                                                ?>
                                                                <?php if (
                                                                    !in_array($row['status'], ['Absent', 'Weekly Off', 'Holiday', 'On Leave'])
                                                                    && $actual_punchout_time < $expected_punchout_time
                                                                ): ?>
                                                                    <img src="assets/img/logos/rabbit.png" alt="" class="attendance-record-inline-icon" style="height: 20px;width:20px">
                                                                <?php endif; ?>
                                                                <?= date('H:i:s', strtotime($row['punch_out_time'])) ?>
                                                                <?php if ($row['is_auto_punchout'] == 1): ?>
                                                                    <img src="assets/img/logos/auto.png" alt="" class="attendance-record-inline-icon" style="height: 20px;width:20px">
                                                                <?php endif; ?>

                                                                <?php if ($row['location_out']): ?>
                                                                    <a onclick="viewLocation(<?= explode(',', $row['location_out'])[0] ?>, <?= explode(',', $row['location_out'])[1] ?>)">
                                                                        <img src="assets/img/location.png" class="attendance-record-inline-icon" style="height: 20px;width:20px" alt="">
                                                                    </a>
                                                                <?php else: ?>
                                                                    <img src="assets/img/no-gps.png" alt="" class="attendance-record-inline-icon">
                                                                <?php endif; ?>
                                                            </h6>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="attendance-record-hours <?php
    if ($row['working_hours'] >= 10) {
        echo 'text-primary blink';
    } elseif ($row['working_hours'] >= 9) {
        echo 'text-success';
    } else {
        echo 'text-danger';
    }
?>">
    <?= $formatted_working_hours ?>
</td>

<td class="attendance-record-hours <?php
    if ($row['break_hours'] >= 10) {
        echo 'text-primary blink';
    } elseif ($row['break_hours'] >= 9) {
        echo 'text-success';
    } else {
        echo 'text-danger';
    }
?>">
    <?= $formatted_break_hours ?>
</td>

<td class="attendance-record-hours <?php
    if ($actual_hours >= 10) {
        echo 'text-primary blink';
    } elseif ($actual_hours >= 9) {
        echo 'text-success';
    } else {
        echo 'text-danger';
    }
?>">
    <?= $formatted_actual_working_time ?>
</td>
                                                <td class="align-middle text-center text-sm attendance-record-status">
                                                    <?php if ($row['status'] == 'Present') : ?>
                                                        <span class="badge badge-sm bg-gradient-success"><?= ucfirst($row['status']) ?></span>
                                                    <?php elseif ($row['status'] == 'Absent') : ?>
                                                        <span class="badge badge-sm bg-gradient-danger"><?= ucfirst($row['status']) ?></span>
                                                    <?php elseif ($row['status'] == 'Weekly Off') : ?>
                                                        <span class="badge badge-sm bg-gradient-dark"><?= ucfirst($row['status']) ?></span>
                                                    <?php elseif ($row['status'] == 'On Leave') : ?>
                                                        <span class="badge badge-sm bg-gradient-danger"><?= ucfirst($row['status']) ?></span>
                                                    <?php elseif ($row['status'] == 'Holiday') : ?>
                                                        <span class="badge badge-sm bg-gradient-warning"><?= ucfirst($row['status']) ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="attendance-record-action-cell">
                                                    <!-- Edit and Delete Buttons -->
                                                    <a href="edit_attendance?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm"><i class="bi bi-pencil-square"></i></a>
                                                    <a href="delete_attendance?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this attendance record?');"><i class="bi bi-trash-fill"></i></a>
                                                </td>
                                            </tr>
                                            <?php $total_working_hours += (float) $row['working_hours']; ?>
                                            <?php $total_break_hours += (float) $row['break_hours']; ?>
                                        <?php endwhile; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="4" class="text-right">Total Working Hours:</th>
                                            <th><?= number_format($total_working_hours, 2) ?> hrs</th>
                                            <th><?= number_format($total_break_hours, 2) ?> hrs</th>
                                        </tr>
                                    </tfoot>
                                </table>
                                <button type="submit" class="btn btn-danger mt-3 attendance-record-delete-selected" onclick="return confirm('Are you sure you want to delete the selected records?');">
                                    Delete Selected
                                </button>
                            </form>
                        <?php else: ?>
                            <p class="attendance-record-empty">No attendance records found for the selected filters.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<script>
    // Open location in Google Maps
    function viewLocation(lat, long) {
        const url = `https://www.google.com/maps?q=${lat},${long}`;
        window.open(url, '_blank');
    }

    function redirectToSite() {
        var site = document.getElementById('site').value;
        if (site) {
            window.location.href = site; // Redirect to the selected site's page
        }
    }
</script>
<script>
    document.getElementById('select_all').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('input[name="ids[]"]');
        for (const checkbox of checkboxes) {
            checkbox.checked = this.checked;
        }
    });
</script>
<?php include("footer.php"); ?>