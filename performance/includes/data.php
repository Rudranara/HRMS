<?php

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/storage.php';

function performance_fetch_employees(mysqli $conn, $context, $role, $userId)
{
    $employees = array();

    if (performance_table_exists($conn, 'employees')) {
        if ($context === 'admin') {
            $query = "SELECT id, employee_id, name, department, designation, role, manager, office FROM employees WHERE status = 'Active' ORDER BY id DESC LIMIT 12";
            $result = $conn->query($query);
            if ($result instanceof mysqli_result) {
                while ($row = $result->fetch_assoc()) {
                    $employees[] = $row;
                }
            }
        } elseif ($role === 'manager') {
            $stmt = $conn->prepare("SELECT id, employee_id, name, department, designation, role, manager, office FROM employees WHERE id = ? OR manager = ? ORDER BY id ASC");
            if ($stmt) {
                $stmt->bind_param('ii', $userId, $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $employees[] = $row;
                }
                $stmt->close();
            }
        } else {
            $stmt = $conn->prepare("SELECT id, employee_id, name, department, designation, role, manager, office FROM employees WHERE id = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param('i', $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $employees[] = $row;
                }
                $stmt->close();
            }
        }
    }

    if (!empty($employees)) {
        return $employees;
    }

    return array(
        array('id' => 1, 'employee_id' => 'MT-1021', 'name' => 'Aarav Shah', 'department' => 'Sales', 'designation' => 'Regional Sales Lead', 'role' => 'Manager', 'manager' => 0, 'office' => 'Ahmedabad'),
        array('id' => 2, 'employee_id' => 'MT-1028', 'name' => 'Priya Nair', 'department' => 'Operations', 'designation' => 'Process Manager', 'role' => 'Manager', 'manager' => 0, 'office' => 'Odisha'),
        array('id' => 3, 'employee_id' => 'MT-1034', 'name' => 'Rohan Mehta', 'department' => 'Technology', 'designation' => 'Product Engineer', 'role' => 'Employee', 'manager' => 2, 'office' => 'Ahmedabad'),
        array('id' => 4, 'employee_id' => 'MT-1042', 'name' => 'Sneha Iyer', 'department' => 'Human Resources', 'designation' => 'HR Business Partner', 'role' => 'HR', 'manager' => 0, 'office' => 'Corporate'),
        array('id' => 5, 'employee_id' => 'MT-1055', 'name' => 'Kunal Verma', 'department' => 'Finance', 'designation' => 'Finance Analyst', 'role' => 'Employee', 'manager' => 2, 'office' => 'Corporate'),
        array('id' => 6, 'employee_id' => 'MT-1062', 'name' => 'Ananya Singh', 'department' => 'Marketing', 'designation' => 'Brand Specialist', 'role' => 'Employee', 'manager' => 1, 'office' => 'Ahmedabad')
    );
}

