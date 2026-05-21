<?php
require_once 'session_check.php';
require 'db_connection.php';
require_once '../includes/advance_salary_request_helper.php';

if (!isset($_SESSION['employee_id'])) {
    echo "<div class='alert alert-danger'>You must be logged in to apply for advance salary.</div>";
    exit;
}

ensureAdvanceSalaryRequestTable($conn);

$employeeId = (int) $_SESSION['employee_id'];
$message = $_SESSION['advance_salary_flash_message'] ?? '';
$messageType = $_SESSION['advance_salary_flash_type'] ?? 'success';
unset($_SESSION['advance_salary_flash_message'], $_SESSION['advance_salary_flash_type']);
$selectedYear = (int) ($_POST['request_year'] ?? date('Y'));
$selectedMonth = (int) ($_POST['request_month'] ?? date('n'));
$amountValue = isset($_POST['amount']) ? (float) $_POST['amount'] : 0;
$reasonValue = trim($_POST['reason'] ?? '');
$currentMonthStart = date('Y-m-01');
$selfRedirectPath = parse_url($_SERVER['REQUEST_URI'] ?? 'apply_advance_salary', PHP_URL_PATH) ?: 'apply_advance_salary';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestYear = (int) ($_POST['request_year'] ?? 0);
    $requestMonth = (int) ($_POST['request_month'] ?? 0);
    $amount = round((float) ($_POST['amount'] ?? 0), 2);
    $reason = trim($_POST['reason'] ?? '');
    $requestMonthStart = sprintf('%04d-%02d-01', $requestYear, $requestMonth);

    if ($requestYear < 2000 || $requestMonth < 1 || $requestMonth > 12) {
        $message = "Please select a valid salary month.";
        $messageType = 'danger';
    } elseif ($amount <= 0) {
        $message = "Advance amount must be greater than zero.";
        $messageType = 'danger';
    } elseif ($reason === '') {
        $message = "Reason is required.";
        $messageType = 'danger';
    } elseif ($requestMonthStart < $currentMonthStart) {
        $message = "You can only apply for the current month or a future month.";
        $messageType = 'danger';
    } elseif (advanceSalaryPayrollExists($conn, $employeeId, $requestYear, $requestMonth)) {
        $message = "Payroll is already generated for the selected month.";
        $messageType = 'warning';
    } else {
        $existingStmt = $conn->prepare("
            SELECT status
            FROM advance_salary_requests
            WHERE employee_id = ?
              AND request_year = ?
              AND request_month = ?
              AND request_type = 'monthly'
              AND status IN ('Pending', 'Approved')
            LIMIT 1
        ");
        $existingStmt->bind_param("iii", $employeeId, $requestYear, $requestMonth);
        $existingStmt->execute();
        $existing = $existingStmt->get_result()->fetch_assoc();
        $existingStmt->close();

        if ($existing) {
            $message = "An advance request already exists for the selected month.";
            $messageType = 'warning';
        } else {
            $appliedAt = date('Y-m-d H:i:s');
            $insertStmt = $conn->prepare("
                INSERT INTO advance_salary_requests (
                    employee_id, request_year, request_month, request_type, amount, reason, status, applied_at
                ) VALUES (?, ?, ?, 'monthly', ?, ?, 'Pending', ?)
            ");
            $insertStmt->bind_param("iiidss", $employeeId, $requestYear, $requestMonth, $amount, $reason, $appliedAt);

            if ($insertStmt->execute()) {
                $_SESSION['advance_salary_flash_message'] = "Advance salary request submitted successfully.";
                $_SESSION['advance_salary_flash_type'] = 'success';
                header("Location: " . $selfRedirectPath, true, 303);
                exit;
            } else {
                $message = "Failed to submit the request. Please try again.";
                $messageType = 'danger';
            }
            $insertStmt->close();
        }
    }
}

$requestsStmt = $conn->prepare("
    SELECT *
    FROM advance_salary_requests
    WHERE employee_id = ?
      AND request_type = 'monthly'
    ORDER BY request_year DESC, request_month DESC, id DESC
");
$requestsStmt->bind_param("i", $employeeId);
$requestsStmt->execute();
$requests = $requestsStmt->get_result();

include("header.php");
?>

<style>
    .advance-salary-page {
        padding-top: 0.95rem !important;
        padding-bottom: 1.2rem !important;
    }

    .advance-salary-alert {
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 18px;
        box-shadow: 0 16px 34px rgba(15, 23, 42, 0.06);
    }

    .advance-salary-card {
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 28px;
        background: linear-gradient(180deg, #f8fbff 0%, #eef4fb 100%);
        box-shadow: 0 24px 56px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .advance-salary-card .card-header {
        padding: 1.25rem 1.35rem 0;
        border: 0;
        background: transparent;
    }

    .advance-salary-card .card-body {
        padding: 1.2rem 1.35rem 1.35rem;
        background: transparent;
    }

    .advance-salary-title {
        margin: 0;
        color: #0f172a;
        font-size: 1.08rem;
        font-weight: 800;
        letter-spacing: -0.02em;
    }

    .advance-salary-subtitle {
        margin: 0.45rem 0 0;
        color: #64748b;
        font-size: 0.9rem;
        line-height: 1.6;
    }

    .advance-salary-form-shell,
    .advance-salary-table-shell {
        border-radius: 22px;
        background: rgba(255, 255, 255, 0.88);
    }

    .advance-salary-form-shell {
        padding: 1.15rem;
    }

    .advance-salary-form .row {
        --bs-gutter-x: 0.9rem;
        --bs-gutter-y: 0.2rem;
    }

    .advance-salary-form .form-label {
        margin-bottom: 0.5rem;
        color: #475569;
        font-size: 0.74rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .advance-salary-form .form-control {
        min-height: 46px;
        border-radius: 15px;
        border: 1px solid #d9e2ec;
        box-shadow: none;
        padding: 0.78rem 0.9rem;
    }

    .advance-salary-form .form-control:focus {
        border-color: #94a3b8;
        box-shadow: 0 0 0 4px rgba(148, 163, 184, 0.16);
    }

    .advance-salary-form textarea.form-control {
        min-height: 122px;
        resize: vertical;
    }

    .advance-salary-actions {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        flex-wrap: wrap;
        margin-top: 0.35rem;
    }

    .advance-salary-btn {
        min-height: 46px;
        padding: 0.72rem 1.2rem;
        border-radius: 15px;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        box-shadow: none !important;
    }

    .advance-salary-btn-primary {
        border: 0;
        background: linear-gradient(135deg, #123b76 0%, #1f4c8f 100%) !important;
        color: #ffffff !important;
    }

    .advance-salary-btn-secondary {
        border: 1px solid #0f172a;
        background: #0f172a;
        color: #ffffff !important;
    }

    .advance-salary-btn-secondary:hover,
    .advance-salary-btn-secondary:focus {
        border-color: #111827;
        background: #111827;
        color: #ffffff !important;
    }

    .advance-salary-history-title {
        margin: 0;
        color: #0f172a;
        font-size: 1rem;
        font-weight: 800;
        letter-spacing: -0.01em;
    }

    .advance-salary-table-wrap {
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
    }

    .advance-salary-table {
        margin-bottom: 0;
        min-width: 920px;
    }

    .advance-salary-table thead th {
        padding: 1rem;
        border-bottom: 1px solid #eef2f7;
        background: #f8fafc;
        color: #64748b;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .advance-salary-table tbody td {
        padding: 1rem;
        border-bottom: 1px solid #eef2f7;
        vertical-align: top;
        color: #1f2937;
        font-size: 0.88rem;
    }

    .advance-salary-table tbody tr:hover {
        background: #fbfdff;
    }

    .advance-salary-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .advance-salary-table .badge {
        border-radius: 999px;
        padding: 0.5rem 0.82rem;
        font-size: 0.67rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        box-shadow: none;
    }

    .advance-salary-table .bg-gradient-success {
        background: #ecfdf3 !important;
        color: #15803d !important;
        border: 1px solid #bbf7d0;
    }

    .advance-salary-table .bg-gradient-danger {
        background: #fff1f2 !important;
        color: #dc2626 !important;
        border: 1px solid #fecdd3;
    }

    .advance-salary-table .bg-gradient-warning {
        background: #fff7db !important;
        color: #b45309 !important;
        border: 1px solid #f8df9c;
    }

    .advance-salary-note-success {
        color: #15803d !important;
        font-weight: 700;
    }

    .advance-salary-note-muted {
        color: #64748b !important;
        font-weight: 600;
    }

    .advance-salary-note-danger {
        color: #dc2626 !important;
        line-height: 1.55;
    }

    .advance-salary-empty {
        color: #64748b;
        font-weight: 600;
    }

    @media (max-width: 767.98px) {
        .advance-salary-page {
            padding-top: 0.6rem !important;
            padding-left: 0.3rem !important;
            padding-right: 0.3rem !important;
            padding-bottom: 0.85rem !important;
        }

        .advance-salary-card {
            border-radius: 22px;
        }

        .advance-salary-card .card-header {
            padding: 1rem 1rem 0;
        }

        .advance-salary-card .card-body {
            padding: 0.95rem 1rem 1rem;
        }

        .advance-salary-title,
        .advance-salary-history-title {
            font-size: 0.98rem;
            line-height: 1.24;
        }

        .advance-salary-subtitle {
            font-size: 0.82rem;
            line-height: 1.5;
        }

        .advance-salary-form-shell {
            padding: 0.95rem;
        }

        .advance-salary-form .form-control,
        .advance-salary-btn {
            min-height: 38px;
            border-radius: 12px;
            font-size: 0.66rem;
        }

        .advance-salary-actions {
            gap: 0.4rem;
            flex-wrap: nowrap;
        }

        .advance-salary-actions .advance-salary-btn {
            flex: 1 1 0;
            min-width: 0;
            padding-left: 0.58rem;
            padding-right: 0.58rem;
            letter-spacing: 0.03em;
        }

        .advance-salary-table thead th,
        .advance-salary-table tbody td {
            padding: 0.82rem 0.78rem;
        }
    }
</style>

<div class="container-fluid py-4 advance-salary-page">
    <?php if ($message !== ''): ?>
        <div class="alert alert-<?= htmlspecialchars($messageType) ?> advance-salary-alert"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12 mb-4">
            <div class="card advance-salary-card">
                <div class="card-header pb-0">
                    <h6 class="advance-salary-title">Apply Monthly Advance Salary</h6>
                </div>
                <div class="card-body">
                    <div class="advance-salary-form-shell">
                    <form method="POST" class="advance-salary-form">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="request_year">Year</label>
                                <select class="form-control" name="request_year" id="request_year" required>
                                    <?php for ($year = date('Y'); $year <= date('Y') + 1; $year++): ?>
                                        <option value="<?= $year ?>" <?= $selectedYear === $year ? 'selected' : '' ?>><?= $year ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="request_month">Month</label>
                                <select class="form-control" name="request_month" id="request_month" required>
                                    <?php for ($month = 1; $month <= 12; $month++): ?>
                                        <option value="<?= $month ?>" <?= $selectedMonth === $month ? 'selected' : '' ?>>
                                            <?= date('F', mktime(0, 0, 0, $month, 1)) ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="amount">Advance Amount</label>
                                <input type="number" class="form-control" name="amount" id="amount" min="0.01" step="0.01" value="<?= htmlspecialchars((string) $amountValue) ?>" required>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label" for="reason">Reason</label>
                                <textarea class="form-control" name="reason" id="reason" rows="4" required><?= htmlspecialchars($reasonValue) ?></textarea>
                            </div>
                            <div class="col-12">
                                <div class="advance-salary-actions">
                                <button type="submit" class="btn mb-0 advance-salary-btn advance-salary-btn-primary" id="submitAdvanceRequestBtn">Submit Request</button>
                                <a href="manage_salary" class="btn mb-0 advance-salary-btn advance-salary-btn-secondary">Back to Salary</a>
                                </div>
                            </div>
                        </div>
                    </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card mb-4 advance-salary-card">
                <div class="card-header pb-0">
                    <h6 class="advance-salary-history-title">My Advance Requests</h6>
                </div>
                <div class="card-body px-0 pt-0 pb-2 advance-salary-table-shell">
                    <div class="table-responsive p-0 advance-salary-table-wrap">
                        <table class="table align-items-center mb-0 advance-salary-table">
                            <thead>
                                <tr>
                                    <th>Payroll Month</th>
                                    <th>Amount</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Applied</th>
                                    <th>Decision</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($requests->num_rows > 0): ?>
                                    <?php while ($row = $requests->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars(date('F', mktime(0, 0, 0, (int) $row['request_month'], 1)) . ' ' . $row['request_year']) ?></td>
                                            <td><?= number_format((float) $row['amount'], 2) ?></td>
                                            <td class="text-wrap" style="max-width: 280px;"><?= nl2br(htmlspecialchars($row['reason'])) ?></td>
                                            <td>
                                                <?php if ($row['status'] === 'Approved'): ?>
                                                    <span class="badge bg-gradient-success">Approved</span>
                                                <?php elseif ($row['status'] === 'Rejected'): ?>
                                                    <span class="badge bg-gradient-danger">Rejected</span>
                                                <?php else: ?>
                                                    <span class="badge bg-gradient-warning">Pending</span>
                                                <?php endif; ?>
                                                <?php if (!empty($row['payroll_applied_at'])): ?>
                                                    <div class="text-xs mt-2 text-success advance-salary-note-success">Added to payroll</div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($row['applied_at']) ?></td>
                                            <td>
                                                <?php if (!empty($row['approved_rejected_at'])): ?>
                                                    <div class="text-xs"><?= htmlspecialchars($row['approved_rejected_at']) ?></div>
                                                    <div class="text-xs"><?= htmlspecialchars($row['approved_by_name'] ?? '') ?></div>
                                                <?php else: ?>
                                                    <span class="text-xs text-muted advance-salary-note-muted">Waiting</span>
                                                <?php endif; ?>
                                                <?php if (!empty($row['reject_reason'])): ?>
                                                    <div class="text-xs text-danger mt-2 advance-salary-note-danger"><?= nl2br(htmlspecialchars($row['reject_reason'])) ?></div>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center advance-salary-empty">No advance salary requests found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form[method="POST"]');
    const submitButton = document.getElementById('submitAdvanceRequestBtn');

    if (!form || !submitButton) {
        return;
    }

    form.addEventListener('submit', function () {
        submitButton.disabled = true;
        submitButton.textContent = 'Submitting...';
    });
});
</script>

<?php
$requestsStmt->close();
include("footer.php");
?>
