<?php
include("header.php"); // Include header file
if (!isset($_SESSION['employee_id'])) {
    echo "<div class='alert alert-danger'>You must be logged in to edit your profile.</div>";
    exit;
}
$employee_id = $_SESSION['employee_id']; // Get employee ID from session
// Fetch employee details
$stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->bind_param("s", $employee_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows !== 1) {
    echo "<div class='alert alert-danger'>Employee not found!</div>";
    exit;
}
$employee = $result->fetch_assoc(); // Fetch employee data
// Handle form submission for updating employee details
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $dob = trim($_POST['dob']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    $anniversary = trim($_POST['anniversary']);
    $father_name = trim($_POST['father_name']);
    $bank_account = trim($_POST['bank_account']);
    $ifsc_code = trim($_POST['ifsc_code']);
    $adhar_number = trim($_POST['adhar_number']);
    $pan_number = trim($_POST['pan_number']);
    $epf_number = trim($_POST['epf_number']);
    $esic = trim($_POST['esic']);
    $emergency_contact = trim($_POST['emergency_contact']);

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $photo_path = $employee['photo']; // Default to current photo
    // Validate inputs
    if (empty($name) || empty($email) || empty($phone) || empty($address) || empty($username)) {
        echo "<div class='alert alert-danger'>All fields except password are required!</div>";
    } else {
        // Handle file upload for photo (if a new photo is uploaded)
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $target_dir = "../uploads/profile_photo/";
            $photo_name = basename($_FILES['photo']['name']);
            $target_file = $target_dir . $photo_name;

            // Check if upload is successful
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
                $photo_path = $target_file; // Update photo path
            } else {
                echo "<div class='alert alert-warning'>Failed to upload new photo. Keeping existing photo.</div>";
            }
        }
        // Hash password if provided
        if (!empty($password)) {
            $password = password_hash($password, PASSWORD_DEFAULT);
        } else {
            $password = $employee['password']; // Keep the current password if not changed
        }
        // Update employee details in the database
        $stmt = $conn->prepare("UPDATE employees SET name = ?, dob = ?, email = ?, phone = ?, address = ?, anniversary = ?, father_name = ?, bank_account = ?, ifsc_code = ?, adhar_number = ?, pan_number = ?, epf_number = ?, esic = ?, emergency_contact = ?, username = ?, password = ?, photo = ? WHERE id = ?");
        $stmt->bind_param("ssssssssssssssssss", $name, $dob, $email, $phone, $address, $anniversary, $father_name, $bank_account, $ifsc_code, $adhar_number, $pan_number, $epf_number, $esic, $emergency_contact, $username, $password, $photo_path, $employee_id);

        if ($stmt->execute()) {
            echo "<div class='alert alert-success'>Profile updated successfully.</div>";
            // Refresh employee data
            $stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
            $stmt->bind_param("s", $employee_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $employee = $result->fetch_assoc();
        } else {
            echo "<div class='alert alert-danger'>Failed to update profile.</div>";
        }
    }
}
?>
<div class="container-fluid py-4">
    <div class="col-md-12 mb-lg-0 mb-4">
        <div class="card mt-4">
            <div class="card-header pb-0 p-3">
                <h6 class="mb-0">Edit Your Profile</h6>
            </div>
            <div class="card-body p-2">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mt-4">
                            <label for="name" class="form-label">Name</label>
                            <input class="form-control" type="text" name="name" id="name" value="<?= htmlspecialchars($employee['name']) ?>"  required>
                        </div>
                        <div class="col-md-6 mt-4">
                            <label for="dob" class="form-label">Date Of Birth</label>
                            <input class="form-control" type="date" name="dob" id="dob" value="<?= htmlspecialchars($employee['dob']) ?>"  >
                        </div>
                        <div class="col-md-6 mt-4">
                            <label for="email" class="form-label">Email</label>
                            <input class="form-control" type="email" name="email" id="email" value="<?= htmlspecialchars($employee['email']) ?>"  required>
                        </div>
                        <div class="col-md-6 mt-4">
                            <label for="phone" class="form-label">Phone</label>
                            <input class="form-control" type="text" name="phone" id="phone" value="<?= htmlspecialchars($employee['phone']) ?>"  required>
                        </div>
                        <div class="col-md-6 mt-4">
                            <label for="address" class="form-label">Address</label>
                            <textarea  class="form-control" name="address" id="address" rows="3" required><?= htmlspecialchars($employee['address']) ?></textarea>
                        </div>

                        <div class="col-md-6 mt-4">
                            <label for="anniversary" class="form-label">anniversary</label>
                            <input  class="form-control" name="anniversary" id="anniversary"  type="date" value="<?= htmlspecialchars($employee['anniversary']) ?>">
                        </div>
                        <div class="col-md-6 mt-4">
                            <label for="father_name" class="form-label">father_name</label>
                            <input  class="form-control" name="father_name" id="father_name" type="text" value="<?= htmlspecialchars($employee['father_name']) ?>">
                        </div>
                        <div class="col-md-6 mt-4">
                            <label for="bank_account" class="form-label">bank_account</label>
                            <input  class="form-control" name="bank_account" id="bank_account" type="text" value="<?= htmlspecialchars($employee['bank_account']) ?>">
                        </div>
                        <div class="col-md-6 mt-4">
                            <label for="ifsc_code" class="form-label">ifsc_code</label>
                            <input  class="form-control" name="ifsc_code" id="ifsc_code" type="text" value="<?= htmlspecialchars($employee['ifsc_code']) ?>">
                        </div>
                        <div class="col-md-6 mt-4">
                            <label for="adhar_number" class="form-label">adhar_number</label>
                            <input  class="form-control" name="adhar_number" id="adhar_number" type="text" value="<?= htmlspecialchars($employee['adhar_number']) ?>" >
                        </div>
                        <div class="col-md-6 mt-4">
                            <label for="pan_number" class="form-label">pan_number</label>
                            <input  class="form-control" name="pan_number" id="pan_number" type="text" value="<?= htmlspecialchars($employee['pan_number']) ?>">
                        </div>
                        <div class="col-md-6 mt-4">
                            <label for="epf_number" class="form-label">epf_number</label>
                            <input  class="form-control" name="epf_number" id="epf_number" type="text" value="<?= htmlspecialchars($employee['epf_number']) ?>">
                        </div>
                        <div class="col-md-6 mt-4">
                            <label for="esic" class="form-label">esic</label>
                            <input  class="form-control" name="esic" id="esic" type="text" value="<?= htmlspecialchars($employee['esic']) ?>">
                        </div>
                        <div class="col-md-6 mt-4">
                            <label for="emergency_contact" class="form-label">emergency_contact</label>
                            <input  class="form-control" name="emergency_contact" id="emergency_contact" type="text" value="<?= htmlspecialchars($employee['emergency_contact']) ?>" >
                        </div>


                        <div class="col-md-6 mt-4">
                            <label for="username" class="form-label">Username/Emp ID</label>
                            <input class="form-control" type="text" name="username" id="username" value="<?= htmlspecialchars($employee['employee_id']) ?>" readonly  required>
                        </div>
                        <div class="col-md-6 mt-4">
                            <label for="password" class="form-label">Password</label>
                            <input class="form-control" type="password" name="password" id="password"  placeholder="Leave blank to keep the current password">
                        </div>
                        <div class="col-md-6 mt-4">
                            <label for="photo" class="form-label">Photo</label>
                            <input class="form-control" type="file" name="photo" id="photo" accept="image/*">
                            <?php if (!empty($employee['photo'])): ?>
                                <img src="<?= htmlspecialchars($employee['photo']) ?>" alt="Profile Photo" class="img-thumbnail mt-2" style="max-width: 100px;">
                            <?php endif; ?>
                        </div>
                        <div class="col-md-12 mt-4">
                            <button class="btn bg-gradient-dark mb-0" type="submit">Update Profile</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include("footer.php"); ?>

