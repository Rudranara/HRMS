<?php
session_start();
require 'db_connection.php';
require_once 'includes/onboarding_helper.php';

onboardingEnsureSchema($conn);

$message = '';
$error = '';
$sessionKey = 'onboarding_portal_id';
$tokenKey = 'onboarding_portal_token';

if (isset($_GET['logout'])) {
    unset($_SESSION[$sessionKey], $_SESSION[$tokenKey]);
    header('Location: onboarding');
    exit;
}

if (!empty($_GET['token'])) {
    $_SESSION[$tokenKey] = trim($_GET['token']);
}

$token = $_SESSION[$tokenKey] ?? '';
$record = $token ? onboardingGetRecordByToken($conn, $token) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['login_onboarding'])) {
            $token = trim($_POST['token'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $record = onboardingGetRecordByToken($conn, $token);

            if (!$record) {
                throw new RuntimeException('Invalid onboarding link.');
            }

            if (!onboardingVerifyPortalPassword($record, $password)) {
                throw new RuntimeException('Incorrect password.');
            }

            $_SESSION[$sessionKey] = (int) $record['id'];
            $_SESSION[$tokenKey] = $token;
            header('Location: onboarding');
            exit;
        }

        if (isset($_POST['set_portal_password'])) {
            $onboardingId = (int) ($_SESSION[$sessionKey] ?? 0);
            $newPassword = trim($_POST['new_password'] ?? '');
            $confirmPassword = trim($_POST['confirm_password'] ?? '');

            if ($newPassword === '' || strlen($newPassword) < 6) {
                throw new RuntimeException('Password must be at least 6 characters long.');
            }

            if ($newPassword !== $confirmPassword) {
                throw new RuntimeException('Password confirmation does not match.');
            }

            onboardingSetPortalPassword($conn, $onboardingId, $newPassword);
            $message = 'Password set successfully. You can continue onboarding now.';
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$authenticatedRecord = null;
if (!empty($_SESSION[$sessionKey])) {
    $authenticatedRecord = onboardingGetRecordById($conn, (int) $_SESSION[$sessionKey]);
    if ($authenticatedRecord && $token && $authenticatedRecord['invitation_token'] !== $token) {
        unset($_SESSION[$sessionKey]);
        $authenticatedRecord = null;
    }
}

$org = $conn->query("SELECT * FROM organization LIMIT 1")->fetch_assoc();
$documents = [];
$tasks = [];
$sections = [];

if ($authenticatedRecord && (int) $authenticatedRecord['first_login_completed'] === 1) {
    $documents = onboardingGetDocuments($conn, (int) $authenticatedRecord['id']);
    $tasks = onboardingGetTasks($conn, (int) $authenticatedRecord['id']);
    $state = onboardingPersistProgress($conn, (int) $authenticatedRecord['id']);
    $authenticatedRecord = onboardingGetRecordById($conn, (int) $authenticatedRecord['id']);
    $sections = $state['sections'];
}

function portalSectionBadge(bool $isDone): string
{
    return $isDone ? 'success' : 'secondary';
}

function onboardingMissingSectionLabels(array $sections): array
{
    $labels = [
        'personal' => 'Personal Details',
        'contact' => 'Contact Info',
        'bank' => 'Bank Details',
        'statutory' => 'Statutory Details',
        'documents' => 'Documents',
    ];

    $missing = [];
    foreach ($labels as $key => $label) {
        if (empty($sections[$key])) {
            $missing[] = $label;
        }
    }

    return $missing;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?= htmlspecialchars($org['name'] ?? 'HRMS') ?> Onboarding Portal</title>
    <link href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,800" rel="stylesheet" />
    <link href="admin/assets/css/soft-ui-dashboard.css?v=1.1.0" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: linear-gradient(135deg, #fff7ed 0%, #eff6ff 45%, #ecfeff 100%); min-height: 100vh; }
        .onboarding-shell { max-width: 1180px; margin: 0 auto; padding: 32px 16px 48px; }
        .hero-card { background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 55%, #0ea5e9 100%); color: #fff; border-radius: 24px; overflow: hidden; }
        .step-card { border: 1px solid rgba(15, 23, 42, 0.08); border-radius: 18px; background: rgba(255,255,255,0.9); }
        .doc-card { border: 1px dashed rgba(15, 23, 42, 0.18); border-radius: 16px; }
        .save-state { font-size: 0.85rem; color: #64748b; }
        .sticky-summary { position: sticky; top: 24px; }
    </style>
</head>
<body>
<div class="onboarding-shell">
    <div class="hero-card card mb-4">
        <div class="card-body p-4 p-lg-5">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <p class="text-uppercase text-white-50 mb-2">Checklist-Based Onboarding</p>
                    <h2 class="text-white mb-2">Complete your onboarding in guided steps</h2>
                    <p class="mb-0 text-white-50">Personal details, contact info, bank details, statutory details, documents, and final review. Your progress saves automatically so you can continue anytime.</p>
                </div>
                <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                    <?php if ($authenticatedRecord): ?>
                        <a href="onboarding?logout=1" class="btn btn-light mb-0">Logout</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if ($message !== ''): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!$record): ?>
        <div class="card"><div class="card-body p-4"><h5 class="mb-2">Secure onboarding link required</h5><p class="mb-0 text-sm">Open the invitation link sent by HR to continue your onboarding.</p></div></div>
    <?php elseif (!$authenticatedRecord): ?>
        <div class="row justify-content-center">
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header pb-0">
                        <h5 class="mb-1">Invitation & Login</h5>
                        <p class="text-sm mb-0">Welcome <?= htmlspecialchars($record['candidate_name']) ?>. Enter your temporary password to continue.</p>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($record['email']) ?>" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><?= !empty($record['portal_password_hash']) ? 'Password' : 'Temporary Password' ?></label>
                                <input type="password" class="form-control" name="password" required>
                            </div>
                            <button type="submit" name="login_onboarding" class="btn bg-gradient-dark w-100 mb-0">Access Onboarding Portal</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif ((int) $authenticatedRecord['first_login_completed'] === 0): ?>
        <div class="row justify-content-center">
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header pb-0">
                        <h5 class="mb-1">First Login Setup</h5>
                        <p class="text-sm mb-0">Set your password once. After that, the same secure link will let you resume anytime.</p>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" class="form-control" name="new_password" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" name="confirm_password" required>
                            </div>
                            <button type="submit" name="set_portal_password" class="btn bg-gradient-dark w-100 mb-0">Save Password & Continue</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php
        $forms = [
            'personal' => [
                ['name' => 'full_name', 'label' => 'Full Name', 'type' => 'text', 'value' => $authenticatedRecord['full_name'] ?: $authenticatedRecord['candidate_name']],
                ['name' => 'date_of_birth', 'label' => 'Date of Birth', 'type' => 'date', 'value' => $authenticatedRecord['date_of_birth']],
                ['name' => 'gender', 'label' => 'Gender', 'type' => 'text', 'value' => $authenticatedRecord['gender']],
                ['name' => 'marital_status', 'label' => 'Marital Status', 'type' => 'text', 'value' => $authenticatedRecord['marital_status']],
            ],
            'contact' => [
                ['name' => 'phone', 'label' => 'Phone Number', 'type' => 'text', 'value' => $authenticatedRecord['phone']],
                ['name' => 'alternate_email', 'label' => 'Alternate Email', 'type' => 'email', 'value' => $authenticatedRecord['alternate_email']],
                ['name' => 'permanent_address', 'label' => 'Permanent Address', 'type' => 'textarea', 'value' => $authenticatedRecord['permanent_address']],
                ['name' => 'current_address', 'label' => 'Current Address', 'type' => 'textarea', 'value' => $authenticatedRecord['current_address']],
            ],
            'bank' => [
                ['name' => 'bank_account_number', 'label' => 'Account Number', 'type' => 'text', 'value' => $authenticatedRecord['bank_account_number']],
                ['name' => 'bank_ifsc_code', 'label' => 'IFSC Code', 'type' => 'text', 'value' => $authenticatedRecord['bank_ifsc_code']],
                ['name' => 'bank_name', 'label' => 'Bank Name', 'type' => 'text', 'value' => $authenticatedRecord['bank_name']],
            ],
            'statutory' => [
                ['name' => 'pan_number', 'label' => 'PAN', 'type' => 'text', 'value' => $authenticatedRecord['pan_number']],
                ['name' => 'aadhaar_number', 'label' => 'Aadhaar', 'type' => 'text', 'value' => $authenticatedRecord['aadhaar_number']],
                ['name' => 'uan_number', 'label' => 'UAN', 'type' => 'text', 'value' => $authenticatedRecord['uan_number']],
            ],
        ];
        $checklist = [
            'personal' => 'Personal Details',
            'contact' => 'Contact Info',
            'bank' => 'Bank Details',
            'statutory' => 'Statutory Details',
            'documents' => 'Documents',
        ];
        $missingSections = onboardingMissingSectionLabels($sections);
        ?>
        <div class="row">
            <div class="col-lg-8 mb-4">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5 class="mb-1">Onboarding Dashboard</h5>
                                <p class="text-sm mb-0">Completion: <strong id="progressText"><?= (int) $authenticatedRecord['progress_percent'] ?>%</strong></p>
                            </div>
                            <span class="badge bg-gradient-<?= $authenticatedRecord['status'] === 'Submitted' ? 'warning' : ($authenticatedRecord['status'] === 'Active' ? 'success' : 'info') ?>" id="statusBadge"><?= htmlspecialchars($authenticatedRecord['status']) ?></span>
                        </div>
                        <div class="progress mb-4" style="height: 10px;"><div class="progress-bar bg-gradient-info" id="progressBar" role="progressbar" style="width: <?= (int) $authenticatedRecord['progress_percent'] ?>%"></div></div>
                        <div class="row">
                            <?php foreach ($checklist as $key => $label): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="step-card p-3 h-100">
                                        <p class="text-sm mb-1"><?= htmlspecialchars($label) ?></p>
                                        <span class="badge bg-gradient-<?= portalSectionBadge(!empty($sections[$key])) ?>" data-section-badge="<?= htmlspecialchars($key) ?>"><?= !empty($sections[$key]) ? 'Complete' : 'Pending' ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <?php foreach ($forms as $section => $fields): ?>
                    <div class="card mb-4">
                        <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0 text-capitalize"><?= htmlspecialchars(str_replace('_', ' ', $section)) ?></h6>
                                <p class="text-sm mb-0">Auto-save is enabled for this step.</p>
                            </div>
                            <span class="save-state" data-save-state="<?= htmlspecialchars($section) ?>">Waiting for changes</span>
                        </div>
                        <div class="card-body">
                            <form class="autosave-form" data-section="<?= htmlspecialchars($section) ?>">
                                <div class="row">
                                    <?php foreach ($fields as $field): ?>
                                        <div class="col-md-<?= $field['type'] === 'textarea' ? '12' : '6' ?> mb-3">
                                            <label class="form-label"><?= htmlspecialchars($field['label']) ?></label>
                                            <?php if ($field['type'] === 'textarea'): ?>
                                                <textarea class="form-control" name="<?= htmlspecialchars($field['name']) ?>" rows="3"><?= htmlspecialchars($field['value'] ?? '') ?></textarea>
                                            <?php else: ?>
                                                <input type="<?= htmlspecialchars($field['type']) ?>" class="form-control" name="<?= htmlspecialchars($field['name']) ?>" value="<?= htmlspecialchars($field['value'] ?? '') ?>">
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="submit" class="btn bg-gradient-dark mb-0">Save <?= htmlspecialchars(ucfirst($section)) ?></button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="card mb-4">
                    <div class="card-header pb-0"><h6 class="mb-0">Document Upload Module</h6><p class="text-sm mb-0">Each document tracks upload status and verification status.</p></div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($documents as $document): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="doc-card p-3 h-100">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <h6 class="mb-1"><?= htmlspecialchars($document['document_label']) ?></h6>
                                                <p class="text-sm mb-0">Upload: <?= htmlspecialchars($document['upload_status']) ?><br>Verification: <?= htmlspecialchars($document['verification_status']) ?></p>
                                            </div>
                                            <?php if (!empty($document['file_path'])): ?><a href="<?= htmlspecialchars($document['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline-dark mb-0">View</a><?php endif; ?>
                                        </div>
                                        <form class="document-upload-form" data-document-key="<?= htmlspecialchars($document['document_key']) ?>">
                                            <input type="file" class="form-control mb-3" name="document_file" accept=".pdf,.jpg,.jpeg,.png" required>
                                            <button type="submit" class="btn bg-gradient-dark btn-sm mb-0">Upload</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header pb-0"><h6 class="mb-0">Final Review & Submit</h6><p class="text-sm mb-0">After all steps are complete, submit your onboarding for HR approval.</p></div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6"><strong>Name:</strong> <?= htmlspecialchars($authenticatedRecord['full_name'] ?: $authenticatedRecord['candidate_name']) ?></div>
                            <div class="col-md-6"><strong>Email:</strong> <?= htmlspecialchars($authenticatedRecord['email']) ?></div>
                            <div class="col-md-6"><strong>Joining Date:</strong> <?= htmlspecialchars($authenticatedRecord['joining_date']) ?></div>
                            <div class="col-md-6"><strong>Department / Role:</strong> <?= htmlspecialchars(($authenticatedRecord['department'] ?: 'Not assigned') . ' / ' . ($authenticatedRecord['role_title'] ?: 'Employee')) ?></div>
                        </div>
                        <button type="button" class="btn bg-gradient-success mb-0" id="submitApprovalBtn" <?= (int) $authenticatedRecord['progress_percent'] < 100 || in_array($authenticatedRecord['status'], ['Submitted', 'Active'], true) ? 'disabled' : '' ?>>Submit for Approval</button>
                        <p class="text-sm text-secondary mt-3 mb-0" id="submitHelpText">
                            <?php if ((int) $authenticatedRecord['progress_percent'] < 100): ?>
                                Complete these sections first: <?= htmlspecialchars(implode(', ', $missingSections)) ?>
                            <?php elseif (in_array($authenticatedRecord['status'], ['Submitted', 'Active'], true)): ?>
                                This onboarding is already <?= htmlspecialchars(strtolower($authenticatedRecord['status'])) ?>.
                            <?php else: ?>
                                All required sections are complete. You can submit now.
                            <?php endif; ?>
                        </p>
                        <?php if ($authenticatedRecord['status'] === 'Submitted'): ?>
                            <p class="text-sm text-warning mt-3 mb-0">Submitted successfully. HR verification is pending.</p>
                        <?php elseif ($authenticatedRecord['status'] === 'Rejected'): ?>
                            <p class="text-sm text-danger mt-3 mb-0">HR requested changes: <?= htmlspecialchars($authenticatedRecord['review_comment'] ?: 'Please review your details and submit again.') ?></p>
                        <?php elseif ($authenticatedRecord['status'] === 'Active'): ?>
                            <p class="text-sm text-success mt-3 mb-0">Your onboarding is approved and your employee account is active.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 mb-4">
                <div class="sticky-summary">
                    <div class="card mb-4">
                        <div class="card-header pb-0"><h6 class="mb-0">Assigned Tasks</h6></div>
                        <div class="card-body">
                            <?php foreach ($tasks as $task): ?>
                                <div class="border border-radius-md p-3 mb-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <strong><?= htmlspecialchars($task['title']) ?></strong>
                                        <span class="badge bg-gradient-<?= $task['status'] === 'Completed' ? 'success' : ($task['status'] === 'In Progress' ? 'info' : 'secondary') ?>"><?= htmlspecialchars($task['status']) ?></span>
                                    </div>
                                    <p class="text-sm mb-1"><?= htmlspecialchars($task['description']) ?></p>
                                    <p class="text-xs text-secondary mb-0"><?= htmlspecialchars($task['owner_type']) ?> • Due <?= htmlspecialchars($task['due_date'] ?: '-') ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header pb-0"><h6 class="mb-0">Important</h6></div>
                        <div class="card-body">
                            <p class="text-sm mb-2">Bank details are required for payroll processing.</p>
                            <p class="text-sm mb-2">PAN, Aadhaar, and UAN are needed for India statutory compliance.</p>
                            <p class="text-sm mb-0">You can log out and return later using the same invitation link.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>
            const saveEndpoint = 'onboarding_save';
            const sectionLabels = {
                personal: 'Personal Details',
                contact: 'Contact Info',
                bank: 'Bank Details',
                statutory: 'Statutory Details',
                documents: 'Documents'
            };

            function updateSubmitButton(payload) {
                const submitBtn = document.getElementById('submitApprovalBtn');
                const helpText = document.getElementById('submitHelpText');
                const status = payload?.status ?? 'In Progress';
                const progress = Number(payload?.progress ?? 0);
                const sections = payload?.sections ?? {};

                if (!submitBtn || !helpText) return;

                const missing = Object.entries(sectionLabels)
                    .filter(([key]) => !sections[key])
                    .map(([, label]) => label);

                const shouldDisable = progress < 100 || status === 'Submitted' || status === 'Active';
                submitBtn.disabled = shouldDisable;

                if (status === 'Submitted') {
                    helpText.textContent = 'This onboarding is already submitted for HR approval.';
                } else if (status === 'Active') {
                    helpText.textContent = 'This onboarding is already active.';
                } else if (progress < 100) {
                    helpText.textContent = 'Complete these sections first: ' + missing.join(', ');
                } else {
                    helpText.textContent = 'All required sections are complete. You can submit now.';
                }
            }

            function updateProgressUI(payload) {
                if (!payload) return;
                const progress = payload.progress ?? 0;
                const status = payload.status ?? 'In Progress';
                const progressBar = document.getElementById('progressBar');
                const progressText = document.getElementById('progressText');
                const statusBadge = document.getElementById('statusBadge');
                if (progressBar) progressBar.style.width = progress + '%';
                if (progressText) progressText.textContent = progress + '%';
                if (statusBadge) statusBadge.textContent = status;
                if (payload.sections) {
                    Object.entries(payload.sections).forEach(([key, value]) => {
                        const badge = document.querySelector(`[data-section-badge="${key}"]`);
                        if (badge) {
                            badge.className = 'badge bg-gradient-' + (value ? 'success' : 'secondary');
                            badge.textContent = value ? 'Complete' : 'Pending';
                        }
                    });
                }
                updateSubmitButton(payload);
            }
            document.querySelectorAll('.autosave-form').forEach((form) => {
                const section = form.dataset.section;
                const saveState = document.querySelector(`[data-save-state="${section}"]`);
                let timer = null;
                async function submitForm(isManual = false) {
                    const data = new FormData(form);
                    data.append('action', 'save_section');
                    data.append('section', section);
                    if (saveState) saveState.textContent = isManual ? 'Saving...' : 'Auto-saving...';
                    const response = await fetch(saveEndpoint, { method: 'POST', body: data });
                    const payload = await response.json();
                    if (!payload.success) {
                        if (saveState) saveState.textContent = payload.message || 'Save failed';
                        return;
                    }
                    updateProgressUI(payload);
                    if (saveState) saveState.textContent = isManual ? 'Saved' : 'Auto-saved';
                }
                form.addEventListener('input', () => {
                    if (timer) clearTimeout(timer);
                    timer = setTimeout(() => submitForm(false), 800);
                });
                form.addEventListener('submit', (event) => {
                    event.preventDefault();
                    submitForm(true);
                });
            });
            document.querySelectorAll('.document-upload-form').forEach((form) => {
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    const data = new FormData(form);
                    data.append('action', 'upload_document');
                    data.append('document_key', form.dataset.documentKey);
                    const response = await fetch(saveEndpoint, { method: 'POST', body: data });
                    const payload = await response.json();
                    if (!payload.success) {
                        alert(payload.message || 'Upload failed');
                        return;
                    }
                    updateProgressUI(payload);
                    window.location.reload();
                });
            });
            const submitBtn = document.getElementById('submitApprovalBtn');
            if (submitBtn) {
                submitBtn.addEventListener('click', async () => {
                    if (submitBtn.disabled) {
                        return;
                    }
                    const data = new FormData();
                    data.append('action', 'submit_for_approval');
                    const response = await fetch(saveEndpoint, { method: 'POST', body: data });
                    const payload = await response.json();
                    if (!payload.success) {
                        alert(payload.message || 'Submission failed');
                        return;
                    }
                    updateProgressUI(payload);
                    window.location.reload();
                });
            }
            updateSubmitButton({
                progress: <?= (int) $authenticatedRecord['progress_percent'] ?>,
                status: <?= json_encode($authenticatedRecord['status']) ?>,
                sections: <?= json_encode($sections) ?>
            });
        </script>
    <?php endif; ?>
</div>
</body>
</html>
