<?php

require_once __DIR__ . '/components.php';

function performance_render_dashboard_widgets($data)
{
    echo '<div class="row">';
    foreach ($data['metrics'] as $metric) {
        performance_render_metric_card($metric);
    }
    echo '</div>';
}

function performance_render_admin_view($view, $data)
{
    switch ($view) {
        case 'review-cycles':
            performance_render_page_head('Appraisal Cycle Management', 'Create and monitor quarterly, half-yearly and annual review cycles with workflow visibility.', array(array('label' => 'Create Cycle', 'href' => 'javascript:void(0);', 'class' => 'btn-admin-primary', 'icon' => 'bi bi-calendar-plus-fill', 'modal' => 'cycleModal')));
            ?>
            <div class="card performance-card mb-4"><div class="card-body">
                            <div class="table-responsive"><table class="table performance-table mb-0"><thead><tr><th>Cycle</th><th>Type</th><th>Start</th><th>End</th><th>Status</th><th>Workflow</th><th>Action</th></tr></thead><tbody>
              <?php foreach ($data['cycles'] as $cycle): ?>
                                <tr><td><strong><?= performance_escape($cycle['name']) ?></strong><div class="text-xs text-secondary"><?= performance_escape($cycle['description']) ?></div></td><td><?= performance_escape($cycle['type']) ?></td><td><?= performance_escape($cycle['start_date']) ?></td><td><?= performance_escape($cycle['end_date']) ?></td><td><?php performance_render_status($cycle['status']); ?></td><td><?= performance_escape($cycle['workflow']) ?></td><td><?php performance_render_edit_modal_button('cycleModal', array('cycle_id' => $cycle['id'], 'cycle_name' => $cycle['name'], 'review_type' => $cycle['type'], 'start_date' => $cycle['start_date'], 'end_date' => $cycle['end_date'], 'status' => $cycle['status'], 'description' => $cycle['description']), 'Cycle'); ?></td></tr>
              <?php endforeach; ?>
              </tbody></table></div>
            </div></div>
            <?php
            break;

        case 'goals-kpis':
            performance_render_page_head('Goal / KPI / OKR Management', 'Assign weighted goals, track progress, approval state and timelines with search-ready cards.', array(array('label' => 'Assign Goal', 'href' => 'javascript:void(0);', 'class' => 'btn-admin-primary', 'icon' => 'bi bi-plus-circle-fill', 'modal' => 'goalModal')));
            echo '<div class="row">';
            foreach ($data['goals'] as $goal) {
                echo '<div class="col-xl-4 col-md-6 mb-4"><div class="card performance-card performance-hover h-100"><div class="card-body">';
                echo '<div class="d-flex justify-content-between gap-2 mb-3"><div><p class="performance-label mb-2">' . performance_escape($goal['type']) . '</p><h5 class="mb-1">' . performance_escape($goal['title']) . '</h5><p class="text-sm text-secondary mb-0">' . performance_escape($goal['employee_name']) . ' · ' . performance_escape($goal['department']) . '</p></div>';
                performance_render_status($goal['status']);
                echo '</div><p class="text-sm text-secondary mb-3">' . performance_escape($goal['description']) . '</p>';
                performance_render_progress($goal['achievement_percentage'], $goal['achievement_percentage'] >= 80 ? 'success' : ($goal['achievement_percentage'] >= 60 ? 'warning' : 'danger'));
                echo '<div class="d-flex justify-content-between mt-2 text-sm"><span>Achievement</span><span>' . performance_percent($goal['achievement_percentage']) . '</span></div><div class="mt-3">';
                performance_render_edit_modal_button('goalModal', array('goal_id' => $goal['id'], 'employee_id' => $goal['employee_id'], 'goal_title' => $goal['title'], 'goal_type' => $goal['type'], 'weightage' => $goal['weightage'], 'achievement_percentage' => $goal['achievement_percentage'], 'due_date' => $goal['due_date'], 'status' => $goal['status'], 'description' => $goal['description']), 'Goal');
                echo '</div>';
                echo '</div></div></div>';
            }
            echo '</div>';
            break;

        case 'feedback':
            performance_render_page_head('Continuous Feedback System', 'Manager notes, peer feedback, appreciation feed and badge-style recognition in one timeline.', array(array('label' => 'Post Feedback', 'href' => 'javascript:void(0);', 'class' => 'btn-admin-primary', 'icon' => 'bi bi-chat-left-dots-fill', 'modal' => 'feedbackModal')));
            echo '<div class="row"><div class="col-xl-8 mb-4"><div class="card performance-card"><div class="card-body d-grid gap-3">';
            foreach ($data['feedbacks'] as $feedback) {
                echo '<div class="performance-feed-item performance-hover"><div class="d-flex justify-content-between gap-2 mb-2"><div><h6 class="mb-1">' . performance_escape($feedback['title']) . '</h6><p class="text-sm text-secondary mb-0">' . performance_escape($feedback['author']) . ' · ' . performance_escape($feedback['role']) . '</p></div><div class="d-flex align-items-center gap-2">';
                performance_render_edit_modal_button('feedbackModal', array('feedback_id' => $feedback['id'], 'employee_id' => $feedback['employee_id'], 'feedback_type' => $feedback['feedback_type'] ?? $feedback['role'], 'title' => $feedback['title'], 'recognition' => $feedback['recognition'], 'comment' => $feedback['comment']), 'Feedback');
                performance_render_status($feedback['recognition']);
                echo '</div></div><p class="text-sm mb-0">' . performance_escape($feedback['comment']) . '</p></div>';
            }
            echo '</div></div></div><div class="col-xl-4 mb-4"><div class="card performance-card h-100"><div class="card-body"><div style="height:280px"><canvas id="goalStatusChart"></canvas></div></div></div></div></div>';
            break;

        case 'check-ins':
            performance_render_page_head('Weekly / Monthly Check-Ins', 'Capture progress updates, blockers, achievements and manager responses with timeline history.', array(array('label' => 'Log Check-In', 'href' => 'javascript:void(0);', 'class' => 'btn-admin-primary', 'icon' => 'bi bi-calendar-plus-fill', 'modal' => 'checkinModal')));
            echo '<div class="card performance-card"><div class="card-body d-grid gap-3">';
            foreach ($data['checkins'] as $checkin) {
                echo '<div class="performance-feed-item"><div class="d-flex justify-content-between gap-3 mb-2"><div><h6 class="mb-1">' . performance_escape($checkin['employee_name']) . '</h6><p class="text-sm text-secondary mb-0">' . performance_escape($checkin['frequency']) . ' check-in</p></div><div class="d-flex align-items-center gap-2">';
                performance_render_edit_modal_button('checkinModal', array('checkin_id' => $checkin['id'], 'employee_id' => $checkin['employee_id'], 'frequency' => $checkin['frequency'], 'progress_summary' => $checkin['progress_summary'], 'achievements' => $checkin['achievements'], 'challenges' => $checkin['challenges'], 'manager_comment' => $checkin['manager_comment'], 'status' => $checkin['status']), 'Check-In');
                performance_render_status($checkin['status']);
                echo '</div></div><div class="row g-3 text-sm"><div class="col-md-6"><strong>Progress</strong><br><span class="text-secondary">' . performance_escape($checkin['progress_summary']) . '</span></div><div class="col-md-6"><strong>Achievements</strong><br><span class="text-secondary">' . performance_escape($checkin['achievements']) . '</span></div><div class="col-md-6"><strong>Challenges</strong><br><span class="text-secondary">' . performance_escape($checkin['challenges']) . '</span></div><div class="col-md-6"><strong>Manager Comment</strong><br><span class="text-secondary">' . performance_escape($checkin['manager_comment']) . '</span></div></div></div>';
            }
            echo '</div></div>';
            break;

        case 'self-reviews':
            performance_render_page_head('Self Review Module', 'Collect self assessment, rating, achievements and goal completion review with responsive forms.', array());
            echo '<div class="row"><div class="col-xl-7 mb-4"><div class="card performance-card"><div class="card-body"><form class="js-performance-form" data-action="save-self-review"><div class="row g-3"><div class="col-md-6"><label class="form-label">Employee</label><select class="form-select" name="employee_id">';
            foreach ($data['employees'] as $employee) {
                echo '<option value="' . (int) $employee['id'] . '">' . performance_escape($employee['name']) . '</option>';
            }
            echo '</select></div><div class="col-md-6"><label class="form-label">Completion %</label><input class="form-control" type="number" name="completion_percentage" min="0" max="100" value="85"></div><div class="col-12"><label class="form-label">Rating</label><select class="form-select" name="rating"><option>1</option><option>2</option><option>3</option><option selected>4</option><option>5</option></select></div><div class="col-md-6"><label class="form-label">Achievements</label><textarea class="form-control" rows="4" name="achievements"></textarea></div><div class="col-md-6"><label class="form-label">Additional Comments</label><textarea class="form-control" rows="4" name="additional_comments"></textarea></div><div class="col-12"><label class="form-label">Goal Completion Review</label><textarea class="form-control" rows="3" name="goal_review"></textarea></div></div><div class="d-flex justify-content-end mt-3"><button class="btn btn-admin-primary mb-0" type="submit">Submit Self Review</button></div></form></div></div></div>';
            echo '<div class="col-xl-5 mb-4"><div class="card performance-card h-100"><div class="card-body d-grid gap-3">';
            foreach ($data['self_reviews'] as $review) {
                echo '<div class="performance-feed-item"><div class="d-flex justify-content-between gap-2 mb-2"><div><h6 class="mb-1">' . performance_escape($review['employee_name']) . '</h6><p class="text-sm text-secondary mb-0">Self rating ' . (int) $review['rating'] . '/5</p></div>';
                performance_render_status(performance_rating_from_score($review['completion']));
                echo '</div>';
                performance_render_progress($review['completion'], 'success');
                echo '<div class="text-sm text-secondary mt-2">Completion ' . performance_escape($review['completion']) . '%</div></div>';
            }
            echo '</div></div></div></div>';
            break;

        case 'manager-reviews':
            performance_render_page_head('Manager Review Module', 'Technical, communication, productivity, teamwork, attendance and leadership ratings with comparison charts.', array());
            echo '<div class="row"><div class="col-xl-7 mb-4"><div class="card performance-card"><div class="card-body"><form class="js-performance-form" data-action="save-manager-review"><div class="row g-3"><div class="col-md-6"><label class="form-label">Employee</label><select class="form-select" name="employee_id">';
            foreach ($data['employees'] as $employee) {
                echo '<option value="' . (int) $employee['id'] . '">' . performance_escape($employee['name']) . '</option>';
            }
            echo '</select></div><div class="col-md-6"><label class="form-label">Promotion Recommendation</label><select class="form-select" name="promotion_recommendation"><option>Recommended</option><option>Future Ready</option><option>Not This Cycle</option></select></div>';
            $fields = array('technical_skill' => 'Technical Skill', 'communication' => 'Communication', 'productivity' => 'Productivity', 'teamwork' => 'Teamwork', 'attendance' => 'Attendance', 'leadership' => 'Leadership');
            foreach ($fields as $name => $label) {
                echo '<div class="col-md-6"><label class="form-label">' . performance_escape($label) . '</label><input type="range" min="1" max="5" step="0.1" value="4" class="form-range" name="' . performance_escape($name) . '"></div>';
            }
            echo '<div class="col-md-6"><label class="form-label">Increment Recommendation</label><input class="form-control" name="increment_recommendation" value="8%"></div><div class="col-md-6"><label class="form-label">Comments</label><textarea class="form-control" rows="3" name="comments"></textarea></div></div><div class="d-flex justify-content-end mt-3"><button class="btn btn-admin-primary mb-0" type="submit">Submit Manager Review</button></div></form></div></div></div>';
            echo '<div class="col-xl-5 mb-4"><div class="card performance-card h-100"><div class="card-body"><div style="height:260px"><canvas id="reviewRadarChart"></canvas></div></div></div></div></div>';
            break;

        case 'reports':
            performance_render_page_head('Reports & Analytics', 'Employee and department reports with PDF/Excel export, trends, heatmap and department comparison.', array(array('label' => 'Export Excel', 'href' => '../performance/reports/export.php?format=excel', 'class' => 'btn-admin-secondary', 'icon' => 'bi bi-file-earmark-spreadsheet-fill'), array('label' => 'Export PDF', 'href' => '../performance/reports/export.php?format=pdf', 'class' => 'btn-admin-primary', 'icon' => 'bi bi-filetype-pdf')));
            echo '<div class="row"><div class="col-xl-8 mb-4"><div class="card performance-card"><div class="card-body"><div class="table-responsive"><table class="table performance-table mb-0"><thead><tr><th>Employee</th><th>Department</th><th>KPI</th><th>Manager</th><th>Attendance</th><th>Self</th><th>Final</th><th>Rating</th></tr></thead><tbody>';
            foreach ($data['results'] as $result) {
                echo '<tr><td>' . performance_escape($result['employee']) . '</td><td>' . performance_escape($result['department']) . '</td><td>' . performance_escape($result['kpi_score']) . '</td><td>' . performance_escape($result['manager_score']) . '</td><td>' . performance_escape($result['attendance_score']) . '</td><td>' . performance_escape($result['self_review_score']) . '</td><td><strong>' . performance_escape($result['final_score']) . '</strong></td><td>';
                performance_render_status($result['final_rating']);
                echo '</td></tr>';
            }
            echo '</tbody></table></div></div></div></div><div class="col-xl-4 mb-4"><div class="card performance-card h-100"><div class="card-body"><div style="height:260px"><canvas id="departmentPerformanceChart"></canvas></div></div></div></div></div>';
            break;

        case 'recognition':
            performance_render_page_head('Recognition & Rewards', 'Badge cards, reward points, appreciation feed and kudos moments that remain consistent with the current HRMS UI.', array(array('label' => 'Issue Recognition', 'href' => 'javascript:void(0);', 'class' => 'btn-admin-primary', 'icon' => 'bi bi-award-fill', 'modal' => 'recognitionModal')));
            echo '<div class="row">';
            foreach ($data['badges'] as $badge) {
                echo '<div class="col-xl-3 col-md-6 mb-4"><div class="card performance-card performance-hover h-100"><div class="card-body"><div class="d-flex justify-content-between align-items-start mb-3"><span class="performance-icon tone-warning"><i class="bi bi-award-fill"></i></span><span class="performance-status performance-status-warning">' . (int) $badge['points'] . ' pts</span></div><h5 class="mb-1">' . performance_escape($badge['badge']) . '</h5><p class="text-sm text-secondary mb-0">' . performance_escape($badge['employee_name']) . '</p><p class="text-sm text-secondary mt-3 mb-3">' . performance_escape($badge['reason']) . '</p>';
                performance_render_edit_modal_button('recognitionModal', array('badge_id' => $badge['id'], 'employee_id' => $badge['employee_id'], 'badge' => $badge['badge'], 'reward_points' => $badge['points'], 'reason' => $badge['reason']), 'Recognition');
                echo '</div></div></div>';
            }
            echo '</div>';
            break;

        case 'pip':
            performance_render_page_head('Performance Improvement Plan (PIP)', 'Timeline-driven improvement tracking with mentor ownership, review deadlines and HR monitoring.', array(array('label' => 'Create PIP', 'href' => 'javascript:void(0);', 'class' => 'btn-admin-primary', 'icon' => 'bi bi-activity', 'modal' => 'pipModal')));
            if (empty($data['pip_records'])) {
                performance_render_empty('No active PIP records', 'PIP items will appear here when low-performer workflows are created.');
                return;
            }
            echo '<div class="card performance-card"><div class="card-body d-grid gap-3">';
            foreach ($data['pip_records'] as $pip) {
                echo '<div class="performance-feed-item"><div class="d-flex justify-content-between gap-2 mb-2"><div><h6 class="mb-1">' . performance_escape($pip['employee_name']) . '</h6><p class="text-sm text-secondary mb-0">Mentor: ' . performance_escape($pip['mentor']) . ' · Deadline ' . performance_escape($pip['deadline']) . '</p></div><div class="d-flex align-items-center gap-2">';
                performance_render_edit_modal_button('pipModal', array('pip_id' => $pip['id'], 'employee_id' => $pip['employee_id'], 'mentor_name' => $pip['mentor_name'] ?? $pip['mentor'], 'deadline' => $pip['deadline'], 'status' => $pip['status'], 'progress' => $pip['progress'], 'notes' => $pip['notes']), 'PIP');
                performance_render_status($pip['status']);
                echo '</div></div>';
                performance_render_progress($pip['progress'], $pip['progress'] >= 60 ? 'success' : 'warning');
                echo '<p class="text-sm text-secondary mt-3 mb-0">' . performance_escape($pip['notes']) . '</p></div>';
            }
            echo '</div></div>';
            break;

        case 'settings':
            performance_render_page_head('Visibility & Role Settings', 'Control employee visibility of final ratings, manager comments and KPI scores while keeping auditability.', array());
            echo '<div class="row"><div class="col-xl-7 mb-4"><div class="card performance-card"><div class="card-body"><form class="js-performance-form" data-action="save-settings"><div class="row g-3"><div class="col-md-4"><label class="form-label">Employee can view final rating</label><select class="form-select" name="employee_final_rating"><option value="1" selected>ON</option><option value="0">OFF</option></select></div><div class="col-md-4"><label class="form-label">Employee can view manager comments</label><select class="form-select" name="employee_manager_comments"><option value="1" selected>ON</option><option value="0">OFF</option></select></div><div class="col-md-4"><label class="form-label">Employee can view KPI scores</label><select class="form-select" name="employee_kpi_scores"><option value="1" selected>ON</option><option value="0">OFF</option></select></div></div><div class="d-flex justify-content-end mt-3"><button class="btn btn-admin-primary mb-0" type="submit">Save Settings</button></div></form></div></div></div><div class="col-xl-5 mb-4"><div class="card performance-card h-100"><div class="card-body d-grid gap-3">';
            foreach ($data['audit_logs'] as $log) {
                echo '<div class="performance-feed-item"><h6 class="mb-1">' . performance_escape($log['event']) . '</h6><p class="text-sm text-secondary mb-1">' . performance_escape($log['description']) . '</p><span class="text-xs text-secondary">' . performance_escape($log['actor']) . ' · ' . performance_escape($log['logged_at']) . '</span></div>';
            }
            echo '</div></div></div></div>';
            break;

        case 'dashboard':
        default:
            performance_render_page_head('Performance Dashboard', 'Track KPI completion, active cycles, appraisal backlog, department analytics and recognition activity from one control center.', array(array('label' => 'Create Cycle', 'href' => 'javascript:void(0);', 'class' => 'btn-admin-primary', 'icon' => 'bi bi-plus-circle-fill', 'modal' => 'cycleModal'), array('label' => 'Assign Goal', 'href' => 'javascript:void(0);', 'class' => 'btn-admin-secondary', 'icon' => 'bi bi-bullseye', 'modal' => 'goalModal')));
            performance_render_dashboard_widgets($data);
            ?>
            <div class="row">
              <div class="col-xl-8 mb-4"><div class="card performance-card h-100"><div class="card-header bg-transparent border-0 pb-0"><p class="performance-label mb-2">Performance Trend</p><h5 class="mb-0">Weighted review score movement</h5></div><div class="card-body pt-3"><div style="height:320px"><canvas id="performanceTrendChart"></canvas></div></div></div></div>
              <div class="col-xl-4 mb-4"><div class="card performance-card h-100"><div class="card-header bg-transparent border-0 pb-0"><p class="performance-label mb-2">Goal Progress</p><h5 class="mb-0">Status distribution</h5></div><div class="card-body pt-3"><div style="height:250px"><canvas id="goalStatusChart"></canvas></div></div></div></div>
            </div>
            <div class="row">
              <div class="col-xl-5 mb-4"><div class="card performance-card h-100"><div class="card-header bg-transparent border-0 pb-0"><p class="performance-label mb-2">Top Performers</p><h5 class="mb-0">Employee ranking cards</h5></div><div class="card-body d-grid gap-3 pt-3">
              <?php foreach (array_slice($data['results'], 0, 4) as $index => $result): ?>
                <div class="performance-feed-item performance-hover"><div class="d-flex align-items-center gap-3"><div class="performance-avatar"><?= performance_escape(performance_initials($result['employee'])) ?></div><div class="flex-grow-1"><div class="d-flex justify-content-between gap-2"><div><h6 class="mb-1">#<?= $index + 1 ?> <?= performance_escape($result['employee']) ?></h6><p class="text-sm text-secondary mb-0"><?= performance_escape($result['department']) ?></p></div><strong><?= performance_escape($result['final_score']) ?></strong></div><?php performance_render_progress($result['final_score'], 'success'); ?></div></div></div>
              <?php endforeach; ?>
              </div></div></div>
              <div class="col-xl-7 mb-4"><div class="card performance-card h-100"><div class="card-header bg-transparent border-0 pb-0"><p class="performance-label mb-2">Department Analytics</p><h5 class="mb-0">Performance by business unit</h5></div><div class="card-body pt-3"><div style="height:280px"><canvas id="departmentPerformanceChart"></canvas></div></div></div></div>
            </div>
            <div class="row"><div class="col-xl-6 mb-4"><div class="card performance-card"><div class="card-header bg-transparent border-0 pb-0"><p class="performance-label mb-2">Recent Feedback Activity</p><h5 class="mb-0">Recognition and coaching feed</h5></div><div class="card-body d-grid gap-3 pt-3">
            <?php foreach (array_slice($data['feedbacks'], 0, 4) as $feedback): ?>
              <div class="performance-feed-item"><div class="d-flex justify-content-between gap-2 mb-2"><div><h6 class="mb-1"><?= performance_escape($feedback['title']) ?></h6><p class="text-sm text-secondary mb-0"><?= performance_escape($feedback['author']) ?> · <?= performance_escape($feedback['role']) ?></p></div><?php performance_render_status($feedback['recognition']); ?></div><p class="text-sm mb-0"><?= performance_escape($feedback['comment']) ?></p></div>
            <?php endforeach; ?>
            </div></div></div><div class="col-xl-6 mb-4"><div class="card performance-card"><div class="card-header bg-transparent border-0 pb-0"><p class="performance-label mb-2">Final Score Summary</p><h5 class="mb-0">Weighted score engine</h5></div><div class="card-body pt-3">
            <?php if (!empty($data['results'])): ?>
            <?php $spotlight = $data['results'][0]; ?>
            <div class="performance-feed-item mb-3"><div class="d-flex justify-content-between gap-2 mb-2"><div><h6 class="mb-1"><?= performance_escape($spotlight['employee']) ?></h6><p class="text-sm text-secondary mb-0"><?= performance_escape($spotlight['department']) ?></p></div><?php performance_render_status($spotlight['final_rating']); ?></div><h2 class="mb-3"><?= performance_escape($spotlight['final_score']) ?></h2><div class="d-grid gap-3"><div><div class="d-flex justify-content-between text-sm mb-2"><span>KPI Achievement 40%</span><span><?= performance_escape($spotlight['kpi_score']) ?></span></div><?php performance_render_progress($spotlight['kpi_score'], 'primary'); ?></div><div><div class="d-flex justify-content-between text-sm mb-2"><span>Manager Review 30%</span><span><?= performance_escape($spotlight['manager_score']) ?></span></div><?php performance_render_progress($spotlight['manager_score'], 'success'); ?></div><div><div class="d-flex justify-content-between text-sm mb-2"><span>Attendance 20%</span><span><?= performance_escape($spotlight['attendance_score']) ?></span></div><?php performance_render_progress($spotlight['attendance_score'], 'warning'); ?></div><div><div class="d-flex justify-content-between text-sm mb-2"><span>Self Review 10%</span><span><?= performance_escape($spotlight['self_review_score']) ?></span></div><?php performance_render_progress($spotlight['self_review_score'], 'secondary'); ?></div></div></div>
            <?php else: ?>
            <?php performance_render_empty('No result summary yet', 'Create a cycle and submit reviews to generate live performance scores.'); ?>
            <?php endif; ?>
            </div></div></div></div>
            <?php
            break;
    }
}

