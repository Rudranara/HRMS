<?php include("header.php"); ?>
<?php
// Handle delete request
if (isset($_POST['delete_task'])) {
    $task_id = $_POST['task_id'];
    $conn->query("DELETE FROM tasks WHERE id = $task_id");
}
// Handle update request
if (isset($_POST['update_task'])) {
    $task_id = $_POST['task_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $due_date = $_POST['due_date'];
    $status = $_POST['status'];
    $remark = $_POST['remark'];
    // Update task details
    $stmt = $conn->prepare("UPDATE tasks SET title = ?, description = ?, due_date = ?, status = ?, remark = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $title, $description, $due_date, $status, $remark, $task_id);
    $stmt->execute();
    // Handle new document uploads
    if (!empty($_FILES['new_documents']['name'][0])) {
        foreach ($_FILES['new_documents']['name'] as $key => $fileName) {
            $fileTmp = $_FILES['new_documents']['tmp_name'][$key];
            $filePath = "../uploads/tasks/" . basename($fileName);
            if (move_uploaded_file($fileTmp, $filePath)) {
                $stmt = $conn->prepare("INSERT INTO task_documents (task_id, file_path) VALUES (?, ?)");
                $stmt->bind_param("is", $task_id, $filePath);
                $stmt->execute();
            }
        }
    }
}
// Handle document delete request
if (isset($_POST['delete_document'])) {
    $document_id = $_POST['document_id'];
    $conn->query("DELETE FROM task_documents WHERE id = $document_id");
}
// Fetch tasks
$tasks = $conn->query("SELECT t.*, e.name AS employee_name FROM tasks t INNER JOIN employees e ON t.employee_id = e.id");
?>
<style>
.manage-task-page {
    padding-bottom: 1.5rem;
}

.manage-task-topbar,
.manage-task-search-card,
.manage-task-table-card {
    border: 1px solid rgba(87, 96, 108, 0.12);
    border-radius: 22px;
    box-shadow: 0 14px 34px rgba(31, 41, 55, 0.06);
    background: #fff;
}

.manage-task-topbar {
    padding: 1.15rem 1.2rem;
    margin-bottom: 1rem;
}

.manage-task-topbar-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 1rem;
    align-items: center;
}

.manage-task-section-label {
    display: block;
    margin-bottom: 0.45rem;
    color: #6b7280;
    font-size: 0.74rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.manage-task-title {
    margin: 0;
    color: #0f172a;
    font-size: 1.45rem;
    font-weight: 800;
    letter-spacing: -0.03em;
}

.manage-task-copy {
    margin: 0.35rem 0 0;
    color: #6b7280;
    font-size: 0.92rem;
}

.manage-task-toolbar {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.8rem;
    flex-wrap: wrap;
}

.manage-task-toolbar .btn,
.manage-task-toolbar a {
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

.manage-task-btn-dark,
.manage-task-search-btn,
.manage-task-modal .btn-primary {
    background: linear-gradient(135deg, #161616 0%, #2d2d2d 100%) !important;
    color: #fff !important;
    border: none !important;
    box-shadow: 0 12px 24px rgba(17, 24, 39, 0.18);
}

.manage-task-btn-dark:hover,
.manage-task-search-btn:hover,
.manage-task-modal .btn-primary:hover {
    background: linear-gradient(135deg, #101010 0%, #242424 100%) !important;
    color: #fff !important;
}

.manage-task-search-card {
    padding: 1rem 1.1rem;
    margin-bottom: 1rem;
}

.manage-task-search-row {
    display: flex;
    gap: 0.85rem;
    align-items: center;
}

.manage-task-search-card .form-control,
.manage-task-modal .form-control {
    min-height: 46px;
    border-radius: 14px;
    border: 1px solid #d8dee7;
    box-shadow: none;
}

.manage-task-search-card .form-control:focus,
.manage-task-modal .form-control:focus {
    border-color: #1e3a5f;
    box-shadow: 0 0 0 0.18rem rgba(30, 58, 95, 0.12);
}

.manage-task-table-card {
    overflow: hidden;
}

.manage-task-table-card .card-body {
    padding: 0 0 1rem;
}

.manage-task-table-wrap {
    padding: 0 1.2rem 1.15rem;
}

.manage-task-table {
    margin-bottom: 0;
}

.manage-task-table thead th {
    border-bottom: 1px solid #e8edf3;
    color: #6b7280;
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    padding: 1rem 0.95rem;
    white-space: nowrap;
    background: #f8fafc;
}

.manage-task-table tbody td {
    padding: 1rem 0.95rem;
    border-bottom: 1px solid #eef2f7;
    color: #1f2937;
    vertical-align: middle;
    font-size: 0.92rem;
}

.manage-task-table tbody tr:last-child td {
    border-bottom: none;
}

.manage-task-table tbody tr:hover {
    background: #fbfcfe;
}

.manage-task-id-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    min-height: 40px;
    padding: 0.35rem 0.65rem;
    border-radius: 14px;
    background: #f8fafc;
    border: 1px solid #dbe3ed;
    color: #475569;
    font-size: 0.82rem;
    font-weight: 700;
}

.manage-task-employee,
.manage-task-title-text {
    color: #0f172a;
    font-weight: 700;
}

.manage-task-due {
    color: #475569;
    font-weight: 600;
}

.manage-task-status {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.42rem 0.8rem;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.manage-task-status.status-pending {
    background: #fff1cf;
    color: #9a6700;
}

.manage-task-status.status-in-progress {
    background: #eaf2ff;
    color: #275ea8;
}

.manage-task-status.status-completed {
    background: #dff5e6;
    color: #21543a;
}

.manage-task-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: nowrap;
    white-space: nowrap;
}

.manage-task-action-btn {
    min-height: 38px;
    padding: 0.5rem 0.9rem;
    border-radius: 12px !important;
    font-size: 0.76rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    margin: 0 !important;
}

.manage-task-action-btn.btn-primary {
    background: #16324f !important;
    color: #fff !important;
    border: 1px solid #16324f !important;
    box-shadow: none !important;
}

.manage-task-action-btn.btn-primary:hover {
    background: #10263c !important;
    border-color: #10263c !important;
    color: #fff !important;
}

.manage-task-action-btn.btn-danger {
    background: #fbe6e5 !important;
    color: #c24141 !important;
    border: 1px solid #f4c9c7 !important;
    box-shadow: none !important;
}

.manage-task-action-btn.btn-danger:hover {
    background: #f7d8d6 !important;
    color: #a93232 !important;
}

.manage-task-modal .modal-dialog {
    max-width: 720px;
}

.manage-task-modal .modal-content {
    border: 1px solid rgba(87, 96, 108, 0.12);
    border-radius: 18px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
    overflow: hidden;
}

.manage-task-modal .modal-header,
.manage-task-modal .modal-footer {
    border-color: #eef2f7;
    padding: 1rem 1.25rem;
}

.manage-task-modal .modal-body {
    padding: 1.25rem;
}

.manage-task-modal .modal-title {
    color: #0f172a;
    font-size: 1rem;
    font-weight: 700;
}

.manage-task-modal .form-label {
    display: block;
    margin-bottom: 0.45rem;
    color: #6b7280;
    font-size: 0.74rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.manage-task-modal textarea.form-control {
    min-height: 110px;
}

.manage-task-modal .btn-secondary {
    background: #f3f4f6;
    color: #334155;
    border: none;
}

.manage-task-documents {
    margin: 0;
    padding-left: 1rem;
}

.manage-task-documents li + li {
    margin-top: 0.75rem;
}

.manage-task-documents embed {
    border-radius: 10px;
    border: 1px solid #dbe3ed;
    background: #f8fafc;
    margin-right: 0.6rem;
}

@media (max-width: 991.98px) {
    .manage-task-topbar-grid,
    .manage-task-search-row,
    .manage-task-actions {
        grid-template-columns: 1fr;
        flex-direction: column;
        align-items: stretch;
    }

    .manage-task-toolbar {
        justify-content: stretch;
    }

    .manage-task-toolbar .btn,
    .manage-task-toolbar a,
    .manage-task-search-row .btn,
    .manage-task-actions > * {
        width: 100%;
    }
}
</style>

<div class="container-fluid py-4 manage-task-page">
    <div class="row">
        <div class="col-12">
            <div class="manage-task-topbar">
                <div class="manage-task-topbar-grid">
                    <div>
                        <span class="manage-task-section-label">Task Directory</span>
                        <h6 class="manage-task-title">Manage Employees</h6>
                        <p class="manage-task-copy">Review assigned tasks, update progress, and manage task documents from one place.</p>
                    </div>
                    <div class="manage-task-toolbar">
                        <a href="assign_task" class="btn manage-task-btn-dark mb-0">Assign Task</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="manage-task-search-card">
                <form method="GET" class="mb-0">
                    <div class="manage-task-search-row">
                        <input type="text" name="search" class="form-control" placeholder="Search by Name, Role or ID" value="">
                        <button type="submit" class="btn manage-task-search-btn mb-0">Search</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-12">
            <div class="card manage-task-table-card mb-4">
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="table-responsive manage-task-table-wrap">
                        <table class="table manage-task-table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Employee</th>
                                    <th>Title</th>
                                  
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $tasks->fetch_assoc()) : ?>
                                    <tr>
                                        <td><span class="manage-task-id-badge"><?= $row['id'] ?></span></td>
                                        <td><span class="manage-task-employee"><?= $row['employee_name'] ?></span></td>
                                        <td><span class="manage-task-title-text"><?= $row['title'] ?></span></td>
                                      
                                        <td><span class="manage-task-due"><?= $row['due_date'] ?></span></td>
                                        <td>
                                            <?php $status_class = strtolower(str_replace(' ', '-', $row['status'])); ?>
                                            <span class="manage-task-status status-<?= htmlspecialchars($status_class) ?>"><?= $row['status'] ?></span>
                                        </td>
                                        <td>
                                            <div class="manage-task-actions">
                                                <a class="btn btn-primary btn-sm manage-task-action-btn" href="edit_task?task_id=<?= $row['id'] ?>">chat</a>
                                                <button class="btn btn-primary btn-sm manage-task-action-btn" data-bs-toggle="modal" data-bs-target="#editTaskModal<?= $row['id'] ?>">Edit</button>
                                                <form action="manage_task" method="POST" style="display: inline;">
                                                    <input type="hidden" name="task_id" value="<?= $row['id'] ?>">
                                                    <button type="submit" name="delete_task" class="btn btn-danger btn-sm manage-task-action-btn">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <!-- Edit Modal -->
                                    <div class="modal fade manage-task-modal" id="editTaskModal<?= $row['id'] ?>" tabindex="-1" aria-labelledby="editTaskModalLabel" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form action="manage_task" method="POST" enctype="multipart/form-data">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="editTaskModalLabel">Edit Task</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="task_id" value="<?= $row['id'] ?>">
                                                        <div class="mb-3">
                                                            <label for="title" class="form-label">Task Title</label>
                                                            <input type="text" name="title" id="title" class="form-control" value="<?= $row['title'] ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="description" class="form-label">Task Description</label>
                                                            <textarea name="description" id="description" class="form-control" rows="4" required><?= $row['description'] ?></textarea>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="due_date" class="form-label">Due Date</label>
                                                            <input type="date" name="due_date" id="due_date" class="form-control" value="<?= $row['due_date'] ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="status" class="form-label">Status</label>
                                                            <select name="status" id="status" class="form-control" required>
                                                                <option value="Pending" <?= $row['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                                <option value="In Progress" <?= $row['status'] == 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                                                <option value="Completed" <?= $row['status'] == 'Completed' ? 'selected' : '' ?>>Completed</option>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="remark" class="form-label">Remark</label>
                                                            <textarea name="remark" id="remark" class="form-control" rows="2"><?= $row['remark'] ?></textarea>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="new_documents" class="form-label">Add More Documents</label>
                                                            <input type="file" name="new_documents[]" id="new_documents" class="form-control" multiple>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            <button type="submit" name="update_task" class="btn btn-primary">Save Changes</button>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Existing Documents</label>
                                                            <ul class="manage-task-documents">
                                                                <?php
                                                                $documents = $conn->query("SELECT * FROM task_documents WHERE task_id = {$row['id']}");
                                                                while ($doc = $documents->fetch_assoc()) : ?>
                                                                    <li>
                                                                        <embed src="<?= $doc['file_path'] ?>" style="height:50px;width:50px;">
                                                                        <a href="<?= $doc['file_path'] ?>" target="_blank">View Document</a>
                                                                        <form action="manage_task" method="POST" style="display: inline;">
                                                                            <input type="hidden" name="document_id" value="<?= $doc['id'] ?>">
                                                                            <button type="submit" name="delete_document" class="btn btn-sm btn-danger manage-task-action-btn">Delete</button>
                                                                        </form>
                                                                    </li>
                                                                <?php endwhile; ?>
                                                            </ul>
                                                        </div>
                                                    </div>

                                                </form>

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
