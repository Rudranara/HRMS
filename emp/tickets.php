
<?php
include("header.php");


if (!isset($_SESSION['employee_id'])) {
    echo "<div class='alert alert-danger'>You must be logged in to access this page.</div>";
    exit;
}

$manager_id = $_SESSION['employee_id'];

// Confirm this user is a manager
$stmt = $conn->prepare("SELECT role FROM employees WHERE id = ?");
$stmt->bind_param("i", $manager_id);
$stmt->execute();
$stmt->bind_result($role);
$stmt->fetch();
$stmt->close();

if ($role !== 'Manager') {
    echo "<div class='alert alert-danger'>Access denied. You do not have permission to view this page.</div>";
    exit;
}

// Handle ticket update form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_ticket'])) {
    $ticket_id = $_POST['ticket_id'];
    $status = $_POST['status'];
    $response = $_POST['response'];

    $stmt = $conn->prepare("UPDATE tickets SET status = ?, response = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("ssi", $status, $response, $ticket_id);
    if ($stmt->execute()) {
        echo "<div class='alert alert-success'>Ticket updated successfully.</div>";
    } else {
        echo "<div class='alert alert-danger'>Failed to update ticket.</div>";
    }
    $stmt->close();
}

// Filter by year/month (optional)
$year = $_GET['year'] ?? '';
$month = $_GET['month'] ?? '';

$query = "SELECT t.*, e.name AS employee_name 
          FROM tickets t 
          JOIN employees e ON t.employee_id = e.id 
          WHERE t.manager_id = ?";

if (!empty($year)) {
    $query .= " AND YEAR(t.created_at) = ?";
}
if (!empty($month)) {
    $query .= " AND MONTH(t.created_at) = ?";
}

$query .= " ORDER BY t.created_at DESC";

$stmt = $conn->prepare($query);

// Bind parameters
if (!empty($year) && !empty($month)) {
    $stmt->bind_param("iii", $manager_id, $year, $month);
} elseif (!empty($year)) {
    $stmt->bind_param("ii", $manager_id, $year);
} elseif (!empty($month)) {
    $stmt->bind_param("ii", $manager_id, $month);
} else {
    $stmt->bind_param("i", $manager_id);
}

