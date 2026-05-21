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
?>
<style>
    :root {
        --chat-shell-bg: linear-gradient(180deg, #f6fbf7 0%, #edf7f0 100%);
        --chat-shell-border: rgba(148, 163, 184, 0.18);
        --chat-shell-shadow: 0 24px 56px rgba(15, 23, 42, 0.08);
    }

    body {
        background: linear-gradient(180deg, #f5fbf6 0%, #eaf5ee 100%);
    }

    .task-chat-page {
        padding-top: 0.95rem !important;
        padding-bottom: 1.2rem !important;
    }

    .task-chat-shell {
        border: 1px solid var(--chat-shell-border);
        border-radius: 30px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fcf9 100%);
        box-shadow: var(--chat-shell-shadow);
        overflow: hidden;
    }

    .task-chat-inner {
        padding: 1.3rem !important;
    }

    .task-chat-title {
        margin: 0;
        color: #0f172a;
        font-size: 1.2rem;
        font-weight: 800;
        letter-spacing: -0.02em;
    }

    .task-chat-subtitle {
        margin-top: 0.3rem;
        color: #64748b;
        font-size: 0.86rem;
        font-weight: 500;
    }

    .task-chat-overview {
        margin-top: 1rem;
    }

    .task-chat-overview .row {
        --bs-gutter-x: 1rem;
        --bs-gutter-y: 0;
    }

    .task-chat-panel {
        height: 100%;
        padding: 1rem 1.05rem;
        border: 1px solid #d8e9dc;
        border-radius: 22px;
        background: linear-gradient(180deg, #ffffff 0%, #f4fbf6 100%);
    }

    .task-chat-panel-label {
        display: block;
        margin-bottom: 0.45rem;
        color: #64748b;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .task-chat-panel-value {
        margin: 0;
        color: #0f172a;
        font-size: 0.95rem;
        font-weight: 700;
        line-height: 1.45;
    }

    .task-chat-status {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.5rem 0.8rem;
        border-radius: 999px;
        border: 1px solid #dbe4f0;
        background: #f8fafc;
        color: #475569;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }

    .task-chat-status.status-pending {
        background: #fff7db;
        border-color: #f8df9c;
        color: #9a6700;
    }

    .task-chat-status.status-in-progress {
        background: #e8f0ff;
        border-color: #bfd4ff;
        color: #1d4ed8;
    }

    .task-chat-status.status-completed {
        background: #ecfdf3;
        border-color: #bbf7d0;
        color: #15803d;
    }

    .task-chat-stream {
        margin-top: 1.5rem;
    }

    .task-chat-stream-title {
        margin-bottom: 0.8rem;
        color: #0f172a;
        font-size: 1rem;
        font-weight: 800;
    }

    .chat-box {
        height: 420px;
        padding: 1rem;
        border: 1px solid #cfe3d4;
        border-radius: 24px;
        background-color: #e7f4ea;
        background-image:
            radial-gradient(circle at 24px 24px, rgba(255, 255, 255, 0.38) 0, rgba(255, 255, 255, 0.38) 2px, transparent 2px),
            radial-gradient(circle at 0 0, rgba(181, 215, 189, 0.14) 0, rgba(181, 215, 189, 0.14) 1px, transparent 1px),
            linear-gradient(180deg, #edf8ef 0%, #e2f1e6 100%);
        background-size: 48px 48px, 24px 24px, 100% 100%;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .chat-box > div {
        max-width: min(78%, 520px);
        padding: 0.85rem 0.95rem;
        border-radius: 18px;
        position: relative;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
    }

    .chat-box p {
        margin-bottom: 0.35rem;
        font-size: 0.88rem;
        line-height: 1.5;
    }

    .chat-box small {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        color: #64748b !important;
        font-size: 0.72rem;
    }

    .chat-box .text-muted {
        align-self: center;
        margin: auto;
        color: #64748b !important;
    }

    .admin-message {
        background: linear-gradient(180deg, #fff4f5 0%, #ffe8eb 100%);
        border: 1px solid #fdcfd5;
        align-self: flex-start;
        color: #881337;
        border-bottom-left-radius: 8px;
    }

    .employee-message {
        background: linear-gradient(180deg, #eefcf1 0%, #ddf7e4 100%);
        border: 1px solid #bae7c6;
        align-self: flex-end;
        color: #166534;
        border-bottom-right-radius: 8px;
    }

    .fa-check-double {
        color: #16a34a;
    }

    .chat-input {
        margin-top: 0.95rem !important;
        display: flex;
        align-items: flex-end;
        gap: 0.75rem;
    }

    .chat-input textarea {
        flex: 1 1 auto;
        min-height: 52px;
        border-radius: 18px;
        border: 1px solid #d9e2ec;
        background: #ffffff;
        color: #0f172a;
        box-shadow: none;
        font-size: 0.92rem;
        font-weight: 500;
        padding: 0.9rem 1rem;
        resize: vertical;
    }

    .chat-input textarea:focus {
        border-color: #94a3b8;
        box-shadow: 0 0 0 4px rgba(148, 163, 184, 0.16);
    }

    .chat-input button {
        flex: 0 0 110px;
        min-height: 52px;
        border-radius: 18px;
        border: 0;
        background: linear-gradient(135deg, #123b76 0%, #1f4c8f 100%) !important;
        color: #ffffff !important;
        font-size: 0.78rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        box-shadow: 0 16px 28px rgba(15, 23, 42, 0.12);
    }

    @media (max-width: 767.98px) {
        .task-chat-page {
            padding-top: 0.6rem !important;
            padding-left: 0.3rem !important;
            padding-right: 0.3rem !important;
            padding-bottom: 0.85rem !important;
        }

        .task-chat-shell {
            border-radius: 22px;
        }

        .task-chat-inner {
            padding: 1rem !important;
        }

        .task-chat-title {
            font-size: 1rem;
        }

        .task-chat-subtitle {
            font-size: 0.78rem;
        }

        .task-chat-overview .row {
            --bs-gutter-x: 0.75rem;
        }

        .task-chat-panel {
            padding: 0.9rem;
            border-radius: 18px;
        }

        .task-chat-panel-value {
            font-size: 0.88rem;
        }

        .chat-box {
            height: 360px;
            padding: 0.85rem;
            border-radius: 20px;
        }

        .chat-box > div {
            max-width: 88%;
            padding: 0.78rem 0.82rem;
            border-radius: 16px;
        }

        .chat-input {
            gap: 0.55rem;
        }

        .chat-input textarea {
            min-height: 46px;
            border-radius: 14px;
            font-size: 0.78rem;
            padding: 0.8rem 0.85rem;
        }

        .chat-input button {
            flex-basis: 92px;
            min-height: 46px;
            border-radius: 14px;
            font-size: 0.7rem;
        }
    }
</style>

<?php $status_class = isset($task['status']) ? strtolower(str_replace(' ', '-', $task['status'])) : ''; ?>
<div class="container-fluid py-4 task-chat-page">
    <div class="task-chat-shell">
        <div class="task-chat-inner">
            <h3 class="task-chat-title">Task Details</h3>
            <div class="task-chat-subtitle">Track the assigned task summary and continue the same remark conversation in a cleaner, more professional workspace.</div>

            <form method="POST" enctype="multipart/form-data" class="task-chat-overview">
                <div class="row">
                    <div class="col-md-6 mt-4">
                        <div class="task-chat-panel">
                            <span class="task-chat-panel-label">Title</span>
                            <p class="task-chat-panel-value"><?= htmlspecialchars($task['title']) ?></p>
                        </div>
                    </div>
                    <div class="col-md-6 mt-4">
                        <div class="task-chat-panel">
                            <span class="task-chat-panel-label">Due Date</span>
                            <p class="task-chat-panel-value"><?= htmlspecialchars($task['due_date']) ?></p>
                        </div>
                    </div>
                    <div class="col-md-6 mt-4">
                        <div class="task-chat-panel">
                            <span class="task-chat-panel-label">Status</span>
                            <div class="task-chat-status status-<?= htmlspecialchars($status_class) ?>"><?= htmlspecialchars($task['status']) ?></div>
                        </div>
                    </div>
                    <div class="col-md-12 mt-4">
                        <div class="task-chat-panel">
                            <span class="task-chat-panel-label">Description</span>
                            <p class="task-chat-panel-value"><?= htmlspecialchars($task['description']) ?></p>
                        </div>
                    </div>
                </div>
            </form>

            <div class="task-chat-stream">
                <h5 class="task-chat-stream-title">Remarks</h5>
                <div id="remark_chat" class="chat-box">
                    <?php if (!empty($remarks)) : ?>
                        <?php foreach ($remarks as $remark) : ?>
                            <div class="<?= $remark['user_type'] === 'Manager' ? 'admin-message' : 'employee-message' ?>">
                                <p><strong><?= ucfirst($remark['user_type']) ?>:</strong> <?= htmlspecialchars($remark['remark']) ?></p>
                                <small class="text-muted"><?= $remark['created_at'] ?> 
                                    <i class="fas fa-check-double"></i>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <p class="text-muted">No messages yet.</p>
                    <?php endif; ?>
                </div>

                <div class="chat-input">
                    <textarea id="new_remark" class="form-control" rows="2" placeholder="Type a message..."></textarea>
                    <button type="button" id="add_remark_btn" class="btn btn-success">Send</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Fetch and display messages every 2 seconds
function fetchMessages() {
    const taskId = <?= $task_id ?>;
    fetch("fetch_remarks?task_id=" + taskId)
        .then((response) => response.text())
        .then((data) => {
            document.getElementById("remark_chat").innerHTML = data;
            document.getElementById("remark_chat").scrollTop = document.getElementById("remark_chat").scrollHeight;
        });
}

// Send a new remark
document.getElementById("add_remark_btn").addEventListener("click", function () {
    const remarkInput = document.getElementById("new_remark");
    const remarkText = remarkInput.value.trim();
    const taskId = <?= $task_id ?>;
    const userType = "<?= htmlspecialchars($employee_name) ?>"; // Adjust this dynamically based on the user

    if (remarkText) {
        fetch("add_remark", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ task_id: taskId, remark: remarkText, user_type: userType }),
        })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                fetchMessages(); // Refresh the chat messages
                remarkInput.value = ""; // Clear the input
            } else {
                alert("Failed to send message!");
            }
        });
    }
});

// Call fetchMessages every 2 seconds
setInterval(fetchMessages, 6000);
// Load messages initially
fetchMessages();
</script>

<?php include("footer.php"); ?>
