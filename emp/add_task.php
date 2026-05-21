<?php
include("header.php");
// Check if the employee is logged in
if (!isset($_SESSION['employee_id'])) {
    echo "<div class='alert alert-danger'>You must be logged in to add tasks.</div>";
    exit;
}
$employee_id = $_SESSION['employee_id']; // Get employee ID from session
// Handle form submission for adding a task
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require 'db_connection.php';

    $task_title = trim($_POST['task_title']);
    $task_description = trim($_POST['task_description']);
    $task_date = date('Y-m-d'); // Get current date
    $selfie_path = null;

    // Check if a task already exists for today
    $stmt = $conn->prepare("SELECT id FROM daily_tasks WHERE employee_id = ? AND task_date = ?");
    $stmt->bind_param("ss", $employee_id, $task_date);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Task already exists for today
        echo "<div class='alert alert-warning add-task-alert add-task-alert-warning'>You have already added a task for today!</div>";
    } else {
        // Validate inputs
        if (empty($task_title) || empty($task_description)) {
            echo "<div class='alert alert-danger add-task-alert add-task-alert-danger'>Task title and description are required.</div>";
        } else {
            // Handle selfie upload
            if (isset($_FILES['selfie']) && $_FILES['selfie']['error'] === UPLOAD_ERR_OK) {
                $target_dir = "uploads/daily_tasks/";
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0755, true); // Ensure uploads/selfies directory exists
                }

                $selfie_name = time() . '_' . basename($_FILES['selfie']['name']);
                $selfie_path = $target_dir . $selfie_name;

                if (!move_uploaded_file($_FILES['selfie']['tmp_name'], $selfie_path)) {
                    echo "<div class='alert alert-warning add-task-alert add-task-alert-warning'>Failed to upload selfie. Task will be saved without selfie.</div>";
                    $selfie_path = null;
                }
            }

            // Insert task into the database
            $stmt = $conn->prepare("INSERT INTO daily_tasks (employee_id, task_title, task_description, task_date, selfie) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $employee_id, $task_title, $task_description, $task_date, $selfie_path);

            if ($stmt->execute()) {
                echo "<div class='alert alert-success add-task-alert add-task-alert-success'>Task added successfully!</div>";
            } else {
                echo "<div class='alert alert-danger add-task-alert add-task-alert-danger'>Failed to add task. Please try again later.</div>";
            }
        }
    }
}
?>
<style>
    body {
        background: linear-gradient(180deg, #f8fafc 0%, #eef3f8 100%);
    }

    .add-task-page {
        padding-top: 0.95rem !important;
        padding-bottom: 1.2rem !important;
    }

    .add-task-shell {
        border: 1px solid rgba(148, 163, 184, 0.16);
        border-radius: 30px;
        background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
        box-shadow: 0 24px 56px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .add-task-inner {
        padding: 1.3rem !important;
    }

    .add-task-title {
        margin: 0;
        color: #0f172a;
        font-size: 1.2rem;
        font-weight: 800;
        letter-spacing: -0.02em;
    }

    .add-task-subtitle {
        margin-top: 0.3rem;
        color: #64748b;
        font-size: 0.86rem;
        font-weight: 500;
    }

    .add-task-form {
        margin-top: 1rem;
    }

    .add-task-form .row {
        --bs-gutter-x: 1rem;
        --bs-gutter-y: 0;
    }

    .add-task-group {
        margin-top: 1rem !important;
    }

    .add-task-label {
        display: block;
        margin-bottom: 0.45rem;
        color: #475569;
        font-size: 0.75rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .add-task-field {
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

    .add-task-field:focus {
        border-color: #94a3b8;
        box-shadow: 0 0 0 4px rgba(148, 163, 184, 0.16);
    }

    textarea.add-task-field {
        min-height: 110px;
        resize: vertical;
    }

    .add-task-upload {
        margin-top: 1rem !important;
        padding: 1rem;
        border-radius: 22px;
        background: linear-gradient(180deg, #f8fbff 0%, #f1f5f9 100%);
        border: 1px solid #dbe4f0;
    }

    .add-task-upload-note {
        margin-top: 0.35rem;
        color: #64748b;
        font-size: 0.78rem;
    }

    .add-task-submit-wrap {
        margin-top: 1.1rem !important;
    }

    .add-task-submit {
        min-height: 48px;
        padding: 0.8rem 1rem;
        border-radius: 16px;
        border: 0;
        background: linear-gradient(135deg, #111827 0%, #1f2937 100%) !important;
        color: #ffffff !important;
        font-size: 0.8rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        box-shadow: 0 16px 28px rgba(15, 23, 42, 0.12);
    }

    .add-task-alert {
        margin: 0.85rem 1rem 0 !important;
        border-radius: 16px;
        font-weight: 600;
    }

    .add-task-alert-success {
        border: 1px solid #bbf7d0;
        background: #f0fdf4;
        color: #166534;
    }

    .add-task-alert-warning {
        border: 1px solid #fde68a;
        background: #fffbeb;
        color: #a16207;
    }

    .add-task-alert-danger {
        border: 1px solid #fecdd3;
        background: #fff1f2;
        color: #be123c;
    }

    @media (max-width: 767.98px) {
        .add-task-page {
            padding-top: 0.6rem !important;
            padding-left: 0.3rem !important;
            padding-right: 0.3rem !important;
            padding-bottom: 0.85rem !important;
        }

        .add-task-shell {
            border-radius: 22px;
        }

        .add-task-inner {
            padding: 1rem !important;
        }

        .add-task-title {
            font-size: 1rem;
        }

        .add-task-subtitle {
            font-size: 0.78rem;
        }

        .add-task-form .row {
            --bs-gutter-x: 0.75rem;
        }

        .add-task-field,
        .add-task-submit {
            min-height: 44px;
            border-radius: 14px;
            font-size: 0.78rem;
        }

        .add-task-upload {
            padding: 0.85rem;
            border-radius: 18px;
        }

        .add-task-alert {
            margin: 0.7rem 0.3rem 0 !important;
        }
    }
</style>

<div class="container-fluid py-4 add-task-page">
    <div class="add-task-shell">
        <div class="add-task-inner">
            <h3 class="add-task-title">Add Your Daily Report</h3>
            <div class="add-task-subtitle">Create today&apos;s report with the same fields and upload flow in a cleaner, more professional layout.</div>
            <form method="POST" enctype="multipart/form-data" class="add-task-form">
                <div class="row">
                    <div class="col-md-6 add-task-group">
                        <label for="task_title" class="form-label add-task-label">Report Title</label>
                        <input class="form-control add-task-field" type="text" name="task_title" id="task_title" required>
                    </div>
                    <div class="col-md-6 add-task-group">
                        <label for="task_description" class="form-label add-task-label">Report Description</label>
                        <textarea class="form-control add-task-field" name="task_description" id="task_description" rows="3" required></textarea>
                    </div>
                    <div class="col-md-6 add-task-group">
                        <div class="add-task-upload">
                            <label for="selfie" class="form-label add-task-label">Selfie (Optional)</label>
                            <input class="form-control add-task-field" type="file" name="selfie" id="selfie" accept="image/*">
                            <div class="add-task-upload-note">Upload an image if you want to attach a selfie with today&apos;s report.</div>
                        </div>
                    </div>
                    <div class="col-md-12 add-task-submit-wrap">
                        <button class="btn bg-gradient-dark mb-0 add-task-submit" type="submit">Add Report</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include("footer.php"); ?>
