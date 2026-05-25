<?php include("header.php"); ?>
<?php
$message = '';
$message_type = '';

// Handle delete expense request
if (isset($_POST['delete_expense'])) {
    $expense_id = isset($_POST['expense_id']) ? (int) $_POST['expense_id'] : 0;
    if ($expense_id > 0) {
        $stmt = $conn->prepare("DELETE FROM expenses WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $expense_id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $message = 'Expense deleted successfully!';
            $message_type = 'success';
        } else {
            $message = 'Expense not found or already deleted.';
            $message_type = 'error';
        }
    } else {
        $message = 'Invalid expense selected for deletion.';
        $message_type = 'error';
    }
}

// Handle update expense request
if (isset($_POST['update_expense'])) {
    $expense_id = $_POST['expense_id'];
    $expense_type = $_POST['expense_type'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $amount = $_POST['amount'];
    $quantity = $_POST['quantity'];
    $unit = $_POST['unit'];
    $status = $_POST['status'];
    $approved_amount = $_POST['approved_amount'] ?? null;
    $reject_reason = $_POST['reject_reason'] ?? null;

    // Update expense details
    $stmt = $conn->prepare("UPDATE expenses SET expense_type = ?, title = ?, description = ?, amount = ?, quantity = ?, unit = ?, status = ?, approved_amount = ?, reject_reason = ? WHERE id = ?");
    $stmt->bind_param("sssisssisi", $expense_type, $title, $description, $amount, $quantity, $unit, $status, $approved_amount, $reject_reason, $expense_id);
    $stmt->execute();

    // Handle new document uploads
    if (!empty($_FILES['new_documents']['name'][0])) {
        foreach ($_FILES['new_documents']['name'] as $key => $fileName) {
            $fileTmp = $_FILES['new_documents']['tmp_name'][$key];
            $filePath = "../uploads/expenses/" . basename($fileName);
            if (move_uploaded_file($fileTmp, $filePath)) {
                $stmt = $conn->prepare("INSERT INTO expense_documents (expense_id, file_path) VALUES (?, ?)");
                $stmt->bind_param("is", $expense_id, $filePath);
                $stmt->execute();
            }
        }
    }
}

// Handle delete document request
if (isset($_POST['delete_document'])) {
    $document_id = $_POST['document_id'];
    $conn->query("DELETE FROM expense_documents WHERE id = $document_id");
}

// Fetch expenses with pagination
$rows_per_page = 10;
$search = trim($_GET['search'] ?? '');
$current_page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$current_page = max(1, $current_page);
$offset = ($current_page - 1) * $rows_per_page;
$search_like = '%' . $search . '%';

if ($search !== '') {
    $count_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM expenses lr
                                  JOIN employees e ON lr.employee_id = e.id
                                  WHERE lr.title LIKE ? OR lr.expense_type LIKE ?");
    $count_stmt->bind_param("ss", $search_like, $search_like);
} else {
    $count_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM expenses lr
                                  JOIN employees e ON lr.employee_id = e.id");
}

$count_stmt->execute();
$total_records = (int) ($count_stmt->get_result()->fetch_assoc()['total'] ?? 0);
$total_pages = $total_records > 0 ? (int) ceil($total_records / $rows_per_page) : 1;
$current_page = min($current_page, $total_pages);
$offset = ($current_page - 1) * $rows_per_page;

if ($search !== '') {
    $stmt = $conn->prepare("SELECT lr.*, e.name AS employee_name, e.employee_id FROM expenses lr
                            JOIN employees e ON lr.employee_id = e.id
                            WHERE lr.title LIKE ? OR lr.expense_type LIKE ?
                            ORDER BY lr.created_at DESC
                            LIMIT ?, ?");
    $stmt->bind_param("ssii", $search_like, $search_like, $offset, $rows_per_page);
} else {
    $stmt = $conn->prepare("SELECT lr.*, e.name AS employee_name, e.employee_id FROM expenses lr
                            JOIN employees e ON lr.employee_id = e.id
                            ORDER BY lr.created_at DESC
                            LIMIT ?, ?");
    $stmt->bind_param("ii", $offset, $rows_per_page);
}

$stmt->execute();
$result = $stmt->get_result();
$page_query = ['page' => $current_page];
if ($search !== '') {
    $page_query['search'] = $search;
}
$page_action_url = 'manage_expenses?' . http_build_query($page_query);

$pagination_pages = [];
if ($total_pages <= 7) {
    $pagination_pages = range(1, $total_pages);
} else {
    $pagination_pages = [1, 2, $current_page - 1, $current_page, $current_page + 1, $total_pages - 1, $total_pages];
    $pagination_pages = array_values(array_unique(array_filter($pagination_pages, function ($page) use ($total_pages) {
        return $page >= 1 && $page <= $total_pages;
    })));
    sort($pagination_pages);
}
?>
<div class="container-fluid py-4">
    <!-- Expense Management Header -->
    <div class="expense-page-header mb-4">
        <div class="expense-header-top">
            <div class="expense-header-content">
                <h2 class="expense-page-title">Manage Daily Expenses</h2>
                <p class="expense-page-subtitle">Track and manage all expense requests</p>
            </div>
            <!-- <a href="add_expense" class="expense-btn-add">
                <i class="fas fa-plus me-2"></i>Add Expense
            </a> -->
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <?php if ($message !== ''): ?>
                <div class="expense-alert expense-alert-<?= htmlspecialchars($message_type) ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            <!-- Filter Card -->
            <div class="expense-filter-card mb-4">
                <form method="GET" class="expense-filter-form">
                    <div class="expense-filter-inputs">
                        <input type="text" name="search" class="expense-filter-input" placeholder="Search by Expense Title or Type" value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="expense-btn-search">
                            <i class="fas fa-search me-2"></i>Search
                        </button>
                    </div>
                </form>
            </div>

            <!-- Expenses Table Card -->
            <div class="expense-table-card">
                <div class="expense-table-header">
                    <h6 class="expense-table-title">Expense Records</h6>
                </div>
                <div class="table-responsive p-0">
                    <div class="expense-table-wrapper">
                    <div class="expense-table-wrapper">
                        <table class="expense-table">
                            <thead class="expense-table-head">
                                <tr class="expense-table-row-header">
                                    <th class="expense-table-th">Employee</th>
                                    <th class="expense-table-th">Date</th>
                                    <th class="expense-table-th">Expense Type</th>
                                    <th class="expense-table-th">Title</th>
                                    <th class="expense-table-th">Amount</th>
                                    <th class="expense-table-th">Status</th>
                                    <th class="expense-table-th">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="expense-table-body">
                                <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr class="expense-table-row">
                                        <td class="expense-table-cell">
                                            <div class="expense-employee-info">
                                                <div class="expense-employee-details">
                                                    <div class="expense-employee-name"><?= htmlspecialchars($row['employee_name']) ?></div>
                                                    <div class="expense-employee-id"><?= htmlspecialchars($row['employee_id']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="expense-table-cell"><?= $row['expense_date'] ?></td>
                                        <td class="expense-table-cell"><?= $row['expense_type'] ?></td>
                                        <td class="expense-table-cell"><?= $row['title'] ?></td>
                                        <td class="expense-table-cell expense-amount">₹<?= number_format($row['amount'], 2) ?></td>
                                        <td class="expense-table-cell">
                                            <span class="expense-status-badge expense-status-<?= strtolower($row['status']) ?>">
                                                <?= ucfirst($row['status']) ?>
                                            </span>
                                        </td>
                                        <td class="expense-table-cell">
                                            <div class="expense-action-group">
                                                <button class="expense-btn-view" data-bs-toggle="modal" data-bs-target="#editExpenseModal<?= $row['id'] ?>" title="View & Edit">
                                                    <i class="fas fa-eye me-1"></i>View
                                                </button>
                                                <form action="<?= htmlspecialchars($page_action_url) ?>" method="POST" style="display: inline;">
                                                    <input type="hidden" name="expense_id" value="<?= $row['id'] ?>">
                                                    <button type="submit" name="delete_expense" class="expense-btn-delete" title="Delete expense" onclick="return confirm('Delete this expense?');">
                                                        <i class="fas fa-trash me-1"></i>Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <!-- Edit Modal -->
                                    <div class="modal fade" id="editExpenseModal<?= $row['id'] ?>" tabindex="-1" aria-labelledby="editExpenseModalLabel" aria-hidden="true">
                                        <div class="modal-dialog modal-lg expense-modal-dialog">
                                            <div class="modal-content expense-modal-content">
                                                <form action="<?= htmlspecialchars($page_action_url) ?>" method="POST" enctype="multipart/form-data">
                                                    <div class="expense-modal-header">
                                                        <h5 class="expense-modal-title">Expense Details</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>

                                                    <div class="expense-modal-body">
                                                        <input type="hidden" name="expense_id" value="<?= $row['id'] ?>">

                                                        <!-- Expense Information Section -->
                                                        <div class="expense-info-section">
                                                            <h6 class="expense-section-title">Expense Information</h6>
                                                            <div class="expense-info-grid">
                                                                <div class="expense-info-item">
                                                                    <label class="expense-info-label">Expense Type</label>
                                                                    <div class="expense-info-value"><?= htmlspecialchars($row['expense_type']) ?></div>
                                                                </div>
                                                                <div class="expense-info-item">
                                                                    <label class="expense-info-label">Expense Title</label>
                                                                    <div class="expense-info-value"><?= htmlspecialchars($row['title']) ?></div>
                                                                </div>
                                                                <div class="expense-info-item">
                                                                    <label class="expense-info-label">Unit</label>
                                                                    <div class="expense-info-value"><?= htmlspecialchars($row['unit']) ?></div>
                                                                </div>
                                                                <div class="expense-info-item">
                                                                    <label class="expense-info-label">Amount</label>
                                                                    <div class="expense-info-value expense-amount-highlight">₹<?= number_format($row['amount'], 2) ?></div>
                                                                </div>
                                                                <div class="expense-info-item">
                                                                    <label class="expense-info-label">Quantity</label>
                                                                    <div class="expense-info-value"><?= htmlspecialchars($row['quantity']) ?></div>
                                                                </div>
                                                                <div class="expense-info-item">
                                                                    <label class="expense-info-label">Description</label>
                                                                    <textarea name="description" id="description" class="expense-textarea" rows="3" required><?= $row['description'] ?></textarea>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <input type="hidden" name="expense_type" id="expense_type" value="<?= $row['expense_type'] ?>" required>
                                                        <input type="hidden" name="title" id="title" value="<?= $row['title'] ?>" required>
                                                        <input type="hidden" name="amount" id="amount" value="<?= $row['amount'] ?>" required>
                                                        <input type="hidden" name="quantity" id="quantity" value="<?= $row['quantity'] ?>" required>
                                                        <input type="hidden" name="unit" id="unit" value="<?= $row['unit'] ?>" required>

                                                        <!-- Status & Approval Section -->
                                                        <div class="expense-approval-section">
                                                            <h6 class="expense-section-title">Status & Approval</h6>
                                                            <div class="expense-form-group">
                                                                <label for="status<?= $row['id'] ?>" class="expense-form-label">STATUS</label>
                                                                <select name="status" id="status<?= $row['id'] ?>" class="expense-form-select status-select" data-id="<?= $row['id'] ?>" required>
                                                                    <option value="Pending" <?= $row['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                                    <option value="Approved" <?= $row['status'] == 'Approved' ? 'selected' : '' ?>>Approved</option>
                                                                    <option value="Reimbursed" <?= $row['status'] == 'Reimbursed' ? 'selected' : '' ?>>Reimbursed</option>
                                                                    <option value="Rejected" <?= $row['status'] == 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                                                                </select>
                                                            </div>

                                                            <div class="expense-form-group approved-section" id="approvedSection<?= $row['id'] ?>" style="display: <?= in_array($row['status'], ['Approved', 'Reimbursed']) ? 'block' : 'none' ?>;">
                                                                <label for="approved_amount<?= $row['id'] ?>" class="expense-form-label">APPROVED AMOUNT</label>
                                                                <input type="number" name="approved_amount" id="approved_amount<?= $row['id'] ?>" class="expense-form-input" value="<?= $row['approved_amount'] ?? $row['amount'] ?>" step="0.01" <?= $row['status'] == 'Approved' ? 'readonly' : '' ?>>
                                                            </div>

                                                            <div class="expense-form-group Reimbursed-section" id="ReimbursedSection<?= $row['id'] ?>" style="display: <?= $row['status'] == 'Reimbursed' ? 'block' : 'none' ?>;">
                                                                <label for="reject_reason" class="expense-form-label">REIMBURSED REASON</label>
                                                                <textarea name="reject_reason" id="reject_reason" class="expense-textarea"><?= $row['reject_reason'] ?? '' ?></textarea>
                                                            </div>
                                                        </div>

                                                        <!-- Documents Section -->
                                                        <div class="expense-documents-section">
                                                            <h6 class="expense-section-title">Documents</h6>
                                                            <div class="expense-form-group">
                                                                <label for="new_documents" class="expense-form-label">ADD NEW DOCUMENTS</label>
                                                                <input type="file" name="new_documents[]" id="new_documents" class="expense-form-input" multiple>
                                                            </div>

                                                            <div class="expense-current-docs">
                                                                <label class="expense-form-label">CURRENT DOCUMENTS</label>
                                                                <div class="expense-docs-list">
                                                                    <?php
                                                                    $documents = $conn->query("SELECT * FROM expense_documents WHERE expense_id = " . $row['id']);
                                                                    if ($documents->num_rows > 0) {
                                                                        while ($doc = $documents->fetch_assoc()) :
                                                                    ?>
                                                                        <div class="expense-doc-item">
                                                                            <embed src="<?= htmlspecialchars($doc['file_path']) ?>" class="expense-doc-preview" type="application/pdf">
                                                                            <div class="expense-doc-actions">
                                                                                <a href="<?= htmlspecialchars($doc['file_path']) ?>" class="expense-btn-doc-download" download>
                                                                                    <i class="fas fa-download"></i>Download
                                                                                </a>
                                                                                <form action="<?= htmlspecialchars($page_action_url) ?>" method="POST" style="display: inline;">
                                                                                    <input type="hidden" name="document_id" value="<?= $doc['id'] ?>">
                                                                                    <button type="submit" name="delete_document" class="expense-btn-doc-delete" onclick="return confirm('Delete this document?');">
                                                                                        <i class="fas fa-trash"></i>Delete
                                                                                    </button>
                                                                                </form>
                                                                            </div>
                                                                        </div>
                                                                    <?php 
                                                                        endwhile;
                                                                    } else {
                                                                        echo '<div class="expense-no-docs">No documents attached</div>';
                                                                    }
                                                                    ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="expense-modal-footer">
                                                        <button type="button" class="expense-btn-close" data-bs-dismiss="modal">Close</button>
                                                        <button type="submit" name="update_expense" class="expense-btn-save">
                                                            <i class="fas fa-check me-2"></i>Save Changes
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                    </div>


                                <?php endwhile; ?>
                                <?php else: ?>
                                    <tr class="expense-table-row">
                                        <td class="expense-table-cell expense-empty-state" colspan="7">No expense records found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php if ($total_pages > 1): ?>
                    <div class="expense-pagination-wrap">
                        <div class="expense-pagination-info">
                            Page <?= $current_page ?> of <?= $total_pages ?>
                        </div>
                        <div class="expense-pagination">
                            <?php
                            $prev_query = ['page' => $current_page - 1];
                            $next_query = ['page' => $current_page + 1];
                            if ($search !== '') {
                                $prev_query['search'] = $search;
                                $next_query['search'] = $search;
                            }
                            ?>
                            <a class="expense-pagination-link <?= $current_page <= 1 ? 'expense-pagination-disabled' : '' ?>" href="<?= $current_page > 1 ? 'manage_expenses?' . htmlspecialchars(http_build_query($prev_query)) : '#' ?>">Previous</a>
                            <?php $last_rendered_page = 0; ?>
                            <?php foreach ($pagination_pages as $page): ?>
                                <?php if ($last_rendered_page > 0 && $page - $last_rendered_page > 1): ?>
                                    <span class="expense-pagination-ellipsis">...</span>
                                <?php endif; ?>
                                <?php $loop_query = ['page' => $page]; ?>
                                <?php if ($search !== '') $loop_query['search'] = $search; ?>
                                <a class="expense-pagination-link <?= $page === $current_page ? 'expense-pagination-active' : '' ?>" href="manage_expenses?<?= htmlspecialchars(http_build_query($loop_query)) ?>"><?= $page ?></a>
                                <?php $last_rendered_page = $page; ?>
                            <?php endforeach; ?>
                            <a class="expense-pagination-link <?= $current_page >= $total_pages ? 'expense-pagination-disabled' : '' ?>" href="<?= $current_page < $total_pages ? 'manage_expenses?' . htmlspecialchars(http_build_query($next_query)) : '#' ?>">Next</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>


<style>
    /* ==================== EXPENSE PAGE HEADER ==================== */
    .expense-page-header {
        display: flex;
        flex-direction: column;
        gap: 0;
    }

    .expense-header-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
    }

    .expense-header-content {
        flex: 1;
    }

    .expense-page-title {
        font-size: 28px;
        font-weight: 700;
        color: #111827;
        margin: 0 0 4px 0;
        letter-spacing: -0.5px;
    }

    .expense-page-subtitle {
        font-size: 13px;
        color: #6b7280;
        margin: 0;
        font-weight: 500;
    }

    .expense-btn-add {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 10px 20px;
        background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        box-shadow: 0 4px 15px rgba(17, 24, 39, 0.2);
        transition: all 0.3s ease;
        text-decoration: none;
        white-space: nowrap;
    }

    .expense-btn-add:hover {
        background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
        box-shadow: 0 6px 20px rgba(17, 24, 39, 0.3);
        color: white;
        text-decoration: none;
        transform: translateY(-2px);
    }

    /* ==================== FILTER CARD ==================== */
    .expense-filter-card {
        background: white;
        border: 1px solid #e5eaf1;
        border-radius: 24px;
        padding: 20px 24px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.07);
    }

    .expense-alert {
        margin-bottom: 16px;
        padding: 14px 18px;
        border-radius: 16px;
        font-size: 14px;
        font-weight: 700;
    }

    .expense-alert-success {
        border: 1px solid #86efac;
        background: #dcfce7;
        color: #166534;
    }

    .expense-alert-error {
        border: 1px solid #fecaca;
        background: #fee2e2;
        color: #991b1b;
    }

    .expense-filter-form {
        margin: 0;
    }

    .expense-filter-inputs {
        display: flex;
        gap: 12px;
        align-items: center;
    }

    .expense-filter-input {
        flex: 1;
        height: 46px;
        border: 1px solid #d7deea;
        border-radius: 14px;
        padding: 12px 16px;
        font-size: 13px;
        background-color: #f8fafc;
        color: #111827;
        transition: all 0.3s ease;
    }

    .expense-filter-input:focus {
        outline: none;
        border-color: #9ca3af;
        background-color: white;
        box-shadow: 0 0 0 3px rgba(17, 24, 39, 0.05);
    }

    .expense-btn-search {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 12px 24px;
        height: 46px;
        background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
        color: white;
        border: none;
        border-radius: 14px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(17, 24, 39, 0.15);
        transition: all 0.3s ease;
        white-space: nowrap;
    }

    .expense-btn-search:hover {
        background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
        box-shadow: 0 6px 16px rgba(17, 24, 39, 0.2);
        transform: translateY(-2px);
    }

    /* ==================== TABLE CARD ==================== */
    .expense-table-card {
        background: white;
        border: 1px solid #e5eaf1;
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.07);
    }

    .expense-table-header {
        padding: 20px 24px;
        border-bottom: 1px solid #e5eaf1;
        background-color: #fafbfc;
    }

    .expense-table-title {
        font-size: 15px;
        font-weight: 700;
        color: #111827;
        margin: 0;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .expense-table-wrapper {
        overflow-x: auto;
    }

    /* ==================== TABLE STYLING ==================== */
    .expense-table {
        width: 100%;
        border-collapse: collapse;
        margin: 0;
    }

    .expense-table-head {
        background-color: #f8fafc;
    }

    .expense-table-row-header {
        border-bottom: 2px solid #e5eaf1;
    }

    .expense-table-th {
        padding: 14px 16px;
        text-align: left;
        font-size: 12px;
        font-weight: 700;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        background-color: #f8fafc;
        border: none;
    }

    .expense-table-body {
        background-color: white;
    }

    .expense-table-row {
        border-bottom: 1px solid #e5eaf1;
        transition: background-color 0.2s ease;
    }

    .expense-table-row:hover {
        background-color: #f8fafc;
    }

    .expense-table-cell {
        padding: 14px 16px;
        font-size: 13px;
        color: #111827;
        vertical-align: middle;
    }

    .expense-empty-state {
        text-align: center;
        color: #6b7280;
        padding: 28px 16px;
    }

    .expense-employee-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .expense-employee-details {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .expense-employee-name {
        font-size: 13px;
        font-weight: 600;
        color: #111827;
    }

    .expense-employee-id {
        font-size: 12px;
        color: #9ca3af;
    }

    .expense-amount {
        font-weight: 600;
        color: #059669;
    }

    /* ==================== STATUS BADGES ==================== */
    .expense-status-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        white-space: nowrap;
    }

    .expense-status-pending {
        background-color: #dbeafe;
        color: #1e40af;
    }

    .expense-status-approved {
        background-color: #dcfce7;
        color: #166534;
    }

    .expense-status-reimbursed {
        background-color: #fef3c7;
        color: #92400e;
    }

    .expense-status-rejected {
        background-color: #fee2e2;
        color: #991b1b;
    }

    /* ==================== ACTION BUTTONS ==================== */
    .expense-action-group {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: nowrap;
        white-space: nowrap;
    }

    .expense-btn-view,
    .expense-btn-delete {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 8px 12px;
        border: none;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        white-space: nowrap;
        text-decoration: none;
    }

    .expense-btn-view {
        background-color: #111827;
        color: white;
    }

    .expense-btn-view:hover {
        background-color: #1f2937;
        color: white;
    }

    .expense-btn-delete {
        background-color: #fee2e2;
        color: #991b1b;
    }

    .expense-btn-delete:hover {
        background-color: #fecaca;
        color: #7f1d1d;
    }

    /* ==================== MODAL STYLING ==================== */
    .expense-modal-dialog {
        max-width: 720px;
    }

    .expense-modal-content {
        border: 1px solid #e5eaf1;
        border-radius: 16px;
        box-shadow: 0 20px 60px rgba(15, 23, 42, 0.15);
    }

    .expense-modal-header {
        padding: 24px;
        border-bottom: 1px solid #e5eaf1;
        background-color: #fafbfc;
    }

    .expense-modal-title {
        font-size: 18px;
        font-weight: 700;
        color: #111827;
        margin: 0;
    }

    .expense-modal-body {
        padding: 24px;
        max-height: 70vh;
        overflow-y: auto;
    }

    /* ==================== MODAL SECTIONS ==================== */
    .expense-info-section,
    .expense-approval-section,
    .expense-documents-section {
        margin-bottom: 24px;
    }

    .expense-section-title {
        font-size: 14px;
        font-weight: 700;
        color: #111827;
        margin: 0 0 16px 0;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    /* ==================== INFO GRID ==================== */
    .expense-info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }

    @media (max-width: 768px) {
        .expense-info-grid {
            grid-template-columns: 1fr;
        }
    }

    .expense-info-item {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .expense-info-label {
        font-size: 12px;
        font-weight: 700;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .expense-info-value {
        font-size: 14px;
        font-weight: 500;
        color: #111827;
        padding: 12px 14px;
        background-color: #f8fafc;
        border: 1px solid #e5eaf1;
        border-radius: 10px;
    }

    .expense-amount-highlight {
        background-color: #dcfce7;
        border-color: #86efac;
        color: #166534;
        font-weight: 700;
    }

    /* ==================== FORM ELEMENTS ==================== */
    .expense-form-group {
        margin-bottom: 16px;
    }

    .expense-form-label {
        display: block;
        font-size: 12px;
        font-weight: 700;
        color: #6b7280;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .expense-form-input,
    .expense-form-select {
        width: 100%;
        height: 46px;
        padding: 12px 14px;
        border: 1px solid #d7deea;
        border-radius: 10px;
        font-size: 13px;
        color: #111827;
        background-color: #f8fafc;
        transition: all 0.3s ease;
    }

    .expense-form-input:focus,
    .expense-form-select:focus {
        outline: none;
        border-color: #9ca3af;
        background-color: white;
        box-shadow: 0 0 0 3px rgba(17, 24, 39, 0.05);
    }

    .expense-textarea {
        width: 100%;
        min-height: 100px;
        padding: 12px 14px;
        border: 1px solid #d7deea;
        border-radius: 10px;
        font-size: 13px;
        color: #111827;
        background-color: #f8fafc;
        font-family: inherit;
        resize: vertical;
        transition: all 0.3s ease;
    }

    .expense-textarea:focus {
        outline: none;
        border-color: #9ca3af;
        background-color: white;
        box-shadow: 0 0 0 3px rgba(17, 24, 39, 0.05);
    }

    /* ==================== DOCUMENTS SECTION ==================== */
    .expense-current-docs {
        margin-top: 20px;
    }

    .expense-docs-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 16px;
        margin-top: 12px;
    }

    .expense-doc-item {
        border: 1px solid #e5eaf1;
        border-radius: 12px;
        overflow: hidden;
        background-color: #fafbfc;
        transition: all 0.3s ease;
    }

    .expense-doc-item:hover {
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.1);
    }

    .expense-doc-preview {
        width: 100%;
        height: 200px;
        background-color: white;
    }

    .expense-doc-actions {
        display: flex;
        gap: 8px;
        padding: 12px;
        background-color: #f8fafc;
        border-top: 1px solid #e5eaf1;
    }

    .expense-btn-doc-download,
    .expense-btn-doc-delete {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 1;
        padding: 8px 12px;
        border: none;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
    }

    .expense-btn-doc-download {
        background-color: #dbeafe;
        color: #1e40af;
    }

    .expense-btn-doc-download:hover {
        background-color: #bfdbfe;
        color: #1e3a8a;
    }

    .expense-btn-doc-delete {
        background-color: #fee2e2;
        color: #991b1b;
    }

    .expense-btn-doc-delete:hover {
        background-color: #fecaca;
        color: #7f1d1d;
    }

    .expense-no-docs {
        text-align: center;
        padding: 24px;
        color: #9ca3af;
        font-size: 13px;
    }

    /* ==================== MODAL FOOTER ==================== */
    .expense-modal-footer {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
        padding: 16px 24px;
        border-top: 1px solid #e5eaf1;
        background-color: #fafbfc;
    }

    .expense-btn-close {
        padding: 10px 20px;
        border: 1px solid #d7deea;
        border-radius: 10px;
        background-color: white;
        color: #6b7280;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .expense-btn-close:hover {
        background-color: #f8fafc;
        border-color: #9ca3af;
        color: #4b5563;
    }

    .expense-btn-save {
        padding: 10px 20px;
        border: none;
        border-radius: 10px;
        background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
        color: white;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(17, 24, 39, 0.15);
        transition: all 0.3s ease;
    }

    .expense-btn-save:hover {
        background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
        box-shadow: 0 6px 16px rgba(17, 24, 39, 0.2);
        transform: translateY(-2px);
    }

    .expense-pagination-wrap {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 18px 24px 24px;
        border-top: 1px solid #e5eaf1;
        flex-wrap: wrap;
    }

    .expense-pagination-info {
        font-size: 13px;
        font-weight: 600;
        color: #6b7280;
    }

    .expense-pagination {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .expense-pagination-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 40px;
        height: 40px;
        padding: 0 14px;
        border: 1px solid #d7deea;
        border-radius: 10px;
        background-color: white;
        color: #111827;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .expense-pagination-link:hover {
        background-color: #f8fafc;
        color: #111827;
        border-color: #9ca3af;
    }

    .expense-pagination-ellipsis {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 24px;
        height: 40px;
        color: #6b7280;
        font-size: 13px;
        font-weight: 700;
    }

    .expense-pagination-active {
        background-color: #111827;
        border-color: #111827;
        color: white;
    }

    .expense-pagination-active:hover {
        background-color: #111827;
        border-color: #111827;
        color: white;
    }

    .expense-pagination-disabled {
        opacity: 0.5;
        pointer-events: none;
    }

    /* ==================== RESPONSIVE ==================== */
    @media (max-width: 768px) {
        .expense-header-top {
            flex-direction: column;
            align-items: stretch;
        }

        .expense-btn-add {
            width: 100%;
            text-align: center;
        }

        .expense-filter-inputs {
            flex-direction: column;
        }

        .expense-filter-input,
        .expense-btn-search {
            width: 100%;
        }

        .expense-action-group {
            flex-direction: column;
        }

        .expense-btn-view,
        .expense-btn-delete {
            width: 100%;
        }

        .expense-modal-dialog {
            margin: 10px;
        }

        .expense-info-grid {
            grid-template-columns: 1fr;
        }

        .expense-docs-list {
            grid-template-columns: 1fr;
        }

        .expense-pagination-wrap {
            flex-direction: column;
            align-items: stretch;
        }

        .expense-pagination {
            justify-content: center;
        }
    }
</style>
<script>
    // Status section toggle logic
    document.querySelectorAll('.status-select').forEach(select => {
        select.addEventListener('change', function() {
            const id = this.dataset.id;
            const status = this.value;
            const approvedSection = document.getElementById(`approvedSection${id}`);
            const ReimbursedSection = document.getElementById(`ReimbursedSection${id}`);
            const approvedAmount = document.getElementById(`approved_amount${id}`);

            if (status === 'Approved') {
                approvedSection.style.display = 'block';
                ReimbursedSection.style.display = 'none';
                if (approvedAmount) {
                    approvedAmount.readOnly = true;
                }
            } else if (status === 'Reimbursed') {
                approvedSection.style.display = 'block';
                ReimbursedSection.style.display = 'block';
                if (approvedAmount) {
                    approvedAmount.readOnly = false;
                }
            } else {
                approvedSection.style.display = 'none';
                ReimbursedSection.style.display = 'none';
            }
        });
    });
</script>
<?php include("footer.php"); ?>