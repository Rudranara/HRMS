<?php
include("header.php"); // Include header file
require 'db_connection.php'; // Include database connection file
// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo "<div class='alert alert-danger'>You must be logged in to edit your profile.</div>";
    exit;
}
$admin_id = $_SESSION['admin_id']; // Get admin ID from session

// Fetch admin details
$stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    echo "<div class='alert alert-danger'>Admin not found!</div>";
    exit;
}
$admin = $result->fetch_assoc(); // Fetch admin data
// Handle form submission for updating admin details
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $role = trim($_POST['role']);
    $password = trim($_POST['password']);
    $password_hash = $admin['password_hash']; // Default to current password hash

    // Validate inputs
    if (empty($name) || empty($email) || empty($phone) || empty($role)) {
        echo "<div class='alert alert-danger'>All fields are required!</div>";
    } else {
        // Handle password change (if provided)
        if (!empty($password)) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT); // Hash new password
        }

        // Update admin details in the database
        $stmt = $conn->prepare("UPDATE admins SET name = ?, email = ?, phone = ?, role = ?, password_hash = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $name, $email, $phone, $role, $password_hash, $admin_id);

        if ($stmt->execute()) {
            echo "<div class='alert alert-success'>Profile updated successfully.</div>";
        } else {
            echo "<div class='alert alert-danger'>Failed to update profile.</div>";
        }
    }
}
?>
<style>
.edit-profile-page {
    padding-bottom: 1.5rem;
}

.edit-profile-topbar,
.edit-profile-form-card,
.edit-profile-security-card {
    border: 1px solid rgba(87, 96, 108, 0.12);
    border-radius: 22px;
    box-shadow: 0 14px 34px rgba(31, 41, 55, 0.06);
    background: #fff;
}

.edit-profile-topbar {
    padding: 1.15rem 1.2rem;
    margin-bottom: 1rem;
}

.edit-profile-topbar-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 1rem;
    align-items: center;
}

.edit-profile-section-label {
    display: block;
    margin-bottom: 0.45rem;
    color: #6b7280;
    font-size: 0.74rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.edit-profile-title {
    margin: 0;
    color: #0f172a;
    font-size: 1.45rem;
    font-weight: 800;
    letter-spacing: -0.03em;
}

.edit-profile-copy {
    margin: 0.35rem 0 0;
    color: #6b7280;
    font-size: 0.92rem;
    max-width: 620px;
}

.edit-profile-status-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 40px;
    padding: 0.55rem 1rem;
    border-radius: 14px;
    background: #f8fafc;
    border: 1px solid #dbe3ed;
    color: #475569;
    font-size: 0.8rem;
    font-weight: 700;
}

.edit-profile-form-card {
    overflow: hidden;
    margin-bottom: 1rem;
}

.edit-profile-form-card .card-body {
    padding: 1.25rem;
}

.edit-profile-form-card .form-label {
    display: block;
    margin-bottom: 0.45rem;
    color: #6b7280;
    font-size: 0.74rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.edit-profile-form-card .form-control {
    min-height: 46px;
    border-radius: 14px;
    border: 1px solid #d8dee7;
    box-shadow: none;
    padding: 0.7rem 0.95rem;
}

.edit-profile-form-card .form-control:focus {
    border-color: #1e3a5f;
    box-shadow: 0 0 0 0.18rem rgba(30, 58, 95, 0.12);
}

.edit-profile-security-card {
    padding: 1rem 1.05rem;
    background: linear-gradient(180deg, #ffffff 0%, #fbfcfe 100%);
}

.edit-profile-actions {
    display: flex;
    justify-content: flex-start;
    align-items: center;
}

.edit-profile-btn {
    min-height: 42px;
    padding: 0.56rem 1rem;
    border-radius: 14px;
    font-size: 0.8rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    background: linear-gradient(135deg, #161616 0%, #2d2d2d 100%) !important;
    color: #fff !important;
    border: none !important;
    box-shadow: 0 12px 24px rgba(17, 24, 39, 0.18);
}

.edit-profile-btn:hover {
    background: linear-gradient(135deg, #101010 0%, #242424 100%) !important;
    color: #fff !important;
}

@media (max-width: 991.98px) {
    .edit-profile-topbar-grid {
        grid-template-columns: 1fr;
    }

    .edit-profile-actions {
        justify-content: stretch;
    }

    .edit-profile-actions .edit-profile-btn {
        width: 100%;
    }
}
</style>

<div class="container-fluid py-4 edit-profile-page">
    <div class="row">
        <div class="col-12">
            <div class="edit-profile-topbar">
                <div class="edit-profile-topbar-grid">
                    <div>
                        <span class="edit-profile-section-label">Admin Profile</span>
                        <h6 class="edit-profile-title">Edit Your Profile</h6>
                        <p class="edit-profile-copy">Review and update your administrator details using the same account settings structure used across the admin area.</p>
                    </div>
                    <div>
                        <span class="edit-profile-status-chip"><?= htmlspecialchars($admin['role']) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card edit-profile-form-card mb-4">
                <div class="card-body">
                    <form method="POST">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Name</label>
                                <input class="form-control" type="text" name="name" id="name" value="<?= htmlspecialchars($admin['name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input class="form-control" type="email" name="email" id="email" value="<?= htmlspecialchars($admin['email']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone</label>
                                <input class="form-control" type="text" name="phone" id="phone" value="<?= htmlspecialchars($admin['phone']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="role" class="form-label">Role</label>
                                <input class="form-control" type="text" name="role" id="role" value="<?= htmlspecialchars($admin['role']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <div class="edit-profile-security-card">
                                    <div class="row g-4 align-items-end">
                                        <div class="col-12">
                                            <label for="password" class="form-label">New Password</label>
                                            <input class="form-control" type="password" name="password" id="password" placeholder="Leave blank if unchanged">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="edit-profile-actions">
                                    <button class="btn edit-profile-btn mb-0" type="submit">Update Profile</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include("footer.php"); ?>
