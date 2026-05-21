<?php
require 'header.php';

// Get the office name from the query string
$office_name = isset($_GET['office']) ? $_GET['office'] : '';

// Set the timezone
date_default_timezone_set('Asia/Kolkata');

// Fetch attendance records
$stmt = $conn->prepare("
    SELECT 
        a.id, 
        e.name AS employee_name, 
        e.employee_id, 
        e.punchin_time, 
        e.punchout_time, 
        a.punch_in_time, 
        a.punch_out_time, 
        a.location_in, 
        a.location_out, 
        a.current_location, 
        a.selfie_in, 
        a.selfie_out, 
        a.status,
        a.working_hours
    FROM attendance a
    JOIN employees e 
    ON a.employee_id = e.id
    WHERE DATE(a.punch_in_time) = CURDATE() AND a.office = ?
    ORDER BY a.punch_in_time DESC
");

// Bind the parameter for the office column
$stmt->bind_param("s", $office_name);

// Execute the statement
$stmt->execute();

// Get the result
$result = $stmt->get_result();

// Fetch offices from the database
$offices_query = $conn->query("SELECT office_name, state_name  FROM offices ORDER BY office_name ASC");
$offices = $offices_query->fetch_all(MYSQLI_ASSOC);
?>

<!-- Rest of the code remains unchanged -->
<!-- End Navbar -->
<div class="container-fluid py-4">
  <div class="row">
    <div class="col-4 mb-4 d-flex align-items-center">
    <h5>Site: <?= htmlspecialchars($office_name) ?> </h5>
    </div>
    <div class="col-4 mb-4 d-flex align-items-center">
    <select name="site" id="site" class="form-control" onchange="redirectToSite()">
        <option value="" selected>Select site</option>
        <?php foreach ($offices as $office): ?>
            <option value="site_manage_attendance?office=<?php echo urlencode($office['office_name']); ?>_<?php echo urlencode($office['state_name']); ?>">
                <?= htmlspecialchars($office['office_name']) ?> (<?php echo urlencode($office['state_name']); ?>)
            </option>
        <?php endforeach; ?>
    </select>
    </div>
    <div class="col-4 mb-4 text-end">
      <a href="attendance_record" class="btn bg-gradient-dark mb-0">Attendace Record</a>
    </div>
    <div class="col-12">
      <div class="card mb-4">
        <div class="card-body px-0 pt-0 pb-2">
          <div class="table-responsive p-0">
            <table class="table align-items-center mb-0">
              <thead>
                <th>Name/ID</th>
                <th>In</th>
                <th>Out</th>
                <th>Wking Hr</th>
                <th>Status</th>
                <th>Action</th>
              </thead>
              <tbody>
                <?php if ($result->num_rows > 0): ?>
                  <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                      <td>
                        <div class="d-flex px-2 py-1">
                          <div class="d-flex flex-column justify-content-center">
                            <h6 class="mb-0 text-sm"><?= htmlspecialchars($row['employee_name']) ?></h6>
                            <p class="text-xs text-secondary mb-0"><?= $row['employee_id'] ?>  <?php if (is_null($row['punch_out_time']) && $row['current_location']): ?>
                          <a onclick="viewLocation(<?= explode(',', $row['current_location'])[0] ?>, <?= explode(',', $row['current_location'])[1] ?>)">
                             <img src="assets/img/location.png" style="height: 20px;width:20px" alt="">
                          </a>
                        <?php else: ?>
                          <img src="assets/img/no-gps.png"  alt="">
                        <?php endif; ?></p>                           
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
        <?php if ($row['punch_out_time']): ?>
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
            <?php else: ?>
              Not Punched<br>Out Yet
            <?php endif; ?>
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
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="10" class="text-center">No attendance records found.</td>
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


  function redirectToSite() {
        var site = document.getElementById('site').value;
        if (site) {
            window.location.href = site; // Redirect to the selected site's page
        }
    }
</script>
<?php include("footer.php") ?>