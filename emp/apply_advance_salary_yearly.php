<?php
require_once 'session_check.php';
require 'db_connection.php';
require_once '../includes/advance_salary_request_helper.php';

if (!isset($_SESSION['employee_id'])) {
    echo "<div class='alert alert-danger'>You must be logged in to apply for yearly advance salary.</div>";
    exit;
}

ensureAdvanceSalaryRequestTable($conn);

$employeeId = (int) $_SESSION['employee_id'];
$message = $_SESSION['advance_salary_yearly_flash_message'] ?? '';
$messageType = $_SESSION['advance_salary_yearly_flash_type'] ?? 'success';
unset($_SESSION['advance_salary_yearly_flash_message'], $_SESSION['advance_salary_yearly_flash_type']);

$selectedYear = (int) ($_POST['request_year'] ?? date('Y'));
$amountValue = isset($_POST['amount']) ? (float) $_POST['amount'] : 0;
$reasonValue = trim($_POST['reason'] ?? '');
$selfRedirectPath = parse_url($_SERVER['REQUEST_URI'] ?? 'apply_advance_salary_yearly', PHP_URL_PATH) ?: 'apply_advance_salary_yearly';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestYear = (int) ($_POST['request_year'] ?? 0);
    $amount = round((float) ($_POST['amount'] ?? 0), 2);
    $reason = trim($_POST['reason'] ?? '');

    if ($requestYear < (int) date('Y') || $requestYear > ((int) date('Y') + 1)) {
        $message = "Please select a valid year.";
        $messageType = 'danger';
    } elseif ($amount <= 0) {
        $message = "Advance amount must be greater than zero.";
        $messageType = 'danger';
    } elseif ($reason === '') {
        $message = "Reason is required.";
        $messageType = 'danger';
    } else {
        $existingStmt = $conn->prepare("
            SELECT id
            FROM advance_salary_requests
            WHERE employee_id = ?
              AND request_year = ?
              AND request_type = 'yearly'
              AND status IN ('Pending', 'Approved')
            LIMIT 1
        ");
        $existingStmt->bind_param("ii", $employeeId, $requestYear);
        $existingStmt->execute();
        $existing = $existingStmt->get_result()->fetch_assoc();
        $existingStmt->close();

        if ($existing) {
            $message = "A yearly advance request already exists for the selected year.";
            $messageType = 'warning';
        } else {
            $appliedAt = date('Y-m-d H:i:s');
            $requestMonth = 0;
            $requestType = 'yearly';
            $insertStmt = $conn->prepare("
                INSERT INTO advance_salary_requests (
                    employee_id, request_year, request_month, request_type, amount, reason, status, applied_at
                ) VALUES (?, ?, ?, ?, ?, ?, 'Pending', ?)
            ");
            $insertStmt->bind_param("iiisdss", $employeeId, $requestYear, $requestMonth, $requestType, $amount, $reason, $appliedAt);

            if ($insertStmt->execute()) {
                $_SESSION['advance_salary_yearly_flash_message'] = "Yearly advance salary request submitted successfully.";
                $_SESSION['advance_salary_yearly_flash_type'] = 'success';
                header("Location: " . $selfRedirectPath, true, 303);
                exit;
            }

            $message = "Failed to submit the request. Please try again.";
            $messageType = 'danger';
            $insertStmt->close();
        }
    }
}

$requestsStmt = $conn->prepare("
    SELECT
        r.*,
        COALESCE(alloc.allocated_amount, 0) AS allocated_amount
    FROM advance_salary_requests r
    LEFT JOIN (
        SELECT request_id, SUM(amount) AS allocated_amount
        FROM advance_salary_request_allocations
        GROUP BY request_id
    ) alloc ON alloc.request_id = r.id
    WHERE r.employee_id = ?
      AND r.request_type = 'yearly'
    ORDER BY r.request_year DESC, r.id DESC
");
$requestsStmt->bind_param("i", $employeeId);
$requestsStmt->execute();
$requests = $requestsStmt->get_result();

include("header.php");
?>

<style>
    .yearly-advance-page {
        padding-top: 0.95rem !important;
        padding-bottom: 1.2rem !important;
    }

    .yearly-advance-alert {
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 18px;
        box-shadow: 0 16px 34px rgba(15, 23, 42, 0.06);
    }

    .yearly-advance-card {
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 28px;
        background: linear-gradient(180deg, #f8fbff 0%, #eef4fb 100%);
        box-shadow: 0 24px 56px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .yearly-advance-card .card-header {
        padding: 1.25rem 1.35rem 0;
        border: 0;
        background: transparent;
    }

    .yearly-advance-card .card-body {
        padding: 1.2rem 1.35rem 1.35rem;
        background: transparent;
    }

    .yearly-advance-title,
    .yearly-advance-history-title {
        margin: 0;
        color: #0f172a;
        font-size: 1.08rem;
        font-weight: 800;
        letter-spacing: -0.02em;
    }

    .yearly-advance-note {
        color: #64748b;
        font-size: 0.9rem;
        line-height: 1.6;
    }

    .yearly-advance-intro {
        margin: 0.45rem 0 0;
    }

    .yearly-advance-form-shell,
    .yearly-advance-table-shell {
        border-radius: 22px;
        background: rgba(255, 255, 255, 0.88);
    }

    .yearly-advance-form-shell {
        padding: 1.15rem;
    }

    .yearly-advance-form .row {
        --bs-gutter-x: 0.9rem;
        --bs-gutter-y: 0.2rem;
    }

    .yearly-advance-form .form-label {
        margin-bottom: 0.5rem;
        color: #475569;
        font-size: 0.74rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .yearly-advance-form .form-control {
        min-height: 46px;
        border-radius: 15px;
        border: 1px solid #d9e2ec;
        box-shadow: none;
        padding: 0.78rem 0.9rem;
    }

    .yearly-advance-form .form-control:focus {
        border-color: #94a3b8;
        box-shadow: 0 0 0 4px rgba(148, 163, 184, 0.16);
    }

    .yearly-advance-form textarea.form-control {
        min-height: 132px;
        resize: vertical;
    }

    .yearly-advance-actions {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        flex-wrap: wrap;
        margin-top: 1rem;
    }

    .yearly-advance-btn {
        min-height: 46px;
        padding: 0.72rem 1.2rem;
        border-radius: 15px;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        box-shadow: none !important;
        border: 0;
        background: linear-gradient(135deg, #123b76 0%, #1f4c8f 100%) !important;
        color: #ffffff !important;
    }

    .yearly-advance-table-wrap {
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
    }

    .yearly-advance-table {
        margin-bottom: 0;
        min-width: 1040px;
    }

    .yearly-advance-table thead th {
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

    .yearly-advance-table tbody td {
        padding: 1rem;
        border-bottom: 1px solid #eef2f7;
        vertical-align: top;
        color: #1f2937;
        font-size: 0.88rem;
    }

    .yearly-advance-table tbody tr:hover {
        background: #fbfdff;
    }

    .yearly-advance-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .yearly-advance-table .badge {
        border-radius: 999px;
        padding: 0.5rem 0.82rem;
        font-size: 0.67rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        box-shadow: none;
    }

    .yearly-advance-table .bg-gradient-success {
        background: #ecfdf3 !important;
        color: #15803d !important;
        border: 1px solid #bbf7d0;
    }

    .yearly-advance-table .bg-gradient-danger {
        background: #fff1f2 !important;
        color: #dc2626 !important;
        border: 1px solid #fecdd3;
    }

    .yearly-advance-table .bg-gradient-warning {
        background: #fff7db !important;
        color: #b45309 !important;
        border: 1px solid #f8df9c;
    }

    .yearly-advance-waiting {
        color: #64748b !important;
        font-weight: 600;
    }

    .yearly-advance-reject {
        color: #dc2626 !important;
        line-height: 1.55;
    }

    .yearly-advance-empty {
        color: #64748b;
        font-weight: 600;
    }

    @media (max-width: 767.98px) {
        .yearly-advance-page {
            padding-top: 0.6rem !important;
            padding-left: 0.3rem !important;
            padding-right: 0.3rem !important;
            padding-bottom: 0.85rem !important;
        }

        .yearly-advance-card {
            border-radius: 22px;
        }

        .yearly-advance-card .card-header {
            padding: 1rem 1rem 0;
        }

        .yearly-advance-card .card-body {
            padding: 0.95rem 1rem 1rem;
        }

        .yearly-advance-title,
        .yearly-advance-history-title {
            font-size: 0.98rem;
            line-height: 1.24;
        }

        .yearly-advance-note {
            font-size: 0.82rem;
            line-height: 1.5;
        }

        .yearly-advance-form-shell {
            padding: 0.95rem;
        }

        .yearly-advance-form .form-control,
        .yearly-advance-btn {
            min-height: 38px;
            border-radius: 12px;
            font-size: 0.66rem;
        }

        .yearly-advance-actions {
            justify-content: flex-start;
        }

        .yearly-advance-actions .yearly-advance-btn {
            padding: 0.54rem 0.9rem;
            letter-spacing: 0.03em;
            width: auto;
            min-width: 0;
        }

        .yearly-advance-table thead th,
        .yearly-advance-table tbody td {
            padding: 0.82rem 0.78rem;
        }
    }
</style>

<div class="container-fluid py-4 yearly-advance-page">
    <?php if ($message !== ''): ?>
        <div class="alert alert-<?= htmlspecialchars($messageType) ?> yearly-advance-alert"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="row yearly-advance-page">
        <div class="col-12 mb-4">
            <div class="card yearly-advance-card">
                <div class="card-header pb-0">
                    <h6 class="yearly-advance-title">Apply Yearly Advance Salary</h6>
                </div>
                <div class="card-body">
                    <div class="yearly-advance-form-shell">
                    <form method="POST" class="yearly-advance-form">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="request_year">Year</label>
                                <select class="form-control" name="request_year" id="request_year" required>
                                    <?php for ($year = date('Y'); $year <= date('Y') + 1; $year++): ?>
                                        <option value="<?= $year ?>" <?= $selectedYear === $year ? 'selected' : '' ?>><?= $year ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="amount">Requested Amount</label>
                                <input type="number" class="form-control" name="amount" id="amount" min="0.01" step="0.01" value="<?= htmlspecialchars((string) $amountValue) ?>" required>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label" for="reason">Reason</label>
                                <textarea class="form-control" name="reason" id="reason" rows="5" required><?= htmlspecialchars($reasonValue) ?></textarea>
                            </div>
                        </div>

                        <div class="yearly-actions yearly-advance-actions">
                            <button type="submit" class="btn mb-0 yearly-advance-btn" id="submitYearlyAdvanceRequestBtn">Submit Yearly Request</button>
                        </div>
                    </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card mb-4 yearly-advance-card">
                <div class="card-header pb-0">
                    <h6 class="yearly-advance-history-title">My Yearly Advance Requests</h6>
                </div>
                <div class="card-body px-0 pt-0 pb-2 yearly-advance-table-shell">
                    <div class="table-responsive p-0 yearly-advance-table-wrap">
                        <table class="table align-items-center mb-0 yearly-advance-table">
                            <thead>
                                <tr>
                                    <th>Year</th>
                                    <th>Requested</th>
                                    <th>Allocated</th>
                                    <th>Remaining</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Applied</th>
                                    <th>Decision</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($requests->num_rows > 0): ?>
                                    <?php while ($row = $requests->fetch_assoc()): ?>
                                        <?php
                                        $allocatedAmount = round((float) $row['allocated_amount'], 2);
                                        $remainingAmount = round((float) $row['amount'] - $allocatedAmount, 2);
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars((string) $row['request_year']) ?></td>
                                            <td><?= number_format((float) $row['amount'], 2) ?></td>
                                            <td><?= number_format($allocatedAmount, 2) ?></td>
                                            <td><?= number_format(max(0, $remainingAmount), 2) ?></td>
                                            <td class="text-wrap" style="max-width: 280px;"><?= nl2br(htmlspecialchars($row['reason'])) ?></td>
                                            <td>
                                                <?php if ($row['status'] === 'Approved'): ?>
                                                    <span class="badge bg-gradient-success">Approved</span>
                                                <?php elseif ($row['status'] === 'Rejected'): ?>
                                                    <span class="badge bg-gradient-danger">Rejected</span>
                                                <?php else: ?>
                                                    <span class="badge bg-gradient-warning">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($row['applied_at']) ?></td>
                                            <td>
                                                <?php if (!empty($row['approved_rejected_at'])): ?>
                                                    <div class="text-xs"><?= htmlspecialchars($row['approved_rejected_at']) ?></div>
                                                    <div class="text-xs"><?= htmlspecialchars($row['approved_by_name'] ?? '') ?></div>
                                                <?php else: ?>
                                                    <span class="text-xs text-muted yearly-advance-waiting">Waiting</span>
                                                <?php endif; ?>
                                                <?php if (!empty($row['reject_reason'])): ?>
                                                    <div class="text-xs text-danger mt-2 yearly-advance-reject"><?= nl2br(htmlspecialchars($row['reject_reason'])) ?></div>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center yearly-advance-empty">No yearly advance salary requests found.</td>
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
    const submitButton = document.getElementById('submitYearlyAdvanceRequestBtn');

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
