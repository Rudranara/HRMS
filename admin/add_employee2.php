<?php
include("header.php");
// Function to generate Employee ID
function generateEmployeeID($conn)
{
    $prefix = "GCPL";
    // Get the maximum existing employee ID from the database
    $stmt = $conn->prepare("SELECT MAX(employee_id) AS max_id FROM employees WHERE employee_id LIKE ?");
    $likePrefix = $prefix . "%";
    $stmt->bind_param("s", $likePrefix);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    if ($row['max_id']) {
        // Extract the numeric part from the max_id (e.g., GCPL0004 -> 4)
        $lastNumber = (int)substr($row['max_id'], strlen($prefix));
        $nextNumber = $lastNumber + 1;
    } else {
        // If no records exist, start with 1
        $nextNumber = 1;
    }
    // Format the number with leading zeros (e.g., 1 -> 0001)
    $formattedNumber = str_pad($nextNumber, 4, "0", STR_PAD_LEFT);
    // Combine the prefix and the formatted number
    $employeeID = $prefix . $formattedNumber;
    return $employeeID;
}
// Handle add employee action
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_employee'])) {
        $employee_id = generateEmployeeID($conn);
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
        $total_ctc = $_POST['total_ctc'];
        $epf_employee = $_POST['epf_employee'];
        $professional_tax = $_POST['professional_tax'];
        $income_tax = $_POST['income_tax'];
        $insurance_premium = $_POST['insurance_premium'];
        $advance = $_POST['advance'];
        $other_deductions = $_POST['other_deductions'];
        $total_deductions = $_POST['total_deductions'];
        $net_salary = $_POST['net_salary'];
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
        $stmt = $conn->prepare("INSERT INTO employees (employee_id, name, dob, anniversary, password, phone, email, address, designation, role, father_name, manager, salary_type, office, latitude, longitude, punchin_time, punchout_time, break_time, working_hours, hourly_salary, daily_salary, date_of_joining, department, emergency_contact, emergency_relation, bank_account, ifsc_code, adhar_number, pan_number, epf_number, esic,  photo, adhar_file, pan_file, sick_leave, casual_leave, paid_leave, other_leave, total_leave, basic, da, hra, conveyance, special_allowance, performance_bonus, medical_allowance, washing_allowance, canteen_allowance,other_allowances,  gross_salary, epf_employer, esic_employer, retention_bonus,leave_encashment, gratuity,total_ctc,epf_employee,professional_tax,income_tax, insurance_premium, advance,other_deductions, total_deductions, net_salary) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssssssssssssssssssssssssssssssdddddddddddddddddddddddddddddd", $employee_id, $name, $dob, $anniversary, $password, $phone, $email, $address, $designation, $role, $father_name, $manager, $salary_type, $office, $latitude, $longitude, $punchin_time, $punchout_time,  $break_time, $working_hours, $hourly_salary, $daily_salary, $date_of_joining, $department, $emergency_contact, $emergency_relation, $bank_account, $ifsc_code, $adhar_number, $pan_number, $epf_number, $esic, $photo_path, $adhar_path, $pan_path, $sick_leave, $casual_leave, $paid_leave, $other_leave, $total_leave, $basic, $da, $hra, $conveyance, $special_allowance, $performance_bonus, $medical_allowance, $washing_allowance, $canteen_allowance, $other_allowances, $gross_salary, $epf_employer, $esic_employer, $gratuity, $retention_bonus, $leave_encashment, $total_ctc, $epf_employee, $professional_tax, $income_tax, $insurance_premium, $advance, $other_deductions, $total_deductions, $net_salary);
        $stmt->execute();
        echo "
        <div class='alert alert-success' style='position: fixed; top: 10px; right: 10px; z-index: 1000;'>
           Employee added successfully. Employee ID: $employee_id
        </div>
        <script>
            // Wait for 3 seconds and then redirect
            setTimeout(function() {
                window.location.href = 'manage_employee.php';
            }, 3000);
        </script>
        ";
    }
}
?>

