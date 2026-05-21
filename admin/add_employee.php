<?php
include("header.php");

function ensureEmployeeToggleColumns(mysqli $conn): void
{
    $toggleColumns = [
        'include_epf' => "TINYINT(1) NOT NULL DEFAULT 0",
        'include_pf_ceiling' => "TINYINT(1) NOT NULL DEFAULT 0",
        'include_esic' => "TINYINT(1) NOT NULL DEFAULT 0",
        'include_gratuity' => "TINYINT(1) NOT NULL DEFAULT 0",
        'include_pt' => "TINYINT(1) NOT NULL DEFAULT 0",
        'esic_employee' => "DECIMAL(10,2) NOT NULL DEFAULT 0.00",
        'gmc' => "DECIMAL(10,2) NOT NULL DEFAULT 0.00",
    ];

    foreach ($toggleColumns as $column => $definition) {
        $columnSafe = $conn->real_escape_string($column);
        $check = $conn->query("SHOW COLUMNS FROM employees LIKE '{$columnSafe}'");
        if ($check && $check->num_rows === 0) {
            $conn->query("ALTER TABLE employees ADD COLUMN {$column} {$definition}");
        }
    }
}

ensureEmployeeToggleColumns($conn);
// Handle add employee action
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_employee'])) {
        $employee_id = $_POST['employee_id'];
        $status = $_POST['status'];
        $name = $_POST['name'];
        $dob = $_POST['dob'];
        $anniversary = $_POST['anniversary'];
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $phone = $_POST['phone'];
        $email = $_POST['email'];
        $role = $_POST['role'];
        $father_name = $_POST['father_name'];
        $manager = $_POST['manager'];
        $address = $_POST['address'];
        $designation = $_POST['designation'];
        $salary_type = $_POST['salary_type'];
        $office = $_POST['office'];
        $latitude = $_POST['latitude'];
        $longitude = $_POST['longitude'];
        $punchin_time = $_POST['punchin_time'];
        $punchout_time = $_POST['punchout_time'];
        $break_time = $_POST['break_time'];
        $working_hours = $_POST['working_hours'];
        $hourly_salary = $_POST['hourly_salary'];
        $daily_salary = $_POST['daily_salary'];
        $date_of_joining = $_POST['date_of_joining'];
        $department = $_POST['department'];
        $emergency_contact = $_POST['emergency_contact'];
        $emergency_relation = $_POST['emergency_relation'];
        $bank_account = $_POST['bank_account'];
        $ifsc_code = $_POST['ifsc_code'];
        $adhar_number = $_POST['adhar_number'];
        $pan_number = $_POST['pan_number'];
        $epf_number = $_POST['epf_number'];
        $esic = $_POST['esic'];
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
        $leave_encashment = $_POST['leave_encashment'];
        $gratuity = $_POST['gratuity'];
        $gmc = $_POST['gmc'] ?? 0;
        $total_ctc = $_POST['total_ctc'];
        $epf_employee = $_POST['epf_employee'];
        $esic_employee = $_POST['esic_employee'] ?? 0;
        $professional_tax = $_POST['professional_tax'];
        $income_tax = $_POST['income_tax'];
        $insurance_premium = $_POST['insurance_premium'];
        $advance = $_POST['advance'];
        $other_deductions = $_POST['other_deductions'];
        $total_deductions = $_POST['total_deductions'];
        $net_salary = $_POST['net_salary'];
        

        $include_epf = isset($_POST['toggle_epf']) ? 1 : 0;
        $include_pf_ceiling = isset($_POST['toggle_pf_ceiling']) ? 1 : 0;
        $include_esic = isset($_POST['toggle_esic']) ? 1 : 0;
        $include_gratuity = isset($_POST['toggle_gratuity']) ? 1 : 0;
        $include_pt = isset($_POST['toggle_pt']) ? 1 : 0;


        // File uploads (Aadhaar, PAN, photo)
        $target_dir = "../uploads/";
        $photo_path = null;
        $adhar_path = null;
        $pan_path = null;
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
        // Insert employee data into the database
        $stmt = $conn->prepare("INSERT INTO employees (employee_id, status, name, dob, anniversary, password, phone, email, address, designation, role, father_name, manager, salary_type, office, latitude, longitude, punchin_time, punchout_time, break_time, working_hours, hourly_salary, daily_salary, date_of_joining, department, emergency_contact, emergency_relation, bank_account, ifsc_code, adhar_number, pan_number, epf_number, esic,  photo, adhar_file, pan_file, sick_leave, casual_leave, paid_leave, other_leave, total_leave, basic, da, hra, conveyance, special_allowance, performance_bonus, medical_allowance, washing_allowance, canteen_allowance,other_allowances,  gross_salary, epf_employer, esic_employer, retention_bonus,leave_encashment, gratuity,total_ctc,epf_employee,professional_tax,income_tax, insurance_premium, advance,other_deductions, total_deductions, net_salary) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssssssssssssssssssssssssssssssdddddddddddddddddddddddddddddd", $employee_id, $status, $name, $dob, $anniversary, $password, $phone, $email, $address, $designation, $role, $father_name, $manager, $salary_type, $office, $latitude, $longitude, $punchin_time, $punchout_time,  $break_time, $working_hours, $hourly_salary, $daily_salary, $date_of_joining, $department, $emergency_contact, $emergency_relation, $bank_account, $ifsc_code, $adhar_number, $pan_number, $epf_number, $esic, $photo_path, $adhar_path, $pan_path, $sick_leave, $casual_leave, $paid_leave, $other_leave, $total_leave, $basic, $da, $hra, $conveyance, $special_allowance, $performance_bonus, $medical_allowance, $washing_allowance, $canteen_allowance, $other_allowances, $gross_salary, $epf_employer, $esic_employer, $gratuity, $retention_bonus, $leave_encashment, $total_ctc, $epf_employee, $professional_tax, $income_tax, $insurance_premium, $advance, $other_deductions, $total_deductions, $net_salary);
        $stmt->execute();

        $toggleStmt = $conn->prepare("
            UPDATE employees
            SET include_epf = ?, include_pf_ceiling = ?, include_esic = ?, include_gratuity = ?, include_pt = ?, esic_employee = ?, gmc = ?
            WHERE employee_id = ?
        ");
        $toggleStmt->bind_param(
            "iiiiidds",
            $include_epf,
            $include_pf_ceiling,
            $include_esic,
            $include_gratuity,
            $include_pt,
            $esic_employee,
            $gmc,
            $employee_id
        );
        $toggleStmt->execute();
        $toggleStmt->close();
        echo "
        <div class='alert alert-success' style='position: fixed; top: 10px; right: 10px; z-index: 1000;'>
           Employee added successfully. Employee ID: $employee_id
        </div>
        <script>
            // Wait for 3 seconds and then redirect
            setTimeout(function() {
                window.location.href = 'manage_employee';
            }, 3000);
        </script>
        ";
    }
}
?>
<style>
.add-employee-shell {
    padding-bottom: 1.5rem;
}

