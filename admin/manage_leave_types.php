<?php
include("header.php");
require 'db_connection.php';

$success = "";

// Handle toggle status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_id'])) {
    $toggle_id = (int)$_POST['toggle_id'];
    $stmt = $conn->prepare("UPDATE leave_types SET is_enabled = NOT is_enabled WHERE id = ?");
    $stmt->bind_param("i", $toggle_id);
    $stmt->execute();
    $stmt->close();
    $success = "Leave type status updated successfully!";
}

// Handle adding new leave type
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_leave_type'])) {
    $new_type = strtolower(trim($_POST['new_leave_type']));
    if ($new_type !== "") {
        $stmt = $conn->prepare("INSERT INTO leave_types (type_name, is_enabled) VALUES (?, 1)");
        $stmt->bind_param("s", $new_type);
        if ($stmt->execute()) {
            $success = "New leave type added successfully!";
        } else {
            $success = "Failed to add new leave type.";
        }
        $stmt->close();
    }
}

// Handle deletion
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM leave_types WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();
    $success = "Leave type deleted successfully!";
}

// Fetch all leave types
$leave_types = $conn->query("SELECT * FROM leave_types ORDER BY id ASC");
?>

<style>
.leave-types-page {
    padding-top: 2rem;
    padding-bottom: 2.5rem;
}

.leave-types-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.4rem;
}

.leave-types-title {
    margin: 0;
    color: #0f172a;
    font-size: 1.45rem;
    font-weight: 800;
    letter-spacing: -0.03em;
}

.leave-types-alert {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.85rem;
    margin-bottom: 1rem;
    padding: 1rem 1.1rem;
    border: 1px solid #d7deea;
    border-radius: 20px;
    background: #ffffff;
    color: #21543a;
    box-shadow: 0 20px 40px rgba(15, 23, 42, 0.06);
}

.leave-types-alert-text {
    font-size: 0.92rem;
    font-weight: 700;
}

.leave-types-card {
    border: 1px solid #e5eaf1;
    border-radius: 26px;
    background: #ffffff;
    box-shadow: 0 28px 60px rgba(15, 23, 42, 0.07);
    overflow: hidden;
}

.leave-types-table-wrap {
    padding: 0 1.5rem 1.5rem;
    overflow-x: auto;
}

.leave-types-table {
    width: 100%;
    margin-bottom: 0;
}

.leave-types-table thead th {
    border-bottom: 1px solid #e8edf3;
    background: #f8fafc;
    color: #64748b;
    font-size: 0.73rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    padding: 1rem 0.95rem;
    white-space: nowrap;
}

.leave-types-table tbody td {
    padding: 1rem 0.95rem;
    border-bottom: 1px solid #eef2f7;
    color: #1f2937;
    font-size: 0.92rem;
    vertical-align: middle;
}

.leave-types-table tbody tr:last-child td {
    border-bottom: none;
}

.leave-types-table tbody tr:hover {
    background: #f8fafc;
}

.leave-types-id-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    min-height: 40px;
    padding: 0.35rem 0.65rem;
    border: 1px solid #d7deea;
    border-radius: 14px;
    background: #eff3f8;
    color: #334155;
    font-size: 0.84rem;
    font-weight: 800;
}

.leave-types-name {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.38rem 0.72rem;
    border-radius: 999px;
    background: #eff3f8;
    color: #334155;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.leave-types-status-form {
    display: inline-block;
    margin: 0;
}

.leave-types-status-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 40px;
    min-width: 112px;
    padding: 0.68rem 0.95rem;
    border-radius: 14px;
    border: none;
    font-size: 0.74rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    text-decoration: none;
}

.leave-types-status-btn.btn-success {
    background: linear-gradient(135deg, #dff5e6 0%, #c8ebd5 100%);
    border: 1px solid #b9dec8;
    color: #21543a;
}

.leave-types-status-btn.btn-secondary {
    background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%);
    border: 1px solid #d1d5db;
    color: #475569;
}

@media (max-width: 767.98px) {
    .leave-types-page {
        padding-top: 1.25rem;
    }

    .leave-types-header,
    .leave-types-alert {
        flex-direction: column;
        align-items: flex-start;
    }

    .leave-types-table-wrap {
        padding-left: 1rem;
        padding-right: 1rem;
    }
}
</style>

<div class="container-fluid leave-types-page">
    <div class="row">
        <div class="col-12">
            <div class="leave-types-header">
                <h6 class="leave-types-title">Manage Leave Types</h6>
            </div>
        </div>

        <?php if (!empty($success)): ?>
        <div class="col-12">
            <div class="alert alert-dismissible fade show leave-types-alert" role="alert">
                <span class="leave-types-alert-text"><?= htmlspecialchars($success) ?></span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
        <?php endif; ?>

        <div class="col-12">
            <div class="leave-types-card mb-4">
                <div class="leave-types-table-wrap">
                    <table class="table align-items-center leave-types-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Leave Type</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $leave_types->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <span class="leave-types-id-badge"><?= $row['id'] ?></span>
                                </td>
                                <td>
                                    <span class="leave-types-name"><?= ucwords(str_replace('_', ' ', htmlspecialchars($row['type_name']))) ?></span>
                                </td>
                                <td>
                                    <form method="POST" class="leave-types-status-form">
                                        <input type="hidden" name="toggle_id" value="<?= $row['id'] ?>">
                                        <button type="submit" class="btn btn-sm leave-types-status-btn <?= $row['is_enabled'] ? 'btn-success' : 'btn-secondary' ?>">
                                            <?= $row['is_enabled'] ? 'Enabled' : 'Disabled' ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("footer.php"); ?>
