<?php
include("db_connection.php");
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

$query = "SELECT * FROM employees WHERE status != 'Active' AND (name LIKE '%$search%' OR employee_id LIKE '%$search%' OR role LIKE '%$search%')";
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

$offices_query = $conn->query("SELECT office_name, state_name  FROM offices ORDER BY office_name ASC");
$offices = $offices_query->fetch_all(MYSQLI_ASSOC);
?>
<?php
include("header.php"); ?>
<style>
.inactive-page {
    padding-top: 1.75rem;
    padding-bottom: 2.5rem;
}

.inactive-toolbar,
.inactive-search-card,
.inactive-table-card {
    border: 1px solid #e5eaf1;
    border-radius: 26px;
    background: #ffffff;
    box-shadow: 0 26px 60px rgba(15, 23, 42, 0.07);
}

.inactive-toolbar {
    padding: 1.35rem 1.5rem;
    margin-bottom: 1rem;
}

.inactive-toolbar-grid {
    display: grid;
    grid-template-columns: minmax(240px, 370px) minmax(0, 1fr);
    gap: 1rem;
    align-items: end;
}

.inactive-toolbar-field label,
.inactive-search-label {
    display: block;
    margin-bottom: 0.45rem;
    color: #64748b;
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.09em;
    text-transform: uppercase;
}

.inactive-toolbar-field .form-control,
.inactive-search-input {
    min-height: 46px;
    border: 1px solid #d7deea;
    border-radius: 14px;
    background: #f8fafc;
    color: #0f172a;
    padding: 0.7rem 0.95rem;
    box-shadow: none;
}

.inactive-toolbar-field .form-control:focus,
.inactive-search-input:focus {
    border-color: #9aa8bc;
    background: #ffffff;
    box-shadow: 0 0 0 0.2rem rgba(148, 163, 184, 0.16);
}

.inactive-toolbar-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.inactive-btn,
.inactive-search-btn,
.inactive-table .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 44px;
    border-radius: 14px;
    padding: 0.72rem 1.1rem;
    font-size: 0.78rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    text-decoration: none;
}

.inactive-btn-primary,
.inactive-search-btn,
.inactive-table .btn-dark {
    border: 1px solid #111827;
    background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
    color: #ffffff;
    box-shadow: 0 18px 32px rgba(15, 23, 42, 0.16);
}

.inactive-btn-secondary,
.inactive-table .btn-danger {
    border: 1px solid #d7deea;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    color: #334155;
}

.inactive-search-card {
    padding: 1.25rem 1.5rem;
    margin-bottom: 1rem;
}

.inactive-search-form {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 150px;
    gap: 0.9rem;
    align-items: end;
}

.inactive-table-card {
    overflow: hidden;
}

.inactive-table-card .card-body {
    padding: 0;
}

.inactive-table-wrap {
    padding: 0 1.4rem 1.25rem;
}

.inactive-table {
    margin-bottom: 0;
}

.inactive-table thead th {
    border-bottom: 1px solid #e8edf5;
    padding: 1rem 0.9rem;
    color: #64748b;
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.09em;
    text-transform: uppercase;
    white-space: nowrap;
}

.inactive-table tbody td {
    padding: 1rem 0.9rem;
    border-bottom: 1px solid #eef2f7;
    vertical-align: middle;
}

.inactive-table tbody tr:last-child td {
    border-bottom: 0;
}

.inactive-employee {
    display: flex;
    align-items: center;
    gap: 0.85rem;
}

.inactive-employee-avatar {
    width: 46px;
    height: 46px;
    border-radius: 14px;
    object-fit: cover;
    border: 1px solid #e2e8f0;
    box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
}

.inactive-employee-name {
    margin: 0;
    color: #0f172a;
    font-size: 0.95rem;
    font-weight: 700;
}

.inactive-employee-id,
.inactive-table-muted {
    margin: 0.18rem 0 0;
    color: #64748b;
    font-size: 0.77rem;
}

.inactive-salary-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 88px;
    padding: 0.48rem 0.78rem;
    border-radius: 999px;
    border: 1px solid #bfe3cf;
    background: #edf9f1;
    color: #15803d;
    font-size: 0.76rem;
    font-weight: 800;
    letter-spacing: 0.04em;
}

.inactive-table-actions {
    display: flex;
    gap: 0.6rem;
    justify-content: center;
    flex-wrap: wrap;
}

.inactive-table .btn {
    min-height: 40px;
    padding: 0.62rem 0.95rem;
    box-shadow: none;
}