.add-employee-card {
    border: 1px solid rgba(87, 96, 108, 0.12);
    border-radius: 24px;
    box-shadow: 0 16px 38px rgba(31, 41, 55, 0.07);
    background: #fff;
    overflow: hidden;
}

.add-employee-toolbar {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    padding: 1.2rem 1.45rem 0;
    margin-bottom: 0 !important;
}

.add-employee-card-body {
    padding: 0.75rem 1.45rem 1.5rem !important;
}

.employee-form-panel {
    padding: 0;
}

.add-employee-form-grid {
    row-gap: 0.1rem;
}

.employee-form-panel h6 {
    width: 100%;
    margin: 0 0 0.75rem !important;
    color: #111827;
    font-size: 1.15rem;
    font-weight: 800;
}

.employee-form-panel h5 {
    width: 100%;
    margin: 1.4rem 0 0.15rem !important;
    padding-top: 1.2rem;
    border-top: 1px solid #eef2f7;
    color: #111827;
    font-size: 1rem;
    font-weight: 800;
}

.employee-form-panel .form-label,
.employee-form-panel label:not(.form-check-label) {
    color: #6b7280;
    font-size: 0.76rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    margin-bottom: 0.45rem;
}

.employee-form-panel .form-control,
.employee-form-panel .form-select,
.employee-form-panel select.form-control {
    min-height: 48px;
    border-radius: 14px;
    border: 1px solid #d8dee7;
    color: #374151;
    box-shadow: none;
    padding: 0.72rem 0.95rem;
    background: #fff;
}

.employee-form-panel textarea.form-control {
    min-height: 110px;
    resize: vertical;
}

.employee-form-panel input[type="file"].form-control {
    min-height: 48px;
    padding-top: 0.62rem;
    padding-bottom: 0.62rem;
}

.employee-form-panel .form-control:focus,
.employee-form-panel .form-select:focus,
.employee-form-panel select.form-control:focus {
    border-color: #1e3a5f;
    box-shadow: 0 0 0 0.2rem rgba(30, 58, 95, 0.12);
}

.employee-form-panel .text-danger {
    font-size: 0.78rem;
}

.employee-form-panel .col-md-2,
.employee-form-panel .col-md-4,
.employee-form-panel .col-md-6,
.employee-form-panel .col-md-12 {
    margin-top: 1rem !important;
}

.employee-form-panel .row > .row {
    margin-top: 0;
}

.add-employee-toggle-grid {
    margin-top: 0.35rem;
    row-gap: 1rem;
}

.add-employee-toggle-grid > div {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 72px;
    padding: 0.72rem 0.82rem;
    border: 1px solid #e8edf3;
    border-radius: 16px;
    background: linear-gradient(180deg, #fbfcfe 0%, #f8fafc 100%);
}

.add-employee-toggle-grid .form-check.form-switch {
    margin: 0;
    padding-left: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.65rem;
}

.add-employee-toggle-grid .form-check-label {
    margin: 0;
    color: #475569;
    font-size: 0.78rem;
    font-weight: 700;
}

.add-employee-toggle-grid .form-check-input {
    width: 2.6rem;
    height: 1.35rem;
    margin: 0;
    float: none;
    border: none;
    box-shadow: none;
    background-color: #cbd5e1;
}

.add-employee-toggle-grid .form-check-input:checked {
    background-color: #16324f;
}

.add-employee-toggle-grid .btn {
    width: 100%;
    min-height: 40px;
    padding: 0.55rem 0.75rem;
}

.add-employee-toggle-grid .add-employee-btn-secondary {
    min-height: 36px;
    padding: 0.45rem 0.7rem;
    font-size: 0.8rem;
}

.add-employee-breakdown-card {
    align-items: center;
    justify-content: center !important;
}

.add-employee-breakdown-card .form-check {
    width: 100%;
    justify-content: center !important;
}

.add-employee-breakdown-btn {
    width: auto !important;
    min-width: 160px;
    max-width: 190px;
    min-height: 36px !important;
    padding: 0.45rem 0.9rem !important;
    font-size: 0.8rem !important;
}

@media (min-width: 768px) {
    .add-employee-toggle-grid {
        display: flex;
        flex-wrap: nowrap;
        gap: 0.7rem;
        align-items: stretch;
    }

    .add-employee-toggle-grid > div {
        flex: 1 1 0;
        max-width: none;
        width: auto;
        margin-top: 0 !important;
    }

    .add-employee-toggle-grid > .col-md-2 {
        flex: 1 1 0;
    }

    .add-employee-toggle-grid > .add-employee-breakdown-card {
        flex: 0 0 200px;
    }

    .add-employee-toggle-grid .form-check.form-switch {
        gap: 0.5rem;
    }

    .add-employee-toggle-grid .form-check-label {
        font-size: 0.76rem;
    }
}

.add-employee-btn-primary,
.add-employee-btn-dark,
.add-employee-shell #submitBtn,
.add-employee-shell .modal-footer .btn-primary,
.add-employee-shell .btn.btn-primary {
    background: linear-gradient(135deg, #161616 0%, #2d2d2d 100%) !important;
    color: #fff !important;
    border: none !important;
    box-shadow: 0 12px 24px rgba(17, 24, 39, 0.18);
}

.add-employee-btn-primary:hover,
.add-employee-btn-dark:hover,
.add-employee-shell #submitBtn:hover,
.add-employee-shell .modal-footer .btn-primary:hover,
.add-employee-shell .btn.btn-primary:hover {
    background: linear-gradient(135deg, #101010 0%, #242424 100%) !important;
    color: #fff !important;
}

.add-employee-btn-secondary,
.add-employee-shell .btn.btn-success {
    background: #16324f !important;
    color: #fff !important;
    border: 1px solid #16324f !important;
    box-shadow: none !important;
}

.add-employee-btn-secondary:hover,
.add-employee-shell .btn.btn-success:hover {
    background: #10263c !important;
    border-color: #10263c !important;
    color: #fff !important;
}

.add-employee-shell .btn.btn-secondary,
.add-employee-shell .btn-close {
    box-shadow: none !important;
}

.add-employee-toolbar .btn,
.employee-form-panel #submitBtn,
.add-employee-toggle-grid .btn {
    min-height: 44px;
    padding: 0.7rem 1.2rem;
    border-radius: 14px;
    font-size: 0.84rem;
    font-weight: 700;
}

.employee-form-panel #submitBtn {
    min-width: 180px;
}

.employee-form-panel .alert {
    border-radius: 14px;
}

#salaryBreakdownModal .modal-content,
#viewModal .modal-content {
    border: none;
    border-radius: 22px;
    box-shadow: 0 24px 52px rgba(15, 23, 42, 0.18);
}

#salaryBreakdownModal .modal-header,
#salaryBreakdownModal .modal-footer,
#viewModal .modal-header,
#viewModal .modal-footer {
    border-color: #eef2f7;
}

