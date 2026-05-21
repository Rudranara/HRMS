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
    $father_name = $_POST['father_name'];
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
        name = ?, username = ?, phone = ?, email = ?, esic = ?, address = ?, dob = ?, anniversary = ?, designation = ?, role = ?, father_name = ?, manager = ?, salary_type = ?, 
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
        "ssssssssssssssssssssssssssssssssiiiiidddddddddddddddddddddddddssss",
        $name, $username, $phone, $email, $esic, $address, $dob, $anniversary, $designation, $role, $father_name, $manager, $salary_type, $office,
        $latitude, $longitude, $punchin_time, $punchout_time, $break_time, $working_hours, $date_of_joining, $department, $emergency_contact, $emergency_relation, $bank_account, $ifsc_code, $adhar_number, $pan_number, $epf_number, $photo_path, $adhar_path, $pan_path, $sick_leave, $casual_leave, $paid_leave, $other_leave, $total_leave, $basic, $da, $hra, $conveyance, $special_allowance, $performance_bonus,
        $medical_allowance, $washing_allowance, $canteen_allowance, $other_allowances, $gross_salary, $epf_employer, $esic_employer, $retention_bonus, $gratuity, $leave_encashment, $total_retentions, $total_ctc, $epf_employee, $professional_tax, $income_tax, $insurance_premium, $advance, $other_deductions,
        $total_deductions, $net_salary, $status, $password, $employee_id
    );
} else {
    $stmt = $conn->prepare("UPDATE employees SET 
        name = ?, username = ?, phone = ?, email = ?, esic = ?, address = ?, dob = ?, anniversary = ?, designation = ?, role = ?, father_name = ?, manager = ?,  salary_type = ?, 
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
        "ssssssssssssssssssssssssssssssssiiiiidddddddddddddddddddddddddsss",
        $name, $username, $phone, $email, $esic, $address, $dob, $anniversary, $designation, $role, $father_name, $manager, $salary_type, $office,
        $latitude, $longitude, $punchin_time, $punchout_time, $break_time, $working_hours, $date_of_joining, $department, $emergency_contact, $emergency_relation, $bank_account, $ifsc_code, $adhar_number, $pan_number, $epf_number, $photo_path, $adhar_path, $pan_path, $sick_leave, $casual_leave, $paid_leave, $other_leave, $total_leave, $basic,$da, $hra, $conveyance, $special_allowance, $performance_bonus,
        $medical_allowance, $washing_allowance, $canteen_allowance, $other_allowances, $gross_salary, $epf_employer, $esic_employer, $retention_bonus, $gratuity, $leave_encashment, $total_retentions, $total_ctc, $epf_employee, $professional_tax, $income_tax, $insurance_premium, $advance, $other_deductions,
        $total_deductions, $net_salary, $status, $employee_id
    );
}
if ($stmt->execute()) {
    echo "<div class='alert alert-success'>Employee updated successfully. Employee ID: $employee_id</div>";
    echo "
        <script>
            setTimeout(function() {
                window.location.href = 'manage_employee.php';
            }, 3000); // Redirect after 3 seconds
        </script>
    ";
} else {
    echo "<div class='alert alert-danger'>Failed to update employee details.</div>";
}
}
?>
<div class="container-fluid py-4">
    <div class="col-md-12 mb-lg-0 mb-4">
        <div class="card mt-4">
            <div class="card-header pb-0 p-3">
                <div class="row">
                    <div class="col-6 d-flex align-items-center">
                        <h6 class="mb-0">Edit Employee Details</h6>
                    </div>
                    <div class="col-6 text-end">
                        <a href="manage_employee.php" class="btn bg-gradient-dark mb-0">Back to Employee List</a>
                    </div>
                </div>
            </div>
            <div class="card-body p-2">
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
                <option value="Manager" <?= ($employee['role'] == 'Manager') ? 'selected' : '' ?>>Manager/Supervisor </option>
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
                            <label for="father_name" class="form-label">Father Name</label>
                            <input class="form-control" type="text" name="father_name" id="father_name" value="<?= htmlspecialchars($employee['father_name']) ?>" required>
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
                        <h5>Employee Leave Structure</h5>
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


                        <h5 class="mt-4">Do you want to include?</h5>
