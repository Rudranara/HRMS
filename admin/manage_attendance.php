<?php
require 'header.php';
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
        a.working_hours,
        a.break_hours
    FROM attendance a
    JOIN employees e 
    ON a.employee_id = e.id
    WHERE DATE(a.punch_in_time) = CURDATE()
    ORDER BY a.punch_in_time DESC
");
$stmt->execute();
$result = $stmt->get_result();
// Fetch offices from the database
$offices_query = $conn->query("SELECT office_name, state_name  FROM offices ORDER BY office_name ASC");
$offices = $offices_query->fetch_all(MYSQLI_ASSOC);
?>
<style>
.attendance-shell {
  padding-bottom: 1.5rem;
}

.attendance-muted {
  color: #6b7280;
}

.attendance-toolbar-card,
.attendance-table-card {
  border: 1px solid rgba(87, 96, 108, 0.12);
  border-radius: 22px;
  box-shadow: 0 14px 34px rgba(31, 41, 55, 0.06);
  background: #fff;
}

.attendance-toolbar-card {
  padding: 1.25rem;
  margin-bottom: 1.5rem;
}

.attendance-toolbar-card .form-label {
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: #6b7280;
  margin-bottom: 0.55rem;
}

.attendance-toolbar-card .form-control,
.attendance-toolbar-card .form-select {
  min-height: 50px;
  border-radius: 14px;
  border: 1px solid #d8dee7;
  color: #374151;
}

.attendance-toolbar-card .form-control:focus,
.attendance-toolbar-card .form-select:focus {
  border-color: #aab7c9;
  box-shadow: 0 0 0 0.2rem rgba(55, 65, 81, 0.08);
}

.attendance-toolbar-card .btn {
  min-height: 46px;
  border-radius: 14px;
  padding-left: 1rem;
  padding-right: 1rem;
  box-shadow: 0 10px 24px rgba(31, 41, 55, 0.10);
}

.attendance-toolbar-card .attendance-top-btn {
  min-width: 150px;
}

.attendance-top-btn-secondary {
  background: #08285c;
  color: #fff !important;
  border: 1px solid #08285c;
}

.attendance-top-btn-secondary:hover {
  background: #061f47;
  color: #fff !important;
}