#salaryBreakdownModal .modal-body,
#viewModal .modal-body {
    color: #475569;
}

#salaryBreakdownModal h4,
#salaryBreakdownModal h5,
#viewModal h5 {
    color: #111827;
    font-weight: 800;
}

#viewModal .modal-dialog {
    max-width: 640px;
}

#viewModal .modal-header,
#viewModal .modal-footer {
    padding: 1.1rem 1.35rem;
}

#viewModal .modal-body {
    padding: 1.25rem 1.35rem 1.35rem;
}

.bulk-add-modal-stack {
    display: flex;
    flex-direction: column;
    gap: 1.15rem;
}

.bulk-add-section {
    border: 1px solid #edf2f7;
    border-radius: 18px;
    background: linear-gradient(180deg, #fbfcfe 0%, #f8fafc 100%);
    padding: 1rem 1rem 1.05rem;
}

.bulk-add-section-title {
    display: block;
    margin-bottom: 0.75rem;
    color: #6b7280;
    font-size: 0.74rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.bulk-add-download-form,
.bulk-add-upload-form {
    margin: 0;
}

.bulk-add-upload-form label {
    color: #374151;
    font-size: 0.86rem;
    font-weight: 700;
    margin-bottom: 0.55rem;
}

.bulk-add-upload-form .form-control {
    min-height: 48px;
    border-radius: 14px;
    border: 1px solid #d8dee7;
    box-shadow: none;
    padding: 0.7rem 0.9rem;
}

.bulk-add-upload-form .form-control:focus {
    border-color: #1e3a5f;
    box-shadow: 0 0 0 0.18rem rgba(30, 58, 95, 0.12);
}

.bulk-add-modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    margin-top: 1rem;
}

@media (max-width: 991.98px) {
    .add-employee-toolbar {
        padding: 1rem 1rem 0;
    }

    .add-employee-card-body {
        padding: 0.75rem 1rem 1.25rem !important;
    }
}

@media (max-width: 767.98px) {
    .add-employee-toolbar,
    .employee-form-panel #submitBtn {
        width: 100%;
    }

    .add-employee-toolbar .btn,
    .employee-form-panel #submitBtn,
    .add-employee-toggle-grid .btn {
        width: 100%;
    }

    .bulk-add-modal-actions {
        flex-direction: column;
    }

    .bulk-add-modal-actions .btn,
    .bulk-add-download-form .btn {
        width: 100%;
    }
}
</style>
<!-- HTML Form -->
<div class="container-fluid py-4 add-employee-shell">
    <div class="col-md-12 mb-lg-0 mb-4">
        <div class="card mt-4 add-employee-card">
            <div class="col-12 text-end add-employee-toolbar">
                <a href="javascript:void(0);" class="btn add-employee-btn-primary mb-0" data-bs-toggle="modal" data-bs-target="#viewModal">Bulk Add</a>
            </div>
            <div class="card-body p-2 add-employee-card-body">
                <form method="POST" enctype="multipart/form-data" id="employeeForm" class="employee-form-panel">
                    <div class="row add-employee-form-grid">
                        <h6 class="mb-0">On Board New Employee</h6>
                         <div class="col-md-4 mt-4">
             <label for="status" class="form-label">Select Status</label>
            <select class="form-control" name="status" id="status" required>
                <option value="Active">Active</option>
                <option value="Pending">Pending</option>            
                <option value="x_employee">X Employee</option>
            </select>
        </div>
                         <div class="col-md-4 mt-4">
    <label for="employee_id" class="form-label">Employee ID</label>
    <input class="form-control" type="text" name="employee_id" id="employee_id" placeholder="Employee ID" required oninput="this.value = this.value.toUpperCase();" onblur="checkEmployeeID(this.value)">
    <div id="employeeIdError" class="text-danger mt-1"></div>
