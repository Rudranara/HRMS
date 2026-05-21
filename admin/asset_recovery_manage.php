<?php
include("header.php");

/* ================================
   FILTER HANDLING
================================ */

$statusFilter = $_GET['status'] ?? 'all';

$whereClause = "";
if ($statusFilter === 'pending') {
    $whereClause = "WHERE ar.status = 'pending'";
} elseif ($statusFilter === 'paid') {
    $whereClause = "WHERE ar.status = 'paid'";
}

/* ================================
   HANDLE RECOVERY UPDATE
================================ */

if (isset($_POST['update_recovery'])) {

    $recovery_id = intval($_POST['recovery_id']);
    $amount = floatval($_POST['recovery_amount']);
    $remarks = trim($_POST['recovery_remarks']);

    $stmt = $conn->prepare("
        UPDATE asset_recovery
        SET recovery_amount = ?,
            remarks = ?,
            status = 'paid',
            paid_at = NOW()
        WHERE id = ? AND status = 'pending'
    ");
    $stmt->bind_param("dsi", $amount, $remarks, $recovery_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $_SESSION['success'] = "Recovery marked as paid successfully.";
    } else {
        $_SESSION['error'] = "Recovery already processed.";
    }

    header("Location: asset_recovery_manage");
    exit;
}

/* ================================
   FETCH DATA
================================ */

$recoveries = $conn->query("
    SELECT 
        ar.*,
        a.asset_name,
        a.asset_code,
        e.name AS employee_name
    FROM asset_recovery ar
    JOIN assets a ON a.id = ar.asset_id
    JOIN employees e ON e.id = ar.employee_id
    $whereClause
    ORDER BY ar.status ASC, ar.id DESC
")->fetch_all(MYSQLI_ASSOC);
?>

<div class="container-fluid py-4">

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Asset Recovery Management</h4>
        <small class="text-muted">Manage damaged & lost asset recoveries</small>
    </div>
</div>


<!-- FILTER BUTTONS -->
<div class="col-12 text-end mb-4">

    <a href="?status=all"
       class="btn btn-sm bg-gradient-dark me-2 <?= $statusFilter=='all' ? '' : 'opacity-8' ?>">
        All
    </a>

    <a href="?status=pending"
       class="btn btn-sm bg-gradient-info me-2 <?= $statusFilter=='pending' ? '' : 'opacity-8' ?>">
        Pending
    </a>

    <a href="?status=paid"
       class="btn btn-sm bg-gradient-success <?= $statusFilter=='paid' ? '' : 'opacity-8' ?>">
        Paid
    </a>

</div>






<?php if (isset($_SESSION['success'])): ?>
    <div id="alertBox" class="alert alert-success">
        <?= $_SESSION['success']; unset($_SESSION['success']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div id="alertBox" class="alert alert-danger">
        <?= $_SESSION['error']; unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>


<div class="card shadow-sm border-0 rounded-4">

<div class="table-responsive p-3">

<table class="table align-items-center mb-0">

<thead>
<tr>
<th>Employee</th>
<th>Asset</th>
<th>Code</th>
<th>Type</th>
<th>Amount</th>
<th>Status</th>
<th>Created</th>
<th>Paid At</th>
<th class="text-center">Action</th>
</tr>
</thead>

<tbody>

<?php if(count($recoveries) > 0): ?>
<?php foreach($recoveries as $r): ?>
<tr>

<td class="fw-semibold">
    <?= htmlspecialchars($r['employee_name']) ?>
</td>

<td>
    <?= htmlspecialchars($r['asset_name']) ?>
</td>

<td>
    <span class="badge bg-secondary">
        <?= htmlspecialchars($r['asset_code']) ?>
    </span>
</td>

<td>
    <?php if($r['recovery_type'] === 'damaged'): ?>
    <span class="badge rounded-pill bg-warning text-dark">
        ⚠ Damaged
    </span>
<?php else: ?>
    <span class="badge rounded-pill bg-dark">
        ✖ Lost
    </span>
<?php endif; ?>

</td>

<td class="fw-bold text-danger">
    ₹<?= number_format($r['recovery_amount'], 2) ?>
</td>

<td>
    <?php if($r['status'] === 'pending'): ?>
    <span class="badge rounded-pill bg-danger">
        ● Pending
    </span>
<?php else: ?>
    <span class="badge rounded-pill bg-success">
        ● Paid
    </span>
<?php endif; ?>

</td>

<td>
    <?= date("d M Y", strtotime($r['created_at'])) ?>
</td>

<td>
    <?= $r['paid_at'] 
        ? date("d M Y", strtotime($r['paid_at'])) 
        : '<span class="text-muted">-</span>' ?>
</td>

<td class="text-center">
<?php if($r['status'] === 'pending'): ?>
    <button class="btn btn-sm btn-warning rounded-pill px-3"

        onclick="openRecoveryModal(
            '<?= $r['id'] ?>',
            '<?= $r['recovery_amount'] ?>'
        )">
        Update
    </button>
<?php else: ?>
    <span class="text-muted small">Completed</span>
<?php endif; ?>
</td>

</tr>
<?php endforeach; ?>
<?php else: ?>
<tr>
<td colspan="9" class="text-center text-muted py-4">
No recovery records found
</td>
</tr>
<?php endif; ?>

</tbody>
</table>

</div>
</div>
</div>


<!-- ================================
     RECOVERY MODAL
================================ -->

<div class="modal-custom" id="recoveryModal">
<div class="modal-content">

<div class="d-flex justify-content-between align-items-center mb-3">
<h5 class="mb-0">Update Recovery</h5>
<button type="button" class="modal-close-btn"
onclick="closeRecoveryModal()">&times;</button>
</div>

<form method="POST">
<input type="hidden" name="recovery_id" id="recovery_id">

<label>Recovery Amount</label>
<input type="number"
step="0.01"
min="0"
name="recovery_amount"
id="recovery_amount"
class="form-control mb-3"
required>

<label>Remarks</label>
<textarea name="recovery_remarks"
class="form-control mb-3"></textarea>

<button type="submit"
name="update_recovery"
class="btn btn-danger w-100">
Mark as Paid
</button>
</form>

</div>
</div>


<style>
.modal-custom {
display: none;
position: fixed;
inset: 0;
background: rgba(15, 23, 42, 0.55);
z-index: 1055;
align-items: center;
justify-content: center;
}


.modal-content {
    background: #fff;
    padding: 24px;
    border-radius: 18px;
    width: 420px;
    max-width: 95%;
    box-shadow: 0 20px 45px rgba(0,0,0,0.15);
}


.modal-close-btn {
background: transparent;
border: none;
font-size: 24px;
cursor: pointer;
}



</style>

<script>
function openRecoveryModal(id, amount) {
document.getElementById('recovery_id').value = id;
document.getElementById('recovery_amount').value = amount;
document.getElementById('recoveryModal').style.display = 'flex';
}

function closeRecoveryModal() {
document.getElementById('recoveryModal').style.display = 'none';
}

setTimeout(function () {
const alertBox = document.getElementById('alertBox');
if (alertBox) {
alertBox.style.transition = 'opacity 0.5s ease';
alertBox.style.opacity = '0';
setTimeout(() => alertBox.remove(), 500);
}
}, 5000);
</script>

<?php include("footer.php"); ?>