<div class="row">
    <div class="col-md-3">
        <label>Include EPF?</label>
        <div class="form-check form-switch">
            <input class="form-check-input toggle-switch" type="checkbox" id="toggle_epf" checked>
            <label class="form-check-label" for="toggle_epf">Yes</label>
        </div>
    </div>
    <div class="col-md-3">
        <label>Include ESIC?</label>
        <div class="form-check form-switch">
            <input class="form-check-input toggle-switch" type="checkbox" id="toggle_esic" checked>
            <label class="form-check-label" for="toggle_esic">Yes</label>
        </div>
    </div>
    <div class="col-md-3">
        <label>Include Gratuity?</label>
        <div class="form-check form-switch">
            <input class="form-check-input toggle-switch" type="checkbox" id="toggle_gratuity" checked>
            <label class="form-check-label" for="toggle_gratuity">Yes</label>
        </div>
    </div>
    <div class="col-md-3">
      
        <div class="form-check form-switch">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#salaryBreakdownModal">Salary Breakdown
</button>
        </div>
    </div>
</div>                 
                        <h5>Salary Structure</h5>

                        <div class="col-md-4">
                            <label for="basic">Basic Salary</label>
                            <input type="number" name="basic" id="basic" class="form-control calculate" value="<?php echo $employee['basic']; ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="da">Dearness Allowance (DA)</label>
                            <input type="decimal" name="da" id="da" class="form-control calculate" value="<?php echo $employee['da']; ?>">
                        </div>


                        <div class="col-md-4">
                            <label for="hra">House Rent Allowance (HRA)</label>
                            <input type="decimal" name="hra" id="hra" class="form-control calculate" value="<?php echo $employee['hra']; ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="conveyance">Conveyance Allowance</label>
                            <input type="decimal" name="conveyance" id="conveyance" class="form-control calculate" value="<?php echo $employee['conveyance']; ?>">
                        </div>

                        <div class="col-md-4">
                            <label for="special_allowance">Special Allowance</label>
                            <input type="decimal" name="special_allowance" id="special_allowance" class="form-control calculate" value="<?php echo $employee['special_allowance']; ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="performance_bonus">Performance Bonus</label>
                            <input type="decimal" name="performance_bonus" id="performance_bonus" class="form-control calculate" value="<?php echo $employee['performance_bonus']; ?>">
                        </div>

                        <div class="col-md-4">
                            <label for="medical_allowance">Medical Allowance</label>
                            <input type="decimal" name="medical_allowance" id="medical_allowance" class="form-control calculate" value="<?php echo $employee['medical_allowance']; ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="washing_allowance">Washing Allowance</label>
                            <input type="decimal" name="washing_allowance" id="washing_allowance" class="form-control calculate" value="<?php echo $employee['washing_allowance']; ?>">
                        </div>

                        <div class="col-md-4">
                            <label for="canteen_allowance">Canteen Allowance</label>
                            <input type="decimal" name="canteen_allowance" id="canteen_allowance" class="form-control calculate" value="<?php echo $employee['canteen_allowance']; ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="other_allowances">Other Allowances</label>
                            <input type="decimal" name="other_allowances" id="other_allowances" class="form-control calculate" value="<?php echo $employee['other_allowances']; ?>">
                        </div>

                        <!-- Gross Salary -->

                        <div class="col-md-6">
                            <label for="gross_salary">Gross Salary</label>
                            <input type="decimal" name="gross_salary" id="gross_salary" class="form-control" value="<?php echo $employee['gross_salary']; ?>" readonly>
                        </div>

                        <!-- Retentions -->
                        <h5 class="mt-4">Employer's Contributions</h5>

                        <div class="col-md-4">
                            <label for="epf_employer">EPF Employer</label>
                            <input type="decimal" name="epf_employer" id="epf_employer" class="form-control calculate" value="<?php echo $employee['epf_employer']; ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="esic_employer">ESIC Employer</label>
                            <input type="decimal" name="esic_employer" id="esic_employer" class="form-control calculate" value="<?php echo $employee['esic_employer']; ?>">
                        </div>


                        <div class="col-md-4">
                            <label for="retention_bonus">Retention Bonus</label>
                            <input type="decimal" name="retention_bonus" id="retention_bonus" class="form-control calculate" value="<?php echo $employee['retention_bonus']; ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="gratuity">Gratuity</label>
                            <input type="decimal" name="gratuity" id="gratuity" class="form-control calculate" value="<?php echo $employee['gratuity']; ?>">
                        </div>


                        <div class="col-md-4">
                            <label for="leave_encashment">Leave Encashment</label>
                            <input type="decimal" name="leave_encashment" id="leave_encashment" class="form-control calculate" value="<?php echo $employee['leave_encashment']; ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="total_ctc">Total Retentions (CTC)</label>
                            <input type="decimal" name="total_ctc" id="total_ctc" class="form-control" value="<?php echo $employee['total_ctc']; ?>" readonly>
                        </div>

                        <!-- Deductions -->
                        <h5 class="mt-4">Deductions</h5>

                        <div class="col-md-4">
                            <label for="epf_employee">Employee Provident Fund (EPF)</label>
                            <input type="decimal" name="epf_employee" id="epf_employee" class="form-control calculate" value="<?php echo $employee['epf_employee']; ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="professional_tax">Professional Tax (PT)</label>
                            <input type="decimal" name="professional_tax" id="professional_tax" class="form-control calculate" value="<?php echo $employee['professional_tax']; ?>">
                        </div>


                        <div class="col-md-4">
                            <label for="income_tax">Income Tax (TDS)</label>
                            <input type="decimal" name="income_tax" id="income_tax" class="form-control calculate" value="<?php echo $employee['income_tax']; ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="insurance_premium">Insurance Premium</label>
                            <input type="decimal" name="insurance_premium" id="insurance_premium" class="form-control calculate" value="<?php echo $employee['insurance_premium']; ?>">
                        </div>

                        <div class="col-md-4">
                            <label for="advance">Advance</label>
                            <input type="decimal" name="advance" id="advance" class="form-control calculate" value="<?php echo $employee['advance']; ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="other_deductions">Other Deductions</label>
                            <input type="decimal" name="other_deductions" id="other_deductions" class="form-control calculate" value="<?php echo $employee['other_deductions']; ?>">
                        </div>

                        <div class="col-md-4">
                            <label for="total_deductions">Total Deductions</label>
                            <input type="decimal" name="total_deductions" id="total_deductions" class="form-control" value="<?php echo $employee['total_deductions']; ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label for="net_salary">Net Salary (In-Hand)</label>
                            <input type="decimal" name="net_salary" id="net_salary" class="form-control" value="<?php echo $employee['net_salary']; ?>" readonly>
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
                    <div class="text-center mt-4">
                        <button class="btn btn-primary" type="submit">Update Employee</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Salary Structure Breakdown Modal -->
