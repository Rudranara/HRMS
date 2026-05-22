<?php
require 'db_connection.php';
// Fetch offices
$offices_query = $conn->query("SELECT office_name, state_name FROM offices ORDER BY office_name ASC");
$offices = $offices_query->fetch_all(MYSQLI_ASSOC);
// Handle filters
$selected_office = isset($_GET['office']) ? trim(urldecode($_GET['office'])) : '';
$selected_year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$selected_month = isset($_GET['month']) ? $_GET['month'] : date('m');
$selected_employee_id = isset($_GET['employee_id']) ? $_GET['employee_id'] : '';
$delete_success_message = isset($_GET['salary_deleted']) ? 'Selected salaries deleted successfully.' : '';
// Prepare office filter
$office_condition = "";
if (!empty($selected_office)) {
    $office_condition = " AND e.office = '$selected_office'";
}

// Employee condition
$employee_condition = "";
if (!empty($selected_employee_id)) {
    $employee_condition = " AND e.id = '$selected_employee_id'";
}

// Prepare year/month filter
$year_condition = "s.year = '$selected_year'";
$month_condition = !empty($selected_month) ? " AND s.month = '$selected_month'" : "";
// Fetch employees and filtered salaries
if (!empty($selected_office)) {
    $employees_stmt = $conn->prepare("SELECT id, name FROM employees WHERE status = 'Active' AND office = ? ORDER BY name");
    $employees_stmt->bind_param("s", $selected_office);
} else {
    $employees_stmt = $conn->prepare("SELECT id, name FROM employees WHERE status = 'Active' ORDER BY name");
}
$employees_stmt->execute();
$employees_result = $employees_stmt->get_result();
$employee_options = [];
while ($employee = $employees_result->fetch_assoc()) {
    $employee_options[] = $employee;
}

if (isset($_GET['download_salary_records'])) {
    $monthNumber = ctype_digit((string) $selected_month) ? (int) $selected_month : (int) date('m');
    $monthLabel = strtolower(date('F', mktime(0, 0, 0, $monthNumber, 1)));

    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"salary_records_{$monthLabel}.csv\"");

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Employee', 'Year', 'Month', 'Gross Salary', 'Total Deduction', 'Net Salary', 'Total Present Days', 'Total Absent Days', 'Total Leave Days']);

    $csvSalaries = $conn->query(
        "SELECT 
            e.name AS employee_name,
            s.year,
            s.month,
            s.gross_salary,
            s.total_deductions,
            s.present_days,
            s.absent_days,
            s.leave_days,
            s.net_salary,
            s.retention_bonus,
            s.leave_encashment
        FROM salary s
        JOIN employees e ON s.employee_id = e.id
        WHERE $year_condition $month_condition $office_condition $employee_condition
        ORDER BY s.year DESC, s.month DESC"
    );

    while ($salary = $csvSalaries->fetch_assoc()) {
        fputcsv($output, [
            $salary['employee_name'],
            $salary['year'],
            date('F', mktime(0, 0, 0, (int) $salary['month'], 1)),
            number_format((float) $salary['gross_salary'], 2, '.', ''),
            number_format((float) $salary['total_deductions'], 2, '.', ''),
            number_format(
                ((float) $salary['net_salary']) + ((float) $salary['retention_bonus']) + ((float) $salary['leave_encashment']),
                2,
                '.',
                ''
            ),
            number_format((float) $salary['present_days'], 2, '.', ''),
            number_format((float) $salary['absent_days'], 2, '.', ''),
            number_format((float) $salary['leave_days'], 2, '.', ''),
        ]);
    }

    fclose($output);
    exit;
}

