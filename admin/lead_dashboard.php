<?php
include("header.php");

if (!isset($_SESSION['admin_id'])) {
    exit("Admin login required");
}

/* ================= KPI COUNTS ================= */
$totalLeads = $conn->query("SELECT COUNT(*) c FROM leads")->fetch_assoc()['c'];
$newLeads = $conn->query("SELECT COUNT(*) c FROM leads WHERE lead_status='New'")->fetch_assoc()['c'];
$followupLeads = $conn->query("SELECT COUNT(*) c FROM leads WHERE lead_status='Follow-up'")->fetch_assoc()['c'];
$convertedLeads = $conn->query("SELECT COUNT(*) c FROM leads WHERE lead_status='Converted'")->fetch_assoc()['c'];
$lostLeads = $conn->query("SELECT COUNT(*) c FROM leads WHERE lead_status='Lost'")->fetch_assoc()['c'];

$conversionRate = $totalLeads > 0
    ? round(($convertedLeads / $totalLeads) * 100, 2)
    : 0;

/* ================= LEADS BY STATUS ================= */
$statusData = $conn->query("
    SELECT lead_status, COUNT(*) total
    FROM leads
    GROUP BY lead_status
");

/* ================= FOLLOW-UPS TODAY ================= */
$followupsToday = $conn->query("
    SELECT 
        l.lead_name,
        f.followup_date,
        e.name AS employee_name
    FROM lead_followups f
    JOIN leads l ON f.lead_id = l.id
    JOIN employees e ON f.created_by = e.id
    WHERE DATE(f.followup_date) = CURDATE()
    ORDER BY f.followup_date ASC
");
?>

<style>
.dashboard-shell {
    padding-bottom: 1.5rem;
}

.dashboard-muted {
    color: #6b7280;
}

.filter-card,
.panel-card,
.metric-card {
    border: 1px solid rgba(87, 96, 108, 0.12);
    border-radius: 22px;
    box-shadow: 0 14px 34px rgba(31, 41, 55, 0.06);
    background: #fff;
}

.filter-card {
    padding: 1.25rem;
    margin-bottom: 1.5rem;
}

.metric-card {
    padding: 1.15rem;
    height: 100%;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.metric-card.metric-action {
    cursor: pointer;
}

.metric-card.metric-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 18px 38px rgba(31, 41, 55, 0.09);
}

.metric-icon {
    width: 52px;
    height: 52px;
    border-radius: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    margin-bottom: 1rem;
}

.metric-icon.metric-slate { background: #eef2f7; color: #374151; }
.metric-icon.metric-blue { background: #e7f0fb; color: #275ea8; }
.metric-icon.metric-red { background: #fcebea; color: #d14343; }
.metric-icon.metric-amber { background: #fff4dd; color: #b7791f; }
.metric-icon.metric-green { background: #e8f7ef; color: #1f8f57; }
.metric-icon.metric-purple { background: #f2ecff; color: #6d4bc3; }

.metric-label {
    color: #6b7280;
    font-size: 0.85rem;
    margin-bottom: 0.35rem;
}

.metric-value {
    color: #111827;
    font-size: 1.85rem;
    font-weight: 800;
    line-height: 1.1;
    margin-bottom: 0.45rem;
}

.metric-footnote {
    color: #94a3b8;
    font-size: 0.78rem;
}

.panel-card {
    padding: 1.25rem;
    height: 100%;
}

.panel-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1rem;
}

.panel-title h5 {
    margin: 0;
    color: #1f2937;
}

.lead-dashboard-table {
    margin-bottom: 0;
}

.lead-dashboard-table thead th {
    border-bottom: 1px solid #e8edf5;
    background: #f8fafc;
    color: #64748b;
    font-size: 0.74rem;
    font-weight: 800;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    padding-top: 0.95rem;
    padding-bottom: 0.95rem;
}

.lead-dashboard-table tbody td {
    padding-top: 0.95rem;
    padding-bottom: 0.95rem;
    border-color: #eef2f7;
    vertical-align: middle;
    color: #374151;
    font-size: 0.9rem;
}

.lead-dashboard-table tbody tr:hover {
    background: #fbfcfe;
}

.lead-dashboard-empty {
    margin: 0;
    color: #6b7280;
    font-size: 0.92rem;
}
</style>

<div class="container-fluid container-fluid-main dashboard-shell py-4">

<div class="filter-card">
    <div class="row align-items-center g-3">
        <div class="col-lg-8">
            <div class="dashboard-muted text-uppercase fw-bold" style="font-size:0.78rem; letter-spacing:0.04em;">Lead Management</div>
            <h4 class="mb-1 fw-bold">Lead Dashboard</h4>
            <p class="dashboard-muted mb-0">Monitor lead flow, conversion performance, and today's follow-up workload from one dashboard.</p>
        </div>
        <div class="col-lg-4 text-lg-end">
            <span class="badge bg-dark px-3 py-2" style="font-size:0.85rem; border-radius:14px;">Conversion Rate: <?= $conversionRate ?>%</span>
        </div>
    </div>
</div>

<div class="row g-3">

<?php
$cards = [
    ['Total Leads', $totalLeads, 'All lead records', 'metric-slate', 'bi bi-people', 'manage_leads'],
    ['New', $newLeads, 'Freshly added leads', 'metric-blue', 'bi bi-stars', 'manage_leads?status=New'],
    ['Follow-up', $followupLeads, 'Pending next action', 'metric-amber', 'bi bi-bell', 'manage_leads?status=Follow-up'],
    ['Converted', $convertedLeads, 'Successfully closed', 'metric-green', 'bi bi-check2-circle', 'manage_leads?status=Converted'],
    ['Lost', $lostLeads, 'Dropped opportunities', 'metric-red', 'bi bi-x-circle', 'manage_leads?status=Lost'],
    ['Conversion %', $conversionRate . '%', 'Overall conversion ratio', 'metric-purple', 'bi bi-graph-up-arrow', 'manage_leads']
];

foreach ($cards as $c):
?>
<div class="col-xl-2 col-md-4 col-sm-6">
    <a href="<?= $c[5] ?>" class="text-decoration-none d-block h-100">
        <div class="metric-card metric-action">
            <div class="metric-icon <?= $c[3] ?>"><i class="<?= $c[4] ?>"></i></div>
            <div class="metric-label"><?= $c[0] ?></div>
            <div class="metric-value"><?= $c[1] ?></div>
            <div class="metric-footnote"><?= $c[2] ?></div>
        </div>
    </a>
</div>
<?php endforeach; ?>

</div>

<!-- ================= STATUS + FOLLOWUPS ================= -->
<div class="row mt-4 g-3">

<!-- Leads by Status -->
<div class="col-md-6">
<div class="panel-card">
<div class="panel-title">
<h5>Leads by Status</h5>
</div>

<table class="table table-sm align-middle mb-0 lead-dashboard-table">
<thead >
<tr>
    <th>Status</th>
    <th>Total</th>
</tr>
</thead>
<tbody>
<?php while ($s = $statusData->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($s['lead_status']) ?></td>
    <td><?= $s['total'] ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

</div>
</div>

<!-- Follow-ups Today -->
<div class="col-md-6">
<div class="panel-card">
<div class="panel-title">
<h5>Follow-ups Due Today</h5>
</div>

<?php if ($followupsToday->num_rows == 0): ?>
    <p class="lead-dashboard-empty">No follow-ups scheduled today.</p>
<?php else: ?>
<table class="table table-sm align-middle mb-0 lead-dashboard-table">
<thead>
<tr>
    <th>Lead</th>
    <th>Employee</th>
    <th>Time</th>
</tr>
</thead>
<tbody>
<?php while ($f = $followupsToday->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($f['lead_name']) ?></td>
    <td><?= htmlspecialchars($f['employee_name']) ?></td>
    <td><?= date("h:i A", strtotime($f['followup_date'])) ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
<?php endif; ?>

</div>
</div>

</div>

</div>

<?php include("footer.php"); ?>