<div class="modal fade" id="salaryBreakdownModal" tabindex="-1" aria-labelledby="salaryBreakdownModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="salaryBreakdownModalLabel">Salary Structure Breakdown</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h4>Understanding the Salary Components</h4>
                <p>The salary structure consists of different components, each serving a specific purpose. Below is a breakdown of how we calculate the salary:</p>

                <h5>1. Basic Salary</h5>
                <p>The **Basic Salary** is the fixed part of an employee’s compensation. It forms the foundation of the salary structure and is used to calculate other benefits like HRA, PF, and gratuity.</p>
                <p><strong>Formula:</strong> Decided as per company policy (e.g., 40-50% of Gross Salary).</p>

                <h5>2. Dearness Allowance (DA)</h5>
                <p>DA is provided to employees to counter inflation. It is calculated as a percentage of the Basic Salary.</p>
                <p><strong>Formula:</strong> DA = **Basic Salary × 10-20%** (varies by company policy).</p>

                <h5>3. House Rent Allowance (HRA)</h5>
                <p>HRA is given to employees for their rental accommodation expenses. It is typically **40% of Basic Salary** for non-metro cities and **50% of Basic Salary** for metro cities.</p>
                <p><strong>Formula:</strong> HRA = **Basic Salary × 40% (Non-Metro) / 50% (Metro)**.</p>

                <h5>4. Conveyance Allowance</h5>
                <p>Conveyance Allowance is given to employees to cover travel expenses from home to office.</p>
                <p><strong>Maximum Tax-Free Limit:</strong> ₹1,600 per month (₹19,200 per year).</p>

                <h5>5. Special Allowance</h5>
                <p>This is a flexible allowance provided by the employer and is fully taxable. It varies from company to company.</p>

                <h5>6. Performance Bonus</h5>
                <p>A bonus given to employees based on their performance. It may be paid monthly, quarterly, or annually.</p>

                <h5>7. Gross Salary</h5>
                <p>Gross Salary is the total earnings before deductions like PF, ESIC, and Income Tax.</p>
                <p><strong>Formula:</strong></p>
                <p>Gross Salary = **Basic + DA + HRA + Other Allowances**</p>

                <h5>8. Employee Provident Fund (EPF)</h5>
                <p>EPF is a government-mandated retirement savings scheme. **12% of Basic Salary** is deducted from the employee’s salary, and the employer also contributes 12%.</p>

                <h5>9. ESIC (Employee State Insurance)</h5>
                <p>ESIC is applicable for employees earning **₹21,000 or less** per month. Employee contribution is **0.75% of Gross Salary**, and employer contribution is **3.25%**.</p>

                <h5>10. Professional Tax (PT)</h5>
                <p>Professional Tax is levied by state governments and varies by state. In most states, it is **₹200 per month** for salaries above ₹15,000.</p>

                <h5>11. Income Tax</h5>
                <p>Income Tax is deducted based on the employee's total taxable income, as per government slabs.</p>

                <h5>12. Net Salary (Take-Home Salary)</h5>
                <p>Net Salary is the final salary credited to the employee’s bank account after all deductions.</p>
                <p><strong>Formula:</strong></p>
                <p>Net Salary = **Gross Salary - (EPF + ESIC + Professional Tax + Income Tax + Other Deductions)**</p>

                <h4>Example Calculation</h4>
                <p>Assuming an employee has a **Basic Salary of ₹30,000**, the breakdown would be:</p>
                <ul>
                    <li>HRA (40% of Basic) = ₹12,000</li>
                    <li>DA (15% of Basic) = ₹4,500</li>
                    <li>Conveyance Allowance = ₹1,600</li>
                    <li>Special Allowance = ₹5,000</li>
                    <li>Gross Salary = ₹53,100</li>
                    <li>EPF Deduction (12%) = ₹3,600</li>
                    <li>ESIC Deduction (0.75%) = ₹398.25</li>
                    <li>Professional Tax = ₹200</li>
                    <li>Net Salary = ₹48,901.75</li>
                </ul>

                <p>This is how an employee's salary is structured.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
        xhr.open('POST', 'get_office_details.php', true);
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
    
    document.addEventListener("DOMContentLoaded", function () {
    function calculateSalary() {
        let basic = parseFloat(document.getElementById("basic").value) || 0;
        let da = parseFloat(document.getElementById("da").value) || 0;
        let hra = (basic * 0.40).toFixed(2);  // HRA is 40% of Basic

        document.getElementById("hra").value = hra;
        let conveyance = Math.min(parseFloat(document.getElementById("conveyance").value) || 0, 1600);
        let specialAllowance = parseFloat(document.getElementById("special_allowance").value) || 0;
        let performanceBonus = parseFloat(document.getElementById("performance_bonus").value) || 0;
        let medicalAllowance = Math.min(parseFloat(document.getElementById("medical_allowance").value) || 0, 1250);
        let washingAllowance = parseFloat(document.getElementById("washing_allowance").value) || 0;
        let canteenAllowance = parseFloat(document.getElementById("canteen_allowance").value) || 0;
        let otherAllowances = parseFloat(document.getElementById("other_allowances").value) || 0;

        let grossSalary = basic + da + parseFloat(hra) + conveyance + specialAllowance + performanceBonus + medicalAllowance + washingAllowance + canteenAllowance + otherAllowances;
        document.getElementById("gross_salary").value = grossSalary.toFixed(2);

        // **Toggle Conditions for Employer Contributions**
        let includeEPF = document.getElementById("toggle_epf").checked;
        let includeESIC = document.getElementById("toggle_esic").checked;
        let includeGratuity = document.getElementById("toggle_gratuity").checked;

        let epfEmployer = includeEPF ? basic * 0.13 : 0; // 12% of Basic
        let esicEmployer = includeESIC && grossSalary <= 21000 ? grossSalary * 0.0325 : 0;  // 3.25% if salary ≤ ₹21,000
        let gratuity = includeGratuity ? basic * 0.0481 : 0;  // 4.81% of Basic

        let retentionBonus = parseFloat(document.getElementById("retention_bonus").value) || 0;
        let leaveEncashment = parseFloat(document.getElementById("leave_encashment").value) || 0;

        let totalCTC = grossSalary + epfEmployer + esicEmployer + gratuity + retentionBonus + leaveEncashment;

        document.getElementById("epf_employer").value = epfEmployer.toFixed(2);
        document.getElementById("esic_employer").value = esicEmployer.toFixed(2);
        document.getElementById("gratuity").value = gratuity.toFixed(2);
        document.getElementById("total_ctc").value = totalCTC.toFixed(2);

        let sick_leave = parseFloat(document.getElementById('sick_leave').value) || 0;
        let casual_leave = parseFloat(document.getElementById('casual_leave').value) || 0;
        let paid_leave = parseFloat(document.getElementById('paid_leave').value) || 0;
        let other_leave = parseFloat(document.getElementById('other_leave').value) || 0;

        let total_leave = sick_leave + casual_leave + paid_leave + other_leave;
        document.getElementById('total_leave').value = total_leave.toFixed(2);

        // **Deductions**
        let epfEmployee = includeEPF ? basic * 0.12 : 0;
        let esicEmployee = includeESIC && grossSalary <= 21000 ? grossSalary * 0.0075 : 0;
        let professionalTax = grossSalary > 15000 ? 200 : (grossSalary > 10000 ? 150 : 0);
        let incomeTax = parseFloat(document.getElementById("income_tax").value) || 0;
        let insurancePremium = parseFloat(document.getElementById("insurance_premium").value) || 0;
        let advance = parseFloat(document.getElementById("advance").value) || 0;
        let otherDeductions = parseFloat(document.getElementById("other_deductions").value) || 0;

        let totalDeductions = epfEmployee + esicEmployee + professionalTax + incomeTax + insurancePremium + advance + otherDeductions;

        document.getElementById("epf_employee").value = epfEmployee.toFixed(2);
        document.getElementById("professional_tax").value = professionalTax.toFixed(2);
        document.getElementById("total_deductions").value = totalDeductions.toFixed(2);

        // **Net Salary Calculation**
        let netSalary = grossSalary - totalDeductions;
        document.getElementById("net_salary").value = netSalary.toFixed(2);
    }

    // **Event Listeners for Calculation**
    document.querySelectorAll(".calculate, .toggle-switch").forEach(input => {
        input.addEventListener("input", calculateSalary);
    });
    // **Initial Calculation**
    calculateSalary();
});
</script>

<script>
    function checkDuplicate(field, value) {
        if (!value) return; // Skip validation if the field is empty

        $.ajax({
            url: 'duplicate_check.php',
            type: 'POST',
            data: {
                field: field,
                value: value
            },
            success: function(response) {
                const errorField = $(`#${field}Error`);
                const inputField = $(`#${field}`);

                if (response === 'exists') {
                    // Highlight the field and display the error message
                    inputField.addClass('border-danger');
                    errorField.text(`This ${field.replace('_', ' ')} is already taken.`);
                } else {
                    // Remove the error highlight and clear the message
                    inputField.removeClass('border-danger');
                    errorField.text('');
                }
            }
        });
    }
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<?php include("footer.php"); ?>