</div>

                         
                        <div class="col-md-4 mt-4">
                            <label for="name" class="form-label">Full Name</label>
                            <input class="form-control" type="text" name="name" placeholder="Full Name" required>
                        </div>
                        <!-- Phone -->
                        <div class="col-md-4 mt-4">
                            <label for="phone" class="form-label">Phone</label>
                            <input class="form-control" type="text" name="phone" id="phone" onkeyup="checkDuplicate('phone', this.value)" placeholder="Phone" required>
                            <div id="phoneError" class="text-danger mt-1"></div>
                        </div>

                        <div class="col-md-4 mt-4">
                            <label for="email" class="form-label">Email (Optional)</label>
                            <input class="form-control" type="email" name="email" id="email" onkeyup="checkDuplicate('email', this.value)" placeholder="Email">
                            <div id="emailError" class="text-danger mt-1"></div>
                        </div>
                        <!-- Password -->
                        <div class="col-md-4 mt-4">
                            <label for="password" class="form-label">Password</label>
                            <input class="form-control" type="password" name="password" placeholder="Password" required>
                        </div>
                        <!-- dob -->
                        <div class="col-md-4 mt-4">
                            <label for="dob" class="form-label">Date Of Birth</label>
                            <input class="form-control" type="date" name="dob" placeholder="Date Of Birth" >
                        </div>

                        <div class="col-md-4 mt-4">
                            <label for="anniversary" class="form-label">Anniversary Date</label>
                            <input class="form-control" type="date" name="anniversary" placeholder="Anniversary Date">
                        </div>
                      <!-- Office -->
                      <div class="col-md-4 mt-4">
    <label for="office" class="form-label">Office</label>
    <select class="form-control" name="office" id="office" required onchange="fetchOfficeDetails(this.value)">
        <option value="">Select Office</option>
        <?php
        // Fetch offices from the database
        $stmt = $conn->prepare("SELECT office_name, state_name FROM offices ORDER BY office_name ASC");
        $stmt->execute();
        $result = $stmt->get_result();

        while ($office = $result->fetch_assoc()) {
            $value = $office['office_name'] . '_' . $office['state_name']; // Format: office_name_state_name
            echo "<option value='" . htmlspecialchars($value) . "'>" . htmlspecialchars($office['office_name']) . " (" . htmlspecialchars($office['state_name']) . ")</option>";
        }
        ?>
    </select>
</div>
    <input class="form-control" type="hidden" name="latitude" id="latitude" readonly >
    <input class="form-control" type="hidden" name="longitude" id="longitude" readonly >
                        <!-- Address -->
                        <div class="col-md-4 mt-4">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" name="address" placeholder="Address" rows="3" ></textarea>
                        </div>
                        <!-- Designation -->
                        <div class="col-md-4 mt-4">
                            <label for="designation" class="form-label">Designation</label>
                            <input class="form-control" type="text" name="designation" placeholder="Designation" >
                        </div>
                        <!-- Department -->
                        <div class="col-md-4 mt-4">
                            <label for="department" class="form-label">Department (Optional)</label>
                            <input class="form-control" type="text" name="department" placeholder="Department">
                        </div>
                        <div class="col-md-4 mt-4">
            <label for="role" class="form-label">Select Role</label>
            <select class="form-control" name="role" id="role" required>
                <option value="">Select Role</option>
                <option value="Manager">Manager/Supervisor </option>
                <option value="Employee">Employee</option>
            </select>
        </div>
        <!-- Select Manager -->
        <div class="col-md-4 mt-4">
            <label for="manager" class="form-label">Select Manager</label>
            <select class="form-control" name="manager" id="manager">
                <option value="">Select Manager</option>
                <?php
                // Fetch managers with role 'manager' and status 'Active'
                $stmt = $conn->prepare("SELECT id, name FROM employees WHERE role = 'Manager' AND status = 'Active' ORDER BY name ASC");
                $stmt->execute();
                $result = $stmt->get_result();

                while ($manager = $result->fetch_assoc()) {
                    echo "<option value='" . htmlspecialchars($manager['id']) . "'>" . htmlspecialchars($manager['name']) . "</option>";
                }
                ?>
            </select>
        </div>
                        <div class="col-md-4 mt-4">
                            <label for="date_of_joining" class="form-label">Date of Joining</label>
                            <input class="form-control" type="date" name="date_of_joining" >
                        </div>
                        <div class="col-md-4 mt-4">
                            <label for="father_name" class="form-label">Father Name</label>
                            <input class="form-control" type="text" name="father_name" placeholder=" father_name" >
                        </div>
                        <div class="col-md-4 mt-4">
                            <label for="bank_account" class="form-label">Bank Account</label>
                            <input class="form-control" type="text" name="bank_account" placeholder="Bank Account Details" >
                        </div>
                        <div class="col-md-4 mt-4">
                            <label for="ifsc_code" class="form-label">IFSC Code</label>
                            <input class="form-control" type="text" name="ifsc_code" placeholder="IFSC Code" >
                        </div>
                        <div class="col-md-4 mt-4">
                            <label for="adhar_number" class="form-label">Aadhaar Number</label>
                            <input class="form-control" type="text" name="adhar_number" id="adhar_number" onkeyup="checkDuplicate('adhar_number', this.value)" placeholder="Aadhaar Number" >
                            <div id="adharError" class="text-danger mt-1"></div>
                        </div>
                        <div class="col-md-4 mt-4">
                            <label for="pan_number" class="form-label">PAN Number (Optional)</label>
                            <input class="form-control" type="text" name="pan_number" id="pan_number" onkeyup="checkDuplicate('pan_number', this.value)" placeholder="PAN Number">
                            <div id="panError" class="text-danger mt-1"></div>
                        </div>
                        <div class="col-md-4 mt-3">
                            <label for="epf_number" class="form-label">Employee UAN (Optional)</label>
                            <input class="form-control" type="text" name="epf_number">
                        </div>
                        <div class="col-md-4 mt-4">
                            <label for="esic" class="form-label">ESIC No. (Optional)</label>
                            <input class="form-control" type="text" name="esic">
                        </div>
                        <div class="col-md-4 mt-4">
                            <label for="emergency_contact" class="form-label">Emergency Contact (Optional)</label>
                            <input class="form-control" type="text" name="emergency_contact">
                        </div>
                        <div class="col-md-4 mt-4">
                            <label for="emergency_relation" class="form-label">Relation With Emergency Contact </label>
                            <input class="form-control" type="text" name="emergency_relation">
                        </div>
                        <div class="col-md-4 mt-4">
                            <label for="photo" class="form-label">Photo (Optional)</label>
                            <input class="form-control" type="file" name="photo" accept=".jpg,.jpeg,.png,.pdf">
                        </div>
                        <div class="col-md-4 mt-4">
                            <label for="adhar_file" class="form-label">Upload Aadhaar (Optional)</label>
                            <input class="form-control" type="file" name="adhar_file" accept=".jpg,.jpeg,.png,.pdf">
                        </div>
                        <div class="col-md-4 mt-4">
                            <label for="pan_file" class="form-label">Upload PAN (Optional)</label>
                            <input class="form-control" type="file" name="pan_file" accept=".jpg,.jpeg,.png,.pdf">
                        </div>
                            <h5 class="mt-4">Employee Leave Structure</h5>
                            <div class="col-md-4">
                                <label for="sick_leave">Sick Leave</label>
                                <input type="number" name="sick_leave" id="sick_leave" class="form-control calculate" >
                            </div>
                            <div class="col-md-4">
                                <label for="casual_leave">Casual Leave</label>
                                <input type="number" name="casual_leave" id="casual_leave" class="form-control calculate">
                            </div>
                            <div class="col-md-4">
                                <label for="paid_leave">Paid Leave</label>
                                <input type="number" name="paid_leave" id="paid_leave" class="form-control calculate">
                            </div>
                            <div class="col-md-4">
                                <label for="other_leave">Other Leave</label>
                                <input type="number" name="other_leave" id="other_leave" class="form-control calculate">
                            </div>
                            <div class="col-md-4">
                                <label for="total_leave">Total Leave</label>
                                <input type="number" name="total_leave" id="total_leave" class="form-control calculate" readonly>
                            </div>
                        <div class="col-md-4">
                            <label for="salary_type" class="form-label">Salary Type</label>
                            <select class="form-control" name="salary_type" id="salary_type" required>
                                <option value="">Select Salary Type</option>
                                <option value="Monthly">Monthly</option>
                                <option value="Daily">Daily</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="salary_type" class="form-label">Punchin Time</label>
                            <input class="form-control" type="time" name="punchin_time" id="punchin_time" required>
                        </div>
                        <div class="col-md-4">
                            <label for="salary_type" class="form-label">Punchout Time</label>
                            <input class="form-control" type="time" name="punchout_time" id="punchout_time" required>
                        </div>
                        <div class="col-md-4">
                            <label for="salary_type" class="form-label">Break Time (in minutes)</label>
                            <input class="form-control" type="number" name="break_time" id="break_time" placeholder="Break Time (in minutes)" required>
                        </div>

                        <div class="col-md-4">
                            <label for="working_hours" class="form-label">Working Hours</label>
                            <input class="form-control" type="text" name="working_hours" id="working_hours" placeholder="Working Hours" readonly>
                        </div>
                        <div class="col-md-4">
                            <label for="hourly_salary" class="form-label">Hourly Salary</label>
                            <input class="form-control" type="text" name="hourly_salary" id="hourly_salary" placeholder="Hourly Salary" readonly>
                        </div>
                        <div class="col-md-4">
                            <label for="daily_salary" class="form-label">Daily Salary</label>
                            <input class="form-control" type="text" name="daily_salary" id="daily_salary" placeholder="Daily Salary" readonly>
                        </div>
                        <h5 class="mt-4">Do you want to include?</h5>
