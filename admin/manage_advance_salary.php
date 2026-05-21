<?php
include("header.php");
require 'db_connection.php';
require_once '../includes/advance_salary_request_helper.php';

if (!isset($_SESSION['admin_id'])) {
    echo "<div class='alert alert-danger'>You must be logged in to access this page.</div>";
    exit;
}

ensureAdvanceSalaryRequestTable($conn);

$flashMessage = $_SESSION['advance_salary_admin_flash_message'] ?? '';
$flashType = $_SESSION['advance_salary_admin_flash_type'] ?? 'success';
unset($_SESSION['advance_salary_admin_flash_message'], $_SESSION['advance_salary_admin_flash_type']);

$statusFilter = $_GET['status'] ?? '';
$typeFilter = $_GET['type'] ?? '';
$allowedTypes = ['monthly', 'yearly'];
$allowedStatuses = ['Pending', 'Approved', 'Rejected'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['action_type'])) {
    $requestId = (int) $_POST['request_id'];
    $actionType = trim($_POST['action_type']);
    $rejectReason = trim($_POST['reject_reason'] ?? '');
    $decisionAt = date('Y-m-d H:i:s');
    $approvedById = (int) ($_SESSION['admin_id'] ?? 0);
    $approvedByName = $_SESSION['admin_name'] ?? 'Admin';
    $approvedByType = $_SESSION['admin_roll'] ?? 'Admin';

    $requestStmt = $conn->prepare("
        SELECT asr.*, e.name, e.employee_id AS employee_code
        FROM advance_salary_requests asr
        JOIN employees e ON asr.employee_id = e.id
        WHERE asr.id = ?
        LIMIT 1
    ");
    $requestStmt->bind_param("i", $requestId);
    $requestStmt->execute();
    $request = $requestStmt->get_result()->fetch_assoc();
    $requestStmt->close();

    if (!$request) {
        $flashMessage = "Request not found.";
        $flashType = 'danger';
    } elseif ($actionType === 'approve') {
        if ($request['status'] !== 'Pending') {
            $flashMessage = "Only pending requests can be approved.";
            $flashType = 'warning';
        } elseif (
            $request['request_type'] === 'monthly' &&
            advanceSalaryPayrollExists($conn, (int) $request['employee_id'], (int) $request['request_year'], (int) $request['request_month'])
        ) {
            $flashMessage = "Payroll is already generated for this employee and month.";
            $flashType = 'warning';
        } else {
            $approveStmt = $conn->prepare("
                UPDATE advance_salary_requests
                SET status = 'Approved',
                    approved_rejected_at = ?,
                    approved_by_id = ?,
                    approved_by_name = ?,
                    approved_by_type = ?,
                    reject_reason = NULL
                WHERE id = ?
            ");
            $approveStmt->bind_param("sissi", $decisionAt, $approvedById, $approvedByName, $approvedByType, $requestId);

            if ($approveStmt->execute()) {
                $_SESSION['advance_salary_admin_flash_message'] = "Advance salary request approved successfully.";
                $_SESSION['advance_salary_admin_flash_type'] = 'success';
                $redirectQuery = http_build_query(array_filter(['status' => $statusFilter, 'type' => $typeFilter]));
                header("Location: manage_advance_salary" . ($redirectQuery !== '' ? '?' . $redirectQuery : ''));
                exit;
            }

            $flashMessage = "Failed to approve the request.";
            $flashType = 'danger';
            $approveStmt->close();
        }
    } elseif ($actionType === 'delete') {
        $deleteAllocationStmt = $conn->prepare("DELETE FROM advance_salary_request_allocations WHERE request_id = ?");
        $deleteAllocationStmt->bind_param("i", $requestId);
        $deleteAllocationStmt->execute();
        $deleteAllocationStmt->close();

        $deleteStmt = $conn->prepare("DELETE FROM advance_salary_requests WHERE id = ?");
        $deleteStmt->bind_param("i", $requestId);

        if ($deleteStmt->execute()) {
            $_SESSION['advance_salary_admin_flash_message'] = "Advance salary request deleted successfully.";
            $_SESSION['advance_salary_admin_flash_type'] = 'success';
            $redirectQuery = http_build_query(array_filter(['status' => $statusFilter, 'type' => $typeFilter]));
            header("Location: manage_advance_salary" . ($redirectQuery !== '' ? '?' . $redirectQuery : ''));
            exit;
        }

        $flashMessage = "Failed to delete the request.";
        $flashType = 'danger';
        $deleteStmt->close();
    } elseif ($actionType === 'reject') {
        if ($request['status'] !== 'Pending') {
            $flashMessage = "Only pending requests can be rejected.";
            $flashType = 'warning';
        } else {
            $rejectStmt = $conn->prepare("
                UPDATE advance_salary_requests
                SET status = 'Rejected',
                    approved_rejected_at = ?,
                    approved_by_id = ?,
                    approved_by_name = ?,
                    approved_by_type = ?,
                    reject_reason = ?
                WHERE id = ?
            ");
            $rejectStmt->bind_param("sisssi", $decisionAt, $approvedById, $approvedByName, $approvedByType, $rejectReason, $requestId);

            if ($rejectStmt->execute()) {
                $_SESSION['advance_salary_admin_flash_message'] = "Advance salary request rejected.";
                $_SESSION['advance_salary_admin_flash_type'] = 'success';
                $redirectQuery = http_build_query(array_filter(['status' => $statusFilter, 'type' => $typeFilter]));
                header("Location: manage_advance_salary" . ($redirectQuery !== '' ? '?' . $redirectQuery : ''));
                exit;
            }

            $flashMessage = "Failed to reject the request.";
            $flashType = 'danger';
            $rejectStmt->close();
        }
    } elseif ($actionType === 'allocate') {
        if ($request['request_type'] !== 'yearly') {
            $flashMessage = "Allocation is only available for yearly advance requests.";
            $flashType = 'warning';
        } elseif ($request['status'] !== 'Approved') {
            $flashMessage = "Only approved yearly requests can be allocated to a payroll month.";
            $flashType = 'warning';
        } else {
            $payrollYear = (int) ($_POST['payroll_year'] ?? 0);
            $payrollMonth = (int) ($_POST['payroll_month'] ?? 0);
            $allocationAmount = round((float) ($_POST['allocation_amount'] ?? 0), 2);
            $allocationNotes = trim($_POST['allocation_notes'] ?? '');

            if ($payrollYear !== (int) $request['request_year']) {
                $flashMessage = "Allocated payroll year must match the request year.";
                $flashType = 'warning';
            } elseif ($payrollMonth < 1 || $payrollMonth > 12) {
                $flashMessage = "Please select a valid payroll month.";
                $flashType = 'warning';
            } elseif ($allocationAmount <= 0) {
                $flashMessage = "Allocation amount must be greater than zero.";
                $flashType = 'warning';
            } elseif (advanceSalaryPayrollExists($conn, (int) $request['employee_id'], $payrollYear, $payrollMonth)) {
                $flashMessage = "Payroll is already generated for that employee and month.";
                $flashType = 'warning';
            } else {
                $existingAllocationStmt = $conn->prepare("
                    SELECT amount, payroll_salary_id
                    FROM advance_salary_request_allocations
                    WHERE request_id = ? AND payroll_year = ? AND payroll_month = ?
                    LIMIT 1
                ");
                $existingAllocationStmt->bind_param("iii", $requestId, $payrollYear, $payrollMonth);
                $existingAllocationStmt->execute();
                $existingAllocation = $existingAllocationStmt->get_result()->fetch_assoc();
                $existingAllocationStmt->close();

                if ($existingAllocation && !empty($existingAllocation['payroll_salary_id'])) {
                    $flashMessage = "This monthly allocation is already applied to payroll and cannot be changed.";
                    $flashType = 'warning';
                } else {
                    $alreadyAllocated = getAdvanceSalaryAllocatedTotal($conn, $requestId);
                    $currentMonthAllocated = (float) ($existingAllocation['amount'] ?? 0);
                    $remainingCapacity = round((float) $request['amount'] - ($alreadyAllocated - $currentMonthAllocated), 2);

                    if ($allocationAmount > $remainingCapacity) {
                        $flashMessage = "Allocation exceeds the remaining approved advance amount.";
                        $flashType = 'warning';
                    } else {
                        $createdAt = date('Y-m-d H:i:s');
                        $allocationStmt = $conn->prepare("
                            INSERT INTO advance_salary_request_allocations (
                                request_id, employee_id, payroll_year, payroll_month, amount, notes, created_at,
                                created_by_id, created_by_name, created_by_type
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                            ON DUPLICATE KEY UPDATE
                                amount = VALUES(amount),
                                notes = VALUES(notes),
                                created_at = VALUES(created_at),
                                created_by_id = VALUES(created_by_id),
                                created_by_name = VALUES(created_by_name),
                                created_by_type = VALUES(created_by_type)
                        ");
                        $allocationStmt->bind_param(
                            "iiiidssiss",
                            $requestId,
                            $request['employee_id'],
                            $payrollYear,
                            $payrollMonth,
                            $allocationAmount,
                            $allocationNotes,
                            $createdAt,
                            $approvedById,
                            $approvedByName,
                            $approvedByType
                        );

                        if ($allocationStmt->execute()) {
                            $_SESSION['advance_salary_admin_flash_message'] = "Monthly allocation saved successfully.";
                            $_SESSION['advance_salary_admin_flash_type'] = 'success';
                            $redirectQuery = http_build_query(array_filter(['status' => $statusFilter, 'type' => $typeFilter]));
                            header("Location: manage_advance_salary" . ($redirectQuery !== '' ? '?' . $redirectQuery : ''));
                            exit;
                        }

                        $flashMessage = "Failed to save the monthly allocation.";
                        $flashType = 'danger';
                        $allocationStmt->close();
                    }
                }
            }
        }
    }
}

$conditions = [];
$params = [];
$types = '';

if (in_array($statusFilter, $allowedStatuses, true)) {
    $conditions[] = "asr.status = ?";
    $params[] = $statusFilter;
    $types .= 's';
}

if (in_array($typeFilter, $allowedTypes, true)) {
    $conditions[] = "asr.request_type = ?";
    $params[] = $typeFilter;
    $types .= 's';
}

$query = "
    SELECT
        asr.*,
        e.name,
        e.employee_id AS employee_code,
        COALESCE(alloc.allocated_amount, 0) AS allocated_amount
    FROM advance_salary_requests asr
    JOIN employees e ON asr.employee_id = e.id
    LEFT JOIN (
        SELECT request_id, SUM(amount) AS allocated_amount
        FROM advance_salary_request_allocations
        GROUP BY request_id
    ) alloc ON alloc.request_id = asr.id
";

if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$query .= " ORDER BY asr.request_year DESC, asr.request_month DESC, asr.id DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$requests = [];
$requestIds = [];
while ($row = $result->fetch_assoc()) {
    $requests[] = $row;
    $requestIds[] = (int) $row['id'];
}
$stmt->close();

$allocationMap = [];
if (!empty($requestIds)) {
    $placeholders = implode(',', array_fill(0, count($requestIds), '?'));
    $allocationQuery = "
        SELECT request_id, payroll_year, payroll_month, amount, notes, payroll_applied_at
        FROM advance_salary_request_allocations
        WHERE request_id IN ($placeholders)
        ORDER BY payroll_year ASC, payroll_month ASC
    ";
    $allocationStmt = $conn->prepare($allocationQuery);
    $allocationTypes = str_repeat('i', count($requestIds));
    $allocationStmt->bind_param($allocationTypes, ...$requestIds);
    $allocationStmt->execute();
    $allocationResult = $allocationStmt->get_result();

    while ($allocation = $allocationResult->fetch_assoc()) {
        $mappedRequestId = (int) $allocation['request_id'];
        if (!isset($allocationMap[$mappedRequestId])) {
            $allocationMap[$mappedRequestId] = [];
        }
        $allocationMap[$mappedRequestId][] = $allocation;
    }
    $allocationStmt->close();
}
?>

<style>
    .advance-salary-page {
        padding-bottom: 2rem;
    }

    .advance-salary-alert {
        border: 1px solid #e5eaf1;
        border-radius: 18px;
        box-shadow: 0 18px 32px rgba(15, 23, 42, 0.08);
    }

    .advance-salary-toolbar,
    .advance-salary-table-card,
    .advance-salary-modal .modal-content {
        border: 1px solid #e5eaf1;
        border-radius: 28px;
        background: #ffffff;
        box-shadow: 0 28px 60px rgba(15, 23, 42, 0.07);
    }

    .advance-salary-toolbar .card-body {
        padding: 1.4rem 1.5rem;
    }

    .advance-salary-title {
        margin: 0;
        color: #0f172a;
        font-size: 1.3rem;
        font-weight: 800;
        letter-spacing: -0.03em;
    }

    .advance-salary-subtitle {
        margin: 0.35rem 0 0;
        color: #64748b;
        font-size: 0.9rem;
        line-height: 1.6;
    }

    .advance-salary-filter-form {
        align-items: end;
    }

    .advance-salary-filter-form .form-label,
    .advance-salary-modal .form-label {
        margin-bottom: 0.45rem;
        color: #64748b;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.09em;
        text-transform: uppercase;
    }

    .advance-salary-filter-form .form-control,
    .advance-salary-modal .form-control {
        min-height: 46px;
        border: 1px solid #d7deea;
        border-radius: 14px;
        padding: 0.72rem 0.95rem;
        background: #f8fafc;
        color: #0f172a;
        box-shadow: none;
    }

    .advance-salary-filter-form textarea.form-control,
    .advance-salary-modal textarea.form-control {
        min-height: auto;
        border-radius: 16px;
    }

    .advance-salary-filter-form .form-control:focus,
    .advance-salary-modal .form-control:focus {
        border-color: #9aa8bc;
        background: #ffffff;
        box-shadow: 0 0 0 0.2rem rgba(148, 163, 184, 0.16);
    }

    .advance-salary-reset-btn,
    .advance-salary-table .btn,
    .advance-salary-modal .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 46px;
        border-radius: 14px;
        font-size: 0.76rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .advance-salary-reset-btn {
        border: 1px solid #111827;
        background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
        color: #ffffff;
        box-shadow: 0 16px 28px rgba(15, 23, 42, 0.16);
        padding: 0.72rem 1rem;
    }

    .advance-salary-reset-btn:hover,
    .advance-salary-reset-btn:focus,
    .advance-salary-reset-btn:active {
        border-color: #111827;
        background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
        color: #ffffff;
        box-shadow: 0 16px 28px rgba(15, 23, 42, 0.16);
    }

    .advance-salary-table-card {
        overflow: hidden;
    }

    .advance-salary-table-card .card-body {
        padding: 0;
    }

    .advance-salary-table-wrap {
        padding: 0 1.4rem 1.35rem;
    }

    .advance-salary-table {
        margin-bottom: 0;
    }

    .advance-salary-table thead th {
        padding: 1rem 0.9rem;
        border-bottom: 1px solid #e8edf5;
        color: #64748b;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.09em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .advance-salary-table tbody td {
        padding: 1rem 0.9rem;
        border-bottom: 1px solid #eef2f7;
        vertical-align: top;
        color: #0f172a;
        font-size: 0.84rem;
    }

    .advance-salary-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .advance-salary-employee {
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
    }

    .advance-salary-employee strong {
        font-size: 0.92rem;
    }

    .advance-salary-meta,
    .advance-salary-note,
    .advance-salary-empty,
    .advance-salary-approved-by,
    .advance-salary-reject-reason,
    .advance-salary-allocation-item {
        color: #64748b;
        font-size: 0.76rem;
        line-height: 1.5;
    }

    .advance-salary-note {
        max-width: 250px;
        color: #334155;
    }

    .advance-salary-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 30px;
        padding: 0.38rem 0.72rem;
        border-radius: 999px;
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        border: 1px solid transparent;
    }

    .advance-salary-chip-type-yearly {
        background: #e8f3ff;
        border-color: #c8defc;
        color: #1d4ed8;
    }

    .advance-salary-chip-type-monthly {
        background: #f1f5f9;
        border-color: #d9e2ec;
        color: #475569;
    }

    .advance-salary-chip-status-approved {
        background: #edf9f1;
        border-color: #bfe3cf;
        color: #15803d;
    }

    .advance-salary-chip-status-rejected {
        background: #fff1f2;
        border-color: #fecdd3;
        color: #be123c;
    }

    .advance-salary-chip-status-pending {
        background: #fff8e8;
        border-color: #fde68a;
        color: #b45309;
    }

    .advance-salary-amount {
        font-size: 0.88rem;
        font-weight: 700;
        color: #0f172a;
        white-space: nowrap;
    }

    .advance-salary-period {
        font-size: 0.84rem;
        font-weight: 700;
        color: #0f172a;
    }

    .advance-salary-action-group {
        display: flex;
        flex-wrap: nowrap;
        gap: 0.55rem;
        align-items: center;
        justify-content: space-between;
    }

    .advance-salary-action-group form {
        margin-bottom: 0 !important;
    }

    .advance-salary-approved-by {
        flex: 1 1 auto;
        min-width: 0;
    }

    .advance-salary-table .btn-success {
        border: 1px solid #15803d;
        background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
        box-shadow: 0 16px 28px rgba(22, 163, 74, 0.18);
    }

    .advance-salary-table .btn-danger,
    .advance-salary-modal .btn-danger {
        border: 1px solid #be123c;
        background: linear-gradient(135deg, #e11d48 0%, #be123c 100%);
        box-shadow: 0 16px 28px rgba(190, 18, 60, 0.18);
    }

    .advance-salary-table .btn-primary,
    .advance-salary-modal .btn-primary {
        border: 1px solid #111827;
        background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
        box-shadow: 0 16px 28px rgba(15, 23, 42, 0.16);
    }

    .advance-salary-table .btn-outline-danger,
    .advance-salary-modal .btn-outline-secondary {
        border: 1px solid #d7deea;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        color: #475569;
        box-shadow: none;
    }

    .advance-salary-modal .modal-dialog {
        max-width: 620px;
    }

    .advance-salary-modal .modal-content {
        overflow: hidden;
        box-shadow: 0 34px 72px rgba(15, 23, 42, 0.18);
    }

    .advance-salary-modal .modal-header,
    .advance-salary-modal .modal-footer {
        padding: 1.15rem 1.35rem;
        border-color: #e9eef5;
    }

    .advance-salary-modal .modal-body {
        padding: 1.2rem 1.35rem 1.35rem;
    }

    .advance-salary-modal .modal-title {
        color: #0f172a;
        font-size: 1.08rem;
        font-weight: 800;
        letter-spacing: -0.02em;
    }

    .advance-salary-modal-summary {
        margin-bottom: 1rem;
        padding: 1rem 1.05rem;
        border: 1px solid #e5eaf1;
        border-radius: 18px;
        background: #f8fafc;
    }

    .advance-salary-modal-summary p {
        margin-bottom: 0.35rem;
        color: #475569;
    }

    .advance-salary-modal-summary p:last-child {
        margin-bottom: 0;
    }

    @media (max-width: 991.98px) {
        .advance-salary-page {
            padding-bottom: 1.25rem;
        }

        .advance-salary-toolbar .card-body {
            padding: 1.1rem;
        }
    }

    @media (max-width: 767.98px) {
        .advance-salary-table-wrap {
            padding: 0 1rem 1rem;
        }

        .advance-salary-modal .modal-header,
        .advance-salary-modal .modal-body,
        .advance-salary-modal .modal-footer {
            padding-left: 1rem;
            padding-right: 1rem;
        }
    }
</style>

<!-- End Navbar -->
<div class="container-fluid container-fluid-main advance-salary-page">
    <?php if ($flashMessage !== ''): ?>
        <div class="alert alert-<?= htmlspecialchars($flashType) ?> advance-salary-alert"><?= htmlspecialchars($flashMessage) ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12 mb-4">
            <div class="card advance-salary-toolbar">
                <div class="card-body">
                    <div class="row align-items-end g-3">
                        <div class="col-md-4">
                            <h6 class="advance-salary-title">Advance Salary Requests</h6>
                            <p class="advance-salary-subtitle">Manage monthly requests and yearly requests with month-wise payroll allocations.</p>
                        </div>
                        <div class="col-md-8">
                            <form method="GET" class="row g-2 justify-content-md-end advance-salary-filter-form">
                                <div class="col-md-4">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-control" onchange="this.form.submit()">
                                        <option value="">All Requests</option>
                                        <option value="Pending" <?= $statusFilter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="Approved" <?= $statusFilter === 'Approved' ? 'selected' : '' ?>>Approved</option>
                                        <option value="Rejected" <?= $statusFilter === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Type</label>
                                    <select name="type" class="form-control" onchange="this.form.submit()">
                                        <option value="">All Types</option>
                                        <option value="monthly" <?= $typeFilter === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                                        <option value="yearly" <?= $typeFilter === 'yearly' ? 'selected' : '' ?>>Yearly</option>
                                    </select>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <a href="manage_advance_salary" class="btn advance-salary-reset-btn w-100 mb-0">Reset Filters</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card mb-4 advance-salary-table-card">
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="table-responsive p-0 advance-salary-table-wrap">
                        <table class="table align-items-center mb-0 advance-salary-table">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Type</th>
                                    <th>Period</th>
                                    <th>Requested</th>
                                    <th>Allocated</th>
                                    <th>Remaining</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Monthly Allocation</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($requests)): ?>
                                    <?php foreach ($requests as $row): ?>
                                        <?php
                                        $requestId = (int) $row['id'];
                                        $requestType = $row['request_type'] ?: 'monthly';
                                        $allocatedAmount = round((float) $row['allocated_amount'], 2);
                                        $remainingAmount = $requestType === 'yearly'
                                            ? round((float) $row['amount'] - $allocatedAmount, 2)
                                            : 0;
                                        $allocationRows = $allocationMap[$requestId] ?? [];
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="advance-salary-employee">
                                                    <strong><?= htmlspecialchars($row['name']) ?></strong>
                                                    <span class="advance-salary-meta"><?= htmlspecialchars($row['employee_code']) ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($requestType === 'yearly'): ?>
                                                    <span class="advance-salary-chip advance-salary-chip-type-yearly">Yearly</span>
                                                <?php else: ?>
                                                    <span class="advance-salary-chip advance-salary-chip-type-monthly">Monthly</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="advance-salary-period">
                                                <?php if ($requestType === 'yearly'): ?>
                                                    <?= htmlspecialchars((string) $row['request_year']) ?>
                                                <?php else: ?>
                                                    <?= htmlspecialchars(date('F', mktime(0, 0, 0, (int) $row['request_month'], 1)) . ' ' . $row['request_year']) ?>
                                                <?php endif; ?>
                                                </div>
                                                <div class="advance-salary-meta"><?= htmlspecialchars($row['applied_at']) ?></div>
                                            </td>
                                            <td><span class="advance-salary-amount"><?= number_format((float) $row['amount'], 2) ?></span></td>
                                            <td><span class="advance-salary-amount"><?= $requestType === 'yearly' ? number_format($allocatedAmount, 2) : '-' ?></span></td>
                                            <td><span class="advance-salary-amount"><?= $requestType === 'yearly' ? number_format(max(0, $remainingAmount), 2) : '-' ?></span></td>
                                            <td class="advance-salary-note"><?= nl2br(htmlspecialchars($row['reason'])) ?></td>
                                            <td>
                                                <?php if ($row['status'] === 'Approved'): ?>
                                                    <span class="advance-salary-chip advance-salary-chip-status-approved">Approved</span>
                                                <?php elseif ($row['status'] === 'Rejected'): ?>
                                                    <span class="advance-salary-chip advance-salary-chip-status-rejected">Rejected</span>
                                                <?php else: ?>
                                                    <span class="advance-salary-chip advance-salary-chip-status-pending">Pending</span>
                                                <?php endif; ?>
                                                <?php if (!empty($row['reject_reason'])): ?>
                                                    <div class="advance-salary-reject-reason mt-2 text-danger"><?= nl2br(htmlspecialchars($row['reject_reason'])) ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($requestType === 'yearly'): ?>
                                                    <?php if (!empty($allocationRows)): ?>
                                                        <?php foreach ($allocationRows as $allocation): ?>
                                                            <div class="advance-salary-allocation-item mb-1">
                                                                <strong><?= htmlspecialchars(date('F', mktime(0, 0, 0, (int) $allocation['payroll_month'], 1)) . ' ' . $allocation['payroll_year']) ?>:</strong>
                                                                <?= number_format((float) $allocation['amount'], 2) ?>
                                                                <?php if (!empty($allocation['payroll_applied_at'])): ?>
                                                                    <span class="text-success">(Applied)</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <span class="advance-salary-empty">No month allocated yet</span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <?php if (!empty($row['payroll_applied_at'])): ?>
                                                        <div class="advance-salary-meta text-success">Payroll updated</div>
                                                        <div class="advance-salary-meta"><?= htmlspecialchars($row['payroll_applied_at']) ?></div>
                                                    <?php else: ?>
                                                        <span class="advance-salary-empty">Auto applies on payroll generation</span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="advance-salary-action-group">
                                                    <?php if ($row['status'] === 'Pending'): ?>
                                                        <form method="POST" class="mb-0">
                                                            <input type="hidden" name="request_id" value="<?= $requestId ?>">
                                                            <input type="hidden" name="action_type" value="approve">
                                                            <button type="submit" class="btn btn-success btn-sm mb-0">Approve</button>
                                                        </form>
                                                        <button
                                                            type="button"
                                                            class="btn btn-danger btn-sm mb-0"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#rejectAdvanceModal"
                                                            data-request-id="<?= $requestId ?>"
                                                            data-employee-name="<?= htmlspecialchars($row['name'], ENT_QUOTES) ?>"
                                                            data-payroll-month="<?= htmlspecialchars($requestType === 'yearly' ? $row['request_year'] : date('F', mktime(0, 0, 0, (int) $row['request_month'], 1)) . ' ' . $row['request_year'], ENT_QUOTES) ?>"
                                                        >
                                                            Reject
                                                        </button>
                                                    <?php elseif ($requestType === 'yearly' && $row['status'] === 'Approved'): ?>
                                                        <button
                                                            type="button"
                                                            class="btn btn-primary btn-sm mb-0"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#allocateAdvanceModal"
                                                            data-request-id="<?= $requestId ?>"
                                                            data-request-year="<?= (int) $row['request_year'] ?>"
                                                            data-employee-name="<?= htmlspecialchars($row['name'], ENT_QUOTES) ?>"
                                                            data-requested-amount="<?= number_format((float) $row['amount'], 2, '.', '') ?>"
                                                            data-allocated-amount="<?= number_format($allocatedAmount, 2, '.', '') ?>"
                                                            data-remaining-amount="<?= number_format(max(0, $remainingAmount), 2, '.', '') ?>"
                                                        >
                                                            Allocate Month
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="advance-salary-approved-by"><?= htmlspecialchars(($row['status'] === 'Approved' ? 'Approved' : 'Rejected') . ' by ' . ($row['approved_by_name'] ?? 'Admin')) ?></span>
                                                    <?php endif; ?>

                                                    <form method="POST" class="mb-0" onsubmit="return confirm('Delete this advance salary request?');">
                                                        <input type="hidden" name="request_id" value="<?= $requestId ?>">
                                                        <input type="hidden" name="action_type" value="delete">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm mb-0">Delete</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="10" class="text-center advance-salary-empty py-4">No advance salary requests found.</td>
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

<div class="modal fade advance-salary-modal" id="rejectAdvanceModal" tabindex="-1" aria-labelledby="rejectAdvanceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="rejectAdvanceModalLabel">Reject Advance Salary Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="request_id" id="reject_request_id">
                    <input type="hidden" name="action_type" value="reject">
                    <div class="advance-salary-modal-summary">
                        <p class="text-sm mb-0">
                            Reject request for <strong id="reject_employee_name"></strong> for
                            <strong id="reject_payroll_month"></strong>.
                        </p>
                    </div>
                    <label class="form-label" for="reject_reason">Reason</label>
                    <textarea class="form-control" name="reject_reason" id="reject_reason" rows="4" placeholder="Optional note"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary mb-0" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger mb-0">Reject Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade advance-salary-modal" id="allocateAdvanceModal" tabindex="-1" aria-labelledby="allocateAdvanceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="allocateAdvanceModalLabel">Allocate Yearly Advance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="request_id" id="allocate_request_id">
                    <input type="hidden" name="action_type" value="allocate">
                    <input type="hidden" name="payroll_year" id="allocate_payroll_year">
                    <div class="advance-salary-modal-summary">
                        <p class="text-sm">Employee: <strong id="allocate_employee_name"></strong></p>
                        <p class="text-sm">
                            Requested: <strong id="allocate_requested_amount"></strong><br>
                            Already Allocated: <strong id="allocate_allocated_amount"></strong><br>
                            Remaining: <strong id="allocate_remaining_amount"></strong>
                        </p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="allocate_month">Payroll Month</label>
                        <select class="form-control" name="payroll_month" id="allocate_month" required>
                            <?php for ($month = 1; $month <= 12; $month++): ?>
                                <option value="<?= $month ?>"><?= date('F', mktime(0, 0, 0, $month, 1)) ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="allocation_amount">Amount For This Month</label>
                        <input type="number" class="form-control" name="allocation_amount" id="allocation_amount" min="0.01" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="allocation_notes">Notes</label>
                        <textarea class="form-control" name="allocation_notes" id="allocation_notes" rows="3" placeholder="Optional note"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary mb-0" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary mb-0">Save Allocation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const rejectModal = document.getElementById('rejectAdvanceModal');
    const allocateModal = document.getElementById('allocateAdvanceModal');

    if (rejectModal) {
        rejectModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('reject_request_id').value = button.getAttribute('data-request-id') || '';
            document.getElementById('reject_employee_name').textContent = button.getAttribute('data-employee-name') || '';
            document.getElementById('reject_payroll_month').textContent = button.getAttribute('data-payroll-month') || '';
            document.getElementById('reject_reason').value = '';
        });
    }

    if (allocateModal) {
        allocateModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('allocate_request_id').value = button.getAttribute('data-request-id') || '';
            document.getElementById('allocate_payroll_year').value = button.getAttribute('data-request-year') || '';
            document.getElementById('allocate_employee_name').textContent = button.getAttribute('data-employee-name') || '';
            document.getElementById('allocate_requested_amount').textContent = button.getAttribute('data-requested-amount') || '0.00';
            document.getElementById('allocate_allocated_amount').textContent = button.getAttribute('data-allocated-amount') || '0.00';
            document.getElementById('allocate_remaining_amount').textContent = button.getAttribute('data-remaining-amount') || '0.00';
            document.getElementById('allocation_amount').value = '';
            document.getElementById('allocation_notes').value = '';
            document.getElementById('allocate_month').selectedIndex = 0;
        });
    }
});
</script>

<?php include("footer.php"); ?>
