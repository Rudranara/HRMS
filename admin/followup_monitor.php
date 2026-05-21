<?php
include("header.php");

if (!isset($_SESSION['admin_id'])) {
    exit("Admin login required");
}

/* =========================
   FETCH FOLLOW-UP STATS
========================= */
$stmt = $conn->query("
    SELECT
        e.id AS employee_id,
        e.name AS employee_name,
        COUNT(f.id) AS total_followups,
        SUM(
            CASE 
                WHEN f.followup_date < NOW() THEN 1 
                ELSE 0 
            END
        ) AS overdue_followups
    FROM employees e
    LEFT JOIN lead_followups f 
        ON f.created_by = e.id
    GROUP BY e.id
    ORDER BY overdue_followups DESC, total_followups DESC
");
?>

<div class="container-fluid py-4">

<!-- PAGE HEADER -->
<div class="mb-4">
    <h5 class="fw-bold mb-1">Follow-up Monitoring</h5>
    <p class="text-muted small mb-0">
        Track employee follow-up performance and identify overdue actions
    </p>
</div>

<div class="row">
    <div class="col-12">
        <div class="card mb-4">

            <div class="card-body px-0 pt-0 pb-2">
                <div class="table-responsive p-0">

                    <table class="table align-items-center mb-0">

                        <thead>
                        <tr>
                            <th>Employee</th>
                            <th class="text-center">Total Follow-ups</th>
                            <th class="text-center">Overdue</th>
                            <th class="text-center">Health Status</th>
                        </tr>
                        </thead>

                        <tbody>

                        <?php if ($stmt->num_rows == 0): ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                No follow-up activity recorded yet
                            </td>
                        </tr>
                        <?php endif; ?>

                        <?php while ($r = $stmt->fetch_assoc()): ?>

                        <?php
                        $total   = (int)$r['total_followups'];
                        $overdue = (int)$r['overdue_followups'];

                        $isCritical = $overdue > 0;

                        $rowClass = $isCritical ? 'table-danger' : '';

                        $statusBadge = $isCritical
                            ? '<span class="badge bg-danger">Needs Attention</span>'
                            : '<span class="badge bg-success">On Track</span>';
                        ?>

                        <tr>

                            <!-- EMPLOYEE -->
                            <td>
                                <div class="fw-semibold">
                                    <?= htmlspecialchars($r['employee_name']) ?>
                                </div>
                                <div class="text-muted small">
                                    Employee ID: <?= $r['employee_id'] ?>
                                </div>
                            </td>

                            <!-- TOTAL FOLLOWUPS -->
                            <td class="text-center">
                                <span class="fw-bold">
                                    <?= $total ?>
                                </span>
                            </td>

                            <!-- OVERDUE -->
                            <td class="text-center">
                                <span class="badge <?= $isCritical ? 'bg-danger' : 'bg-success' ?>">
                                    <?= $overdue ?>
                                </span>
                            </td>

                            <!-- STATUS -->
                            <td class="text-center">
                                <?= $statusBadge ?>
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

<?php include("footer.php"); ?>
