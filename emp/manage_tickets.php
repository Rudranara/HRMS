<?php
include("header.php");

// Check if employee is logged in
if (!isset($_SESSION['employee_id'])) {
    echo "<div class='alert alert-danger'>You must be logged in to view your tickets.</div>";
    exit;
}

require 'db_connection.php';

$employee_id = $_SESSION['employee_id'];

// Handle delete (optional: if you allow employees to delete their tickets)
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM tickets WHERE id = ? AND employee_id = ?");
    $stmt->bind_param("ii", $delete_id, $employee_id);
    if ($stmt->execute()) {
        echo "<div class='alert alert-success'>Ticket deleted successfully!</div>";
    } else {
        echo "<div class='alert alert-danger'>Failed to delete ticket.</div>";
    }
    $stmt->close();
}

// Fetch tickets for logged-in employee
$stmt = $conn->prepare("SELECT * FROM tickets WHERE employee_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<style>
    :root {
        --ticket-shell-bg: linear-gradient(180deg, #f8fafc 0%, #eef3f8 100%);
        --ticket-shell-border: rgba(148, 163, 184, 0.18);
        --ticket-shell-shadow: 0 24px 56px rgba(15, 23, 42, 0.08);
    }

    .manage-tickets-page {
        padding-top: 0.95rem !important;
        padding-bottom: 1.2rem !important;
    }

    .manage-tickets-title {
        margin: 0;
        color: #0f172a;
        font-size: 1.15rem;
        font-weight: 800;
        letter-spacing: -0.02em;
    }

    .manage-tickets-header-row {
        align-items: center;
    }

    .manage-tickets-title-col {
        display: flex;
        align-items: center;
    }

    .manage-tickets-action-col {
        text-align: right;
    }

    .manage-tickets-actions {
        display: inline-flex;
        flex-wrap: wrap;
        gap: 0.55rem;
        justify-content: flex-end;
    }

    .manage-tickets-cta {
        min-height: 46px;
        padding: 0.75rem 1rem;
        border-radius: 16px;
        border: 0;
        box-shadow: 0 16px 28px rgba(15, 23, 42, 0.12);
        font-size: 0.82rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .manage-tickets-cta.manage-tickets-dark {
        background: linear-gradient(135deg, #111827 0%, #1f2937 100%) !important;
        color: #ffffff !important;
    }

    .manage-tickets-cta.manage-tickets-navy {
        background: linear-gradient(135deg, #123b76 0%, #1f4c8f 100%) !important;
        color: #ffffff !important;
    }

    .manage-tickets-card {
        border: 1px solid var(--ticket-shell-border);
        border-radius: 28px;
        background: var(--ticket-shell-bg);
        box-shadow: var(--ticket-shell-shadow);
        overflow: hidden;
    }

    .manage-tickets-shell {
        background: #ffffff;
    }

    .manage-tickets-wrap {
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
    }

    .manage-tickets-table {
        margin-bottom: 0;
        min-width: 980px;
    }

    .manage-tickets-table thead th {
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

    .manage-tickets-table tbody td {
        padding: 1rem;
        border-bottom: 1px solid #eef2f7;
        vertical-align: middle;
        color: #1f2937;
        font-size: 0.88rem;
    }

    .manage-tickets-table tbody tr:hover {
        background: #fbfdff;
    }

    .manage-tickets-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .manage-tickets-subject {
        font-weight: 700;
        color: #0f172a;
    }

    .manage-tickets-message,
    .manage-tickets-response {
        max-width: 230px;
        color: #64748b;
        line-height: 1.55;
    }

    .manage-tickets-status {
        text-align: center;
    }

    .manage-tickets-status .badge {
        border-radius: 999px;
        padding: 0.52rem 0.82rem;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        border: 1px solid transparent;
        box-shadow: none;
    }

    .manage-tickets-status .status-open {
        background: #fff7db !important;
        color: #b45309 !important;
        border-color: #f8df9c;
    }

    .manage-tickets-status .status-in-progress {
        background: #e8f0ff !important;
        color: #1d4ed8 !important;
        border-color: #bfd4ff;
    }

    .manage-tickets-status .status-closed,
    .manage-tickets-status .status-resolved {
        background: #ecfdf3 !important;
        color: #15803d !important;
        border-color: #bbf7d0;
    }

    .manage-tickets-date {
        color: #64748b;
        font-weight: 600;
        white-space: nowrap;
    }

    .manage-tickets-action {
        min-height: 36px;
        padding: 0.58rem 0.78rem;
        border-radius: 12px;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        box-shadow: none !important;
    }

    .manage-tickets-action.btn-info {
        background: #e9f2ff;
        border-color: #c7dafc;
        color: #1d4f91;
    }

    .manage-tickets-action.btn-info:hover,
    .manage-tickets-action.btn-info:focus {
        background: #dce9ff;
        border-color: #b5cffd;
        color: #153d74;
    }

    .manage-tickets-modal .modal-content {
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 24px;
        box-shadow: 0 26px 60px rgba(15, 23, 42, 0.16);
        overflow: hidden;
    }

    .manage-tickets-modal .modal-header,
    .manage-tickets-modal .modal-footer {
        background: #ffffff;
        border-color: #eef2f7;
    }

    .manage-tickets-modal .modal-body {
        background: #f8fafc;
    }

    .manage-tickets-modal .modal-title {
        color: #0f172a;
        font-size: 1rem;
        font-weight: 800;
    }

    .manage-tickets-modal .modal-body p {
        margin-bottom: 0.85rem;
        color: #334155;
        line-height: 1.6;
    }

    @media (max-width: 767.98px) {
        .manage-tickets-page {
            padding-top: 0.6rem !important;
            padding-left: 0.3rem !important;
            padding-right: 0.3rem !important;
            padding-bottom: 0.85rem !important;
        }

        .manage-tickets-header-row {
            flex-wrap: nowrap;
            align-items: center;
            min-width: 0;
        }

        .manage-tickets-title-col {
            flex: 1 1 auto;
            max-width: calc(100% - 188px);
            width: calc(100% - 188px);
            margin-bottom: 0.85rem !important;
            padding-right: 0.45rem;
            min-width: 0;
        }

        .manage-tickets-action-col {
            flex: 0 0 188px;
            max-width: 188px;
            width: 188px;
            margin-bottom: 0.85rem !important;
            text-align: right !important;
        }

        .manage-tickets-actions {
            width: 100%;
            gap: 0.32rem;
            justify-content: flex-end;
            flex-wrap: nowrap;
        }

        .manage-tickets-actions .manage-tickets-cta {
            flex: 0 0 auto;
        }

        .manage-tickets-title {
            font-size: 0.9rem;
            line-height: 1.18;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .manage-tickets-cta {
            min-height: 38px;
            padding: 0.48rem 0.54rem;
            border-radius: 12px;
            font-size: 0.56rem;
            letter-spacing: 0.02em;
            box-shadow: none;
        }

        .manage-tickets-card {
            border-radius: 22px;
        }

        .manage-tickets-table thead th,
        .manage-tickets-table tbody td {
            padding: 0.82rem 0.78rem;
        }
    }

    @media (max-width: 420px) {
        .manage-tickets-title-col {
            max-width: calc(100% - 176px);
            width: calc(100% - 176px);
        }

        .manage-tickets-action-col {
            flex: 0 0 176px;
            max-width: 176px;
            width: 176px;
        }

        .manage-tickets-actions {
            gap: 0.28rem;
        }

        .manage-tickets-cta {
            min-height: 34px;
            padding: 0.44rem 0.5rem;
            font-size: 0.52rem;
            letter-spacing: 0.01em;
            border-radius: 11px;
        }

        .manage-tickets-title {
            font-size: 0.84rem;
        }
    }
</style>

<div class="container-fluid py-4 manage-tickets-page">
    <div class="row">
        <div class="col-12">
            <div class="row manage-tickets-header-row">
                <div class="col-6 mb-4 manage-tickets-title-col">
                    <h6 class="mb-0 manage-tickets-title">My Raised Tickets</h6>
                </div>
                <div class="col-6 mb-4 manage-tickets-action-col">
                    <div class="manage-tickets-actions">
                        <a href="tickets" class="btn mb-0 manage-tickets-cta manage-tickets-dark">raised Ticket</a>
                        <a href="create_ticket" class="btn mb-0 manage-tickets-cta manage-tickets-navy">Create Ticket</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card mb-4 manage-tickets-card">
                <div class="card-body px-0 pt-0 pb-2 manage-tickets-shell">
                    <div class="table-responsive p-0 manage-tickets-wrap">
                        <table class="table align-items-center mb-0 manage-tickets-table">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Message</th>
                                    <th>Status</th>
                                    <th>Response</th>
                                    <th>Created</th>
                                    <th>Updated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <?php $status_class = 'status-' . strtolower(str_replace(' ', '-', trim($row['status']))); ?>
                                    <tr>
                                        <td><span class="manage-tickets-subject"><?= htmlspecialchars($row['subject']) ?></span></td>
                                        <td><div class="manage-tickets-message"><?= htmlspecialchars($row['message']) ?></div></td>
                                        <td class="align-middle text-center text-sm manage-tickets-status"><span class="badge badge-sm <?= htmlspecialchars($status_class) ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                                        <td><div class="manage-tickets-response"><?= nl2br(htmlspecialchars($row['response'])) ?></div></td>
                                        <td><span class="manage-tickets-date"><?= date('Y-m-d', strtotime($row['created_at'])) ?></span></td>
                                        <td><span class="manage-tickets-date"><?= date('Y-m-d', strtotime($row['updated_at'])) ?></span></td>
                                        <td>
                                            <a href="javascript:void(0);" 
                                               class="btn btn-info btn-sm manage-tickets-action" 
                                               onclick="openTicketViewModal(<?= $row['id'] ?>)">View</a>
                                            <!-- Uncomment below if delete is allowed -->
                                            <!-- <a href="?delete_id=<?= $row['id'] ?>" class="btn btn-danger btn-sm">Delete</a> -->
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

<!-- Ticket View Modal -->
<div class="modal fade manage-tickets-modal" id="ticketViewModal" tabindex="-1" aria-labelledby="ticketViewModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ticket Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>Subject:</strong> <span id="ticketSubject"></span></p>
                <p><strong>Message:</strong> <span id="ticketMessage"></span></p>
                <p><strong>Status:</strong> <span id="ticketStatus"></span></p>
                <p><strong>Response:</strong> <span id="ticketResponse"></span></p>
                <p><strong>Created At:</strong> <span id="ticketCreatedAt"></span></p>
                <p><strong>Updated At:</strong> <span id="ticketUpdatedAt"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function openTicketViewModal(id) {
    fetch(`fetch_ticket_details?id=${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('ticketSubject').innerText = data.subject;
            document.getElementById('ticketMessage').innerText = data.message;
            document.getElementById('ticketStatus').innerText = data.status;
            document.getElementById('ticketResponse').innerText = data.response;
            document.getElementById('ticketCreatedAt').innerText = data.created_at;
            document.getElementById('ticketUpdatedAt').innerText = data.updated_at;

            var modal = new bootstrap.Modal(document.getElementById('ticketViewModal'));
            modal.show();
        });
}
</script>

<?php include("footer.php"); ?>
