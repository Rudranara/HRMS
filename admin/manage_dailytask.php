<?php
include("header.php");
$current_date = date('Y-m-d'); // Get today's date
$rowsPerPage = 10;
$currentPage = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$searchTerm = isset($_GET['search']) ? trim((string) $_GET['search']) : '';
$offset = ($currentPage - 1) * $rowsPerPage;

$whereSql = '';
$searchLike = '';
if ($searchTerm !== '') {
    $whereSql = " WHERE e.name LIKE ? OR e.employee_id LIKE ? OR lr.task_title LIKE ? OR lr.task_description LIKE ?";
    $searchLike = '%' . $searchTerm . '%';
}

$countSql = "SELECT COUNT(*) AS total_rows FROM daily_tasks lr JOIN employees e ON lr.employee_id = e.id" . $whereSql;
$countStmt = $conn->prepare($countSql);
if ($searchTerm !== '') {
    $countStmt->bind_param("ssss", $searchLike, $searchLike, $searchLike, $searchLike);
}
$countStmt->execute();
$totalRows = (int) ($countStmt->get_result()->fetch_assoc()['total_rows'] ?? 0);
$countStmt->close();

$totalPages = max(1, (int) ceil($totalRows / $rowsPerPage));
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * $rowsPerPage;

$taskSql = "SELECT lr.*, e.name AS employee_name, e.employee_id FROM daily_tasks lr JOIN employees e ON lr.employee_id = e.id"
    . $whereSql
    . " ORDER BY lr.id DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($taskSql);
if ($searchTerm !== '') {
    $stmt->bind_param("ssssii", $searchLike, $searchLike, $searchLike, $searchLike, $rowsPerPage, $offset);
} else {
    $stmt->bind_param("ii", $rowsPerPage, $offset);
}
$stmt->execute();
$result = $stmt->get_result();
$paginationQuery = [];
if ($searchTerm !== '') {
    $paginationQuery['search'] = $searchTerm;
}

function normalizeDailyTaskSelfiePath(?string $path): string
{
    $path = trim((string) $path);

    if ($path === '') {
        return '';
    }

    $normalizedPath = str_replace('\\', '/', $path);

    if (
        preg_match('#^(?:[a-z]+:)?//#i', $normalizedPath)
        || str_starts_with($normalizedPath, 'data:')
        || str_starts_with($normalizedPath, '/')
        || str_starts_with($normalizedPath, '../')
    ) {
        return $normalizedPath;
    }

    if (str_starts_with($normalizedPath, 'uploads/daily_tasks/')) {
        return '../emp/' . $normalizedPath;
    }

    return '../' . ltrim($normalizedPath, '/');
}

?>
<?php
// Handle edit task
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_task_id'])) {
    $edit_task_id = $_POST['edit_task_id'];
    $edit_task_title = trim($_POST['edit_task_title']);
    $edit_task_description = trim($_POST['edit_task_description']);

    $stmt = $conn->prepare("UPDATE daily_tasks SET task_title = ?, task_description = ? WHERE id = ?");
    $stmt->bind_param("ssi", $edit_task_title, $edit_task_description, $edit_task_id);

    if ($stmt->execute()) {
        echo "<script>alert('Task updated successfully!'); window.location.href='manage_dailytask';</script>";
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
        echo "<script>alert('Task deleted successfully!'); window.location.href='manage_dailytask';</script>";
    } else {
        echo "<div class='alert alert-danger'>Failed to delete task.</div>";
    }
}

?>
<style>
.manage-dailytask-page {
    padding-bottom: 1.5rem;
}

.manage-dailytask-topbar,
.manage-dailytask-search-card,
.manage-dailytask-table-card {
    border: 1px solid rgba(87, 96, 108, 0.12);
    border-radius: 22px;
    box-shadow: 0 14px 34px rgba(31, 41, 55, 0.06);
    background: #fff;
}

.manage-dailytask-topbar {
    padding: 1.15rem 1.2rem;
    margin-bottom: 1rem;
}

.manage-dailytask-topbar-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.5rem;
    align-items: start;
}

