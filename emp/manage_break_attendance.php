<?php
require 'header.php';

// Ensure the employee is logged in
if (!isset($_SESSION['employee_id'])) {
    echo "Access Denied!";
    exit;
}
$employee_id = $_SESSION['employee_id'];

// Get the current date, month, and year
$current_date = date('Y-m-d');
$current_month = date('m');
$current_year = date('Y');

// Check for selected month and year, or use the current month and year by default
$selected_month = isset($_GET['month']) ? $_GET['month'] : $current_month;
$selected_year = isset($_GET['year']) ? $_GET['year'] : $current_year;

// Define the start and end dates for the filter
$filter_start = "{$selected_year}-{$selected_month}-01";
$filter_end = date("Y-m-t 23:59:59", strtotime($filter_start));

// Fetch attendance records for the logged-in employee within the selected date range
$stmt = $conn->prepare("
    SELECT 
        a.id, 
        e.name AS employee_name, 
        e.employee_id, 
        e.punchin_time, 
        e.punchout_time, 
        e.break_time, 
        a.punch_in_time, 
        a.punch_out_time, 
        a.location_in, 
        a.location_out, 
        a.current_location, 
        a.selfie_in, 
        a.selfie_out, 
        a.status,
        a.working_hours
    FROM break_attendance a
    JOIN employees e ON a.employee_id = e.id
    WHERE a.employee_id = ? AND a.punch_in_time BETWEEN ? AND ?
    ORDER BY a.punch_in_time DESC
");
$stmt->bind_param("iss", $employee_id, $filter_start, $filter_end);
$stmt->execute();
$result = $stmt->get_result();
?>

<style>
    :root {
        --emp-break-shell: linear-gradient(180deg, #f8fafc 0%, #eef3f8 100%);
        --emp-break-border: rgba(148, 163, 184, 0.18);
        --emp-break-shadow: 0 24px 56px rgba(15, 23, 42, 0.08);
    }

    .emp-break-page {
        padding-top: 0.95rem !important;
        padding-bottom: 1.25rem !important;
    }

    .emp-break-card {
        border: 1px solid var(--emp-break-border);
        border-radius: 28px;
        background: var(--emp-break-shell);
        box-shadow: var(--emp-break-shadow);
        overflow: hidden;
    }

    .emp-break-card .card-header {
        padding: 1.2rem 1.25rem 0.25rem;
        border-bottom: 0;
        background: transparent;
    }

    .emp-break-title {
        margin: 0;
        color: #0f172a;
        font-size: 1.15rem;
        font-weight: 800;
        letter-spacing: -0.02em;
    }

    .emp-break-filter-wrap {
        padding: 0 1.25rem 1rem;
    }

    .emp-break-filter-form {
        padding: 1rem;
        border: 1px solid #e5ebf2;
        border-radius: 22px;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.94) 0%, rgba(248, 250, 252, 0.98) 100%);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7), 0 12px 28px rgba(15, 23, 42, 0.04);
    }

    .emp-break-filter-form .row {
        --bs-gutter-x: 0.9rem;
        --bs-gutter-y: 0.8rem;
        margin: 0;
    }

    .emp-break-filter-form label {
        margin-bottom: 0.45rem;
        color: #64748b;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .emp-break-filter-form .form-control {
        min-height: 48px;
        border-radius: 14px;
        border: 1px solid #d8e0ea;
        color: #334155;
        box-shadow: none;
    }

    .emp-break-filter-form .btn {
        min-height: 48px;
        width: 100%;
        border: 0;
        border-radius: 15px;
        background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
        box-shadow: 0 16px 28px rgba(15, 23, 42, 0.12);
        font-size: 0.84rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .emp-break-table-wrap {
        padding: 0 1.25rem 1.25rem;
    }

    .emp-break-table-shell {
        border: 1px solid #e7edf4;
        border-radius: 24px;
        background: #ffffff;
        box-shadow: 0 16px 34px rgba(15, 23, 42, 0.05);
        overflow: hidden;
    }

    .emp-break-table {
        margin-bottom: 0;
        min-width: 760px;
    }

    .emp-break-table thead th {
        padding: 1rem 1.1rem;
        border-bottom: 1px solid #eef2f7;
        background: #f8fafc;
        color: #64748b;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .emp-break-table tbody td {
        padding: 1rem 1.1rem;
        border-bottom: 1px solid #eef2f7;
        vertical-align: middle;
        color: #1f2937;
    }

    .emp-break-table tbody tr:hover {
        background: #fbfdff;
    }

    .emp-break-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .emp-break-entry {
        display: flex;
        align-items: center;
        gap: 0.8rem;
    }

    .emp-break-entry .avatar,
    .emp-break-entry img:not(.emp-break-inline-icon) {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        object-fit: cover;
        box-shadow: 0 10px 22px rgba(15, 23, 42, 0.08);
    }

    .emp-break-time {
        color: #0f172a;
        font-size: 0.9rem;
        font-weight: 700;
        line-height: 1.45;
        margin: 0;
    }

    .emp-break-inline-icon {
        width: 18px !important;
        height: 18px !important;
        vertical-align: text-bottom;
        object-fit: contain;
        box-shadow: none !important;
        border-radius: 0 !important;
    }

    .emp-break-hours {
        font-size: 0.94rem;
        font-weight: 800;
    }

    td.emp-break-hours.text-success {
        color: #22c55e !important;
    }

    td.emp-break-hours.text-danger {
        color: #fb7185 !important;
    }

    .emp-break-status-cell {
        text-align: center;
    }

    .emp-break-status-cell .badge {
        border-radius: 999px;
        padding: 0.52rem 0.82rem;
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        border: 1px solid transparent;
        box-shadow: none;
    }

    .emp-break-status-cell .bg-gradient-success {
        background: #e7f8ef !important;
        color: #16a34a !important;
        border-color: #bfe8cd;
    }

    .emp-break-status-cell .bg-gradient-danger {
        background: #fff1f2 !important;
        color: #dc2626 !important;
        border-color: #fecdd3;
    }

    .emp-break-status-cell .bg-gradient-dark {
        background: #eef2f7 !important;
        color: #334155 !important;
        border-color: #d9e1ec;
    }

    .emp-break-status-cell .bg-gradient-warning {
        background: #fff7db !important;
        color: #b45309 !important;
        border-color: #f8df9c;
    }

    .emp-break-empty {
        padding: 1.4rem 1rem !important;
        color: #64748b;
        font-weight: 600;
    }

    @media (max-width: 767.98px) {
        .emp-break-page {
            padding-top: 0.6rem !important;
            padding-left: 0.3rem !important;
            padding-right: 0.3rem !important;
            padding-bottom: 0.8rem !important;
        }

        .emp-break-card {
            border-radius: 22px;
        }

        .emp-break-card .card-header {
            padding: 0.95rem 0.95rem 0.2rem;
        }

        .emp-break-title {
            font-size: 1rem;
        }

        .emp-break-filter-wrap,
        .emp-break-table-wrap {
            padding-left: 0.95rem;
            padding-right: 0.95rem;
        }

        .emp-break-filter-form {
            padding: 0.85rem;
            border-radius: 18px;
        }

        .emp-break-filter-form .row {
            display: flex;
            flex-wrap: nowrap;
            align-items: flex-end;
            gap: 0.45rem;
            --bs-gutter-x: 0;
            --bs-gutter-y: 0;
        }

        .emp-break-filter-form .row > [class*="col-"] {
            padding-left: 0;
            padding-right: 0;
            min-width: 0;
        }

        .emp-break-filter-form .row > .col-md-5 {
            flex: 1 1 0;
            max-width: none;
        }

        .emp-break-filter-form .row > .col-md-2 {
            flex: 0 0 94px;
            max-width: 94px;
        }

        .emp-break-filter-form label {
            margin-bottom: 0.32rem;
            font-size: 0.64rem;
        }

        .emp-break-filter-form .btn,
        .emp-break-filter-form .form-control {
            min-height: 44px;
            font-size: 0.82rem;
        }

        .emp-break-filter-form .btn {
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }

        .emp-break-table-shell {
            border-radius: 18px;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
        }

        .emp-break-table thead th,
        .emp-break-table tbody td {
            padding: 0.85rem 0.8rem;
        }

        .emp-break-entry {
            gap: 0.65rem;
        }

        .emp-break-entry .avatar,
        .emp-break-entry img:not(.emp-break-inline-icon) {
            width: 36px;
            height: 36px;
            border-radius: 12px;
        }

        .emp-break-time {
            font-size: 0.82rem;
        }

        .emp-break-hours {
            font-size: 0.86rem;
        }
    }
</style>
<!-- Attendance Management for Employee -->
<div class="container-fluid py-4 emp-break-page">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4 emp-break-card">
                <div class="card-header pb-0">
                    <h6 class="emp-break-title">My Breaks</h6>
                </div>
                <div class="col-12 mb-4 text-end emp-break-filter-wrap">
                    <form method="GET" action="" class="mb-3 emp-break-filter-form">
                        <div class="row m-2">
                            <div class="col-md-5">
                                <label for="month">Select Month</label>
                                <select name="month" id="month" class="form-control">
                                    <?php for ($m = 1; $m <= 12; $m++): ?>
                                        <option value="<?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>" <?= $selected_month == $m ? 'selected' : '' ?>>
                                            <?= date("F", mktime(0, 0, 0, $m, 10)) ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-5">
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
                        </div>
                    </form>
                </div>
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="emp-break-table-wrap">
                    <div class="table-responsive p-0 emp-break-table-shell">
                        <table class="table align-items-center mb-0 emp-break-table" id="attendanceTable">
                            <thead>
                                <th>Start</th>
                                <th>End</th>
                                <th>Break Hr</th>
                                <th>Status</th>
                            </thead>
                            <tbody>
                                <?php if ($result->num_rows > 0): ?>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <?php
                                        // Convert decimal working hours to hours and minutes
                                        $hours = floor($row['working_hours']); // Extract hours
                                        $minutes = round(($row['working_hours'] - $hours) * 60); // Convert decimal to minutes
                                        $formatted_working_hours = sprintf("%02d:%02d", $hours, $minutes);
                                        ?>
                                        <tr>

                                            <td>
                                                <div class="d-flex px-2 py-1 emp-break-entry">
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

                                                            <?= date('H:i:s', strtotime($row['punch_in_time'])) ?>
                                                            <?php if ($row['location_in']): ?>
                                                                <a onclick="viewLocation(<?= explode(',', $row['location_in'])[0] ?>, <?= explode(',', $row['location_in'])[1] ?>)">
                                                                    <img src="assets/img/location.png" alt="" class="emp-break-inline-icon" style="height: 20px;width:20px">
                                                                </a>
                                                            <?php else: ?>
                                                                <img src="assets/img/no-gps.png" alt="" class="emp-break-inline-icon">
                                                            <?php endif; ?>
                                                        </h6>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex px-2 py-1 emp-break-entry">
                                                    <div>
                                                        <?php if ($row['selfie_out']): ?>
                                                            <img src="<?= htmlspecialchars($row['selfie_out']) ?>" class="avatar avatar-sm me-3" alt="user1">
                                                        <?php else: ?>
                                                            <img src="assets/img/user-account (1).png" alt="">
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="d-flex flex-column justify-content-center">
                                                        <?php if ($row['punch_out_time']): ?>
                                                            <h6 class="mb-0 text-sm">
                                                                <?= date('Y-m-d', strtotime($row['punch_out_time'])) ?><br>

                                                                <?= date('H:i:s', strtotime($row['punch_out_time'])) ?>
                                                                <?php if ($row['location_out']): ?>
                                                                    <a onclick="viewLocation(<?= explode(',', $row['location_out'])[0] ?>, <?= explode(',', $row['location_out'])[1] ?>)">
                                                                        <img src="assets/img/location.png" class="emp-break-inline-icon" style="height: 20px;width:20px" alt="">
                                                                    </a>
                                                                <?php else: ?>
                                                                    <img src="assets/img/no-gps.png" alt="" class="emp-break-inline-icon">
                                                                <?php endif; ?>
                                                            </h6>
                                                        <?php else: ?>
                                                            Not Punched<br>Out Yet
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <?php
// Convert decimal working hours to hours and minutes
$working_hours = (float) $row['working_hours']; // Ensure it's a float
$break_time_hours = $row['break_time'] / 60; // Convert break time (minutes) to decimal hours

$hours = floor($working_hours); // Extract whole hours
$minutes = round(($working_hours - $hours) * 60); // Convert decimal part to minutes

$formatted_working_hours = sprintf("%02d:%02d", $hours, $minutes);
?>

<td class="<?php
    if ($working_hours > $break_time_hours) { 
        // If working hours are less than break time, mark it as danger
        echo 'text-danger';
    } else {
        // If working hours are sufficient, mark it as success
        echo 'text-success';
    }
?> emp-break-hours">
    <?= htmlspecialchars($formatted_working_hours) ?>
</td>




                                            <td class="align-middle text-center text-sm emp-break-status-cell">
                                                <?php if ($row['status'] == 'Present') : ?>
                                                    <span class="badge badge-sm bg-gradient-success">Break Taken</span>
                                                <?php elseif ($row['status'] == 'Absent') : ?>
                                                    <span class="badge badge-sm bg-gradient-danger">Break Not taken</span>
                                                <?php elseif ($row['status'] == 'Weekly Off') : ?>
                                                    <span class="badge badge-sm bg-gradient-dark"><?= ucfirst($row['status']) ?></span>
                                                <?php elseif ($row['status'] == 'On Leave') : ?>
                                                    <span class="badge badge-sm bg-gradient-danger"><?= ucfirst($row['status']) ?></span>
                                                <?php elseif ($row['status'] == 'Holiday') : ?>
                                                    <span class="badge badge-sm bg-gradient-warning"><?= ucfirst($row['status']) ?></span>

                                                <?php endif; ?>
                                            </td>

                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="10" class="text-center emp-break-empty">No attendance records found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
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
</script>
<?php include("footer.php") ?>
<script>
    // Function to search table
    function searchTable() {
        var input = document.getElementById("searchInput"); // Search input element
        var filter = input.value.toLowerCase(); // Convert input to lowercase
        var table = document.getElementById("attendanceTable"); // Table element
        var tr = table.getElementsByTagName("tr"); // All rows in the table

        // Loop through all table rows (excluding the header row)
        for (var i = 1; i < tr.length; i++) {
            var tdArray = tr[i].getElementsByTagName("td"); // All cells in the current row
            var found = false;

            // Loop through all cells in the row
            for (var j = 0; j < tdArray.length; j++) {
                if (tdArray[j]) {
                    // Check if the cell text contains the search query
                    if (tdArray[j].textContent.toLowerCase().indexOf(filter) > -1) {
                        found = true;
                        break;
                    }
                }
            }

            // Show or hide the row based on the search result
            tr[i].style.display = found ? "" : "none";
        }
    }
</script>