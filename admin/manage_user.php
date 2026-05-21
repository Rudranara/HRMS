<?php
include("header.php"); // Include header file
// Fetch all admins
$stmt = $conn->prepare("SELECT * FROM admins");
$stmt->execute();
$admins_result = $stmt->get_result();

// Add Admin
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_admin'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $role = $_POST['role'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $insert_stmt = $conn->prepare("INSERT INTO admins (name, email, phone, role, password_hash) VALUES (?, ?, ?, ?, ?)");
    $insert_stmt->bind_param("sssss", $name, $email, $phone, $role, $password);
    if ($insert_stmt->execute()) {
        $_SESSION['message'] = "Admin added successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Failed to add admin.";
        $_SESSION['message_type'] = "danger";
    }
    // No redirect, message is set in session
}

// Update Admin
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_admin'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $role = $_POST['role'];

    $update_stmt = $conn->prepare("UPDATE admins SET name = ?, email = ?, phone = ?, role = ? WHERE id = ?");
    $update_stmt->bind_param("ssssi", $name, $email, $phone, $role, $id);
    if ($update_stmt->execute()) {
        $_SESSION['message'] = "Admin updated successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Failed to update admin.";
        $_SESSION['message_type'] = "danger";
    }
    // No redirect, message is set in session
}

// Delete Admin
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_admin'])) {
    $id = $_POST['id'];
    $delete_stmt = $conn->prepare("DELETE FROM admins WHERE id = ?");
    $delete_stmt->bind_param("i", $id);
    if ($delete_stmt->execute()) {
        $_SESSION['message'] = "Admin deleted successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Failed to delete admin.";
        $_SESSION['message_type'] = "danger";
    }
    // No redirect, message is set in session
}
?>

<style>
.manage-user-page {
    padding-bottom: 1.5rem;
}

.manage-user-topbar,
.manage-user-search-card,
.manage-user-table-card {
    border: 1px solid rgba(87, 96, 108, 0.12);
    border-radius: 22px;
    box-shadow: 0 14px 34px rgba(31, 41, 55, 0.06);
    background: #fff;
}

.manage-user-topbar {
    padding: 1.15rem 1.2rem;
    margin-bottom: 1rem;
}

.manage-user-topbar-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 1rem;
    align-items: center;
}

