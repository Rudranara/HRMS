<?php
include("header.php");

if (!isset($_SESSION['admin_id'])) {
    exit("Admin login required");
}

/* =========================
   FILTERS
========================= */
$statusFilter = $_GET['status'] ?? '';
$search       = trim($_GET['search'] ?? '');

/* =========================
   FETCH EMPLOYEES
========================= */
$employees = [];
$empRes = $conn->query("SELECT id, name FROM employees ORDER BY name");
while ($e = $empRes->fetch_assoc()) {
    $employees[] = $e;
}

/* =========================
   BUILD QUERY
========================= */
$where = [];
$params = [];
$types  = '';

if ($statusFilter !== '') {
    $where[] = "l.lead_status = ?";
    $params[] = $statusFilter;
    $types   .= 's';
}

if ($search !== '') {
    $where[] = "(l.lead_name LIKE ? OR l.phone LIKE ? OR l.company_name LIKE ?)";
    $searchLike = "%$search%";
    array_push($params, $searchLike, $searchLike, $searchLike);
    $types .= 'sss';
}

$sql = "
    SELECT 
        l.id,
        l.lead_name,
        l.company_name,
        l.phone,
        l.lead_status,
        l.assigned_to,
        l.created_at,
        c.name AS created_by_name,
        a.name AS assigned_name
    FROM leads l
    LEFT JOIN employees c ON l.created_by = c.id
    LEFT JOIN employees a ON l.assigned_to = a.id
";

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY l.created_at DESC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$leads = $stmt->get_result();
?>

<div class="container-fluid py-4">

<!-- ================= HEADER ================= -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0"> Manage Leads</h4>
</div>

<!-- ================= SUCCESS MESSAGE ================= -->
<?php if (isset($_GET['assigned'])): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    ✅ Lead assignment updated successfully
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- ================= FILTER BAR ================= -->
<div class="card mb-3 shadow-sm border-0">
<div class="card-body">

<form method="GET" class="row g-2 align-items-center">

    <div class="col-md-3">
        <input type="text"
               name="search"
               value="<?= htmlspecialchars($search) ?>"
               class="form-control"
               placeholder="🔍 Search lead / phone / company">
    </div>

    <div class="col-md-3">
        <select name="status" class="form-control">
            <option value="">All Status</option>
            <?php
            $statuses = ['New','Contacted','Follow-up','Interested','Converted','Not Interested','Lost'];
            foreach ($statuses as $s):
            ?>
            <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>>
                <?= $s ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-md-2">
        <button class="btn btn-dark w-100">
            Apply
        </button>
    </div>

    <div class="col-md-2">
        <a href="manage_leads" class="btn btn-outline-secondary w-100">
            Reset
        </a>
    </div>

</form>

</div>
</div>

<!-- ================= TABLE ================= -->
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
                            <th>Created By</th>
                            <th>Assigned To</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                        </thead>

                        <tbody>

                        <?php if ($leads->num_rows === 0): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                No leads found
                            </td>
                        </tr>
                        <?php endif; ?>

                        <?php while ($row = $leads->fetch_assoc()): ?>

                        <?php
                        $statusClass = match ($row['lead_status']) {
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
                            <div class="fw-bold"><?= htmlspecialchars($row['lead_name']) ?></div>
                            <small class="text-muted"><?= $row['company_name'] ?: '-' ?></small>
                        </td>

                        <td><?= htmlspecialchars($row['phone']) ?></td>

                        <td><?= htmlspecialchars($row['created_by_name'] ?? 'Admin') ?></td>

                        <td>
                        <form method="POST" action="assign_lead" class="d-flex">
                            <input type="hidden" name="lead_id" value="<?= $row['id'] ?>">

                            <select name="assigned_to" class="form-control form-control-sm">
                                <option value="">Unassigned</option>
                                <?php foreach ($employees as $e): ?>
                                    <option value="<?= $e['id'] ?>"
                                        <?= ($row['assigned_to'] == $e['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($e['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <button class="btn btn-sm btn-primary ms-2">Save</button>
                        </form>
                        </td>

                        <td>
                            <span class="badge <?= $statusClass ?>">
                                <?= htmlspecialchars($row['lead_status']) ?>
                            </span>
                        </td>

                        <td class="text-end">
                            <a href="view_lead?id=<?= $row['id'] ?>"
                               class="btn btn-sm btn-outline-dark">
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

<script>
/* Auto hide success alert + clean URL */
document.addEventListener("DOMContentLoaded", () => {

    const alertBox = document.querySelector(".alert-success");
    if (!alertBox) return;

    setTimeout(() => {
        alertBox.remove();
    }, 3500);

    if (window.history.replaceState) {
        const url = new URL(window.location);
        url.searchParams.delete('assigned');
        window.history.replaceState({}, document.title, url.pathname + url.search);
    }
});
</script>

<?php include("footer.php"); ?>
