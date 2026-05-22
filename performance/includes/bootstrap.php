<?php

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/components.php';
require_once __DIR__ . '/data.php';
require_once __DIR__ . '/renderers.php';

$performanceContext = isset($performanceContext) ? $performanceContext : 'admin';
$performanceUserRole = isset($performanceUserRole) ? $performanceUserRole : 'Admin';
$performanceUserId = isset($performanceUserId) ? (int) $performanceUserId : 0;
$performanceRole = performance_normalize_role($performanceUserRole);

try {
    performance_install_schema($conn);
    $performanceInstallerError = null;
} catch (Throwable $exception) {
    $performanceInstallerError = $exception->getMessage();
}

$performanceMenu = performance_menu_config($performanceContext, $performanceRole);
$performanceView = performance_slug($_GET['view'] ?? array_key_first($performanceMenu));

if (!performance_can_access_view($performanceContext, $performanceRole, $performanceView)) {
    $performanceView = array_key_first($performanceMenu);
}

$performanceData = performance_load_data($conn, $performanceContext, $performanceRole, $performanceUserId);
