<?php

function performance_escape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function performance_json_attr($value)
{
    return htmlspecialchars((string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
}

function performance_slug($value)
{
    $value = strtolower(trim((string) $value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value);
    $value = trim((string) $value, '-');

    return $value !== '' ? $value : 'dashboard';
}

function performance_table_exists(mysqli $conn, $tableName)
{
    static $cache = array();

    if (isset($cache[$tableName])) {
        return $cache[$tableName];
    }

    $databaseResult = $conn->query("SELECT DATABASE() AS database_name");
    $databaseName = '';

    if ($databaseResult instanceof mysqli_result) {
        $row = $databaseResult->fetch_assoc();
        $databaseName = $row['database_name'] ?? '';
    }

    if ($databaseName === '') {
        $cache[$tableName] = false;
        return false;
    }

    $safeTable = $conn->real_escape_string($tableName);
    $safeDatabase = $conn->real_escape_string($databaseName);
    $query = "SELECT 1 FROM information_schema.tables WHERE table_schema = '{$safeDatabase}' AND table_name = '{$safeTable}' LIMIT 1";
    $result = $conn->query($query);
    $exists = $result instanceof mysqli_result && $result->num_rows > 0;
    $cache[$tableName] = $exists;

    return $exists;
}

function performance_normalize_role($role)
{
    $role = strtolower(trim((string) $role));

    if ($role === 'admin' || $role === 'hr' || $role === 'super admin' || $role === 'hr admin') {
        return 'admin';
    }

    if ($role === 'manager' || $role === 'supervisor') {
        return 'manager';
    }

    return 'employee';
}

function performance_status_class($status)
{
    $status = strtolower(trim((string) $status));

    if (in_array($status, array('active', 'approved', 'completed', 'reviewed', 'excellent', 'outstanding', 'on track'), true)) {
        return 'success';
    }

    if (in_array($status, array('scheduled', 'in progress', 'submitted', 'manager', 'peer', 'annual', 'quarterly', 'half-yearly'), true)) {
        return 'primary';
    }

    if (in_array($status, array('pending', 'pending approval', 'needs improvement', 'warning', 'monitoring'), true)) {
        return 'warning';
    }

    if (in_array($status, array('closed', 'poor', 'draft', 'high risk'), true)) {
        return 'danger';
    }

    return 'secondary';
}

function performance_rating_from_score($score)
{
    $score = (float) $score;

    if ($score >= 90) {
        return 'Outstanding';
    }
    if ($score >= 80) {
        return 'Excellent';
    }
    if ($score >= 70) {
        return 'Good';
    }
    if ($score >= 60) {
        return 'Needs Improvement';
    }

    return 'Poor';
}

function performance_calculate_score($kpi, $manager, $attendance, $self)
{
    return round(($kpi * 0.4) + ($manager * 0.3) + ($attendance * 0.2) + ($self * 0.1), 2);
}

function performance_percent($value)
{
    return number_format((float) $value, ((float) $value == floor((float) $value)) ? 0 : 1) . '%';
}

function performance_average($values)
{
    if (!$values || count($values) === 0) {
        return 0;
    }

    return array_sum($values) / count($values);
}

function performance_initials($name)
{
    $parts = preg_split('/\s+/', trim((string) $name));
    $initials = '';

    foreach ($parts as $part) {
        if ($part !== '') {
            $initials .= strtoupper(substr($part, 0, 1));
        }
        if (strlen($initials) >= 2) {
            break;
        }
    }

    return $initials !== '' ? $initials : 'PM';
}

function performance_menu_config($context, $role)
{
    if ($context === 'admin') {
        return array(
            'dashboard' => 'Dashboard',
            'review-cycles' => 'Review Cycles',
            'goals-kpis' => 'Goals & KPIs',
            'feedback' => 'Feedback',
            'check-ins' => 'Check-Ins',
            'self-reviews' => 'Self Reviews',
            'manager-reviews' => 'Manager Reviews',
            'reports' => 'Reports',
            'recognition' => 'Recognition',
            'pip' => 'PIP',
            'settings' => 'Settings'
        );
    }

    $menu = array(
        'my-dashboard' => 'My Dashboard',
        'my-goals' => 'My Goals',
        'my-checkins' => 'My Check-Ins',
        'my-self-reviews' => 'My Self Reviews',
        'my-feedback' => 'My Feedback',
        'my-recognition' => 'My Recognition',
        'my-history' => 'My Performance'
    );

    if ($role === 'manager') {
        $menu['team-dashboard'] = 'Team Performance Dashboard';
        $menu['team-goals'] = 'Team Goals';
        $menu['team-reviews'] = 'Team Reviews';
        $menu['pending-approvals'] = 'Pending Approvals';
        $menu['employee-feedback'] = 'Employee Feedback';
        $menu['team-analytics'] = 'Team Analytics';
        $menu['goal-assignment'] = 'Goal Assignment';
        $menu['performance-monitoring'] = 'Performance Monitoring';
    }

    return $menu;
}

function performance_can_access_view($context, $role, $view)
{
    $menu = performance_menu_config($context, $role);
    return isset($menu[$view]);
}
