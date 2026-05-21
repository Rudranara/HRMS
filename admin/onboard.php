<?php
include("header.php");
if (!isset($_GET['employee_id'])) {
    echo "<div class='alert alert-danger'>Employee ID is missing!</div>";
    exit;
}
$employee_id = $_GET['employee_id'];
// Fetch employee details
$stmt = $conn->prepare("SELECT * FROM employees WHERE employee_id = ?");
$stmt->bind_param("s", $employee_id);
$stmt->execute();
$result = $stmt->get_result();
$employee = $result->fetch_assoc();
if (!$employee) {
    echo "<div class='alert alert-danger'>Employee not found!</div>";
    exit;
}
// Handle form submission for updating employee details
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $username = $_POST['username'];
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_BCRYPT) : null;
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $esic = $_POST['esic'];
    $address = $_POST['address'];
    $designation = $_POST['designation'];
    $role = $_POST['role'];
    $manager = $_POST['manager'];
    $salary_type = $_POST['salary_type'];
    $dob = $_POST['dob'];
    $anniversary = $_POST['anniversary'];
    $office = $_POST['office'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $punchin_time = $_POST['punchin_time'];
    $punchout_time = $_POST['punchout_time'];
    $break_time = $_POST['break_time'];
    $working_hours = $_POST['working_hours'];
    $date_of_joining = $_POST['date_of_joining'];
    $department = $_POST['department'];
    $emergency_contact = $_POST['emergency_contact'];
    $emergency_relation = $_POST['emergency_relation'];
    $bank_account = $_POST['bank_account'];
    $ifsc_code = $_POST['ifsc_code'];
    $adhar_number = $_POST['adhar_number'];
    $pan_number = $_POST['pan_number'];
    $epf_number = $_POST['epf_number'];
    // Salary-related fields
    $sick_leave = $_POST['sick_leave'];
    $casual_leave = $_POST['casual_leave'];
    $paid_leave = $_POST['paid_leave'];
    $other_leave = $_POST['other_leave'];
    $total_leave = $_POST['total_leave'];
    $basic = $_POST['basic'];
    $da = $_POST['da'];
    $hra = $_POST['hra'];
    $conveyance = $_POST['conveyance'];
    $special_allowance = $_POST['special_allowance'];
    $performance_bonus = $_POST['performance_bonus'];
    $medical_allowance = $_POST['medical_allowance'];
    $washing_allowance = $_POST['washing_allowance'];
    $canteen_allowance = $_POST['canteen_allowance'];
    $other_allowances = $_POST['other_allowances'];
    $gross_salary = $_POST['gross_salary'];
    $epf_employer = $_POST['epf_employer'];
    $esic_employer = $_POST['esic_employer'];
    $retention_bonus = $_POST['retention_bonus'];
    $gratuity = $_POST['gratuity'];
    $leave_encashment = $_POST['leave_encashment'];
    $total_ctc = $_POST['total_ctc'];
    $epf_employee = $_POST['epf_employee'];
    $professional_tax = $_POST['professional_tax'];
    $income_tax = $_POST['income_tax'];
    $insurance_premium = $_POST['insurance_premium'];
    $advance = $_POST['advance'];
    $other_deductions = $_POST['other_deductions'];
    $total_deductions = $_POST['total_deductions'];
    $net_salary = $_POST['net_salary'];
    $status = $_POST['status'];
    // Handle file uploads
    $target_dir = "../uploads/";
    $photo_path = $employee['photo'];
    $adhar_path = $employee['adhar_file'];
    $pan_path = $employee['pan_file'];

    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
        $photo_path = $target_dir . basename($_FILES['photo']['name']);
        move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path);
    }
    if (isset($_FILES['adhar_file']) && $_FILES['adhar_file']['error'] == UPLOAD_ERR_OK) {
        $adhar_path = $target_dir . basename($_FILES['adhar_file']['name']);
        move_uploaded_file($_FILES['adhar_file']['tmp_name'], $adhar_path);
    }
    if (isset($_FILES['pan_file']) && $_FILES['pan_file']['error'] == UPLOAD_ERR_OK) {
        $pan_path = $target_dir . basename($_FILES['pan_file']['name']);
        move_uploaded_file($_FILES['pan_file']['tmp_name'], $pan_path);
    }
    // Update employee details in the database
 // Only update the password if a new one is provided
