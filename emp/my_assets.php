<?php

require_once("db_connection.php");
session_start();
/* ===============================
   HANDLE FORM SUBMISSION FIRST
================================ */
if (isset($_POST['upload_signed_document'])) {

    $assign_id = intval($_POST['assign_id']);

    $uploadDir = "../uploads/asset_docs/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileName = time() . '_' . basename($_FILES['signed_document']['name']);

    $filePath = $uploadDir . $fileName;

    $fileType = mime_content_type($_FILES['signed_document']['tmp_name']);

    if ($fileType !== 'application/pdf') {
        $_SESSION['error'] = "Invalid file type. Only PDF allowed.";
        header("Location: my_assets");
        exit;
    }

    if ($_FILES['signed_document']['size'] > 5 * 1024 * 1024) {
        $_SESSION['error'] = "File too large. Max 5MB allowed.";
        header("Location: my_assets");
        exit;
    }


    
    if (!move_uploaded_file($_FILES['signed_document']['tmp_name'], $filePath)) {
        $_SESSION['error'] = "File upload failed.";
        header("Location: my_assets");
        exit;
    }


    $stmt = $conn->prepare("
        INSERT INTO asset_documents
            (asset_assignment_id, employee_document_path, status, uploaded_at)
        VALUES (?, ?, 'uploaded', NOW())
        ON DUPLICATE KEY UPDATE
            employee_document_path = VALUES(employee_document_path),
            status = 'uploaded',
            uploaded_at = NOW()
    ");

    $stmt->bind_param("is", $assign_id, $filePath);
    $stmt->execute();
    


    if ($stmt->affected_rows > 0) {
        $_SESSION['success'] = "Signed document uploaded successfully";
    } else {
        $_SESSION['success'] = "Document already up to date";
    }

    header("Location: my_assets");
    exit;
}

/* ===============================
   NOW SAFE TO OUTPUT HTML
================================ */



$emp_id = $_SESSION['employee_id'];

$sql = $conn->prepare("
  SELECT 
      aa.id AS assign_id,
      aa.status AS assignment_status,
      aa.return_condition,
      a.asset_name,
      ad.admin_document_path,
      ad.employee_document_path,
      ad.status
  FROM asset_assignments aa
  JOIN assets a ON a.id = aa.asset_id
  LEFT JOIN asset_documents ad
         ON ad.asset_assignment_id = aa.id
  WHERE aa.employee_id = ?
");


$sql->bind_param("i", $emp_id);
$sql->execute();
$data = $sql->get_result()->fetch_all(MYSQLI_ASSOC);


include("header.php");
?>

<?php if (isset($_SESSION['success'])): ?>
  <div id="successAlert" class="alert alert-success">
    <?= $_SESSION['success'] ?>
  </div>
  <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
  <div id="successAlert" class="alert alert-danger">
    <?= $_SESSION['error'] ?>
  </div>
  <?php unset($_SESSION['error']); ?>
<?php endif; ?>


<div class="container-fluid py-4">
  <div class="card p-3">
    <table class="table align-items-center">
      <thead>
        <tr>
          <th>Asset</th>
          <th>Admin Document</th>
          <th>Signed Document</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>

      <tbody>
        <?php foreach ($data as $d): ?>
        <tr>
          <!-- ASSET -->
          <td><?= htmlspecialchars($d['asset_name']) ?></td>

          <!-- ADMIN DOCUMENT -->
          <td>
            <?php if (!empty($d['admin_document_path'])): ?>
              <a href="<?= $d['admin_document_path'] ?>"
                 download
                 class="btn btn-sm btn-info">
                Download
              </a>
            <?php else: ?>
              <span class="text-muted">Not available</span>
            <?php endif; ?>
          </td>

          <!-- EMPLOYEE SIGNED DOCUMENT -->
          <td>
            <?php if (!empty($d['employee_document_path'])): ?>
              <a href="<?= $d['employee_document_path'] ?>"
                 download
                 class="btn btn-sm btn-success">
                Download
              </a>
            <?php else: ?>
              <span class="text-muted">Not uploaded</span>
            <?php endif; ?>
          </td>

          <!-- STATUS (ONLY FOR EMPLOYEE DOC) -->
          <td>
            <?php
              if (empty($d['employee_document_path'])) {
                  if ($d['status'] === 'admin_uploaded') {
                      echo '<span class="badge bg-info">Admin uploaded</span>';
                  } else {
                      echo '<span class="badge bg-secondary">Not uploaded</span>';
                  }
              } elseif ($d['status'] === 'uploaded') {
                  echo '<span class="badge bg-warning">Pending approval</span>';
              } elseif ($d['status'] === 'approved') {
                  echo '<span class="badge bg-success">Approved</span>';
              } elseif ($d['status'] === 'rejected') {
                  echo '<span class="badge bg-danger">Rejected</span>';
              }
            ?>
          </td>


          
          <!-- ACTION -->
          <td>
            <?php if ($d['assignment_status'] !== 'assigned'): ?>

            <?php if ($d['return_condition'] === 'damaged'): ?>
                <span class="badge bg-danger">Returned Damaged</span>

            <?php elseif ($d['return_condition'] === 'lost'): ?>
                <span class="badge bg-dark">Returned Lost</span>

            <?php else: ?>
                <span class="badge bg-secondary">Returned</span>
            <?php endif; ?>


            <?php elseif (empty($d['employee_document_path']) || $d['status'] === 'rejected'): ?>

              <button type="button"
                      class="btn btn-sm btn-primary"
                      onclick="openUploadSignedModal('<?= $d['assign_id'] ?>')">
                Upload Signed
              </button>

            <?php elseif ($d['status'] === 'uploaded'): ?>

              <button class="btn btn-sm btn-secondary" disabled>
                Waiting for approval
              </button>

            <?php elseif ($d['status'] === 'approved'): ?>

              <button class="btn btn-sm btn-success" disabled>
                Approved
              </button>

            <?php endif; ?>
          </td>

        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>




<div class="modal-custom" id="uploadSignedModal">
  <div class="modal-content small" style="position:relative;">

    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="mb-0">Upload Signed Document</h5>
      <button type="button"
              class="modal-close-btn"
              onclick="closeUploadSignedModal()">
        &times;
      </button>
    </div>

    <form method="POST" enctype="multipart/form-data">

      <input type="hidden" name="assign_id" id="signed_assign_id">

      <label class="form-label">Upload Signed PDF</label>
      <input type="file"
             name="signed_document"
             class="form-control mb-3"
             accept="application/pdf"
             required>

      <div class="text-end">
        <button type="button"
                class="btn btn-secondary me-2"
                onclick="closeUploadSignedModal()">
          Cancel
        </button>

        <button type="submit"
                name="upload_signed_document"
                class="btn bg-gradient-primary">
          Upload
        </button>
      </div>

    </form>

  </div>
</div>


<style>
.modal-custom {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.45);
    z-index: 9999; /* VERY IMPORTANT */
    align-items: center;
    justify-content: center;
}

.modal-custom .modal-content {
    background: #fff;
    padding: 24px;
    border-radius: 12px;
    width: 420px;
    max-width: 95%;
    box-shadow: 0 10px 40px rgba(0,0,0,0.25);
}

.modal-close-btn {
    position: absolute;
    top: 10px;
    right: 12px;
    background: transparent;
    border: none;
    font-size: 26px;
    font-weight: bold;
    cursor: pointer;
}
</style>


<script>
function openUploadSignedModal(assignId) {
    document.getElementById('signed_assign_id').value = assignId;
    document.getElementById('uploadSignedModal').style.display = 'flex';
}

function closeUploadSignedModal() {
    document.getElementById('uploadSignedModal').style.display = 'none';
}
</script>

<script>
  setTimeout(function () {
    const alertBox = document.getElementById('successAlert');
    if (alertBox) {
      alertBox.style.transition = 'opacity 0.5s ease';
      alertBox.style.opacity = '0';

      setTimeout(() => {
        alertBox.remove();
      }, 500);
    }
  }, 5000); // 5 seconds
</script>


<?php include("footer.php"); ?>
