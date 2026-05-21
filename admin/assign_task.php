<?php include("header.php"); ?>
<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employee_id = $_POST['employee_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $due_date = $_POST['due_date'];
    $remark = $_POST['remark'];
    $upload_dir = "../uploads/tasks/";

    // Insert task into the database
    $stmt = $conn->prepare("INSERT INTO tasks (employee_id, title, description, due_date, remark) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $employee_id, $title, $description, $due_date, $remark);
    if ($stmt->execute()) {
        $task_id = $stmt->insert_id;

        // Handle multiple file uploads
        if (!empty($_FILES['documents']['name'][0])) {
            foreach ($_FILES['documents']['name'] as $key => $filename) {
                $target_file = $upload_dir . basename($filename);
                if (move_uploaded_file($_FILES['documents']['tmp_name'][$key], $target_file)) {
                    // Insert document record into the database
                    $stmt = $conn->prepare("INSERT INTO task_documents (task_id, file_path) VALUES (?, ?)");
                    $stmt->bind_param("is", $task_id, $target_file);
                    $stmt->execute();
                }
            }
        }

        $message = "Task assigned successfully!";
    } else {
        $message = "Failed to assign task!";
    }
}
?>
<style>
.assign-task-page {
    padding-bottom: 1.5rem;
}

.assign-task-topbar,
.assign-task-form-card {
    border: 1px solid rgba(87, 96, 108, 0.12);
    border-radius: 22px;
    box-shadow: 0 14px 34px rgba(31, 41, 55, 0.06);
    background: #fff;
}

.assign-task-topbar {
    padding: 1.15rem 1.2rem;
    margin-bottom: 1rem;
}

.assign-task-topbar-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 1rem;
    align-items: center;
}

.assign-task-section-label {
    display: block;
    margin-bottom: 0.45rem;
    color: #6b7280;
    font-size: 0.74rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.assign-task-title {
    margin: 0;
    color: #0f172a;
    font-size: 1.45rem;
    font-weight: 800;
    letter-spacing: -0.03em;
}

.assign-task-copy {
    margin: 0.35rem 0 0;
    color: #6b7280;
    font-size: 0.92rem;
}

.assign-task-toolbar {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.8rem;
    flex-wrap: wrap;
}

.assign-task-toolbar .btn,
.assign-task-toolbar a,
.assign-task-actions .btn {
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

.assign-task-btn-dark,
.assign-task-actions .btn-primary {
    background: linear-gradient(135deg, #161616 0%, #2d2d2d 100%) !important;
    color: #fff !important;
    border: none !important;
    box-shadow: 0 12px 24px rgba(17, 24, 39, 0.18);
}

.assign-task-btn-dark:hover,
.assign-task-actions .btn-primary:hover {
    background: linear-gradient(135deg, #101010 0%, #242424 100%) !important;
    color: #fff !important;
}

.assign-task-form-card {
    overflow: hidden;
}

.assign-task-form-body {
    padding: 1.2rem;
}

.assign-task-alert {
    margin-bottom: 1rem;
    padding: 0.95rem 1.05rem;
    border-radius: 16px;
    border: 1px solid #dbe3ed;
    background: #f8fafc;
    color: #334155;
    font-size: 0.92rem;
    font-weight: 700;
}

.assign-task-form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem 1.1rem;
}

.assign-task-field {
    min-width: 0;
}

.assign-task-field-full {
    grid-column: 1 / -1;
}

.assign-task-field .form-label {
    display: block;
    margin-bottom: 0.45rem;
    color: #6b7280;
    font-size: 0.74rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.assign-task-field .form-control {
    min-height: 46px;
    border-radius: 14px;
    border: 1px solid #d8dee7;
    box-shadow: none;
    background: #fff;
    color: #111827;
}

.assign-task-field .form-control:focus {
    border-color: #1e3a5f;
    box-shadow: 0 0 0 0.18rem rgba(30, 58, 95, 0.12);
}

.assign-task-field textarea.form-control {
    min-height: 110px;
}

#file-input-container .input-group {
    display: flex;
    gap: 0.65rem;
    align-items: center;
}

#file-input-container .form-control {
    min-height: 46px;
}

#file-input-container .btn {
    min-height: 46px;
    border-radius: 14px;
    padding: 0.56rem 1rem;
    font-size: 0.78rem;
    font-weight: 700;
    white-space: nowrap;
}

#file-input-container .add-file {
    background: #16324f;
    border: 1px solid #16324f;
    color: #fff;
}

