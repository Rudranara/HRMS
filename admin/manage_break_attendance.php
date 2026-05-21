<?php
require 'header.php';
date_default_timezone_set('Asia/Kolkata');

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
    WHERE DATE(a.punch_in_time) = CURDATE()
    ORDER BY a.punch_in_time DESC
");
$stmt->execute();
$result = $stmt->get_result();

$offices_query = $conn->query("SELECT office_name, state_name  FROM offices ORDER BY office_name ASC");
$offices = $offices_query->fetch_all(MYSQLI_ASSOC);
?>
<style>
.break-manage-shell {
  padding-bottom: 1.5rem;
}

.break-manage-muted {
  color: #6b7280;
}

.break-manage-toolbar-card,
.break-manage-table-card {
  border: 1px solid rgba(87, 96, 108, 0.12);
  border-radius: 22px;
  box-shadow: 0 14px 34px rgba(31, 41, 55, 0.06);
  background: #fff;
}

.break-manage-toolbar-card {
  padding: 1.25rem;
  margin-bottom: 1.5rem;
}

.break-manage-toolbar-card .form-label {
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: #6b7280;
  margin-bottom: 0.55rem;
}

.break-manage-toolbar-card .form-control {
  min-height: 50px;
  border-radius: 14px;
  border: 1px solid #d8dee7;
  color: #374151;
}

.break-manage-toolbar-card .form-control:focus {
  border-color: #aab7c9;
  box-shadow: 0 0 0 0.2rem rgba(55, 65, 81, 0.08);
}

.break-manage-title {
  margin: 0;
  color: #111827;
  font-size: 1.05rem;
  font-weight: 800;
}

.break-manage-meta {
  color: #94a3b8;
  font-size: 0.78rem;
  margin-top: 0.3rem;
}

.break-manage-btn {
  min-height: 46px;
  border-radius: 14px;
  padding-left: 1rem;
  padding-right: 1rem;
  box-shadow: 0 10px 24px rgba(31, 41, 55, 0.10);
}