<div class="row add-employee-toggle-grid">
    <div class="col-md-2">
        <label>Include EPF?</label>
        <div class="form-check form-switch">
            <input class="form-check-input toggle-switch" type="checkbox" id="toggle_epf" name="toggle_epf" value="1">
            <label class="form-check-label" for="toggle_epf">Yes</label>
        </div>
    </div>
    <div class="col-md-2 d-none" id="pf_ceiling_wrapper">
        <label>PF Ceiling?</label>
        <div class="form-check form-switch">
            <input class="form-check-input toggle-switch" type="checkbox" id="toggle_pf_ceiling" name="toggle_pf_ceiling" value="1">
            <label class="form-check-label" for="toggle_pf_ceiling">Yes</label>
        </div>
    </div>
    <div class="col-md-2">
        <label>Include ESIC?</label>
        <div class="form-check form-switch">
            <input class="form-check-input toggle-switch" type="checkbox" id="toggle_esic" name="toggle_esic" value="1">
            <label class="form-check-label" for="toggle_esic">Yes</label>
        </div>
    </div>

    <div class="col-md-2">
        <label>Include Gratuity?</label>
        <div class="form-check form-switch">
            <input class="form-check-input toggle-switch" type="checkbox" id="toggle_gratuity" name="toggle_gratuity" value="1">
            <label class="form-check-label" for="toggle_gratuity">Yes</label>
        </div>
    </div>

    <div class="col-md-2">
        <label>Include PT?</label>
        <div class="form-check form-switch">
            <input class="form-check-input toggle-switch" type="checkbox" id="toggle_pt" name="toggle_pt" value="1">
            <label class="form-check-label" for="toggle_pt">Yes</label>
        </div>
    </div>

    <div class="col-md-2 add-employee-breakdown-card">
      
        <div class="form-check form-switch">
        <button type="button" class="btn add-employee-btn-secondary add-employee-breakdown-btn" data-bs-toggle="modal" data-bs-target="#salaryBreakdownModal">Salary Breakdown
</button>
        </div>
    </div>
