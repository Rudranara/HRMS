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
    FROM break_attendance a
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

<style>
.site-break-page {
    padding-bottom: 1.5rem;
}

.site-break-topbar,
.site-break-filter-card,
.site-break-table-card {
    border: 1px solid rgba(87, 96, 108, 0.12);
    border-radius: 22px;
    box-shadow: 0 14px 34px rgba(31, 41, 55, 0.06);
    background: #fff;
}

.site-break-topbar {
    padding: 1.15rem 1.2rem;
    margin-bottom: 1rem;
}

.site-break-topbar-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 1rem;
    align-items: center;
}

.site-break-section-label {
    display: block;
    margin-bottom: 0.45rem;
    color: #6b7280;
    font-size: 0.74rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.site-break-title {
    margin: 0;
    color: #0f172a;
    font-size: 1.45rem;
    font-weight: 800;
    letter-spacing: -0.03em;
}

.site-break-copy {
    margin: 0.35rem 0 0;
    color: #6b7280;
    font-size: 0.92rem;
}

.site-break-site-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 40px;
    padding: 0.55rem 1rem;
    border-radius: 14px;
    background: #f8fafc;
    border: 1px solid #dbe3ed;
    color: #475569;
    font-size: 0.8rem;
    font-weight: 700;
    text-align: center;
}

.site-break-filter-card {
    padding: 1rem 1.1rem;
    margin-bottom: 1rem;
}

.site-break-filter-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 0.85rem;
    align-items: end;
}

.site-break-field-label {
    display: block;
    margin-bottom: 0.45rem;
    color: #6b7280;
    font-size: 0.74rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.site-break-filter-card .form-control {
    min-height: 46px;
    border-radius: 14px;
    border: 1px solid #d8dee7;
    box-shadow: none;
}

.site-break-filter-card .form-control:focus {
    border-color: #1e3a5f;
    box-shadow: 0 0 0 0.18rem rgba(30, 58, 95, 0.12);
}

.site-break-btn-dark {
    min-height: 40px;
    padding: 0.56rem 1rem;
    border-radius: 14px;
    font-size: 0.8rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    background: linear-gradient(135deg, #161616 0%, #2d2d2d 100%) !important;
    color: #fff !important;
    border: none !important;
    box-shadow: 0 12px 24px rgba(17, 24, 39, 0.18);
}

.site-break-btn-dark:hover {
    background: linear-gradient(135deg, #101010 0%, #242424 100%) !important;
    color: #fff !important;
}

.site-break-table-card {
    overflow: hidden;
}

.site-break-table-header {
    padding: 1.15rem 1.2rem;
    border-bottom: 1px solid #eef2f7;
    background: #fff;
}

.site-break-table-title {
    margin: 0;
    color: #0f172a;
    font-size: 1rem;
    font-weight: 700;
}

.site-break-table-copy {
    margin: 0.35rem 0 0;
    color: #6b7280;
    font-size: 0.88rem;
}

.site-break-table-wrap {
    padding: 0 1.2rem 1.15rem;
}

.site-break-table {
    width: 100%;
    margin-bottom: 0;
    border-collapse: collapse;
}

.site-break-table thead th {
    border-bottom: 1px solid #e8edf3;
    color: #6b7280;
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    padding: 1rem 0.95rem;
    white-space: nowrap;
    background: #f8fafc;
}

.site-break-table tbody td {
    padding: 1rem 0.95rem;
    border-bottom: 1px solid #eef2f7;
    color: #1f2937;
    vertical-align: middle;
    font-size: 0.92rem;
}

.site-break-table tbody tr:last-child td {
    border-bottom: none;
}

.site-break-table tbody tr:hover {
    background: #fbfcfe;
}

.site-break-person-name,
.site-break-time-primary {
    color: #0f172a;
    font-weight: 700;
}

.site-break-person-meta,
.site-break-time-secondary {
    color: #6b7280;
    font-size: 0.82rem;
}

.site-break-avatar {
    width: 42px;
    height: 42px;
    object-fit: cover;
    border-radius: 14px;
    border: 1px solid #dbe3ed;
    background: #f8fafc;
}

.site-break-location-icon,
.site-break-state-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-left: 0.28rem;
    vertical-align: middle;
}

.site-break-location-icon img,
.site-break-state-icon img {
    width: 18px;
    height: 18px;
    object-fit: contain;
}

.site-break-hours-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 88px;
    padding: 0.45rem 0.8rem;
    border-radius: 999px;
    border: 1px solid transparent;
    font-size: 0.8rem;
    font-weight: 700;
}

.site-break-hours-badge.text-primary {
    background: #e8f1ff;
    border-color: #cfe0ff;
    color: #345ea8 !important;
}

.site-break-hours-badge.text-success {
    background: #e9f8ef;
    border-color: #cbeed9;
    color: #25744c !important;
}

.site-break-hours-badge.text-danger {
    background: #fbe6e5;
    border-color: #f4c9c7;
    color: #c24141 !important;
}

.site-break-status-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 110px;
    padding: 0.42rem 0.7rem;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    border: 1px solid transparent;
    white-space: nowrap;
}

