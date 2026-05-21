<?php
// Include database connection
include 'header.php';

// Ensure the employee is logged in
if (!isset($_SESSION['employee_id'])) {
    echo "Access Denied!";
    exit;
}
$employee_id = $_SESSION['employee_id'];

// Prepare and execute the query to fetch salaries
$stmt = $conn->prepare("
    SELECT 
        s.id,
        e.name AS employee_name,
        s.year,
        s.month,
        s.gross_salary,
        s.net_salary,
        s.retention_bonus,
        s.leave_encashment
    FROM salary s
    JOIN employees e ON s.employee_id = e.id 
    WHERE s.employee_id = ?
    ORDER BY s.year DESC, s.month DESC
");

$stmt->bind_param("i", $employee_id); // Bind the employee ID (assumes it is an integer)
$stmt->execute();
$result = $stmt->get_result();

// Fetch all results as an associative array
$salaries = $result->fetch_all(MYSQLI_ASSOC);

$stmt->close();
$conn->close();
?>
<style>
    .manage-salary-page {
        padding-top: 0.95rem !important;
        padding-bottom: 1.2rem !important;
    }

    .manage-salary-header-row {
        align-items: center;
    }

    .manage-salary-title {
        margin: 0;
        color: #0f172a;
        font-size: 1.14rem;
        font-weight: 800;
        letter-spacing: -0.02em;
    }

    .manage-salary-actions {
        display: inline-flex;
        align-items: center;
        justify-content: flex-end;
        gap: 0.65rem;
        flex-wrap: wrap;
    }

    .manage-salary-cta {
        min-height: 44px;
        padding: 0.72rem 1.15rem;
        border-radius: 15px;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        box-shadow: none !important;
    }

    .manage-salary-cta-outline {
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #0f172a !important;
    }

    .manage-salary-cta-outline:hover,
    .manage-salary-cta-outline:focus {
        border-color: #94a3b8;
        background: #f8fafc;
        color: #0f172a !important;
    }

    .manage-salary-cta-dark {
        border: 0;
        background: linear-gradient(135deg, #123b76 0%, #1f4c8f 100%) !important;
        color: #ffffff !important;
    }

    .manage-salary-card {
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 28px;
        background: linear-gradient(180deg, #f8fbff 0%, #eef4fb 100%);
        box-shadow: 0 24px 56px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .manage-salary-shell {
        background: #ffffff;
    }

    .manage-salary-wrap {
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
    }

    .manage-salary-table {
        margin-bottom: 0;
        min-width: 760px;
    }

    .manage-salary-table thead th {
        padding: 1rem;
        border-bottom: 1px solid #eef2f7;
        background: #f8fafc;
        color: #64748b;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .manage-salary-table tbody td {
        padding: 1rem;
        border-bottom: 1px solid #eef2f7;
        vertical-align: middle;
        color: #1f2937;
        font-size: 0.88rem;
    }

    .manage-salary-table tbody tr:hover {
        background: #fbfdff;
    }

    .manage-salary-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .manage-salary-download {
        min-width: 40px;
        min-height: 38px;
        border-radius: 12px;
        border: 1px solid #bbf7d0;
        background: #ecfdf3;
        color: #15803d;
        box-shadow: none !important;
    }

    .manage-salary-download:hover,
    .manage-salary-download:focus {
        background: #dcfce7;
        border-color: #86efac;
        color: #166534;
    }

    .manage-salary-empty {
        color: #64748b;
        font-weight: 600;
    }

    @media (max-width: 767.98px) {
        .manage-salary-page {
            padding-top: 0.6rem !important;
            padding-left: 0.3rem !important;
            padding-right: 0.3rem !important;
            padding-bottom: 0.85rem !important;
        }

        .manage-salary-header-row {
            flex-wrap: wrap;
            align-items: center;
            min-width: 0;
        }

        .manage-salary-title-col {
            flex: 1 1 auto;
            max-width: calc(100% - 176px);
            width: calc(100% - 176px);
            margin-bottom: 0.85rem !important;
            padding-right: 0.45rem;
            min-width: 0;
        }

        .manage-salary-action-col {
            flex: 0 0 176px;
            max-width: 176px;
            width: 176px;
            margin-bottom: 0.85rem !important;
            text-align: right !important;
        }

        .manage-salary-table-col {
            flex: 0 0 100%;
            max-width: 100%;
            width: 100%;
        }

        .manage-salary-actions {
            width: 100%;
            gap: 0.35rem;
            justify-content: flex-end;
            flex-wrap: nowrap;
        }

        .manage-salary-title {
            font-size: 0.94rem;
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .manage-salary-cta {
            min-height: 38px;
            padding: 0.5rem 0.62rem;
            border-radius: 12px;
            font-size: 0.56rem;
            letter-spacing: 0.03em;
        }

        .manage-salary-card {
            border-radius: 22px;
        }

        .manage-salary-table thead th,
        .manage-salary-table tbody td {
            padding: 0.82rem 0.78rem;
        }
    }

    @media (max-width: 420px) {
        .manage-salary-title-col {
            max-width: calc(100% - 150px);
            width: calc(100% - 150px);
        }

        .manage-salary-action-col {
            flex: 0 0 150px;
            max-width: 150px;
            width: 150px;
        }

        .manage-salary-cta {
            min-height: 36px;
            padding: 0.46rem 0.54rem;
            font-size: 0.53rem;
            letter-spacing: 0.02em;
            border-radius: 11px;
        }

        .manage-salary-title {
            font-size: 0.86rem;
            white-space: normal;
            overflow: visible;
            text-overflow: clip;
            line-height: 1.15;
        }
    }
</style>
<div class="container-fluid py-4 manage-salary-page">
    <div class="row manage-salary-header-row">
        <div class="col-6 mb-4 d-flex align-items-center manage-salary-title-col">
            <h6 class="mb-0 manage-salary-title">Manage Your Salary</h6>
        </div>
        <div class="col-6 mb-4 text-end manage-salary-action-col">
            <div class="manage-salary-actions">
            <a href="apply_advance_salary" class="btn mb-0 manage-salary-cta manage-salary-cta-dark">Apply Advance</a>
            </div>
        </div>
        <div class="col-12 manage-salary-table-col">
            <div class="card mb-4 manage-salary-card">
                <div class="card-body px-0 pt-0 pb-2 manage-salary-shell">
                    <div class="table-responsive p-0 manage-salary-wrap">
                        <!-- Salaries Table -->
                        <table class="table align-items-center mb-0 manage-salary-table">
                            <thead>
                                <tr>
                                   
                                    <th>Employee</th>
                                    <th>Year</th>
                                    <th>Month</th>
                                    <th>Gross Salary</th>
                                    <th>Net Salary</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($salaries) > 0): // Check if there are salaries ?>
                                    <?php foreach ($salaries as $salary): // Loop through the salaries array ?>
                                        <tr>
                                          
                                            <td><?= $salary['employee_name'] ?></td>
                                            <td><?= $salary['year'] ?></td>
                                            <td><?= date("F", mktime(0, 0, 0, $salary['month'], 10)) ?></td>
                                            <td><?= number_format($salary['gross_salary'], 2) ?></td>
                                            <td><?= number_format(((float) $salary['net_salary']) + ((float) $salary['retention_bonus']) + ((float) $salary['leave_encashment']), 2) ?></td>
                                            <td>
                                              
                                                <a href="download_salary_slip?id=<?= $salary['id'] ?>" class="btn btn-sm manage-salary-download"><i class="bi bi-download"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center manage-salary-empty">No salaries found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End Navbar -->
<?php include("footer.php") ?>