$salaries = $conn->query("
    SELECT 
        s.id,
        e.name AS employee_name,
        s.year,
        s.month,
        s.gross_salary,
        s.total_deductions,
        s.net_salary,
        s.retention_bonus,
        s.leave_encashment
    FROM salary s
    JOIN employees e ON s.employee_id = e.id
    WHERE $year_condition $month_condition $office_condition $employee_condition
    ORDER BY s.year DESC, s.month DESC
");

// Handle delete action
if (isset($_POST['deleteSelected']) && !empty($_POST['selected_ids'])) {
    $ids_to_delete = implode(',', $_POST['selected_ids']);
    $conn->query("DELETE FROM salary WHERE id IN ($ids_to_delete)");

    $redirect_params = $_GET;
    $redirect_params['salary_deleted'] = 1;

    header('Location: manage_salary?' . http_build_query($redirect_params));
    exit;
}

include 'header.php';
?>

<style>
    .salary-page {
        background:
            radial-gradient(circle at top right, rgba(15, 23, 42, 0.05), transparent 24%),
            linear-gradient(180deg, #f6f7f9 0%, #f2f4f7 100%);
    }

    .salary-page .form-label,
    .salary-page label {
        display: block;
        margin-bottom: 0.45rem;
        color: #6b7280;
        font-size: 0.76rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }

    .salary-page .form-control,
    .salary-page select.form-control {
        min-height: 44px;
        border: 1px solid #d8dee7;
        border-radius: 14px;
        background: #ffffff;
        box-shadow: none;
        color: #1f2937;
        padding: 0.65rem 0.9rem;
    }

    .salary-page .form-control:focus,
    .salary-page select.form-control:focus {
        border-color: #16324f;
        box-shadow: 0 0 0 0.18rem rgba(22, 50, 79, 0.12);
    }

    .salary-page-title {
        margin: 0;
        color: #111827;
        font-size: 1.55rem;
        font-weight: 800;
        letter-spacing: -0.02em;
    }

    .salary-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.25rem;
    }

    .salary-toolbar-actions {
        display: flex;
        gap: 0.75rem;
        align-items: center;
        justify-content: flex-end;
        flex-wrap: wrap;
    }

    .salary-toolbar-btn,
    .salary-outline-btn,
    .salary-danger-btn,
    .salary-table .btn-success {
        min-height: 42px;
        padding: 0.65rem 1rem;
        border-radius: 14px;
        font-size: 0.82rem;
        font-weight: 700;
        box-shadow: none;
    }

    .salary-toolbar-btn {
        background: linear-gradient(135deg, #171717 0%, #2f2f2f 100%) !important;
        border: 1px solid #171717 !important;
        color: #ffffff !important;
    }

    .salary-toolbar-btn:hover {
        background: linear-gradient(135deg, #111111 0%, #252525 100%) !important;
        color: #ffffff !important;
    }

    .salary-outline-btn {
        background: #16324f !important;
        border: 1px solid #16324f !important;
        color: #ffffff !important;
    }

    .salary-outline-btn:hover {
        background: #10263c !important;
        border-color: #10263c !important;
        color: #ffffff !important;
    }

    .salary-filter-card,
    .salary-table-card {
        border: 1px solid rgba(107, 114, 128, 0.14);
        border-radius: 24px;
        background: rgba(255, 255, 255, 0.96);
        box-shadow: 0 18px 42px rgba(15, 23, 42, 0.07);
        overflow: hidden;
    }

    .salary-filter-card {
        padding: 1.1rem;
        margin-bottom: 1.2rem;
        background: linear-gradient(180deg, #fafbfc 0%, #f7f9fb 100%);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.9), 0 14px 32px rgba(15, 23, 42, 0.05);
    }

    .salary-flash-message {
        margin-bottom: 1.2rem;
        border: 1px solid #22c55e;
        border-radius: 18px;
        background-color: #22c55e;
        background-image: linear-gradient(310deg, #22c55e 0%, rgb(132.1624454148, 220.2707423581, 19.9292576419) 100%);
        color: #183153;
        box-shadow: 0 12px 26px rgba(34, 197, 94, 0.24);
    }

    .salary-filter-card .row {
        --bs-gutter-x: 0.9rem;
        --bs-gutter-y: 0.9rem;
    }

    .salary-filter-submit {
        min-height: 44px;
        border-radius: 14px;
        font-size: 0.82rem;
        font-weight: 700;
        background: linear-gradient(135deg, #171717 0%, #2f2f2f 100%) !important;
        border: 1px solid #171717 !important;
        color: #ffffff !important;
        box-shadow: none;
    }

    .salary-filter-submit:hover {
        background: linear-gradient(135deg, #111111 0%, #252525 100%) !important;
        color: #ffffff !important;
    }

    .salary-table-card .card-body {
        padding: 0;
    }

    .salary-table-wrap {
        padding: 0 1.15rem 1.15rem;
    }

    .salary-table {
        margin-bottom: 0;
    }

    .salary-table thead th {
        border-bottom: 1px solid #e8edf3;
        background: #f8fafc;
        color: #6b7280;
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        padding: 1rem 0.95rem;
        white-space: nowrap;
    }

    .salary-table tbody td {
        padding: 1rem 0.95rem;
        border-bottom: 1px solid #eef2f7;
        color: #1f2937;
        vertical-align: middle;
    }

    .salary-table tbody tr:last-child td {
        border-bottom: none;
    }

    .salary-table tbody tr:hover {
        background: #fbfcfe;
    }

    .salary-table .btn-success {
        min-width: 42px;
        padding: 0.55rem 0.75rem;
        background: #e8f7f1 !important;
        border: 1px solid #c7e8dd !important;
        color: #2f6f62 !important;
    }

    .salary-table .btn-success:hover {
        background: #dff2ea !important;
        color: #24584f !important;
    }

    .salary-danger-btn {
        background: #fbe6e5 !important;
        border: 1px solid #f4c9c7 !important;
        color: #c24141 !important;
    }

    .salary-danger-btn:hover {
        background: #f7d8d6 !important;
        color: #a93232 !important;
    }

    .salary-table-actions {
        padding: 1rem 1.15rem 1.15rem;
        border-top: 1px solid #edf1f5;
        background: #ffffff;
    }

    .salary-empty {
        color: #6b7280 !important;
        font-weight: 600;
        text-align: center;
        padding: 1.8rem 1rem !important;
    }

    @media (max-width: 991.98px) {
        .salary-toolbar {
            flex-direction: column;
            align-items: stretch;
        }

        .salary-toolbar-actions {
            justify-content: flex-start;
        }
    }

    @media (max-width: 767.98px) {
        .salary-filter-card,
        .salary-table-card {
            border-radius: 20px;
        }

        .salary-filter-card {
            padding: 0.9rem;
        }

        .salary-table-wrap {
            padding: 0 0.85rem 0.95rem;
        }

        .salary-table-actions {
            padding: 0.9rem 0.85rem 1rem;
        }
    }
</style>

<div class="container-fluid py-4 salary-page">
    <div class="row">
        <div class="col-12">
            <div class="salary-toolbar">
                <h6 class="salary-page-title">Manage Employees</h6>
                <div class="salary-toolbar-actions">
                    <a href="bulk_salary" class="btn salary-toolbar-btn mb-0">Generate Payroll</a>
                    <button type="button" id="editGeneratedPayrollBtn" class="btn salary-toolbar-btn mb-0">Edit Generated Payroll</button>
                    <button type="button" id="salaryRecordsBtn" class="btn salary-outline-btn mb-0">CSV(Salary Records)</button>
                    <a href="manage_advance_salary" class="btn salary-outline-btn mb-0">Advance Requests</a>
                </div>
            </div>
        </div>
        <?php if (!empty($delete_success_message)): ?>
            <div class="col-12">
                <div class="alert salary-flash-message" role="alert">
                    <?= htmlspecialchars($delete_success_message) ?>
                </div>
            </div>
        <?php endif; ?>
        <div class="col-12">
            <div class="salary-filter-card">
            <form method="GET" action="manage_salary" class="row align-items-end">
                <div class="col-md-3">
                    <label>Select Office</label>
                    <select name="office" class="form-control">
                        <option value="">All Offices</option>
                        <?php foreach ($offices as $office): 
                            $value = $office['office_name'] . "_" . $office['state_name']; ?>
                            <option value="<?= htmlspecialchars($value) ?>" <?= $selected_office == $value ? 'selected' : '' ?>>
                                <?= htmlspecialchars($office['office_name']) ?> (<?= htmlspecialchars($office['state_name']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
    <label>Select Employee</label>
    <select name="employee_id" id="employeeSelect" class="form-control">
        <option value="">All Employees</option>
        <?php foreach ($employee_options as $employee): ?>
            <option value="<?= htmlspecialchars($employee['id']) ?>" <?= (string) $selected_employee_id === (string) $employee['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($employee['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

                <div class="col-md-3">
                    <label>Select Year</label>
                    <select name="year" class="form-control">
                        <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                            <option value="<?= $y ?>" <?= $selected_year == $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Select Month</label>
                    <select name="month" class="form-control">
                        <option value="">All</option>
                        <?php for ($m = 1; $m <= 12; $m++): 
                            $m_value = str_pad($m, 2, '0', STR_PAD_LEFT); ?>
                            <option value="<?= $m_value ?>" <?= $selected_month == $m_value ? 'selected' : '' ?>>
                                <?= date("F", mktime(0, 0, 0, $m, 1)) ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn salary-filter-submit w-100">Filter</button>
                </div>
            </form>
            </div>
        </div>
        <div class="col-12">
            <div class="card salary-table-card mb-4">
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="table-responsive salary-table-wrap">
                <!-- Salaries Table -->
                <form id="salaryForm" method="POST" action="">
                    <table class="table align-items-center mb-0 salary-table">
                        <thead>
                            <tr>
                                <th>
                                    <div class="checkboxes__item">
                                        <label class="checkbox style-h">
                                            <input type="checkbox" id="selectAll">
                                            <div class="checkbox__checkmark" style="background-color: #9fddb2;"></div>

                                        </label>
                                    </div>
                                </th>
                                <th>Employee</th>
                                <th>Year</th>
                                <th>Month</th>
                                <th>Gross Salary</th>
                                <th>Total Deduction</th>
                                <th>Net Salary</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($salaries->num_rows > 0): ?>
                                <?php while ($salary = $salaries->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="checkboxes__item">
                                                <label class="checkbox style-h">
                                                    <input type="checkbox" name="selected_ids[]" value="<?= $salary['id'] ?>">
                                                    <div class="checkbox__checkmark" style="background-color: #9fddb2;"></div>

                                                </label>
                                            </div>
                                        </td>
                                        <td><?= $salary['employee_name'] ?></td>
                                        <td><?= $salary['year'] ?></td>
                                        <td><?= date("F", mktime(0, 0, 0, $salary['month'], 10)) ?></td>
                                        <td><?= number_format($salary['gross_salary'], 2) ?></td>
                                        <td><?= number_format((float) $salary['total_deductions'], 2) ?></td>
                                        <td><?= number_format(((float) $salary['net_salary']) + ((float) $salary['retention_bonus']) + ((float) $salary['leave_encashment']), 2) ?></td>
                                        <td>                                         
                                            <a href="download_salary_slip?id=<?= $salary['id'] ?>" class="btn btn-success btn-sm"><i class="bi bi-download"></i></a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="salary-empty">No salaries found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <div class="salary-table-actions">
                        <button type="submit" name="deleteSelected" class="btn salary-danger-btn">Delete Selected</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</div>
</div>
<!-- End Navbar -->
<?php include("footer.php") ?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // Select All Functionality
    document.getElementById('selectAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll("input[name='selected_ids[]']");
        checkboxes.forEach(checkbox => checkbox.checked = this.checked);
    });

    document.getElementById('editGeneratedPayrollBtn').addEventListener('click', function () {
        const selectedPayrolls = Array.from(document.querySelectorAll("input[name='selected_ids[]']:checked"))
            .map(checkbox => checkbox.value);

        if (selectedPayrolls.length === 0) {
            alert('Please select at least one payroll record to edit.');
            return;
        }

        const params = new URLSearchParams();
        const office = document.querySelector('select[name="office"]')?.value || '';
        const employeeId = document.querySelector('select[name="employee_id"]')?.value || '';
        const year = document.querySelector('select[name="year"]')?.value || '';
        const month = document.querySelector('select[name="month"]')?.value || '';

        if (office) params.append('office', office);
        if (employeeId) params.append('employee_id', employeeId);
        if (year) params.append('year', year);
        if (month) params.append('month', month);

        selectedPayrolls.forEach(id => params.append('selected_ids[]', id));

        window.location.href = `edit_generated_payrolls?${params.toString()}`;
    });

    document.getElementById('salaryRecordsBtn').addEventListener('click', function () {
        const params = new URLSearchParams();
        const office = document.querySelector('select[name="office"]')?.value || '';
        const employeeId = document.querySelector('select[name="employee_id"]')?.value || '';
        const year = document.querySelector('select[name="year"]')?.value || '';
        const month = document.querySelector('select[name="month"]')?.value || '';

        params.append('download_salary_records', '1');
        if (office) params.append('office', office);
        if (employeeId) params.append('employee_id', employeeId);
        if (year) params.append('year', year);
        if (month) params.append('month', month);

        window.location.href = `manage_salary?${params.toString()}`;
    });

    $(document).ready(function() {
        // Handle live search
        $('#searchEmployee').on('keyup', function() {
            const query = $(this).val().trim();
            if (query.length > 0) {
                $.ajax({
                    url: 'search_employee',
                    method: 'GET',
                    data: {
                        search: query
                    },
                    success: function(response) {
                        $('#employeeList').html(response).fadeIn();
                    }
                });
            } else {
                $('#employeeList').fadeOut();
            }
        });

        // Handle selection from search results
        $(document).on('click', '.employee-item', function() {
            const employeeId = $(this).data('id');
            const employeeName = $(this).text();
            $('#searchEmployee').val(employeeName);
            $('#selectedEmployeeId').val(employeeId);
            $('#employeeList').fadeOut();
        });

        // Hide results if clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#searchEmployee, #employeeList').length) {
                $('#employeeList').fadeOut();
            }
        });
    });
</script>
<script>
document.querySelector('select[name="office"]').addEventListener('change', function () {
    var office = this.value;
    var employeeSelect = document.getElementById('employeeSelect');
    
    // Clear current options
    employeeSelect.innerHTML = '<option value="">All Employees</option>';
    
    const params = new URLSearchParams();
    if (office) {
        params.append('office', office);
    }

    fetch(`fetch_employees_by_office?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            data.forEach(employee => {
                var option = document.createElement('option');
                option.value = employee.id;
                option.text = employee.name;
                employeeSelect.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Error fetching employees:', error);
        });
});
</script>
