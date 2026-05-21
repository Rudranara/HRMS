<?php
include("header.php");

if (!isset($_SESSION['employee_id'])) exit("Login required");

$employee_id = $_SESSION['employee_id'];
$lead_id = intval($_GET['id']);

/* =======================
   FETCH LEAD
======================= */
$stmt = $conn->prepare("
    SELECT *
    FROM leads
    WHERE id = ? AND assigned_to = ?
");
$stmt->bind_param("ii", $lead_id, $employee_id);
$stmt->execute();
$lead = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$lead) exit("Access denied");

/* =======================
   FETCH ACTIVITY TIMELINE
======================= */
$timeline = $conn->prepare("
    SELECT a.activity_type, a.activity_text, a.created_at, e.name
    FROM lead_activities a
    LEFT JOIN employees e ON a.created_by = e.id
    WHERE a.lead_id = ?
    ORDER BY a.created_at DESC
");
$timeline->bind_param("i", $lead_id);
$timeline->execute();
$activities = $timeline->get_result();

/* Status color */
$statusColor = match ($lead['lead_status']) {
    'New' => 'primary',
    'Contacted' => 'info',
    'Follow-up' => 'warning',
    'Interested' => 'success',
    'Converted' => 'success',
    'Lost' => 'danger',
    default => 'secondary'
};
?>

<div class="container-fluid py-4">

<!-- =======================
     LEAD HEADER CARD
======================= -->
<div class="card mb-4 shadow-sm">
<div class="card-body d-flex justify-content-between align-items-center">

    <div>
        <h5 class="mb-1">
            <?= htmlspecialchars($lead['lead_name']) ?>
        </h5>
        <span class="badge bg-<?= $statusColor ?>">
            <?= htmlspecialchars($lead['lead_status']) ?>
        </span>
    </div>

    <div class="d-flex flex-wrap gap-2">

        <button class="btn btn-success btn-sm"
                data-bs-toggle="modal"
                data-bs-target="#followupModal">
            <i class="bi bi-telephone-plus me-1"></i>
            Add Follow-up
        </button>

        <button class="btn btn-danger btn-sm"
                data-bs-toggle="modal"
                data-bs-target="#visitModal">
            <i class="bi bi-geo-alt me-1"></i>
            Add Visit
        </button>

        <button class="btn btn-primary btn-sm"
                data-bs-toggle="modal"
                data-bs-target="#statusModal">
            <i class="bi bi-arrow-repeat me-1"></i>
            Change Status
        </button>

    </div>


</div>
</div>

<div class="row">

<!-- =======================
     LEAD DETAILS
======================= -->
<div class="col-lg-4">
<div class="card mb-4 shadow-sm">
<div class="card-body">

<h6 class="mb-3 text-muted">Lead Information</h6>

<div class="mb-2"><b>Company:</b><br><?= $lead['company_name'] ?: '-' ?></div>
<div class="mb-2"><b>Phone:</b><br><?= htmlspecialchars($lead['phone']) ?></div>
<div class="mb-2"><b>Email:</b><br><?= $lead['email'] ?: '-' ?></div>
<div class="mb-2"><b>Expected Value:</b><br>₹ <?= number_format($lead['expected_value'],2) ?></div>

</div>
</div>
</div>

<!-- =======================
     ACTIVITY TIMELINE
======================= -->
<div class="col-lg-8">
<div class="card shadow-sm">
<div class="card-body">

<h6 class="mb-3 text-muted">Activity Timeline</h6>

<?php if ($activities->num_rows === 0): ?>
    <p class="text-muted">No activity recorded yet.</p>
<?php else: ?>
<ul class="list-group list-group-flush">

<?php while ($a = $activities->fetch_assoc()): ?>
<li class="list-group-item border-0 border-start border-3 ps-3 mb-2">

    <div class="d-flex justify-content-between">
        <b><?= htmlspecialchars($a['activity_type']) ?></b>
        <small class="text-muted">
            <?= date("d M Y h:i A", strtotime($a['created_at'])) ?>
        </small>
    </div>

    <div class="mt-1">
        <?= $a['activity_text'] ?>
    </div>

    <small class="text-muted">
        by <?= $a['name'] ?? 'System' ?>
    </small>

</li>
<?php endwhile; ?>

</ul>
<?php endif; ?>

</div>
</div>
</div>

</div>
</div>

<!-- =======================
     FOLLOW-UP MODAL
======================= -->
<div class="modal fade" id="followupModal">
<div class="modal-dialog modal-dialog-centered">
<div class="modal-content">

<form id="followupForm">
<div class="modal-header">
    <h5 class="modal-title">Add Follow-up</h5>
    <button class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">
    <input type="hidden" name="lead_id" value="<?= $lead_id ?>">

    <select name="followup_type" class="form-control mb-2" required>
        <option value="">Select Type</option>
        <option>Call</option>
        <option>WhatsApp</option>
        <option>Email</option>
        <option>Meeting</option>
    </select>

    <input type="datetime-local"
           name="followup_date"
           class="form-control mb-2"
           required>

    <textarea name="remarks"
              class="form-control"
              placeholder="Remarks"
              required></textarea>
</div>

<div class="modal-footer">
    <button class="btn btn-success w-100">Save Follow-up</button>
</div>
</form>

</div>
</div>
</div>

<!-- =======================
     VISIT MODAL
======================= -->
<div class="modal fade" id="visitModal">
<div class="modal-dialog modal-dialog-centered">
<div class="modal-content">

<form id="visitForm">
<div class="modal-header">
    <h5 class="modal-title">Add Visit</h5>
    <button class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">
    <input type="hidden" name="lead_id" value="<?= $lead_id ?>">
    <textarea name="remarks"
              class="form-control"
              placeholder="Visit remarks"
              required></textarea>
</div>

<div class="modal-footer">
    <button class="btn btn-danger w-100">Save Visit</button>
</div>
</form>

</div>
</div>
</div>

<!-- =======================
     STATUS MODAL
======================= -->
<div class="modal fade" id="statusModal">
<div class="modal-dialog modal-dialog-centered">
<div class="modal-content">

<form id="statusForm">
<div class="modal-header">
    <h5 class="modal-title">Change Lead Status</h5>
    <button class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">
    <input type="hidden" name="lead_id" value="<?= $lead_id ?>">

    <select name="status" class="form-control" required>
        <?php
        $statuses = ['New','Contacted','Follow-up','Interested','Converted','Lost'];
        foreach ($statuses as $s):
        ?>
        <option value="<?= $s ?>" <?= $lead['lead_status']===$s?'selected':'' ?>>
            <?= $s ?>
        </option>
        <?php endforeach; ?>
    </select>
</div>

<div class="modal-footer">
    <button class="btn btn-primary w-100">Update Status</button>
</div>
</form>

</div>
</div>
</div>


<script>
function showToast(message, type = 'success', duration = 6000) {

    const toast = document.createElement('div');

    // IMPORTANT: unique class `custom-toast`
    toast.className = `alert alert-${type} custom-toast position-fixed top-0 end-0 m-3 shadow`;

    toast.style.zIndex = 9999;
    toast.style.opacity = '1';
    toast.style.transition = 'opacity 0.6s ease';
    toast.innerHTML = message;

    document.body.appendChild(toast);

    // Force repaint (important in some browsers)
    requestAnimationFrame(() => {
        toast.style.opacity = '1';
    });

    // Remove after duration
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 600);
    }, Number(duration)); // ensure it's a number
}



