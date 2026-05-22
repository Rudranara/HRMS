<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require dirname(__DIR__) . '/../db_connection.php';
require dirname(__DIR__) . '/includes/helpers.php';
require dirname(__DIR__) . '/includes/storage.php';

function performance_ajax_response($status, $message, $extra = array())
{
    echo json_encode(array_merge(array('status' => $status, 'message' => $message), $extra));
    exit;
}

$hasAdmin = !empty($_SESSION['admin_logged_in']);
$hasEmployee = !empty($_SESSION['employee_logged_in']);

if (!$hasAdmin && !$hasEmployee) {
    performance_ajax_response('error', 'Unauthorized request.');
}

$action = performance_slug($_POST['action'] ?? '');

if ($action === '') {
    performance_ajax_response('error', 'Invalid action.');
}

try {
    performance_install_schema($conn);
} catch (Throwable $exception) {
    performance_ajax_response('error', $exception->getMessage());
}

$actorId = performance_actor_id_from_session();
$actorRole = performance_actor_role_from_session();

try {
    switch ($action) {
        case 'save-cycle':
            if ($actorRole !== 'admin') {
                performance_ajax_response('error', 'Only admin or HR users can create cycles.');
            }
            performance_save_cycle($conn, $_POST);
            performance_ajax_response('success', 'Review cycle saved successfully.', array('reload' => true));
            break;

        case 'save-goal':
            performance_save_goal($conn, $_POST, $actorId, $actorRole);
            performance_ajax_response('success', 'Goal saved successfully.', array('reload' => true));
            break;

        case 'save-feedback':
            performance_save_feedback($conn, $_POST, $actorId, $actorRole);
            performance_ajax_response('success', 'Feedback published successfully.', array('reload' => true));
            break;

        case 'save-checkin':
            performance_save_checkin($conn, $_POST, $actorId, $actorRole);
            performance_ajax_response('success', 'Check-in updated successfully.', array('reload' => true));
            break;

        case 'save-self-review':
            performance_save_self_review($conn, $_POST);
            performance_ajax_response('success', 'Self review submitted successfully.', array('reload' => true));
            break;

        case 'save-manager-review':
            if (!in_array($actorRole, array('admin', 'manager'), true)) {
                performance_ajax_response('error', 'Only managers or admin users can submit manager reviews.');
            }
            performance_save_manager_review($conn, $_POST, $actorId);
            performance_ajax_response('success', 'Manager review submitted successfully.', array('reload' => true));
            break;

        case 'save-recognition':
            performance_save_recognition($conn, $_POST, $actorId);
            performance_ajax_response('success', 'Recognition saved successfully.', array('reload' => true));
            break;

        case 'save-pip':
            if ($actorRole !== 'admin') {
                performance_ajax_response('error', 'Only admin or HR users can create PIP records.');
            }
            performance_save_pip($conn, $_POST, $actorId);
            performance_ajax_response('success', 'PIP record saved successfully.', array('reload' => true));
            break;

        case 'save-settings':
            if ($actorRole !== 'admin') {
                performance_ajax_response('error', 'Only admin or HR users can update settings.');
            }
            performance_save_settings($conn, $_POST, $actorId);
            performance_ajax_response('success', 'Visibility settings saved successfully.', array('reload' => true));
            break;
    }
} catch (InvalidArgumentException $exception) {
    performance_ajax_response('error', $exception->getMessage());
} catch (Throwable $exception) {
    performance_ajax_response('error', 'Unable to complete this action right now: ' . $exception->getMessage());
}

performance_ajax_response('warning', 'This action is not available.');