function performance_attendance_percentage(mysqli $conn, $employeeId)
{
    if (!performance_table_exists($conn, 'attendance')) {
        return 92;
    }

    $stmt = $conn->prepare("SELECT COUNT(*) AS total_days, SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) AS present_days FROM attendance WHERE employee_id = ? AND MONTH(punch_in_time) = MONTH(CURDATE()) AND YEAR(punch_in_time) = YEAR(CURDATE())");
    if (!$stmt) {
        return 92;
    }

    $stmt->bind_param('i', $employeeId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : array();
    $stmt->close();

    $total = (int) ($row['total_days'] ?? 0);
    $present = (int) ($row['present_days'] ?? 0);

    if ($total === 0) {
        return 92;
    }

    return round(($present / $total) * 100, 1);
}

function performance_seed_payload(mysqli $conn, $context, $role, $userId)
{
    $employees = performance_fetch_employees($conn, $context, $role, $userId);
    $employeeMap = array();

    foreach ($employees as $employee) {
        $employeeMap[(int) $employee['id']] = $employee;
    }

    $cycles = array(
        array('id' => 1, 'name' => 'FY 2026 Annual Appraisal', 'type' => 'Annual', 'start_date' => '2026-04-01', 'end_date' => '2026-06-30', 'status' => 'Active', 'description' => 'Annual review cycle aligned with business KPIs.', 'assigned' => 128, 'workflow' => 'Manager Review in Progress'),
        array('id' => 2, 'name' => 'Q2 OKR Review', 'type' => 'Quarterly', 'start_date' => '2026-05-01', 'end_date' => '2026-06-20', 'status' => 'Scheduled', 'description' => 'Quarterly OKR review with employee self reviews.', 'assigned' => 86, 'workflow' => 'Self Review Window Open'),
        array('id' => 3, 'name' => 'Leadership Calibration', 'type' => 'Half-Yearly', 'start_date' => '2026-01-10', 'end_date' => '2026-02-25', 'status' => 'Closed', 'description' => 'Leadership calibration and increment planning.', 'assigned' => 32, 'workflow' => 'Finalised')
    );

    $defaultEmployees = array_values($employeeMap);
    if (empty($defaultEmployees)) {
        $defaultEmployees = performance_fetch_employees($conn, 'admin', 'admin', 0);
        foreach ($defaultEmployees as $employee) {
            $employeeMap[(int) $employee['id']] = $employee;
        }
    }

    $goals = array();
    $goalTemplates = array(
        array('title' => 'Increase channel sales in West zone', 'type' => 'KPI', 'weightage' => 30, 'status' => 'In Progress', 'achievement' => 82, 'timeline' => 'Apr - Jun'),
        array('title' => 'Reduce attendance regularisation delays', 'type' => 'SMART Goals', 'weightage' => 20, 'status' => 'On Track', 'achievement' => 76, 'timeline' => 'May - Jun'),
        array('title' => 'Release performance APIs', 'type' => 'OKR', 'weightage' => 25, 'status' => 'Pending Approval', 'achievement' => 61, 'timeline' => 'May - Jul'),
        array('title' => 'Launch manager coaching playbook', 'type' => 'KRA', 'weightage' => 15, 'status' => 'Completed', 'achievement' => 100, 'timeline' => 'Apr - Jun'),
        array('title' => 'Improve incentive budget adherence', 'type' => 'KPI', 'weightage' => 10, 'status' => 'Needs Improvement', 'achievement' => 48, 'timeline' => 'Apr - Jun')
    );

    $goalId = 1;
    foreach ($defaultEmployees as $index => $employee) {
        $template = $goalTemplates[$index % count($goalTemplates)];
        $goals[] = array(
            'id' => $goalId++,
            'employee_id' => (int) $employee['id'],
            'employee_name' => $employee['name'],
            'department' => $employee['department'] ?: ($employee['office'] ?: 'General'),
            'title' => $template['title'],
            'description' => 'Goal aligned to department targets and ongoing performance cycle.',
            'type' => $template['type'],
            'weightage' => $template['weightage'],
            'due_date' => '2026-06-30',
            'status' => $template['status'],
            'achievement_percentage' => $template['achievement'],
            'timeline' => $template['timeline'],
            'manager_comment' => 'Keep updating weekly milestones and blockers.'
        );
    }

    $feedbacks = array();
    $feedbackTexts = array(
        array('author' => 'Nikita Rao', 'role' => 'Manager', 'title' => 'Leadership under pressure', 'comment' => 'Handled escalations calmly and improved stakeholder confidence.', 'recognition' => 'Leadership Star'),
        array('author' => 'Devansh Jain', 'role' => 'Peer', 'title' => 'Cross-functional collaboration', 'comment' => 'Supported payroll and HR integration blockers without delay.', 'recognition' => 'Team Player'),
        array('author' => 'HR Ops', 'role' => 'Recognition', 'title' => 'People champion', 'comment' => 'Enabled stronger review adoption through practical training sessions.', 'recognition' => 'Best Performer')
    );
    $feedbackId = 1;
    foreach ($defaultEmployees as $index => $employee) {
        $template = $feedbackTexts[$index % count($feedbackTexts)];
        $feedbacks[] = array(
            'id' => $feedbackId++,
            'employee_id' => (int) $employee['id'],
            'employee_name' => $employee['name'],
            'author' => $template['author'],
            'feedback_type' => $template['role'],
            'role' => $template['role'],
            'title' => $template['title'],
            'comment' => $template['comment'],
            'recognition' => $template['recognition'],
            'created_at' => date('Y-m-d H:i:s', strtotime('-' . ($index + 1) . ' day'))
        );
    }

    $checkins = array();
    $checkinId = 1;
    foreach ($defaultEmployees as $index => $employee) {
        $checkins[] = array(
            'id' => $checkinId++,
            'employee_id' => (int) $employee['id'],
            'employee_name' => $employee['name'],
            'frequency' => $index % 2 === 0 ? 'Weekly' : 'Monthly',
            'progress_summary' => 'Core goals are progressing with measurable movement this period.',
            'achievements' => 'Closed top pending deliverables and improved response time.',
            'challenges' => 'Waiting on stakeholder approvals and cross-team dependencies.',
            'manager_comment' => 'Escalate blockers early and maintain progress visibility.',
            'status' => $index % 3 === 0 ? 'Reviewed' : 'Submitted',
            'created_at' => date('Y-m-d', strtotime('-' . ($index + 2) . ' day'))
        );
    }

    $selfReviews = array();
    $managerReviews = array();
    $results = array();
    $badges = array();
    $pipRecords = array();
    $departments = array();
    $historySeries = array(74, 77, 79, 82, 85, 88);

    $badgeNames = array('Team Player', 'Leadership Star', 'Best Performer', 'Innovation Award');
    $departmentScores = array();

    foreach ($defaultEmployees as $index => $employee) {
        $attendanceScore = performance_attendance_percentage($conn, (int) $employee['id']);
        $kpiScore = 68 + (($index * 7) % 28);
        $managerScore = 72 + (($index * 6) % 22);
        $selfScore = 70 + (($index * 5) % 24);
        $finalScore = performance_calculate_score($kpiScore, $managerScore, $attendanceScore, $selfScore);

        $selfReviews[] = array(
            'id' => $index + 1,
            'employee_id' => (int) $employee['id'],
            'employee_name' => $employee['name'],
            'rating' => min(5, max(1, round($selfScore / 20))),
            'completion' => 76 + (($index * 4) % 20),
            'achievements' => 'Delivered measurable improvements on assigned goals.',
            'comments' => 'Needs faster approvals and better cross-functional timelines.',
            'goal_review' => 'Goals updated in current cycle.'
        );

        $managerReviews[] = array(
            'employee_id' => (int) $employee['id'],
            'employee_name' => $employee['name'],
            'technical_skill' => round(3.8 + (($index % 4) * 0.2), 1),
            'communication' => round(4.0 + (($index % 3) * 0.2), 1),
            'productivity' => round(4.1 + (($index % 4) * 0.15), 1),
            'teamwork' => round(4.0 + (($index % 5) * 0.12), 1),
            'attendance' => round($attendanceScore / 20, 1),
            'leadership' => round(3.7 + (($index % 4) * 0.2), 1),
            'comments' => 'Strong ownership with room to improve planning cadence.',
            'promotion' => $finalScore >= 85 ? 'Recommended' : ($finalScore >= 75 ? 'Future Ready' : 'Not This Cycle'),
            'increment' => $finalScore >= 85 ? '12%' : ($finalScore >= 75 ? '8%' : '5%')
        );

        $results[] = array(
            'employee_id' => (int) $employee['id'],
            'employee' => $employee['name'],
            'department' => $employee['department'] ?: ($employee['office'] ?: 'General'),
            'kpi_score' => $kpiScore,
            'manager_score' => $managerScore,
            'attendance_score' => $attendanceScore,
            'self_review_score' => $selfScore,
            'final_score' => $finalScore,
            'final_rating' => performance_rating_from_score($finalScore),
            'history' => array($historySeries[0] + $index, $historySeries[1] + $index, $historySeries[2] + $index, $historySeries[3] + $index)
        );

        $badges[] = array(
            'id' => $index + 1,
            'employee_id' => (int) $employee['id'],
            'employee_name' => $employee['name'],
            'badge' => $badgeNames[$index % count($badgeNames)],
            'points' => 100 + ($index * 20),
            'reason' => 'Recognition aligned to recent performance contribution and collaboration.',
            'created_at' => date('Y-m-d', strtotime('-' . ($index + 3) . ' day'))
        );

        if ($finalScore < 72) {
            $pipRecords[] = array(
                'id' => count($pipRecords) + 1,
                'employee_id' => (int) $employee['id'],
                'employee_name' => $employee['name'],
                'mentor_name' => 'Performance Coach',
                'mentor' => 'Performance Coach',
                'deadline' => date('Y-m-d', strtotime('+45 day')),
                'status' => 'Active',
                'progress' => 42 + ($index * 6),
                'notes' => 'Weekly review milestones and HR monitoring enabled.'
            );
        }

        $departmentName = $employee['department'] ?: ($employee['office'] ?: 'General');
        if (!isset($departmentScores[$departmentName])) {
            $departmentScores[$departmentName] = array();
        }
        $departmentScores[$departmentName][] = $finalScore;
    }

    foreach ($departmentScores as $department => $scores) {
        $departments[] = array(
            'department' => $department,
            'score' => round(performance_average($scores), 1),
            'goal_completion' => round(min(100, performance_average($scores) + 4), 1),
            'reviews_closed' => count($scores) * 3
        );
    }

    usort($results, function ($left, $right) {
        if ($left['final_score'] === $right['final_score']) {
            return 0;
        }
        return $left['final_score'] < $right['final_score'] ? 1 : -1;
    });

    $notifications = array(
        array('title' => 'Pending self reviews', 'message' => '14 employees still need to submit self review forms.', 'time' => '20 min ago'),
        array('title' => 'Recognition posted', 'message' => 'A new Innovation Award was issued to the Technology team.', 'time' => 'Today'),
        array('title' => 'PIP milestone due', 'message' => 'A finance improvement checkpoint is due in 4 days.', 'time' => 'Today')
    );

    $auditLogs = array(
        array('event' => 'Cycle Updated', 'description' => 'FY 2026 Annual Appraisal is now active.', 'actor' => 'HR Admin', 'logged_at' => date('Y-m-d H:i', strtotime('-1 day'))),
        array('event' => 'Goal Approved', 'description' => 'Goal approval completed for current quarterly plan.', 'actor' => 'Manager', 'logged_at' => date('Y-m-d H:i', strtotime('-2 day'))),
        array('event' => 'Review Submitted', 'description' => 'Manager review submitted for a reporting employee.', 'actor' => 'Manager', 'logged_at' => date('Y-m-d H:i', strtotime('-3 day')))
    );

    $metrics = array(
        array('title' => 'KPI Completion', 'value' => performance_percent(performance_average(array_column($goals, 'achievement_percentage'))), 'meta' => '+6.4% from previous cycle', 'icon' => 'bi bi-bullseye', 'tone' => 'success'),
        array('title' => 'Pending Appraisals', 'value' => (string) max(1, count($results) - 2), 'meta' => 'Awaiting manager or HR closure', 'icon' => 'bi bi-hourglass-split', 'tone' => 'warning'),
        array('title' => 'Active Review Cycles', 'value' => (string) count(array_filter($cycles, function ($cycle) { return $cycle['status'] === 'Active'; })), 'meta' => 'Quarterly, annual and half-yearly', 'icon' => 'bi bi-arrow-repeat', 'tone' => 'primary'),
        array('title' => 'Team Performance Score', 'value' => number_format(performance_average(array_column($results, 'final_score')), 1), 'meta' => 'Weighted KPI, manager, attendance and self review', 'icon' => 'bi bi-graph-up-arrow', 'tone' => 'secondary')
    );

    $chartData = array(
        'trendLabels' => array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'),
        'trendScores' => array(72, 75, 78, 81, 84, 87),
        'goalStatusLabels' => array('Completed', 'In Progress', 'Pending Approval', 'Needs Improvement'),
        'goalStatusValues' => array(12, 21, 7, 4),
        'departmentLabels' => array_values(array_map(function ($row) { return $row['department']; }, $departments)),
        'departmentScores' => array_values(array_map(function ($row) { return $row['score']; }, $departments)),
        'radarLabels' => array('Technical', 'Communication', 'Productivity', 'Teamwork', 'Attendance', 'Leadership'),
        'radarValues' => array(88, 84, 91, 86, 94, 82)
    );

    return array(
        'employees' => $employees,
        'employee_map' => $employeeMap,
        'cycles' => $cycles,
        'goals' => $goals,
        'feedbacks' => $feedbacks,
        'checkins' => $checkins,
        'self_reviews' => $selfReviews,
        'manager_reviews' => $managerReviews,
        'results' => $results,
        'badges' => $badges,
        'pip_records' => $pipRecords,
        'departments' => $departments,
        'notifications' => $notifications,
        'audit_logs' => $auditLogs,
        'metrics' => $metrics,
        'charts' => $chartData,
        'visibility' => array(
            'employee_final_rating' => true,
            'employee_manager_comments' => true,
            'employee_kpi_scores' => true
        ),
        'uses_demo' => !performance_table_exists($conn, 'performance_cycles')
    );
}

function performance_scope_employee_ids($employees)
{
    $ids = array();
    foreach ($employees as $employee) {
        $employeeId = (int) ($employee['id'] ?? 0);
        if ($employeeId > 0) {
            $ids[] = $employeeId;
        }
    }

    return array_values(array_unique($ids));
}

function performance_id_sql_list($ids)
{
    $safe = array();
    foreach ($ids as $id) {
        $id = (int) $id;
        if ($id > 0) {
            $safe[] = $id;
        }
    }

    return empty($safe) ? '0' : implode(',', $safe);
}

function performance_build_payload_from_database(mysqli $conn, $context, $role, $userId)
{
    $employees = performance_fetch_employees($conn, $context, $role, $userId);
    $employeeMap = array();
    foreach ($employees as $employee) {
        $employeeMap[(int) $employee['id']] = $employee;
    }

    $employeeIds = performance_scope_employee_ids($employees);
    $employeeIdSql = performance_id_sql_list($employeeIds);
    $payload = array(
        'employees' => $employees,
        'employee_map' => $employeeMap,
        'cycles' => array(),
        'goals' => array(),
        'feedbacks' => array(),
        'checkins' => array(),
        'self_reviews' => array(),
        'manager_reviews' => array(),
        'results' => array(),
        'badges' => array(),
        'pip_records' => array(),
        'departments' => array(),
        'notifications' => array(),
        'audit_logs' => array(),
        'metrics' => array(),
        'charts' => array(),
        'visibility' => performance_fetch_settings($conn),
        'uses_demo' => false
    );

    $result = $conn->query('SELECT id, cycle_name, review_type, start_date, end_date, status, description FROM performance_cycles ORDER BY start_date DESC, id DESC');
    if ($result instanceof mysqli_result) {
        while ($row = $result->fetch_assoc()) {
            $payload['cycles'][] = array(
                'id' => (int) $row['id'],
                'name' => $row['cycle_name'],
                'type' => $row['review_type'],
                'start_date' => $row['start_date'],
                'end_date' => $row['end_date'],
                'status' => $row['status'],
                'description' => $row['description'] ?? '',
                'workflow' => $row['status'] === 'Active' ? 'Open for submissions' : 'Awaiting activity'
            );
        }
    }

    if (!empty($employeeIds)) {
        $goalSql = "SELECT g.id, g.employee_id, e.name AS employee_name, e.department, e.office, g.goal_title, g.description, g.goal_type, g.weightage, g.due_date, g.status, g.achievement_percentage, COALESCE(g.manager_comment, '') AS manager_comment FROM employee_goals g INNER JOIN employees e ON e.id = g.employee_id WHERE g.employee_id IN ({$employeeIdSql}) ORDER BY g.updated_at DESC, g.id DESC";
        $result = $conn->query($goalSql);
        if ($result instanceof mysqli_result) {
            while ($row = $result->fetch_assoc()) {
                $payload['goals'][] = array(
                    'id' => (int) $row['id'],
                    'employee_id' => (int) $row['employee_id'],
                    'employee_name' => $row['employee_name'],
                    'department' => $row['department'] ?: ($row['office'] ?: 'General'),
                    'title' => $row['goal_title'],
                    'description' => $row['description'] ?? '',
                    'type' => $row['goal_type'],
                    'weightage' => (float) $row['weightage'],
                    'due_date' => $row['due_date'],
                    'status' => $row['status'],
                    'achievement_percentage' => (float) $row['achievement_percentage'],
                    'timeline' => $row['due_date'],
                    'manager_comment' => $row['manager_comment']
                );
            }
        }

        $feedbackSql = "SELECT f.id, f.employee_id, e.name AS employee_name, COALESCE(r.name, 'System') AS author, f.feedback_type, f.feedback_title, f.comments, f.recognition_badge, f.created_at FROM feedbacks f INNER JOIN employees e ON e.id = f.employee_id LEFT JOIN employees r ON r.id = f.reviewer_id WHERE f.employee_id IN ({$employeeIdSql}) ORDER BY f.created_at DESC, f.id DESC";
        $result = $conn->query($feedbackSql);
        if ($result instanceof mysqli_result) {
            while ($row = $result->fetch_assoc()) {
                $payload['feedbacks'][] = array(
                    'id' => (int) $row['id'],
                    'employee_id' => (int) $row['employee_id'],
                    'employee_name' => $row['employee_name'],
                    'author' => $row['author'],
                    'feedback_type' => $row['feedback_type'],
                    'role' => $row['feedback_type'],
                    'title' => $row['feedback_title'] ?: ($row['feedback_type'] . ' Feedback'),
                    'comment' => $row['comments'],
                    'recognition' => $row['recognition_badge'] ?: $row['feedback_type'],
                    'created_at' => $row['created_at']
                );
            }
        }

        $checkinSql = "SELECT c.id, c.employee_id, e.name AS employee_name, c.frequency_type, c.progress_summary, c.achievements, c.challenges, COALESCE(c.manager_comment, '') AS manager_comment, c.status, c.checkin_date FROM checkins c INNER JOIN employees e ON e.id = c.employee_id WHERE c.employee_id IN ({$employeeIdSql}) ORDER BY c.checkin_date DESC, c.id DESC";
        $result = $conn->query($checkinSql);
        if ($result instanceof mysqli_result) {
            while ($row = $result->fetch_assoc()) {
                $payload['checkins'][] = array(
                    'id' => (int) $row['id'],
                    'employee_id' => (int) $row['employee_id'],
                    'employee_name' => $row['employee_name'],
                    'frequency' => $row['frequency_type'],
                    'progress_summary' => $row['progress_summary'],
                    'achievements' => $row['achievements'] ?? '',
                    'challenges' => $row['challenges'] ?? '',
                    'manager_comment' => $row['manager_comment'],
                    'status' => $row['status'],
                    'created_at' => $row['checkin_date']
                );
            }
        }

        $selfSql = "SELECT s.id, s.employee_id, e.name AS employee_name, s.self_rating, s.completion_percentage, s.achievements, s.additional_comments, s.goal_review, s.status FROM self_reviews s INNER JOIN employees e ON e.id = s.employee_id WHERE s.employee_id IN ({$employeeIdSql}) ORDER BY s.updated_at DESC, s.id DESC";
        $result = $conn->query($selfSql);
        if ($result instanceof mysqli_result) {
            while ($row = $result->fetch_assoc()) {
                $payload['self_reviews'][] = array(
                    'id' => (int) $row['id'],
                    'employee_id' => (int) $row['employee_id'],
                    'employee_name' => $row['employee_name'],
                    'rating' => (float) $row['self_rating'],
                    'completion' => (float) $row['completion_percentage'],
                    'achievements' => $row['achievements'] ?? '',
                    'comments' => $row['additional_comments'] ?? '',
                    'goal_review' => $row['goal_review'] ?? '',
                    'status' => $row['status']
                );
            }
        }

        $managerSql = "SELECT m.id, m.employee_id, e.name AS employee_name, m.technical_skill, m.communication, m.productivity, m.teamwork, m.attendance, m.leadership, m.overall_comments, m.promotion_recommendation, m.increment_recommendation FROM manager_reviews m INNER JOIN employees e ON e.id = m.employee_id WHERE m.employee_id IN ({$employeeIdSql}) ORDER BY m.updated_at DESC, m.id DESC";
        $result = $conn->query($managerSql);
        if ($result instanceof mysqli_result) {
            while ($row = $result->fetch_assoc()) {
                $payload['manager_reviews'][] = array(
                    'employee_id' => (int) $row['employee_id'],
                    'employee_name' => $row['employee_name'],
                    'technical_skill' => (float) $row['technical_skill'],
                    'communication' => (float) $row['communication'],
                    'productivity' => (float) $row['productivity'],
                    'teamwork' => (float) $row['teamwork'],
                    'attendance' => (float) $row['attendance'],
                    'leadership' => (float) $row['leadership'],
                    'comments' => $row['overall_comments'] ?? '',
                    'promotion' => $row['promotion_recommendation'] ?? '',
                    'increment' => $row['increment_recommendation'] !== null ? $row['increment_recommendation'] . '%' : ''
                );
            }
        }

        $resultSql = "SELECT pr.id, pr.employee_id, e.name AS employee_name, e.department, e.office, pr.goal_score, pr.manager_score, pr.attendance_score, pr.self_review_score, pr.final_score, pr.final_rating FROM performance_results pr INNER JOIN employees e ON e.id = pr.employee_id WHERE pr.employee_id IN ({$employeeIdSql}) ORDER BY pr.final_score DESC, pr.updated_at DESC, pr.id DESC";
        $result = $conn->query($resultSql);
        if ($result instanceof mysqli_result) {
            while ($row = $result->fetch_assoc()) {
                $payload['results'][] = array(
                    'employee_id' => (int) $row['employee_id'],
                    'employee' => $row['employee_name'],
                    'department' => $row['department'] ?: ($row['office'] ?: 'General'),
                    'kpi_score' => (float) $row['goal_score'],
                    'manager_score' => (float) $row['manager_score'],
                    'attendance_score' => (float) $row['attendance_score'],
                    'self_review_score' => (float) $row['self_review_score'],
                    'final_score' => (float) $row['final_score'],
                    'final_rating' => $row['final_rating'],
                    'history' => array((float) $row['goal_score'], (float) $row['manager_score'], (float) $row['attendance_score'], (float) $row['final_score'])
                );
            }
        }

        $badgeSql = "SELECT b.id, b.employee_id, e.name AS employee_name, b.badge_name, b.reward_points, b.recognition_reason, b.created_at FROM employee_badges b INNER JOIN employees e ON e.id = b.employee_id WHERE b.employee_id IN ({$employeeIdSql}) ORDER BY b.created_at DESC, b.id DESC";
        $result = $conn->query($badgeSql);
        if ($result instanceof mysqli_result) {
            while ($row = $result->fetch_assoc()) {
                $payload['badges'][] = array(
                    'id' => (int) $row['id'],
                    'employee_id' => (int) $row['employee_id'],
                    'employee_name' => $row['employee_name'],
                    'badge' => $row['badge_name'],
                    'points' => (int) $row['reward_points'],
                    'reason' => $row['recognition_reason'] ?? '',
                    'created_at' => $row['created_at']
                );
            }
        }

        $pipSql = "SELECT p.id, p.employee_id, e.name AS employee_name, COALESCE(p.mentor_name, mentor.name, 'Not Assigned') AS mentor_name, p.review_deadline, p.status, p.progress_percentage, p.progress_notes FROM pip_records p INNER JOIN employees e ON e.id = p.employee_id LEFT JOIN employees mentor ON mentor.id = p.mentor_id WHERE p.employee_id IN ({$employeeIdSql}) ORDER BY p.updated_at DESC, p.id DESC";
        $result = $conn->query($pipSql);
        if ($result instanceof mysqli_result) {
            while ($row = $result->fetch_assoc()) {
                $payload['pip_records'][] = array(
                    'id' => (int) $row['id'],
                    'employee_id' => (int) $row['employee_id'],
                    'employee_name' => $row['employee_name'],
                    'mentor_name' => $row['mentor_name'],
                    'mentor' => $row['mentor_name'],
                    'deadline' => $row['review_deadline'],
                    'status' => $row['status'],
                    'progress' => (float) $row['progress_percentage'],
                    'notes' => $row['progress_notes'] ?? ''
                );
            }
        }
    }

    $departmentBuckets = array();
    foreach ($payload['results'] as $resultRow) {
        $department = $resultRow['department'] ?: 'General';
        if (!isset($departmentBuckets[$department])) {
            $departmentBuckets[$department] = array();
        }
        $departmentBuckets[$department][] = (float) $resultRow['final_score'];
    }
    foreach ($departmentBuckets as $department => $scores) {
        $payload['departments'][] = array(
            'department' => $department,
            'score' => round(performance_average($scores), 1),
            'goal_completion' => round(performance_average($scores), 1),
            'reviews_closed' => count($scores)
        );
    }

    $goalStatusCounts = array();
    foreach ($payload['goals'] as $goalRow) {
        if (!isset($goalStatusCounts[$goalRow['status']])) {
            $goalStatusCounts[$goalRow['status']] = 0;
        }
        $goalStatusCounts[$goalRow['status']]++;
    }

    $payload['metrics'] = array(
        array('title' => 'KPI Completion', 'value' => performance_percent(performance_average(array_column($payload['goals'], 'achievement_percentage'))), 'meta' => 'Live goal completion from current records', 'icon' => 'bi bi-bullseye', 'tone' => 'success'),
        array('title' => 'Pending Appraisals', 'value' => (string) count(array_filter($payload['self_reviews'], function ($review) { return ($review['status'] ?? '') !== 'Reviewed'; })), 'meta' => 'Open self review workload', 'icon' => 'bi bi-hourglass-split', 'tone' => 'warning'),
        array('title' => 'Active Review Cycles', 'value' => (string) count(array_filter($payload['cycles'], function ($cycle) { return ($cycle['status'] ?? '') === 'Active'; })), 'meta' => 'Current active performance windows', 'icon' => 'bi bi-arrow-repeat', 'tone' => 'primary'),
        array('title' => 'Team Performance Score', 'value' => number_format(performance_average(array_column($payload['results'], 'final_score')), 1), 'meta' => 'Weighted result score from saved reviews', 'icon' => 'bi bi-graph-up-arrow', 'tone' => 'secondary')
    );

    $payload['charts'] = array(
        'trendLabels' => array_values(array_map(function ($cycle) { return $cycle['name']; }, array_slice(array_reverse($payload['cycles']), -6))),
        'trendScores' => empty($payload['results']) ? array() : array_values(array_fill(0, min(6, max(1, count($payload['cycles']))), round(performance_average(array_column($payload['results'], 'final_score')), 1))),
        'goalStatusLabels' => array_values(array_keys($goalStatusCounts)),
        'goalStatusValues' => array_values($goalStatusCounts),
        'departmentLabels' => array_values(array_map(function ($row) { return $row['department']; }, $payload['departments'])),
        'departmentScores' => array_values(array_map(function ($row) { return $row['score']; }, $payload['departments'])),
        'radarLabels' => array('Technical', 'Communication', 'Productivity', 'Teamwork', 'Attendance', 'Leadership'),
        'radarValues' => empty($payload['manager_reviews']) ? array(0, 0, 0, 0, 0, 0) : array(
            round(performance_average(array_column($payload['manager_reviews'], 'technical_skill')) * 20, 1),
            round(performance_average(array_column($payload['manager_reviews'], 'communication')) * 20, 1),
            round(performance_average(array_column($payload['manager_reviews'], 'productivity')) * 20, 1),
            round(performance_average(array_column($payload['manager_reviews'], 'teamwork')) * 20, 1),
            round(performance_average(array_column($payload['manager_reviews'], 'attendance')) * 20, 1),
            round(performance_average(array_column($payload['manager_reviews'], 'leadership')) * 20, 1)
        )
    );

    $payload['notifications'] = array(
        array('title' => 'Schema Installed', 'message' => 'Performance tables are active and saving live records.', 'time' => 'Now'),
        array('title' => 'Saved Goals', 'message' => count($payload['goals']) . ' goal records available for review.', 'time' => 'Now'),
        array('title' => 'Saved Reviews', 'message' => count($payload['self_reviews']) . ' self reviews and ' . count($payload['manager_reviews']) . ' manager reviews found.', 'time' => 'Now')
    );

    $payload['audit_logs'] = array(
        array('event' => 'Installer Ready', 'description' => 'One-time performance schema installer is enabled.', 'actor' => 'System', 'logged_at' => date('Y-m-d H:i')),
        array('event' => 'Saved Goals', 'description' => 'Performance goals now write to the database.', 'actor' => 'System', 'logged_at' => date('Y-m-d H:i')),
        array('event' => 'Saved Reviews', 'description' => 'Self reviews and manager reviews update live results.', 'actor' => 'System', 'logged_at' => date('Y-m-d H:i'))
    );

    return $payload;
}

function performance_filter_for_portal($payload, $role, $userId)
{
    if ($role === 'manager') {
        return $payload;
    }

    $keepEmployee = function ($row) use ($userId) {
        return (int) $row['employee_id'] === (int) $userId;
    };

    $payload['goals'] = array_values(array_filter($payload['goals'], $keepEmployee));
    $payload['feedbacks'] = array_values(array_filter($payload['feedbacks'], $keepEmployee));
    $payload['checkins'] = array_values(array_filter($payload['checkins'], $keepEmployee));
    $payload['self_reviews'] = array_values(array_filter($payload['self_reviews'], $keepEmployee));
    $payload['manager_reviews'] = array_values(array_filter($payload['manager_reviews'], $keepEmployee));
    $payload['results'] = array_values(array_filter($payload['results'], function ($row) use ($userId) { return (int) $row['employee_id'] === (int) $userId; }));
    $payload['badges'] = array_values(array_filter($payload['badges'], $keepEmployee));
    $payload['pip_records'] = array_values(array_filter($payload['pip_records'], $keepEmployee));

    return $payload;
}

function performance_load_data(mysqli $conn, $context, $role, $userId)
{
    if (empty(performance_missing_tables($conn))) {
        $payload = performance_build_payload_from_database($conn, $context, $role, $userId);
    } else {
        $payload = performance_seed_payload($conn, $context, $role, $userId);
    }

    if ($context === 'portal') {
        $payload = performance_filter_for_portal($payload, $role, $userId);
    }

    return $payload;
}
