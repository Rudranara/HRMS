<?php
include("header.php");
// Check if the employee is logged in
if (!isset($_SESSION['employee_id'])) {
    echo "<div class='alert alert-danger'>You must be logged in to manage tasks.</div>";
    exit;
}
$employee_id = $_SESSION['employee_id']; // Get employee ID from session
$current_date = date('Y-m-d'); // Get today's date

// Fetch tasks
require 'db_connection.php';
$stmt = $conn->prepare("SELECT * FROM daily_tasks WHERE employee_id = ?");
$stmt->bind_param("s", $employee_id);
$stmt->execute();
$result = $stmt->get_result();

?>

<style>
    :root {
        --task-shell-bg: linear-gradient(180deg, #f8fafc 0%, #eef3f8 100%);
        --task-shell-border: rgba(148, 163, 184, 0.18);
        --task-shell-shadow: 0 24px 56px rgba(15, 23, 42, 0.08);
    }

    .manage-task-page {
        padding-top: 0.95rem !important;
        padding-bottom: 1.2rem !important;
    }

    .manage-task-title {
        margin: 0;
        color: #0f172a;
        font-size: 1.15rem;
        font-weight: 800;
        letter-spacing: -0.02em;
    }

    .manage-task-header-row {
        align-items: center;
    }

    .manage-task-cta {
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

    .manage-task-card {
        border: 1px solid var(--task-shell-border);
        border-radius: 28px;
        background: var(--task-shell-bg);
        box-shadow: var(--task-shell-shadow);
        overflow: hidden;
    }

    .manage-task-shell {
        background: #ffffff;
    }

    .manage-task-search {
        margin: 1.15rem 1.1rem 0.8rem !important;
    }

    .manage-task-search .row {
        --bs-gutter-x: 0.8rem;
        align-items: center;
    }

    .manage-task-input {
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

    .manage-task-input:focus {
        border-color: #94a3b8;
        box-shadow: 0 0 0 4px rgba(148, 163, 184, 0.16);
    }

    .manage-task-search-btn {
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

    .manage-task-wrap {
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
    }

    .manage-task-table {
        margin-bottom: 0;
        min-width: 880px;
    }

    .manage-task-table thead th {
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

    .manage-task-table tbody td {
        padding: 1rem;
        border-bottom: 1px solid #eef2f7;
        vertical-align: middle;
        color: #1f2937;
        font-size: 0.88rem;
    }

    .manage-task-table tbody tr:hover {
        background: #fbfdff;
    }

    .manage-task-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .manage-task-selfie {
        width: 96px;
        height: 96px;
        object-fit: cover;
        border-radius: 16px;
        border: 2px solid #e2e8f0;
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
    }

    .manage-task-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
        align-items: center;
    }

    .manage-task-action {
        min-height: 36px;
        padding: 0.58rem 0.78rem;
        border-radius: 12px;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        box-shadow: none !important;
    }

    .manage-task-action.btn-warning {
        background: #fff7db;
        border-color: #f8df9c;
        color: #9a6700;
    }

    .manage-task-action.btn-warning:hover,
    .manage-task-action.btn-warning:focus {
        background: #ffefbf;
        border-color: #f4cf72;
        color: #7c5200;
    }

    .manage-task-action.btn-danger {
        background: #fff1f2;
        border-color: #fecdd3;
        color: #c24153;
    }

    .manage-task-action.btn-danger:hover,
    .manage-task-action.btn-danger:focus {
        background: #ffe4e8;
        border-color: #fda4af;
        color: #9f1239;
    }

    .manage-task-action:disabled,
    .manage-task-action.disabled {
        opacity: 1;
    }

    .manage-task-modal .modal-content {
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 24px;
        box-shadow: 0 26px 60px rgba(15, 23, 42, 0.16);
        overflow: hidden;
    }

    .manage-task-modal .modal-header,
    .manage-task-modal .modal-footer {
        background: #ffffff;
        border-color: #eef2f7;
    }

    .manage-task-modal .modal-body {
        background: #f8fafc;
    }

    .manage-task-modal .modal-title {
        color: #0f172a;
        font-size: 1rem;
        font-weight: 800;
    }

    .manage-task-modal .form-label {
        color: #475569;
        font-size: 0.75rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .manage-task-modal .form-control {
        min-height: 44px;
        border-radius: 14px;
        border: 1px solid #d9e2ec;
        box-shadow: none;
    }

    .manage-task-modal textarea.form-control {
        min-height: 108px;
    }

    .manage-task-empty {
        margin: 1rem 1rem 1.2rem;
        border-radius: 16px;
        border: 1px solid #d8e6f6;
        background: #eef6ff;
        color: #123b76;
        font-weight: 600;
    }

    @media (max-width: 767.98px) {
        .manage-task-page {
            padding-top: 0.6rem !important;
            padding-left: 0.3rem !important;
            padding-right: 0.3rem !important;
            padding-bottom: 0.85rem !important;
        }

        .manage-task-header-row {
            flex-wrap: nowrap;
            align-items: center;
            min-width: 0;
        }

        .manage-task-title-col {
            flex: 1 1 auto;
            max-width: calc(100% - 154px);
            width: calc(100% - 154px);
            margin-bottom: 0.8rem !important;
            padding-right: 0.35rem;
        }

        .manage-task-action-col {
            flex: 0 0 154px;
            max-width: 154px;
            width: 154px;
            margin-bottom: 0.8rem !important;
            text-align: right !important;
        }

        .manage-task-title {
            font-size: 0.94rem;
            line-height: 1.2;
        }

        .manage-task-cta {
            width: 100%;
            min-height: 40px;
            padding: 0.66rem 0.72rem;
            border-radius: 14px;
            font-size: 0.69rem;
            letter-spacing: 0.05em;
        }

        .manage-task-card {
            border-radius: 22px;
        }

        .manage-task-search {
            margin: 1rem 0.85rem 0.75rem !important;
        }

        .manage-task-search .row {
            --bs-gutter-x: 0.6rem;
            --bs-gutter-y: 0.65rem;
        }

        .manage-task-input,
        .manage-task-search-btn {
            min-height: 42px;
            border-radius: 14px;
            font-size: 0.76rem;
        }

        .manage-task-table {
            min-width: 760px;
        }

        .manage-task-table thead th,
        .manage-task-table tbody td {
            padding: 0.82rem 0.78rem;
        }

        .manage-task-selfie {
            width: 72px;
            height: 72px;
            border-radius: 14px;
        }
    }
</style>
    <div class="container-fluid py-4 manage-task-page">
        <div class="row">
      <div class="col-12">
                    <div class="row manage-task-header-row">
                        <div class="col-6 mb-4 d-flex align-items-center manage-task-title-col">
                            <h6 class="mb-0 manage-task-title">Manage Daily Report</h6>
                        </div>
                        <div class="col-6 mb-4 text-end manage-task-action-col">
                            <a href="add_task" class="btn bg-gradient-dark mb-0 manage-task-cta">Add Today Report</a>
                        </div>
                    </div>
                    </div>
        <div class="col-12">
          <div class="card mb-4 manage-task-card">           
            <div class="card-body px-0 pt-0 pb-2 manage-task-shell">
              <div class="table-responsive p-0 manage-task-wrap">
              <form method="GET" class="mb-3 mt-4 manage-task-search">
                    <div class="row">
                        <div class="col-md-10">
                            <input type="text" name="search" class="form-control manage-task-input" placeholder="Search by Name, Role or ID" value="">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn bg-gradient-dark mb-0 manage-task-search-btn">Search</button>
                        </div>
                    </div>
                </form>
                <?php if ($result->num_rows > 0): ?>
                    <table class="table align-items-center mb-0 manage-task-table">
                        <thead>
                            <tr>
                                <th>Report Title</th>
                                <th>Description</th>
                                <th>Selfie</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($task = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($task['task_title']) ?></td>
                                    <td><?= htmlspecialchars($task['task_description']) ?></td>
                                    <td>
                                        <?php if ($task['selfie']): ?>
                                            <img src="<?= htmlspecialchars($task['selfie']) ?>" alt="Selfie" class="img-thumbnail manage-task-selfie" style="max-width: 100px;">
                                        <?php else: ?>
                                            No Selfie
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="manage-task-actions">
                                        <?php if ($task['task_date'] === $current_date): ?>
                                            <button class="btn btn-warning btn-sm manage-task-action" data-bs-toggle="modal" data-bs-target="#editTaskModal" 
                                                data-id="<?= $task['id'] ?>" 
                                                data-title="<?= htmlspecialchars($task['task_title']) ?>" 
                                                data-description="<?= htmlspecialchars($task['task_description']) ?>">Edit</button>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="delete_task_id" value="<?= $task['id'] ?>">
                                                <button class="btn btn-danger btn-sm manage-task-action" onclick="return confirm('Are you sure you want to delete this task?');">Delete</button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn btn-warning btn-sm manage-task-action" disabled>Edit</button>
                                            <button class="btn btn-danger btn-sm manage-task-action" disabled>Delete</button>
                                        <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="alert alert-info manage-task-empty">No tasks found.</div>
                <?php endif; ?>
                </div>
            </div>
          </div>
        </div>
      </div>
    </div>
<!-- Edit Task Modal -->
<div class="modal fade manage-task-modal" id="editTaskModal" tabindex="-1" aria-labelledby="editTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="editTaskModalLabel">Edit Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="edit_task_id" id="edit_task_id">
                    <div class="mb-3">
                        <label for="edit_task_title" class="form-label">Task Title</label>
                        <input type="text" class="form-control" name="edit_task_title" id="edit_task_title" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_task_description" class="form-label">Task Description</label>
                        <textarea class="form-control" name="edit_task_description" id="edit_task_description" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Handle edit task
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_task_id'])) {
    $edit_task_id = $_POST['edit_task_id'];
    $edit_task_title = trim($_POST['edit_task_title']);
    $edit_task_description = trim($_POST['edit_task_description']);

    $stmt = $conn->prepare("UPDATE daily_tasks SET task_title = ?, task_description = ? WHERE id = ?");
    $stmt->bind_param("ssi", $edit_task_title, $edit_task_description, $edit_task_id);

    if ($stmt->execute()) {
        echo "<script>alert('Task updated successfully!'); window.location.href='manage_task';</script>";
    } else {
        echo "<div class='alert alert-danger'>Failed to update task.</div>";
    }
}

// Handle delete task
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_task_id'])) {
    $delete_task_id = $_POST['delete_task_id'];

    $stmt = $conn->prepare("DELETE FROM daily_tasks WHERE id = ?");
    $stmt->bind_param("i", $delete_task_id);

    if ($stmt->execute()) {
        echo "<script>alert('Task deleted successfully!'); window.location.href='manage_task';</script>";
    } else {
        echo "<div class='alert alert-danger'>Failed to delete task.</div>";
    }
}

include("footer.php");
?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const editTaskModal = document.getElementById('editTaskModal');
    editTaskModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget; // Button that triggered the modal
        const taskId = button.getAttribute('data-id');
        const taskTitle = button.getAttribute('data-title');
        const taskDescription = button.getAttribute('data-description');

        // Populate the modal fields
        document.getElementById('edit_task_id').value = taskId;
        document.getElementById('edit_task_title').value = taskTitle;
        document.getElementById('edit_task_description').value = taskDescription;
    });
});
</script>
