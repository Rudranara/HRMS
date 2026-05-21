<?php
include("header.php");
require_once '../includes/onboarding_helper.php';

onboardingEnsureSchema($conn);

$recordId = (int) ($_GET['id'] ?? 0);
$record = onboardingGetRecordById($conn, $recordId);

if (!$record) {
    echo '<div class="container-fluid py-4"><div class="alert alert-danger">Onboarding record not found.</div></div>';
    include("footer.php");
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['update_document'])) {
            onboardingUpdateDocumentVerification(
                $conn,
                (int) $_POST['document_id'],
                trim($_POST['verification_status'] ?? 'Pending'),
                trim($_POST['verification_comment'] ?? '')
            );
            $message = 'Document verification updated.';
        }

        if (isset($_POST['update_task'])) {
            onboardingUpdateTaskStatus(
                $conn,
                (int) $_POST['task_id'],
                trim($_POST['task_status'] ?? 'Pending')
            );
            $message = 'Task status updated.';
        }

        if (isset($_POST['review_decision'])) {
            $decision = trim($_POST['review_decision']);
            $status = onboardingReviewDecision($conn, $recordId, $decision, trim($_POST['review_comment'] ?? ''));
            $message = $status === 'Active' ? 'Employee approved and activated successfully.' : 'Onboarding rejected with comments.';
        }

        $record = onboardingGetRecordById($conn, $recordId);
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$documents = onboardingGetDocuments($conn, $recordId);
$tasks = onboardingGetTasks($conn, $recordId);
$state = onboardingPersistProgress($conn, $recordId);
$record = onboardingGetRecordById($conn, $recordId);
$sections = $state['sections'];

function reviewBadge(string $state): string
{
    return match ($state) {
        'Verified', 'Completed', 'Active' => 'success',
        'Submitted' => 'warning',
        'Rejected' => 'danger',
        'In Progress' => 'info',
        default => 'secondary',
    };
}
?>

<style>
.review-shell {
    padding-bottom: 1.5rem;
}

.review-alert {
    border: 1px solid transparent;
    border-radius: 18px;
    box-shadow: 0 12px 28px rgba(31, 41, 55, 0.06);
    padding: 1rem 1.1rem;
}

.review-alert.alert-success {
    background: #f0fbf5;
    border-color: #d7f1e1;
    color: #4f8a6f;
}

.review-alert.alert-danger {
    background: #fef2f2;
    border-color: #f4d1d1;
    color: #b91c1c;
}

.review-card,
.review-table-card,
.review-mini-card {
    border: 1px solid rgba(87, 96, 108, 0.12);
    border-radius: 22px;
    box-shadow: 0 14px 34px rgba(31, 41, 55, 0.06);
    background: #fff;
}

.review-card,
.review-table-card {
    height: 100%;
}

.review-card .card-header,
.review-table-card .card-header {
    border-bottom: 0;
    background: transparent;
    padding: 1rem 1.1rem 0.35rem;
}

.review-card .card-body {
    padding: 0.9rem 1.1rem 1.15rem;
}

.review-table-card .card-body {
    padding: 0 0 1rem;
}

.review-title {
    color: #111827;
    font-size: 1rem;
    font-weight: 800;
}

.review-subtitle {
    color: #6b7280 !important;
    font-size: 0.84rem;
}

