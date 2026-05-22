<?php
require 'header.php';

$performanceContext = 'admin';
$performanceUserRole = $_SESSION['role'] ?? $_SESSION['admin_roll'] ?? 'Admin';
$performanceUserId = (int) ($_SESSION['admin_id'] ?? 0);

require __DIR__ . '/../performance/includes/bootstrap.php';
?>

<link rel="stylesheet" href="../performance/assets/css/performance.css">

<div class="container-fluid py-4 performance-shell">
  <div class="performance-loading">
    <div class="performance-skeleton large"></div>
    <div class="performance-skeleton"></div>
  </div>

  <div class="performance-module-main d-none">
    <?php if ($performanceData['uses_demo']): ?>
      <div class="alert alert-light performance-card mb-4 border-0" role="alert">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
          <div>
            <h6 class="mb-1 text-dark">Performance schema not installed yet</h6>
            <p class="text-sm text-secondary mb-0">The module is rendering with integrated demo data. Import `performance/reports/schema.sql` to enable live storage and reporting tables.</p>
          </div>
          <a href="../performance/reports/schema.sql" class="btn btn-admin-secondary mb-0" download>
            <i class="bi bi-download me-2"></i>Download Schema
          </a>
        </div>
      </div>
    <?php endif; ?>

    <?php performance_render_nav($performanceMenu, $performanceView); ?>
    <?php performance_render_admin_view($performanceView, $performanceData); ?>
  </div>
</div>

<div class="performance-toast-wrap">
  <div id="performanceToast" class="performance-toast" style="display:none;">
    <div class="d-flex align-items-center gap-2">
      <span class="performance-icon tone-primary" style="width: 38px; height: 38px;"><i class="bi bi-bell-fill"></i></span>
      <span class="performance-toast-message text-sm text-dark">Saved successfully.</span>
    </div>
  </div>
</div>

<?php
$employeeOptions = '<option value="">Choose employee</option>';
foreach ($performanceData['employees'] as $employee) {
    $employeeOptions .= '<option value="' . (int) $employee['id'] . '">' . performance_escape($employee['name']) . ' - ' . performance_escape($employee['designation']) . '</option>';
}

performance_render_modal('cycleModal', 'Create Review Cycle',
    '<div class="row g-3">'
  . '<input type="hidden" class="js-performance-record-id" name="cycle_id">'
    . '<div class="col-md-6"><label class="form-label">Cycle Name</label><input type="text" class="form-control" name="cycle_name"></div>'
    . '<div class="col-md-6"><label class="form-label">Review Type</label><select class="form-select" name="review_type"><option>Quarterly</option><option>Half-Yearly</option><option>Annual</option></select></div>'
    . '<div class="col-md-6"><label class="form-label">Start Date</label><input type="date" class="form-control" name="start_date"></div>'
    . '<div class="col-md-6"><label class="form-label">End Date</label><input type="date" class="form-control" name="end_date"></div>'
    . '<div class="col-md-6"><label class="form-label">Status</label><select class="form-select" name="status"><option>Draft</option><option>Scheduled</option><option>Active</option><option>Closed</option></select></div>'
    . '<div class="col-12"><label class="form-label">Description</label><textarea class="form-control" rows="3" name="description"></textarea></div>'
    . '</div>', 'save-cycle');

performance_render_modal('goalModal', 'Assign Goal / KPI / OKR',
    '<div class="row g-3">'
  . '<input type="hidden" class="js-performance-record-id" name="goal_id">'
    . '<div class="col-md-6"><label class="form-label">Goal Title</label><input type="text" class="form-control" name="goal_title"></div>'
    . '<div class="col-md-6"><label class="form-label">Employee</label><select class="form-select" name="employee_id">' . $employeeOptions . '</select></div>'
    . '<div class="col-md-4"><label class="form-label">Goal Type</label><select class="form-select" name="goal_type"><option>KPI</option><option>KRA</option><option>OKR</option><option>SMART Goals</option></select></div>'
    . '<div class="col-md-4"><label class="form-label">Weightage</label><input type="number" class="form-control" name="weightage"></div>'
    . '<div class="col-md-4"><label class="form-label">Achievement %</label><input type="number" class="form-control" name="achievement_percentage"></div>'
    . '<div class="col-md-6"><label class="form-label">Due Date</label><input type="date" class="form-control" name="due_date"></div>'
    . '<div class="col-md-6"><label class="form-label">Status</label><select class="form-select" name="status"><option>Pending Approval</option><option>On Track</option><option>In Progress</option><option>Completed</option><option>Needs Improvement</option></select></div>'
    . '<div class="col-12"><label class="form-label">Description</label><textarea class="form-control" rows="3" name="description"></textarea></div>'
    . '</div>', 'save-goal');