<!-- HTML Form -->
<div class="container-fluid py-4">
    <div class="col-md-12 mb-lg-0 mb-4">
        <div class="card mt-4">
            <div class="col-12 mb-4 text-end">
                <a href="javascript:void(0);" class="btn bg-gradient-dark mb-0" data-bs-toggle="modal" data-bs-target="#viewModal">Bulk Add</a>
            </div>
            <div class="card-body p-2">
                <form method="POST" enctype="multipart/form-data" id="employeeForm">
                    <div class="row">
                        <h6 class="mb-0">On Board New Employee</h6>
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
                $stmt = $conn->prepare("SELECT id, name FROM employees WHERE status = 'Active' AND  role = 'Manager' AND status = 'Active' ORDER BY name ASC");
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
                       
                            <h5 class="mt-4">Salary Structure</h5>
                            <div class="col-md-4">
                                <label for="basic">Basic Salary</label>
                                <input type="number" name="basic" id="basic" class="form-control calculate" value="<?= $calculated_salary ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="da">Dearness Allowance (DA)</label>
                                <input type="number" name="da" id="da" class="form-control calculate">
                            </div>
              
                     
                            <div class="col-md-4">
                                <label for="hra">House Rent Allowance (HRA)</label>
                                <input type="number" name="hra" id="hra" class="form-control calculate">
                            </div>
                            <div class="col-md-4">
                                <label for="conveyance">Conveyance Allowance</label>
                                <input type="number" name="conveyance" id="conveyance" class="form-control calculate">
                            </div>
                 

               
                            <div class="col-md-4">
                                <label for="special_allowance">Special Allowance</label>
                                <input type="number" name="special_allowance" id="special_allowance" class="form-control calculate" value="">
                            </div>
                            <div class="col-md-4">
                                <label for="performance_bonus">Performance Bonus</label>
                                <input type="number" name="performance_bonus" id="performance_bonus" class="form-control calculate" value="">
                            </div>
             
                
                            <div class="col-md-4">
                                <label for="medical_allowance">Medical Allowance</label>
                                <input type="number" name="medical_allowance" id="medical_allowance" class="form-control calculate" value="">
                            </div>
                            <div class="col-md-4">
                                <label for="washing_allowance">Washing Allowance</label>
                                <input type="number" name="washing_allowance" id="washing_allowance" class="form-control calculate" value="">
                            </div>
                   
                     
                            <div class="col-md-4">
                                <label for="canteen_allowance">Canteen Allowance</label>
                                <input type="number" name="canteen_allowance" id="canteen_allowance" class="form-control calculate" value="">
                            </div>
                            <div class="col-md-6">
                                <label for="other_allowances">Other Allowances</label>
                                <input type="number" name="other_allowances" id="other_allowances" class="form-control calculate" value="">
                            </div>
         

                        
                            <div class="col-md-6">
                                <label for="gross_salary">Gross Salary</label>
                                <input type="number" name="gross_salary" id="gross_salary" class="form-control" readonly>
                            </div>
                 
                        <!-- Retentions -->
                        <h5 class="mt-4">Employer's Contributions</h5>
                        <div class="row">
                            <div class="col-md-4">
                                <label for="epf_employer">EPF Employer</label>
                                <input type="number" name="epf_employer" id="epf_employer" class="form-control calculate">
                            </div>
                            <div class="col-md-4">
                                <label for="esic_employer">ESIC Employer</label>
                                <input type="number" name="esic_employer" id="esic_employer" class="form-control calculate">
                            </div>
                            <div class="col-md-4">
                                <label for="gratuity">Gratuity</label>
                                <input type="number" name="gratuity" id="gratuity" class="form-control calculate">
                            </div>


                            <div class="col-md-4">
                                <label for="retention_bonus">Retention Bonus</label>
                                <input type="number" name="retention_bonus" id="retention_bonus" class="form-control calculate" value="">
                            </div>

                            <div class="col-md-4">
                                <label for="leave_encashment">Leave Encashment</label>
                                <input type="number" name="leave_encashment" id="leave_encashment" class="form-control calculate" value="">
                            </div>
                            <div class="col-md-4">
                                <label for="total_ctc">Total Retentions (CTC)</label>
                                <input type="number" name="total_ctc" id="total_ctc" class="form-control" readonly>
                            </div>
                        </div>

                        <h5 class="mt-4">Deductions</h5>
                       
                            <div class="col-md-4">
                                <label for="epf_employee">Employee Provident Fund (EPF)</label>
                                <input type="number" name="epf_employee" id="epf_employee" class="form-control calculate">
                            </div>
                            <div class="col-md-4">
                                <label for="professional_tax">Professional Tax (PT)</label>
                                <input type="number" name="professional_tax" id="professional_tax" class="form-control calculate">
                            </div>
                                  
                            <div class="col-md-4">
                                <label for="income_tax">Income Tax (TDS)</label>
                                <input type="number" name="income_tax" id="income_tax" class="form-control calculate">
                            </div>
                            <div class="col-md-4">
                                <label for="insurance_premium">Insurance Premium</label>
                                <input type="number" name="insurance_premium" id="insurance_premium" class="form-control calculate" value="">
                            </div>
              
                        
                            <div class="col-md-4">
                                <label for="advance">Advance</label>
                                <input type="number" name="advance" id="advance" class="form-control calculate" value="">
                            </div>
                            <div class="col-md-4">
                                <label for="other_deductions">Other Deductions</label>
                                <input type="number" name="other_deductions" id="other_deductions" class="form-control calculate" value="">
                            </div>
                     
                        <div class="col-md-4">
                            <label for="total_deductions">Total Deductions</label>
                            <input type="number" name="total_deductions" id="total_deductions" class="form-control" readonly>
                        </div>
                        <div class="col-md-4">
                            <label for="net_salary">Net Salary (In-Hand)</label>
                            <input type="number" name="net_salary" id="net_salary" class="form-control" readonly>
                        </div>
                    </div>
                    <div class="col-md-12 mt-4">
                        <button id="submitBtn" class="btn bg-gradient-dark mb-0" type="submit" name="add_employee">Add Employee</button>
                    </div>
            </div>
            </form>
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


                <div class="modal-header">
                    <form method="GET" action="csv.php">
                        <button type="submit" name="download_csv_format" class="btn btn-success">Download CSV Format</button>
                    </form>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="csv.php" enctype="multipart/form-data">
                    <div class="modal-body">

                        <div class="mb-3">
                            <label for="csv_file">Upload CSV File:</label>
                            <input type="file" name="csv_file" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="upload_csv" class="btn btn-primary">Upload</button>
                    </div>
                </form>


            </div>
        </div>
    </div>
</div>
<?php include("footer.php") ?>
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

    function disableSubmitButton() {
        var submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting...'; // Optional: change the button text
    }
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