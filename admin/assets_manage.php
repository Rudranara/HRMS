<?php

include("header.php");

if (isset($_SESSION['success'])) {
    echo "<div id='successAlert' class='alert alert-success'>
            {$_SESSION['success']}
          </div>";
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    echo "<div id='successAlert' class='alert alert-danger'>
            {$_SESSION['error']}
          </div>";
    unset($_SESSION['error']);
}

if (isset($_POST['delete_asset_id'])) {
     $asset_id = intval($_POST['delete_asset_id']);

    try {
        $stmt = $conn->prepare("DELETE FROM assets WHERE id = ?");
        $stmt->bind_param("i", $asset_id);
        $stmt->execute();

        $_SESSION['success'] = "Asset deleted successfully";
        header("Location: assets_manage");
        exit;

    } catch (mysqli_sql_exception $e) {
        // Foreign key constraint (asset already assigned)
        if ($e->getCode() == 1451) {
            $_SESSION['error'] = "Cannot delete asset. It is already assigned.";
            header("Location: assets_manage");
            exit;

        } else {
            $_SESSION['error'] = "Something went wrong. Please try again.";
            header("Location: assets_manage");
            exit;
        }
    }
}



if (isset($_POST['upload_document'])) {

    $assignment_id = intval($_POST['assignment_id']);

    
    $uploadDir = "../uploads/asset_docs/";

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileName = time() . '_' . basename($_FILES['document']['name']);
        $filePath = $uploadDir . $fileName;

        $fileType = mime_content_type($_FILES['document']['tmp_name']);

        if ($fileType !== 'application/pdf') {
            $_SESSION['error'] = "Only PDF allowed.";
            header("Location: assets_manage");
            exit;
        }

        if ($_FILES['document']['size'] > 5 * 1024 * 1024) {
            $_SESSION['error'] = "File too large. Max 5MB allowed.";
            header("Location: assets_manage");
            exit;
        }

        if (!move_uploaded_file($_FILES['document']['tmp_name'], $filePath)) {
            $_SESSION['error'] = "Upload failed.";
            header("Location: assets_manage");
            exit;
        }


    

    $stmt = $conn->prepare("
        INSERT INTO asset_documents 
            (asset_assignment_id, admin_document_path, status, uploaded_at)
        VALUES (?, ?, 'admin_uploaded', NOW())
        ON DUPLICATE KEY UPDATE
            admin_document_path = VALUES(admin_document_path),
            status = 'admin_uploaded',
            uploaded_at = NOW()
    ");
    $stmt->bind_param("is", $assignment_id, $filePath);
    $stmt->execute();


    $_SESSION['success'] = "Asset document uploaded successfully";
    header("Location: assets_manage");
    exit;
}



if (isset($_POST['add_asset'])) {

    $asset_name = trim($_POST['asset_name']);
    $asset_type = trim($_POST['asset_type']);
    $asset_code = trim($_POST['asset_code']);


    // 🔐 CHECK DUPLICATE ASSET CODE
    $check = $conn->prepare("SELECT id FROM assets WHERE asset_code = ?");
    $check->bind_param("s", $asset_code);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $_SESSION['error'] = "Asset code already exists. Please use a unique code.";
        header("Location: assets_manage");
        exit;
    }

    // Upload images
    $uploadDir = "uploads/asset_images/";
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $imagePaths = [];

    if (!empty($_FILES['asset_images']['name'][0])) {
        foreach ($_FILES['asset_images']['tmp_name'] as $key => $tmp) {
            $fileName = time() . '_' . $_FILES['asset_images']['name'][$key];
            $path = $uploadDir . $fileName;
            move_uploaded_file($tmp, $path);
            $imagePaths[] = $path;
        }
    }

    $images = implode(',', $imagePaths);

    // INSERT
    $stmt = $conn->prepare("
        INSERT INTO assets (asset_name, asset_type, asset_code, asset_images, status)
        VALUES (?, ?, ?, ?, 'available')
    ");
    $stmt->bind_param("ssss", $asset_name, $asset_type, $asset_code, $images);
    $stmt->execute();

  
    $_SESSION['success'] = "Asset added successfully";
    header("Location: assets_manage");
    exit;
}



if (isset($_POST['update_asset'])) {

    $id = intval($_POST['edit_asset_id']);
    $asset_name = trim($_POST['asset_name']);
    $asset_type = trim($_POST['asset_type']);
    $asset_code = trim($_POST['asset_code']);

    /* ===============================
       1️⃣ CHECK DUPLICATE ASSET CODE
       (Allow same code for same ID)
    =============================== */
    $check = $conn->prepare("
        SELECT id FROM assets
        WHERE asset_code = ? AND id != ?
    ");
    $check->bind_param("si", $asset_code, $id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $_SESSION['error'] = "Asset code already exists. Please use another one.";
        header("Location: assets_manage");
        exit;
    }

    /* ===============================
       2️⃣ FETCH OLD IMAGES
    =============================== */
    $old = $conn->query("
        SELECT asset_images FROM assets WHERE id = $id
    ")->fetch_assoc();

    $oldImages = $old['asset_images']
        ? explode(',', $old['asset_images'])
        : [];

    /* ===============================
       3️⃣ REMAINING IMAGES (FROM UI)
    =============================== */
    $existingImages = $_POST['existing_images'] ?? '';
    $remainingImages = $existingImages
        ? explode(',', $existingImages)
        : [];

    /* ===============================
       4️⃣ DELETE REMOVED FILES
    =============================== */
    $deletedImages = array_diff($oldImages, $remainingImages);
    foreach ($deletedImages as $img) {
        if ($img && file_exists($img)) {
            unlink($img);
        }
    }

    /* ===============================
       5️⃣ UPLOAD NEW IMAGES
    =============================== */
    $uploadDir = "uploads/asset_images/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $newImages = [];

    if (!empty($_FILES['asset_images']['name'][0])) {
        foreach ($_FILES['asset_images']['tmp_name'] as $key => $tmp) {

            if (!$tmp) continue;

            $fileName = time() . '_' . $_FILES['asset_images']['name'][$key];
            $path = $uploadDir . $fileName;

            move_uploaded_file($tmp, $path);
            $newImages[] = $path;
        }
    }

    /* ===============================
       6️⃣ FINAL IMAGE LIST
    =============================== */
    $finalImagesArray = array_merge($remainingImages, $newImages);
    $finalImages = implode(',', $finalImagesArray);

    /* ===============================
       7️⃣ UPDATE ASSET
    =============================== */
    $stmt = $conn->prepare("
        UPDATE assets
        SET asset_name = ?,
            asset_type = ?,
            asset_code = ?,
            asset_images = ?
        WHERE id = ?
    ");
    $stmt->bind_param(
        "ssssi",
        $asset_name,
        $asset_type,
        $asset_code,
        $finalImages,
        $id
    );
    $stmt->execute();

    $_SESSION['success'] = "Asset updated successfully";
      header("Location: assets_manage");
      exit;
}



if (isset($_POST['return_asset'])) {

    $assignment_id = intval($_POST['assignment_id']);
    $condition = $_POST['return_condition'];
    $remarks = trim($_POST['return_remarks']);

    $conn->begin_transaction();

    try {

        $assignmentStatus = 'returned';


        // Update assignment
        $stmt = $conn->prepare("
            UPDATE asset_assignments
            SET status = ?, 
                returned_at = NOW(),
                return_condition = ?,
                return_remarks = ?
            WHERE id = ?
        ");
        $stmt->bind_param("sssi",
            $assignmentStatus,
            $condition,
            $remarks,
            $assignment_id
        );
        $stmt->execute();

        // Get asset + employee
        $assignment = $conn->query("
            SELECT asset_id, employee_id 
            FROM asset_assignments 
            WHERE id = $assignment_id
        ")->fetch_assoc();

        $asset_id = $assignment['asset_id'];
        $employee_id = $assignment['employee_id'];

        if ($condition === 'good') {
            $newStatus = 'available';
        } elseif ($condition === 'damaged') {
            $newStatus = 'damaged';
        } elseif ($condition === 'lost') {
            $newStatus = 'lost';
        } else {
            $newStatus = 'available';
        }


        // Update asset
        $stmt2 = $conn->prepare("
            UPDATE assets SET status = ? WHERE id = ?
        ");
        $stmt2->bind_param("si", $newStatus, $asset_id);
        $stmt2->execute();

        // Insert history
        $stmt3 = $conn->prepare("
            INSERT INTO asset_history
            (asset_id, assignment_id, action_type, remarks)
            VALUES (?, ?, ?, ?)
        ");
        $stmt3->bind_param("iiss",
            $asset_id,
            $assignment_id,
            $condition,
            $remarks
        );
        $stmt3->execute();

        
        // Recovery if needed
        if ($condition === 'damaged' || $condition === 'lost') {

            $checkRecovery = $conn->prepare("
                SELECT id FROM asset_recovery 
                WHERE assignment_id = ? AND recovery_status = 'pending'
            ");
            $checkRecovery->bind_param("i", $assignment_id);
            $checkRecovery->execute();
            $checkRecovery->store_result();

            if ($checkRecovery->num_rows == 0) {

                $stmt4 = $conn->prepare("
                    INSERT INTO asset_recovery
                    (asset_id, assignment_id, employee_id, recovery_type, recovery_amount, recovery_status, created_at)
                    VALUES (?, ?, ?, ?, 0, 'pending', NOW())
                ");

                $stmt4->bind_param(
                    "iiis",
                    $asset_id,
                    $assignment_id,
                    $employee_id,
                    $condition   // ← THIS WAS MISSING
                );

                $stmt4->execute();
            }
        }


        $conn->commit();

        $_SESSION['success'] = "Asset processed successfully";

    } catch (Exception $e) {

        $conn->rollback();
        $_SESSION['error'] = $e->getMessage(); // show real error
    }


    header("Location: assets_manage");
    exit;
}






if (isset($_POST['assign_asset'])) {

    $asset_id = intval($_POST['assign_asset_id']);
    $employee_id = intval($_POST['employee_id']);

    $checkAsset = $conn->prepare("
        SELECT status FROM assets WHERE id = ?
    ");
    $checkAsset->bind_param("i", $asset_id);
    $checkAsset->execute();
    $result = $checkAsset->get_result()->fetch_assoc();

    if (!$result || $result['status'] !== 'available') {
        $_SESSION['error'] = "Asset not available.";
        header("Location: assets_manage");
        exit;
    }

    // Insert assignment
    $stmt = $conn->prepare("
        INSERT INTO asset_assignments (asset_id, employee_id, assigned_date, status)
        VALUES (?, ?, CURDATE(), 'assigned')
    ");
    $stmt->bind_param("ii", $asset_id, $employee_id);
    $stmt->execute();

    // Update asset status
    $stmt2 = $conn->prepare("
        UPDATE assets SET status='assigned' WHERE id=?
    ");
    $stmt2->bind_param("i", $asset_id);
    $stmt2->execute();

    $_SESSION['success'] = "Asset assigned successfully";
    header("Location: assets_manage");
    exit;
}

$assets = $conn->query("
  SELECT 
    a.*,
    aa.id AS assignment_id,
    ad.admin_document_path
FROM assets a
LEFT JOIN asset_assignments aa ON aa.asset_id = a.id AND aa.status = 'assigned'
LEFT JOIN asset_documents ad ON ad.asset_assignment_id = aa.id
ORDER BY a.id DESC
")->fetch_all(MYSQLI_ASSOC);

?>


<div class="container-fluid py-4">
  <div class="row">
    <div class="col-12 text-end mb-3">

      <button type="button"
              class="btn bg-gradient-info btn-sm me-2"
              onclick="openAssetDocModal()">
        Upload Documents
      </button>


      <button type="button"
              class="btn bg-gradient-dark btn-sm"
              onclick="openAddAssetModal()">
        Add Asset
      </button>


    </div>


    <div class="col-12">
      <div class="card">
        <div class="table-responsive p-3">

          <table class="table align-items-center mb-0">

            <thead>
              <tr>
                <th>Asset</th>
                <th>Code</th>
                <th>Type</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($assets as $a): ?>
              <tr>
                <td><?= $a['asset_name'] ?></td>
                <td><?= $a['asset_code'] ?></td>
                <td><?= $a['asset_type'] ?></td>
                <td>
                  
                  <?php
                    $statusColor = match($a['status']) {
                        'available' => 'success',
                        'assigned'  => 'warning',
                        'damaged'   => 'danger',
                        'lost'      => 'dark',
                        'retired'   => 'secondary',
                        default     => 'secondary'
                    };

                    ?>

                    <span class="badge bg-gradient-<?= $statusColor ?>">
                        <?php
                            $statusText = match($a['status']) {
                                'available' => 'Available',
                                'assigned'  => 'Assigned',
                                'damaged'   => 'Damaged',
                                'lost'      => 'Lost',
                                'retired'   => 'Retired',
                                default     => ucfirst($a['status'])
                            };
                            echo $statusText;
                            ?>

                    </span>

                </td>
                <td>
                  <!-- View Images -->
                  <?php if (!empty($a['asset_images'])): ?>
                    <button type="button"
                            class="btn btn-sm btn-info me-1"
                            onclick="viewAssetImages('<?= htmlspecialchars($a['asset_images'], ENT_QUOTES) ?>')">
                      <i class="bi bi-eye-fill"></i>
                    </button>
                  <?php endif; ?>

                  <?php if (!empty($a['admin_document_path'])): ?>
                      <a href="<?= $a['admin_document_path'] ?>"
                         target="_blank"
                         class="btn btn-sm btn-primary me-1">
                         <i class="bi bi-file-earmark-pdf"></i>
                      </a>
                    <?php endif; ?>


                  <!-- Edit Asset -->
                  <button type="button"
                      class="btn btn-sm btn-warning me-1"
                      onclick="openEditAssetModal(
                        '<?= $a['id'] ?>',
                        '<?= htmlspecialchars($a['asset_name'], ENT_QUOTES) ?>',
                        '<?= htmlspecialchars($a['asset_type'], ENT_QUOTES) ?>',
                        '<?= htmlspecialchars($a['asset_code'], ENT_QUOTES) ?>',
                        '<?= htmlspecialchars($a['asset_images'], ENT_QUOTES) ?>'
                      )">
                      <i class="bi bi-pencil-square"></i>
                    </button>


                  <?php if ($a['status'] === 'available'): ?>

                    <button type="button"
                          class="btn btn-sm bg-gradient-primary"
                          onclick="openAssignAssetModal('<?= $a['id'] ?>')">
                     Assign
                  </button>


                    <form method="POST" class="d-inline"
                          onsubmit="return confirm('Are you sure you want to delete this asset?');">
                        <input type="hidden" name="delete_asset_id" value="<?= $a['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">
                            <i class="bi bi-trash-fill"></i>
                        </button>
                    </form>


                  <?php elseif ($a['status'] === 'assigned'): ?>

                      <span class="badge bg-gradient-warning me-2">Assigned</span>

                      <button class="btn btn-sm btn-danger me-1"
                              onclick="openReturnModal('<?= $a['assignment_id'] ?>')">
                        <i class="bi bi-arrow-return-left"></i> Return
                      </button>

                    <?php endif; ?>


                </td>


              </tr>
              <?php endforeach ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal-custom" id="assetDocModal">
  <div class="modal-content small" style="position: relative;">

    <!-- Header with cross -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="mb-0">Upload Asset Document</h5>

      <button type="button"
              class="modal-close-btn"
              onclick="closeAssetDocModal()">
        &times;
      </button>
    </div>

    <form method="POST" enctype="multipart/form-data">

      <label class="form-label">Select Assigned Asset</label>
      <select name="assignment_id" class="form-control mb-3" required>
        <?php
        $assignedAssets = $conn->query("
          SELECT aa.id, a.asset_name, e.name
          FROM asset_assignments aa
          JOIN assets a ON a.id = aa.asset_id
          JOIN employees e ON e.id = aa.employee_id
          WHERE aa.status = 'assigned'
        ");

        while ($row = $assignedAssets->fetch_assoc()):
        ?>
          <option value="<?= $row['id'] ?>">
            <?= $row['asset_name'] ?> – <?= $row['name'] ?>
          </option>
        <?php endwhile; ?>
      </select>

      <label class="form-label">Upload Document (PDF)</label>
      <input type="file" name="document" class="form-control mb-3" required>

      <div class="text-end">
        <button type="button"
                class="btn btn-secondary me-2"
                onclick="closeAssetDocModal()">
          Cancel
        </button>

        <button type="submit"
                name="upload_document"
                class="btn bg-gradient-primary">
          Upload
        </button>
      </div>

    </form>

  </div>
</div>


<div class="modal-custom" id="addAssetModal">
  <div class="modal-content small">

    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="mb-0">Add Asset</h5>

      <button type="button"
              class="modal-close-btn"
              onclick="closeAddAssetModal()">
        &times;
      </button>
    </div>

    <form method="POST" enctype="multipart/form-data">

      <!-- Asset Name -->
      <label class="form-label">Asset Name</label>
      <input type="text"
             name="asset_name"
             class="form-control mb-3"
             placeholder="e.g. Dell Latitude Laptop"
             required>

      <!-- Asset Type -->
      <label class="form-label">Asset Type</label>
      <input type="text"
             name="asset_type"
             class="form-control mb-3"
             placeholder="e.g. Laptop / Mobile / ID Card"
             required>

      <!-- Asset Code -->
      <label class="form-label">Asset Code</label>
      <input type="text"
             name="asset_code"
             class="form-control mb-3"
             placeholder="e.g. LAP-DEL-001"
             pattern="[A-Za-z0-9\-]+"
             title="Only letters, numbers and hyphen allowed"
             required>

      <!-- Asset Images -->
      <label class="form-label">
        Asset Images
        <small class="text-muted">(you can select multiple)</small>
      </label>
      <input type="file"
             id="addAssetImages"
             name="asset_images[]"
             class="form-control mb-2"
             multiple
             accept="image/*"
             onchange="previewImages(this, 'addAssetPreview')">
             
      <div id="addAssetPreview" class="d-flex flex-wrap gap-2 mb-3"></div>


      <!-- Buttons -->
      <div class="text-end">
        <button type="button"
                class="btn btn-secondary me-2"
                onclick="closeAddAssetModal()">
          Cancel
        </button>

        <button type="submit"
                name="add_asset"
                class="btn bg-gradient-primary">
          Save Asset
        </button>
      </div>

    </form>

  </div>
</div>


<div class="modal-custom" id="editAssetModal">
  <div class="modal-content small">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="mb-0">Edit Asset</h5>
      <button type="button"
              class="modal-close-btn"
              onclick="closeEditAssetModal()">&times;</button>
    </div>

    <form method="POST" enctype="multipart/form-data">

      <!-- Required hidden fields -->
      <input type="hidden" name="edit_asset_id" id="edit_asset_id">
      <input type="hidden" name="existing_images" id="existing_images">

      <!-- Asset Name -->
      <label class="form-label">Asset Name</label>
      <input type="text"
             name="asset_name"
             id="edit_asset_name"
             class="form-control mb-3"
             required>

      <!-- Asset Type -->
      <label class="form-label">Asset Type</label>
      <input type="text"
             name="asset_type"
             id="edit_asset_type"
             class="form-control mb-3"
             required>

      <!-- Asset Code -->
      <label class="form-label">Asset Code</label>
      <input type="text"
             name="asset_code"
             id="edit_asset_code"
             class="form-control mb-3"
             required>

      <!-- Existing Images -->
      <label class="form-label">Existing Images</label>
      <div id="existingImagesPreview"
           class="d-flex flex-wrap gap-2 mb-3">
        <!-- Old images with ❌ injected by JS -->
      </div>

      <!-- New Images -->
      <label class="form-label">
        Add More Images
        <small class="text-muted">(optional)</small>
      </label>
      <input type="file"
             id="editAssetImages"
             name="asset_images[]"
             class="form-control mb-2"
             multiple
             accept="image/*"
             onchange="previewImages(this, 'editAssetPreview')">

      <!-- New image preview -->
      <div id="editAssetPreview"
           class="d-flex flex-wrap gap-2 mb-3"></div>

      <!-- Buttons -->
      <div class="text-end">
        <button type="button"
                class="btn btn-secondary me-2"
                onclick="closeEditAssetModal()">
          Cancel
        </button>

        <button type="submit"
                name="update_asset"
                class="btn bg-gradient-primary">
          Update Asset
        </button>
      </div>

    </form>

  </div>
</div>



<div class="modal-custom" id="assetImageModal">
  <div class="modal-content" style="width:600px;max-width:95%; position: relative;">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="mb-0">Asset Images</h5>

      <!-- Cross button -->
      <button type="button"
              class="modal-close-btn"
              onclick="closeAssetImageModal()">
        &times;
      </button>
    </div>

    <div id="assetImageContainer"
         class="d-flex flex-wrap gap-2 justify-content-start">
      <!-- Images injected here -->
    </div>

  </div>
</div>

<div class="modal-custom" id="assignAssetModal">
  <div class="modal-content small" style="position: relative;">

    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="mb-0">Assign Asset</h5>
      <button type="button"
              class="modal-close-btn"
              onclick="closeAssignAssetModal()">&times;</button>
    </div>

    <form method="POST">

      <input type="hidden" name="assign_asset_id" id="assign_asset_id">

      <label class="form-label">Select Employee</label>
      <select name="employee_id" class="form-control mb-3" required>
        <option value="">-- Select Employee --</option>
        <?php
        $emps = $conn->query("
          SELECT id, name
          FROM employees
          WHERE status='Active'
          ORDER BY name
        ");
        while ($e = $emps->fetch_assoc()):
        ?>
          <option value="<?= $e['id'] ?>">
            <?= $e['name'] ?>
          </option>
        <?php endwhile; ?>
      </select>

      <div class="text-end">
        <button type="button"
                class="btn btn-secondary me-2"
                onclick="closeAssignAssetModal()">Cancel</button>

        <button type="submit"
                name="assign_asset"
                class="btn bg-gradient-primary">
          Assign Asset
        </button>
      </div>

    </form>

  </div>
</div>


<div class="modal-custom" id="returnAssetModal">
  <div class="modal-content small">

    <h5>Return Asset</h5>

    <form method="POST">
      <input type="hidden" name="assignment_id" id="return_assignment_id">

      <label>Return Condition</label>
      <select name="return_condition" class="form-control mb-2" required>
        <option value="good">Good</option>
        <option value="damaged">Damaged</option>
        <option value="lost">Lost</option>
      </select>

      <label>Remarks</label>
      <textarea name="return_remarks" class="form-control mb-3"></textarea>

      <button type="submit"
              name="return_asset"
              class="btn btn-danger">
        Confirm Return
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

.modal-custom .modal-content {
    background: #fff;
    padding: 22px;
    border-radius: 16px;
    width: 420px;
    max-width: 95%;
}




.modal-close-btn {
    position: absolute;
    top: 12px;
    right: 14px;
    background: transparent;
    border: none;
    font-size: 26px;
    font-weight: bold;
    color: #374151; /* dark gray */
    cursor: pointer;
    line-height: 1;
}

.modal-close-btn:hover {
    color: #ef4444; /* red on hover */
}

</style>



<script>
  setTimeout(function () {
    const alertBox = document.getElementById('successAlert');
    if (alertBox) {
      alertBox.style.transition = 'opacity 0.5s ease';
      alertBox.style.opacity = '0';
      setTimeout(() => alertBox.remove(), 500);
    }
  }, 6000);
</script>

<script>
function openAssetDocModal() {
    document.getElementById('assetDocModal').style.display = 'flex';
}

function closeAssetDocModal() {
    document.getElementById('assetDocModal').style.display = 'none';
}
</script>
<script>
function openAddAssetModal() {
    document.getElementById('addAssetModal').style.display = 'flex';
}

function closeAddAssetModal() {
    document.getElementById('addAssetModal').style.display = 'none';
}
</script>

<script>
function viewAssetImages(images) {

    const container = document.getElementById('assetImageContainer');
    container.innerHTML = '';

    const imageArray = images.split(',');

    imageArray.forEach(src => {
        const img = document.createElement('img');
        img.src = src.trim();
        img.style.width = '150px';
        img.style.height = '150px';
        img.style.objectFit = 'cover';
        img.style.borderRadius = '10px';
        img.style.border = '1px solid #e5e7eb';
        container.appendChild(img);
    });

    document.getElementById('assetImageModal').style.display = 'flex';
}

function closeAssetImageModal() {
    document.getElementById('assetImageModal').style.display = 'none';
}
</script>

<script>
let existingImageList = [];

function openEditAssetModal(id, name, type, code, images) {

    document.getElementById('edit_asset_id').value = id;
    document.getElementById('edit_asset_name').value = name;
    document.getElementById('edit_asset_type').value = type;
    document.getElementById('edit_asset_code').value = code;

    // Prepare existing images
    existingImageList = images ? images.split(',') : [];
    document.getElementById('existing_images').value =
        existingImageList.join(',');

    renderExistingImages();

    // Reset new image preview & input
    document.getElementById('editAssetPreview').innerHTML = '';
    document.getElementById('editAssetImages').value = '';

    document.getElementById('editAssetModal').style.display = 'flex';
}

function renderExistingImages() {

    const container = document.getElementById('existingImagesPreview');
    container.innerHTML = '';

    existingImageList.forEach((src, index) => {

        const box = document.createElement('div');
        box.style.position = 'relative';
        box.style.width = '100px';

        const img = document.createElement('img');
        img.src = src.trim();
        img.style.width = '100px';
        img.style.height = '100px';
        img.style.objectFit = 'cover';
        img.style.borderRadius = '8px';
        img.style.border = '1px solid #e5e7eb';

        const remove = document.createElement('button');
        remove.innerHTML = '&times;';
        remove.type = 'button';
        remove.style.position = 'absolute';
        remove.style.top = '2px';
        remove.style.right = '2px';
        remove.style.width = '22px';
        remove.style.height = '22px';
        remove.style.borderRadius = '50%';
        remove.style.border = 'none';
        remove.style.background = '#ef4444';
        remove.style.color = '#fff';
        remove.style.cursor = 'pointer';

        remove.onclick = function () {
            existingImageList.splice(index, 1);
            document.getElementById('existing_images').value =
                existingImageList.join(',');
            renderExistingImages();
        };

        box.appendChild(img);
        box.appendChild(remove);
        container.appendChild(box);
    });
}

function closeEditAssetModal() {
    document.getElementById('editAssetModal').style.display = 'none';
}
</script>

<script>
function previewImages(input, previewId) {

    const preview = document.getElementById(previewId);
    preview.innerHTML = '';

    let files = Array.from(input.files);
    const dt = new DataTransfer();

    files.forEach((file, index) => {

        const reader = new FileReader();
        reader.onload = function (e) {

            const box = document.createElement('div');
            box.style.position = 'relative';
            box.style.width = '100px';

            const img = document.createElement('img');
            img.src = e.target.result;
            img.style.width = '100px';
            img.style.height = '100px';
            img.style.objectFit = 'cover';
            img.style.borderRadius = '8px';
            img.style.border = '1px solid #e5e7eb';

            const remove = document.createElement('button');
            remove.innerHTML = '&times;';
            remove.type = 'button';
            remove.style.position = 'absolute';
            remove.style.top = '2px';
            remove.style.right = '2px';
            remove.style.width = '22px';
            remove.style.height = '22px';
            remove.style.borderRadius = '50%';
            remove.style.border = 'none';
            remove.style.background = '#ef4444';
            remove.style.color = '#fff';
            remove.style.fontSize = '14px';
            remove.style.cursor = 'pointer';

            remove.onclick = function () {
                files.splice(index, 1);
                updateInputFiles(input, files);
                previewImages(input, previewId);
            };

            box.appendChild(img);
            box.appendChild(remove);
            preview.appendChild(box);
        };

        reader.readAsDataURL(file);
        dt.items.add(file);
    });

    input.files = dt.files;
}

function updateInputFiles(input, files) {
    const dt = new DataTransfer();
    files.forEach(file => dt.items.add(file));
    input.files = dt.files;
}
</script>
<script>
function openAssignAssetModal(assetId) {
    document.getElementById('assign_asset_id').value = assetId;
    document.getElementById('assignAssetModal').style.display = 'flex';
}

function closeAssignAssetModal() {
    document.getElementById('assignAssetModal').style.display = 'none';
}
</script>

<script>
   function openReturnModal(assignmentId) {
  document.getElementById('return_assignment_id').value = assignmentId;
  document.getElementById('returnAssetModal').style.display = 'flex';
} 
</script>

<?php include("footer.php"); ?>
