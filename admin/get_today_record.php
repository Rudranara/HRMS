<?php
require 'header.php';

// Get query parameters
$employee_id = isset($_GET['employee_id']) ? intval($_GET['employee_id']) : 0;
$date = isset($_GET['date']) ? $_GET['date'] : null;

if ($employee_id && $date) {
    // Fetch all attendance records for the selected employee and date
    $stmt = $conn->prepare("
        SELECT 
            a.id, 
            e.name AS employee_name, 
            a.employee_id AS attendance_employee_id, 
            a.punch_in_time, 
            a.punch_out_time, 
            a.location_in, 
            a.location_out, 
            a.current_location, 
            a.selfie_in, 
            a.selfie_out, 
            TIMESTAMPDIFF(SECOND, a.punch_in_time, a.punch_out_time) AS working_seconds
        FROM attendance a
        JOIN employees e ON a.employee_id = e.id
        WHERE a.employee_id = ? AND DATE(a.punch_in_time) = ?
        ORDER BY a.punch_in_time ASC
    ");
    $stmt->bind_param('is', $employee_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = null; // Handle case where no parameters are passed
}
?>


<!-- Rest of the code remains unchanged -->
<!-- End Navbar -->
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h6 class="mb-0">Attendance Records for <?= htmlspecialchars($date) ?></h6>
            <div class="card mb-4">
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="table-responsive p-0">
                    <table class="table align-items-center mb-0">
    <thead>
        <th>Name/ID</th>
        <th>Punch In</th>
        <th>Punch Out</th>
        <th>Current Location</th>
        <th>Working Hours</th>
        <th>Actions</th>
    </thead>
    <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td>
                        <div class="d-flex flex-column justify-content-center">
                            <h6 class="mb-0 text-sm"><?= htmlspecialchars($row['employee_name']) ?></h6>
                            <small class="text-secondary"><?= $row['attendance_employee_id'] ?></small>
                        </div>
                    </td>
                    <td>
                        <div class="d-flex px-2 py-1">
                            <div>
                                <?php if ($row['selfie_in']): ?>
                                    <img src="<?= $row['selfie_in'] ?>" class="avatar avatar-sm me-3" alt="Selfie In">
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </div>
                            <div class="d-flex flex-column justify-content-center">
                                <h6 class="mb-0 text-sm"><?= $row['punch_in_time'] ?></h6>
                                <?php if ($row['location_in']): ?>
                                    <a onclick="viewLocation(<?= explode(',', $row['location_in'])[0] ?>, <?= explode(',', $row['location_in'])[1] ?>)">
                                        <img src="assets/img/location.png" alt="Location In">
                                    </a>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="d-flex px-2 py-1">
                            <div>
                                <?php if ($row['selfie_out']): ?>
                                    <img src="<?= $row['selfie_out'] ?>" class="avatar avatar-sm me-3" alt="Selfie Out">
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </div>
                            <div class="d-flex flex-column justify-content-center">
                                <h6 class="mb-0 text-sm"><?= $row['punch_out_time'] ?: 'N/A' ?></h6>
                                <?php if ($row['location_out']): ?>
                                    <a onclick="viewLocation(<?= explode(',', $row['location_out'])[0] ?>, <?= explode(',', $row['location_out'])[1] ?>)">
                                        <img src="assets/img/location.png" alt="Location Out">
                                    </a>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td>
                        <?php if ($row['current_location']): ?>
                            <a onclick="viewLocation(<?= explode(',', $row['current_location'])[0] ?>, <?= explode(',', $row['current_location'])[1] ?>)">
                                Current <img src="assets/img/location.png" alt="Current Location">
                            </a>
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php 
                        if (!is_null($row['working_seconds'])) {
                            $hours = floor($row['working_seconds'] / 3600);
                            $minutes = floor(($row['working_seconds'] % 3600) / 60);
                            echo "{$hours} hr {$minutes} min";
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </td>
                    <td>
                        <a href="edit_attendance?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                        <a href="delete_attendance?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" class="text-center">No records found.</td>
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
<script>
    // Open location in Google Maps
    function viewLocation(lat, long) {
    const url = `https://www.google.com/maps?q=${lat},${long}`;
    window.open(url, '_blank');
}
</script>
