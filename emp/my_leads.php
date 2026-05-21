<?php
include("header.php");

if (!isset($_SESSION['employee_id'])) {
    exit("Login required");
}

$employee_id = $_SESSION['employee_id'];

/* =========================
   FETCH EMPLOYEE LEADS
========================= */
$stmt = $conn->prepare("
    SELECT 
        id,
        lead_name,
        company_name,
        phone,
        lead_status,
        created_at
    FROM leads
    WHERE assigned_to = ?
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$leads = $stmt->get_result();

/* =========================
   KPI COUNTS
========================= */
$kpi = $conn->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(lead_status = 'New') AS new_count,
        SUM(lead_status = 'Follow-up') AS followup_count,
        SUM(lead_status = 'Converted') AS converted_count
    FROM leads
    WHERE assigned_to = ?
");
$kpi->bind_param("i", $employee_id);
$kpi->execute();
$stats = $kpi->get_result()->fetch_assoc();
?>

<div class="container-fluid py-4">

<!-- PAGE HEADER -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0"> My Leads</h5>

    <a href="add_lead" class="btn bg-gradient-dark">
        <i class="bi bi-plus-circle me-2"></i> Add Lead
    </a>
</div>

<!-- SUCCESS MESSAGE -->
<?php if (isset($_GET['added'])): ?>
<div id="successAlert" class="alert alert-success alert-dismissible fade show">
    ✅ Lead added successfully
    <button class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- KPI CARDS -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <small class="text-muted">Total Leads</small>
                <h4><?= (int)$stats['total'] ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <small class="text-muted">New</small>
                <h4 class="text-primary"><?= (int)$stats['new_count'] ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <small class="text-muted">Follow-up</small>
                <h4 class="text-warning"><?= (int)$stats['followup_count'] ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <small class="text-muted">Converted</small>
                <h4 class="text-success"><?= (int)$stats['converted_count'] ?></h4>
            </div>
        </div>
    </div>
</div>

<!-- LEADS TABLE -->
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
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                        </thead>

                        <tbody>

                        <?php if ($leads->num_rows === 0): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                                    No leads assigned yet
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php while ($l = $leads->fetch_assoc()): ?>

                            <?php
                            $statusClass = match ($l['lead_status']) {
                                'New'            => 'bg-primary',
                                'Contacted'      => 'bg-info',
                                'Follow-up'      => 'bg-warning text-dark',
                                'Interested'     => 'bg-success',
                                'Converted'      => 'bg-success',
                                'Not Interested' => 'bg-secondary',
                                'Lost'           => 'bg-danger',
                                default          => 'bg-secondary'
                            };
                            ?>

                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($l['lead_name']) ?></strong><br>
                                    <small class="text-muted">
                                        <?= $l['company_name'] ?: '-' ?>
                                    </small>
                                </td>

                                <td><?= htmlspecialchars($l['phone']) ?></td>

                                <td>
                                    <span class="badge <?= $statusClass ?>">
                                        <?= htmlspecialchars($l['lead_status']) ?>
                                    </span>
                                </td>

                                <td class="text-end">
                                    <a href="lead_view?id=<?= $l['id'] ?>"
                                       class="btn btn-outline-primary btn-sm">
                                        View
                                    </a>
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

<!-- SUCCESS ALERT HANDLER -->
<script>
document.addEventListener("DOMContentLoaded", function () {

    const alertBox = document.getElementById("successAlert");
    if (!alertBox) return;

    setTimeout(() => {
        alertBox.classList.remove("show");
        setTimeout(() => alertBox.remove(), 300);
    }, 4000);

    // Remove URL param
    const url = new URL(window.location);
    url.searchParams.delete("added");
    window.history.replaceState({}, document.title, url.pathname);
});
</script>

<?php include("footer.php"); ?>