.attendance-top-btn-primary {
  background: linear-gradient(180deg, #2b2c31 0%, #1f2024 100%);
  color: #fff !important;
  border: 1px solid #2b2c31;
  box-shadow: 0 10px 24px rgba(24, 24, 27, 0.22);
}

.attendance-top-btn-primary:hover {
  background: linear-gradient(180deg, #32343a 0%, #23242a 100%);
  color: #fff !important;
  border-color: #32343a;
}

.attendance-page-title {
  margin: 0;
  color: #111827;
  font-size: 1.05rem;
  font-weight: 800;
}

.attendance-page-meta {
  color: #94a3b8;
  font-size: 0.78rem;
  margin-top: 0.3rem;
}

.attendance-table-card {
  overflow: hidden;
}

.attendance-table-card .card-body {
  padding: 0;
}

.attendance-table {
  margin-bottom: 0;
}

.attendance-table thead th {
  border-bottom: 1px solid #e8edf5;
  background: #f8fafc;
  color: #64748b;
  font-size: 0.74rem;
  font-weight: 800;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  padding-top: 1rem;
  padding-bottom: 1rem;
  white-space: nowrap;
  text-align: center;
}

.attendance-table tbody td {
  padding-top: 1rem;
  padding-bottom: 1rem;
  border-color: #eef2f7;
  vertical-align: middle;
  text-align: center;
}

.attendance-table tbody tr:first-child td {
  border-top: 0;
}

.attendance-table tbody tr {
  transition: background-color 0.2s ease;
}

.attendance-table tbody tr:hover {
  background: #fbfcfe;
}

.attendance-table .avatar,
.attendance-table img.avatar-sm {
  border-radius: 14px;
  object-fit: cover;
  border: 1px solid #e5e7eb;
}

.attendance-table h6 {
  color: #111827;
  font-weight: 700;
}

.attendance-table td .d-flex {
  justify-content: center;
}

.attendance-table td .d-flex.flex-column,
.attendance-table td .d-flex .d-flex.flex-column {
  align-items: center;
  text-align: center;
}

.attendance-table td .text-xs,
.attendance-table td p {
  text-align: center;
}

.attendance-table .text-xs {
  color: #6b7280 !important;
}

.attendance-table .text-primary,
.attendance-table .text-success {
  color: #334155 !important;
}

.attendance-table .text-danger {
  color: #7c2d12 !important;
}

.attendance-table .btn.btn-sm {
  border-radius: 12px;
  min-width: 3rem;
  box-shadow: 0 10px 18px rgba(31, 41, 55, 0.08);
}

.attendance-table .attendance-row-btn {
  min-width: 3.2rem;
  border: 1px solid #d9e0e8;
}

.attendance-table .attendance-row-btn-edit {
  background: #08285c;
  border-color: #08285c;
  color: #fff;
}

.attendance-table .attendance-row-btn-edit:hover {
  background: #061f47;
  border-color: #061f47;
  color: #fff;
}

.attendance-table .attendance-row-btn-delete {
  background: linear-gradient(180deg, #2b2c31 0%, #1f2024 100%);
  border-color: #2b2c31;
  color: #fff;
  box-shadow: 0 10px 20px rgba(24, 24, 27, 0.18);
}

.attendance-table .attendance-row-btn-delete:hover {
  background: linear-gradient(180deg, #32343a 0%, #23242a 100%);
  border-color: #32343a;
  color: #fff;
}

.attendance-status-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 86px;
  padding: 0.42rem 0.7rem;
  border-radius: 999px;
  font-size: 0.68rem;
  font-weight: 800;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  border: 1px solid transparent;
}

.attendance-status-present {
  background: #e8f7ef;
  border-color: #cfe9da;
  color: #1f8f57;
}

.attendance-status-absent {
  background: #fdf2f2;
  border-color: #f3d6d6;
  color: #991b1b;
}

.attendance-status-weekoff,
.attendance-status-holiday {
  background: #f8fafc;
  border-color: #dbe3ed;
  color: #475569;
}

.attendance-status-leave {
  background: #f5f3ff;
  border-color: #e4ddff;
  color: #5b3dbb;
}

.attendance-delete-bar {
  padding: 1rem 1.25rem 1.25rem;
  border-top: 1px solid #eef2f7;
  background: linear-gradient(180deg, rgba(248, 250, 252, 0.35) 0%, rgba(255, 255, 255, 1) 100%);
}

.attendance-delete-btn {
  background: linear-gradient(180deg, #2b2c31 0%, #1f2024 100%);
  border-color: #2b2c31;
  color: #fff;
  border-radius: 14px;
  box-shadow: 0 10px 24px rgba(24, 24, 27, 0.22);
}

.attendance-delete-btn:hover {
  background: linear-gradient(180deg, #32343a 0%, #23242a 100%);
  border-color: #32343a;
  color: #fff;
}

.attendance-table .btn:focus,
.attendance-toolbar-card .btn:focus,
.attendance-delete-btn:focus {
  box-shadow: 0 0 0 0.2rem rgba(8, 40, 92, 0.18) !important;
}

.attendance-checkbox-cell .checkbox__checkmark,
.checkboxes__item .checkbox__checkmark {
  background-color: #1f2937 !important;
  box-shadow: 0 8px 16px rgba(31, 41, 55, 0.14);
}

.attendance-checkbox-cell {
  width: 64px;
  min-width: 64px;
  text-align: center;
  vertical-align: middle;
  padding-left: 0 !important;
  padding-right: 0 !important;
}

.attendance-checkbox-cell .checkboxes__item {
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0;
  width: 100%;
}

.attendance-checkbox-cell .checkbox {
  margin: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
}

.attendance-location-icon,
.attendance-gps-icon {
  width: 18px;
  height: 18px;
}

@media (max-width: 991.98px) {
  .attendance-toolbar-card,
  .attendance-table-card {
    border-radius: 18px;
  }
}
</style>

<div class="container-fluid container-fluid-main attendance-shell py-4">
  <div class="row">
    <div class="col-12">
      <div class="attendance-toolbar-card">
        <div class="row g-3 align-items-end">
          <div class="col-lg-4 col-md-6">
            <div class="attendance-page-title">Today Attendance Record For All Sites</div>
            <div class="attendance-page-meta">Attendance management</div>
          </div>
          <div class="col-lg-4 col-md-6">
            <label for="site" class="form-label">Select Site</label>
            <select name="site" id="site" class="form-control" onchange="redirectToSite()">
              <option value="" selected>Select site</option>
              <?php foreach ($offices as $office): ?>
                <option value="site_manage_attendance?office=<?php echo urlencode($office['office_name']); ?>_<?php echo urlencode($office['state_name']); ?>">
                  <?= htmlspecialchars($office['office_name']) ?> (<?php echo urlencode($office['state_name']); ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-lg-4 text-lg-end">
            <a href="forgot_punchout_requests" class="btn attendance-top-btn attendance-top-btn-secondary mb-0 me-2">
              Forgot Punch-Out
            </a>
            <a href="attendance_record" class="btn attendance-top-btn attendance-top-btn-primary mb-0">Attendace Record</a>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12">
      <div class="card attendance-table-card mb-4">
        <div class="card-body px-0 pt-0 pb-2">
          <div class="table-responsive p-0">
            <form method="POST" action="delete_multiple">
              <table class="table attendance-table align-items-center mb-0">
                <thead>
                  <th class="attendance-checkbox-cell">

                    <div class="checkboxes__item">
                      <label class="checkbox style-h">
                        <input type="checkbox" id="select_all">
                        <div class="checkbox__checkmark"></div>

                      </label>
                    </div>
                  </th>
                  <th>Name/ID</th>
                  <th>In</th>
                  <th>Out</th>
                  <th>W Hrs</th>
                  <th>B Hrs</th>
                  <th>Status</th>
                  <th>Action</th>
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

<?php
                      // Convert decimal working hours to hours and minutes
                      $hours = floor($row['break_hours']); // Extract hours
                      $minutes = round(($row['break_hours'] - $hours) * 60); // Convert decimal to minutes
                      $formatted_break_hours = sprintf("%02d:%02d", $hours, $minutes);
                      ?>
                      <tr>
                        <td class="attendance-checkbox-cell">
                          <div class="checkboxes__item">
                            <label class="checkbox style-h">
                              <input type="checkbox" name="ids[]" value="<?= $row['id'] ?>">
                              <div class="checkbox__checkmark"></div>

                            </label>
                          </div>
                        </td>
                        <td>
                          <div class="d-flex px-2 py-1">
                            <div class="d-flex flex-column justify-content-center">
                              <h6 class="mb-0 text-sm"><?= htmlspecialchars($row['employee_name']) ?></h6>
                              <p class="text-xs text-secondary mb-0"><?= $row['employee_id'] ?> <?php if (is_null($row['punch_out_time']) && $row['current_location']): ?>
                                  <a onclick="viewLocation(<?= explode(',', $row['current_location'])[0] ?>, <?= explode(',', $row['current_location'])[1] ?>)">
                                    <img src="assets/img/location.png" class="attendance-location-icon" alt="">
                                  </a>
                                <?php else: ?>
                                  <img src="assets/img/no-gps.png" class="attendance-gps-icon" alt="">
                                <?php endif; ?>
                              </p>
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
                                <?php if (
                                  !in_array($row['status'], ['Absent', 'Weekly Off', 'Holiday', 'On Leave'])
                                  && $actual_punchin_time > $expected_punchin_time
                                ): ?>
                                  <img src="assets/img/logos/tortoise.png" alt="" style="height: 20px;width:20px">
                                <?php endif; ?>
                                <?= date('H:i:s', strtotime($row['punch_in_time'])) ?>
                                <?php if ($row['location_in']): ?>
                                  <a onclick="viewLocation(<?= explode(',', $row['location_in'])[0] ?>, <?= explode(',', $row['location_in'])[1] ?>)">
                                    <img src="assets/img/location.png" class="attendance-location-icon" alt="">
                                  </a>
                                <?php else: ?>
                                  <img src="assets/img/no-gps.png" class="attendance-gps-icon" alt="">
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
                                  <?php if (
                                    !in_array($row['status'], ['Absent', 'Weekly Off', 'Holiday', 'On Leave'])
                                    && $actual_punchout_time < $expected_punchout_time
                                  ): ?>
                                    <img src="assets/img/logos/running.png" alt="" style="height: 20px;width:20px">
                                  <?php endif; ?>
                                  <?= date('H:i:s', strtotime($row['punch_out_time'])) ?>
                                  <?php if ($row['location_out']): ?>
                                    <a onclick="viewLocation(<?= explode(',', $row['location_out'])[0] ?>, <?= explode(',', $row['location_out'])[1] ?>)">
                                      <img src="assets/img/location.png" class="attendance-location-icon" alt="">
                                    </a>
                                  <?php else: ?>
                                    <img src="assets/img/no-gps.png" class="attendance-gps-icon" alt="">
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
                          <?= $formatted_working_hours ?>
                        </td>
                        <td class="<?php
                                    if ($row['break_hours'] <= 1) {
                                      echo 'text-primary blink';
                                    } elseif ($row['break_hours'] <= 1) {
                                      echo 'text-success';
                                    } else {
                                      echo 'text-danger';
                                    }
                                    ?>">
                          <?= $formatted_break_hours ?>
                        </td>

                        <td class="align-middle text-center text-sm">
                          <?php if ($row['status'] == 'Present') : ?>
                            <span class="attendance-status-badge attendance-status-present"><?= ucfirst($row['status']) ?></span>
                          <?php elseif ($row['status'] == 'Absent') : ?>
                            <span class="attendance-status-badge attendance-status-absent"><?= ucfirst($row['status']) ?></span>
                          <?php elseif ($row['status'] == 'Weekly Off') : ?>
                            <span class="attendance-status-badge attendance-status-weekoff"><?= ucfirst($row['status']) ?></span>
                          <?php elseif ($row['status'] == 'On Leave') : ?>
                            <span class="attendance-status-badge attendance-status-leave"><?= ucfirst($row['status']) ?></span>
                          <?php elseif ($row['status'] == 'Holiday') : ?>
                            <span class="attendance-status-badge attendance-status-holiday"><?= ucfirst($row['status']) ?></span>

                          <?php endif; ?>
                        </td>
                        <td>
                          <!-- Edit and Delete Buttons -->
                          <a href="edit_attendance?id=<?= $row['id'] ?>" class="btn attendance-row-btn attendance-row-btn-edit btn-sm"><i class="bi bi-pencil-square"></i></a>
                          <a href="delete_attendance?id=<?= $row['id'] ?>" class="btn attendance-row-btn attendance-row-btn-delete btn-sm" onclick="return confirm('Are you sure you want to delete this attendance record?');"><i class="bi bi-trash-fill"></i></a>
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
              <div class="attendance-delete-bar">
                <button type="submit" class="btn attendance-delete-btn mb-0" onclick="return confirm('Are you sure you want to delete the selected records?');">
                  Delete Selected
                </button>
              </div>
            </form>
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

<?php include("footer.php") ?>