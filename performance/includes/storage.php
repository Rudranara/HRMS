<?php

require_once __DIR__ . '/helpers.php';

function performance_required_tables()
{
    return array(
        'performance_cycles',
        'employee_goals',
        'goal_progress',
        'checkins',
        'feedbacks',
        'self_reviews',
        'manager_reviews',
        'performance_results',
        'employee_badges',
        'pip_records',
        'performance_settings'
    );
}

function performance_missing_tables(mysqli $conn)
{
    $missing = array();
    foreach (performance_required_tables() as $table) {
        if (!performance_table_exists($conn, $table)) {
            $missing[] = $table;
        }
    }

    return $missing;
}

function performance_install_schema(mysqli $conn)
{
    static $attempted = false;

    if ($attempted) {
        return false;
    }

    $attempted = true;
    $missing = performance_missing_tables($conn);
    if (empty($missing)) {
        return false;
    }

    $schemaPath = dirname(__DIR__) . '/reports/schema.sql';
    $schemaSql = @file_get_contents($schemaPath);
    if ($schemaSql === false || trim($schemaSql) === '') {
        throw new RuntimeException('Performance schema file could not be loaded.');
    }

    performance_prepare_employee_indexes($conn);

    if (!$conn->multi_query($schemaSql)) {
        throw new RuntimeException('Performance schema install failed: ' . $conn->error);
    }

    do {
        $result = $conn->store_result();
        if ($result instanceof mysqli_result) {
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_result());

    if ($conn->errno) {
        throw new RuntimeException('Performance schema install failed: ' . $conn->error);
    }

    return true;
}

function performance_prepare_employee_indexes(mysqli $conn)
{
    if (!performance_table_exists($conn, 'employees')) {
        throw new RuntimeException('Employees table is required before installing performance tables.');
    }

    $hasIndex = false;
    $hasUniqueIndex = false;
    $result = $conn->query("SHOW INDEX FROM employees WHERE Column_name = 'id'");
    if ($result instanceof mysqli_result) {
        while ($row = $result->fetch_assoc()) {
            $hasIndex = true;
            if ((int) ($row['Non_unique'] ?? 1) === 0) {
                $hasUniqueIndex = true;
            }
        }
    }

    if (!$hasIndex) {
        if (!$conn->query('ALTER TABLE employees ADD INDEX idx_employees_id (id)')) {
            throw new RuntimeException('Unable to prepare employees.id for performance foreign keys: ' . $conn->error);
        }
    }

    if (!$hasUniqueIndex) {
        if (!$conn->query('ALTER TABLE employees ADD UNIQUE KEY uniq_employees_id (id)')) {
            throw new RuntimeException('Unable to prepare a unique employees.id key for performance foreign keys: ' . $conn->error);
        }
    }
}

function performance_post_string(array $input, $key, $default = '')
{
    return trim((string) ($input[$key] ?? $default));
}

function performance_post_int(array $input, $key, $default = 0)
{
    return (int) ($input[$key] ?? $default);
}

function performance_post_float(array $input, $key, $default = 0)
{
    return round((float) ($input[$key] ?? $default), 2);
}

function performance_nullable_date($value)
{
    $value = trim((string) $value);
    return $value !== '' ? $value : null;
}

function performance_actor_id_from_session()
{
    if (!empty($_SESSION['admin_logged_in'])) {
        return (int) ($_SESSION['admin_id'] ?? 0);
    }

    if (!empty($_SESSION['employee_logged_in'])) {
        return (int) ($_SESSION['employee_id'] ?? 0);
    }

    return 0;
}

function performance_actor_role_from_session()
{
    if (!empty($_SESSION['admin_logged_in'])) {
        return 'admin';
    }

    return performance_normalize_role($_SESSION['role'] ?? $_SESSION['employee_role'] ?? 'employee');
}

function performance_find_cycle_id(mysqli $conn, $requestedCycleId = null)
{
    $cycleId = (int) $requestedCycleId;
    if ($cycleId > 0) {
        return $cycleId;
    }

    $query = "SELECT id FROM performance_cycles WHERE status IN ('Active', 'Scheduled') ORDER BY start_date DESC, id DESC LIMIT 1";
    $result = $conn->query($query);
    if ($result instanceof mysqli_result) {
        $row = $result->fetch_assoc();
        if (!empty($row['id'])) {
            return (int) $row['id'];
        }
    }

    $result = $conn->query("SELECT id FROM performance_cycles ORDER BY start_date DESC, id DESC LIMIT 1");
    if ($result instanceof mysqli_result) {
        $row = $result->fetch_assoc();
        if (!empty($row['id'])) {
            return (int) $row['id'];
        }
    }

    return null;
}

function performance_employee_manager_id(mysqli $conn, $employeeId)
{
    $employeeId = (int) $employeeId;
    if ($employeeId <= 0) {
        return null;
    }

    $stmt = $conn->prepare('SELECT manager FROM employees WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $employeeId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    $managerId = isset($row['manager']) ? (int) $row['manager'] : 0;
    return $managerId > 0 ? $managerId : null;
}

function performance_attendance_score_for_result(mysqli $conn, $employeeId)
{
    $employeeId = (int) $employeeId;
    if ($employeeId <= 0 || !performance_table_exists($conn, 'attendance')) {
        return 0;
    }

    $stmt = $conn->prepare("SELECT COUNT(*) AS total_days, SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) AS present_days FROM attendance WHERE employee_id = ? AND MONTH(punch_in_time) = MONTH(CURDATE()) AND YEAR(punch_in_time) = YEAR(CURDATE())");
    if (!$stmt) {
        return 0;
    }

    $stmt->bind_param('i', $employeeId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    $totalDays = (int) ($row['total_days'] ?? 0);
    $presentDays = (int) ($row['present_days'] ?? 0);
    if ($totalDays <= 0) {
        return 0;
    }

    return round(($presentDays / $totalDays) * 100, 2);
}

function performance_feedback_score(mysqli $conn, $employeeId, $cycleId = null)
{
    $employeeId = (int) $employeeId;
    if ($employeeId <= 0) {
        return 0;
    }

    $sql = "SELECT AVG(CASE feedback_type WHEN 'Recognition' THEN 100 WHEN 'Manager' THEN 88 WHEN 'Peer' THEN 80 WHEN '360' THEN 85 ELSE 72 END) AS score FROM feedbacks WHERE employee_id = ?";
    if ($cycleId) {
        $sql .= ' AND cycle_id = ?';
    }
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return 0;
    }

    if ($cycleId) {
        $stmt->bind_param('ii', $employeeId, $cycleId);
    } else {
        $stmt->bind_param('i', $employeeId);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return round((float) ($row['score'] ?? 0), 2);
}

function performance_checkin_score(mysqli $conn, $employeeId, $cycleId = null)
{
    $employeeId = (int) $employeeId;
    if ($employeeId <= 0) {
        return 0;
    }

    $sql = "SELECT AVG(CASE status WHEN 'Reviewed' THEN 100 WHEN 'Closed' THEN 100 WHEN 'Submitted' THEN 78 ELSE 40 END) AS score FROM checkins WHERE employee_id = ?";
    if ($cycleId) {
        $sql .= ' AND cycle_id = ?';
    }
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return 0;
    }

    if ($cycleId) {
        $stmt->bind_param('ii', $employeeId, $cycleId);
    } else {
        $stmt->bind_param('i', $employeeId);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return round((float) ($row['score'] ?? 0), 2);
}

function performance_recalculate_result(mysqli $conn, $employeeId, $cycleId = null)
{
    $employeeId = (int) $employeeId;
    if ($employeeId <= 0) {
        return false;
    }

    $cycleId = $cycleId ? (int) $cycleId : performance_find_cycle_id($conn, null);
    $managerId = performance_employee_manager_id($conn, $employeeId);

    $goalScore = 0;
    $sql = 'SELECT AVG(achievement_percentage) AS score FROM employee_goals WHERE employee_id = ?';
    if ($cycleId) {
        $sql .= ' AND cycle_id = ?';
    }
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        if ($cycleId) {
            $stmt->bind_param('ii', $employeeId, $cycleId);
        } else {
            $stmt->bind_param('i', $employeeId);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $goalScore = round((float) ($row['score'] ?? 0), 2);
        $stmt->close();
    }

    $selfReviewId = null;
    $selfReviewScore = 0;
    $sql = 'SELECT id, self_rating FROM self_reviews WHERE employee_id = ?';
    if ($cycleId) {
        $sql .= ' AND cycle_id = ?';
    }
    $sql .= ' ORDER BY updated_at DESC, id DESC LIMIT 1';
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        if ($cycleId) {
            $stmt->bind_param('ii', $employeeId, $cycleId);
        } else {
            $stmt->bind_param('i', $employeeId);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        if ($row) {
            $selfReviewId = (int) $row['id'];
            $selfReviewScore = round(((float) $row['self_rating']) * 20, 2);
        }
        $stmt->close();
    }

    $managerReviewId = null;
    $managerScore = 0;
    $sql = 'SELECT id, overall_rating FROM manager_reviews WHERE employee_id = ?';
    if ($cycleId) {
        $sql .= ' AND cycle_id = ?';
    }
    $sql .= ' ORDER BY updated_at DESC, id DESC LIMIT 1';
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        if ($cycleId) {
            $stmt->bind_param('ii', $employeeId, $cycleId);
        } else {
            $stmt->bind_param('i', $employeeId);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        if ($row) {
            $managerReviewId = (int) $row['id'];
            $managerScore = round(((float) $row['overall_rating']) * 20, 2);
        }
        $stmt->close();
    }

    $attendanceScore = performance_attendance_score_for_result($conn, $employeeId);
    $checkinScore = performance_checkin_score($conn, $employeeId, $cycleId);
    $feedbackScore = performance_feedback_score($conn, $employeeId, $cycleId);
    $finalScore = performance_calculate_score($goalScore, $managerScore, $attendanceScore, $selfReviewScore);
    $finalRating = performance_rating_from_score($finalScore);

    $existingId = null;
    $sql = 'SELECT id FROM performance_results WHERE employee_id = ?';
    if ($cycleId) {
        $sql .= ' AND cycle_id = ?';
    } else {
        $sql .= ' AND cycle_id IS NULL';
    }
    $sql .= ' LIMIT 1';
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        if ($cycleId) {
            $stmt->bind_param('ii', $employeeId, $cycleId);
        } else {
            $stmt->bind_param('i', $employeeId);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        if ($row) {
            $existingId = (int) $row['id'];
        }
        $stmt->close();
    }

    if ($existingId) {
        $stmt = $conn->prepare('UPDATE performance_results SET manager_id = ?, self_review_id = ?, manager_review_id = ?, goal_score = ?, checkin_score = ?, feedback_score = ?, attendance_score = ?, self_review_score = ?, manager_score = ?, final_score = ?, final_rating = ?, performance_bucket = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
        if (!$stmt) {
            return false;
        }
        $bucket = $finalScore >= 90 ? 'Top Talent' : ($finalScore >= 80 ? 'High' : ($finalScore >= 65 ? 'Medium' : 'Low'));
        $stmt->bind_param('iiidddddddssi', $managerId, $selfReviewId, $managerReviewId, $goalScore, $checkinScore, $feedbackScore, $attendanceScore, $selfReviewScore, $managerScore, $finalScore, $finalRating, $bucket, $existingId);
        $saved = $stmt->execute();
        $stmt->close();
        return $saved;
    }

    $stmt = $conn->prepare('INSERT INTO performance_results (employee_id, manager_id, cycle_id, self_review_id, manager_review_id, goal_score, checkin_score, feedback_score, attendance_score, self_review_score, manager_score, final_score, final_rating, performance_bucket) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    if (!$stmt) {
        return false;
    }
    $bucket = $finalScore >= 90 ? 'Top Talent' : ($finalScore >= 80 ? 'High' : ($finalScore >= 65 ? 'Medium' : 'Low'));
    $stmt->bind_param('iiiiidddddddss', $employeeId, $managerId, $cycleId, $selfReviewId, $managerReviewId, $goalScore, $checkinScore, $feedbackScore, $attendanceScore, $selfReviewScore, $managerScore, $finalScore, $finalRating, $bucket);
    $saved = $stmt->execute();
    $stmt->close();

    return $saved;
}

function performance_save_cycle(mysqli $conn, array $input)
{
    $cycleId = performance_post_int($input, 'cycle_id');
    $cycleName = performance_post_string($input, 'cycle_name');
    $reviewType = performance_post_string($input, 'review_type');
    $startDate = performance_post_string($input, 'start_date');
    $endDate = performance_post_string($input, 'end_date');
    $status = performance_post_string($input, 'status', 'Draft');
    $description = performance_post_string($input, 'description');

    if ($cycleName === '' || $reviewType === '' || $startDate === '' || $endDate === '') {
        throw new InvalidArgumentException('Cycle name, review type, start date and end date are required.');
    }

    if ($cycleId > 0) {
        $stmt = $conn->prepare('UPDATE performance_cycles SET cycle_name = ?, review_type = ?, description = ?, start_date = ?, end_date = ?, status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
        if (!$stmt) {
            throw new RuntimeException($conn->error);
        }
        $stmt->bind_param('ssssssi', $cycleName, $reviewType, $description, $startDate, $endDate, $status, $cycleId);
        $saved = $stmt->execute();
        $stmt->close();
        return $saved;
    }

    $stmt = $conn->prepare('INSERT INTO performance_cycles (cycle_name, review_type, description, start_date, end_date, status) VALUES (?, ?, ?, ?, ?, ?)');
    if (!$stmt) {
        throw new RuntimeException($conn->error);
    }
    $stmt->bind_param('ssssss', $cycleName, $reviewType, $description, $startDate, $endDate, $status);
    $saved = $stmt->execute();
    $stmt->close();

    return $saved;
}

function performance_save_goal(mysqli $conn, array $input, $actorId, $actorRole)
{
    $goalId = performance_post_int($input, 'goal_id');
    $employeeId = performance_post_int($input, 'employee_id');
    $goalTitle = performance_post_string($input, 'goal_title');
    $goalType = str_replace('SMART Goals', 'SMART Goal', performance_post_string($input, 'goal_type', 'KPI'));
    $weightage = performance_post_float($input, 'weightage');
    $achievement = performance_post_float($input, 'achievement_percentage');
    $dueDate = performance_post_string($input, 'due_date');
    $status = performance_post_string($input, 'status', 'Draft');
    $description = performance_post_string($input, 'description');

    if ($employeeId <= 0 || $goalTitle === '' || $dueDate === '') {
        throw new InvalidArgumentException('Employee, goal title and due date are required.');
    }

    $cycleId = performance_find_cycle_id($conn, $input['cycle_id'] ?? null);
    $managerId = $actorRole === 'manager' ? (int) $actorId : performance_employee_manager_id($conn, $employeeId);

    if ($goalId > 0) {
        $stmt = $conn->prepare('UPDATE employee_goals SET employee_id = ?, manager_id = ?, cycle_id = ?, goal_title = ?, description = ?, goal_type = ?, due_date = ?, weightage = ?, achievement_percentage = ?, status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
        if (!$stmt) {
            throw new RuntimeException($conn->error);
        }
        $stmt->bind_param('iiissssddsi', $employeeId, $managerId, $cycleId, $goalTitle, $description, $goalType, $dueDate, $weightage, $achievement, $status, $goalId);
        $saved = $stmt->execute();
        $stmt->close();
        if ($saved) {
            $progressNote = 'Goal updated from performance module.';
            $progressStatus = $achievement >= 100 ? 'Completed' : ($achievement >= 60 ? 'On Track' : 'At Risk');
            $progressDate = date('Y-m-d');
            $stmt = $conn->prepare('INSERT INTO goal_progress (goal_id, employee_id, updated_by, progress_date, progress_note, achievement_percentage, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
            if ($stmt) {
                $stmt->bind_param('iiissds', $goalId, $employeeId, $actorId, $progressDate, $progressNote, $achievement, $progressStatus);
                $stmt->execute();
                $stmt->close();
            }
            performance_recalculate_result($conn, $employeeId, $cycleId);
        }
        return $saved;
    }

    $stmt = $conn->prepare('INSERT INTO employee_goals (employee_id, manager_id, cycle_id, goal_title, description, goal_type, due_date, weightage, achievement_percentage, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    if (!$stmt) {
        throw new RuntimeException($conn->error);
    }
    $stmt->bind_param('iiissssdds', $employeeId, $managerId, $cycleId, $goalTitle, $description, $goalType, $dueDate, $weightage, $achievement, $status);
    $saved = $stmt->execute();
    $goalId = $saved ? (int) $stmt->insert_id : 0;
    $stmt->close();

    if (!$saved) {
        return false;
    }

    $progressNote = 'Initial goal assignment created.';
    $progressStatus = $achievement >= 100 ? 'Completed' : ($achievement >= 60 ? 'On Track' : 'At Risk');
    $progressDate = date('Y-m-d');
    $stmt = $conn->prepare('INSERT INTO goal_progress (goal_id, employee_id, updated_by, progress_date, progress_note, achievement_percentage, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
    if ($stmt) {
        $stmt->bind_param('iiissds', $goalId, $employeeId, $actorId, $progressDate, $progressNote, $achievement, $progressStatus);
        $stmt->execute();
        $stmt->close();
    }

    performance_recalculate_result($conn, $employeeId, $cycleId);
    return true;
}

function performance_save_feedback(mysqli $conn, array $input, $actorId, $actorRole)
{
    $feedbackId = performance_post_int($input, 'feedback_id');
    $employeeId = performance_post_int($input, 'employee_id');
    $feedbackType = performance_post_string($input, 'feedback_type', 'Manager');
    $title = performance_post_string($input, 'title');
    $comment = performance_post_string($input, 'comment');
    $recognition = performance_post_string($input, 'recognition');
    if ($employeeId <= 0 || $comment === '') {
        throw new InvalidArgumentException('Employee and comment are required.');
    }

    $cycleId = performance_find_cycle_id($conn, $input['cycle_id'] ?? null);
    $managerId = $actorRole === 'manager' ? (int) $actorId : performance_employee_manager_id($conn, $employeeId);
    $reviewerId = $actorId > 0 ? (int) $actorId : null;
    $badge = $feedbackType === 'Recognition' ? $recognition : null;

    if ($feedbackId > 0) {
        $stmt = $conn->prepare('UPDATE feedbacks SET employee_id = ?, reviewer_id = ?, manager_id = ?, cycle_id = ?, feedback_type = ?, feedback_title = ?, comments = ?, recognition_badge = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
        if (!$stmt) {
            throw new RuntimeException($conn->error);
        }
        $stmt->bind_param('iiiissssi', $employeeId, $reviewerId, $managerId, $cycleId, $feedbackType, $title, $comment, $badge, $feedbackId);
        $saved = $stmt->execute();
        $stmt->close();
        if ($saved) {
            performance_recalculate_result($conn, $employeeId, $cycleId);
        }
        return $saved;
    }

    $stmt = $conn->prepare('INSERT INTO feedbacks (employee_id, reviewer_id, manager_id, cycle_id, feedback_type, feedback_title, comments, recognition_badge, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    if (!$stmt) {
        throw new RuntimeException($conn->error);
    }
    $status = 'Submitted';
    $stmt->bind_param('iiiisssss', $employeeId, $reviewerId, $managerId, $cycleId, $feedbackType, $title, $comment, $badge, $status);
    $saved = $stmt->execute();
    $stmt->close();

    if ($saved) {
        performance_recalculate_result($conn, $employeeId, $cycleId);
    }

    return $saved;
}

function performance_save_checkin(mysqli $conn, array $input, $actorId, $actorRole)
{
    $checkinId = performance_post_int($input, 'checkin_id');
    $employeeId = performance_post_int($input, 'employee_id');
    $frequency = performance_post_string($input, 'frequency', 'Weekly');
    $progressSummary = performance_post_string($input, 'progress_summary');
    $achievements = performance_post_string($input, 'achievements');
    $challenges = performance_post_string($input, 'challenges');
    $managerComment = performance_post_string($input, 'manager_comment');
    $status = performance_post_string($input, 'status', 'Draft');

    if ($employeeId <= 0 || $progressSummary === '') {
        throw new InvalidArgumentException('Employee and progress summary are required.');
    }

    $cycleId = performance_find_cycle_id($conn, $input['cycle_id'] ?? null);
    $managerId = $actorRole === 'manager' ? (int) $actorId : performance_employee_manager_id($conn, $employeeId);
    $checkinDate = date('Y-m-d');

    if ($checkinId > 0) {
        $stmt = $conn->prepare('UPDATE checkins SET employee_id = ?, manager_id = ?, cycle_id = ?, checkin_date = ?, frequency_type = ?, progress_summary = ?, achievements = ?, challenges = ?, manager_comment = ?, status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
        if (!$stmt) {
            throw new RuntimeException($conn->error);
        }
        $stmt->bind_param('iiisssssssi', $employeeId, $managerId, $cycleId, $checkinDate, $frequency, $progressSummary, $achievements, $challenges, $managerComment, $status, $checkinId);
        $saved = $stmt->execute();
        $stmt->close();
        if ($saved) {
            performance_recalculate_result($conn, $employeeId, $cycleId);
        }
        return $saved;
    }

    $stmt = $conn->prepare('INSERT INTO checkins (employee_id, manager_id, cycle_id, checkin_date, frequency_type, progress_summary, achievements, challenges, manager_comment, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    if (!$stmt) {
        throw new RuntimeException($conn->error);
    }
    $stmt->bind_param('iiisssssss', $employeeId, $managerId, $cycleId, $checkinDate, $frequency, $progressSummary, $achievements, $challenges, $managerComment, $status);
    $saved = $stmt->execute();
    $stmt->close();

    if ($saved) {
        performance_recalculate_result($conn, $employeeId, $cycleId);
    }

    return $saved;
}

function performance_save_self_review(mysqli $conn, array $input)
{
    $employeeId = performance_post_int($input, 'employee_id');
    $rating = performance_post_float($input, 'rating');
    $completionPercentage = performance_post_float($input, 'completion_percentage');
    $achievements = performance_post_string($input, 'achievements');
    $comments = performance_post_string($input, 'additional_comments');
    $goalReview = performance_post_string($input, 'goal_review');
    $status = performance_post_string($input, 'status', 'Submitted');

    if ($employeeId <= 0) {
        throw new InvalidArgumentException('Employee is required.');
    }

    $cycleId = performance_find_cycle_id($conn, $input['cycle_id'] ?? null);
    $managerId = performance_employee_manager_id($conn, $employeeId);
    $submittedAt = in_array($status, array('Submitted', 'Reviewed'), true) ? date('Y-m-d H:i:s') : null;

    $existingId = null;
    $sql = 'SELECT id FROM self_reviews WHERE employee_id = ?';
    if ($cycleId) {
        $sql .= ' AND cycle_id = ?';
    } else {
        $sql .= ' AND cycle_id IS NULL';
    }
    $sql .= ' LIMIT 1';
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        if ($cycleId) {
            $stmt->bind_param('ii', $employeeId, $cycleId);
        } else {
            $stmt->bind_param('i', $employeeId);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        if ($row) {
            $existingId = (int) $row['id'];
        }
        $stmt->close();
    }

    if ($existingId) {
        $stmt = $conn->prepare('UPDATE self_reviews SET manager_id = ?, self_rating = ?, completion_percentage = ?, achievements = ?, additional_comments = ?, goal_review = ?, status = ?, submitted_at = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
        if (!$stmt) {
            throw new RuntimeException($conn->error);
        }
        $stmt->bind_param('iddsssssi', $managerId, $rating, $completionPercentage, $achievements, $comments, $goalReview, $status, $submittedAt, $existingId);
        $saved = $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare('INSERT INTO self_reviews (employee_id, manager_id, cycle_id, self_rating, completion_percentage, achievements, additional_comments, goal_review, status, submitted_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        if (!$stmt) {
            throw new RuntimeException($conn->error);
        }
        $stmt->bind_param('iiiddsssss', $employeeId, $managerId, $cycleId, $rating, $completionPercentage, $achievements, $comments, $goalReview, $status, $submittedAt);
        $saved = $stmt->execute();
        $stmt->close();
    }

    if ($saved) {
        performance_recalculate_result($conn, $employeeId, $cycleId);
    }

    return $saved;
}

function performance_save_manager_review(mysqli $conn, array $input, $actorId)
{
    $employeeId = performance_post_int($input, 'employee_id');
    if ($employeeId <= 0) {
        throw new InvalidArgumentException('Employee is required.');
    }

    $cycleId = performance_find_cycle_id($conn, $input['cycle_id'] ?? null);
    $technical = performance_post_float($input, 'technical_skill', 0);
    $communication = performance_post_float($input, 'communication', 0);
    $productivity = performance_post_float($input, 'productivity', 0);
    $teamwork = performance_post_float($input, 'teamwork', 0);
    $attendance = performance_post_float($input, 'attendance', 0);
    $leadership = performance_post_float($input, 'leadership', 0);
    $overallRating = round(($technical + $communication + $productivity + $teamwork + $attendance + $leadership) / 6, 2);
    $promotion = performance_post_string($input, 'promotion_recommendation');
    $incrementRaw = str_replace('%', '', performance_post_string($input, 'increment_recommendation'));
    $increment = $incrementRaw === '' ? null : round((float) $incrementRaw, 2);
    $comments = performance_post_string($input, 'comments');
    $status = performance_post_string($input, 'status', 'Submitted');
    $submittedAt = in_array($status, array('Submitted', 'Approved', 'Calibrated'), true) ? date('Y-m-d H:i:s') : null;

    $selfReviewId = null;
    $sql = 'SELECT id FROM self_reviews WHERE employee_id = ?';
    if ($cycleId) {
        $sql .= ' AND cycle_id = ?';
    }
    $sql .= ' ORDER BY updated_at DESC, id DESC LIMIT 1';
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        if ($cycleId) {
            $stmt->bind_param('ii', $employeeId, $cycleId);
        } else {
            $stmt->bind_param('i', $employeeId);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        if ($row) {
            $selfReviewId = (int) $row['id'];
        }
        $stmt->close();
    }

    $existingId = null;
    $sql = 'SELECT id FROM manager_reviews WHERE employee_id = ? AND reviewer_id = ?';
    if ($cycleId) {
        $sql .= ' AND cycle_id = ?';
    } else {
        $sql .= ' AND cycle_id IS NULL';
    }
    $sql .= ' LIMIT 1';
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        if ($cycleId) {
            $stmt->bind_param('iii', $employeeId, $actorId, $cycleId);
        } else {
            $stmt->bind_param('ii', $employeeId, $actorId);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        if ($row) {
            $existingId = (int) $row['id'];
        }
        $stmt->close();
    }

    if ($existingId) {
        $stmt = $conn->prepare('UPDATE manager_reviews SET technical_skill = ?, communication = ?, productivity = ?, teamwork = ?, attendance = ?, leadership = ?, overall_rating = ?, overall_comments = ?, promotion_recommendation = ?, increment_recommendation = ?, status = ?, submitted_at = ?, self_review_id = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
        if (!$stmt) {
            throw new RuntimeException($conn->error);
        }
        $stmt->bind_param('dddddddsssssii', $technical, $communication, $productivity, $teamwork, $attendance, $leadership, $overallRating, $comments, $promotion, $increment, $status, $submittedAt, $selfReviewId, $existingId);
        $saved = $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare('INSERT INTO manager_reviews (employee_id, reviewer_id, cycle_id, self_review_id, technical_skill, communication, productivity, teamwork, attendance, leadership, overall_rating, overall_comments, promotion_recommendation, increment_recommendation, status, submitted_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        if (!$stmt) {
            throw new RuntimeException($conn->error);
        }
        $stmt->bind_param('iiiidddddddssdss', $employeeId, $actorId, $cycleId, $selfReviewId, $technical, $communication, $productivity, $teamwork, $attendance, $leadership, $overallRating, $comments, $promotion, $increment, $status, $submittedAt);
        $saved = $stmt->execute();
        $stmt->close();
    }

    if ($saved) {
        performance_recalculate_result($conn, $employeeId, $cycleId);
    }

    return $saved;
}

function performance_save_recognition(mysqli $conn, array $input, $actorId)
{
    $badgeId = performance_post_int($input, 'badge_id');
    $employeeId = performance_post_int($input, 'employee_id');
    $badge = performance_post_string($input, 'badge');
    $points = performance_post_int($input, 'reward_points', 0);
    $reason = performance_post_string($input, 'reason');
    if ($employeeId <= 0 || $badge === '') {
        throw new InvalidArgumentException('Employee and badge are required.');
    }

    $cycleId = performance_find_cycle_id($conn, $input['cycle_id'] ?? null);
    $awardedAt = date('Y-m-d H:i:s');

    if ($badgeId > 0) {
        $stmt = $conn->prepare('UPDATE employee_badges SET employee_id = ?, reviewer_id = ?, cycle_id = ?, badge_name = ?, reward_points = ?, recognition_reason = ?, awarded_at = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
        if (!$stmt) {
            throw new RuntimeException($conn->error);
        }
        $stmt->bind_param('iiisissi', $employeeId, $actorId, $cycleId, $badge, $points, $reason, $awardedAt, $badgeId);
        $saved = $stmt->execute();
        $stmt->close();
        if ($saved) {
            performance_recalculate_result($conn, $employeeId, $cycleId);
        }
        return $saved;
    }

    $stmt = $conn->prepare('INSERT INTO employee_badges (employee_id, reviewer_id, cycle_id, badge_name, reward_points, recognition_reason, awarded_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
    if (!$stmt) {
        throw new RuntimeException($conn->error);
    }
    $stmt->bind_param('iiisiss', $employeeId, $actorId, $cycleId, $badge, $points, $reason, $awardedAt);
    $saved = $stmt->execute();
    $stmt->close();

    if ($saved) {
        performance_recalculate_result($conn, $employeeId, $cycleId);
    }

    return $saved;
}

function performance_save_pip(mysqli $conn, array $input, $actorId)
{
    $pipId = performance_post_int($input, 'pip_id');
    $employeeId = performance_post_int($input, 'employee_id');
    $mentorName = performance_post_string($input, 'mentor_name');
    $deadline = performance_post_string($input, 'deadline');
    $status = performance_post_string($input, 'status', 'Draft');
    $progress = performance_post_float($input, 'progress', 0);
    $notes = performance_post_string($input, 'notes');
    if ($employeeId <= 0 || $deadline === '') {
        throw new InvalidArgumentException('Employee and deadline are required.');
    }

    $cycleId = performance_find_cycle_id($conn, $input['cycle_id'] ?? null);
    $managerId = performance_employee_manager_id($conn, $employeeId);
    $resultId = null;
    $sql = 'SELECT id FROM performance_results WHERE employee_id = ?';
    if ($cycleId) {
        $sql .= ' AND cycle_id = ?';
    }
    $sql .= ' ORDER BY updated_at DESC, id DESC LIMIT 1';
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        if ($cycleId) {
            $stmt->bind_param('ii', $employeeId, $cycleId);
        } else {
            $stmt->bind_param('i', $employeeId);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        if ($row) {
            $resultId = (int) $row['id'];
        }
        $stmt->close();
    }

    $planTitle = 'Performance Improvement Plan';

    if ($pipId > 0) {
        $stmt = $conn->prepare('UPDATE pip_records SET employee_id = ?, manager_id = ?, mentor_id = ?, cycle_id = ?, performance_result_id = ?, mentor_name = ?, review_deadline = ?, status = ?, progress_percentage = ?, progress_notes = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
        if (!$stmt) {
            throw new RuntimeException($conn->error);
        }
        $stmt->bind_param('iiiiisssdsi', $employeeId, $managerId, $actorId, $cycleId, $resultId, $mentorName, $deadline, $status, $progress, $notes, $pipId);
        $saved = $stmt->execute();
        $stmt->close();
        return $saved;
    }

    $stmt = $conn->prepare('INSERT INTO pip_records (employee_id, manager_id, mentor_id, cycle_id, performance_result_id, plan_title, mentor_name, review_deadline, status, progress_percentage, progress_notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    if (!$stmt) {
        throw new RuntimeException($conn->error);
    }
    $stmt->bind_param('iiiiissssds', $employeeId, $managerId, $actorId, $cycleId, $resultId, $planTitle, $mentorName, $deadline, $status, $progress, $notes);
    $saved = $stmt->execute();
    $stmt->close();

    return $saved;
}

function performance_save_settings(mysqli $conn, array $input, $actorId)
{
    $settings = array(
        'employee_final_rating' => performance_post_string($input, 'employee_final_rating', '1'),
        'employee_manager_comments' => performance_post_string($input, 'employee_manager_comments', '1'),
        'employee_kpi_scores' => performance_post_string($input, 'employee_kpi_scores', '1')
    );

    $stmt = $conn->prepare('INSERT INTO performance_settings (setting_key, setting_value, updated_by) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by), updated_at = CURRENT_TIMESTAMP');
    if (!$stmt) {
        throw new RuntimeException($conn->error);
    }

    foreach ($settings as $key => $value) {
        $stmt->bind_param('ssi', $key, $value, $actorId);
        if (!$stmt->execute()) {
            $stmt->close();
            return false;
        }
    }

    $stmt->close();
    return true;
}

function performance_fetch_settings(mysqli $conn)
{
    $defaults = array(
        'employee_final_rating' => true,
        'employee_manager_comments' => true,
        'employee_kpi_scores' => true
    );

    if (!performance_table_exists($conn, 'performance_settings')) {
        return $defaults;
    }

    $result = $conn->query('SELECT setting_key, setting_value FROM performance_settings');
    if (!($result instanceof mysqli_result)) {
        return $defaults;
    }

    while ($row = $result->fetch_assoc()) {
        $defaults[$row['setting_key']] = $row['setting_value'] === '1';
    }

    return $defaults;
}