.switch {
    position: relative;
    display: inline-block;
    width: 54px;
    height: 28px;
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
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    transition: .3s ease;
    border-radius: 999px;
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.24);
}

.slider:before {
    position: absolute;
    content: "";
    height: 20px;
    width: 20px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .3s ease;
    border-radius: 50%;
    box-shadow: 0 6px 14px rgba(15, 23, 42, 0.18);
}

input:checked + .slider {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

input:checked + .slider:before {
    transform: translateX(26px);
}

@media (max-width: 991.98px) {
    .inactive-toolbar-grid,
    .inactive-search-form {
        grid-template-columns: 1fr;
    }

    .inactive-toolbar-actions {
        justify-content: flex-start;
    }
}

@media (max-width: 767.98px) {
    .inactive-page {
        padding-top: 1.2rem;
    }

    .inactive-toolbar,
    .inactive-search-card {
        padding: 1rem;
    }

    .inactive-table-wrap {
        padding: 0 1rem 1rem;
    }

    .inactive-table-actions {
        justify-content: flex-start;
    }
}
</style>

<div class="container-fluid inactive-page">
    <div class="row">
        <div class="col-12">
            <div class="inactive-toolbar">
                <div class="inactive-toolbar-grid">
                    <div class="inactive-toolbar-field">
                        <label for="site">Select Site</label>
                        <select name="site" id="site" class="form-control" onchange="redirectToSite()">
                            <option value="" selected>Select site</option>
                            <?php foreach ($offices as $office): ?>
                                <option value="site_manage_employee?office=<?php echo urlencode($office['office_name']); ?>_<?php echo urlencode($office['state_name']); ?>">
                                    <?= htmlspecialchars($office['office_name']) ?> (<?php echo urlencode($office['state_name']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="inactive-toolbar-actions">
                        <a href="manage_employee" class="inactive-btn inactive-btn-secondary">Active EMP</a>
                        <a href="?download_csv=1&office=<?= urlencode($filter_office) ?>" class="inactive-btn inactive-btn-secondary">Download CSV</a>
                        <a href="add_employee" class="inactive-btn inactive-btn-primary">Add New Employee</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="inactive-search-card">
                <form method="GET" class="inactive-search-form">
                    <div>
                        <label for="inactive-search" class="inactive-search-label">Search Employees</label>
                        <input type="text" id="inactive-search" name="search" class="form-control inactive-search-input" placeholder="Search by Name, Role or ID" value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div>
                        <button type="submit" class="btn inactive-search-btn w-100">Search</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-12">
            <div class="card mb-4 inactive-table-card">
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="table-responsive inactive-table-wrap">
                        <table class="table align-items-center mb-0 inactive-table">
    <thead>
        <tr>
            <th>Name/ID</th>
            <th>Designation</th>
            <th>Office</th>
            <th>Salary</th>
            <th>Join Date</th>
            <th>Restriction</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($employees as $employee) : ?>
            <tr>
                <td>
                    <div class="inactive-employee">
                        <div>
                            <img src="<?= $employee['photo'] ?>" class="inactive-employee-avatar" alt="user1">
                        </div>
                        <div class="d-flex flex-column justify-content-center">
                            <h6 class="inactive-employee-name"><?= $employee['name'] ?></h6>
                            <p class="inactive-employee-id"><?= $employee['employee_id'] ?></p>
                        </div>
                    </div>
                </td>
                <td>
                    <p class="text-xs font-weight-bold mb-0 inactive-table-muted"><?= $employee['designation'] ?></p>
                </td>
                <td>
                    <p class="text-xs mb-0 inactive-table-muted"><?= $employee['office'] ?></p>
                </td>
                <td class="align-middle text-center text-sm">
                    <span class="inactive-salary-badge"><?= $employee['net_salary'] ?></span>
                </td>
                <td class="align-middle text-center">
                    <span class="text-secondary text-xs font-weight-bold inactive-table-muted"><?= $employee['date_of_joining'] ?></span>
                </td>
               <!-- Add this in your manage employee table -->
<td class="align-middle text-center">
    <label class="switch">
        <input type="checkbox" class="toggle-status" data-employee-id="<?= $employee['employee_id'] ?>" <?= $employee['restriction_status'] === 'Yes' ? 'checked' : '' ?>>
        <span class="slider round"></span>
    </label>
</td>



                <td class="align-middle">
                    <div class="inactive-table-actions">
                        <a href="onboard?employee_id=<?= $employee['employee_id'] ?>" class="btn btn-dark btn-sm">Onboard</a>
                        <a href="?delete=<?= $employee['employee_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this employee?');"><i class="bi bi-trash-fill"></i></a>
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