/* =========================
   FOLLOW-UP SUBMIT
========================= */
document.getElementById('followupForm')?.addEventListener('submit', async function (e) {
    e.preventDefault();

    const formData = new FormData(this);

    const res = await fetch('lead_followup', {
        method: 'POST',
        body: formData
    });

    const data = await res.json();

    if (data.status === 'success') {
        bootstrap.Modal.getInstance(document.getElementById('followupModal')).hide();
        showToast('Follow-up added successfully');
        setTimeout(() => {
                    location.reload();
                }, 6000); 

    } else {
        showToast(data.message || 'Error', 'danger');
    }
});

/* =========================
   VISIT SUBMIT
========================= */
document.getElementById('visitForm')?.addEventListener('submit', async function (e) {
    e.preventDefault();

    const formData = new FormData(this);

    const res = await fetch('lead_visit', {
        method: 'POST',
        body: formData
    });

    const data = await res.json();

    if (data.status === 'success') {
        bootstrap.Modal.getInstance(document.getElementById('visitModal')).hide();
        showToast('Visit added successfully');
        setTimeout(() => {
                    location.reload();
                }, 6000);

    } else {
        showToast(data.message || 'Error', 'danger');
    }
});

/* =========================
   STATUS UPDATE
========================= */
document.getElementById('statusForm')?.addEventListener('submit', async function (e) {
    e.preventDefault();

    const formData = new FormData(this);

    const res = await fetch('lead_status', {
        method: 'POST',
        body: formData
    });

    const data = await res.json();

    if (data.status === 'success') {

        // Update badge text
        document.querySelector('.badge').innerText = data.new_status;

        bootstrap.Modal.getInstance(document.getElementById('statusModal')).hide();
        showToast('Status updated successfully');
        setTimeout(() => {
                    location.reload();
                }, 6000); 
        
    } else {
        showToast(data.message || 'Error', 'danger');
    }
});
</script>


<?php include("footer.php"); ?>