</div>          




                            <h5 class="mt-4">Salary Structure</h5>
                            <div class="col-md-4">
                                <label for="basic">Basic Salary</label>
                                <input type="number" name="basic" id="basic" class="form-control calculate" value="<?= $calculated_salary ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="da">Dearness Allowance (DA)</label>
                                <input type="decimal" name="da" id="da" class="form-control calculate">
                            </div>
                            <div class="col-md-4">
                                <label for="hra">House Rent Allowance(HRA)</label>
                                <input type="number" step="0.01" name="hra" id="hra" class="form-control calculate">
                            </div>
                            <div class="col-md-4">
                                <label for="conveyance">Conveyance Allowance</label>
                                <input type="decimal" name="conveyance" id="conveyance" class="form-control calculate">
                            </div>
                            <div class="col-md-4">
                                <label for="special_allowance">Special Allowance</label>
                                <input type="decimal" name="special_allowance" id="special_allowance" class="form-control calculate" value="">
                            </div>
                            <div class="col-md-4">
                                <label for="performance_bonus">Bonus Advance</label>
                                <input type="decimal" name="performance_bonus" id="performance_bonus" class="form-control calculate" value="">
                            </div>
                            <div class="col-md-4">
                                <label for="medical_allowance">Medical Allowance</label>
                                <input type="decimal" name="medical_allowance" id="medical_allowance" class="form-control calculate" value="">
                            </div>
                            <div class="col-md-4">
                                <label for="washing_allowance">Washing Allowance</label>
                                <input type="decimal" name="washing_allowance" id="washing_allowance" class="form-control calculate" value="">
                            </div>
                            <div class="col-md-4">
                                <label for="canteen_allowance">Canteen Allowance</label>
                                <input type="decimal" name="canteen_allowance" id="canteen_allowance" class="form-control calculate" value="">
                            </div>
                            <div class="col-md-6">
                                <label for="other_allowances">Other Allowances</label>
                                <input type="decimal" name="other_allowances" id="other_allowances" class="form-control calculate" value="">
                            </div>
                            <div class="col-md-6">
                                <label for="gross_salary">Gross Salary</label>
                                <input type="decimal" name="gross_salary" id="gross_salary" class="form-control" readonly>
                            </div>
                 
                        <!-- Retentions -->
                        <h5 class="mt-4">Employer's Contributions</h5>
                        <div class="row">
                            <div class="col-md-4">
                                <label for="epf_employer">EPF Employer</label>
                                <input type="decimal" name="epf_employer" id="epf_employer" class="form-control calculate">
                            </div>
                            <div class="col-md-4">
                                <label for="esic_employer">ESIC Employer</label>
                                <input type="decimal" name="esic_employer" id="esic_employer" class="form-control calculate">
                            </div>
                            <div class="col-md-4">
                                <label for="gmc">GMC</label>
                                <input type="decimal" name="gmc" id="gmc" class="form-control calculate" value="">
                            </div>
                            <div class="col-md-4">
                                <label for="gratuity">Gratuity</label>
                                <input type="decimal" name="gratuity" id="gratuity" class="form-control calculate">
                            </div>
                            <div class="col-md-4">
                                <label for="retention_bonus">Retention Bonus</label>
                                <input type="decimal" name="retention_bonus" id="retention_bonus" class="form-control calculate" value="">
                            </div>
                            <div class="col-md-4">
                                <label for="leave_encashment">Leave Encashment</label>
                                <input type="decimal" name="leave_encashment" id="leave_encashment" class="form-control calculate" value="">
                            </div>
                            <div class="col-md-4">
                                <label for="total_ctc">Cost to Company(CTC)</label>
                                <input type="decimal" name="total_ctc" id="total_ctc" class="form-control" readonly>
                            </div>
                        </div>
                        <h5 class="mt-4">Employee's Contributions</h5>                      
                            <div class="col-md-4">
                                <label for="epf_employee">Employee Provident Fund (EPF)</label>
                                <input type="decimal" name="epf_employee" id="epf_employee" class="form-control calculate">
                            </div>
                            <div class="col-md-4">
                                <label for="esic_employee">ESIC Employee</label>
                                <input type="decimal" name="esic_employee" id="esic_employee" class="form-control calculate">
                            </div>
                            <div class="col-md-4">
                                <label for="professional_tax">Professional Tax (PT)</label>
                                
                                <input type="decimal" name="professional_tax" id="professional_tax" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label for="income_tax">Income Tax (TDS)</label>
                                <input type="decimal" name="income_tax" id="income_tax" class="form-control calculate">
                            </div>
                            <div class="col-md-4">
                                <label for="insurance_premium">Insurance Premium</label>
                                <input type="decimal" name="insurance_premium" id="insurance_premium" class="form-control calculate" value="">
                            </div>
                            <div class="col-md-4">
                                <label for="advance">Advance</label>
                                <input type="decimal" name="advance" id="advance" class="form-control calculate" value="">
                            </div>
                            <div class="col-md-4">
                                <label for="other_deductions">Other Deductions</label>
                                <input type="decimal" name="other_deductions" id="other_deductions" class="form-control calculate" value="">
                            </div>
                        <div class="col-md-4">
                            <label for="total_deductions">Total Deductions</label>
                            <input type="decimal" name="total_deductions" id="total_deductions" class="form-control" readonly>
                        </div>
                        <div class="col-md-4">
                            <label for="net_salary">Net Salary (In-Hand)</label>
                            <input type="decimal" name="net_salary" id="net_salary" class="form-control" readonly>
                        </div>
                    </div>
                    <div class="col-md-12 mt-4">
                        <button id="submitBtn" class="btn add-employee-btn-primary mb-0" type="submit" name="add_employee">Add Employee</button>
                    </div>
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
                <h5 class="modal-title">Salary Structure Breakdown</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <h4>Understanding the Salary Components</h4>
                <p>This salary structure is calculated as per company policy and statutory rules.</p>

                <h5>1. Basic Salary</h5>
                <p>
                    Basic salary forms the core component of the salary structure.
                    It is used to calculate EPF and other statutory benefits.
                </p>

                <h5>2. House Rent Allowance (HRA)</h5>
                <p>
                    HRA is provided towards house rent expenses and is decided as per company policy.
                </p>

                <h5>3. Conveyance Allowance</h5>
                <p>
                    Fixed allowance for travel-related expenses.
                </p>

                <h5>4. Bonus / Advance</h5>
                <p>
                    Performance-based or adjustment-based monthly bonus.
                </p>

                <h5>5. Special Allowance</h5>
                <p>
                    Flexible taxable allowance used to balance salary.
                </p>

                <h5>6. Washing Allowance</h5>
                <p>
                    Fixed allowance for uniform or maintenance expenses.
                </p>

                <h5>7. Gross Salary (A)</h5>
                <p><strong>Formula:</strong></p>
                <p>
                    Gross = Basic + HRA + Conveyance + Bonus +
                    Special Allowance + Washing Allowance
                </p>

                <h5>8. Employer Contributions (Benefits – B)</h5>
                <ul>
                    <li>
                        <strong>EPF (Employer):</strong>
                        <ul>
                            <li>If PF Ceiling OFF → 13% of Basic Salary</li>
                            <li>
                                If PF Ceiling ON → 13% of 
                                (Gross − HRA − Bonus − Washing Allowance), capped at ₹15,000
                            </li>
                        </ul>
                    </li>

                    <li>
                        <strong>ESIC (Employer):</strong>
                        <ul>
                            <li>Applicable only if Gross ≤ ₹21,000</li>
                            <li>ESIC Wages = Gross − Washing Allowance</li>
                            <li>Employer Contribution = 3.25% of ESIC Wages</li>
                        </ul>
                    </li>

                    <li>
                        <strong>GMC (Group Medical Coverage):</strong>
                        Company-paid medical insurance provided to employees.
                        This is a fixed employer cost and included in CTC.
                    </li>

                    <li>
                        <strong>Gratuity:</strong>
                        Statutory benefit provided as per company policy.
                    </li>
                </ul>

                <p>
                    <strong>Total Benefits (B)</strong> include EPF, ESIC, GMC, Gratuity and other employer contributions,
                    which are added to Gross Salary to calculate CTC.
                </p>

                <h5>9. Cost to Company (CTC)</h5>
                <p><strong>Formula:</strong> CTC = Gross (A) + Benefits (B)</p>

                <h5>10. Deductions (C)</h5>
                <ul>
                    <li>
                        <strong>EPF (Employee):</strong>
                        <ul>
                            <li>If PF Ceiling OFF → 12% of Basic Salary</li>
                            <li>
                                If PF Ceiling ON → 12% of 
                                (Gross − HRA − Bonus − Washing Allowance), capped at ₹15,000
                            </li>
                        </ul>
                    </li>

                    <li>
                        <strong>ESIC (Employee):</strong>
                        <ul>
                            <li>Applicable only if Gross ≤ ₹21,000</li>
                            <li>0.75% of (Gross − Washing Allowance)</li>
                        </ul>
                    </li>

                    <li>
                        <strong>Professional Tax:</strong>
                        ₹0 / ₹125 / ₹200 based on salary slab
                    </li>

                    <li>
                        <strong>Income Tax:</strong>
                        Applicable as per income tax rules.
                    </li>
                </ul>

                <h5>11. Net Salary (In-Hand)</h5>
                <p><strong>Formula:</strong></p>
                <p>
                    Net Salary = Gross Salary − Total Deductions
                </p>

                <h4>Example</h4>
                <ul>
                    <li>Gross Salary: ₹20,000</li>
                    <li>Washing Allowance: ₹1,000</li>
                    <li>ESIC Wages: ₹19,000</li>
                    <li>ESIC Employee: ₹142.5</li>
                    <li>ESIC Employer: ₹617.5</li>
                </ul>

                <p>This structure ensures transparency between earnings, deductions, and company cost.</p>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- View Details Modal -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewModalLabel">Add Bulk of employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="bulk-add-modal-stack">
                    <div class="bulk-add-section">
                        <span class="bulk-add-section-title">Download Template</span>
                        <form method="GET" action="csv" class="bulk-add-download-form">
                            <button type="submit" name="download_csv_format" class="btn btn-success">Download CSV Format</button>
                        </form>
                    </div>

                    <div class="bulk-add-section">
                        <span class="bulk-add-section-title">Upload File</span>
                        <form method="POST" action="csv" enctype="multipart/form-data" class="bulk-add-upload-form">
                            <div class="mb-0">
                                <label for="csv_file">Upload CSV File:</label>
                                <input type="file" name="csv_file" class="form-control" required>
                            </div>

                            <div class="bulk-add-modal-actions">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" name="upload_csv" class="btn btn-primary">Upload</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes blinkBorder {
  0%   { border-color: red; }
  50%  { border-color: transparent; }
  100% { border-color: red; }
}