.break-manage-btn-primary {
  background: linear-gradient(180deg, #2b2c31 0%, #1f2024 100%);
  color: #fff !important;
  border: 1px solid #2b2c31;
  box-shadow: 0 10px 24px rgba(24, 24, 27, 0.22);
}

.break-manage-btn-primary:hover {
  background: linear-gradient(180deg, #32343a 0%, #23242a 100%);
  color: #fff !important;
  border-color: #32343a;
}

.break-manage-btn-secondary {
  background: #08285c;
  color: #fff !important;
  border: 1px solid #08285c;
}

.break-manage-btn-secondary:hover {
  background: #061f47;
  color: #fff !important;
}

.break-manage-table-card {
  overflow: hidden;
}

.break-manage-table-card .card-body {
  padding: 0;
}

.break-manage-table {
  margin-bottom: 0;
}

.break-manage-table thead th {
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

.break-manage-table tbody td {
  padding-top: 1rem;
  padding-bottom: 1rem;
  border-color: #eef2f7;
  vertical-align: middle;
  text-align: center;
}

.break-manage-table tbody tr:hover {
  background: #fbfcfe;
}

.break-manage-table .avatar,
.break-manage-table img.avatar-sm {
  border-radius: 14px;
  object-fit: cover;
  border: 1px solid #e5e7eb;
}

.break-manage-table h6 {
  color: #111827;
  font-weight: 700;
}

.break-manage-table td .d-flex {
  justify-content: center;
}

.break-manage-table td .d-flex.flex-column,
.break-manage-table td .d-flex .d-flex.flex-column {
  align-items: center;
  text-align: center;
}

.break-manage-table td .text-xs,
.break-manage-table td p {
  text-align: center;
}

.break-manage-table .text-xs {
  color: #6b7280 !important;
}

.break-manage-table .text-primary,
.break-manage-table .text-success {
  color: #334155 !important;
}

.break-manage-table .text-danger {
  color: #7c2d12 !important;
}

.break-manage-row-btn {
  min-width: 3.2rem;
  border: 1px solid #d9e0e8;
  border-radius: 12px;
  box-shadow: 0 10px 18px rgba(31, 41, 55, 0.08);
}

.break-manage-row-btn-edit {
  background: #08285c;
  border-color: #08285c;
  color: #fff;
}

.break-manage-row-btn-edit:hover {
  background: #061f47;
  border-color: #061f47;
  color: #fff;
}

.break-manage-row-btn-delete {
  background: linear-gradient(180deg, #2b2c31 0%, #1f2024 100%);
  border-color: #2b2c31;
  color: #fff;
}

.break-manage-row-btn-delete:hover {
  background: linear-gradient(180deg, #32343a 0%, #23242a 100%);
  border-color: #32343a;
  color: #fff;
}

.break-manage-status-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 104px;
  padding: 0.42rem 0.7rem;
  border-radius: 999px;
  font-size: 0.68rem;
  font-weight: 800;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  border: 1px solid transparent;
}

.break-manage-status-taken {
  background: #e8f7ef;
  border-color: #cfe9da;
  color: #1f8f57;
}

.break-manage-status-not-taken {
  background: #fdf2f2;
  border-color: #f3d6d6;
  color: #991b1b;
}

.break-manage-status-weekoff,
.break-manage-status-holiday {
  background: #f8fafc;
  border-color: #dbe3ed;
  color: #475569;
}

.break-manage-status-leave {
  background: #f5f3ff;
  border-color: #e4ddff;
  color: #5b3dbb;
}

.break-manage-delete-bar {
  padding: 1rem 1.25rem 1.25rem;
  border-top: 1px solid #eef2f7;
  background: linear-gradient(180deg, rgba(248, 250, 252, 0.35) 0%, rgba(255, 255, 255, 1) 100%);
}

.break-manage-checkbox-cell .checkbox__checkmark,
.checkboxes__item .checkbox__checkmark {
  background-color: #1f2937 !important;
  box-shadow: 0 8px 16px rgba(31, 41, 55, 0.14);
}

.break-manage-checkbox-cell {
  width: 64px;
  min-width: 64px;
  text-align: center;
  vertical-align: middle;
  padding-left: 0 !important;
  padding-right: 0 !important;
}

.break-manage-checkbox-cell .checkboxes__item {
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0;
  width: 100%;
}

.break-manage-checkbox-cell .checkbox {
  margin: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
}

.break-manage-location-icon,
.break-manage-gps-icon {
  width: 18px;
  height: 18px;
}

.break-manage-toolbar-card .btn:focus,
.break-manage-row-btn:focus,
.break-manage-btn:focus {
  box-shadow: 0 0 0 0.2rem rgba(8, 40, 92, 0.18) !important;
}

@media (max-width: 991.98px) {
  .break-manage-toolbar-card,
  .break-manage-table-card {
    border-radius: 18px;
  }
}
</style>

<div class="container-fluid container-fluid-main break-manage-shell py-4">
  <div class="row">
    <div class="col-12">
      <div class="break-manage-toolbar-card">
        <div class="row g-3 align-items-end">
          <div class="col-lg-4 col-md-6">
            <div class="break-manage-title">Today Attendance Record For All Sites</div>
            <div class="break-manage-meta">Manage today’s break records in the same clean admin style used across the dashboard.</div>
          </div>
          <div class="col-lg-4 col-md-6">
            <label for="site" class="form-label">Select Site</label>
            <select name="site" id="site" class="form-control" onchange="redirectToSite()">
              <option value="" selected>Select site</option>
              <?php foreach ($offices as $office): ?>
                <option value="site_manage_break_attendance?office=<?php echo urlencode($office['office_name']); ?>_<?php echo urlencode($office['state_name']); ?>">
                  <?= htmlspecialchars($office['office_name']) ?> (<?php echo urlencode($office['state_name']); ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-lg-4 text-lg-end">
            <a href="break_attendance_record" class="btn break-manage-btn break-manage-btn-primary mb-0">Break Record</a>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12">
      <div class="card break-manage-table-card mb-4">
        <div class="card-body px-0 pt-0 pb-2">
          <div class="table-responsive p-0">
            <form method="POST" action="break_delete_multiple">
              <table class="table break-manage-table align-items-center mb-0">
                <thead>
                  <tr>
                    <th class="break-manage-checkbox-cell">
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
                    <th>Wking Hr</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                      <?php
                      $hours = floor($row['working_hours']);
                      $minutes = round(($row['working_hours'] - $hours) * 60);
                      $formatted_working_hours = sprintf("%02d:%02d", $hours, $minutes);
                      ?>
                      <tr>
                        <td class="break-manage-checkbox-cell">
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
                                    <img src="assets/img/location.png" class="break-manage-location-icon" alt="">
                                  </a>
                                <?php else: ?>
                                  <img src="assets/img/no-gps.png" class="break-manage-gps-icon" alt="">
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
                                <?php
                                $record_date = date('Y-m-d', strtotime($row['punch_in_time']));
                                $expected_punchin_time = strtotime("$record_date " . $row['punchin_time']);
                                $actual_punchin_time = strtotime($row['punch_in_time']);
                                ?>
                                <?= date('H:i:s', strtotime($row['punch_in_time'])) ?>
                                <?php if ($row['location_in']): ?>
                                  <a onclick="viewLocation(<?= explode(',', $row['location_in'])[0] ?>, <?= explode(',', $row['location_in'])[1] ?>)">
                                    <img src="assets/img/location.png" class="break-manage-location-icon" alt="">
                                  </a>
                                <?php else: ?>
                                  <img src="assets/img/no-gps.png" class="break-manage-gps-icon" alt="">
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
                                  <?php
                                  $expected_punchout_time = strtotime("$record_date " . $row['punchout_time']);
                                  $actual_punchout_time = strtotime($row['punch_out_time']);
                                  ?>
                                  <?= date('H:i:s', strtotime($row['punch_out_time'])) ?>
                                  <?php if ($row['location_out']): ?>
                                    <a onclick="viewLocation(<?= explode(',', $row['location_out'])[0] ?>, <?= explode(',', $row['location_out'])[1] ?>)">
                                      <img src="assets/img/location.png" class="break-manage-location-icon" alt="">
                                    </a>
                                  <?php else: ?>
                                    <img src="assets/img/no-gps.png" class="break-manage-gps-icon" alt="">
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

                        <td class="align-middle text-center text-sm">
                          <?php if ($row['status'] == 'Present') : ?>
                            <span class="break-manage-status-badge break-manage-status-taken">Break Taken</span>
                          <?php elseif ($row['status'] == 'Absent') : ?>
                            <span class="break-manage-status-badge break-manage-status-not-taken">Break Not Taken</span>
                          <?php elseif ($row['status'] == 'Weekly Off') : ?>
                            <span class="break-manage-status-badge break-manage-status-weekoff"><?= ucfirst($row['status']) ?></span>
                          <?php elseif ($row['status'] == 'On Leave') : ?>
                            <span class="break-manage-status-badge break-manage-status-leave"><?= ucfirst($row['status']) ?></span>
                          <?php elseif ($row['status'] == 'Holiday') : ?>
                            <span class="break-manage-status-badge break-manage-status-holiday"><?= ucfirst($row['status']) ?></span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <a href="edit_break_attendance?id=<?= $row['id'] ?>" class="btn break-manage-row-btn break-manage-row-btn-edit btn-sm"><i class="bi bi-pencil-square"></i></a>
                          <a href="delete_break_attendance?id=<?= $row['id'] ?>" class="btn break-manage-row-btn break-manage-row-btn-delete btn-sm" onclick="return confirm('Are you sure you want to delete this attendance record?');"><i class="bi bi-trash-fill"></i></a>
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
              <div class="break-manage-delete-bar">
                <button type="submit" class="btn break-manage-btn break-manage-btn-primary mb-0" onclick="return confirm('Are you sure you want to delete the selected records?');">
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
  function viewLocation(lat, long) {
    const url = `https://www.google.com/maps?q=${lat},${long}`;
    window.open(url, '_blank');
  }

  function redirectToSite() {
    var site = document.getElementById('site').value;
    if (site) {
      window.location.href = site;
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