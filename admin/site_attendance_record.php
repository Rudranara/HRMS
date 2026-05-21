<?php
include("header.php");
$office_name = isset($_GET['office']) ? $_GET['office'] : '';
// Fetch all employees for the dropdown
$employees_stmt = $conn->prepare("SELECT id, name FROM employees");
$employees_stmt->execute();
$employees_result = $employees_stmt->get_result();
// Handle filters
$current_date = date('Y-m-d');
$current_month = date('m');
$current_year = date('Y');
$selected_employee = isset($_GET['employee_id']) ? $_GET['employee_id'] : '';
$selected_month = isset($_GET['month']) ? $_GET['month'] : $current_month;
$selected_year = isset($_GET['year']) ? $_GET['year'] : $current_year;
// Filter dates for the selected month and year
// Filter dates for the selected month and year
$filter_start = "{$selected_year}-{$selected_month}-01";
$filter_end = date("Y-m-t 23:59:59", strtotime($filter_start)); // Add 23:59:59 to include the last day's data

// Fetch attendance data based on the filters
$attendance_stmt = $conn->prepare("    
    SELECT a.*, e.name AS employee_name,
           e.employee_id AS emp_id,
           e.punchin_time, 
           e.punchout_time
    FROM attendance a
    INNER JOIN employees e ON a.employee_id = e.id
    WHERE a.punch_out_time BETWEEN ? AND ? 
    " . ($selected_employee ? " AND a.employee_id = ?" : "") . "
   AND a.office = ?  ORDER BY a.punch_in_time DESC
");
if ($selected_employee) {
    $attendance_stmt->bind_param("sssi", $filter_start, $filter_end, $selected_employee, $office_name);
} else {
    $attendance_stmt->bind_param("sss", $filter_start, $filter_end,$office_name);
}
$attendance_stmt->execute();
$attendance_result = $attendance_stmt->get_result();
// Fetch offices from the database
$offices_query = $conn->query("SELECT office_name, state_name  FROM offices ORDER BY office_name ASC");
$offices = $offices_query->fetch_all(MYSQLI_ASSOC);
?>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-6 mb-4 d-flex align-items-center">
        <h5>Site: <?= htmlspecialchars($office_name) ?> </h5>
        </div>
        <div class="col-12 mb-4 text-end">
            <form method="GET" action="site_attendance_record" class="mb-3">
                <div class="row">

                <div class="col-md-2">
                <label for="site" class="form-label">Select site</label>
    <select name="site" id="site" class="form-control" onchange="redirectToSite()">
        <option value="" selected>Select site</option>
        <?php foreach ($offices as $office): ?>
            <option value="site_attendance_record?office=<?php echo urlencode($office['office_name']); ?>_<?php echo urlencode($office['state_name']); ?>">
                <?= htmlspecialchars($office['office_name']) ?> (<?php echo urlencode($office['state_name']); ?>)
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
    <a href="download_attendance_csv?employee_id=<?= $selected_employee ?>&month=<?= $selected_month ?>&year=<?= $selected_year ?>" class="btn btn-dark mb-0"><i class="bi bi-cloud-arrow-down-fill"></i> CSV</a>
</div>

                </div>
            </form>
        </div>
        <div class="col-12">
            <h6>Attendance Report (<?= date('d M Y', strtotime($filter_start)) ?> - <?= date('d M Y', strtotime($filter_end)) ?>)</h6>
            <div class="card mb-4">
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="table-responsive p-0">
                        <?php if ($attendance_result->num_rows > 0): ?>
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th>Employee Name</th>
                                        <th>Punch In</th>
                                        <th>Punch Out</th>
                                        <th>Working Hours</th>
                                        <th>Status</th>
                                        <th>Action</th>

                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $total_working_hours = 0; ?>
                                    <?php while ($row = $attendance_result->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex px-2 py-1">
                                                    <div class="d-flex flex-column justify-content-center">
                                                        <h6 class="mb-0 text-sm"><?= htmlspecialchars($row['employee_name']) ?></h6>
                                                        <p class="text-xs text-secondary mb-0"><?= htmlspecialchars($row['emp_id']) ?></p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
    <div class="d-flex px-2 py-1">
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
                <?php if (!in_array($row['status'], ['Absent', 'Weekly Off', 'Holiday', 'On Leave']) 
                          && $actual_punchin_time > $expected_punchin_time): ?>
                    <img src="assets/img/logos/tortoise.png" alt="" style="height: 20px;width:20px">
                <?php endif; ?>
                <?= date('H:i:s', strtotime($row['punch_in_time'])) ?>
                <?php if ($row['location_in']): ?>
                    <a onclick="viewLocation(<?= explode(',', $row['location_in'])[0] ?>, <?= explode(',', $row['location_in'])[1] ?>)">
                        <img src="assets/img/location.png" alt="" style="height: 20px;width:20px">
                    </a>
                <?php else: ?>
                    <img src="assets/img/no-gps.png" alt="">
                <?php endif; ?>
            </h6>
        </div>
    </div>
</td>
<td>
    <div class="d-flex px-2 py-1">
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
                <?php if (!in_array($row['status'], ['Absent', 'Weekly Off', 'Holiday', 'On Leave']) 
                          && $actual_punchout_time < $expected_punchout_time): ?>
                    <img src="assets/img/logos/running.png" alt="" style="height: 20px;width:20px">
                <?php endif; ?>
                <?= date('H:i:s', strtotime($row['punch_out_time'])) ?>
                <?php if ($row['location_out']): ?>
                    <a onclick="viewLocation(<?= explode(',', $row['location_out'])[0] ?>, <?= explode(',', $row['location_out'])[1] ?>)">
                        <img src="assets/img/location.png" style="height: 20px;width:20px" alt="">
                    </a>
                <?php else: ?>
                    <img src="assets/img/no-gps.png" alt="">
                <?php endif; ?>
            </h6>
        </div>
    </div>
</td>
<td class="<?php 
    if ($row['working_hours'] >= 10) {
        echo 'text-primary blink';
    } elseif ($row['working_hours'] >= 9) {
        echo 'text-success';
    } else {
        echo 'text-danger';
    }
?>">
    <?= $row['working_hours'] ?>
</td>

                                            <td class="align-middle text-center text-sm">
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
                                            <td>
                                                <!-- Edit and Delete Buttons -->
                                                <a href="edit_attendance?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm"><i class="bi bi-pencil-square"></i></a>
                                                <a href="delete_attendance?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this attendance record?');"><i class="bi bi-trash-fill"></i></a>
                                            </td>
                                        </tr>
                                        <?php $total_working_hours += (float) $row['working_hours']; ?>
                                    <?php endwhile; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3" class="text-right">Total Working Hours:</th>
                                        <th><?= number_format($total_working_hours, 2) ?> hrs</th>
                                    </tr>
                                </tfoot>
                            </table>
                        <?php else: ?>
                            <p>No attendance records found for the selected filters.</p>
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
<?php include("footer.php"); ?>