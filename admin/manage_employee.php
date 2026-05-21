<?php
include("db_connection.php");

function ensureAutoPunchoutColumn(mysqli $conn): void
{
    $check = $conn->query("SHOW COLUMNS FROM employees LIKE 'disable_auto_punchout'");
    if ($check && $check->num_rows === 0) {
        $conn->query("ALTER TABLE employees ADD COLUMN disable_auto_punchout TINYINT(1) NOT NULL DEFAULT 0");
    }
}

ensureAutoPunchoutColumn($conn);

if (isset($_GET['delete'])) {
    $employee_id = $_GET['delete'];
    try {
        // Attempt to delete the employee
        $stmt = $conn->prepare("DELETE FROM employees WHERE employee_id = ?");
        $stmt->bind_param("s", $employee_id);
        $stmt->execute();

        echo " <div class='alert alert-success' style='position: fixed; top: 10px; right: 10px; z-index: 1000;'>Employee deleted successfully.</div>";
    } catch (mysqli_sql_exception $e) {
        // Check if the error is caused by a foreign key constraint
        if ($e->getCode() == 1451) { // Error code 1451 indicates foreign key constraint violation

            echo " <div class='alert alert-success' style='position: fixed; top: 10px; right: 10px; z-index: 1000;'>
                The selected employee cannot be deleted because their data exists in other tables. 
                Please delete the related data first.
            </div>";
        } else {
            // For other database errors, display a generic error message
            echo " <div class='alert alert-success' style='position: fixed; top: 10px; right: 10px; z-index: 1000;'>An error occurred while deleting the employee. Please try again.</div>";
        }
    }
}
// Handle add attendance
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_attendance'])) {
    $employee_id = $_POST['employee_id'];
    $attendance_date = $_POST['attendance_date'];
    $status = $_POST['status'];
    $stmt = $conn->prepare("INSERT INTO attendance (employee_id, attendance_date, status) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $employee_id, $attendance_date, $status);
    $stmt->execute();
    echo "<div class='alert alert-success'>Attendance added successfully.</div>";
}

// Handle filtering by office
$search = isset($_GET['search']) ? $_GET['search'] : '';
$filter_office = isset($_GET['office']) ? $_GET['office'] : '';

$query = "SELECT * FROM employees WHERE status = 'Active' AND (name LIKE '%$search%' OR employee_id LIKE '%$search%' OR role LIKE '%$search%')";
if (!empty($filter_office)) {
    $query .= " AND office = '$filter_office'";
}
$result = $conn->query($query);
$employees = $result->fetch_all(MYSQLI_ASSOC);

