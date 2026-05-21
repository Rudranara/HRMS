<?php
include("header.php");
require 'db_connection.php';
require_once '../includes/attendance_change_request_helper.php';

if (!isset($_SESSION['employee_id'])) {
    echo "<div class='alert alert-danger'>You must be logged in to view attendance change requests.</div>";
    exit;
}

ensureAttendanceChangeRequestTable($conn);

$employee_id = (int) $_SESSION['employee_id'];

$stmt = $conn->prepare("
    SELECT *
    FROM attendance_change_requests
    WHERE employee_id = ?
    ORDER BY applied_at DESC, id DESC
");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<style>
    :root {
        --attendance-change-list-shell: linear-gradient(180deg, #f8fafc 0%, #eef3f8 100%);
        --attendance-change-list-border: rgba(148, 163, 184, 0.18);
        --attendance-change-list-shadow: 0 24px 56px rgba(15, 23, 42, 0.08);
    }

    .attendance-change-list-page {
        padding-top: 0.95rem !important;
        padding-bottom: 1.2rem !important;
    }

    .attendance-change-list-header {
        align-items: center;
    }

    .attendance-change-list-title-row {
        align-items: center;
    }

    .attendance-change-list-title {
        margin: 0;
        color: #0f172a;
        font-size: 1.15rem;
        font-weight: 800;
        letter-spacing: -0.02em;
    }

    .attendance-change-list-cta {
        min-height: 46px;
        padding: 0.75rem 1rem;
        border-radius: 16px;
        border: 0;
        background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
        box-shadow: 0 16px 28px rgba(15, 23, 42, 0.12);
        font-size: 0.82rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .attendance-change-list-card {
        border: 1px solid var(--attendance-change-list-border);
        border-radius: 28px;
        background: var(--attendance-change-list-shell);
        box-shadow: var(--attendance-change-list-shadow);
        overflow: hidden;
    }

    .attendance-change-list-shell {
        background: #ffffff;
    }

    .attendance-change-list-wrap {
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
    }

    .attendance-change-list-table {
        margin-bottom: 0;
        min-width: 940px;
    }

    .attendance-change-list-table thead th {
        padding: 1rem 1rem;
        border-bottom: 1px solid #eef2f7;
        background: #f8fafc;
        color: #64748b;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .attendance-change-list-table tbody td {
        padding: 1rem;
        border-bottom: 1px solid #eef2f7;
        vertical-align: top;
        color: #1f2937;
    }

    .attendance-change-list-table tbody tr:hover {
        background: #fbfdff;
    }

    .attendance-change-list-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .attendance-change-list-date {
        color: #0f172a;
        font-size: 0.92rem;
        font-weight: 700;
        line-height: 1.45;
    }

    .attendance-change-list-meta,
    .attendance-change-list-reason,
    .attendance-change-list-decision {
        color: #64748b;
        font-size: 0.76rem;
        line-height: 1.55;
    }

    .attendance-change-list-reason {
        color: #475569;
        font-weight: 500;
    }

    .attendance-change-list-status .badge {
        border-radius: 999px;
        padding: 0.52rem 0.82rem;
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        border: 1px solid transparent;
        box-shadow: none;
    }

    .attendance-change-list-status .bg-gradient-success {
        background: #e7f8ef !important;
        color: #16a34a !important;
        border-color: #bfe8cd;
    }

    .attendance-change-list-status .bg-gradient-danger {
        background: #fff1f2 !important;
        color: #dc2626 !important;
        border-color: #fecdd3;
    }

    .attendance-change-list-status .bg-gradient-warning {
        background: #fff7db !important;
        color: #b45309 !important;
        border-color: #f8df9c;
    }

    .attendance-change-list-empty {
        padding: 1.4rem 1rem !important;
        color: #64748b;
        font-weight: 600;
    }

    @media (max-width: 767.98px) {
        .attendance-change-list-page {
            padding-top: 0.6rem !important;
            padding-left: 0.3rem !important;
            padding-right: 0.3rem !important;
            padding-bottom: 0.8rem !important;
        }

        .attendance-change-list-header {
            flex-wrap: nowrap;
            align-items: center;
            min-width: 0;
        }

        .attendance-change-list-title-col {
            flex: 1 1 auto;
            max-width: calc(100% - 138px);
            width: calc(100% - 138px);
            margin-bottom: 0.85rem !important;
            padding-right: 0.45rem;
        }

        .attendance-change-list-action-col {
            flex: 0 0 138px;
            max-width: 138px;
            width: 138px;
            margin-bottom: 0.85rem !important;
            text-align: right !important;
        }

        .attendance-change-list-title {
            font-size: 0.92rem;
            line-height: 1.3;
        }

        .attendance-change-list-cta {
            width: 100%;
            min-height: 40px;
            padding: 0.65rem 0.8rem;
            border-radius: 14px;
            font-size: 0.72rem;
            letter-spacing: 0.05em;
        }

        .attendance-change-list-card {
            border-radius: 22px;
        }

        .attendance-change-list-table thead th,
        .attendance-change-list-table tbody td {
            padding: 0.85rem 0.8rem;
        }

        .attendance-change-list-date {
            font-size: 0.84rem;
        }
    }
</style>

<div class="container-fluid py-4 attendance-change-list-page">
    <div class="row">
        <div class="col-12">
            <div class="row attendance-change-list-header">
                <div class="col-6 mb-4 d-flex align-items-center attendance-change-list-title-row attendance-change-list-title-col">
                    <h6 class="mb-0 attendance-change-list-title">My Attendance Change Requests</h6>
                </div>
                <div class="col-6 mb-4 text-end attendance-change-list-action-col">
                    <a href="apply_attendance_change" class="btn bg-gradient-dark mb-0 attendance-change-list-cta">Apply Change</a>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card mb-4 attendance-change-list-card">
                <div class="card-body px-0 pt-0 pb-2 attendance-change-list-shell">
                    <div class="table-responsive p-0 attendance-change-list-wrap">
                        <table class="table align-items-center mb-0 attendance-change-list-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Requested Status</th>
                                    <th>Requested Time</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Decision Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->num_rows > 0): ?>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <span class="attendance-change-list-date"><?= htmlspecialchars($row['attendance_date']) ?></span><br>
                                                <span class="text-xs attendance-change-list-meta"><?= htmlspecialchars($row['applied_at']) ?></span>
                                            </td>
                                            <td><span class="attendance-change-list-date"><?= htmlspecialchars($row['requested_status'] ?? 'Present') ?></span></td>
                                            <td>
                                                <span class="text-xs attendance-change-list-meta">In: <?= htmlspecialchars($row['requested_punch_in']) ?></span><br>
                                                <span class="text-xs attendance-change-list-meta">Out: <?= htmlspecialchars($row['requested_punch_out']) ?></span>
                                            </td>
                                            <td><span class="text-xs attendance-change-list-reason"><?= nl2br(htmlspecialchars($row['reason'] ?: 'N/A')) ?></span></td>
                                            <td class="attendance-change-list-status">
                                                <?php if ($row['status'] === 'Approved'): ?>
                                                    <span class="badge bg-gradient-success">Approved</span>
                                                <?php elseif ($row['status'] === 'Rejected'): ?>
                                                    <span class="badge bg-gradient-danger">Rejected</span>
                                                <?php else: ?>
                                                    <span class="badge bg-gradient-warning">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($row['status'] === 'Rejected'): ?>
                                                    <span class="text-xs attendance-change-list-decision"><strong>Reason:</strong> <?= htmlspecialchars($row['reject_reason'] ?: 'N/A') ?></span><br>
                                                    <span class="text-xs attendance-change-list-decision"><strong>By:</strong> <?= htmlspecialchars($row['approved_by_name'] ?? 'Admin') ?></span><br>
                                                    <span class="text-xs attendance-change-list-decision"><strong>On:</strong> <?= htmlspecialchars($row['approved_rejected_at'] ?? 'N/A') ?></span>
                                                <?php elseif ($row['status'] === 'Approved'): ?>
                                                    <span class="text-xs attendance-change-list-decision"><strong>Approved By:</strong> <?= htmlspecialchars($row['approved_by_name'] ?? 'Admin') ?></span><br>
                                                    <span class="text-xs attendance-change-list-decision"><strong>On:</strong> <?= htmlspecialchars($row['approved_rejected_at'] ?? 'N/A') ?></span>
                                                <?php else: ?>
                                                    <span class="text-xs text-muted attendance-change-list-decision">Waiting for admin action</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center attendance-change-list-empty">No attendance change requests found.</td>
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

<?php
$stmt->close();
include("footer.php");
?>
