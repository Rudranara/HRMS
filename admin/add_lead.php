<?php
include("header.php");

if (!isset($_SESSION['admin_id'])) {
    exit("Admin login required");
}

$error = "";

/* ===============================
   HANDLE SINGLE LEAD ADD
=============================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lead_name'])) {

    $lead_name      = trim($_POST['lead_name']);
    $company_name   = trim($_POST['company_name']);
    $phone          = trim($_POST['phone']);
    $email          = trim($_POST['email']);
    $expected_value = !empty($_POST['expected_value']) ? floatval($_POST['expected_value']) : 0;
    $notes          = trim($_POST['notes']);

    $assigned_to = !empty($_POST['assigned_to']) ? intval($_POST['assigned_to']) : 0;
    $created_by  = $_SESSION['admin_id'];

    if ($lead_name === '' || $phone === '') {
        $error = "Lead name and phone are required.";
    }
    elseif ($assigned_to <= 0) {
        $error = "Please assign the lead to an employee.";
    }
    else {

        /* ===============================
           DUPLICATE CHECK
        =============================== */
        $dup = $conn->prepare("
            SELECT id
            FROM leads
            WHERE phone = ?
               OR (email <> '' AND email = ?)
            LIMIT 1
        ");
        $dup->bind_param("ss", $phone, $email);
        $dup->execute();
        $dup->store_result();

        if ($dup->num_rows > 0) {
            $error = "⚠️ A lead with this phone or email already exists.";
            $dup->close();
        } else {
            $dup->close();

            /* ===============================
               INSERT LEAD
            =============================== */
            $stmt = $conn->prepare("
                INSERT INTO leads
                (
                    lead_name,
                    company_name,
                    phone,
                    email,
                    assigned_to,
                    created_by,
                    expected_value,
                    notes
                )
                VALUES (?,?,?,?,?,?,?,?)
            ");

            $stmt->bind_param(
                "ssssiids",
                $lead_name,
                $company_name,
                $phone,
                $email,
                $assigned_to,
                $created_by,
                $expected_value,
                $notes
            );

            $stmt->execute();
            $lead_id = $conn->insert_id;
            $stmt->close();

            /* ===============================
               ACTIVITY LOG
            =============================== */

            $type = "Lead Created";
            $text = "Lead created and assigned by admin";


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
                $created_by
            );
            $log->execute();
            $log->close();

            header("Location: add_lead?success=1");
            exit;
        }
    }
}
?>

<div class="container-fluid py-4">

<!-- ================= HEADER ================= -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"> Add Lead</h4>
        <small class="text-muted">Create and assign a new lead</small>
    </div>

    <button class="btn btn-dark"
            data-bs-toggle="modal"
            data-bs-target="#bulkLeadModal">
        📥 Bulk Upload
    </button>
</div>

<!-- ================= ALERTS ================= -->
<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success alert-dismissible fade show">
    ✅ Lead added successfully
    <button class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (isset($_GET['bulk_success'])): ?>
<div class="alert alert-success alert-dismissible fade show">
    <strong>✅ Bulk Upload Successful</strong><br>
    Inserted: <b><?= (int)($_GET['inserted'] ?? 0) ?></b> |
    Skipped: <b><?= (int)($_GET['skipped'] ?? 0) ?></b>
    <button class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show" id="errorAlert">
    <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>


<!-- ================= SINGLE LEAD FORM ================= -->
<div class="card shadow-sm border-0">
<div class="card-body">

<h6 class="mb-3 fw-bold">Lead Information</h6>

<form method="POST">
<div class="row">

<div class="col-md-4 mb-3">
    <label class="form-label">Lead Name *</label>
    <input type="text" name="lead_name" class="form-control" required>
</div>

<div class="col-md-4 mb-3">
    <label class="form-label">Company</label>
    <input type="text" name="company_name" class="form-control">
</div>

<div class="col-md-4 mb-3">
    <label class="form-label">Phone *</label>
    <input type="text" name="phone" class="form-control" maxlength="10" required>
</div>

<div class="col-md-4 mb-3">
    <label class="form-label">Email</label>
    <input type="email" name="email" class="form-control">
</div>

<div class="col-md-4 mb-3">
    <label class="form-label">Assign To *</label>
    <select name="assigned_to" class="form-control" required>
        <option value="">Select Employee</option>
        <?php
        $emps = $conn->query("SELECT id, name FROM employees ORDER BY name");
        while ($e = $emps->fetch_assoc()):
        ?>
        <option value="<?= $e['id'] ?>">
            <?= htmlspecialchars($e['name']) ?>
        </option>
        <?php endwhile; ?>
    </select>
</div>

<div class="col-md-4 mb-3">
    <label class="form-label">Expected Value (₹)</label>
    <input type="number" step="0.01" name="expected_value" class="form-control">
</div>

<div class="col-md-12 mb-3">
    <label class="form-label">Notes</label>
    <textarea name="notes" class="form-control" rows="3"></textarea>
</div>

</div>

<div class="text-end">
    <button class="btn btn-primary px-4">
        Save Lead
    </button>
</div>

</form>
</div>
</div>

</div>

<!-- ================= BULK UPLOAD MODAL ================= -->
<div class="modal fade" id="bulkLeadModal" tabindex="-1">
<div class="modal-dialog modal-md modal-dialog-centered">
<div class="modal-content">

<div class="modal-header">
    <h5 class="modal-title">📥 Bulk Upload Leads</h5>
    <button class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">
    <p class="text-muted">
        Download CSV → Fill data → Upload file
    </p>

    <form method="GET" action="lead_csv" class="mb-3">
        <button type="submit" name="download_csv" class="btn btn-outline-success w-100">
            ⬇ Download CSV Format
        </button>
    </form>

    <form method="POST" action="lead_csv" enctype="multipart/form-data">
        <input type="file" name="csv_file" class="form-control mb-3" accept=".csv" required>
        <button type="submit" name="upload_csv" class="btn btn-dark w-100">
            🚀 Upload Leads
        </button>
    </form>
</div>

</div>
</div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {

    const alertBox = document.querySelector(".alert-success");
    if (!alertBox) return;

    setTimeout(() => {
        alertBox.remove();
    }, 3500);

    if (window.history.replaceState) {
        const url = new URL(window.location);
        ['success','bulk_success','inserted','skipped'].forEach(p => url.searchParams.delete(p));
        window.history.replaceState({}, document.title, url.pathname);
    }
});
</script>


<script>
document.addEventListener("DOMContentLoaded", function () {

    const alertBox = document.getElementById("errorAlert");
    if (!alertBox) return;

    // Hide after 4 seconds
    setTimeout(() => {
        alertBox.classList.remove("show");

        // Remove completely to avoid top gap
        setTimeout(() => {
            alertBox.remove();
        }, 300);

    }, 4000);
});
</script>


<?php include("footer.php"); ?>