// Handle CSV download
if (isset($_GET['download_csv'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="employees.csv"');
    $output = fopen("php://output", "w");
    fputcsv($output, ["Name", "Employee ID", "Designation", "Office", "Salary", "Join Date"]);

    foreach ($employees as $employee) {
        fputcsv($output, [
            $employee['name'],
            $employee['employee_id'],
            $employee['designation'],
            $employee['office'],
            $employee['net_salary'],
            $employee['date_of_joining'],
        ]);
    }
    fclose($output);
    exit;
}

// Handle CSV download
if (isset($_GET['download_csv2'])) {

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="employees_full.csv"');

    $output = fopen("php://output", "w");

    // CSV Header Row
    fputcsv($output, [
        "S.No",
        "Employee ID", "Status", "Name", "DOB", "Anniversary", "Password",
        "Phone", "Email", "Address", "Designation", "Role", "Father Name",
        "Manager", "Salary Type", "Office", "Latitude", "Longitude",
        "Punch In Time", "Punch Out Time", "Break Time", "Working Hours",
        "Hourly Salary", "Daily Salary", "Date of Joining", "Department",
        "Emergency Contact", "Emergency Relation", "Bank Account", "IFSC Code",
        "Aadhar Number", "PAN Number", "EPF Number", "ESIC",
        "Photo", "Aadhar File", "PAN File",
        "Sick Leave", "Casual Leave", "Paid Leave", "Other Leave", "Total Leave",
        "Basic", "DA", "HRA", "Conveyance", "Special Allowance",
        "Performance Bonus", "Medical Allowance", "Washing Allowance",
        "Canteen Allowance", "Other Allowances",
        "Gross Salary", "EPF Employer", "ESIC Employer", "Retention Bonus",
        "Leave Encashment", "Gratuity", "Total CTC",
        "EPF Employee", "Professional Tax", "Income Tax", "Insurance Premium",
        "Advance", "Other Deductions", "Total Deductions", "Net Salary"
    ]);

    // Serial Number
    $serial = 1;

    foreach ($employees as $employee) {

        fputcsv($output, [
            $serial++,

            $employee['employee_id'] ?? '',
            $employee['status'] ?? '',
            $employee['name'] ?? '',
            $employee['dob'] ?? '',
            $employee['anniversary'] ?? '',
            $employee['password'] ?? '',
            $employee['phone'] ?? '',
            $employee['email'] ?? '',
            $employee['address'] ?? '',
            $employee['designation'] ?? '',
            $employee['role'] ?? '',
            $employee['father_name'] ?? '',
            $employee['manager'] ?? '',
            $employee['salary_type'] ?? '',
            $employee['office'] ?? '',
            $employee['latitude'] ?? '',
            $employee['longitude'] ?? '',
            $employee['punchin_time'] ?? '',
            $employee['punchout_time'] ?? '',
            $employee['break_time'] ?? '',
            $employee['working_hours'] ?? '',
            $employee['hourly_salary'] ?? '',
            $employee['daily_salary'] ?? '',
            $employee['date_of_joining'] ?? '',
            $employee['department'] ?? '',
            $employee['emergency_contact'] ?? '',
            $employee['emergency_relation'] ?? '',
            $employee['bank_account'] ?? '',
            $employee['ifsc_code'] ?? '',
            $employee['adhar_number'] ?? '',
            $employee['pan_number'] ?? '',
            $employee['epf_number'] ?? '',
            $employee['esic'] ?? '',
            $employee['photo'] ?? '',
            $employee['adhar_file'] ?? '',
            $employee['pan_file'] ?? '',
            $employee['sick_leave'] ?? '',
            $employee['casual_leave'] ?? '',
            $employee['paid_leave'] ?? '',
            $employee['other_leave'] ?? '',
            $employee['total_leave'] ?? '',
            $employee['basic'] ?? '',
            $employee['da'] ?? '',
            $employee['hra'] ?? '',
            $employee['conveyance'] ?? '',
            $employee['special_allowance'] ?? '',
            $employee['performance_bonus'] ?? '',
            $employee['medical_allowance'] ?? '',
            $employee['washing_allowance'] ?? '',
            $employee['canteen_allowance'] ?? '',
            $employee['other_allowances'] ?? '',
            $employee['gross_salary'] ?? '',
            $employee['epf_employer'] ?? '',
            $employee['esic_employer'] ?? '',
            $employee['retention_bonus'] ?? '',
            $employee['leave_encashment'] ?? '',
            $employee['gratuity'] ?? '',
            $employee['total_ctc'] ?? '',
            $employee['epf_employee'] ?? '',
            $employee['professional_tax'] ?? '',
            $employee['income_tax'] ?? '',
            $employee['insurance_premium'] ?? '',
            $employee['advance'] ?? '',
            $employee['other_deductions'] ?? '',
            $employee['total_deductions'] ?? '',
            $employee['net_salary'] ?? ''
        ]);
    }

    fclose($output);
    exit;
}

$offices_query = $conn->query("SELECT office_name, state_name  FROM offices ORDER BY office_name ASC");
$offices = $offices_query->fetch_all(MYSQLI_ASSOC);
?>
<?php
include("header.php"); ?>

<style>
.manage-employee-shell {
    padding-bottom: 1.5rem;
}

.manage-employee-card,
.manage-employee-search,
.manage-employee-table-card {
    border: 1px solid rgba(87, 96, 108, 0.12);
    border-radius: 22px;
    box-shadow: 0 14px 34px rgba(31, 41, 55, 0.06);
    background: #fff;
}

.manage-employee-card {
    padding: 1rem 1.1rem;
    height: 100%;
}

.manage-employee-search {
    padding: 1rem 1.1rem;
    margin-bottom: 1rem;
}

.manage-employee-table-card {
    overflow: hidden;
}

.manage-employee-section-label {
    display: block;
    margin-bottom: 0.45rem;
    color: #6b7280;
    font-size: 0.74rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.manage-employee-card .form-control,
.manage-employee-search .form-control {
    min-height: 46px;
    border-radius: 14px;
    border: 1px solid #d8dee7;
    box-shadow: none;
}

.manage-employee-card .form-control:focus,
.manage-employee-search .form-control:focus {
    border-color: #1e3a5f;
    box-shadow: 0 0 0 0.18rem rgba(30, 58, 95, 0.12);
}

.manage-employee-toolbar {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.8rem;
    flex-wrap: wrap;
}

.manage-employee-toolbar .btn {
    min-height: 38px;
    padding: 0.52rem 0.95rem;
    border-radius: 14px;
    font-size: 0.8rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.manage-employee-search-row {
    display: flex;
    gap: 0.85rem;
    align-items: center;
}

.employee-btn-primary,
.employee-btn-dark,
.manage-employee-search .btn,
.modal-custom .save {
    background: linear-gradient(135deg, #161616 0%, #2d2d2d 100%) !important;
    color: #fff !important;
    border: none !important;
    box-shadow: 0 12px 24px rgba(17, 24, 39, 0.18);
}

.employee-btn-primary:hover,
.employee-btn-dark:hover,
.manage-employee-search .btn:hover,
.modal-custom .save:hover {
    background: linear-gradient(135deg, #101010 0%, #242424 100%) !important;
    color: #fff !important;
}

.employee-btn-secondary,
.employee-row-btn-view {
    background: #16324f !important;
    color: #fff !important;
    border: 1px solid #16324f !important;
    box-shadow: none !important;
}

.employee-btn-secondary:hover,
.employee-row-btn-view:hover {
    background: #10263c !important;
    border-color: #10263c !important;
    color: #fff !important;
}

.employee-btn-soft {
    background: #edf4ff !important;
    color: #163b72 !important;
    border: 1px solid #d7e4fb !important;
    box-shadow: none !important;
}

.employee-btn-soft:hover {
    background: #e2edff !important;
    color: #0f2e59 !important;
}

.manage-employee-table-card .card-body {
    padding: 0 0 1rem;
}

.manage-employee-table-wrap {
    padding: 0 1.2rem 1.15rem;
}

.manage-employee-table {
    margin-bottom: 0;
}

.manage-employee-table thead th {
    border-bottom: 1px solid #e8edf3;
    color: #6b7280;
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    padding: 1rem 0.95rem;
    white-space: nowrap;
    background: #f8fafc;
}

.manage-employee-table tbody td {
    padding: 1rem 0.95rem;
    border-bottom: 1px solid #eef2f7;
    color: #1f2937;
    vertical-align: middle;
}

.manage-employee-table thead th:last-child,
.manage-employee-table tbody td:last-child {
    min-width: 330px;
}

.manage-employee-table tbody tr:last-child td {
    border-bottom: none;
}

.manage-employee-table tbody tr:hover {
    background: #fbfcfe;
}

.manage-employee-avatar {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    object-fit: cover;
    border: 1px solid #e5e7eb;
    background: #f8fafc;
}

.manage-employee-role,
.manage-employee-center {
    text-align: center;
}

.manage-employee-role span {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 96px;
    padding: 0.42rem 0.7rem;
    border-radius: 999px;
    background: #f8fafc;
    border: 1px solid #dbe3ed;
    color: #475569 !important;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.employee-row-actions {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    gap: 0.8rem;
    flex-wrap: nowrap;
    white-space: nowrap;
}

.employee-row-buttons {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: nowrap;
    flex-shrink: 0;
}

.employee-row-btn {
    min-width: 38px;
    height: 38px;
    min-height: 38px;
    padding: 0.45rem 0.7rem;
    border-radius: 12px !important;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin: 0 !important;
}

.employee-row-btn-delete {
    background: #fbe6e5 !important;
    color: #c24141 !important;
    border: 1px solid #f4c9c7 !important;
    box-shadow: none !important;
}

.employee-row-btn-delete:hover {
    background: #f7d8d6 !important;
    color: #a93232 !important;
}

.employee-row-toggle-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 6px 12px;
    min-height: 38px;
    box-sizing: border-box;
    border: 1px solid #fbcfe8;
    border-radius: 999px;
    background: linear-gradient(135deg, #fff1f2, #ffe4e6);
    flex-shrink: 0;
}

.employee-row-toggle-pill span {
    font-size: 10px;
    line-height: 1;
    font-weight: 700;
    color: #be185d;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    white-space: nowrap;
}

.employee-row-toggle-pill .switch {
    margin: 0;
    width: 46px;
    height: 24px;
    flex-shrink: 0;
}

@media (max-width: 991.98px) {
    .manage-employee-toolbar,
    .manage-employee-search-row,
    .employee-row-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .manage-employee-toolbar .btn,
    .manage-employee-search-row .btn {
        width: 100%;
    }
}
</style>

<div class="container-fluid py-4 manage-employee-shell">
    <div class="row">
        <div class="col-lg-4 mb-4 d-flex">
            <div class="manage-employee-card w-100">
                <span class="manage-employee-section-label">Office Filter</span>
                <select name="site" id="site" class="form-control" onchange="redirectToSite()">
                    <option value="" selected>Select site</option>
                    <?php foreach ($offices as $office): ?>
                        <option value="site_manage_employee?office=<?php echo urlencode($office['office_name']); ?>_<?php echo urlencode($office['state_name']); ?>">
                            <?= htmlspecialchars($office['office_name']) ?> (<?php echo urlencode($office['state_name']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="col-lg-8 mb-4 d-flex">
            <div class="manage-employee-card w-100">
                <span class="manage-employee-section-label">Actions</span>
                <div class="manage-employee-toolbar">
                    <a href="manage_inactive" class="btn employee-btn-soft btn-sm mb-0">Inactive EMP</a>
                    <form method="GET" class="d-inline mb-0">
                        <button type="submit" class="btn employee-btn-primary btn-sm mb-0">Filter</button>
                    </form>
                    <a href="?download_csv=1&office=<?= urlencode($filter_office) ?>" class="btn employee-btn-secondary btn-sm mb-0">CSV</a>
                    <a href="?download_csv2=1&office=<?= urlencode($filter_office) ?>" class="btn employee-btn-secondary btn-sm mb-0">CSV(Large Data)</a>
                    <a href="add_employee" class="btn employee-btn-dark btn-sm mb-0">Add New Employee</a>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="manage-employee-search">
                <form method="GET" class="mb-0">
                    <div class="manage-employee-search-row">
                        <input type="text" name="search" class="form-control" placeholder="Search by Name, Role or ID" value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn btn-sm mb-0 px-4">Search</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-12">
            <div class="card manage-employee-table-card mb-4">
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="table-responsive manage-employee-table-wrap">
                        <table class="table manage-employee-table align-items-center mb-0">
    <thead>
        <tr>
            <th>Name/ID</th>
            <th>Designation</th>
            <th>Office</th>
            <th>Role</th>
            <th>Restriction</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($employees as $employee) : ?>
            <tr>
                <td>
                    <div class="d-flex px-2 py-1">
                        <div>
                        <img src="<?= !empty($employee['photo']) ? $employee['photo'] : 'assets/img/logos/user.png' ?>" class="manage-employee-avatar me-3" alt="user1">

                        </div>
                        <div class="d-flex flex-column justify-content-center">
                            <h6 class="mb-0 text-sm"><?= $employee['name'] ?></h6>
                            <p class="text-xs text-secondary mb-0"><?= $employee['employee_id'] ?></p>
                        </div>
                    </div>
                </td>
                <td>
                    <p class="text-xs font-weight-bold mb-0"><?= $employee['designation'] ?></p>
                </td>
                <td>
                    <p class="text-xs mb-0"><?= $employee['office'] ?></p>
                </td>
               
                <td class="align-middle manage-employee-role">
                    <span><?= $employee['role'] ?></span>
                </td>
               <!-- Add this in your manage employee table -->
<td class="align-middle manage-employee-center">
    <label class="switch">
        <input type="checkbox" class="toggle-status" data-employee-id="<?= $employee['employee_id'] ?>" <?= $employee['restriction_status'] === 'Yes' ? 'checked' : '' ?>>
        <span class="slider round"></span>
    </label>
</td>

                <td class="align-middle">
                    <div class="employee-row-actions">
                        <div class="employee-row-buttons">
                            <a href="emp_profile?employee_id=<?= $employee['employee_id'] ?>" class="btn employee-row-btn employee-row-btn-view btn-sm"><i class="bi bi-eye-fill"></i></a>
                            <a href="edit_employee?employee_id=<?= $employee['employee_id'] ?>" class="btn employee-row-btn employee-btn-dark btn-sm"><i class="bi bi-pencil-square"></i></a>

                            <button type="button"
                                    class="btn employee-row-btn km-btn"
                                    title="Update KM Price"
                                    onclick="openKmModal('<?= $employee['employee_id'] ?>','<?= $employee['price_km'] ?>')">
                                <i class="bi bi-currency-rupee"></i>
                            </button>

                            <a href="?delete=<?= $employee['employee_id'] ?>" class="btn employee-row-btn employee-row-btn-delete btn-sm" onclick="return confirm('Are you sure you want to delete this employee?');"><i class="bi bi-trash-fill"></i></a>
                        </div>

                        <div class="employee-row-toggle-pill" title="Turn on to stop nightly auto punch-out for this employee">
                            <span>Auto Punch-Out Off</span>
                            <label class="switch switch-auto-punch mb-0">
                                <input
                                    type="checkbox"
                                    class="toggle-auto-punchout"
                                    data-employee-id="<?= htmlspecialchars($employee['employee_id']) ?>"
                                    <?= !empty($employee['disable_auto_punchout']) ? 'checked' : '' ?>
                                >
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>





<div class="modal-custom" id="kmModal">
  <div class="modal-content small">
    <h5>Update KM Price</h5>

    <form method="POST" action="update_km_price">
      <input type="hidden" name="employee_id" id="km_employee_id">

      <label>Price per KM (₹)</label>
      <input type="number" step="0.01" name="price_km" id="km_price"
             class="form-control" required>

      <button class="save">Save</button>
      <button type="button" class="close" onclick="closeKmModal()">Cancel</button>
    </form>
  </div>
</div>

<script>
function openKmModal(empId, price) {
    document.getElementById('km_employee_id').value = empId;
    document.getElementById('km_price').value = price || '';
    document.getElementById('kmModal').style.display = 'flex';
}

function closeKmModal() {
    document.getElementById('kmModal').style.display = 'none';
}
</script>

<!-- Add this CSS for switch styling -->
<style>
.switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 24px;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #4CAF50; /* Green when 'No' */
    transition: .4s;
    border-radius: 24px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 4px;
    bottom: 3px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

input:checked + .slider {
    background-color: #FF4C4C; /* Red when 'Yes' */
}

input:checked + .slider:before {
    transform: translateX(24px);
}



/* Modal overlay */
.modal-custom {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.55);
    z-index: 1055;
    align-items: center;
    justify-content: center;
}

/* Modal box */
.modal-custom .modal-content {
    background: #ffffff;
    padding: 22px 24px;
    border-radius: 16px;
    width: 420px;
    max-width: 95%;
    box-shadow: 0 20px 40px rgba(0,0,0,0.25);
    animation: scaleIn 0.25s ease;
}

/* Title */
.modal-custom h5 {
    font-weight: 700;
    margin-bottom: 14px;
    color: #0f172a;
}

/* Input */
.modal-custom input.form-control {
    border-radius: 10px;
    padding: 10px 12px;
    font-size: 15px;
}

/* Buttons row */
.modal-custom .save,
.modal-custom .close {
    border-radius: 10px;
    padding: 8px 14px;
    font-weight: 600;
    width: auto;
}

/* Save button */
.modal-custom .save {
    background: linear-gradient(135deg, #161616, #2d2d2d);
    color: #fff;
    border: none;
}

.modal-custom .save:hover {
    box-shadow: 0 10px 24px rgba(17,24,39,0.22);
}

/* Cancel button */
.modal-custom .close {
    background: #f3f4f6;
    color: #374151;
    border: none;
    margin-left: 8px;
}

/* Animation */
@keyframes scaleIn {
    from { transform: scale(0.9); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}




/* KM price button */
.km-btn {
    background: linear-gradient(135deg, #1f8f57, #187247);
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 6px 12px;
    font-size: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.25s ease;
}

.km-btn:hover {
    transform: translateY(-1px) scale(1.05);
    box-shadow: 0 8px 18px rgba(24,114,71,0.3);
    color: #fff;
}

.km-btn i {
    font-size: 16px;
}

.switch-auto-punch .slider {
    background-color: #fb7185;
}

.switch-auto-punch input:checked + .slider {
    background-color: #a855f7;
}

</style>



<!-- Add this script for AJAX toggle -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggles = document.querySelectorAll('.toggle-status');
    toggles.forEach(toggle => {
        toggle.addEventListener('change', function () {
            const employeeId = this.getAttribute('data-employee-id');
            const status = this.checked ? 'Yes' : 'No';

            fetch('update_restriction_status', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `employee_id=${employeeId}&restriction_status=${status}`
            })
            .then(response => response.text())
            .then(result => {
                console.log(result); // Optional: Debug response
                alert(result); // Optional: Show success message
            })
            .catch(error => console.error('Error:', error));
        });
    });

    const autoPunchToggles = document.querySelectorAll('.toggle-auto-punchout');
    autoPunchToggles.forEach(toggle => {
        toggle.addEventListener('change', function () {
            const employeeId = this.getAttribute('data-employee-id');
            const disableAutoPunchout = this.checked ? 1 : 0;
            const currentToggle = this;

            fetch('update_auto_punchout_status', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `employee_id=${encodeURIComponent(employeeId)}&disable_auto_punchout=${disableAutoPunchout}`
            })
            .then(response => response.text())
            .then(result => {
                if (!result.includes('updated successfully')) {
                    currentToggle.checked = !currentToggle.checked;
                    alert(result || 'Failed to update auto punch-out status.');
                    return;
                }

                alert(
                    disableAutoPunchout
                        ? 'Auto punch-out disabled for this employee.'
                        : 'Auto punch-out enabled for this employee.'
                );
            })
            .catch(() => {
                currentToggle.checked = !currentToggle.checked;
                alert('Failed to update auto punch-out status.');
            });
        });
    });
});
</script>
<script>
       function redirectToSite() {
        var site = document.getElementById('site').value;
        if (site) {
            window.location.href = site; // Redirect to the selected site's page
        }
    }
</script>


<?php include("footer.php") ?>