.blink-red {
  animation: blinkBorder 0.5s ease-in-out 3;
  border: 2px solid red !important;
}

</style>
<?php include("footer.php") ?>
<script>
function checkEmployeeID(employeeId) {
    if (employeeId.trim() === '') return;

    fetch('check_employee_id?employee_id=' + employeeId)
        .then(response => response.json())
        .then(data => {
            const input = document.getElementById('employee_id');
            const errorDiv = document.getElementById('employeeIdError');

            if (data.exists) {
                errorDiv.textContent = "This Employee ID already exists.";
                input.classList.add('blink-red');
                input.value = '';
                setTimeout(() => {
                    input.classList.remove('blink-red');
                }, 1500);
            } else {
                errorDiv.textContent = '';
                input.classList.remove('blink-red');
            }
        })
        .catch(error => {
            console.error('Error checking Employee ID:', error);
        });
}
</script>

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

    function disableSubmitButton() {
        var submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting...'; // Optional: change the button text
    }
</script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    ["toggle_epf", "toggle_pf_ceiling", "toggle_esic", "toggle_gratuity", "toggle_pt"].forEach(id => {
        const toggle = document.getElementById(id);
        if (toggle) {
            toggle.checked = false;
        }
    });

    const epfToggle = document.getElementById("toggle_epf");
    const pfCeilingToggle = document.getElementById("toggle_pf_ceiling");
    const pfCeilingWrapper = document.getElementById("pf_ceiling_wrapper");

    // Flags to detect manual overrides
    let manualOverride = {
        hra: false,
        gross_salary: false,
        total_ctc: false,
        total_deductions: false,
        net_salary: false
    };

    function syncPfCeilingVisibility() {
        if (!epfToggle || !pfCeilingWrapper || !pfCeilingToggle) {
            return;
        }

        if (epfToggle.checked) {
            pfCeilingWrapper.classList.remove("d-none");
        } else {
            pfCeilingWrapper.classList.add("d-none");
            pfCeilingToggle.checked = false;
        }
    }

    function calculateSalary() {

        let basic = parseFloat(document.getElementById("basic").value) || 0;
        let da = parseFloat(document.getElementById("da").value) || 0;
        let basicVDA = basic + da;

        // ---------- HRA ----------
        let hra = parseFloat(document.getElementById("hra").value) || 0;

        // ---------- ALLOWANCES ----------
        let conveyance = parseFloat(document.getElementById("conveyance").value) || 0;
        let performanceBonus = parseFloat(document.getElementById("performance_bonus").value) || 0;
        let specialAllowance = parseFloat(document.getElementById("special_allowance").value) || 0;
        let washingAllowance = parseFloat(document.getElementById("washing_allowance").value) || 0;
        let medicalAllowance = parseFloat(document.getElementById("medical_allowance").value) || 0;
        let canteenAllowance = parseFloat(document.getElementById("canteen_allowance").value) || 0;
        let otherAllowances = parseFloat(document.getElementById("other_allowances").value) || 0;

        // ---------- GROSS (A) ----------
        let grossSalary =
            basicVDA + hra + conveyance + performanceBonus +
            specialAllowance + washingAllowance +
            medicalAllowance + canteenAllowance + otherAllowances;

        if (!manualOverride.gross_salary) {
            document.getElementById("gross_salary").value = grossSalary.toFixed(2);
        }

        // ---------- TOGGLES ----------
        let includeEPF = document.getElementById("toggle_epf").checked;
        let includePFCeiling = document.getElementById("toggle_pf_ceiling").checked;
        let includeGratuity = document.getElementById("toggle_gratuity").checked;
        let includeESIC = document.getElementById("toggle_esic").checked;
        let includePT = document.getElementById("toggle_pt").checked;

        // ---------- EMPLOYER BENEFITS (B) ----------
        let pfCeilingBase = Math.max(0, grossSalary - hra - performanceBonus - washingAllowance);
        let epfEmployeeBase = includePFCeiling ? Math.min(pfCeilingBase, 15000) : basic;
        let esicBase = Math.max(0, grossSalary - washingAllowance);

        let epfEmployer = includeEPF
            ? basic * 0.13
            : 0;

        let epfEmployee = includeEPF
            ? epfEmployeeBase * 0.12
            : 0;

        let gratuity = includeGratuity
            ? Math.round((basicVDA * 15) / (26 * 12))
            : 0;

        let esicEmployer = includeESIC && grossSalary <= 21000
            ? esicBase * 0.0325
            : 0;

        let esicEmployee = includeESIC && grossSalary <= 21000
            ? esicBase * 0.0075
            : 0;

        document.getElementById("epf_employer").value = epfEmployer.toFixed(2);
        document.getElementById("gratuity").value = gratuity.toFixed(2);
        document.getElementById("esic_employer").value = esicEmployer.toFixed(2);
        document.getElementById("esic_employee").value = esicEmployee.toFixed(2);

        let retentionBonus = parseFloat(document.getElementById("retention_bonus").value) || 0;
        let leaveEncashment = parseFloat(document.getElementById("leave_encashment").value) || 0;
        let gmc = parseFloat(document.getElementById("gmc").value) || 0;

        let totalCTC =
            grossSalary + epfEmployer + esicEmployer + gratuity + gmc +
            retentionBonus + leaveEncashment;

        if (!manualOverride.total_ctc) {
            document.getElementById("total_ctc").value = totalCTC.toFixed(2);
        }
        
        // ---------- LEAVE CALCULATION ----------
        let total_leave = ["sick_leave", "casual_leave", "paid_leave", "other_leave"].reduce((sum, id) => {
            return sum + (parseFloat(document.getElementById(id).value) || 0);
        }, 0);

        document.getElementById("total_leave").value = total_leave;

        // ---------- DEDUCTIONS (C) ----------
        let professionalTax = 0;

        if (includePT) {
            if (grossSalary >= 25000) {
                professionalTax = 200;
            } else if (grossSalary > 13305) {
                professionalTax = 125;
            }
        }

        let incomeTax = parseFloat(document.getElementById("income_tax").value) || 0;
        let insurancePremium = parseFloat(document.getElementById("insurance_premium").value) || 0;
        let advance = parseFloat(document.getElementById("advance").value) || 0;
        let otherDeductions = parseFloat(document.getElementById("other_deductions").value) || 0;

        document.getElementById("epf_employee").value = epfEmployee.toFixed(2);

        let ptField = document.getElementById("professional_tax");

        

        let professionalTaxValue = parseFloat(ptField.value) || 0;

        if (!includePT) {
            ptField.value = 0;
            ptField.dataset.auto = "true";
        } else {
            if (ptField.dataset.auto === "true") {
                ptField.value = professionalTax.toFixed(2);
            }
        }

        //  ALWAYS take latest value from field
        let finalPT = parseFloat(ptField.value) || 0;

        let totalDeductions =
            epfEmployee + esicEmployee + finalPT +
            incomeTax + insurancePremium +
            advance + otherDeductions;

        if (!manualOverride.total_deductions) {
            document.getElementById("total_deductions").value = totalDeductions.toFixed(2);
        }

        // ---------- NET SALARY ----------
        let netSalary = grossSalary - totalDeductions;
        if (!manualOverride.net_salary) {
            document.getElementById("net_salary").value = netSalary.toFixed(2);
        }
    }

    // Trigger calculation
    document.querySelectorAll(".calculate").forEach(input => {
        input.addEventListener("input", calculateSalary);
    });

        document.querySelectorAll(".toggle-switch").forEach(toggle => {
        toggle.addEventListener("change", calculateSalary);
    });

    if (epfToggle) {
        epfToggle.addEventListener("change", function () {
            syncPfCeilingVisibility();
            calculateSalary();
        });
    }

    if (pfCeilingToggle) {
        pfCeilingToggle.addEventListener("change", calculateSalary);
    }

    document.getElementById("toggle_pt").addEventListener("change", function () {
        let ptField = document.getElementById("professional_tax");

        if (this.checked) {
            ptField.dataset.auto = "true";
        }
    });

    // Mark field as manually overridden when user edits
    ["hra", "gross_salary", "total_ctc", "total_deductions", "net_salary"].forEach(id => {
        const input = document.getElementById(id);
        input.addEventListener("input", () => {
            manualOverride[id] = true;
        });
    });

    document.getElementById("professional_tax").addEventListener("input", function () {
        this.dataset.auto = "false";

        //  Recalculate immediately when user edits PT
        calculateSalary();
    });

    let ptField = document.getElementById("professional_tax");

    //  Initialize only once (CORRECT PLACE)
    
    if (ptField.value && parseFloat(ptField.value) > 0) {
        ptField.dataset.auto = "false";
    } else {
        // ADD mode OR empty
        ptField.dataset.auto = "true";
    }

    syncPfCeilingVisibility();
    calculateSalary();
});
</script>


<script>
    function checkDuplicate(field, value) {
        if (!value) return; // Skip validation if the field is empty

        $.ajax({
            url: 'duplicate_check',
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
