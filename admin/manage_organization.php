<?php
include '../db_connection.php';

// Fetch organization details (if any)
$sql = "SELECT * FROM organization LIMIT 1";
$result = $conn->query($sql);
$org = $result->fetch_assoc();

$message = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $address = $_POST['address'];
    $contact_email = $_POST['contact_email'];
    $phone = $_POST['phone'];
    $website = $_POST['website'];

    // Handle logo upload
    $logo_name = $org['logo'] ?? '';
    if (!empty($_FILES['logo']['name'])) {
        $logo_name = time() . '_' . basename($_FILES["logo"]["name"]);
        $target_dir = "../uploads/org/";
        $target_file = $target_dir . $logo_name;
        move_uploaded_file($_FILES["logo"]["tmp_name"], $target_file);
    }

    if ($org) {
        $sql = "UPDATE organization SET 
                    name='$name', 
                    address='$address', 
                    contact_email='$contact_email', 
                    phone='$phone', 
                    website='$website', 
                    logo='$logo_name' 
                WHERE id=" . $org['id'];
    } else {
        $sql = "INSERT INTO organization (name, address, contact_email, phone, website, logo)
                VALUES ('$name', '$address', '$contact_email', '$phone', '$website', '$logo_name')";
    }

    if ($conn->query($sql) === TRUE) {
        $message = "Organization details saved successfully.";
        header("Refresh: 1"); // Refresh after 1 second
    } else {
        $message = "Error: " . $conn->error;
    }
}
?>
<?php include("header.php"); ?>

<style>
.manage-organization-page {
    padding-bottom: 1.5rem;
}

.manage-organization-topbar,
.manage-organization-form-card {
    border: 1px solid rgba(87, 96, 108, 0.12);
    border-radius: 22px;
    box-shadow: 0 14px 34px rgba(31, 41, 55, 0.06);
    background: #fff;
}

.manage-organization-topbar {
    padding: 1.15rem 1.2rem;
    margin-bottom: 1rem;
}

.manage-organization-section-label {
    display: block;
    margin-bottom: 0.45rem;
    color: #6b7280;
    font-size: 0.74rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.manage-organization-title {
    margin: 0;
    color: #0f172a;
    font-size: 1.45rem;
    font-weight: 800;
    letter-spacing: -0.03em;
}

.manage-organization-copy {
    margin: 0.35rem 0 0;
    color: #6b7280;
    font-size: 0.92rem;
}

.manage-organization-alert {
    margin-bottom: 1rem;
    padding: 0.95rem 1.05rem;
    border-radius: 16px;
    border: 1px solid #dbe3ed;
    background: #f8fafc;
    color: #334155;
    font-size: 0.92rem;
    font-weight: 700;
}

.manage-organization-form-card {
    overflow: hidden;
}

.manage-organization-form-body {
    padding: 1.25rem;
}

.manage-organization-form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem 1.1rem;
}

.manage-organization-field {
    min-width: 0;
}

.manage-organization-field-full {
    grid-column: 1 / -1;
}

.manage-organization-field .form-label {
    display: block;
    margin-bottom: 0.45rem;
    color: #6b7280;
    font-size: 0.74rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.manage-organization-field .form-control {
    min-height: 46px;
    border-radius: 14px;
    border: 1px solid #d8dee7;
    box-shadow: none;
    background: #fff;
}

.manage-organization-field .form-control:focus {
    border-color: #1e3a5f;
    box-shadow: 0 0 0 0.18rem rgba(30, 58, 95, 0.12);
}

.manage-organization-field textarea.form-control {
    min-height: 110px;
}

.manage-organization-logo-wrap {
    display: flex;
    align-items: center;
    gap: 0.9rem;
    margin-top: 0.85rem;
    padding: 0.85rem 0.95rem;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    background: #f8fafc;
}

.manage-organization-logo-label {
    color: #475569;
    font-size: 0.82rem;
    font-weight: 700;
}

.manage-organization-logo-preview {
    max-height: 60px;
    max-width: 120px;
    border-radius: 12px;
    object-fit: contain;
    background: #fff;
    border: 1px solid #dbe3ed;
    padding: 0.3rem;
}

.manage-organization-actions {
    margin-top: 1.25rem;
}

.manage-organization-actions .btn {
    min-height: 40px;
    padding: 0.56rem 1rem;
    border-radius: 14px;
    font-size: 0.8rem;
    font-weight: 700;
}

.manage-organization-actions .btn-primary {
    background: linear-gradient(135deg, #161616 0%, #2d2d2d 100%) !important;
    color: #fff !important;
    border: none !important;
    box-shadow: 0 12px 24px rgba(17, 24, 39, 0.18);
}

.manage-organization-actions .btn-primary:hover {
    background: linear-gradient(135deg, #101010 0%, #242424 100%) !important;
    color: #fff !important;
}

@media (max-width: 991.98px) {
    .manage-organization-form-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="container-fluid py-4 manage-organization-page">
    <div class="row">
        <div class="col-12">
            <div class="manage-organization-topbar">
                <span class="manage-organization-section-label">Organization Settings</span>
                <h3 class="manage-organization-title">Manage Organization</h3>
                <p class="manage-organization-copy">Update organization profile details, contact information, and branding assets.</p>
            </div>
        </div>

        <?php if (!empty($message)) : ?>
            <div class="col-12">
                <div class="manage-organization-alert"><?= $message ?></div>
            </div>
        <?php endif; ?>

        <div class="col-12">
            <div class="manage-organization-form-card">
                <div class="manage-organization-form-body">
                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="manage-organization-form-grid">
                            <div class="manage-organization-field">
                                <label for="name" class="form-label">Organization Name</label>
                                <input type="text" name="name" id="name" class="form-control" required value="<?= htmlspecialchars($org['name'] ?? '') ?>">
                            </div>

                            <div class="manage-organization-field">
                                <label for="contact_email" class="form-label">Contact Email</label>
                                <input type="email" name="contact_email" id="contact_email" class="form-control" value="<?= htmlspecialchars($org['contact_email'] ?? '') ?>">
                            </div>

                            <div class="manage-organization-field">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="text" name="phone" id="phone" class="form-control" value="<?= htmlspecialchars($org['phone'] ?? '') ?>">
                            </div>

                            <div class="manage-organization-field">
                                <label for="website" class="form-label">Website</label>
                                <input type="text" name="website" id="website" class="form-control" value="<?= htmlspecialchars($org['website'] ?? '') ?>">
                            </div>

                            <div class="manage-organization-field manage-organization-field-full">
                                <label for="address" class="form-label">Address</label>
                                <textarea name="address" id="address" class="form-control" rows="2"><?= htmlspecialchars($org['address'] ?? '') ?></textarea>
                            </div>

                            <div class="manage-organization-field">
                                <label for="logo" class="form-label">Organization Logo</label>
                                <input type="file" name="logo" id="logo" class="form-control" accept=".jpg,.jpeg,.png,.svg">
                                <?php if (!empty($org['logo'])) : ?>
                                    <div class="manage-organization-logo-wrap">
                                        <div class="manage-organization-logo-label">Current Logo</div>
                                        <img src="../uploads/org/<?= $org['logo'] ?>" alt="Logo" class="manage-organization-logo-preview">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="manage-organization-actions">
                            <button type="submit" class="btn btn-primary">Save Organization</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include("footer.php"); ?>