.manage-user-section-label {
    display: block;
    margin-bottom: 0.45rem;
    color: #6b7280;
    font-size: 0.74rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.manage-user-title {
    margin: 0;
    color: #0f172a;
    font-size: 1.45rem;
    font-weight: 800;
    letter-spacing: -0.03em;
}

.manage-user-copy {
    margin: 0.35rem 0 0;
    color: #6b7280;
    font-size: 0.92rem;
}

.manage-user-toolbar {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.8rem;
    flex-wrap: wrap;
}

.manage-user-toolbar .btn {
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

.manage-user-btn-dark,
.manage-user-search-btn,
.manage-user-modal .btn-primary {
    background: linear-gradient(135deg, #161616 0%, #2d2d2d 100%) !important;
    color: #fff !important;
    border: none !important;
    box-shadow: 0 12px 24px rgba(17, 24, 39, 0.18);
}

.manage-user-btn-dark:hover,
.manage-user-search-btn:hover,
.manage-user-modal .btn-primary:hover {
    background: linear-gradient(135deg, #101010 0%, #242424 100%) !important;
    color: #fff !important;
}

.manage-user-alert {
    margin-bottom: 1rem;
    padding: 1rem 1.05rem;
    border-radius: 18px;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
}

.manage-user-search-card {
    padding: 1rem 1.1rem;
    margin-bottom: 1rem;
}

.manage-user-search-row {
    display: flex;
    gap: 0.85rem;
    align-items: center;
}

.manage-user-search-card .form-control,
.manage-user-modal .form-control {
    min-height: 46px;
    border-radius: 14px;
    border: 1px solid #d8dee7;
    box-shadow: none;
}

.manage-user-search-card .form-control:focus,
.manage-user-modal .form-control:focus {
    border-color: #1e3a5f;
    box-shadow: 0 0 0 0.18rem rgba(30, 58, 95, 0.12);
}

.manage-user-table-card {
    overflow: hidden;
}

.manage-user-table-card .card-body {
    padding: 0 0 1rem;
}

.manage-user-table-wrap {
    padding: 0 1.2rem 1.15rem;
}

.manage-user-table {
    margin-bottom: 0;
}

.manage-user-table thead th {
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

.manage-user-table tbody td {
    padding: 1rem 0.95rem;
    border-bottom: 1px solid #eef2f7;
    color: #1f2937;
    vertical-align: middle;
    font-size: 0.92rem;
}

.manage-user-table tbody tr:last-child td {
    border-bottom: none;
}

.manage-user-table tbody tr:hover {
    background: #fbfcfe;
}

.manage-user-id-badge {
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

.manage-user-name,
.manage-user-email {
    color: #0f172a;
    font-weight: 700;
}

.manage-user-phone {
    color: #475569;
}

.manage-user-role {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 96px;
    padding: 0.42rem 0.7rem;
    border-radius: 999px;
    background: #f8fafc;
    border: 1px solid #dbe3ed;
    color: #475569;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.manage-user-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: nowrap;
    white-space: nowrap;
}

.manage-user-action-btn {
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

.manage-user-action-btn.btn-warning {
    background: linear-gradient(135deg, #161616 0%, #2d2d2d 100%) !important;
    color: #fff !important;
    border: none !important;
    box-shadow: 0 12px 24px rgba(17, 24, 39, 0.18);
}

.manage-user-action-btn.btn-danger {
    background: #fbe6e5 !important;
    color: #c24141 !important;
    border: 1px solid #f4c9c7 !important;
    box-shadow: none !important;
}

.manage-user-action-btn.btn-danger:hover {
    background: #f7d8d6 !important;
    color: #a93232 !important;
}

.manage-user-modal .modal-dialog {
    max-width: 620px;
}

.manage-user-modal .modal-content {
    border: 1px solid rgba(87, 96, 108, 0.12);
    border-radius: 18px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
    overflow: hidden;
}

.manage-user-modal .modal-header,
.manage-user-modal .modal-footer {
    border-color: #eef2f7;
    padding: 1rem 1.25rem;
}

.manage-user-modal .modal-body {
    padding: 1.25rem;
}

.manage-user-modal .modal-title {
    color: #0f172a;
    font-size: 1rem;
    font-weight: 700;
}

.manage-user-modal .form-label {
    display: block;
    margin-bottom: 0.45rem;
    color: #6b7280;
    font-size: 0.74rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.manage-user-modal .btn-secondary {
    background: #f3f4f6;
    color: #334155;
    border: none;
}

@media (max-width: 991.98px) {
    .manage-user-topbar-grid,
    .manage-user-search-row,
    .manage-user-actions {
        grid-template-columns: 1fr;
        flex-direction: column;
        align-items: stretch;
    }

    .manage-user-toolbar {
        justify-content: stretch;
    }

    .manage-user-toolbar .btn,
    .manage-user-search-row .btn,
    .manage-user-actions > * {
        width: 100%;
    }
}
</style>

<div class="container-fluid py-4 manage-user-page">
      <div class="row">
      <div class="col-12">
            <div class="manage-user-topbar">
                <div class="manage-user-topbar-grid">
                    <div>
                        <span class="manage-user-section-label">Admin Directory</span>
                        <h6 class="manage-user-title">Manage User/Admin</h6>
                        <p class="manage-user-copy">Create, update, and maintain administrator accounts from one place.</p>
                    </div>
                    <div class="manage-user-toolbar">
                        <button class="btn manage-user-btn-dark mb-0" data-bs-toggle="modal" data-bs-target="#addAdminModal">Add New Admin</button>
                    </div>
                </div>
            </div>
      </div>
      <?php
if (isset($_SESSION['message'])): ?>
    <div class="col-12">
    <div class="alert alert-<?= $_SESSION['message_type']; ?> alert-dismissible fade show manage-user-alert" role="alert">
        <?= $_SESSION['message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    </div>
    <?php 
    unset($_SESSION['message']); // Clear the message after display
    unset($_SESSION['message_type']); // Clear the message type
endif;
?>
        <div class="col-12">
          <div class="manage-user-search-card">
              <form method="GET" class="mb-0">
                    <div class="manage-user-search-row">
                            <input type="text" name="search" class="form-control" placeholder="Search by Name, Role or ID" value="">
                            <button type="submit" class="btn manage-user-search-btn mb-0">Search</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-12">
          <div class="card manage-user-table-card mb-4">           
            <div class="card-body px-0 pt-0 pb-2">
              <div class="table-responsive manage-user-table-wrap">
                <table class="table manage-user-table align-items-center mb-0">
                    <thead>
                        <tr>
                            <th>SL .NO</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $serial_no = 1; ?>
                        <?php while ($row = $admins_result->fetch_assoc()): ?>
                            <tr>
                                <td><span class="manage-user-id-badge"><?= $serial_no++ ?></span></td>
                                <td><span class="manage-user-name"><?= htmlspecialchars($row['name']) ?></span></td>
                                <td><span class="manage-user-email"><?= htmlspecialchars($row['email']) ?></span></td>
                                <td><span class="manage-user-phone"><?= htmlspecialchars($row['phone']) ?></span></td>
                                <td><span class="manage-user-role"><?= htmlspecialchars($row['role']) ?></span></td>
                                <td>
                                    <div class="manage-user-actions">
                                    <!-- Edit Button -->
                                    <button class="btn btn-warning btn-sm manage-user-action-btn" data-bs-toggle="modal" data-bs-target="#editAdminModal" data-id="<?= htmlspecialchars($row['id']) ?>" data-name="<?= htmlspecialchars($row['name']) ?>" data-email="<?= htmlspecialchars($row['email']) ?>" data-phone="<?= htmlspecialchars($row['phone']) ?>" data-role="<?= htmlspecialchars($row['role']) ?>">Edit</button>

                                    <!-- Delete Button -->
                                    <button class="btn btn-danger btn-sm manage-user-action-btn" data-bs-toggle="modal" data-bs-target="#deleteAdminModal" data-id="<?= htmlspecialchars($row['id']) ?>">Delete</button>
                                    </div>
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
</div>

<!-- Add Admin Modal -->
<div class="modal fade manage-user-modal" id="addAdminModal" tabindex="-1" aria-labelledby="addAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addAdminModalLabel">Add New Admin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="manage_user">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input class="form-control" type="text" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input class="form-control" type="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input class="form-control" type="text" name="phone" required>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <input class="form-control" type="text" name="role" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input class="form-control" type="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary" name="add_admin">Add Admin</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Admin Modal -->
<div class="modal fade manage-user-modal" id="editAdminModal" tabindex="-1" aria-labelledby="editAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editAdminModalLabel">Edit Admin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="manage_user">
                    <input type="hidden" name="id" id="editAdminId">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input class="form-control" type="text" name="name" id="editAdminName" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input class="form-control" type="email" name="email" id="editAdminEmail" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input class="form-control" type="text" name="phone" id="editAdminPhone" required>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <input class="form-control" type="text" name="role" id="editAdminRole" required>
                    </div>
                    <button type="submit" class="btn btn-primary" name="update_admin">Update Admin</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Admin Modal -->
<div class="modal fade manage-user-modal" id="deleteAdminModal" tabindex="-1" aria-labelledby="deleteAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteAdminModalLabel">Delete Admin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="manage_user">
                    <input type="hidden" name="id" id="deleteAdminId">
                    <p>Are you sure you want to delete this admin?</p>
                    <button type="submit" class="btn btn-danger" name="delete_admin">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    // JavaScript to populate Edit Modal with selected admin's data
    const editButtons = document.querySelectorAll('[data-bs-toggle="modal"][data-bs-target="#editAdminModal"]');
    editButtons.forEach(button => {
        button.addEventListener('click', () => {
            document.getElementById('editAdminId').value = button.getAttribute('data-id');
            document.getElementById('editAdminName').value = button.getAttribute('data-name');
            document.getElementById('editAdminEmail').value = button.getAttribute('data-email');
            document.getElementById('editAdminPhone').value = button.getAttribute('data-phone');
            document.getElementById('editAdminRole').value = button.getAttribute('data-role');
        });
    });

    // JavaScript to populate Delete Modal with selected admin's ID
    const deleteButtons = document.querySelectorAll('[data-bs-toggle="modal"][data-bs-target="#deleteAdminModal"]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', () => {
            document.getElementById('deleteAdminId').value = button.getAttribute('data-id');
        });
    });
</script>

<?php include("footer.php"); ?>
