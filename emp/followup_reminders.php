<?php
include("header.php");

if (!isset($_SESSION['employee_id'])) {
    exit("Login required");
}

$employee_id = $_SESSION['employee_id'];

/* =========================
   FETCH FOLLOW-UPS
========================= */
$stmt = $conn->prepare("
    SELECT
        f.id AS followup_id,
        f.followup_type,
        f.followup_date,
        f.remarks,
        l.id AS lead_id,
        l.lead_name,
        l.company_name,
        l.phone,
        DATEDIFF(CURDATE(), DATE(f.followup_date)) AS overdue_days
    FROM lead_followups f
    JOIN leads l ON f.lead_id = l.id
    WHERE f.created_by = ?
    ORDER BY f.followup_date ASC
");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$followups = $stmt->get_result();

/* =========================
   KPI CALCULATION
========================= */
$total = $today = $overdue = $upcoming = 0;
$todayDate = date('Y-m-d');

$rows = [];
while ($row = $followups->fetch_assoc()) {
    $rows[] = $row;
    $total++;

    $fDate = date('Y-m-d', strtotime($row['followup_date']));
    if ($row['overdue_days'] > 0) {
        $overdue++;
    } elseif ($fDate === $todayDate) {
        $today++;
    } else {
        $upcoming++;
    }
}
?>

<div class="container-fluid py-4">

<!-- PAGE HEADER -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0"> Follow-up Reminders</h5>
</div>

<!-- KPI CARDS -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <small class="text-muted">Total</small>
                <h4><?= $total ?></h4>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <small class="text-muted">Today</small>
                <h4 class="text-warning"><?= $today ?></h4>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <small class="text-muted">Overdue</small>
                <h4 class="text-danger"><?= $overdue ?></h4>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <small class="text-muted">Upcoming</small>
                <h4 class="text-success"><?= $upcoming ?></h4>
            </div>
        </div>
    </div>
</div>

<!-- FOLLOW-UP TABLE -->

<div class="row">
    <div class="col-12">
        <div class="card mb-4">

            <div class="card-body px-0 pt-0 pb-2">
                <div class="table-responsive p-0">

                    <table class="table align-items-center mb-0">

                        <thead >
                        <tr>
                            <th>Lead</th>
                            <th>Phone</th>
                            <th>Type</th>
                            <th>Follow-up Time</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                        </thead>

                        <tbody>

                        <?php if ($total === 0): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="bi bi-check2-circle fs-4 d-block mb-2"></i>
                                    No follow-ups scheduled 🎉
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($rows as $f): ?>

                            <?php
                            $fDate = date('Y-m-d', strtotime($f['followup_date']));
                            $isOverdue = ($f['overdue_days'] > 0);
                            $isToday   = ($fDate === $todayDate);

                            
                            ?>

                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($f['lead_name']) ?></strong><br>
                                    <small class="text-muted">
                                        <?= $f['company_name'] ?: '-' ?>
                                    </small>
                                </td>

                                <td><?= htmlspecialchars($f['phone']) ?></td>

                                <td><?= htmlspecialchars($f['followup_type']) ?></td>

                                <td>
                                    <?= date("d M Y", strtotime($f['followup_date'])) ?><br>
                                    <small class="text-muted">
                                        <?= date("h:i A", strtotime($f['followup_date'])) ?>
                                    </small>
                                </td>

                                <td>
                                    <?php if ($isOverdue): ?>
                                        <span class="badge bg-danger">
                                            Overdue <?= $f['overdue_days'] ?> day(s)
                                        </span>
                                    <?php elseif ($isToday): ?>
                                        <span class="badge bg-warning text-dark">
                                            Today
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success">
                                            Upcoming
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td class="text-end">
                                    <a href="lead_view?id=<?= $f['lead_id'] ?>"
                                       class="btn btn-outline-primary btn-sm">
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
