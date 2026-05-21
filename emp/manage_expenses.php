<?php include("header.php"); ?>

<?php
// Handle delete expense request
if (isset($_POST['delete_expense'])) {
    $expense_id = $_POST['expense_id'];
    $conn->query("DELETE FROM expenses WHERE id = $expense_id");
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

    // Update expense details
    $stmt = $conn->prepare("UPDATE expenses SET expense_type = ?, title = ?, description = ?, amount = ?, quantity = ?, unit = ?, status = ? WHERE id = ?");
    $stmt->bind_param("sssisssi", $expense_type, $title, $description, $amount, $quantity, $unit, $status, $expense_id);
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




// Fetch employee's leave applications
$stmt = $conn->prepare("SELECT * FROM expenses WHERE employee_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$expenses  = $stmt->get_result();
?>

<style>
    :root {
        --expense-shell-bg: linear-gradient(180deg, #f8fafc 0%, #eef3f8 100%);
        --expense-shell-border: rgba(148, 163, 184, 0.18);
        --expense-shell-shadow: 0 24px 56px rgba(15, 23, 42, 0.08);
    }

    .manage-expenses-page {
        padding-top: 0.95rem !important;
        padding-bottom: 1.2rem !important;
    }

    .manage-expenses-title {
        margin: 0;
        color: #0f172a;
        font-size: 1.15rem;
        font-weight: 800;
        letter-spacing: -0.02em;
    }

    .manage-expenses-header-row {
        align-items: center;
    }

    .manage-expenses-cta {
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

    .manage-expenses-card {
        border: 1px solid var(--expense-shell-border);
        border-radius: 28px;
        background: var(--expense-shell-bg);
        box-shadow: var(--expense-shell-shadow);
        overflow: hidden;
    }

    .manage-expenses-shell {
        background: #ffffff;
    }

    .manage-expenses-search {
        margin: 1.15rem 1.1rem 0.8rem !important;
    }

    .manage-expenses-search .row {
        --bs-gutter-x: 0.8rem;
        align-items: center;
    }

    .manage-expenses-input {
        min-height: 48px;
        border-radius: 16px;
        border: 1px solid #d9e2ec;
        background: #ffffff;
        color: #0f172a;
        box-shadow: none;
        font-size: 0.92rem;
        font-weight: 500;
        padding: 0.8rem 0.95rem;
    }

    .manage-expenses-input:focus {
        border-color: #94a3b8;
        box-shadow: 0 0 0 4px rgba(148, 163, 184, 0.16);
    }

    .manage-expenses-search-btn {
        min-height: 48px;
        width: 100%;
        border-radius: 16px;
        border: 0;
        background: linear-gradient(135deg, #123b76 0%, #1f4c8f 100%) !important;
        color: #ffffff !important;
        font-size: 0.78rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        box-shadow: none;
    }

    .manage-expenses-wrap {
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
    }

    .manage-expenses-table {
        margin-bottom: 0;
        min-width: 860px;
    }

    .manage-expenses-table thead th {
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

    .manage-expenses-table tbody td {
        padding: 1rem;
        border-bottom: 1px solid #eef2f7;
        vertical-align: middle;
        color: #1f2937;
        font-size: 0.88rem;
    }

    .manage-expenses-table tbody tr:hover {
        background: #fbfdff;
    }

    .manage-expenses-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .manage-expenses-status .badge {
        border-radius: 999px;
        padding: 0.52rem 0.82rem;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        border: 1px solid transparent;
        box-shadow: none;
    }

    .manage-expenses-status .bg-gradient-primary,
    .manage-expenses-status .bg-primary {
        background: #e8f0ff !important;
        color: #1d4ed8 !important;
        border-color: #bfd4ff;
    }

    .manage-expenses-status .bg-gradient-danger {
        background: #fff1f2 !important;
        color: #dc2626 !important;
        border-color: #fecdd3;
    }

    .manage-expenses-status .bg-gradient-warning {
        background: #fff7db !important;
        color: #b45309 !important;
        border-color: #f8df9c;
    }

    .manage-expenses-action,
    .manage-expenses-doc-action {
        min-height: 36px;
        padding: 0.58rem 0.78rem;
        border-radius: 12px;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        box-shadow: none !important;
    }

    .manage-expenses-action.btn-primary {
        background: #e9f2ff;
        border-color: #c7dafc;
        color: #1d4f91;
    }

    .manage-expenses-action.btn-primary:hover,
    .manage-expenses-action.btn-primary:focus {
        background: #dce9ff;
        border-color: #b5cffd;
        color: #153d74;
    }

    .manage-expenses-action.btn-danger,
    .manage-expenses-doc-action.btn-danger {
        background: #fff1f2;
        border-color: #fecdd3;
        color: #c24153;
    }

    .manage-expenses-action.btn-danger:hover,
    .manage-expenses-action.btn-danger:focus,
    .manage-expenses-doc-action.btn-danger:hover,
    .manage-expenses-doc-action.btn-danger:focus {
        background: #ffe4e8;
        border-color: #fda4af;
        color: #9f1239;
    }

    .manage-expenses-action + form,
    .manage-expenses-action + .manage-expenses-action,
    .manage-expenses-actions form {
        display: inline-block;
    }

    .manage-expenses-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
        align-items: center;
    }

    .manage-expenses-modal .modal-content {
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 24px;
        box-shadow: 0 26px 60px rgba(15, 23, 42, 0.16);
        overflow: hidden;
    }

    .manage-expenses-modal .modal-header,
    .manage-expenses-modal .modal-footer {
        background: #ffffff;
        border-color: #eef2f7;
    }

    .manage-expenses-modal .modal-body {
        background: #f8fafc;
    }

    .manage-expenses-modal .modal-title {
        color: #0f172a;
        font-size: 1rem;
        font-weight: 800;
    }

    .manage-expenses-modal .form-label {
        color: #475569;
        font-size: 0.75rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .manage-expenses-modal .form-control {
        min-height: 44px;
        border-radius: 14px;
        border: 1px solid #d9e2ec;
        box-shadow: none;
    }

    .manage-expenses-modal textarea.form-control {
        min-height: 96px;
    }

    .manage-expenses-doc-list {
        margin: 0;
        padding-left: 1rem;
    }

    .manage-expenses-doc-list li {
        margin-bottom: 0.45rem;
        color: #334155;
    }

    .manage-expenses-doc-list a {
        color: #123b76;
        font-weight: 700;
        text-decoration: none;
    }

    @media (max-width: 767.98px) {
        .manage-expenses-page {
            padding-top: 0.6rem !important;
            padding-left: 0.3rem !important;
            padding-right: 0.3rem !important;
            padding-bottom: 0.85rem !important;
        }

        .manage-expenses-header-row {
            flex-wrap: nowrap;
            align-items: center;
            min-width: 0;
        }

        .manage-expenses-title-col {
            flex: 1 1 auto;
            max-width: calc(100% - 146px);
            width: calc(100% - 146px);
            margin-bottom: 0.85rem !important;
            padding-right: 0.45rem;
        }

        .manage-expenses-action-col {
            flex: 0 0 146px;
            max-width: 146px;
            width: 146px;
            margin-bottom: 0.85rem !important;
            text-align: right !important;
        }

        .manage-expenses-title {
            font-size: 0.96rem;
            line-height: 1.25;
        }

        .manage-expenses-cta {
            width: 100%;
            min-height: 42px;
            padding: 0.66rem 0.76rem;
            border-radius: 14px;
            font-size: 0.7rem;
            letter-spacing: 0.05em;
        }

        .manage-expenses-card {
            border-radius: 22px;
        }

        .manage-expenses-search {
            margin: 1rem 0.85rem 0.75rem !important;
        }

        .manage-expenses-search .row {
            --bs-gutter-x: 0.6rem;
            --bs-gutter-y: 0.65rem;
        }

        .manage-expenses-input,
        .manage-expenses-search-btn {
            min-height: 42px;
            border-radius: 14px;
            font-size: 0.76rem;
        }

        .manage-expenses-table thead th,
        .manage-expenses-table tbody td {
            padding: 0.82rem 0.78rem;
        }
    }
</style>

<div class="container-fluid py-4 manage-expenses-page">
    <div class="row">
        <div class="col-12">
            <div class="row manage-expenses-header-row">
                <div class="col-6 mb-4 d-flex align-items-center manage-expenses-title-col">
                    <h6 class="mb-0 manage-expenses-title">Manage Daily Expenses</h6>
                </div>
                <div class="col-6 mb-4 text-end manage-expenses-action-col">
                    <a href="add_expenses" class="btn bg-gradient-dark mb-0 manage-expenses-cta">Add Expense</a>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card mb-4 manage-expenses-card">
                <div class="card-body px-0 pt-0 pb-2 manage-expenses-shell">
                    <div class="table-responsive p-0 manage-expenses-wrap">
                        <form method="GET" class="mb-3 mt-4 manage-expenses-search">
                            <div class="row">
                                <div class="col-md-10">
                                    <input type="text" name="search" class="form-control manage-expenses-input" placeholder="Search by Expense Title or Type">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn bg-gradient-dark mb-0 manage-expenses-search-btn">Search</button>
                                </div>
                            </div>
                        </form>
                        <table class="table align-items-center mb-0 manage-expenses-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Expense Type</th>
                                    <th>Title</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $expenses->fetch_assoc()) : ?>
                                    <tr>
                                        <td><?= $row['expense_date'] ?></td>
                                        <td><?= $row['expense_type'] ?></td>
                                        <td><?= $row['title'] ?></td>
                                        <td><?= $row['amount'] ?></td>
                                                                                <td class="align-middle text-center text-sm manage-expenses-status">
                        <?php if ($row['status'] == 'Pending') : ?>
                          <span class="badge badge-sm bg-gradient-primary"><?= ucfirst($row['status']) ?></span>
                        <?php elseif ($row['status'] == 'Reimbursed') : ?>
                          <span class="badge badge-sm bg-gradient-danger"><?= ucfirst($row['status']) ?></span>
                        <?php elseif ($row['status'] == 'Approved') : ?>
                          <span class="badge badge-sm bg-primary"><?= ucfirst($row['status']) ?></span>
                        <?php elseif ($row['status'] == 'Rejected') : ?>
                          <span class="badge badge-sm bg-gradient-danger"><?= ucfirst($row['status']) ?></span>
                        <?php elseif ($row['status'] == 'Holiday') : ?>
                          <span class="badge badge-sm bg-gradient-warning"><?= ucfirst($row['status']) ?></span>

                        <?php endif; ?>
                      </td>
                                        <td>
                                            <div class="manage-expenses-actions">

                                            <?php if ($row['status'] === 'Pending'): ?>
                                                <button class="btn btn-primary btn-sm manage-expenses-action" data-bs-toggle="modal" data-bs-target="#editExpenseModal<?= $row['id'] ?>">Edit</button>
                                                <form action="manage_expenses" method="POST" style="display: inline;">
                                                    <input type="hidden" name="expense_id" value="<?= $row['id'] ?>">
                                                    <button type="submit" name="delete_expense" class="btn btn-danger btn-sm manage-expenses-action">Delete</button>
                                                </form>
                                            <?php else: ?>

                                            <?php endif; ?>
                                            <?php if (in_array($row['status'], ['Approved', 'Reimbursed', 'Rejected'])): ?>
                                                <button class="btn btn-dark btn-sm manage-expenses-action" data-bs-toggle="modal" data-bs-target="#viewExpenseModal<?= $row['id'] ?>">View</button>
                                            <?php else: ?>
                                               
                                            <?php endif; ?>
                                            </div>

                                        </td>
                                    </tr>
                                    <!-- Edit Modal -->
                                    <div class="modal fade manage-expenses-modal" id="editExpenseModal<?= $row['id'] ?>" tabindex="-1" aria-labelledby="editExpenseModalLabel" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form action="manage_expenses" method="POST" enctype="multipart/form-data">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="editExpenseModalLabel">Edit Expense</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>


                                                    <div class="modal-body">
                                                        <input type="hidden" name="expense_id" value="<?= $row['id'] ?>">
                                                        <div class="mb-3">
                                                            <label for="expense_type" class="form-label">Expense Type</label>
                                                            <select name="expense_type" id="expense_type" class="form-control" required>
                                                                <option value="Goods" <?= $row['expense_type'] == 'Goods' ? 'selected' : '' ?>>Goods</option>
                                                                <option value="Services" <?= $row['expense_type'] == 'Services' ? 'selected' : '' ?>>Services</option>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="title" class="form-label">Expense Title</label>
                                                            <input type="text" name="title" id="title" class="form-control" value="<?= $row['title'] ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="description" class="form-label">Description</label>
                                                            <textarea name="description" id="description" class="form-control" rows="3" required><?= $row['description'] ?></textarea>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="amount" class="form-label">Amount</label>
                                                            <input type="number" name="amount" id="amount" class="form-control" value="<?= $row['amount'] ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="quantity" class="form-label">Quantity</label>
                                                            <input type="number" name="quantity" id="quantity" class="form-control" value="<?= $row['quantity'] ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="unit" class="form-label">Unit</label>
                                                            <input type="text" name="unit" id="unit" class="form-control" value="<?= $row['unit'] ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="status" class="form-label">Status</label>
                                                            <input name="status" id="status" class="form-control" readonly required value="<?= $row['status'] ?>">

                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="new_documents" class="form-label">Add Documents</label>
                                                            <input type="file" name="new_documents[]" id="new_documents" class="form-control" multiple>
                                                        </div>
                                                        
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        <button type="submit" name="update_expense" class="btn btn-primary">Save Changes</button>
                                                    </div>
                                                </form>
                                                <div class="mb-3">
                                                            <label class="form-label">Existing Documents</label>
                                                            <ul class="manage-expenses-doc-list">
                                                                <?php
                                                                $documents = $conn->query("SELECT * FROM expense_documents WHERE expense_id = {$row['id']}");
                                                                while ($doc = $documents->fetch_assoc()) : ?>
                                                                    <li>
                                                                        <a href="<?= $doc['file_path'] ?>" target="_blank">View Document</a>
                                                                        <form action="manage_expenses" method="POST" style="display: inline;">
                                                                            <input type="hidden" name="document_id" value="<?= $doc['id'] ?>">
                                                                            <button type="submit" name="delete_document" class="btn btn-sm btn-danger manage-expenses-doc-action">Delete</button>
                                                                        </form>
                                                                    </li>
                                                                <?php endwhile; ?>
                                                            </ul>
                                                        </div>
                                            </div>
                                        </div>
                                    </div>













                                    <!-- view Modal -->
                                    <div class="modal fade manage-expenses-modal" id="viewExpenseModal<?= $row['id'] ?>" tabindex="-1" aria-labelledby="viewExpenseModalLabel" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">

                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="viewExpenseModalLabel">View Expense</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>


                                                <div class="modal-body">
                                                    <input type="hidden" name="expense_id" value="<?= $row['id'] ?>">
                                                    <div class="mb-3">
                                                        <label for="expense_type" class="form-label">Expense Type</label>
                                                        <input name="expense_type" id="expense_type" class="form-control" readonly required value="<?= $row['expense_type'] ?>">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="title" class="form-label">Expense Title</label>
                                                        <input type="text" name="title" id="title" class="form-control" value="<?= $row['title'] ?>" readonly required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="description" class="form-label">Description</label>
                                                        <textarea name="description" id="description" class="form-control" rows="3" required readonly><?= $row['description'] ?></textarea>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="amount" class="form-label">Amount</label>
                                                        <input type="number" name="amount" id="amount" class="form-control" value="<?= $row['amount'] ?>" readonly required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="status" class="form-label">Status</label>
                                                        <input name="status" id="status" class="form-control" readonly required value="<?= $row['status'] ?>" readonly>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label for="approved_amount" class="form-label">Approved Amount</label>
                                                        <input type="number" name="approved_amount" id="approved_amount" class="form-control" value="<?= $row['approved_amount'] ?>" readonly>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="reject_reason" class="form-label">Reimbursed Reason</label>
                                                        <textarea name="reject_reason" id="reject_reason" class="form-control" readonly><?= $row['reject_reason'] ?? '' ?></textarea>
                                                    </div>


                                                    <div class="mb-3">
                                                        <label class="form-label">Existing Documents</label>
                                                        <ul class="manage-expenses-doc-list">
                                                            <?php
                                                            $documents = $conn->query("SELECT * FROM expense_documents WHERE expense_id = {$row['id']}");
                                                            while ($doc = $documents->fetch_assoc()) : ?>
                                                                <li>
                                                                    <a href="<?= $doc['file_path'] ?>" target="_blank">View Document</a>

                                                                </li>
                                                            <?php endwhile; ?>
                                                        </ul>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>

                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("footer.php"); ?>