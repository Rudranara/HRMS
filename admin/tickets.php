

<?php
include("header.php");
// Handle ticket update
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

// Optional filters
$year = $_GET['year'] ?? '';
$month = $_GET['month'] ?? '';

$query = "SELECT t.*, e.name AS employee_name, m.name AS manager_name 
          FROM tickets t 
          JOIN employees e ON t.employee_id = e.id 
          LEFT JOIN employees m ON t.manager_id = m.id 
          WHERE 1=1";

if (!empty($year)) {
    $query .= " AND YEAR(t.created_at) = ?";
}
if (!empty($month)) {
    $query .= " AND MONTH(t.created_at) = ?";
}

$query .= " ORDER BY t.created_at DESC";

$stmt = $conn->prepare($query);

// Bind params
if (!empty($year) && !empty($month)) {
    $stmt->bind_param("ii", $year, $month);
} elseif (!empty($year)) {
    $stmt->bind_param("i", $year);
} elseif (!empty($month)) {
    $stmt->bind_param("i", $month);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<div class="container-fluid py-4 ticket-page">
    <div class="ticket-topbar">
        <div class="ticket-topbar-grid">
            <div>
                <span class="ticket-section-label">Support Desk</span>
                <h6 class="ticket-topbar-title">Manage Employee Tickets</h6>
                <p class="ticket-topbar-copy">Review ticket conversations, update statuses, and respond to employee requests from one place.</p>
            </div>
            <div class="ticket-topbar-side">
                <span class="ticket-topbar-chip">Ticket Queue</span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card ticket-table-card mb-4">
                <div class="ticket-table-header">
                    <div>
                        <h6 class="ticket-table-title">All Tickets</h6>
                        <p class="ticket-table-copy">Current ticket records with assigned managers, latest responses, and status controls.</p>
                    </div>
                </div>
                <div class="table-responsive ticket-table-wrap">
                    <div class="ticket-table-wrapper">
                        <table class="ticket-table">
                            <thead class="ticket-table-head">
                                <tr class="ticket-table-row-header">
                                    <th class="ticket-table-th">Employee</th>
                                    <th class="ticket-table-th">Manager</th>
                                    <th class="ticket-table-th">Subject</th>
                                    <th class="ticket-table-th">Message</th>
                                    <th class="ticket-table-th">Status</th>
                                    <th class="ticket-table-th">Response</th>
                                    <th class="ticket-table-th">Update</th>
                                </tr>
                            </thead>
                            <tbody class="ticket-table-body">
                                <?php while ($ticket = $result->fetch_assoc()): ?>
                                <tr class="ticket-table-row">
                                    <td class="ticket-table-cell">
                                        <div class="ticket-person-block">
                                            <span class="ticket-person-name"><?= htmlspecialchars($ticket['employee_name']) ?></span>
                                            <span class="ticket-person-meta">Employee</span>
                                        </div>
                                    </td>
                                    <td class="ticket-table-cell">
                                        <div class="ticket-person-block">
                                            <span class="ticket-manager-name"><?= htmlspecialchars($ticket['manager_name']) ?></span>
                                            <span class="ticket-person-meta">Manager</span>
                                        </div>
                                    </td>
                                    <td class="ticket-table-cell">
                                        <div class="ticket-subject"><?= htmlspecialchars($ticket['subject']) ?></div>
                                    </td>
                                    <td class="ticket-table-cell">
                                        <div class="ticket-message"><?= nl2br(htmlspecialchars($ticket['message'])) ?></div>
                                    </td>
                                    <td class="ticket-table-cell">
                                        <?php
                                            $status = $ticket['status'];
                                            $statusClass = match($status) {
                                                'Open'        => 'ticket-status-open',
                                                'In Progress' => 'ticket-status-inprogress',
                                                'Resolved'    => 'ticket-status-resolved',
                                                'Closed'      => 'ticket-status-closed',
                                                default       => 'ticket-status-open',
                                            };
                                        ?>
                                        <span class="ticket-status-badge <?= $statusClass ?>"><?= htmlspecialchars($status) ?></span>
                                    </td>
                                    <td class="ticket-table-cell">
                                        <div class="ticket-response"><?= nl2br(htmlspecialchars($ticket['response'])) ?></div>
                                    </td>
                                    <td class="ticket-table-cell">
                                        <div class="ticket-actions">
                                            <button class="ticket-btn-update" data-bs-toggle="modal" data-bs-target="#updateTicketModal<?= $ticket['id'] ?>">
                                                <i class="fas fa-edit me-1"></i>Update
                                            </button>
                                        </div>

                                        <!-- Update Modal -->
                                        <div class="modal fade ticket-manage-modal" id="updateTicketModal<?= $ticket['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog ticket-modal-dialog">
                                                <form method="POST" class="modal-content ticket-modal-content">
                                                    <div class="ticket-modal-header">
                                                        <h5 class="ticket-modal-title">Update Ticket</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="ticket-modal-body">
                                                        <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">

                                                        <!-- Ticket Info Summary -->
                                                        <div class="ticket-modal-info">
                                                            <div class="ticket-modal-info-item">
                                                                <span class="ticket-modal-info-label">Employee</span>
                                                                <span class="ticket-modal-info-value"><?= htmlspecialchars($ticket['employee_name']) ?></span>
                                                            </div>
                                                            <div class="ticket-modal-info-item">
                                                                <span class="ticket-modal-info-label">Subject</span>
                                                                <span class="ticket-modal-info-value"><?= htmlspecialchars($ticket['subject']) ?></span>
                                                            </div>
                                                        </div>

                                                        <div class="ticket-form-group">
                                                            <label class="ticket-form-label">STATUS</label>
                                                            <select name="status" class="ticket-form-select">
                                                                <option <?= $ticket['status'] == 'Open' ? 'selected' : '' ?>>Open</option>
                                                                <option <?= $ticket['status'] == 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                                                <option <?= $ticket['status'] == 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                                                                <option <?= $ticket['status'] == 'Closed' ? 'selected' : '' ?>>Closed</option>
                                                            </select>
                                                        </div>

                                                        <div class="ticket-form-group">
                                                            <label class="ticket-form-label">RESPONSE</label>
                                                            <textarea name="response" class="ticket-form-textarea" rows="4"><?= htmlspecialchars($ticket['response']) ?></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="ticket-modal-footer">
                                                        <button type="button" class="ticket-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="update_ticket" class="ticket-btn-save">
                                                            <i class="fas fa-check me-2"></i>Save
                                                        </button>
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

<style>
    .ticket-page {
        padding-bottom: 1.5rem;
    }

    .ticket-topbar,
    .ticket-table-card {
        border: 1px solid rgba(87, 96, 108, 0.12);
        border-radius: 22px;
        box-shadow: 0 14px 34px rgba(31, 41, 55, 0.06);
        background: #fff;
    }

    .ticket-topbar {
        padding: 1.15rem 1.2rem;
        margin-bottom: 1rem;
    }

    .ticket-topbar-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 1rem;
        align-items: center;
    }

    .ticket-section-label {
        display: block;
        margin-bottom: 0.45rem;
        color: #6b7280;
        font-size: 0.74rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .ticket-topbar-title {
        margin: 0;
        color: #0f172a;
        font-size: 1.45rem;
        font-weight: 800;
        letter-spacing: -0.03em;
    }

    .ticket-topbar-copy {
        margin: 0.35rem 0 0;
        color: #6b7280;
        font-size: 0.92rem;
        max-width: 640px;
    }

    .ticket-topbar-side {
        display: flex;
        align-items: center;
        justify-content: flex-end;
    }

    .ticket-topbar-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 40px;
        padding: 0.55rem 1rem;
        border-radius: 14px;
        background: #f8fafc;
        border: 1px solid #dbe3ed;
        color: #475569;
        font-size: 0.8rem;
        font-weight: 700;
    }

    .ticket-table-card {
        overflow: hidden;
    }

    .ticket-table-header {
        padding: 1.15rem 1.2rem;
        border-bottom: 1px solid #eef2f7;
        background: #fff;
    }

    .ticket-table-title {
        margin: 0;
        color: #0f172a;
        font-size: 1rem;
        font-weight: 700;
    }

    .ticket-table-copy {
        margin: 0.35rem 0 0;
        color: #6b7280;
        font-size: 0.88rem;
    }

    .ticket-table-wrap {
        padding: 0 1.2rem 1.15rem;
    }

    .ticket-table-wrapper {
        overflow-x: auto;
    }

    .ticket-table {
        width: 100%;
        margin-bottom: 0;
        border-collapse: collapse;
    }

    .ticket-table-head th,
    .ticket-table-th {
        border-bottom: 1px solid #e8edf3;
        color: #6b7280;
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        padding: 1rem 0.95rem;
        white-space: nowrap;
        background: #f8fafc;
        text-align: left;
    }

    .ticket-table-body td,
    .ticket-table-cell {
        padding: 1rem 0.95rem;
        border-bottom: 1px solid #eef2f7;
        color: #1f2937;
        vertical-align: middle;
        font-size: 0.92rem;
        max-width: 230px;
    }

    .ticket-table-body tr:last-child td {
        border-bottom: none;
    }

    .ticket-table-row:hover {
        background: #fbfcfe;
    }

    .ticket-person-block {
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
    }

    .ticket-person-name,
    .ticket-subject {
        color: #0f172a;
        font-weight: 700;
    }

    .ticket-manager-name {
        color: #0f172a;
        font-weight: 600;
    }

    .ticket-person-meta {
        color: #94a3b8;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }

    .ticket-message,
    .ticket-response {
        color: #475569;
        font-size: 0.84rem;
        line-height: 1.55;
        max-height: 64px;
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
    }

    .ticket-status-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 104px;
        padding: 0.42rem 0.7rem;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        white-space: nowrap;
        border: 1px solid transparent;
    }

    .ticket-status-open {
        background: #e8f1ff;
        border-color: #cfe0ff;
        color: #345ea8;
    }

    .ticket-status-inprogress {
        background: #fff4da;
        border-color: #f8e2a8;
        color: #9a6b11;
    }

    .ticket-status-resolved {
        background: #e9f8ef;
        border-color: #cbeed9;
        color: #25744c;
    }

    .ticket-status-closed {
        background: #f8fafc;
        border-color: #dbe3ed;
        color: #475569;
    }

    .ticket-actions {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        white-space: nowrap;
    }

    .ticket-btn-update {
        min-height: 38px;
        padding: 0.5rem 0.9rem;
        border-radius: 12px;
        font-size: 0.76rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        background: linear-gradient(135deg, #161616 0%, #2d2d2d 100%);
        color: #fff;
        border: none;
        box-shadow: 0 12px 24px rgba(17, 24, 39, 0.18);
    }

    .ticket-btn-update:hover {
        background: linear-gradient(135deg, #101010 0%, #242424 100%);
        color: #fff;
    }

    .ticket-manage-modal .modal-dialog,
    .ticket-modal-dialog {
        max-width: 620px;
    }

    .ticket-manage-modal .modal-content,
    .ticket-modal-content {
        border: 1px solid rgba(87, 96, 108, 0.12);
        border-radius: 18px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        overflow: hidden;
    }

    .ticket-manage-modal .modal-header,
    .ticket-manage-modal .modal-footer,
    .ticket-modal-header,
    .ticket-modal-footer {
        border-color: #eef2f7;
        padding: 1rem 1.25rem;
    }

    .ticket-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #fff;
    }

    .ticket-modal-body {
        padding: 1.25rem;
    }

    .ticket-modal-title {
        color: #0f172a;
        font-size: 1rem;
        font-weight: 700;
        margin: 0;
    }

    .ticket-modal-info {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.9rem;
        padding: 0.95rem 1rem;
        margin-bottom: 1rem;
        border-radius: 16px;
        background: #f8fafc;
        border: 1px solid #e8edf3;
    }

    .ticket-modal-info-item {
        display: flex;
        flex-direction: column;
        gap: 0.3rem;
    }

    .ticket-modal-info-label,
    .ticket-form-label {
        display: block;
        margin-bottom: 0.45rem;
        color: #6b7280;
        font-size: 0.74rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .ticket-modal-info-value {
        color: #0f172a;
        font-size: 0.92rem;
        font-weight: 700;
    }

    .ticket-form-group {
        margin-bottom: 1rem;
    }

    .ticket-form-select,
    .ticket-form-textarea {
        width: 100%;
        border-radius: 14px;
        border: 1px solid #d8dee7;
        box-shadow: none;
        background: #fff;
        color: #1f2937;
        font-size: 0.92rem;
    }

    .ticket-form-select {
        min-height: 46px;
        padding: 0.7rem 0.95rem;
    }

    .ticket-form-textarea {
        min-height: 120px;
        padding: 0.8rem 0.95rem;
        resize: vertical;
    }

    .ticket-form-select:focus,
    .ticket-form-textarea:focus {
        border-color: #1e3a5f;
        box-shadow: 0 0 0 0.18rem rgba(30, 58, 95, 0.12);
        outline: none;
    }

    .ticket-modal-footer {
        display: flex;
        gap: 0.75rem;
        justify-content: flex-end;
        background: #fff;
    }

    .ticket-btn-cancel,
    .ticket-btn-save {
        min-height: 40px;
        padding: 0.56rem 1rem;
        border-radius: 14px;
        font-size: 0.8rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
    }

    .ticket-btn-cancel {
        background: #f3f4f6;
        color: #334155;
        border: none;
    }

    .ticket-btn-save {
        background: linear-gradient(135deg, #161616 0%, #2d2d2d 100%);
        color: #fff;
        border: none;
        box-shadow: 0 12px 24px rgba(17, 24, 39, 0.18);
    }

    .ticket-btn-save:hover {
        background: linear-gradient(135deg, #101010 0%, #242424 100%);
        color: #fff;
    }

    @media (max-width: 991.98px) {
        .ticket-topbar-grid,
        .ticket-actions {
            grid-template-columns: 1fr;
            flex-direction: column;
            align-items: stretch;
        }

        .ticket-topbar-side {
            justify-content: stretch;
        }

        .ticket-topbar-chip,
        .ticket-actions > * {
            width: 100%;
        }
    }

    @media (max-width: 767.98px) {
        .ticket-table-wrap {
            padding: 0 0.85rem 0.95rem;
        }

        .ticket-modal-dialog {
            margin: 0.75rem;
        }

        .ticket-modal-info {
            grid-template-columns: 1fr;
        }
    }
</style>
<!-- End Navbar -->
<?php include("footer.php") ?>