if (!empty($_POST['password'])) {
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $stmt = $conn->prepare("UPDATE employees SET 
        name = ?, username = ?, phone = ?, email = ?, esic = ?, address = ?, dob = ?, anniversary = ?, designation = ?, role = ?, manager = ?, salary_type = ?, 
        office = ?, latitude = ?, longitude = ?, punchin_time = ?, punchout_time = ?, break_time = ?, working_hours = ?, date_of_joining = ?, 
        department = ?, emergency_contact = ?, emergency_relation = ?, bank_account = ?, ifsc_code = ?, 
        adhar_number = ?, pan_number = ?, epf_number = ?, photo = ?, adhar_file = ?, pan_file = ?, 
        sick_leave = ?, casual_leave = ?, paid_leave = ?, other_leave = ?, total_leave = ?, basic = ?, da = ?, hra = ?, conveyance = ?, special_allowance = ?, performance_bonus = ?, 
        medical_allowance = ?, washing_allowance = ?, canteen_allowance = ?, other_allowances = ?, 
        gross_salary = ?, epf_employer = ?, esic_employer = ?, retention_bonus = ?, gratuity = ?, 
        leave_encashment = ?, total_retentions = ?, total_ctc = ?, epf_employee = ?, professional_tax = ?, 
        income_tax = ?, insurance_premium = ?, advance = ?, other_deductions = ?, total_deductions = ?, 
        net_salary = ?, status = ?, password = ? WHERE employee_id = ?");
    $stmt->bind_param(
        "sssssssssssssssssssssssssssssssiiiiidddddddddddddddddddddddddssss",
        $name, $username, $phone, $email, $esic, $address, $dob, $anniversary, $designation, $role, $manager, $salary_type, $office,
        $latitude, $longitude, $punchin_time, $punchout_time, $break_time, $working_hours, $date_of_joining, $department, $emergency_contact, $emergency_relation, $bank_account, $ifsc_code, $adhar_number, $pan_number, $epf_number, $photo_path, $adhar_path, $pan_path, $sick_leave, $casual_leave, $paid_leave, $other_leave, $total_leave, $basic, $da, $hra, $conveyance, $special_allowance, $performance_bonus,
        $medical_allowance, $washing_allowance, $canteen_allowance, $other_allowances, $gross_salary, $epf_employer, $esic_employer, $retention_bonus, $gratuity, $leave_encashment, $total_retentions, $total_ctc, $epf_employee, $professional_tax, $income_tax, $insurance_premium, $advance, $other_deductions,
        $total_deductions, $net_salary, $status, $password, $employee_id
    );
} else {
    $stmt = $conn->prepare("UPDATE employees SET 
        name = ?, username = ?, phone = ?, email = ?, esic = ?, address = ?, dob = ?, anniversary = ?, designation = ?, role = ?, manager = ?,  salary_type = ?, 
        office = ?, latitude = ?, longitude = ?, punchin_time = ?, punchout_time = ?, break_time = ?, working_hours = ?, date_of_joining = ?, 
        department = ?, emergency_contact = ?, emergency_relation = ?, bank_account = ?, ifsc_code = ?, 
        adhar_number = ?, pan_number = ?, epf_number = ?, photo = ?, adhar_file = ?, pan_file = ?, 
        sick_leave = ?, casual_leave = ?, paid_leave = ?, other_leave = ?, total_leave = ?, basic = ?, da = ?, hra = ?, conveyance = ?, special_allowance = ?, performance_bonus = ?, 
        medical_allowance = ?, washing_allowance = ?, canteen_allowance = ?, other_allowances = ?, 
        gross_salary = ?, epf_employer = ?, esic_employer = ?, retention_bonus = ?, gratuity = ?, 
        leave_encashment = ?, total_retentions = ?, total_ctc = ?, epf_employee = ?, professional_tax = ?, 
        income_tax = ?, insurance_premium = ?, advance = ?, other_deductions = ?, total_deductions = ?, 
        net_salary = ?, status = ? WHERE employee_id = ?");
    $stmt->bind_param(
        "sssssssssssssssssssssssssssssssiiiiidddddddddddddddddddddddddsss",
        $name, $username, $phone, $email, $esic, $address, $dob, $anniversary, $designation, $role, $manager, $salary_type, $office,
        $latitude, $longitude, $punchin_time, $punchout_time, $break_time, $working_hours, $date_of_joining, $department, $emergency_contact, $emergency_relation, $bank_account, $ifsc_code, $adhar_number, $pan_number, $epf_number, $photo_path, $adhar_path, $pan_path, $sick_leave, $casual_leave, $paid_leave, $other_leave, $total_leave, $basic, $da, $hra, $conveyance, $special_allowance, $performance_bonus,
        $medical_allowance, $washing_allowance, $canteen_allowance, $other_allowances, $gross_salary, $epf_employer, $esic_employer, $retention_bonus, $gratuity, $leave_encashment, $total_retentions, $total_ctc, $epf_employee, $professional_tax, $income_tax, $insurance_premium, $advance, $other_deductions,
        $total_deductions, $net_salary, $status, $employee_id
    );
}

if ($stmt->execute()) {
    echo "<div class='alert alert-success'>Employee updated successfully. Employee ID: $employee_id</div>";
    echo "
        <script>
            setTimeout(function() {
                window.location.href = 'manage_employee';
            }, 3000); // Redirect after 3 seconds
        </script>
    ";
} else {
    echo "<div class='alert alert-danger'>Failed to update employee details.</div>";
}

}
?>
<style>
.onboard-page {
    padding-bottom: 1.5rem;
}

.onboard-topbar,
.onboard-form-card {
    border: 1px solid rgba(87, 96, 108, 0.12);
    border-radius: 22px;
    box-shadow: 0 14px 34px rgba(31, 41, 55, 0.06);
    background: #fff;
}

.onboard-topbar {
    padding: 1.15rem 1.2rem;
    margin-bottom: 1rem;
}

.onboard-topbar-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 1rem;
    align-items: center;
}