#file-input-container .add-file:hover {
    background: #10263c;
    border-color: #10263c;
}

#file-input-container .remove-file {
    background: #fbe6e5;
    color: #c24141;
    border: 1px solid #f4c9c7;
}

#file-input-container .remove-file:hover {
    background: #f7d8d6;
    color: #a93232;
}

.assign-task-actions {
    margin-top: 1.25rem;
}

@media (max-width: 991.98px) {
    .assign-task-topbar-grid,
    .assign-task-form-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 767.98px) {
    .assign-task-toolbar,
    .assign-task-actions,
    #file-input-container .input-group {
        flex-direction: column;
        align-items: stretch;
    }

    .assign-task-toolbar .btn,
    .assign-task-toolbar a,
    .assign-task-actions .btn,
    #file-input-container .btn {
        width: 100%;
    }
}
</style>

<div class="container-fluid py-4 assign-task-page">
    <div class="row">
        <div class="col-12">
            <div class="assign-task-topbar">
                <div class="assign-task-topbar-grid">
                    <div>
                        <span class="assign-task-section-label">Task Assignment</span>
                        <h3 class="assign-task-title">Assign Task</h3>
                        <p class="assign-task-copy">Create and assign a new task with due date, notes, and supporting documents.</p>
                    </div>
                    <div class="assign-task-toolbar">
                        <a href="manage_task" class="btn assign-task-btn-dark">Back To Tasks</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="assign-task-form-card">
                <div class="assign-task-form-body">
                    <?php if (isset($message)) : ?>
                        <div class="assign-task-alert"><?= $message ?></div>
                    <?php endif; ?>

                    <form action="assign_task" method="POST" enctype="multipart/form-data">
                        <div class="assign-task-form-grid">
                            <div class="assign-task-field">
                                <label for="employee_id" class="form-label">Employee</label>
                                <select name="employee_id" id="employee_id" class="form-control" required>
                                    <option value="" disabled selected>Select Employee</option>
                                    <?php
                                    $result = $conn->query("SELECT id, name FROM employees WHERE status = 'Active'");
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<option value='{$row['id']}'>{$row['name']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="assign-task-field">
                                <label for="title" class="form-label">Task Title</label>
                                <input type="text" name="title" id="title" class="form-control" required>
                            </div>

                            <div class="assign-task-field">
                                <label for="description" class="form-label">Task Description</label>
                                <textarea name="description" id="description" class="form-control" rows="2" required></textarea>
                            </div>

                            <div class="assign-task-field">
                                <label for="due_date" class="form-label">Due Date</label>
                                <input type="date" name="due_date" id="due_date" class="form-control" required>
                            </div>

                            <div class="assign-task-field assign-task-field-full">
                                <label for="remark" class="form-label">Remark</label>
                                <textarea name="remark" id="remark" class="form-control" rows="2"></textarea>
                            </div>

                            <div class="assign-task-field assign-task-field-full">
                                <label for="documents" class="form-label">Attach Documents</label>
                                <div id="file-input-container">
                                    <div class="input-group mb-3">
                                        <input type="file" name="documents[]" class="form-control" accept=".pdf, .doc, .docx, .jpg, .png">
                                        <button type="button" class="btn btn-secondary add-file">Add More</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="assign-task-actions">
                            <button type="submit" class="btn btn-primary">Assign Task</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const container = document.getElementById("file-input-container");
        container.addEventListener("click", function (e) {
            if (e.target.classList.contains("add-file")) {
                const newInput = document.createElement("div");
                newInput.classList.add("input-group", "mb-3");
                newInput.innerHTML = `
                    <input type="file" name="documents[]" class="form-control" accept=".pdf, .doc, .docx, .jpg, .png">
                    <button type="button" class="btn btn-danger remove-file">Remove</button>
                `;
                container.appendChild(newInput);
            }
            if (e.target.classList.contains("remove-file")) {
                e.target.parentElement.remove();
            }
        });
    });
</script>
<?php include("footer.php"); ?>