.review-status-badge,
.review-table-card .badge {
    border-radius: 999px;
    padding: 0.5rem 0.8rem;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.review-shell .bg-gradient-success {
    background: linear-gradient(135deg, #c9efd8 0%, #b7e7ca 100%) !important;
    color: #3d775b !important;
    border: 1px solid #b7e7ca;
    box-shadow: none !important;
}

.review-shell .review-status-badge.bg-gradient-success {
    background: linear-gradient(135deg, #b9e9cd 0%, #a2dec0 100%) !important;
    color: #2f6d52 !important;
    border-color: transparent;
}

.review-card .form-label,
.review-table-card .form-label {
    color: #6b7280;
    font-size: 0.76rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    margin-bottom: 0.45rem;
}

.review-card .form-control,
.review-table-card .form-control,
.review-table-card .form-select,
.review-table-card select.form-control {
    min-height: 42px;
    border-radius: 14px;
    border: 1px solid #d8dee7;
    box-shadow: none;
    padding: 0.62rem 0.85rem;
}

.review-card .form-control:focus,
.review-table-card .form-control:focus,
.review-table-card .form-select:focus,
.review-table-card select.form-control:focus {
    border-color: #1e3a5f;
    box-shadow: 0 0 0 0.18rem rgba(30, 58, 95, 0.12);
}

.review-btn-dark,
.review-table-card .btn.bg-gradient-dark {
    background: linear-gradient(135deg, #161616 0%, #2d2d2d 100%) !important;
    color: #fff !important;
    border: none !important;
    box-shadow: 0 12px 24px rgba(17, 24, 39, 0.18);
}

.review-btn-dark:hover,
.review-table-card .btn.bg-gradient-dark:hover {
    background: linear-gradient(135deg, #101010 0%, #242424 100%) !important;
    color: #fff !important;
}

.review-btn-success {
    background: #16324f !important;
    color: #fff !important;
    border: 1px solid #16324f !important;
    box-shadow: none !important;
}

.review-btn-success:hover {
    background: #10263c !important;
    border-color: #10263c !important;
    color: #fff !important;
}

.review-btn-danger {
    background: #fbe6e5 !important;
    color: #c24141 !important;
    border: 1px solid #f4c9c7 !important;
    box-shadow: none !important;
}

.review-btn-danger:hover {
    background: #f7d8d6 !important;
    color: #a93232 !important;
}

.review-btn-block {
    min-height: 42px;
    border-radius: 14px;
    font-size: 0.82rem;
    font-weight: 700;
}

.review-overview-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.85rem;
    margin-bottom: 0.9rem;
}

.review-overview-item,
.review-mini-card {
    border: 1px solid #ebeff5;
    border-radius: 16px;
    background: #f8fafc;
    padding: 0.9rem 1rem;
}

.review-overview-item strong,
.review-mini-card p {
    display: block;
    margin-bottom: 0.35rem;
    color: #6b7280;
    font-size: 0.74rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
}

.review-overview-item p,
.review-mini-card h5 {
    margin: 0;
    color: #111827;
}

.review-section-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.85rem;
}

.review-mini-card h5 {
    font-size: 1rem;
    font-weight: 800;
}

.review-table-wrap {
    padding: 0 1.2rem 1.15rem;
}

.review-table-card .table {
    margin-bottom: 0;
}

.review-table-card .table thead th {
    border-bottom: 1px solid #e8edf3;
    background: #f8fafc;
    color: #6b7280;
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    padding: 1rem 0.95rem;
    white-space: nowrap;
}

.review-table-card .table tbody td {
    padding: 1rem 0.95rem;
    border-bottom: 1px solid #eef2f7;
    color: #1f2937;
    vertical-align: middle;
}

.review-table-card .table tbody tr:last-child td {
    border-bottom: none;
}

.review-table-card .table tbody tr:hover {
    background: #fbfcfe;
}

.review-table-card .text-muted,
.review-table-card .text-sm,
.review-table-card .text-xs {
    color: #6b7280 !important;
}

.review-inline-form {
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
}

.review-task-form {
    display: flex;
    gap: 0.55rem;
    align-items: center;
}

.review-empty {
    padding: 2rem 1rem !important;
    color: #6b7280 !important;
    font-weight: 600;
}

@media (max-width: 991.98px) {
    .review-section-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 767.98px) {
    .review-overview-grid,
    .review-section-grid,
    .review-task-form {
        grid-template-columns: 1fr;
        flex-direction: column;
        align-items: stretch;
    }

    .review-task-form .btn,
    .review-inline-form .btn {
        width: 100%;
    }
}
</style>

<div class="container-fluid py-4 review-shell">
    <?php if ($message !== ''): ?>
        <div class="alert alert-success review-alert"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="alert alert-danger review-alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card review-card">
                <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0 review-title"><?= htmlspecialchars($record['candidate_name']) ?></h6>
                        <p class="text-sm mb-0 review-subtitle"><?= htmlspecialchars($record['email']) ?> • <?= htmlspecialchars($record['onboarding_code']) ?></p>
                    </div>
                    <span class="badge review-status-badge bg-gradient-<?= reviewBadge($record['status']) ?>"><?= htmlspecialchars($record['status']) ?></span>
                </div>
                <div class="card-body">
                    <div class="review-overview-grid">
                        <div class="review-overview-item"><strong>Joining Date</strong><p><?= htmlspecialchars($record['joining_date']) ?></p></div>
                        <div class="review-overview-item"><strong>Department / Role</strong><p><?= htmlspecialchars(($record['department'] ?: 'Not assigned') . ' / ' . ($record['role_title'] ?: 'Employee')) ?></p></div>
                        <div class="review-overview-item"><strong>Progress</strong><p><?= (int) $record['progress_percent'] ?>%</p></div>
                        <div class="review-overview-item"><strong>Current Step</strong><p class="text-capitalize"><?= htmlspecialchars(str_replace('_', ' ', $record['current_step'])) ?></p></div>
                    </div>
                    <div class="review-section-grid">
                        <?php
                        $labels = [
                            'personal' => 'Personal Details',
                            'contact' => 'Contact Info',
                            'bank' => 'Bank Details',
                            'statutory' => 'Statutory Details',
                            'documents' => 'Documents',
                        ];
                        foreach ($labels as $key => $label):
                        ?>
                            <div class="review-mini-card">
                                    <p><?= htmlspecialchars($label) ?></p>
                                    <span class="badge bg-gradient-<?= !empty($sections[$key]) ? 'success' : 'secondary' ?>">
                                        <?= !empty($sections[$key]) ? 'Complete' : 'Pending' ?>
                                    </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card review-card h-100">
                <div class="card-header pb-0"><h6 class="mb-0">Review Decision</h6></div>
                <div class="card-body">
                    <form method="POST" class="mb-3">
                        <div class="mb-3">
                            <label class="form-label">Comments</label>
                            <textarea class="form-control" name="review_comment" rows="4" placeholder="Add approval or rejection notes"><?= htmlspecialchars($record['review_comment'] ?? '') ?></textarea>
                        </div>
                        <button type="submit" name="review_decision" value="approve" class="btn review-btn-success review-btn-block w-100 mb-2" <?= $record['status'] === 'Active' ? 'disabled' : '' ?>>Approve & Activate</button>
                        <button type="submit" name="review_decision" value="reject" class="btn review-btn-danger review-btn-block w-100 mb-0" <?= $record['status'] === 'Active' ? 'disabled' : '' ?>>Reject With Comment</button>
                    </form>
                    <?php if ((int) $record['activated_employee_id'] > 0): ?>
                        <div class="alert alert-success review-alert mb-0">Employee record created. Internal employee row ID: <?= (int) $record['activated_employee_id'] ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card review-card h-100">
                <div class="card-header pb-0"><h6 class="mb-0">Employee Details</h6></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3"><strong>Full Name</strong><p class="mb-0"><?= htmlspecialchars($record['full_name'] ?: '-') ?></p></div>
                        <div class="col-md-6 mb-3"><strong>Date of Birth</strong><p class="mb-0"><?= htmlspecialchars($record['date_of_birth'] ?: '-') ?></p></div>
                        <div class="col-md-6 mb-3"><strong>Gender</strong><p class="mb-0"><?= htmlspecialchars($record['gender'] ?: '-') ?></p></div>
                        <div class="col-md-6 mb-3"><strong>Marital Status</strong><p class="mb-0"><?= htmlspecialchars($record['marital_status'] ?: '-') ?></p></div>
                        <div class="col-md-6 mb-3"><strong>Phone</strong><p class="mb-0"><?= htmlspecialchars($record['phone'] ?: '-') ?></p></div>
                        <div class="col-md-6 mb-3"><strong>Email</strong><p class="mb-0"><?= htmlspecialchars($record['email'] ?: '-') ?></p></div>
                        <div class="col-md-12 mb-3"><strong>Permanent Address</strong><p class="mb-0"><?= nl2br(htmlspecialchars($record['permanent_address'] ?: '-')) ?></p></div>
                        <div class="col-md-12 mb-3"><strong>Current Address</strong><p class="mb-0"><?= nl2br(htmlspecialchars($record['current_address'] ?: '-')) ?></p></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card review-card h-100">
                <div class="card-header pb-0"><h6 class="mb-0">Payroll & Statutory</h6></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3"><strong>Bank Name</strong><p class="mb-0"><?= htmlspecialchars($record['bank_name'] ?: '-') ?></p></div>
                        <div class="col-md-6 mb-3"><strong>Account Number</strong><p class="mb-0"><?= htmlspecialchars($record['bank_account_number'] ?: '-') ?></p></div>
                        <div class="col-md-6 mb-3"><strong>IFSC Code</strong><p class="mb-0"><?= htmlspecialchars($record['bank_ifsc_code'] ?: '-') ?></p></div>
                        <div class="col-md-6 mb-3"><strong>PAN</strong><p class="mb-0"><?= htmlspecialchars($record['pan_number'] ?: '-') ?></p></div>
                        <div class="col-md-6 mb-3"><strong>Aadhaar</strong><p class="mb-0"><?= htmlspecialchars($record['aadhaar_number'] ?: '-') ?></p></div>
                        <div class="col-md-6 mb-3"><strong>UAN</strong><p class="mb-0"><?= htmlspecialchars($record['uan_number'] ?: '-') ?></p></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card review-table-card mb-4">
        <div class="card-header pb-0"><h6 class="mb-0">Document Verification</h6></div>
        <div class="card-body px-0 pt-0 pb-2">
            <div class="table-responsive review-table-wrap">
                <table class="table align-items-center mb-0">
                    <thead>
                        <tr>
                            <th>Document</th>
                            <th>Upload Status</th>
                            <th>Verification</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents as $document): ?>
                            <tr>
                                <td class="px-3">
                                    <strong><?= htmlspecialchars($document['document_label']) ?></strong>
                                    <?php if (!empty($document['file_path'])): ?>
                                        <p class="mb-0"><a href="../<?= htmlspecialchars($document['file_path']) ?>" target="_blank">View file</a></p>
                                    <?php else: ?>
                                        <p class="mb-0 text-muted">Not uploaded yet</p>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-gradient-<?= $document['upload_status'] === 'Uploaded' ? 'success' : 'secondary' ?>"><?= htmlspecialchars($document['upload_status']) ?></span></td>
                                <td><span class="badge bg-gradient-<?= reviewBadge($document['verification_status']) ?>"><?= htmlspecialchars($document['verification_status']) ?></span></td>
                                <td class="px-3">
                                    <form method="POST" class="review-inline-form">
                                        <input type="hidden" name="document_id" value="<?= (int) $document['id'] ?>">
                                        <select name="verification_status" class="form-control form-control-sm">
                                            <?php foreach (['Pending', 'Verified', 'Rejected'] as $status): ?>
                                                <option value="<?= $status ?>" <?= $document['verification_status'] === $status ? 'selected' : '' ?>><?= $status ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="text" name="verification_comment" class="form-control form-control-sm" placeholder="Comment" value="<?= htmlspecialchars($document['verification_comment'] ?? '') ?>">
                                        <button type="submit" name="update_document" class="btn btn-sm bg-gradient-dark mb-0">Save</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card review-table-card">
        <div class="card-header pb-0"><h6 class="mb-0">Task Assignment System</h6></div>
        <div class="card-body px-0 pt-0 pb-2">
            <div class="table-responsive review-table-wrap">
                <table class="table align-items-center mb-0">
                    <thead>
                        <tr>
                            <th>Owner</th>
                            <th>Task</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasks as $task): ?>
                            <tr>
                                <td class="px-3"><span class="badge bg-gradient-dark"><?= htmlspecialchars($task['owner_type']) ?></span></td>
                                <td class="px-3">
                                    <strong><?= htmlspecialchars($task['title']) ?></strong>
                                    <p class="mb-0 text-sm"><?= htmlspecialchars($task['description']) ?></p>
                                </td>
                                <td><?= htmlspecialchars($task['due_date'] ?: '-') ?></td>
                                <td><span class="badge bg-gradient-<?= reviewBadge($task['status']) ?>"><?= htmlspecialchars($task['status']) ?></span></td>
                                <td class="px-3">
                                    <form method="POST" class="review-task-form">
                                        <input type="hidden" name="task_id" value="<?= (int) $task['id'] ?>">
                                        <select name="task_status" class="form-control form-control-sm">
                                            <?php foreach (['Pending', 'In Progress', 'Completed'] as $status): ?>
                                                <option value="<?= $status ?>" <?= $task['status'] === $status ? 'selected' : '' ?>><?= $status ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" name="update_task" class="btn btn-sm bg-gradient-dark mb-0">Update</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include("footer.php"); ?>
