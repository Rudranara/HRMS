<?php
include("header.php");
include("db_connection.php");

// Fetch task details
if (isset($_GET['task_id'])) {
    $task_id = $_GET['task_id'];

    // Get task details
    $stmt = $conn->prepare("SELECT * FROM tasks WHERE id = ?");
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    $task = $stmt->get_result()->fetch_assoc();

    // Get task remarks
    $remarks_query = $conn->prepare("SELECT * FROM task_remarks WHERE task_id = ? ORDER BY created_at ASC");
    $remarks_query->bind_param("i", $task_id);
    $remarks_query->execute();
    $remarks_result = $remarks_query->get_result();
    $remarks = $remarks_result->fetch_all(MYSQLI_ASSOC);
}

// Update task
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $due_date = $_POST['due_date'];

    $stmt = $conn->prepare("UPDATE tasks SET title = ?, description = ?, due_date = ? WHERE id = ?");
    $stmt->bind_param("sssi", $title, $description, $due_date, $task_id);

    if ($stmt->execute()) {
        $message = "Task updated successfully!";
    } else {
        $message = "Failed to update task!";
    }
}
?>
<style>
.edit-task-page {
    padding-bottom: 1.5rem;
}

.edit-task-topbar,
.edit-task-card {
    border: 1px solid rgba(87, 96, 108, 0.12);
    border-radius: 22px;
    box-shadow: 0 14px 34px rgba(31, 41, 55, 0.06);
    background: #fff;
}

.edit-task-topbar {
    padding: 1.15rem 1.2rem;
    margin-bottom: 1rem;
}

.edit-task-topbar-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 1rem;
    align-items: center;
}

.edit-task-section-label {
    display: block;
    margin-bottom: 0.45rem;
    color: #6b7280;
    font-size: 0.74rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.edit-task-title {
    margin: 0;
    color: #0f172a;
    font-size: 1.45rem;
    font-weight: 800;
    letter-spacing: -0.03em;
}

.edit-task-copy {
    margin: 0.35rem 0 0;
    color: #6b7280;
    font-size: 0.92rem;
}

.edit-task-toolbar {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.8rem;
    flex-wrap: wrap;
}

.edit-task-btn-dark,
#add_remark_btn {
    background: linear-gradient(135deg, #161616 0%, #2d2d2d 100%) !important;
    color: #fff !important;
    border: none !important;
    box-shadow: 0 12px 24px rgba(17, 24, 39, 0.18);
}

.edit-task-btn-dark:hover,
#add_remark_btn:hover {
    background: linear-gradient(135deg, #101010 0%, #242424 100%) !important;
    color: #fff !important;
}

.edit-task-toolbar .btn,
.edit-task-toolbar a,
.edit-task-form-actions .btn,
#add_remark_btn {
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

.edit-task-card {
    overflow: hidden;
}

.edit-task-card-body {
    padding: 1.2rem;
}

.edit-task-alert {
    margin-bottom: 1rem;
    padding: 0.95rem 1.05rem;
    border-radius: 16px;
    border: 1px solid #dbe3ed;
    background: #f8fafc;
    color: #334155;
    font-size: 0.92rem;
    font-weight: 700;
}

.edit-task-form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem 1.1rem;
}

.edit-task-field {
    min-width: 0;
}

.edit-task-field-full {
    grid-column: 1 / -1;
}

.edit-task-field .form-label,
.edit-task-chat-title {
    display: block;
    margin-bottom: 0.45rem;
    color: #6b7280;
    font-size: 0.74rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.edit-task-field .form-control,
#new_remark {
    min-height: 46px;
    border-radius: 14px;
    border: 1px solid #d8dee7;
    box-shadow: none;
    background: #fff;
    color: #111827;
}

.edit-task-field .form-control:focus,
#new_remark:focus {
    border-color: #1e3a5f;
    box-shadow: 0 0 0 0.18rem rgba(30, 58, 95, 0.12);
}

.edit-task-field textarea.form-control {
    min-height: 130px;
}

.edit-task-form-actions {
    margin-top: 1.15rem;
}

.edit-task-chat-section {
    margin-top: 2rem;
}

.edit-task-chat-heading {
    margin: 0 0 0.85rem;
    color: #0f172a;
    font-size: 1.05rem;
    font-weight: 800;
}

.chat-box {
    height: 600px;
    border: 1px solid #dbe3ed;
    border-radius: 18px;
    padding: 1rem;
    background-color: #f4f7fb;
    background-image:
        radial-gradient(circle at 24px 24px, rgba(255, 255, 255, 0.55) 1.8px, transparent 0),
        radial-gradient(circle at 72px 72px, rgba(148, 163, 184, 0.08) 1.6px, transparent 0);
    background-size: 96px 96px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 0.9rem;
}

.chat-box div {
    max-width: 68%;
    padding: 0.9rem 1rem;
    border-radius: 16px;
    position: relative;
}

.text-start {
    background: #fbe6e5;
    align-self: flex-start;
    color: #7f1d1d;
    border: 1px solid #f4c9c7;
}

.text-end {
    background: #dff5e6;
    align-self: flex-end;
    color: #166534;
    border: 1px solid #b9dec8;
}

.chat-box p {
    margin: 0.35rem 0 0;
}

.chat-box hr {
    display: none;
}