function performance_render_portal_view($view, $data, $role, $userId)
{
    $employeeResult = !empty($data['results']) ? $data['results'][0] : null;

    switch ($view) {
        case 'my-goals':
        case 'team-goals':
        case 'goal-assignment':
            $title = $view === 'my-goals' ? 'My Goals & KPIs' : ($view === 'goal-assignment' ? 'Goal Assignment' : 'Team Goals');
            $subtitle = $view === 'my-goals' ? 'View assigned goals, deadlines, progress and manager guidance.' : 'Manage team goal plans, assignments and status tracking.';
            $actions = $view === 'goal-assignment' ? array(array('label' => 'Assign Goal', 'href' => 'javascript:void(0);', 'class' => 'btn-admin-primary', 'icon' => 'bi bi-plus-circle-fill', 'modal' => 'goalModal')) : array();
            performance_render_page_head($title, $subtitle, $actions);
            echo '<div class="row">';
            foreach ($data['goals'] as $goal) {
                echo '<div class="col-xl-4 col-md-6 mb-4"><div class="card performance-card performance-hover h-100"><div class="card-body"><div class="d-flex justify-content-between gap-2 mb-3"><div><p class="performance-label mb-2">' . performance_escape($goal['type']) . '</p><h5 class="mb-1">' . performance_escape($goal['title']) . '</h5><p class="text-sm text-secondary mb-0">' . performance_escape($goal['employee_name']) . '</p></div>';
                performance_render_status($goal['status']);
                echo '</div>';
                performance_render_progress($goal['achievement_percentage'], $goal['achievement_percentage'] >= 80 ? 'success' : 'warning');
                echo '<div class="d-flex justify-content-between mt-2 text-sm"><span>Achievement</span><span>' . performance_percent($goal['achievement_percentage']) . '</span></div><p class="text-sm text-secondary mt-3 mb-0">Manager comment: ' . performance_escape($goal['manager_comment']) . '</p></div></div></div>';
            }
            echo '</div>';
            break;

        case 'my-checkins':
            performance_render_page_head('My Check-Ins', 'Submit weekly or monthly progress updates, blockers and achievements without leaving the current portal.', array());
            $currentCheckin = !empty($data['checkins']) ? $data['checkins'][0] : null;
            echo '<div class="row"><div class="col-xl-6 mb-4"><div class="card performance-card"><div class="card-body"><form class="js-performance-form" data-action="save-checkin"><input type="hidden" name="employee_id" value="' . (int) $userId . '"><input type="hidden" class="js-performance-record-id" name="checkin_id" value="' . (int) ($currentCheckin['id'] ?? 0) . '"><div class="row g-3"><div class="col-md-6"><label class="form-label">Frequency</label><select class="form-select" name="frequency"><option ' . (($currentCheckin['frequency'] ?? '') === 'Weekly' ? 'selected' : '') . '>Weekly</option><option ' . (($currentCheckin['frequency'] ?? '') === 'Monthly' ? 'selected' : '') . '>Monthly</option></select></div><div class="col-md-6"><label class="form-label">Status</label><select class="form-select" name="status"><option ' . (($currentCheckin['status'] ?? '') === 'Submitted' ? 'selected' : '') . '>Submitted</option><option ' . (($currentCheckin['status'] ?? '') === 'Draft' ? 'selected' : '') . '>Draft</option><option ' . (($currentCheckin['status'] ?? '') === 'Reviewed' ? 'selected' : '') . '>Reviewed</option></select></div><div class="col-12"><label class="form-label">Progress Summary</label><textarea class="form-control" rows="3" name="progress_summary">' . performance_escape($currentCheckin['progress_summary'] ?? '') . '</textarea></div><div class="col-md-6"><label class="form-label">Achievements</label><textarea class="form-control" rows="3" name="achievements">' . performance_escape($currentCheckin['achievements'] ?? '') . '</textarea></div><div class="col-md-6"><label class="form-label">Challenges</label><textarea class="form-control" rows="3" name="challenges">' . performance_escape($currentCheckin['challenges'] ?? '') . '</textarea></div></div><div class="d-flex justify-content-end mt-3"><button class="btn btn-admin-primary mb-0" type="submit">' . (!empty($currentCheckin) ? 'Update Check-In' : 'Submit Check-In') . '</button></div></form></div></div></div><div class="col-xl-6 mb-4"><div class="card performance-card h-100"><div class="card-body d-grid gap-3">';
            foreach ($data['checkins'] as $checkin) {
                echo '<div class="performance-feed-item"><div class="d-flex justify-content-between gap-2 mb-2"><strong>' . performance_escape($checkin['frequency']) . '</strong>';
                performance_render_status($checkin['status']);
                echo '</div><p class="text-sm text-secondary mb-1">' . performance_escape($checkin['progress_summary']) . '</p><p class="text-xs text-secondary mb-0">Manager: ' . performance_escape($checkin['manager_comment']) . '</p></div>';
            }
            echo '</div></div></div></div>';
            break;

        case 'my-self-reviews':
            performance_render_page_head('My Self Reviews', 'Submit self appraisal, achievements, comments and rating with status visibility.', array());
            $currentSelfReview = !empty($data['self_reviews']) ? $data['self_reviews'][0] : null;
            echo '<div class="card performance-card"><div class="card-body"><form class="js-performance-form" data-action="save-self-review"><input type="hidden" name="employee_id" value="' . (int) $userId . '"><input type="hidden" class="js-performance-record-id" name="self_review_id" value="' . (int) ($currentSelfReview['id'] ?? 0) . '"><div class="row g-3"><div class="col-md-4"><label class="form-label">Self Rating</label><select class="form-select" name="rating"><option ' . (((string) ($currentSelfReview['rating'] ?? '')) === '1' ? 'selected' : '') . '>1</option><option ' . (((string) ($currentSelfReview['rating'] ?? '')) === '2' ? 'selected' : '') . '>2</option><option ' . (((string) ($currentSelfReview['rating'] ?? '')) === '3' ? 'selected' : '') . '>3</option><option ' . (((string) ($currentSelfReview['rating'] ?? '4')) === '4' ? 'selected' : '') . '>4</option><option ' . (((string) ($currentSelfReview['rating'] ?? '')) === '5' ? 'selected' : '') . '>5</option></select></div><div class="col-md-4"><label class="form-label">Completion %</label><input class="form-control" type="number" name="completion_percentage" value="' . performance_escape($currentSelfReview['completion'] ?? '84') . '"></div><div class="col-md-4"><label class="form-label">Submission Status</label><input class="form-control" value="' . performance_escape($currentSelfReview['status'] ?? 'Open') . '" readonly></div><div class="col-md-6"><label class="form-label">Achievements</label><textarea class="form-control" rows="4" name="achievements">' . performance_escape($currentSelfReview['achievements'] ?? '') . '</textarea></div><div class="col-md-6"><label class="form-label">Comments</label><textarea class="form-control" rows="4" name="additional_comments">' . performance_escape($currentSelfReview['comments'] ?? '') . '</textarea></div><div class="col-12"><label class="form-label">Goal Review</label><textarea class="form-control" rows="3" name="goal_review">' . performance_escape($currentSelfReview['goal_review'] ?? '') . '</textarea></div></div><div class="d-flex justify-content-end mt-3"><button class="btn btn-admin-primary mb-0" type="submit">' . (!empty($currentSelfReview) ? 'Update Self Review' : 'Submit Self Review') . '</button></div></form></div></div>';
            break;

        case 'my-feedback':
        case 'employee-feedback':
            performance_render_page_head($view === 'my-feedback' ? 'My Feedback' : 'Employee Feedback', $view === 'my-feedback' ? 'View manager comments, peer appreciation and recognition history.' : 'Add and review team feedback inside the same employee portal.', $view === 'employee-feedback' ? array(array('label' => 'Post Feedback', 'href' => 'javascript:void(0);', 'class' => 'btn-admin-primary', 'icon' => 'bi bi-chat-left-dots-fill', 'modal' => 'feedbackModal')) : array());
            echo '<div class="card performance-card"><div class="card-body d-grid gap-3">';
            foreach ($data['feedbacks'] as $feedback) {
                echo '<div class="performance-feed-item"><div class="d-flex justify-content-between gap-2 mb-2"><div><h6 class="mb-1">' . performance_escape($feedback['title']) . '</h6><p class="text-sm text-secondary mb-0">' . performance_escape($feedback['author']) . ' · ' . performance_escape($feedback['role']) . '</p></div>';
                performance_render_status($feedback['recognition']);
                echo '</div><p class="text-sm mb-0">' . performance_escape($feedback['comment']) . '</p></div>';
            }
            echo '</div></div>';
            break;

        case 'my-recognition':
            performance_render_page_head('My Recognition & Rewards', 'View badges, reward points, achievement timeline and kudos received.', array());
            echo '<div class="row">';
            foreach ($data['badges'] as $badge) {
                echo '<div class="col-xl-4 col-md-6 mb-4"><div class="card performance-card"><div class="card-body"><div class="d-flex justify-content-between mb-3"><h5 class="mb-0">' . performance_escape($badge['badge']) . '</h5><span class="performance-status performance-status-warning">' . (int) $badge['points'] . ' pts</span></div><p class="text-sm text-secondary mb-0">' . performance_escape($badge['reason']) . '</p></div></div></div>';
            }
            echo '</div>';
            break;

        case 'my-history':
        case 'team-analytics':
        case 'team-dashboard':
        case 'performance-monitoring':
            $title = 'My Performance Dashboard';
            $subtitle = 'Track KPI progress, appraisal status, trend movement and upcoming review deadlines.';
            if ($view === 'my-history') {
                $title = 'My Performance';
                $subtitle = 'Review appraisal records, ratings, goal progress and score trends in one place.';
            } elseif ($view === 'team-analytics') {
                $title = 'Team Analytics';
                $subtitle = 'Department comparison, trend graphs and performance heatmap for reporting employees.';
            } elseif ($view === 'team-dashboard') {
                $title = 'Team Performance Dashboard';
                $subtitle = 'Monitor team KPI completion, pending reviews, recognitions and cycle progress.';
            } elseif ($view === 'performance-monitoring') {
                $title = 'Performance Monitoring';
                $subtitle = 'Watch low-performer risk, check-in quality and review flow inside the same portal.';
            }
            performance_render_page_head($title, $subtitle, array());
            performance_render_dashboard_widgets($data);
            echo '<div class="row"><div class="col-xl-8 mb-4"><div class="card performance-card"><div class="card-body"><div style="height:300px"><canvas id="performanceTrendChart"></canvas></div></div></div></div><div class="col-xl-4 mb-4"><div class="card performance-card h-100"><div class="card-body"><div style="height:300px"><canvas id="departmentPerformanceChart"></canvas></div></div></div></div></div>';
            break;

        case 'team-reviews':
        case 'pending-approvals':
            performance_render_page_head($view === 'team-reviews' ? 'Team Reviews' : 'Pending Approvals', $view === 'team-reviews' ? 'Submit manager reviews and compare team performance.' : 'Review items waiting for manager action such as goals and self appraisals.', array());
            echo '<div class="card performance-card"><div class="card-body"><div class="table-responsive"><table class="table performance-table mb-0"><thead><tr><th>Employee</th><th>KPI</th><th>Manager</th><th>Attendance</th><th>Self</th><th>Final</th><th>Status</th></tr></thead><tbody>';
            foreach ($data['results'] as $result) {
                echo '<tr><td>' . performance_escape($result['employee']) . '</td><td>' . performance_escape($result['kpi_score']) . '</td><td>' . performance_escape($result['manager_score']) . '</td><td>' . performance_escape($result['attendance_score']) . '</td><td>' . performance_escape($result['self_review_score']) . '</td><td>' . performance_escape($result['final_score']) . '</td><td>';
                performance_render_status($view === 'pending-approvals' ? 'Pending' : $result['final_rating']);
                echo '</td></tr>';
            }
            echo '</tbody></table></div></div></div>';
            break;

        case 'my-dashboard':
        default:
            performance_render_page_head('My Performance Dashboard', 'Track my KPI progress, current appraisal cycle, recent feedback, recognition and upcoming review deadlines.', array());
            performance_render_dashboard_widgets($data);
            echo '<div class="row"><div class="col-xl-8 mb-4"><div class="card performance-card"><div class="card-body"><div style="height:300px"><canvas id="performanceTrendChart"></canvas></div></div></div></div><div class="col-xl-4 mb-4"><div class="card performance-card h-100"><div class="card-body d-grid gap-3">';
            foreach ($data['notifications'] as $notification) {
                echo '<div class="performance-feed-item"><h6 class="mb-1">' . performance_escape($notification['title']) . '</h6><p class="text-sm text-secondary mb-0">' . performance_escape($notification['message']) . '</p></div>';
            }
            echo '</div></div></div></div>';
            break;
    }
}