performance_render_modal('feedbackModal', 'Post Feedback / Recognition',
    '<div class="row g-3">'
  . '<input type="hidden" class="js-performance-record-id" name="feedback_id">'
    . '<div class="col-md-6"><label class="form-label">Employee</label><select class="form-select" name="employee_id">' . $employeeOptions . '</select></div>'
    . '<div class="col-md-6"><label class="form-label">Feedback Type</label><select class="form-select" name="feedback_type"><option>Manager</option><option>Peer</option><option>Recognition</option></select></div>'
    . '<div class="col-md-6"><label class="form-label">Title</label><input type="text" class="form-control" name="title"></div>'
    . '<div class="col-md-6"><label class="form-label">Recognition Badge</label><select class="form-select" name="recognition"><option>Team Player</option><option>Leadership Star</option><option>Best Performer</option><option>Innovation Award</option></select></div>'
    . '<div class="col-12"><label class="form-label">Comment</label><textarea class="form-control" rows="4" name="comment"></textarea></div>'
    . '</div>', 'save-feedback');

performance_render_modal('checkinModal', 'Log Check-In',
    '<div class="row g-3">'
  . '<input type="hidden" class="js-performance-record-id" name="checkin_id">'
    . '<div class="col-md-6"><label class="form-label">Employee</label><select class="form-select" name="employee_id">' . $employeeOptions . '</select></div>'
    . '<div class="col-md-6"><label class="form-label">Frequency</label><select class="form-select" name="frequency"><option>Weekly</option><option>Monthly</option></select></div>'
    . '<div class="col-12"><label class="form-label">Progress Summary</label><textarea class="form-control" rows="3" name="progress_summary"></textarea></div>'
    . '<div class="col-md-6"><label class="form-label">Achievements</label><textarea class="form-control" rows="3" name="achievements"></textarea></div>'
    . '<div class="col-md-6"><label class="form-label">Challenges</label><textarea class="form-control" rows="3" name="challenges"></textarea></div>'
    . '<div class="col-md-6"><label class="form-label">Manager Comment</label><textarea class="form-control" rows="3" name="manager_comment"></textarea></div>'
    . '<div class="col-md-6"><label class="form-label">Status</label><select class="form-select" name="status"><option>Submitted</option><option>Reviewed</option><option>Draft</option></select></div>'
    . '</div>', 'save-checkin');

performance_render_modal('recognitionModal', 'Issue Recognition',
    '<div class="row g-3">'
  . '<input type="hidden" class="js-performance-record-id" name="badge_id">'
    . '<div class="col-md-6"><label class="form-label">Employee</label><select class="form-select" name="employee_id">' . $employeeOptions . '</select></div>'
    . '<div class="col-md-6"><label class="form-label">Badge</label><select class="form-select" name="badge"><option>Team Player</option><option>Leadership Star</option><option>Best Performer</option><option>Innovation Award</option></select></div>'
    . '<div class="col-md-6"><label class="form-label">Reward Points</label><input type="number" class="form-control" name="reward_points" value="100"></div>'
    . '<div class="col-12"><label class="form-label">Reason</label><textarea class="form-control" rows="4" name="reason"></textarea></div>'
    . '</div>', 'save-recognition');

performance_render_modal('pipModal', 'Create PIP Record',
    '<div class="row g-3">'
  . '<input type="hidden" class="js-performance-record-id" name="pip_id">'
    . '<div class="col-md-6"><label class="form-label">Employee</label><select class="form-select" name="employee_id">' . $employeeOptions . '</select></div>'
    . '<div class="col-md-6"><label class="form-label">Assigned Mentor</label><input type="text" class="form-control" name="mentor_name"></div>'
    . '<div class="col-md-4"><label class="form-label">Deadline</label><input type="date" class="form-control" name="deadline"></div>'
    . '<div class="col-md-4"><label class="form-label">Status</label><select class="form-select" name="status"><option>Active</option><option>Monitoring</option><option>Closed</option></select></div>'
    . '<div class="col-md-4"><label class="form-label">Progress %</label><input type="number" class="form-control" name="progress"></div>'
    . '<div class="col-12"><label class="form-label">Notes</label><textarea class="form-control" rows="4" name="notes"></textarea></div>'
    . '</div>', 'save-pip');
?>

<script>
window.performanceModule = {
  ajaxUrl: '../performance/ajax/actions.php',
  charts: <?= json_encode($performanceData['charts'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
};
</script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="../performance/assets/js/performance.js"></script>

<?php require 'footer.php'; ?>
