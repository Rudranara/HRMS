<?php
include("header.php");

if (!isset($_SESSION['employee_id'])) {
    exit("Login required");
}

$employee_id = $_SESSION['employee_id'];

/* =========================
   FETCH LEAD AGING DATA
========================= */
$stmt = $conn->prepare("
    SELECT 
        l.id,
        l.lead_name,
        l.company_name,
        l.phone,
        l.lead_status,
        l.created_at,
        DATEDIFF(CURDATE(), DATE(l.created_at)) AS lead_age,
        MAX(f.followup_date) AS last_followup_date,
        DATEDIFF(CURDATE(), DATE(MAX(f.followup_date))) AS idle_days
    FROM leads l
    LEFT JOIN lead_followups f ON f.lead_id = l.id
    WHERE l.assigned_to = ?
    GROUP BY l.id
    ORDER BY lead_age DESC
");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$res = $stmt->get_result();

/* =========================
   KPI COUNTS
========================= */
$total = $fresh = $attention = $critical = 0;
$rows = [];

while ($row = $res->fetch_assoc()) {
    $rows[] = $row;
    $total++;

    if ($row['idle_days'] === null || $row['idle_days'] <= 3) {
        $fresh++;
    } elseif ($row['idle_days'] <= 6) {
        $attention++;
    } else {
        $critical++;
    }
}
?>

<div class="container-fluid py-4">

<!-- PAGE HEADER -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0"> Lead Aging Report</h5>
</div>

<!-- KPI CARDS -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <small class="text-muted">Total Leads</small>
                <h4><?= $total ?></h4>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <small class="text-muted">Fresh</small>
                <h4 class="text-success"><?= $fresh ?></h4>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <small class="text-muted">Needs Attention</small>
                <h4 class="text-warning"><?= $attention ?></h4>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <small class="text-muted">Critical</small>
                <h4 class="text-danger"><?= $critical ?></h4>
            </div>
        </div>
    </div>
</div>

<!-- LEAD AGING TABLE -->
<div class="row">
    <div class="col-12">
        <div class="card mb-4">

            <div class="card-body px-0 pt-0 pb-2">
                <div class="table-responsive p-0">

                    <table class="table align-items-center mb-0">

                        <thead>
                        <tr>
                            <th>Lead</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Lead Age</th>
                            <th>Idle Days</th>
                            <th class="text-end">Action</th>
                        </tr>
                        </thead>

                        <tbody>

                        <?php if ($total === 0): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                No leads assigned
                            </td>
                        </tr>
                        <?php endif; ?>

                        <?php foreach ($rows as $row): ?>

                        <?php
                        $idle = $row['idle_days'];

                        /* Idle badge logic (NO row color) */
                        if ($idle === null) {
                            $idleBadge = '<span class="badge bg-warning text-dark">No follow-up</span>';
                        } elseif ($idle <= 3) {
                            $idleBadge = '<span class="badge bg-success">Fresh</span>';
                        } elseif ($idle <= 6) {
                            $idleBadge = '<span class="badge bg-warning text-dark">Needs Attention</span>';
                        } else {
                            $idleBadge = '<span class="badge bg-danger">Critical</span>';
                        }

                        /* Status badge */
                        $statusClass = match ($row['lead_status']) {
                            'New'        => 'bg-primary',
                            'Contacted'  => 'bg-info',
                            'Follow-up'  => 'bg-warning text-dark',
                            'Interested' => 'bg-success',
                            'Converted'  => 'bg-success',
                            'Lost'       => 'bg-danger',
                            default      => 'bg-secondary'
                        };
                        ?>

                        <tr>
                            <td>
                                <b><?= htmlspecialchars($row['lead_name']) ?></b><br>
                                <small class="text-muted"><?= $row['company_name'] ?: '-' ?></small>
                            </td>

                            <td><?= htmlspecialchars($row['phone']) ?></td>

                            <td>
                                <span class="badge <?= $statusClass ?>">
                                    <?= htmlspecialchars($row['lead_status']) ?>
                                </span>
                            </td>

                            <td>
                                <?= (int)$row['lead_age'] ?> days
                            </td>

                            <td>
                                <?= $idleBadge ?>
                                <?php if ($idle !== null): ?>
                                    <small class="text-muted ms-1">(<?= $idle ?>d)</small>
                                <?php endif; ?>
                            </td>

                            <td class="text-end">
                                <a href="lead_view?id=<?= $row['id'] ?>"
                                   class="btn btn-sm btn-outline-dark">
                                    View
                                </a>
                            </td>
                        </tr>

                        <?php endforeach; ?>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


</div>

<?php include("footer.php"); ?>
