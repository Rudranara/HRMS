<?php
include("header.php");

if (!isset($_SESSION['admin_id'])) {
    exit("Admin login required");
}

$lead_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($lead_id <= 0) exit("Invalid lead");

/* =========================
   FETCH LEAD
========================= */
$stmt = $conn->prepare("
    SELECT 
        l.*,
        e.name AS assigned_name
    FROM leads l
    LEFT JOIN employees e ON l.assigned_to = e.id
    WHERE l.id = ?
");
$stmt->bind_param("i", $lead_id);
$stmt->execute();
$lead = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$lead) {
    echo "<div class='alert alert-danger'>Lead not found</div>";
    exit;
}

/* =========================
   FETCH ACTIVITY TIMELINE
========================= */
$timeline = $conn->prepare("
    SELECT 
        a.activity_type,
        a.activity_text,
        a.created_at,
        emp.name AS employee_name,
        ad.role
    FROM lead_activities a
    LEFT JOIN employees emp ON a.created_by = emp.id
    LEFT JOIN admins ad ON a.created_by = ad.id
    WHERE a.lead_id = ?
    ORDER BY a.created_at DESC
");
$timeline->bind_param("i", $lead_id);
$timeline->execute();
$activities = $timeline->get_result();

/* STATUS COLOR */
$statusClass = match ($lead['lead_status']) {
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

<div class="row g-4">

<!-- ===================== LEAD PROFILE ===================== -->
<div class="col-lg-5">
<div class="card shadow-sm h-100">
<div class="card-body">

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold">📌 Lead Profile</h5>
    <span class="badge bg-<?= $statusClass ?>">
        <?= htmlspecialchars($lead['lead_status']) ?>
    </span>
</div>

<div class="row small text-muted mb-2">
    <div class="col-12">
        <strong class="text-dark fs-6">
            <?= htmlspecialchars($lead['lead_name']) ?>
        </strong>
    </div>
</div>

<hr class="my-2">

<div class="row g-2 small">
    <div class="col-6"><b>Company</b></div>
    <div class="col-6"><?= $lead['company_name'] ?: '-' ?></div>

    <div class="col-6"><b>Phone</b></div>
    <div class="col-6"><?= htmlspecialchars($lead['phone']) ?></div>

    <div class="col-6"><b>Email</b></div>
    <div class="col-6"><?= $lead['email'] ?: '-' ?></div>

    <div class="col-6"><b>Source</b></div>
    <div class="col-6"><?= htmlspecialchars($lead['lead_source']) ?></div>

    <div class="col-6"><b>Assigned To</b></div>
    <div class="col-6"><?= $lead['assigned_name'] ?: 'Unassigned' ?></div>

    <div class="col-6"><b>Expected Value</b></div>
    <div class="col-6">₹ <?= number_format($lead['expected_value'], 2) ?></div>
</div>

<?php if (!empty($lead['notes'])): ?>
<hr>
<div class="small">
    <b>Notes</b><br>
    <?= nl2br(htmlspecialchars($lead['notes'])) ?>
</div>
<?php endif; ?>

<hr>

<small class="text-muted">
    Created on <?= date("d M Y h:i A", strtotime($lead['created_at'])) ?>
</small>

</div>
</div>
</div>

<!-- ===================== ACTIVITY TIMELINE ===================== -->
<div class="col-lg-7">
<div class="card shadow-sm h-100">
<div class="card-body">

<h5 class="fw-bold mb-3">🕒 Activity Timeline</h5>

<?php if ($activities->num_rows === 0): ?>
    <p class="text-muted">No activities recorded yet.</p>
<?php else: ?>
<ul class="list-group list-group-flush">

<?php while ($a = $activities->fetch_assoc()): ?>
<?php
$actor = ($a['role'] === 'Admin')
    ? 'Admin'
    : ($a['employee_name'] ?? 'Employee');
?>

<li class="list-group-item px-0">
    <div class="d-flex justify-content-between">
        <div>
            <strong><?= htmlspecialchars($a['activity_type']) ?></strong><br>
            <span class="text-muted small">
                <?= $a['activity_text'] ?>
            </span>
        </div>
        <div class="text-end small text-muted">
            <?= htmlspecialchars($actor) ?><br>
            <?= date("d M Y h:i A", strtotime($a['created_at'])) ?>
        </div>
    </div>
</li>

<?php endwhile; ?>

</ul>
<?php endif; ?>

</div>
</div>
</div>

</div>
</div>

<?php include("footer.php"); ?>