.site-break-status-present {
    background: #e9f8ef;
    border-color: #cbeed9;
    color: #25744c;
}

.site-break-status-absent,
.site-break-status-onleave {
    background: #fbe6e5;
    border-color: #f4c9c7;
    color: #c24141;
}

.site-break-status-weeklyoff {
    background: #eef2f7;
    border-color: #dbe3ed;
    color: #475569;
}

.site-break-status-holiday {
    background: #fff4da;
    border-color: #f8e2a8;
    color: #9a6b11;
}

.site-break-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: nowrap;
    white-space: nowrap;
}

.site-break-action-btn {
    min-height: 38px;
    padding: 0.5rem 0.9rem;
    border-radius: 12px !important;
    font-size: 0.76rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    margin: 0 !important;
}

.site-break-action-btn.btn-warning {
    background: linear-gradient(135deg, #161616 0%, #2d2d2d 100%) !important;
    color: #fff !important;
    border: none !important;
    box-shadow: 0 12px 24px rgba(17, 24, 39, 0.18);
}

.site-break-action-btn.btn-danger {
    background: #fbe6e5 !important;
    color: #c24141 !important;
    border: 1px solid #f4c9c7 !important;
    box-shadow: none !important;
}

.site-break-empty {
    padding: 1rem 0.95rem !important;
    color: #6b7280 !important;
    font-weight: 600;
    text-align: center;
}

@media (max-width: 991.98px) {
    .site-break-topbar-grid,
    .site-break-filter-grid,
    .site-break-actions {
        grid-template-columns: 1fr;
        flex-direction: column;
        align-items: stretch;
    }

    .site-break-btn-dark,
    .site-break-actions > * {
        width: 100%;
    }
}
</style>

<!-- Rest of the code remains unchanged -->
<!-- End Navbar -->
<div class="container-fluid py-4 site-break-page">
  <div class="row">
    <div class="col-12">
      <div class="site-break-topbar">
        <div class="site-break-topbar-grid">
          <div>
            <span class="site-break-section-label">Break Attendance</span>
            <h6 class="site-break-title">Site Break Attendance</h6>
            <p class="site-break-copy">Track today's break attendance activity for the selected site and manage attendance actions quickly.</p>
          </div>
          <div>
            <span class="site-break-site-chip">Site: <?= htmlspecialchars($office_name) ?></span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12">
      <div class="site-break-filter-card">
        <div class="site-break-filter-grid">
          <div>
            <label for="site" class="site-break-field-label">Select Site</label>
            <select name="site" id="site" class="form-control" onchange="redirectToSite()">
                <option value="" selected>Select site</option>
                <?php foreach ($offices as $office): ?>
                    <option value="site_manage_break_attendance?office=<?php echo urlencode($office['office_name']); ?>_<?php echo urlencode($office['state_name']); ?>">
                        <?= htmlspecialchars($office['office_name']) ?> (<?php echo urlencode($office['state_name']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
          </div>
          <div>
            <a href="break_attendance_record" class="btn site-break-btn-dark mb-0">Attendace Record</a>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12">
      <div class="card site-break-table-card mb-4">
        <div class="site-break-table-header">
          <h6 class="site-break-table-title">Today's Attendance Records</h6>
          <p class="site-break-table-copy">Review employee break start, break end, working hours, and live location details for the selected site.</p>
        </div>
        <div class="table-responsive site-break-table-wrap">
          <table class="table site-break-table align-items-center mb-0">
            <thead>
              <tr>
                <th>Name/ID</th>
                <th>Start On</th>
                <th>End On</th>
                <th>Break Hr</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                  <tr>
                    <td>
                      <div class="d-flex px-2 py-1">
                        <div class="d-flex flex-column justify-content-center">
                          <h6 class="mb-0 text-sm site-break-person-name"><?= htmlspecialchars($row['employee_name']) ?></h6>
                          <p class="mb-0 site-break-person-meta"><?= $row['employee_id'] ?>
                            <?php if (is_null($row['punch_out_time']) && $row['current_location']): ?>
                              <a class="site-break-location-icon" onclick="viewLocation(<?= explode(',', $row['current_location'])[0] ?>, <?= explode(',', $row['current_location'])[1] ?>)">
                                <img src="assets/img/location.png" alt="">
                              </a>
                            <?php else: ?>
                              <span class="site-break-state-icon"><img src="assets/img/no-gps.png" alt=""></span>
                            <?php endif; ?>
                          </p>
                        </div>
                      </div>
                    </td>
                    <td>
                      <div class="d-flex px-2 py-1 align-items-center gap-3">
                        <div>
                          <?php if ($row['selfie_in']): ?>
                              <img src="<?= htmlspecialchars($row['selfie_in']) ?>" class="site-break-avatar" alt="user1">
                          <?php else: ?>
                              <img src="assets/img/user-account (1).png" class="site-break-avatar" alt="">
                          <?php endif; ?>
                        </div>
                        <div class="d-flex flex-column justify-content-center">
                          <h6 class="mb-0 text-sm site-break-time-primary">
                            <?= date('Y-m-d', strtotime($row['punch_in_time'])) ?><br>
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
                                <a class="site-break-location-icon" onclick="viewLocation(<?= explode(',', $row['location_in'])[0] ?>, <?= explode(',', $row['location_in'])[1] ?>)">
                                    <img src="assets/img/location.png" alt="">
                                </a>
                            <?php else: ?>
                                <span class="site-break-state-icon"><img src="assets/img/no-gps.png" alt=""></span>
                            <?php endif; ?>
                          </h6>
                        </div>
                      </div>
                    </td>
                    <td>
                      <div class="d-flex px-2 py-1 align-items-center gap-3">
                        <div>
                          <?php if ($row['selfie_out']): ?>
                              <img src="<?= htmlspecialchars($row['selfie_out']) ?>" class="site-break-avatar" alt="user1">
                          <?php else: ?>
                              <img src="assets/img/user-account (1).png" class="site-break-avatar" alt="">
                          <?php endif; ?>
                        </div>
                        <div class="d-flex flex-column justify-content-center">
                        <?php if ($row['punch_out_time']): ?>
                          <h6 class="mb-0 text-sm site-break-time-primary">
                            <?= date('Y-m-d', strtotime($row['punch_out_time'])) ?><br>
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
                                <a class="site-break-location-icon" onclick="viewLocation(<?= explode(',', $row['location_out'])[0] ?>, <?= explode(',', $row['location_out'])[1] ?>)">
                                    <img src="assets/img/location.png" alt="">
                                </a>
                            <?php else: ?>
                                <span class="site-break-state-icon"><img src="assets/img/no-gps.png" alt=""></span>
                            <?php endif; ?>
                          </h6>
                        <?php else: ?>
                          <div class="site-break-time-secondary">Not Punched<br>Out Yet</div>
                        <?php endif; ?>
                        </div>
                      </div>
                    </td>
                    <td>
                      <span class="site-break-hours-badge <?php 
                          if ($row['working_hours'] >= 10) {
                              echo 'text-primary blink';
                          } elseif ($row['working_hours'] >= 9) {
                              echo 'text-success';
                          } else {
                              echo 'text-danger';
                          }
                      ?>">
                          <?= $row['working_hours'] ?>
                      </span>
                    </td>
                    <td class="align-middle text-center text-sm">
                      <?php if ($row['status'] == 'Present') : ?>
                        <span class="site-break-status-badge site-break-status-present"><?= ucfirst($row['status']) ?></span>
                      <?php elseif ($row['status'] == 'Absent') : ?>
                        <span class="site-break-status-badge site-break-status-absent"><?= ucfirst($row['status']) ?></span>
                      <?php elseif ($row['status'] == 'Weekly Off') : ?>
                        <span class="site-break-status-badge site-break-status-weeklyoff"><?= ucfirst($row['status']) ?></span>
                      <?php elseif ($row['status'] == 'On Leave') : ?>
                        <span class="site-break-status-badge site-break-status-onleave"><?= ucfirst($row['status']) ?></span>
                      <?php elseif ($row['status'] == 'Holiday') : ?>
                        <span class="site-break-status-badge site-break-status-holiday"><?= ucfirst($row['status']) ?></span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div class="site-break-actions">
                        <a href="edit_break_attendance?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm site-break-action-btn"><i class="bi bi-pencil-square"></i></a>
                        <a href="delete_break_attendance?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm site-break-action-btn" onclick="return confirm('Are you sure you want to delete this attendance record?');"><i class="bi bi-trash-fill"></i></a>
                      </div>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="10" class="site-break-empty">No attendance records found.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
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