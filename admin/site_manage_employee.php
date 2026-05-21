<?php
include("db_connection.php");

$office_name = isset($_GET['office']) ? $_GET['office'] : '';
// Handle delete employee
if (isset($_GET['delete'])) {
    $employee_id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM employees WHERE employee_id = ?");
    $stmt->bind_param("s", $employee_id);
    $stmt->execute();
    echo "<div class='alert alert-success'>Employee deleted successfully.</div>";
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
        border: 1px solid rgba(148, 163, 184, 0.18);
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

    .manage-employee-toolbar .btn,
    .manage-employee-search .btn {
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
    .manage-employee-search .btn {
        background: linear-gradient(135deg, #161616 0%, #2d2d2d 100%) !important;
        color: #fff !important;
        border: none !important;
        box-shadow: 0 12px 24px rgba(17, 24, 39, 0.18);
    }

    .employee-btn-primary:hover,
    .employee-btn-dark:hover,
    .manage-employee-search .btn:hover {
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
        min-width: 170px;
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

    .manage-employee-role span,
    .site-manage-site-badge {
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

    .site-manage-salary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 90px;
        padding: 0.48rem 0.8rem;
        border-radius: 999px;
        background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
        color: #166534;
        font-size: 0.76rem;
        font-weight: 800;
        letter-spacing: 0.03em;
    }

    .employee-row-actions {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: nowrap;
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
                    <a href="?download_csv=1&office=<?= urlencode($filter_office) ?>" class="btn employee-btn-secondary btn-sm mb-0">Download CSV</a>
                    <a href="add_employee" class="btn employee-btn-dark btn-sm mb-0">Add New Employee</a>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="manage-employee-search">
                <form method="GET" class="mb-0">
                    <input type="hidden" name="office" value="<?= htmlspecialchars($filter_office) ?>">
                    <div class="manage-employee-search-row">
                        <div class="site-manage-site-badge">Site: <?= htmlspecialchars($office_name) ?></div>
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
                                    <th>Salary</th>
                                    <th>Join Date</th>
                                    <th>Profile</th>
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
                                        <td class="align-middle text-center text-sm">
                                            <span class="site-manage-salary"><?= $employee['net_salary'] ?></span>
                                        </td>
                                        <td class="align-middle text-center">
                                            <span class="text-secondary text-xs font-weight-bold"><?= $employee['date_of_joining'] ?></span>
                                        </td>
                                        <td class="align-middle">
                                             <a href="emp_profile?employee_id=<?= $employee['employee_id'] ?>" class="btn employee-row-btn employee-row-btn-view btn-sm"><i class="bi bi-eye-fill"></i></a>
                                        </td>
                                        <td class="align-middle">
                                            <div class="employee-row-actions">
                                                <a href="edit_employee?employee_id=<?= $employee['employee_id'] ?>" class="btn employee-row-btn employee-btn-dark btn-sm"><i class="bi bi-pencil-square"></i></a>
                                                <a href="?delete=<?= $employee['employee_id'] ?>" class="btn employee-row-btn employee-row-btn-delete btn-sm" onclick="return confirm('Are you sure you want to delete this employee?');"><i class="bi bi-trash-fill"></i></a>
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

<script>
       function redirectToSite() {
        var site = document.getElementById('site').value;
        if (site) {
            window.location.href = site; // Redirect to the selected site's page
        }
    }
</script>
<?php include("footer.php") ?>
