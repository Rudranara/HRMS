<?php
include("header.php");
require 'db_connection.php';

// Check login
if (!isset($_SESSION['employee_id'])) {
    echo "<div class='alert alert-danger'>You must be logged in to raise a ticket.</div>";
    exit;
}

$employee_id = $_SESSION['employee_id'];

// Get manager ID of logged-in employee
$query = $conn->prepare("SELECT manager FROM employees WHERE id = ?");
$query->bind_param("s", $employee_id);
$query->execute();
$query->bind_result($manager_id);
$query->fetch();
$query->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    if (empty($subject) || empty($message)) {
        echo "<div class='alert alert-danger'>Subject and message are required.</div>";
    } else {
        // Insert ticket
        $stmt = $conn->prepare("
            INSERT INTO tickets (employee_id, manager_id, subject, message)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("iiss", $employee_id, $manager_id, $subject, $message);

        if ($stmt->execute()) {
            echo "<div class='alert alert-success'>Ticket raised successfully! Your manager and admin will review it.</div>
            <script>
                setTimeout(function() {
                    location.replace(document.referrer);
                }, 2000);
            </script>";
        } else {
            echo "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
        }
    }
}
?>

<style>
    .create-ticket-page {
        padding-top: 0.95rem !important;
        padding-bottom: 1.2rem !important;
    }

    .create-ticket-card {
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 28px;
        background: linear-gradient(180deg, #f8fafc 0%, #eef3f8 100%);
        box-shadow: 0 24px 56px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .create-ticket-card .card-header {
        padding: 1.25rem 1.35rem 0.5rem;
        border-bottom: 0;
        background: transparent;
    }

    .create-ticket-title {
        margin: 0;
        color: #0f172a;
        font-size: 1.08rem;
        font-weight: 800;
        letter-spacing: -0.02em;
    }

    .create-ticket-form-wrap {
        margin: 0 1rem 1rem;
        padding: 1.2rem;
        border: 1px solid #e2e8f0;
        border-radius: 22px;
        background: #ffffff;
    }

    .create-ticket-form-wrap .row {
        --bs-gutter-x: 1rem;
        --bs-gutter-y: 0.25rem;
    }

    .create-ticket-form-wrap .form-label {
        margin-bottom: 0.55rem;
        color: #475569;
        font-size: 0.74rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .create-ticket-form-wrap .form-control {
        min-height: 48px;
        border-radius: 16px;
        border: 1px solid #d9e2ec;
        background: #ffffff;
        color: #0f172a;
        font-size: 0.93rem;
        font-weight: 500;
        box-shadow: none;
        padding: 0.85rem 0.95rem;
    }

    .create-ticket-form-wrap textarea.form-control {
        min-height: 140px;
        resize: vertical;
    }

    .create-ticket-form-wrap .form-control:focus {
        border-color: #94a3b8;
        box-shadow: 0 0 0 4px rgba(148, 163, 184, 0.16);
    }

    .create-ticket-submit {
        min-height: 46px;
        padding: 0.75rem 1.15rem;
        border: 0;
        border-radius: 16px;
        background: linear-gradient(135deg, #123b76 0%, #1f4c8f 100%) !important;
        color: #ffffff !important;
        font-size: 0.8rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        box-shadow: 0 16px 28px rgba(15, 23, 42, 0.12);
    }

    @media (max-width: 767.98px) {
        .create-ticket-page {
            padding-top: 0.6rem !important;
            padding-left: 0.3rem !important;
            padding-right: 0.3rem !important;
            padding-bottom: 0.85rem !important;
        }

        .create-ticket-card {
            border-radius: 22px;
        }

        .create-ticket-card .card-header {
            padding: 1.05rem 1rem 0.35rem;
        }

        .create-ticket-title {
            font-size: 0.98rem;
        }

        .create-ticket-form-wrap {
            margin: 0 0.85rem 0.85rem;
            padding: 1rem;
            border-radius: 18px;
        }

        .create-ticket-form-wrap .form-control,
        .create-ticket-submit {
            min-height: 42px;
            border-radius: 14px;
            font-size: 0.78rem;
        }

        .create-ticket-form-wrap textarea.form-control {
            min-height: 120px;
        }
    }
</style>

<div class="container-fluid py-4 create-ticket-page">
    <div class="col-md-12 mb-lg-0 mb-4">
        <div class="card mt-4 create-ticket-card">
            <div class="card-header pb-0 p-3">
                <h6 class="mb-0 create-ticket-title">Apply for Leave</h6>
            </div>
            <div class="create-ticket-form-wrap">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-12 mt-4">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" name="subject" id="subject" class="form-control" required>
                        </div>
                        <div class="col-md-12 mt-4">
                            <label for="message" class="form-label">Message</label>
                            <textarea name="message" id="message" class="form-control" rows="4" required></textarea>
                        </div>
                        <div class="col-md-6 mt-4">
                            <button type="submit" class="btn btn-primary create-ticket-submit">Submit Ticket</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php
include("footer.php")?>