<?php include("header.php"); ?>
<?php
// Fetch tasks assigned to the logged-in employee
$employee_id = $_SESSION['employee_id'];  // Assuming the employee ID is stored in the session
// Fetch tasks assigned to the employee
$tasks = $conn->query("SELECT t.*, e.name AS employee_name FROM tasks t 
                       INNER JOIN employees e ON t.employee_id = e.id
                       WHERE t.employee_id = $employee_id");
?>
<style>
    :root {
        --assigned-task-shell-bg: linear-gradient(180deg, #f8fafc 0%, #eef3f8 100%);
        --assigned-task-shell-border: rgba(148, 163, 184, 0.18);
        --assigned-task-shell-shadow: 0 24px 56px rgba(15, 23, 42, 0.08);
    }

    .assigned-task-page {
        padding-top: 0.95rem !important;
        padding-bottom: 1.2rem !important;
    }

    .assigned-task-title {
        margin: 0;
        color: #0f172a;
        font-size: 1.15rem;
        font-weight: 800;
        letter-spacing: -0.02em;
    }

    .assigned-task-card {
        border: 1px solid var(--assigned-task-shell-border);
        border-radius: 28px;
        background: var(--assigned-task-shell-bg);
        box-shadow: var(--assigned-task-shell-shadow);
        overflow: hidden;
    }

    .assigned-task-shell {
        background: #ffffff;
    }

    .assigned-task-search {
        margin: 1.15rem 1.1rem 0.8rem !important;
    }

    .assigned-task-search .row {
        --bs-gutter-x: 0.8rem;
        align-items: center;
    }

    .assigned-task-input {
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

    .assigned-task-input:focus {
        border-color: #94a3b8;
        box-shadow: 0 0 0 4px rgba(148, 163, 184, 0.16);
    }

    .assigned-task-search-btn {
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

    .assigned-task-wrap {
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
    }

    .assigned-task-table {
        margin-bottom: 0;
        min-width: 860px;
    }

    .assigned-task-table thead th {
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

    .assigned-task-table tbody td {
        padding: 1rem;
        border-bottom: 1px solid #eef2f7;
        vertical-align: middle;
        color: #1f2937;
        font-size: 0.88rem;
    }

    .assigned-task-table tbody tr:hover {
        background: #fbfdff;
    }

    .assigned-task-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .assigned-task-status {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.5rem 0.78rem;
        border-radius: 999px;
        border: 1px solid #dbe4f0;
        background: #f8fafc;
        color: #475569;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .assigned-task-status.status-pending {
        background: #fff7db;
        border-color: #f8df9c;
        color: #9a6700;
    }

    .assigned-task-status.status-in-progress {
        background: #e8f0ff;
        border-color: #bfd4ff;
        color: #1d4ed8;
    }

    .assigned-task-status.status-completed {
        background: #ecfdf3;
        border-color: #bbf7d0;
        color: #15803d;
    }

    .assigned-task-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
        align-items: center;
    }

    .assigned-task-action {
        min-height: 36px;
        padding: 0.58rem 0.78rem;
        border-radius: 12px;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        box-shadow: none !important;
    }

    .assigned-task-action.btn-primary {
        background: #e8f0ff;
        border-color: #bfd4ff;
        color: #1d4ed8;
    }

    .assigned-task-action.btn-primary:hover,
    .assigned-task-action.btn-primary:focus {
        background: #dbe8ff;
        border-color: #a9c6ff;
        color: #1e40af;
    }

    .assigned-task-action.btn-info {
        background: #e6f7fb;
        border-color: #b8e8f2;
        color: #0f766e;
    }

    .assigned-task-action.btn-info:hover,
    .assigned-task-action.btn-info:focus {
        background: #d6f0f7;
        border-color: #95dce9;
        color: #115e59;
    }

    .assigned-task-action.btn-warning {
        background: #fff7db;
        border-color: #f8df9c;
        color: #9a6700;
    }

    .assigned-task-action.btn-warning:hover,
    .assigned-task-action.btn-warning:focus {
        background: #ffefbf;
        border-color: #f4cf72;
        color: #7c5200;
    }

    .assigned-task-modal .modal-content {
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 24px;
        box-shadow: 0 26px 60px rgba(15, 23, 42, 0.16);
        overflow: hidden;
    }

    .assigned-task-modal .modal-header,
    .assigned-task-modal .modal-footer {
        background: #ffffff;
        border-color: #eef2f7;
    }

    .assigned-task-modal .modal-body {
        background: #f8fafc;
    }

    .assigned-task-modal .modal-title {
        color: #0f172a;
        font-size: 1rem;
        font-weight: 800;
    }

    .assigned-task-modal .form-label {
        color: #475569;
        font-size: 0.75rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .assigned-task-modal .form-control {
        min-height: 44px;
        border-radius: 14px;
        border: 1px solid #d9e2ec;
        box-shadow: none;
    }

    .assigned-task-modal textarea.form-control {
        min-height: 108px;
    }

    .assigned-task-details p {
        margin-bottom: 0.65rem;
        color: #334155;
    }

    .assigned-task-documents {
        display: flex;
        flex-wrap: wrap;
        gap: 0.85rem;
        margin-top: 0.75rem;
    }

    .assigned-task-document {
        width: 150px;
        padding: 0.7rem;
        border-radius: 18px;
        border: 1px solid #dbe4f0;
        background: #ffffff;
    }

    .assigned-task-document embed {
        display: block;
        width: 100%;
        height: 150px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
    }

    .assigned-task-document-links {
        display: flex;
        gap: 0.35rem;
        margin-top: 0.55rem;
        flex-wrap: wrap;
    }

    .assigned-task-document-links .btn-link {
        padding: 0;
        color: #123b76;
        font-size: 0.74rem;
        font-weight: 700;
        text-decoration: none;
    }

    .assigned-task-empty {
        margin: 1rem 1rem 1.2rem;
        border-radius: 16px;
        border: 1px solid #d8e6f6;
        background: #eef6ff;
        color: #123b76;
        font-weight: 600;
    }

    @media (max-width: 767.98px) {
        .assigned-task-page {
            padding-top: 0.6rem !important;
            padding-left: 0.3rem !important;
            padding-right: 0.3rem !important;
            padding-bottom: 0.85rem !important;
        }

        .assigned-task-title {
            font-size: 0.98rem;
            line-height: 1.25;
        }

        .assigned-task-card {
            border-radius: 22px;
        }

        .assigned-task-search {
            margin: 1rem 0.85rem 0.75rem !important;
        }

        .assigned-task-search .row {
            --bs-gutter-x: 0.6rem;
            --bs-gutter-y: 0.65rem;
        }

        .assigned-task-input,
        .assigned-task-search-btn {
            min-height: 42px;
            border-radius: 14px;
            font-size: 0.76rem;
        }

        .assigned-task-table {
            min-width: 760px;
        }

        .assigned-task-table thead th,
        .assigned-task-table tbody td {
            padding: 0.82rem 0.78rem;
        }

        .assigned-task-document {
            width: 132px;
        }
    }
</style>

<div class="container-fluid py-4 assigned-task-page">
    <div class="row">
        <div class="col-12 mb-4 d-flex align-items-center">
            <h6 class="mb-0 assigned-task-title">Manage Task</h6>
        </div>
        <div class="col-12">
            <div class="card mb-4 assigned-task-card">
                <div class="card-body px-0 pt-0 pb-2 assigned-task-shell">
                    <div class="table-responsive p-0 assigned-task-wrap">
                        <form method="GET" class="mb-3 mt-4 assigned-task-search">
                            <div class="row">
                                <div class="col-md-10">
                                    <input type="text" name="search" class="form-control assigned-task-input" placeholder="Search by Name, Role or ID" value="">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn bg-gradient-dark mb-0 assigned-task-search-btn">Search</button>
                                </div>
                            </div>
                        </form>
                        <?php if ($tasks->num_rows > 0) : ?>
                        <table class="table align-items-center mb-0 assigned-task-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                   
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $tasks->fetch_assoc()) : ?>
                                    <tr>
                                        <td><?= $row['id'] ?></td>
                                        <td><?= $row['title'] ?></td>
                                       
                                        <td><?= $row['due_date'] ?></td>
                                        <td>
                                            <?php $status_class = strtolower(str_replace(' ', '-', $row['status'])); ?>
                                            <span class="assigned-task-status status-<?= htmlspecialchars($status_class) ?>"><?= $row['status'] ?></span>
                                        </td>
                                        <td>
                                        <div class="assigned-task-actions">
                                        <a href="chat?task_id=<?= $row['id'] ?>" class="btn btn-primary btn-sm assigned-task-action">Chat</a>
                                            <button class="btn btn-info btn-sm assigned-task-action" data-bs-toggle="modal" data-bs-target="#viewTaskModal<?= $row['id'] ?>">View</button>
                                            <button class="btn btn-warning btn-sm assigned-task-action" data-bs-toggle="modal" data-bs-target="#updateTaskModal<?= $row['id'] ?>">Update</button>
                                        </div>
                                        </td>
                                    </tr>
                                    <!-- View Task Modal -->
                                    <div class="modal fade assigned-task-modal" id="viewTaskModal<?= $row['id'] ?>" tabindex="-1" aria-labelledby="viewTaskModalLabel" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="viewTaskModalLabel">Task Details</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body assigned-task-details">
                                                    <p><strong>Title:</strong> <?= $row['title'] ?></p>
                                                    <p><strong>Description:</strong> <?= $row['description'] ?></p>
                                                    <p><strong>Due Date:</strong> <?= $row['due_date'] ?></p>
                                                    <p><strong>Status:</strong> <?= $row['status'] ?></p>
                                                    <p><strong>Remark:</strong> <?= $row['remark'] ?? 'No remarks yet' ?></p>
                                                    <p><strong>Documents:</strong></p>
                                                    <?php
                                                    // Fetch attached documents for this task
                                                    $task_id = $row['id'];
                                                    $docs = $conn->query("SELECT * FROM task_documents WHERE task_id = $task_id");
                                                    if ($docs->num_rows > 0) :
                                                        echo '<div class="assigned-task-documents">';
                                                        while ($doc = $docs->fetch_assoc()) :
                                                    ?>
                                                        <div class="assigned-task-document">
                                                            <embed src="<?= $doc['file_path'] ?>">
                                                            <div class="assigned-task-document-links">
                                                                <a href="<?= $doc['file_path'] ?>" class="btn btn-link" target="_blank">View</a>
                                                                <a href="<?= $doc['file_path'] ?>" download class="btn btn-link">Download</a>
                                                            </div>
                                                        </div>
                                                    <?php
                                                        endwhile;
                                                        echo '</div>';
                                                    else :
                                                        echo "<p>No documents uploaded</p>";
                                                    endif;
                                                    ?>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Update Task Modal -->
                                    <div class="modal fade assigned-task-modal" id="updateTaskModal<?= $row['id'] ?>" tabindex="-1" aria-labelledby="updateTaskModalLabel" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form action="assigned_task" method="POST" enctype="multipart/form-data">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="updateTaskModalLabel">Update Task Status</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="task_id" value="<?= $row['id'] ?>">
                                                        <div class="mb-3">
                                                            <label for="status" class="form-label">Task Status</label>
                                                            <select name="status" id="status" class="form-control" required>
                                                                <option value="Pending" <?= $row['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                                <option value="In Progress" <?= $row['status'] == 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                                                <option value="Completed" <?= $row['status'] == 'Completed' ? 'selected' : '' ?>>Completed</option>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="remark" class="form-label">Remark</label>
                                                            <textarea name="remark" id="remark" class="form-control" rows="3"><?= $row['remark'] ?? '' ?></textarea>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="documents" class="form-label">Attach Documents</label>
                                                            <input type="file" name="documents[]" id="documents" class="form-control" multiple>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        <?php else : ?>
                            <div class="alert alert-info assigned-task-empty">No tasks found.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
// Handle task status update
if (isset($_POST['update_status'])) {
    $task_id = $_POST['task_id'];
    $status = $_POST['status'];
    $remark = $_POST['remark'] ?? '';
    $stmt = $conn->prepare("UPDATE tasks SET status = ?, remark = ? WHERE id = ?");
    $stmt->bind_param("ssi", $status, $remark, $task_id);
    if ($stmt->execute()) {
        // Handle file uploads
        if (!empty($_FILES['documents']['name'][0])) {
            foreach ($_FILES['documents']['name'] as $key => $fileName) {
                $fileTmp = $_FILES['documents']['tmp_name'][$key];
                $filePath = "../uploads/tasks/" . basename($fileName); // Full path to save
                if (move_uploaded_file($fileTmp, $filePath)) {
                    // Save the complete file path in the database
                    $conn->query("INSERT INTO task_documents (task_id, file_path) VALUES ($task_id, '$filePath')");
                }
            }
        }        
        echo '<script>alert("Task status updated successfully!"); window.location="assigned_task";</script>';
    } else {
        echo '<script>alert("Failed to update task status!");</script>';
    }
}
include("footer.php");
?>