$stmt->execute();
$result = $stmt->get_result();
?>
<style>
    :root {
        --tickets-shell-bg: linear-gradient(180deg, #f8fafc 0%, #eef3f8 100%);
        --tickets-shell-border: rgba(148, 163, 184, 0.18);
        --tickets-shell-shadow: 0 24px 56px rgba(15, 23, 42, 0.08);
    }

    .manager-tickets-page {
        padding-top: 0.95rem !important;
        padding-bottom: 1.2rem !important;
    }

    .manager-tickets-title {
        margin: 0;
        color: #0f172a;
        font-size: 1.15rem;
        font-weight: 800;
        letter-spacing: -0.02em;
    }

    .manager-tickets-header-row {
        align-items: center;
    }

    .manager-tickets-title-col {
        display: flex;
        align-items: center;
    }

    .manager-tickets-action-col {
        text-align: right;
    }

    .manager-tickets-cta {
        min-height: 46px;
        padding: 0.75rem 1rem;
        border-radius: 16px;
        border: 0;
        background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
        box-shadow: 0 16px 28px rgba(15, 23, 42, 0.12);
        color: #ffffff !important;
        font-size: 0.82rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .manager-tickets-card {
        border: 1px solid var(--tickets-shell-border);
        border-radius: 28px;
        background: var(--tickets-shell-bg);
        box-shadow: var(--tickets-shell-shadow);
        overflow: hidden;
    }

    .manager-tickets-shell {
        background: #ffffff;
    }

    .manager-tickets-wrap {
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
    }

    .manager-tickets-table {
        margin-bottom: 0;
        min-width: 1080px;
    }

    .manager-tickets-table thead th {
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

    .manager-tickets-table tbody td {
        padding: 1rem;
        border-bottom: 1px solid #eef2f7;
        vertical-align: middle;
        color: #1f2937;
        font-size: 0.88rem;
    }

    .manager-tickets-table tbody tr:hover {
        background: #fbfdff;
    }

    .manager-tickets-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .manager-ticket-employee,
    .manager-ticket-subject {
        font-weight: 700;
        color: #0f172a;
    }

    .manager-ticket-message,
    .manager-ticket-response {
        max-width: 230px;
        color: #64748b;
        line-height: 1.55;
    }

    .manager-ticket-status {
        text-align: center;
    }

    .manager-ticket-status .badge {
        border-radius: 999px;
        padding: 0.52rem 0.82rem;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        border: 1px solid transparent;
        box-shadow: none;
    }

    .manager-ticket-status .status-open {
        background: #fff7db !important;
        color: #b45309 !important;
        border-color: #f8df9c;
    }

    .manager-ticket-status .status-in-progress {
        background: #e8f0ff !important;
        color: #1d4ed8 !important;
        border-color: #bfd4ff;
    }

    .manager-ticket-status .status-resolved,
    .manager-ticket-status .status-closed {
        background: #ecfdf3 !important;
        color: #15803d !important;
        border-color: #bbf7d0;
    }

    .manager-ticket-action {
        min-height: 36px;
        padding: 0.58rem 0.78rem;
        border-radius: 12px;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        box-shadow: none !important;
    }

    .manager-ticket-action.btn-primary {
        background: #e9f2ff;
        border-color: #c7dafc;
        color: #1d4f91;
    }

    .manager-ticket-action.btn-primary:hover,
    .manager-ticket-action.btn-primary:focus {
        background: #dce9ff;
        border-color: #b5cffd;
        color: #153d74;
    }

    .manager-tickets-modal .btn-success {
        background: linear-gradient(135deg, #123b76 0%, #1f4c8f 100%) !important;
        border-color: #1f4c8f !important;
        color: #ffffff !important;
        box-shadow: 0 12px 22px rgba(18, 59, 118, 0.18);
    }

    .manager-tickets-modal .btn-success:hover,
    .manager-tickets-modal .btn-success:focus {
        background: linear-gradient(135deg, #0f315f 0%, #183f76 100%) !important;
        border-color: #183f76 !important;
        color: #ffffff !important;
    }

    .manager-tickets-modal .modal-content {
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 24px;
        box-shadow: 0 26px 60px rgba(15, 23, 42, 0.16);
        overflow: hidden;
    }

    .manager-tickets-modal .modal-header,
    .manager-tickets-modal .modal-footer {
        background: #ffffff;
        border-color: #eef2f7;
    }

    .manager-tickets-modal .modal-body {
        background: #f8fafc;
    }

    .manager-tickets-modal .modal-title {
        color: #0f172a;
        font-size: 1rem;
        font-weight: 800;
    }

    .manager-tickets-modal label {
        margin-bottom: 0.55rem;
        color: #475569;
        font-size: 0.74rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .manager-tickets-modal .form-control {
        min-height: 44px;
        border-radius: 14px;
        border: 1px solid #d9e2ec;
        box-shadow: none;
    }

    .manager-tickets-modal textarea.form-control {
        min-height: 110px;
    }

    .manager-tickets-modal .form-control:focus {
        border-color: #94a3b8;
        box-shadow: 0 0 0 4px rgba(148, 163, 184, 0.16);
    }

    @media (max-width: 767.98px) {
        .manager-tickets-page {
            padding-top: 0.6rem !important;
            padding-left: 0.3rem !important;
            padding-right: 0.3rem !important;
            padding-bottom: 0.85rem !important;
        }

        .manager-tickets-header-row {
            flex-wrap: nowrap;
            align-items: center;
            min-width: 0;
        }

        .manager-tickets-title-col {
            flex: 1 1 auto;
            max-width: calc(100% - 146px);
            width: calc(100% - 146px);
            margin-bottom: 0.85rem !important;
            padding-right: 0.55rem;
        }

        .manager-tickets-action-col {
            flex: 0 0 146px;
            max-width: 146px;
            width: 146px;
            margin-bottom: 0.85rem !important;
            text-align: right !important;
        }

        .manager-tickets-title {
            font-size: 0.98rem;
            line-height: 1.25;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .manager-tickets-cta {
            min-height: 40px;
            width: 100%;
            padding: 0.62rem 0.72rem;
            border-radius: 14px;
            font-size: 0.66rem;
            letter-spacing: 0.04em;
        }

        .manager-tickets-card {
            border-radius: 22px;
        }

        .manager-tickets-table thead th,
        .manager-tickets-table tbody td {
            padding: 0.82rem 0.78rem;
        }
    }

    @media (max-width: 420px) {
        .manager-tickets-title-col {
            max-width: calc(100% - 132px);
            width: calc(100% - 132px);
            padding-right: 0.45rem;
        }

        .manager-tickets-action-col {
            flex: 0 0 132px;
            max-width: 132px;
            width: 132px;
        }

        .manager-tickets-title {
            font-size: 0.82rem;
            white-space: normal;
            overflow: visible;
            text-overflow: clip;
            line-height: 1.18;
        }

        .manager-tickets-cta {
            min-height: 38px;
            padding: 0.56rem 0.6rem;
            font-size: 0.58rem;
            border-radius: 12px;
        }
    }
</style>
<div class="container-fluid py-4 manager-tickets-page">
    <div class="row">
        <div class="col-12">
            <div class="row manager-tickets-header-row">
                <div class="col-6 mb-4 manager-tickets-title-col">
                    <h6 class="mb-0 manager-tickets-title">Manage Employee Tickets</h6>
                </div>
                <div class="col-6 mb-4 manager-tickets-action-col">
                    <a href="create_ticket" class="btn mb-0 manager-tickets-cta">Raise a Ticket</a>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card mb-4 manager-tickets-card">
                <div class="card-body px-0 pt-0 pb-2 manager-tickets-shell">
                    <div class="table-responsive p-0 manager-tickets-wrap">
                        <!-- Salaries Table -->
                        <table class="table align-items-center mb-0 manager-tickets-table">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Subject</th>
                                    <th>Message</th>
                                    <th>Status</th>
                                    <th>Response</th>
                                    <th>Update</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($ticket = $result->fetch_assoc()): ?>
            <?php $status_class = 'status-' . strtolower(str_replace(' ', '-', trim($ticket['status']))); ?>
            <tr>
                <td><span class="manager-ticket-employee"><?= htmlspecialchars($ticket['employee_name']) ?></span></td>
                <td><span class="manager-ticket-subject"><?= htmlspecialchars($ticket['subject']) ?></span></td>
                <td><div class="manager-ticket-message"><?= nl2br(htmlspecialchars($ticket['message'])) ?></div></td>
                <td class="align-middle text-center text-sm manager-ticket-status"><span class="badge badge-sm <?= htmlspecialchars($status_class) ?>"><?= htmlspecialchars($ticket['status']) ?></span></td>
                <td><div class="manager-ticket-response"><?= nl2br(htmlspecialchars($ticket['response'])) ?></div></td>
                <td>
                    <button class="btn btn-sm btn-primary manager-ticket-action" data-bs-toggle="modal" data-bs-target="#updateTicketModal<?= $ticket['id'] ?>">Update</button>

                    <!-- Modal -->
                    <div class="modal fade manager-tickets-modal" id="updateTicketModal<?= $ticket['id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <form method="POST" class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Update Ticket</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                                    <label>Status</label>
                                    <select name="status" class="form-control">
                                        <option <?= $ticket['status'] == 'Open' ? 'selected' : '' ?>>Open</option>
                                        <option <?= $ticket['status'] == 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                        <option <?= $ticket['status'] == 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                                        <option <?= $ticket['status'] == 'Closed' ? 'selected' : '' ?>>Closed</option>
                                    </select>

                                    <label class="mt-3">Response</label>
                                    <textarea name="response" class="form-control"><?= htmlspecialchars($ticket['response']) ?></textarea>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" name="update_ticket" class="btn btn-success">Save</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </td>
            </tr>
        <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End Navbar -->
<?php include("footer.php") ?>

