<?php
include("header.php");

if (!isset($_SESSION['admin_id'])) {
    header("Location: login");
    exit;
}

if (isset($_POST['doc_action'])) {

    $doc_id = intval($_POST['doc_id']);
    $action = $_POST['doc_action'];
    $admin_id = $_SESSION['admin_id']; // Make sure admin login stores this

    if ($action === 'approved') {

        $stmt = $conn->prepare("
            UPDATE asset_documents
            SET status = 'approved',
                approved_by = ?,
                approved_at = NOW()
            WHERE id = ? AND status = 'uploaded'
        ");
        $stmt->bind_param("ii", $admin_id, $doc_id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $_SESSION['success'] = "Document approved successfully";
        } else {
            $_SESSION['error'] = "Document already processed.";
        }

    }

    if ($action === 'rejected') {

        
        $reason = trim($_POST['rejection_reason']);

          if ($reason === '') {
              $_SESSION['error'] = "Rejection reason is required.";
              header("Location: asset_documents");
              exit;
          }


        $stmt = $conn->prepare("
            UPDATE asset_documents
            SET status = 'rejected',
                rejection_reason = ?
            WHERE id = ? AND status = 'uploaded'
        ");
        $stmt->bind_param("si", $reason, $doc_id);
        $stmt->execute();

        //$_SESSION['success'] = "Document rejected successfully";
        if ($stmt->affected_rows > 0) {
            $_SESSION['success'] = "Document rejected successfully";
        } else {
            $_SESSION['error'] = "Document already processed.";
        }
    }

    header("Location: asset_documents");
    exit;
}




$stmt = $conn->prepare("
  SELECT 
      ad.id,
      ad.employee_document_path,
      ad.uploaded_at,
      ad.status,
      a.asset_name,
      e.name AS employee_name
  FROM asset_documents ad
  JOIN asset_assignments aa ON aa.id = ad.asset_assignment_id
  JOIN assets a ON a.id = aa.asset_id
  JOIN employees e ON e.id = aa.employee_id
  WHERE ad.status IN ('uploaded', 'approved')
    AND aa.status = 'assigned'
  ORDER BY ad.uploaded_at DESC
");
$stmt->execute();

$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);



?>


<div class="container-fluid py-4">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="table-responsive p-3">

          <table class="table align-items-center mb-0">
            <thead>
              <tr>
                <th>Employee</th>
                <th>Asset</th>
                <th>Signed Document</th>
                <th>Uploaded On</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>

              <tbody>
                <?php if (count($data) > 0): ?>
                  <?php foreach ($data as $d): ?>
                    <tr>
                      <!-- EMPLOYEE -->
                      <td><?= htmlspecialchars($d['employee_name']) ?></td>

                      <!-- ASSET -->
                      <td><?= htmlspecialchars($d['asset_name']) ?></td>

                      <!-- SIGNED DOCUMENT -->
                      <td>
                        <a href="<?= $d['employee_document_path'] ?>"
                           target="_blank"
                           class="btn btn-sm btn-info">
                          View Signed PDF
                        </a>
                      </td>

                      <!-- UPLOADED DATE -->
                      <td>
                        <?= date("d M Y", strtotime($d['uploaded_at'])) ?>
                      </td>

                      <!-- STATUS -->
                      <td>
                        <?php if ($d['status'] === 'uploaded'): ?>
                          <span class="badge bg-warning">Pending</span>

                        <?php elseif ($d['status'] === 'approved'): ?>
                          <span class="badge bg-success">Approved</span>
                        <?php endif; ?>
                      </td>

                      <!-- ACTION -->
                      <td>
                        <?php if ($d['status'] === 'uploaded'): ?>

                          <!-- APPROVE -->
                          <form method="POST" class="d-inline">
                            <input type="hidden" name="doc_id" value="<?= $d['id'] ?>">
                            <input type="hidden" name="doc_action" value="approved">
                            <button type="submit"
                                    class="btn btn-sm btn-success"
                                    onclick="return confirm('Approve this document?')">
                              Approve
                            </button>
                          </form>

                          <!-- REJECT -->
                          <form method="POST" class="d-inline">
                              <input type="hidden" name="doc_id" value="<?= $d['id'] ?>">
                              <input type="hidden" name="doc_action" value="rejected">

                              <button type="submit"
                                      class="btn btn-sm btn-danger"
                                      onclick="return confirmReject(this.form);">
                                  Reject
                              </button>
                          </form>



                        <?php elseif ($d['status'] === 'approved'): ?>

                          <!-- ADMIN PROOF ACTION -->
                          <a href="<?= $d['employee_document_path'] ?>"
                             download
                             class="btn btn-sm btn-primary">
                            Download
                          </a>

                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="6" class="text-center text-muted">
                      No signed documents found
                    </td>
                  </tr>
                <?php endif; ?>
                </tbody>

          </table>

        </div>
      </div>
    </div>
  </div>
</div>
<script>
function confirmReject(form) {
    let reason = prompt("Enter rejection reason:");
    if (!reason) return false;

    let input = document.createElement("input");
    input.type = "hidden";
    input.name = "rejection_reason";
    input.value = reason;

    form.appendChild(input);
    return true;
}
</script>

<?php include("footer.php"); ?>
