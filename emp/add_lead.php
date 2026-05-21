<?php
session_start();
require 'db_connection.php';

/* =========================
   AUTH CHECK
========================= */
if (!isset($_SESSION['employee_id'])) {
    exit("Login required");
}

$employee_id = $_SESSION['employee_id'];

/* =========================
   HANDLE FORM SUBMIT
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $lead_name      = trim($_POST['lead_name']);
    $company_name   = trim($_POST['company_name']);
    $phone          = trim($_POST['phone']);
    $email          = trim($_POST['email']);
    $lead_source    = $_POST['lead_source'];
    $expected_value = is_numeric($_POST['expected_value']) ? $_POST['expected_value'] : 0;
    $notes          = trim($_POST['notes']);

    if ($lead_name === '' || $phone === '') {
        header("Location: add_lead?error=required");
        exit;
    }

    /* DUPLICATE CHECK */
    $check = $conn->prepare("SELECT id FROM leads WHERE phone = ? LIMIT 1");
    $check->bind_param("s", $phone);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $check->close();
        header("Location: add_lead?duplicate=1");
        exit;
    }
    $check->close();

    /* INSERT LEAD */

    $stmt = $conn->prepare("
        INSERT INTO leads
        (lead_name, company_name, phone, email, lead_source, expected_value, notes, assigned_to, created_by)
        VALUES (?,?,?,?,?,?,?,?,?)
    ");

    $stmt->bind_param(
        "sssssdssi",
        $lead_name,
        $company_name,
        $phone,
        $email,
        $lead_source,
        $expected_value,
        $notes,
        $employee_id,
        $employee_id
    );

    $stmt->execute();
    $lead_id = $conn->insert_id;
    $stmt->close();

    /* ACTIVITY LOG */
    $type = "Lead Created";
    $text = "Lead created by employee";

    $log = $conn->prepare("
        INSERT INTO lead_activities
        (lead_id, activity_type, activity_text, created_by)
        VALUES (?,?,?,?)
    ");
    $log->bind_param(
        "issi",
        $lead_id,
        $type,
        $text,
        $employee_id
    );
    $log->execute();
    $log->close();

    header("Location: my_leads?added=1");
    exit;
}

/* =========================
   UI STARTS
========================= */
include("header.php");
?>

<div class="container-fluid py-4">

<!-- PAGE HEADER -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0"> Add New Lead</h5>
    
</div>

<!-- ALERTS -->
<?php if (isset($_GET['error']) && $_GET['error'] === 'required'): ?>
<div class="alert alert-danger alert-dismissible fade show" id="alertBox">
    ⚠️ Lead name and phone are required.
    <button class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (isset($_GET['duplicate'])): ?>
<div class="alert alert-warning alert-dismissible fade show" id="alertBox">
    ⚠️ A lead with this phone number already exists.
    <button class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- FORM CARD -->
<div class="card shadow-sm">
<div class="card-body">

<h6 class="mb-3 text-muted">Lead Information</h6>

<form method="POST">

<div class="row">

    <div class="col-md-4 mb-3">
        <label class="form-label">Lead Name <span class="text-danger">*</span></label>
        <input name="lead_name" class="form-control" required>
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">Company Name</label>
        <input name="company_name" class="form-control">
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">Phone <span class="text-danger">*</span></label>
        <input name="phone" class="form-control" maxlength="10" required>
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">Email</label>
        <input name="email" type="email" class="form-control">
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">Lead Source</label>
        <select name="lead_source" class="form-control">
            <option>Website</option>
            <option>Phone Call</option>
            <option>Referral</option>
            <option>Email</option>
            <option>Walk-in</option>
            <option>Social Media</option>
            <option>Other</option>
        </select>
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label">Expected Value</label>
        <input name="expected_value" type="number" step="0.01" class="form-control">
    </div>

    <div class="col-md-12 mb-3">
        <label class="form-label">Notes</label>
        <textarea name="notes" class="form-control" rows="3"></textarea>
    </div>

</div>

<div class="d-flex justify-content-end gap-2">
    <a href="my_leads" class="btn btn-light">Cancel</a>
    <button class="btn btn-primary">
        <i class="bi bi-check-circle me-1"></i> Save Lead
    </button>
</div>

</form>

</div>
</div>

</div>

<script>
document.addEventListener("DOMContentLoaded", function () {

    const alertBox = document.getElementById("alertBox");
    if (!alertBox) return;

    setTimeout(() => {
        alertBox.classList.remove("show");
        setTimeout(() => alertBox.remove(), 300);
    }, 4000);

    if (window.history.replaceState) {
        const url = new URL(window.location);
        ['error','duplicate'].forEach(p => url.searchParams.delete(p));
        window.history.replaceState({}, document.title, url.pathname);
    }
});
</script>

<?php include("footer.php"); ?>
