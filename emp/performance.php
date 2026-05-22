<?php
require 'header.php';

$performanceContext = 'portal';
$performanceUserRole = $_SESSION['role'] ?? $_SESSION['employee_role'] ?? 'Employee';
$performanceUserId = (int) ($_SESSION['employee_id'] ?? 0);

require __DIR__ . '/../performance/includes/bootstrap.php';
?>

<link rel="stylesheet" href="../performance/assets/css/performance.css">

<div class="container-fluid py-4 performance-shell">
  <div class="performance-loading">
    <div class="performance-skeleton large"></div>
    <div class="performance-skeleton"></div>
  </div>

  <div class="performance-module-main d-none">
    <div class="performance-portal-top-nav">
      <?php performance_render_nav($performanceMenu, $performanceView); ?>
    </div>
    <?php performance_render_portal_view($performanceView, $performanceData, $performanceRole, $performanceUserId); ?>
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
$latestPortalCheckin = !empty($performanceData['checkins']) ? $performanceData['checkins'][0] : null;
$latestPortalSelfReview = !empty($performanceData['self_reviews']) ? $performanceData['self_reviews'][0] : null;

$portalEmployeeOptions = '<option value="">Choose employee</option>';
foreach ($performanceData['employees'] as $employee) {
  if ((int) $employee['id'] === (int) $performanceUserId) {
    continue;
  }
  $portalEmployeeOptions .= '<option value="' . (int) $employee['id'] . '">' . performance_escape($employee['name']) . ' - ' . performance_escape($employee['designation']) . '</option>';
}

$portalFeedbackModal = '<div class="row g-3">'
  . '<input type="hidden" class="js-performance-record-id" name="feedback_id">'
  . '<div class="col-md-6"><label class="form-label">Employee</label><select class="form-select" name="employee_id">' . $portalEmployeeOptions . '</select></div>'
    . '<div class="col-md-6"><label class="form-label">Feedback Type</label><select class="form-select" name="feedback_type"><option>Manager</option><option>Peer</option><option>Recognition</option></select></div>'
    . '<div class="col-md-6"><label class="form-label">Title</label><input type="text" class="form-control" name="title"></div>'
  . '<div class="col-md-6"><label class="form-label">Recognition Badge</label><select class="form-select" name="recognition"><option>Team Player</option><option>Leadership Star</option><option>Best Performer</option><option>Innovation Award</option></select></div>'
  . '<div class="col-12"><label class="form-label">Comment</label><textarea class="form-control" rows="4" name="comment"></textarea></div>'
    . '</div>';

$portalGoalModal = '<div class="row g-3">'
  . '<input type="hidden" class="js-performance-record-id" name="goal_id">'
  . '<div class="col-md-6"><label class="form-label">Goal Title</label><input type="text" class="form-control" name="goal_title"></div>'
  . '<div class="col-md-6"><label class="form-label">Employee</label><select class="form-select" name="employee_id">' . $portalEmployeeOptions . '</select></div>'
  . '<div class="col-md-6"><label class="form-label">Weightage</label><input type="number" class="form-control" name="weightage"></div>'
    . '<div class="col-md-6"><label class="form-label">Due Date</label><input type="date" class="form-control" name="due_date"></div>'
    . '<div class="col-md-6"><label class="form-label">Type</label><select class="form-select" name="goal_type"><option>KPI</option><option>KRA</option><option>OKR</option><option>SMART Goals</option></select></div>'
  . '<div class="col-md-6"><label class="form-label">Achievement %</label><input type="number" class="form-control" name="achievement_percentage" value="0"></div>'
  . '<div class="col-md-6"><label class="form-label">Status</label><select class="form-select" name="status"><option>Pending Approval</option><option>On Track</option><option>In Progress</option><option>Completed</option><option>Needs Improvement</option></select></div>'
    . '<div class="col-12"><label class="form-label">Description</label><textarea class="form-control" rows="3" name="description"></textarea></div>'
    . '</div>';

if ($performanceRole === 'manager') {
    performance_render_modal('feedbackModal', 'Post Employee Feedback', $portalFeedbackModal, 'save-feedback');
    performance_render_modal('goalModal', 'Assign Team Goal', $portalGoalModal, 'save-goal');
}
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