.onboard-section-label {
    display: block;
    margin-bottom: 0.45rem;
    color: #6b7280;
    font-size: 0.74rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.onboard-title {
    margin: 0;
    color: #0f172a;
    font-size: 1.45rem;
    font-weight: 800;
    letter-spacing: -0.03em;
}

.onboard-copy {
    margin: 0.35rem 0 0;
    color: #6b7280;
    font-size: 0.92rem;
    max-width: 700px;
}

.onboard-topbar-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
}

.onboard-btn-dark,
.onboard-submit-btn {
    min-height: 40px;
    padding: 0.56rem 1rem;
    border-radius: 14px;
    font-size: 0.8rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    background: linear-gradient(135deg, #161616 0%, #2d2d2d 100%) !important;
    color: #fff !important;
    border: none !important;
    box-shadow: 0 12px 24px rgba(17, 24, 39, 0.18);
}

.onboard-btn-dark:hover,
.onboard-submit-btn:hover {
    background: linear-gradient(135deg, #101010 0%, #242424 100%) !important;
    color: #fff !important;
}

.onboard-form-card {
    overflow: hidden;
    margin-bottom: 1rem;
}

.onboard-form-card .card-header {
    padding: 1.1rem 1.2rem;
    border-bottom: 1px solid #eef2f7;
    background: #f8fafc;
}

.onboard-card-title {
    margin: 0;
    color: #0f172a;
    font-size: 1rem;
    font-weight: 700;
}

.onboard-card-copy {
    margin: 0.35rem 0 0;
    color: #6b7280;
    font-size: 0.88rem;
}

.onboard-form-card .card-body {
    padding: 1.25rem;
}

.onboard-form-card .row {
    row-gap: 0.2rem;
}

.onboard-form-card .form-label,
.onboard-form-card label {
    display: block;
    margin-bottom: 0.45rem;
    color: #6b7280;
    font-size: 0.74rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.onboard-form-card .form-control {
    min-height: 46px;
    border-radius: 14px;
    border: 1px solid #d8dee7;
    box-shadow: none;
    padding: 0.7rem 0.95rem;
}

.onboard-form-card textarea.form-control {
    min-height: 96px;
    padding-top: 0.85rem;
}

.onboard-form-card .form-control:focus {
    border-color: #1e3a5f;
    box-shadow: 0 0 0 0.18rem rgba(30, 58, 95, 0.12);
}

.onboard-form-card input[readonly] {
    background: #f8fafc;
    color: #64748b;
}

.onboard-form-card small {
    display: inline-block;
    margin-top: 0.45rem;
    color: #64748b;
    font-size: 0.76rem;
}

.onboard-form-card small a {
    color: #1e3a5f;
    font-weight: 700;
    text-decoration: none;
}

.onboard-section-title {
    width: 100%;
    margin: 1.4rem 0 0.2rem;
    padding-top: 1.2rem;
    border-top: 1px solid #eef2f7;
    color: #0f172a;
    font-size: 1rem;
    font-weight: 700;
}

.onboard-actions {
    display: flex;
    justify-content: flex-end;
    padding-top: 1rem;
    margin-top: 0.6rem;
    border-top: 1px solid #eef2f7;
}

@media (max-width: 991.98px) {
    .onboard-topbar-grid {
        grid-template-columns: 1fr;
    }

    .onboard-topbar-actions {
        justify-content: stretch;
    }

    .onboard-btn-dark,
    .onboard-submit-btn {
        width: 100%;
    }
}
</style>

<div class="container-fluid py-4 onboard-page">
    <div class="row">
        <div class="col-12">
            <div class="onboard-topbar">
                <div class="onboard-topbar-grid">
                    <div>
                        <span class="onboard-section-label">Employee Onboarding</span>
                        <h6 class="onboard-title">Edit Employee Details</h6>
                        <p class="onboard-copy">Update profile, office assignment, leave structure, salary components, deductions, and supporting files from one structured form.</p>
                    </div>
                    <div class="onboard-topbar-actions">
                        <a href="manage_employee" class="btn onboard-btn-dark mb-0">Back to Employee List</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-12 mb-lg-0 mb-4">
            <div class="card onboard-form-card">
                <div class="card-header">
                    <h6 class="onboard-card-title">Employee Information</h6>
                    <p class="onboard-card-copy">All existing onboarding fields are preserved. This update only improves layout and presentation.</p>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" id="employeeForm">
                    <div class="row">
                        <div class="col-md-4 mt-4">
                            <label for="name" class="form-label">Name</label>
                            <input class="form-control" type="text" name="name" id="name" value="<?= htmlspecialchars($employee['name']) ?>" required>
                        </div>
                        <div class="col-md-4 mt-4">
                            <label for="email" class="form-label">Email (Optional)</label>
                            <input class="form-control" type="email" name="email" id="email" value="<?= htmlspecialchars($employee['email']) ?>">
                        </div>
                        <div class="col-md-4 mt-4">
                            <label for="phone" class="form-label">Phone</label>
                            <input class="form-control" type="text" name="phone" id="phone" value="<?= htmlspecialchars($employee['phone']) ?>" required>
                        </div>
                        <div class="col-md-4 mt-4">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" name="address" id="address" rows="3" required><?= htmlspecialchars($employee['address']) ?></textarea>
                        </div>
                        <div class="col-md-4 mt-4">
                            <label for="designation" class="form-label">Designation</label>
                            <input class="form-control" type="text" name="designation" id="designation" value="<?= htmlspecialchars($employee['designation']) ?>">
                        </div>
                        <div class="col-md-4 mt-4">
                            <label for="dob" class="form-label">Date Of Birth</label>
                            <input class="form-control" type="date" name="dob" value="<?= htmlspecialchars($employee['dob']) ?>" placeholder="Date Of Birth">
                        </div>

                        <div class="col-md-4 mt-4">
                            <label for="anniversary" class="form-label">Anniversary Date</label>
                            <input class="form-control" type="date" name="anniversary" value="<?= htmlspecialchars($employee['anniversary']) ?>" placeholder="Anniversary Date">
                        </div>
                        <!-- Office -->
                        <div class="col-md-4 mt-4">
                            <label for="office" class="form-label">Office</label>
                            <select class="form-control" name="office" id="office" required onchange="fetchOfficeDetails(this.value)">
                                <option value="">Select Office</option>
                                <?php
                                $stmt = $conn->prepare("SELECT office_name, state_name FROM offices ORDER BY office_name ASC");
                                $stmt->execute();
                                $result = $stmt->get_result();

                                while ($office = $result->fetch_assoc()) {
                                    $value = $office['office_name'] . '_' . $office['state_name'];
                                    $selected = ($employee['office'] == $value) ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($value) . "' $selected>" . htmlspecialchars($office['office_name']) . " (" . htmlspecialchars($office['state_name']) . ")</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <input class="form-control" type="hidden" name="latitude" id="latitude" value="<?= htmlspecialchars($employee['latitude'] ?? '') ?>" readonly>
                        <input class="form-control" type="hidden" name="longitude" id="longitude" value="<?= htmlspecialchars($employee['longitude'] ?? '') ?>" readonly>

                        <div class="col-md-4 mt-4">
                            <label for="date_of_joining" class="form-label">Date of Joining</label>
                            <input class="form-control" type="date" name="date_of_joining" value="<?= htmlspecialchars($employee['date_of_joining']) ?>">
                        </div>
                        <div class="col-md-4 mt-4">
                            <label for="department" class="form-label">Department (Optional)</label>
                            <input class="form-control" type="text" name="department" value="<?= htmlspecialchars($employee['department']) ?>" placeholder="Department">
                        </div>

                        
        <!-- Select Role -->
        <div class="col-md-4 mt-4">
            <label for="role" class="form-label">Select Role</label>
            <select class="form-control" name="role" id="role" required>
                <option value="">Select Role</option>
                <option value="Manager" <?= ($employee['role'] == 'Manager') ? 'selected' : '' ?>>Manager</option>
                <option value="Employee" <?= ($employee['role'] == 'Employee') ? 'selected' : '' ?>>Employee</option>
            </select>
        </div>

        <!-- Select Manager -->
        <div class="col-md-4 mt-4">
            <label for="manager" class="form-label">Select Manager</label>
            <select class="form-control" name="manager" id="manager">
                <option value="">Select Manager</option>
                <?php
                $stmt = $conn->prepare("SELECT id, name FROM employees WHERE role = 'Manager' AND status = 'Active' ORDER BY name ASC");
                $stmt->execute();
                $managers = $stmt->get_result();

                while ($manager = $managers->fetch_assoc()) {
                    $selected = ($employee['manager'] == $manager['id']) ? 'selected' : '';
                    echo "<option value='" . htmlspecialchars($manager['id']) . "' $selected>" . htmlspecialchars($manager['name']) . "</option>";
                }
                ?>
            </select>
        </div>
                        <div class="col-md-4 mt-4">
                            <label for="bank_account" class="form-label">Bank Account</label>
                            <input class="form-control" type="text" name="bank_account" value="<?= htmlspecialchars($employee['bank_account']) ?>" placeholder="Bank Account Details">
                        </div>

                        <div class="col-md-4 mt-4">
                            <label for="ifsc_code" class="form-label">IFSC Code</label>
                            <input class="form-control" type="text" name="ifsc_code" value="<?= htmlspecialchars($employee['ifsc_code']) ?>" placeholder="IFSC Code">
                        </div>

                        <div class="col-md-4 mt-3">
                            <label for="adhar_number" class="form-label">Aadhaar Number</label>
                            <input class="form-control" type="text" name="adhar_number" value="<?= htmlspecialchars($employee['adhar_number']) ?>">
                        </div>
                        <div class="col-md-4 mt-3">
                            <label for="pan_number" class="form-label">PAN Number</label>
                            <input class="form-control" type="text" name="pan_number" value="<?= htmlspecialchars($employee['pan_number']) ?>">
                        </div>
                        <div class="col-md-4 mt-3">
                            <label for="epf_number" class="form-label">Employee UAN (Optional)</label>
                            <input class="form-control" type="text" name="epf_number" value="<?= htmlspecialchars($employee['epf_number']) ?>">
                        </div>
                        <div class="col-md-4 mt-3">
                            <label for="esic" class="form-label">ESIC No.</label>
                            <input class="form-control" type="text" name="esic" value="<?= htmlspecialchars($employee['esic']) ?>">
                        </div>
                        <div class="col-md-4 mt-3">
                            <label for="emergency_contact" class="form-label">Emergency Contact (Optional)</label>
                            <input class="form-control" type="text" name="emergency_contact" value="<?= htmlspecialchars($employee['emergency_contact']) ?>">
                        </div>
                        <div class="col-md-4 mt-3">
                            <label for="emergency_relation" class="form-label">Relation (Optional)</label>
                            <input class="form-control" type="text" name="emergency_relation" value="<?= htmlspecialchars($employee['emergency_relation']) ?>">
                        </div>
                        <div class="col-md-4 mt-3">
                            <label for="adhar_file" class="form-label">Upload Aadhaar (Optional)</label>
                            <input class="form-control" type="file" name="adhar_file" accept="application/pdf,image/*">
                            <?php if ($employee['adhar_file']) : ?>
                                <small>Current Photo: <a href="<?= htmlspecialchars($employee['adhar_file']) ?>" target="_blank">View</a></small>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4 mt-3">
                            <label for="pan_file" class="form-label">Upload PAN (Optional)</label>
                            <input class="form-control" type="file" name="pan_file" accept="application/pdf,image/*">
                            <?php if ($employee['pan_file']) : ?>
                                <small>Current Photo: <a href="<?= htmlspecialchars($employee['pan_file']) ?>" target="_blank">View</a></small>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4 mt-4">
                            <label for="photo" class="form-label">Profile Photo (Optional)</label>
                            <input class="form-control" type="file" name="photo" id="photo" accept="image/*">
                            <?php if ($employee['photo']) : ?>
                                <small>Current Photo: <a href="<?= htmlspecialchars($employee['photo']) ?>" target="_blank">View</a></small>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6 mt-4">
                            <label for="username" class="form-label">Username</label>
                            <input class="form-control" type="text" name="username" id="username" value="<?= htmlspecialchars($employee['employee_id']) ?>" readonly>
                        </div>
                        <div class="col-md-6 mt-4">
                            <label for="password" class="form-label">Password (leave blank to keep current password)</label>
                            <input class="form-control" type="password" name="password" id="password">
                        </div>
                        <h5 class="onboard-section-title">Employee Leave Structure</h5>
                        <div class="col-md-4">
                            <label for="sick_leave">Sick Leave</label>
                            <input type="number" name="sick_leave" id="sick_leave" class="form-control calculate" value="<?= htmlspecialchars($employee['sick_leave']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="casual_leave">Casual Leave</label>
                            <input type="number" name="casual_leave" id="casual_leave" class="form-control calculate" value="<?= htmlspecialchars($employee['casual_leave']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="paid_leave">Casual Leave</label>
                            <input type="number" name="paid_leave" id="paid_leave" class="form-control calculate" value="<?= htmlspecialchars($employee['paid_leave']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="other_leave">Other Leave</label>
                            <input type="number" name="other_leave" id="other_leave" class="form-control calculate" value="<?= htmlspecialchars($employee['other_leave']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="total_leave">Total Leave</label>
                            <input type="number" name="total_leave" id="total_leave" class="form-control calculate" value="<?= htmlspecialchars($employee['total_leave']) ?>" readonly>
                        </div>



                        <div class="col-md-4 ">
                            <label for="salary_type" class="form-label">Salary Type</label>
                            <select class="form-control" name="salary_type" id="salary_type">

                                <option value="Daily" <?= $employee['salary_type'] == 'Daily' ? 'selected' : '' ?>>Daily</option>
                                <option value="Monthly" <?= $employee['salary_type'] == 'Monthly' ? 'selected' : '' ?>>Monthly</option>
                            </select>
                        </div>
                        <div class="col-md-4 ">
                            <label for="punchin_time" class="form-label">Punch-In Time</label>
                            <input class="form-control" type="time" name="punchin_time" id="punchin_time" value="<?= htmlspecialchars($employee['punchin_time']) ?>" required>
                        </div>
                        <div class="col-md-4 ">
                            <label for="punchout_time" class="form-label">Punch-Out Time</label>
                            <input class="form-control" type="time" name="punchout_time" id="punchout_time" value="<?= htmlspecialchars($employee['punchout_time']) ?>" required>
                        </div>

                        <div class="col-md-4 ">
                            <label for="break_time" class="form-label">Break Time (in minutes)</label>
                            <input class="form-control" type="number" value="<?= htmlspecialchars($employee['break_time']) ?>" name="break_time" id="break_time" placeholder="Break Time (in minutes)">
                        </div>

                        <div class="col-md-4 ">
                            <label for="working_hours" class="form-label">Working Hours</label>
                            <input class="form-control" type="text" name="working_hours" id="working_hours" value="<?= htmlspecialchars($employee['working_hours']) ?>" required>
                        </div>

                        <div class="col-md-4 ">
                            <label for="hourly_salary" class="form-label">Hourly Salary</label>
                            <input class="form-control" type="text" name="hourly_salary" id="hourly_salary" value="<?= htmlspecialchars($employee['hourly_salary']) ?>">
                        </div>
                        <div class="col-md-4 ">
                            <label for="daily_salary" class="form-label">Daily Salary</label>
                            <input class="form-control" type="text" name="daily_salary" id="daily_salary" value="<?= htmlspecialchars($employee['daily_salary']) ?>">
                        </div>
                        <h5 class="onboard-section-title">Salary Structure</h5>

                        <div class="col-md-4">
                            <label for="basic">Basic Salary</label>
                            <input type="number" name="basic" id="basic" class="form-control calculate" value="<?php echo $employee['basic']; ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="da">Dearness Allowance (DA)</label>
                            <input type="number" name="da" id="da" class="form-control calculate" value="<?php echo $employee['da']; ?>">
                        </div>


                        <div class="col-md-4">
                            <label for="hra">House Rent Allowance (HRA)</label>
                            <input type="number" name="hra" id="hra" class="form-control calculate" value="<?php echo $employee['hra']; ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="conveyance">Conveyance Allowance</label>
                            <input type="number" name="conveyance" id="conveyance" class="form-control calculate" value="<?php echo $employee['conveyance']; ?>">
                        </div>

                        <div class="col-md-4">
                            <label for="special_allowance">Special Allowance</label>
                            <input type="number" name="special_allowance" id="special_allowance" class="form-control calculate" value="<?php echo $employee['special_allowance']; ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="performance_bonus">Performance Bonus</label>
                            <input type="number" name="performance_bonus" id="performance_bonus" class="form-control calculate" value="<?php echo $employee['performance_bonus']; ?>">
                        </div>

                        <div class="col-md-4">
                            <label for="medical_allowance">Medical Allowance</label>
                            <input type="number" name="medical_allowance" id="medical_allowance" class="form-control calculate" value="<?php echo $employee['medical_allowance']; ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="washing_allowance">Washing Allowance</label>
                            <input type="number" name="washing_allowance" id="washing_allowance" class="form-control calculate" value="<?php echo $employee['washing_allowance']; ?>">
                        </div>

                        <div class="col-md-4">
                            <label for="canteen_allowance">Canteen Allowance</label>
                            <input type="number" name="canteen_allowance" id="canteen_allowance" class="form-control calculate" value="<?php echo $employee['canteen_allowance']; ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="other_allowances">Other Allowances</label>
                            <input type="number" name="other_allowances" id="other_allowances" class="form-control calculate" value="<?php echo $employee['other_allowances']; ?>">
                        </div>

                        <!-- Gross Salary -->

                        <div class="col-md-6">
                            <label for="gross_salary">Gross Salary</label>
                            <input type="number" name="gross_salary" id="gross_salary" class="form-control" value="<?php echo $employee['gross_salary']; ?>" readonly>
                        </div>

                        <!-- Retentions -->
                        <h5 class="onboard-section-title mt-4">Employer's Contributions</h5>

                        <div class="col-md-4">
                            <label for="epf_employer">EPF Employer</label>
                            <input type="number" name="epf_employer" id="epf_employer" class="form-control calculate" value="<?php echo $employee['epf_employer']; ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="esic_employer">ESIC Employer</label>
                            <input type="number" name="esic_employer" id="esic_employer" class="form-control calculate" value="<?php echo $employee['esic_employer']; ?>">
                        </div>


                        <div class="col-md-4">
                            <label for="retention_bonus">Retention Bonus</label>
                            <input type="number" name="retention_bonus" id="retention_bonus" class="form-control calculate" value="<?php echo $employee['retention_bonus']; ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="gratuity">Gratuity</label>
                            <input type="number" name="gratuity" id="gratuity" class="form-control calculate" value="<?php echo $employee['gratuity']; ?>">
                        </div>


                        <div class="col-md-4">
                            <label for="leave_encashment">Leave Encashment</label>
                            <input type="number" name="leave_encashment" id="leave_encashment" class="form-control calculate" value="<?php echo $employee['leave_encashment']; ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="total_ctc">Total Retentions (CTC)</label>
                            <input type="number" name="total_ctc" id="total_ctc" class="form-control" value="<?php echo $employee['total_ctc']; ?>" readonly>
                        </div>

                        <!-- Deductions -->
                        <h5 class="onboard-section-title mt-4">Deductions</h5>

                        <div class="col-md-4">
                            <label for="epf_employee">Employee Provident Fund (EPF)</label>
                            <input type="number" name="epf_employee" id="epf_employee" class="form-control calculate" value="<?php echo $employee['epf_employee']; ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="professional_tax">Professional Tax (PT)</label>
                            <input type="number" name="professional_tax" id="professional_tax" class="form-control calculate" value="<?php echo $employee['professional_tax']; ?>">
                        </div>


                        <div class="col-md-4">
                            <label for="income_tax">Income Tax (TDS)</label>
                            <input type="number" name="income_tax" id="income_tax" class="form-control calculate" value="<?php echo $employee['income_tax']; ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="insurance_premium">Insurance Premium</label>
                            <input type="number" name="insurance_premium" id="insurance_premium" class="form-control calculate" value="<?php echo $employee['insurance_premium']; ?>">
                        </div>

                        <div class="col-md-4">
                            <label for="advance">Advance</label>
                            <input type="number" name="advance" id="advance" class="form-control calculate" value="<?php echo $employee['advance']; ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="other_deductions">Other Deductions</label>
                            <input type="number" name="other_deductions" id="other_deductions" class="form-control calculate" value="<?php echo $employee['other_deductions']; ?>">
                        </div>

                        <div class="col-md-4">
                            <label for="total_deductions">Total Deductions</label>
                            <input type="number" name="total_deductions" id="total_deductions" class="form-control" value="<?php echo $employee['total_deductions']; ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label for="net_salary">Net Salary (In-Hand)</label>
                            <input type="number" name="net_salary" id="net_salary" class="form-control" value="<?php echo $employee['net_salary']; ?>" readonly>
                        </div>
                        <div class="col-md-4">
            <label for="status" class="form-label">Select Status</label>
            <select class="form-control" name="status" id="status" required>
                <option value="">Select status</option>
                <option value="Pending" <?= ($employee['status'] == 'Pending') ? 'selected' : '' ?>>Pending</option>
                <option value="Active" <?= ($employee['status'] == 'Active') ? 'selected' : '' ?>>Active</option>
                <option value="x_employee" <?= ($employee['status'] == 'x_employee') ? 'selected' : '' ?>>X Employee</option>
            </select>
        </div>
                    </div>
                    <div class="onboard-actions mt-4">
                        <button class="btn onboard-submit-btn" type="submit">Update Employee</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    function fetchOfficeDetails(officeId) {
        if (!officeId) {
            document.getElementById('latitude').value = '';
            document.getElementById('longitude').value = '';
            return;
        }

        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'get_office_details', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        xhr.onload = function() {
            if (this.status === 200) {
                const response = JSON.parse(this.responseText);
                if (response.success) {
                    document.getElementById('latitude').value = response.latitude;
                    document.getElementById('longitude').value = response.longitude;
                } else {
                    alert(response.message);
                }
            }
        };

        xhr.send('office_id=' + officeId);
    }
</script>
<script>
    // Calculate Working Hours, Hourly Salary, and Daily Salary
    document.getElementById('employeeForm').addEventListener('input', function() {
        const punchin = document.getElementById('punchin_time').value;
        const punchout = document.getElementById('punchout_time').value;
        const salary = parseFloat(document.getElementById('net_salary').value) || 0;
        const salaryType = document.getElementById('salary_type').value;
        const breakTime = parseFloat(document.getElementById('break_time').value) || 0; // Break time in minutes
        if (punchin && punchout) {
            const [punchinH, punchinM] = punchin.split(':').map(Number);
            const [punchoutH, punchoutM] = punchout.split(':').map(Number);

            // Calculate total working hours before deducting break time
            let workingHours = ((punchoutH + punchoutM / 60) - (punchinH + punchinM / 60)).toFixed(2);
            workingHours = Math.max(0, workingHours - (breakTime / 60)); // Deduct break time in hours

            document.getElementById('working_hours').value = workingHours > 0 ? workingHours.toFixed(2) : 0;

            if (salaryType === 'Monthly') {
                const hourlySalary = workingHours > 0 ? (salary / 30 / workingHours).toFixed(2) : 0;
                document.getElementById('hourly_salary').value = hourlySalary;
                document.getElementById('daily_salary').value = (salary / 30).toFixed(2);
            } else if (salaryType === 'Daily') {
                document.getElementById('hourly_salary').value = workingHours > 0 ? (salary / workingHours).toFixed(2) : 0;
                document.getElementById('daily_salary').value = salary.toFixed(2);
            }
        }
    });
</script>
<script>
    document.querySelectorAll('.calculate').forEach(input => {
        input.addEventListener('input', calculateSalary);
    });

    function calculateSalary() {
        // Fetch all input values
        const basic = parseFloat(document.getElementById('basic').value) || 0;
        const da = parseFloat(document.getElementById('da').value) || 0;
        const hra = parseFloat(document.getElementById('hra').value) || 0;
        const conveyance = parseFloat(document.getElementById('conveyance').value) || 0;
        const special_allowance = parseFloat(document.getElementById('special_allowance').value) || 0;
        const performance_bonus = parseFloat(document.getElementById('performance_bonus').value) || 0;
        const medical_allowance = parseFloat(document.getElementById('medical_allowance').value) || 0;
        const washing_allowance = parseFloat(document.getElementById('washing_allowance').value) || 0;
        const canteen_allowance = parseFloat(document.getElementById('canteen_allowance').value) || 0;
        const other_allowances = parseFloat(document.getElementById('other_allowances').value) || 0;
        // Gross Salary Calculation
        const gross_salary = basic + da + hra + conveyance + special_allowance +
            performance_bonus + medical_allowance + washing_allowance +
            canteen_allowance + other_allowances;
        document.getElementById('gross_salary').value = gross_salary.toFixed(2);

        // Retentions
        const epf_employer = parseFloat(document.getElementById('epf_employer').value) || 0;
        const esic_employer = parseFloat(document.getElementById('esic_employer').value) || 0;
        const retention_bonus = parseFloat(document.getElementById('retention_bonus').value) || 0;
        const gratuity = parseFloat(document.getElementById('gratuity').value) || 0;
        const leave_encashment = parseFloat(document.getElementById('leave_encashment').value) || 0;

        const total_ctc = epf_employer + esic_employer + retention_bonus + gratuity + leave_encashment;
        document.getElementById('total_ctc').value = total_ctc.toFixed(2);

        // Deductions
        const epf_employee = parseFloat(document.getElementById('epf_employee').value) || 0;
        const professional_tax = parseFloat(document.getElementById('professional_tax').value) || 0;
        const income_tax = parseFloat(document.getElementById('income_tax').value) || 0;
        const insurance_premium = parseFloat(document.getElementById('insurance_premium').value) || 0;
        const advance = parseFloat(document.getElementById('advance').value) || 0;
        const other_deductions = parseFloat(document.getElementById('other_deductions').value) || 0;

        const total_deductions = epf_employee + professional_tax + income_tax + insurance_premium + advance + other_deductions;
        document.getElementById('total_deductions').value = total_deductions.toFixed(2);



        const sick_leave = parseFloat(document.getElementById('sick_leave').value) || 0;
        const casual_leave = parseFloat(document.getElementById('casual_leave').value) || 0;
        const paid_leave = parseFloat(document.getElementById('paid_leave').value) || 0;
        const other_leave = parseFloat(document.getElementById('other_leave').value) || 0;

        const total_leave = sick_leave + casual_leave + paid_leave + other_leave;
        document.getElementById('total_leave').value = total_leave.toFixed(2);

        // Net Salary Calculation
        const net_salary = gross_salary - total_deductions;
        document.getElementById('net_salary').value = net_salary.toFixed(2);
    }
</script>
<?php include("footer.php"); ?>