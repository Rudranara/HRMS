<?php
session_start();
require 'db_connection.php';
require_once 'includes/onboarding_helper.php';

header('Content-Type: application/json');

onboardingEnsureSchema($conn);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$onboardingId = (int) ($_SESSION['onboarding_portal_id'] ?? 0);
if ($onboardingId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please log in again.']);
    exit;
}

try {
    $action = trim($_POST['action'] ?? '');

    if ($action === 'save_section') {
        $section = trim($_POST['section'] ?? '');
        $state = onboardingSaveSection($conn, $onboardingId, $section, $_POST);
        echo json_encode([
            'success' => true,
            'message' => 'Section saved.',
            'progress' => $state['progress'],
            'status' => $state['status'],
            'sections' => $state['sections'],
        ]);
        exit;
    }

    if ($action === 'upload_document') {
        $documentKey = trim($_POST['document_key'] ?? '');
        $state = onboardingUploadDocument($conn, $onboardingId, $documentKey, $_FILES['document_file'] ?? []);
        echo json_encode([
            'success' => true,
            'message' => 'Document uploaded successfully.',
            'progress' => $state['progress'],
            'status' => $state['status'],
            'sections' => $state['sections'],
        ]);
        exit;
    }

    if ($action === 'submit_for_approval') {
        $state = onboardingSubmitForApproval($conn, $onboardingId);
        echo json_encode([
            'success' => true,
            'message' => 'Submitted for approval.',
            'progress' => $state['progress'],
            'status' => 'Submitted',
            'sections' => $state['sections'],
        ]);
        exit;
    }

    throw new RuntimeException('Unsupported onboarding action.');
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