.manage-dailytask-section-label {
    display: block;
    margin-bottom: 0.45rem;
    color: #6b7280;
    font-size: 0.74rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.manage-dailytask-title {
    margin: 0;
    color: #0f172a;
    font-size: 1.45rem;
    font-weight: 800;
    letter-spacing: -0.03em;
}

.manage-dailytask-copy {
    margin: 0.35rem 0 0;
    color: #6b7280;
    font-size: 0.92rem;
}

.manage-dailytask-search-card {
    padding: 1rem 1.1rem;
    margin-bottom: 1rem;
}

.manage-dailytask-search-row {
    display: flex;
    gap: 0.85rem;
    align-items: center;
}

.manage-dailytask-search-card .form-control,
.manage-dailytask-modal .form-control {
    min-height: 46px;
    border-radius: 14px;
    border: 1px solid #d8dee7;
    box-shadow: none;
}

.manage-dailytask-search-card .form-control:focus,
.manage-dailytask-modal .form-control:focus {
    border-color: #1e3a5f;
    box-shadow: 0 0 0 0.18rem rgba(30, 58, 95, 0.12);
}

.manage-dailytask-search-btn,
.manage-dailytask-modal .btn-primary {
    background: linear-gradient(135deg, #161616 0%, #2d2d2d 100%) !important;
    color: #fff !important;
    border: none !important;
    box-shadow: 0 12px 24px rgba(17, 24, 39, 0.18);
}

.manage-dailytask-search-btn:hover,
.manage-dailytask-modal .btn-primary:hover {
    background: linear-gradient(135deg, #101010 0%, #242424 100%) !important;
    color: #fff !important;
}

.manage-dailytask-table-card {
    overflow: hidden;
}

.manage-dailytask-table-card .card-body {
    padding: 0 0 1rem;
}

.manage-dailytask-table-wrap {
    padding: 0 1.2rem 1.15rem;
}

.manage-dailytask-table {
    margin-bottom: 0;
}

.manage-dailytask-table thead th {
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

.manage-dailytask-table tbody td {
    padding: 1rem 0.95rem;
    border-bottom: 1px solid #eef2f7;
    color: #1f2937;
    vertical-align: middle;
    font-size: 0.92rem;
}

.manage-dailytask-table tbody tr:last-child td {
    border-bottom: none;
}

.manage-dailytask-table tbody tr:hover {
    background: #fbfcfe;
}

.manage-dailytask-person-name {
    margin: 0;
    color: #0f172a;
    font-size: 0.95rem;
    font-weight: 700;
}

.manage-dailytask-person-meta {
    margin: 0.22rem 0 0;
    color: #6b7280;
    font-size: 0.82rem;
}

.manage-dailytask-report-title {
    color: #0f172a;
    font-weight: 700;
}

.manage-dailytask-description {
    color: #475569;
}

.manage-dailytask-selfie {
    width: 76px;
    height: 76px;
    object-fit: cover;
    display: block;
    border-radius: 14px;
    border: 1px solid #dbe3ed;
    background: #f8fafc;
    padding: 0.2rem;
}

.manage-dailytask-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: nowrap;
    white-space: nowrap;
}

.manage-dailytask-action-btn {
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

.manage-dailytask-action-btn.btn-warning {
    background: linear-gradient(135deg, #161616 0%, #2d2d2d 100%) !important;
    color: #fff !important;
    border: none !important;
    box-shadow: 0 12px 24px rgba(17, 24, 39, 0.18);
}

.manage-dailytask-action-btn.btn-dark {
    background: #16324f !important;
    color: #fff !important;
    border: 1px solid #16324f !important;
    box-shadow: none !important;
}

.manage-dailytask-action-btn.btn-dark:hover {
    background: #10263c !important;
    border-color: #10263c !important;
}

.manage-dailytask-action-btn.btn-danger {
    background: #fbe6e5 !important;
    color: #c24141 !important;
    border: 1px solid #f4c9c7 !important;
    box-shadow: none !important;
}

.manage-dailytask-action-btn.btn-danger:hover {
    background: #f7d8d6 !important;
    color: #a93232 !important;
}

.manage-dailytask-empty {
    margin: 1rem 1.2rem 0;
    padding: 1rem 1.1rem;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    background: #f8fafc;
    color: #6b7280;
    font-size: 0.9rem;
    font-weight: 700;
}

.manage-dailytask-modal .modal-dialog {
    max-width: 720px;
}

.manage-dailytask-modal .modal-content {
    border: 1px solid rgba(87, 96, 108, 0.12);
    border-radius: 18px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
    overflow: hidden;
}

.manage-dailytask-modal .modal-header,
.manage-dailytask-modal .modal-footer {
    border-color: #eef2f7;
    padding: 1rem 1.25rem;
}

.manage-dailytask-modal .modal-body {
    padding: 1.25rem;
}

.manage-dailytask-modal .modal-title {
    color: #0f172a;
    font-size: 1rem;
    font-weight: 700;
}

.manage-dailytask-modal .form-label {
    display: block;
    margin-bottom: 0.45rem;
    color: #6b7280;
    font-size: 0.74rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.manage-dailytask-modal textarea.form-control {
    min-height: 120px;
}

.manage-dailytask-modal .btn-secondary {
    background: #f3f4f6;
    color: #334155;
    border: none;
}

.manage-dailytask-pagination {
    padding: 0 1.2rem 1.2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.manage-dailytask-pagination-copy {
    color: #6b7280;
    font-size: 0.88rem;
    font-weight: 600;
}

.manage-dailytask-pagination .pagination {
    margin: 0;
}

.manage-dailytask-pagination .page-link {
    border-radius: 10px;
    border-color: #dbe3ed;
    color: #16324f;
    min-width: 40px;
    padding: 0.55rem 0.9rem;
    text-align: center;
    box-shadow: none;
    white-space: nowrap;
}

.manage-dailytask-pagination .page-item:first-child .page-link,
.manage-dailytask-pagination .page-item:last-child .page-link {
    min-width: 86px;
}

.manage-dailytask-pagination .page-item.active .page-link {
    background: #16324f;
    border-color: #16324f;
    color: #ffffff;
}

@media (max-width: 991.98px) {
    .manage-dailytask-search-row,
    .manage-dailytask-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .manage-dailytask-search-row .btn,
    .manage-dailytask-actions > * {
        width: 100%;
    }

    .manage-dailytask-pagination {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>

<div class="container-fluid py-4 manage-dailytask-page">
      <div class="row">
      <div class="col-12">
            <div class="manage-dailytask-topbar">
                <div class="manage-dailytask-topbar-grid">
                    <div>
                        <span class="manage-dailytask-section-label">Daily Reports</span>
                        <h6 class="manage-dailytask-title">Manage Daily Report</h6>
                        <p class="manage-dailytask-copy">Review submitted daily reports, inspect selfies, and update or remove entries.</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
          <div class="manage-dailytask-search-card">
              <form method="GET" class="mb-0">
                    <div class="manage-dailytask-search-row">
                            <input type="text" name="search" class="form-control" placeholder="Search by Name, Role or ID" value="<?= htmlspecialchars($searchTerm) ?>">
                            <button type="submit" class="btn manage-dailytask-search-btn mb-0">Search</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-12">
          <div class="card manage-dailytask-table-card mb-4">           
            <div class="card-body px-0 pt-0 pb-2">
              <div class="table-responsive manage-dailytask-table-wrap">
                <?php if ($result->num_rows > 0): ?>
                    <table class="table manage-dailytask-table align-items-center mb-0">
                        <thead>
                            <tr>
                            <th>Name</th>
                                <th>Report Title</th>
                                <th>Description</th>
                                <th>Selfie</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($task = $result->fetch_assoc()): ?>
                                <?php $selfiePath = normalizeDailyTaskSelfiePath($task['selfie'] ?? ''); ?>
                                <tr>
                                <td>
                                            <div class="d-flex px-2 py-1">
                                              
                                                <div class="d-flex flex-column justify-content-center">
                                                    <h6 class="manage-dailytask-person-name"><?= htmlspecialchars($task['employee_name']) ?></h6>
                                                    <p class="manage-dailytask-person-meta"><?= htmlspecialchars($task['employee_id']) ?></p>
                                                </div>
                                            </div>
                                        </td>
                                    <td><span class="manage-dailytask-report-title"><?= htmlspecialchars($task['task_title']) ?></span></td>
                                    <td><span class="manage-dailytask-description"><?= htmlspecialchars($task['task_description']) ?></span></td>
                                    <td>
                                        <?php if ($selfiePath !== ''): ?>
                                            <img src="<?= htmlspecialchars($selfiePath) ?>" alt="Selfie" class="img-thumbnail manage-dailytask-selfie">
                                        <?php else: ?>
                                            No Selfie
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                            <div class="manage-dailytask-actions">
                                            <button class="btn btn-warning btn-sm manage-dailytask-action-btn" data-bs-toggle="modal" data-bs-target="#editTaskModal" 
                                                data-id="<?= $task['id'] ?>" 
                                                data-title="<?= htmlspecialchars($task['task_title']) ?>"
                                                data-description="<?= htmlspecialchars($task['task_description']) ?>">Edit</button>
                                                <button class="btn btn-dark btn-sm manage-dailytask-action-btn" data-bs-toggle="modal" data-bs-target="#viewTaskModal" 
                                                data-id="<?= $task['id'] ?>" 
                                                data-title="<?= htmlspecialchars($task['task_title']) ?>" 
                                                    data-description="<?= htmlspecialchars($task['task_description']) ?>"
                                                    data-selfie="<?= htmlspecialchars($selfiePath) ?>">View</button>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="delete_task_id" value="<?= $task['id'] ?>">
                                                <button class="btn btn-danger btn-sm manage-dailytask-action-btn" onclick="return confirm('Are you sure you want to delete this task?');">Delete</button>
                                            </form>
                                            </div>
                                
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="manage-dailytask-empty">No tasks found.</div>
                <?php endif; ?>
                </div>
                <?php if ($totalRows > 0): ?>
                    <div class="manage-dailytask-pagination">
                        <div class="manage-dailytask-pagination-copy">
                            Showing <?= (($currentPage - 1) * $rowsPerPage) + 1 ?>-<?= min($currentPage * $rowsPerPage, $totalRows) ?> of <?= $totalRows ?> reports
                        </div>
                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="Daily report pagination">
                                <ul class="pagination">
                                    <?php $previousQuery = http_build_query(array_merge($paginationQuery, ['page' => max(1, $currentPage - 1)])); ?>
                                    <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?<?= htmlspecialchars($previousQuery) ?>">Previous</a>
                                    </li>
                                    <?php
                                        $visibleStart = max(1, $currentPage - 2);
                                        $visibleEnd = min($totalPages, $currentPage + 2);

                                        if ($visibleStart <= 3) {
                                            $visibleEnd = min($totalPages, 5);
                                        }

                                        if ($visibleEnd >= $totalPages - 2) {
                                            $visibleStart = max(1, $totalPages - 4);
                                        }
                                    ?>

                                    <?php if ($visibleStart > 1): ?>
                                        <?php $firstPageQuery = http_build_query(array_merge($paginationQuery, ['page' => 1])); ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?= htmlspecialchars($firstPageQuery) ?>">1</a>
                                        </li>
                                        <?php if ($visibleStart > 2): ?>
                                            <li class="page-item disabled"><span class="page-link">...</span></li>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <?php for ($page = $visibleStart; $page <= $visibleEnd; $page++): ?>
                                        <?php $pageQuery = http_build_query(array_merge($paginationQuery, ['page' => $page])); ?>
                                        <li class="page-item <?= $page === $currentPage ? 'active' : '' ?>">
                                            <a class="page-link" href="?<?= htmlspecialchars($pageQuery) ?>"><?= $page ?></a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($visibleEnd < $totalPages): ?>
                                        <?php if ($visibleEnd < $totalPages - 1): ?>
                                            <li class="page-item disabled"><span class="page-link">...</span></li>
                                        <?php endif; ?>
                                        <?php $lastPageQuery = http_build_query(array_merge($paginationQuery, ['page' => $totalPages])); ?>
                                        <li class="page-item <?= $totalPages === $currentPage ? 'active' : '' ?>">
                                            <a class="page-link" href="?<?= htmlspecialchars($lastPageQuery) ?>"><?= $totalPages ?></a>
                                        </li>
                                    <?php endif; ?>

                                    <?php $nextQuery = http_build_query(array_merge($paginationQuery, ['page' => min($totalPages, $currentPage + 1)])); ?>
                                    <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?<?= htmlspecialchars($nextQuery) ?>">Next</a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    
    <div class="modal fade manage-dailytask-modal" id="viewTaskModal" tabindex="-1" aria-labelledby="viewTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewTaskModalLabel">View Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="view_task_id" id="view_task_id">
                    <div class="mb-3">
                        <label for="view_task_title" class="form-label">Task Title</label>
                        <input type="text" class="form-control" name="view_task_title" id="view_task_title" readonly required>
                    </div>
                    <div class="mb-3">
                        <label for="view_task_description" class="form-label">Task Description</label>
                        <textarea class="form-control" name="view_task_description" id="view_task_description" rows="3" readonly required></textarea>
                    </div>
                    <div class="mb-3">
                        <a href="#" target="_blank" id="view_task_selfie_link" class="btn btn-sm btn-outline-dark d-none">View Photo</a>
                        <span id="view_task_selfie_empty" class="text-muted">No Selfie</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                  
                </div>
            </form>
        </div>
    </div>
</div>



<!-- Edit Task Modal -->
<div class="modal fade manage-dailytask-modal" id="editTaskModal" tabindex="-1" aria-labelledby="editTaskModalLabel" aria-hidden="true">
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

document.addEventListener('DOMContentLoaded', function () {
    const viewTaskModal = document.getElementById('viewTaskModal');
    viewTaskModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget; // Button that triggered the modal
        const taskId = button.getAttribute('data-id');
        const taskTitle = button.getAttribute('data-title');
        const taskDescription = button.getAttribute('data-description');
        const taskSelfie = button.getAttribute('data-selfie');

        // Populate the modal fields
        document.getElementById('view_task_id').value = taskId;
        document.getElementById('view_task_title').value = taskTitle;
        document.getElementById('view_task_description').value = taskDescription;

        const selfieLink = document.getElementById('view_task_selfie_link');
        const selfieEmpty = document.getElementById('view_task_selfie_empty');

        if (taskSelfie) {
            selfieLink.href = taskSelfie;
            selfieLink.classList.remove('d-none');
            selfieEmpty.classList.add('d-none');
        } else {
            selfieLink.href = '#';
            selfieLink.classList.add('d-none');
            selfieEmpty.classList.remove('d-none');
        }
    });
});
</script>
<?php include("footer.php"); ?>