.chat-input {
    margin-top: 1rem;
}

textarea#new_remark {
    min-height: 100px;
    border-radius: 16px;
    padding: 0.85rem 1rem;
}

button#add_remark_btn {
    margin-top: 0.75rem;
}

@media (max-width: 991.98px) {
    .edit-task-topbar-grid,
    .edit-task-form-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 767.98px) {
    .edit-task-toolbar,
    .edit-task-form-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .edit-task-toolbar .btn,
    .edit-task-toolbar a,
    .edit-task-form-actions .btn,
    #add_remark_btn,
    .chat-box div {
        width: 100%;
        max-width: 100%;
    }
}
</style>

<div class="container-fluid py-4 edit-task-page">
    <div class="row">
        <div class="col-12">
            <div class="edit-task-topbar">
                <div class="edit-task-topbar-grid">
                    <div>
                        <span class="edit-task-section-label">Task Workspace</span>
                        <h6 class="edit-task-title">Edit Task & Chat with Employee</h6>
                        <p class="edit-task-copy">Update task details and continue the conversation with the assigned employee.</p>
                    </div>
                    <div class="edit-task-toolbar">
                        <a href="manage_task" class="btn edit-task-btn-dark">Back To Tasks</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="edit-task-card">
                <div class="edit-task-card-body">
                    <?php if (isset($message)) : ?>
                        <div class="edit-task-alert"><?= $message ?></div>
                    <?php endif; ?>

                    <form method="POST" action="edit_task?task_id=<?= $task_id ?>" enctype="multipart/form-data">
                        <div class="edit-task-form-grid">
                            <div class="edit-task-field">
                                <label for="title" class="form-label">Task Title</label>
                                <input type="text" name="title" id="title" class="form-control" value="<?= $task['title'] ?>" required>
                            </div>
                            <div class="edit-task-field">
                                <label for="due_date" class="form-label">Due Date</label>
                                <input type="date" name="due_date" id="due_date" class="form-control" value="<?= $task['due_date'] ?>" required>
                            </div>
                            <div class="edit-task-field edit-task-field-full">
                                <label for="description" class="form-label">Task Description</label>
                                <textarea name="description" id="description" class="form-control" rows="3" required><?= $task['description'] ?></textarea>
                            </div>
                        </div>

                        <div class="edit-task-form-actions">
                            <button class="btn edit-task-btn-dark mb-0" type="submit">Update Profile</button>
                        </div>
                    </form>

                    <div class="edit-task-chat-section">
                        <h5 class="edit-task-chat-heading">Chat With Employee</h5>
                        <div id="remark_chat" class="chat-box border p-3">
                            <?php if (!empty($remarks)) : ?>
                                <?php foreach ($remarks as $remark) : ?>
                                    <div class="<?= $remark['user_type'] == "Manager" ? 'text-end' : 'text-start' ?>">
                                        <strong><?= ucfirst($remark['user_type']) ?>:</strong>
                                        <p><?= htmlspecialchars($remark['remark']) ?></p>
                                        <small class="text-muted"><?= $remark['created_at'] ?></small>
                                    </div>
                                    <hr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <p class="text-muted">No Message yet.</p>
                            <?php endif; ?>
                        </div>

                        <div class="chat-input mt-3">
                            <textarea id="new_remark" class="form-control" rows="2" placeholder="Write Your Message..."></textarea>
                            <button type="button" id="add_remark_btn" class="btn btn-primary mt-2">Send</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function fetchRemarks() {
    const taskId = <?= $task_id ?>;

    fetch(`fetch_remarks?task_id=${taskId}`)
        .then((response) => response.json())
        .then((data) => {
            const chatBox = document.getElementById("remark_chat");
            chatBox.innerHTML = ""; // Clear the chat box

            if (data.remarks.length > 0) {
                data.remarks.forEach((remark) => {
                    const remarkDiv = document.createElement("div");
                    remarkDiv.className =
                        remark.user_type === "Manager" ? "text-end" : "text-start";
                    remarkDiv.innerHTML = `
                        <strong>${remark.user_type}:</strong>
                        <p>${remark.remark}</p>
                        <small class="text-muted">${remark.created_at}</small>
                        <hr>
                    `;
                    chatBox.appendChild(remarkDiv);
                });

                // Scroll to the latest message
                chatBox.scrollTop = chatBox.scrollHeight;
            } else {
                chatBox.innerHTML = '<p class="text-muted">No messages yet.</p>';
            }
        })
        .catch((error) => console.error("Error fetching remarks:", error));
}

// Call fetchRemarks() every 3 seconds to update chat in real-time
setInterval(fetchRemarks, 3000);

document.getElementById("add_remark_btn").addEventListener("click", function () {
    const remarkInput = document.getElementById("new_remark");
    const remarkText = remarkInput.value.trim();
    const taskId = <?= $task_id ?>;
    const userType = "Manager"; // Adjust for logged-in user type

    if (remarkText) {
        fetch("add_remark", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ task_id: taskId, remark: remarkText, user_type: userType }),
        })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                // Immediately refresh chat messages
                fetchRemarks();
                remarkInput.value = ""; // Clear input
            } else {
                alert("Failed to send remark!");
            }
        });
    }
});

</script>
<?php include("footer.php"); ?>
