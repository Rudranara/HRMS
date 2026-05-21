<?php
include("header.php");
require_once '../includes/onboarding_helper.php';

onboardingEnsureSchema($conn);

$success = '';
$error = '';
$inviteMeta = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_onboarding'])) {
    try {
        $candidateName = trim($_POST['candidate_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $joiningDate = trim($_POST['joining_date'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $roleTitle = trim($_POST['role_title'] ?? '');

        if ($candidateName === '' || $email === '' || $joiningDate === '') {
            throw new RuntimeException('Name, email, and joining date are required.');
        }

        $inviteMeta = onboardingCreateInvitation($conn, [
            'candidate_name' => $candidateName,
            'email' => $email,
            'joining_date' => $joiningDate,
            'department' => $department,
            'role_title' => $roleTitle,
        ], [
            'admin_id' => $_SESSION['admin_id'] ?? 0,
            'admin_name' => $_SESSION['admin_name'] ?? 'Admin',
        ]);

        $success = 'Onboarding invitation created successfully.';
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$records = $conn->query("SELECT * FROM onboarding_records ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);

function onboardingBadgeClass(string $status): string
{
    return match ($status) {
        'Invited' => 'secondary',
        'In Progress' => 'info',
        'Submitted' => 'warning',
        'Approved' => 'success',
        'Active' => 'success',
        'Rejected' => 'danger',
        default => 'dark',
    };
}
?>

<style>
.onboarding-shell {
    padding-bottom: 1.5rem;
}

.onboarding-alert {
    border: 1px solid transparent;
    border-radius: 18px;
    box-shadow: 0 12px 28px rgba(31, 41, 55, 0.06);
    padding: 1rem 1.1rem;
}

.onboarding-alert.alert-success {
    background: #ecfdf3;
    border-color: #ccebd9;
    color: #166534;
}

.onboarding-alert.alert-danger {
    background: #fef2f2;
    border-color: #f4d1d1;
    color: #b91c1c;
}

.onboarding-card,
.onboarding-table-card,
.onboarding-meta-card,
.onboarding-mini-card {
    border: 1px solid rgba(87, 96, 108, 0.12);
    border-radius: 22px;
    box-shadow: 0 14px 34px rgba(31, 41, 55, 0.06);
    background: #fff;
}

.onboarding-card,
.onboarding-table-card,
.onboarding-meta-card {
    height: 100%;
}

.onboarding-card .card-header,
.onboarding-table-card .card-header,
.onboarding-meta-card .card-header {
    border-bottom: 0;
    background: transparent;
    padding: 1rem 1.1rem 0.35rem;
}

.onboarding-card .card-body,
.onboarding-meta-card .card-body {
    padding: 0.85rem 1.1rem 1.1rem;
}

.onboarding-table-card .card-body {
    padding: 0 0 1rem;
}

.onboarding-card h6,
.onboarding-table-card h6,
.onboarding-meta-card h6 {
    color: #111827;
    font-size: 1rem;
    font-weight: 800;
}

.onboarding-card .text-sm,
.onboarding-table-card .text-sm,
.onboarding-meta-card .text-sm {
    color: #6b7280 !important;
}

.onboarding-card .form-label {
    color: #6b7280;
    font-size: 0.76rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    margin-bottom: 0.45rem;
}

.onboarding-card .form-control {
    min-height: 46px;
    border-radius: 14px;
    border: 1px solid #d8dee7;
    box-shadow: none;
    padding: 0.68rem 0.95rem;
}

.onboarding-card .form-control:focus {
    border-color: #1e3a5f;
    box-shadow: 0 0 0 0.2rem rgba(30, 58, 95, 0.12);
}

.onboarding-btn-primary,
.onboarding-table-card .btn.bg-gradient-dark {
    background: linear-gradient(135deg, #161616 0%, #2d2d2d 100%) !important;
    color: #fff !important;
    border: none !important;
    box-shadow: 0 12px 24px rgba(17, 24, 39, 0.18);
}

.onboarding-btn-primary,
.onboarding-table-card .btn.bg-gradient-dark,
.onboarding-table-card .btn.btn-sm.bg-gradient-dark {
    min-height: 38px;
    padding: 0.52rem 0.95rem;
    border-radius: 14px;
    font-size: 0.8rem;
    font-weight: 700;
}

.onboarding-btn-primary:hover,
.onboarding-table-card .btn.bg-gradient-dark:hover {
    background: linear-gradient(135deg, #101010 0%, #242424 100%) !important;
    color: #fff !important;
}

.onboarding-meta-card {
    overflow: hidden;
}

.onboarding-invite-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.8rem;
}

.onboarding-meta-item {
    border: 1px solid #eef2f7;
    border-radius: 16px;
    background: #fff;
    padding: 0.85rem 0.95rem;
}

.onboarding-meta-item strong {
    display: block;
    color: #6b7280;
    font-size: 0.72rem;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    margin-bottom: 0.35rem;
}

.onboarding-meta-item p,
.onboarding-meta-item a {
    margin: 0;
    color: #111827;
    font-size: 0.92rem;
    word-break: break-word;
}

.onboarding-status-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.8rem;
}

.onboarding-mini-card {
    padding: 0.9rem 1rem;
    background: #f8fafc;
    border: 1px solid #ebeff5;
    box-shadow: none;
}

.onboarding-mini-card p {
    margin: 0 0 0.35rem;
    color: #6b7280;
    font-size: 0.76rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
}

.onboarding-mini-card h5 {
    margin: 0;
    color: #111827;
    font-size: 1.2rem;
    font-weight: 800;
}

.onboarding-table-wrap {
    padding: 0 1.2rem 1.15rem;
}

.onboarding-table-card .table {
    margin-bottom: 0;
}

.onboarding-table-card .table thead th {
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

.onboarding-table-card .table tbody td {
    padding: 1rem 0.95rem;
    border-bottom: 1px solid #eef2f7;
    color: #1f2937;
    vertical-align: middle;
}

.onboarding-table-card .table tbody tr:last-child td {
    border-bottom: none;
}

.onboarding-table-card .table tbody tr:hover {
    background: #fbfcfe;
}

.onboarding-record-name {
    color: #111827;
    font-weight: 700;
}

.onboarding-record-meta {
    color: #6b7280 !important;
}

.onboarding-table-card .badge {
    border-radius: 999px;
    padding: 0.5rem 0.8rem;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.onboarding-table-card .bg-gradient-success {
    background: linear-gradient(135deg, #b9e9cd 0%, #a2dec0 100%) !important;
    color: #2f6d52 !important;
    border: 1px solid #a2dec0;
    box-shadow: none !important;
}

.onboarding-progress-wrap {
    min-width: 160px;
}

.onboarding-progress-wrap .progress {
    height: 8px;
    background: #e8edf3;
    border-radius: 999px;
    overflow: hidden;
}

.onboarding-progress-wrap small {
    display: inline-block;
    margin-top: 0.45rem;
    color: #6b7280;
    font-weight: 700;
}

.onboarding-empty {
    padding: 2rem 1rem !important;
    color: #6b7280 !important;
    font-weight: 600;
}

@media (max-width: 991.98px) {
    .onboarding-status-grid,
    .onboarding-invite-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 767.98px) {
    .onboarding-status-grid,
    .onboarding-invite-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="container-fluid py-4 onboarding-shell">
    <?php if ($success !== ''): ?>
        <div class="alert alert-success onboarding-alert"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="alert alert-danger onboarding-alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($inviteMeta): ?>
        <div class="card onboarding-meta-card mb-4">
            <div class="card-body">
                <h6 class="mb-3">Invitation Details</h6>
                <div class="onboarding-invite-grid">
                    <div class="onboarding-meta-item">
                        <strong>Onboarding ID</strong>
                        <p><?= htmlspecialchars($inviteMeta['onboarding_code']) ?></p>
                    </div>
                    <div class="onboarding-meta-item">
                        <strong>Temporary Password</strong>
                        <p><?= htmlspecialchars($inviteMeta['temp_password']) ?></p>
                    </div>
                    <div class="onboarding-meta-item">
                        <strong>Secure Link</strong>
                        <a href="<?= htmlspecialchars($inviteMeta['portal_url']) ?>" target="_blank"><?= htmlspecialchars($inviteMeta['portal_url']) ?></a>
                    </div>
                    <div class="onboarding-meta-item">
                        <strong>Email Delivery</strong>
                        <p><?= $inviteMeta['email_sent'] ? 'Sent' : 'Not sent from server, but link and password are ready to share manually.' ?></p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-5 mb-4">
            <div class="card onboarding-card h-100">
                <div class="card-header pb-0">
                    <h6 class="mb-0">Create Onboarding Invitation</h6>
                    <p class="text-sm mb-0">Pre-onboarding starts right after the candidate is selected.</p>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="candidate_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Joining Date</label>
                            <input type="date" class="form-control" name="joining_date" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Department</label>
                            <input type="text" class="form-control" name="department" placeholder="Example: HR, Finance, Operations">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <input type="text" class="form-control" name="role_title" placeholder="Example: Executive, Manager, Developer">
                        </div>
                        <button type="submit" name="create_onboarding" class="btn onboarding-btn-primary mb-0">Create Invitation</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7 mb-4">
            <div class="card onboarding-card h-100">
                <div class="card-header pb-0">
                    <h6 class="mb-0">Flow Status</h6>
                    <p class="text-sm mb-0">Invited → In Progress → Submitted → Approved → Active</p>
                </div>
                <div class="card-body">
                    <div class="onboarding-status-grid">
                        <?php
                        $statusCounts = ['Invited' => 0, 'In Progress' => 0, 'Submitted' => 0, 'Approved' => 0, 'Active' => 0, 'Rejected' => 0];
                        foreach ($records as $record) {
                            if (isset($statusCounts[$record['status']])) {
                                $statusCounts[$record['status']]++;
                            }
                        }
                        foreach ($statusCounts as $label => $count):
                        ?>
                            <div class="onboarding-mini-card">
                                    <p><?= htmlspecialchars($label) ?></p>
                                    <h5 class="mb-0"><?= (int) $count ?></h5>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card onboarding-table-card">
        <div class="card-header pb-0">
            <h6 class="mb-0">Onboarding Records</h6>
        </div>
        <div class="card-body px-0 pt-0 pb-2">
            <div class="table-responsive onboarding-table-wrap">
                <table class="table align-items-center mb-0">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Joining</th>
                            <th>Department / Role</th>
                            <th>Status</th>
                            <th>Progress</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$records): ?>
                            <tr>
                                <td colspan="6" class="text-center onboarding-empty">No onboarding invitations created yet.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($records as $record): ?>
                            <tr>
                                <td>
                                    <div class="px-3 py-2">
                                        <h6 class="mb-0 text-sm onboarding-record-name"><?= htmlspecialchars($record['candidate_name']) ?></h6>
                                        <p class="text-xs text-secondary mb-0 onboarding-record-meta"><?= htmlspecialchars($record['email']) ?><br><?= htmlspecialchars($record['onboarding_code']) ?></p>
                                    </div>
                                </td>
                                <td><p class="text-sm mb-0 px-3"><?= htmlspecialchars($record['joining_date']) ?></p></td>
                                <td><p class="text-sm mb-0 px-3"><?= htmlspecialchars(($record['department'] ?: 'No department') . ' / ' . ($record['role_title'] ?: 'No role')) ?></p></td>
                                <td><span class="badge bg-gradient-<?= onboardingBadgeClass($record['status']) ?>"><?= htmlspecialchars($record['status']) ?></span></td>
                                <td>
                                    <div class="px-3 onboarding-progress-wrap">
                                        <div class="progress">
                                            <div class="progress-bar bg-gradient-info" role="progressbar" style="width: <?= (int) $record['progress_percent'] ?>%"></div>
                                        </div>
                                        <small><?= (int) $record['progress_percent'] ?>%</small>
                                    </div>
                                </td>
                                <td class="px-3">
                                    <a href="onboarding_review?id=<?= (int) $record['id'] ?>" class="btn btn-sm bg-gradient-dark mb-0">Review</a>
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
