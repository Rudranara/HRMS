<?php
// Include database connection
include 'header.php';

// Fetch salary details
$salary_id = $_GET['id'] ?? null;
if (!$salary_id) {
    echo "<div class='alert alert-danger'>Invalid salary ID.</div>";
    exit;
}

$stmt = $conn->prepare("
    SELECT 
        s.*, e.name AS employee_name, e.employee_id, e.designation, e.department 
    FROM salary s
    JOIN employees e ON s.employee_id = e.id
    WHERE s.id = ?
");
$stmt->bind_param("i", $salary_id);
$stmt->execute();
$salary = $stmt->get_result()->fetch_assoc();

if (!$salary) {
    echo "<div class='alert alert-danger'>Salary not found.</div>";
    exit;
}
?>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-body px-4 pt-4 pb-2">
                    <h3 class="text-center mb-4">Salary Slip for <?= date("F", mktime(0, 0, 0, $salary["month"], 10)) ?></h3>

                    <!-- Personal Details -->
                    <div class="container">
        <div class="header">
           <img src="assets/img/logos/greenwey_logo.png" style="height:60px; width:200px" alt="Company Logo">
        </div>
        <div class="row">
        <div class="col-md-6">
        <div class="details">
            <h3 class="section-title">Employee Details</h3>
            <div class="table-responsive p-0">
            <table class="table align-items-center mb-0">
                <tr>
                    <th>Employee Name</th>
                    <td><?= $salary["employee_name"] ?></td>
                </tr>
                <tr>
                    <th>Employee ID</th>
                    <td><?= $salary["employee_id"] ?></td>
                </tr>
                <tr>
                    <th>Designation</th>
                    <td><?= $salary["designation"] ?></td>
                </tr>
                <tr>
                    <th>Department</th>
                    <td><?= $salary["department"] ?></td>
                </tr>
            </table>
        </div>
        </div>
        </div>
        <div class="col-md-6">
        <div class="summary">
            <h3 class="section-title">Summary</h3>
            <div class="table-responsive p-0">
            <table class="table align-items-center ">
                <tr>
                    <th>Total Earnings</th>
                    <td><?= number_format($salary["gross_salary"], 2) ?></td>
                </tr>
                <tr>
                    <th>Total Deductions</th>
                    <td><?= number_format($salary["total_deductions"], 2) ?></td>
                </tr>
                <tr>
                    <th>Net Salary</th>
                    <td><?= number_format($salary["net_salary"], 2) ?></td>
                </tr>
            </table>
        </div>
        </div>
        </div>
        </div>
        <div class="row">
        <div class="col-md-6">
        <div class="deductions">
            <h3 class="section-title">Deductions</h3>
            <div class="table-responsive p-0">
            <table class="table align-items-center mb-0">
                <tr>
                    <th>EPF (Employer)</th>
                    <td><?= number_format($salary["epf_employer"], 2) ?></td>
                </tr>
                <tr>
                    <th>EPF (Employee)</th>
                    <td><?= number_format($salary["epf_employee"], 2) ?></td>
                </tr>
                <tr>
                    <th>Professional Tax</th>
                    <td><?= number_format($salary["professional_tax"], 2) ?></td>
                </tr>
                <tr>
                    <th>Income Tax</th>
                    <td><?= number_format($salary["income_tax"], 2) ?></td>
                </tr>
                <tr>
                    <th>Insurance Premium</th>
                    <td><?= number_format($salary["insurance_premium"], 2) ?></td>
                </tr>
             
                </tr>
                <tr>
                    <th>Other Deductions</th>
                    <td><?= number_format($salary["other_deductions"], 2) ?></td>
                </tr>
               
            </table>
        </div>
        </div>
        </div>
    
        <div class="col-md-6">
      <div class="allowances">
            <h3 class="section-title">Allowances</h3>
            <div class="table-responsive p-0">
            <table class="table align-items-center mb-0">
                <tr>
                    <th>Basic</th>
                    <td><?= number_format($salary["basic"], 2) ?></td>
                </tr>
                <tr>
                    <th>DA</th>
                    <td><?= number_format($salary["da"], 2) ?></td>
                </tr>
                <tr>
                    <th>HRA</th>
                    <td><?= number_format($salary["hra"], 2) ?></td>
                </tr>
                <tr>
                    <th>Conveyance</th>
                    <td><?= number_format($salary["conveyance"], 2) ?></td>
                </tr>
                <tr>
                    <th>Special Allowance</th>
                    <td><?= number_format($salary["special_allowance"], 2) ?></td>
                </tr>
                <tr>
                    <th>Performance Allowances</th>
                    <td><?= number_format($salary["performance_bonus"], 2) ?></td>
                </tr>
                <tr>
                    <th>Medical Allowances</th>
                    <td><?= number_format($salary["medical_allowance"], 2) ?></td>
                </tr>
                <tr>
                    <th>Washing Allowances</th>
                    <td><?= number_format($salary["washing_allowance"], 2) ?></td>
                </tr>
                <tr>
                    <th>Canteen Allowances</th>
                    <td><?= number_format($salary["canteen_allowance"], 2) ?></td>
                </tr>
                <tr>
                    <th>Other Allowances</th>
                    <td><?= number_format($salary["other_allowances"], 2) ?></td>
                </tr>
            </table>
        </div>
        </div>
        </div>
        </div>
        <div class="footer">
            This is a system-generated salary slip. If you have any questions, please contact HR.
        </div>
    </div>

                    <!-- Footer Section -->
                    <div class="row mt-4">
                        <div class="col-md-12 text-center">
                            <p>This is a system-generated salary slip. If you have any questions, please contact HR.</p>
                            <a href="manage_salary" class="btn bg-gradient-dark">Back</a>
                            <a href="download_salary_slip?id=<?= $salary['id'] ?>" class="btn btn-success">Download Slip</a>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
<style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
          background:rgba(223, 230, 238, 0.65);
        }
        .container {
            width: 80%;
            margin: 20px auto;
           
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        .header {
            text-align: center;
            padding: 10px 0;
            border-bottom: 2px solid #007bff;
        }
        .header img {
            width: 100px;
            height: auto;
        }
        .header h2 {
            margin: 5px 0 0;
            color: #333;
        }
        .details, .allowances, .deductions, .summary {
            margin-top: 20px;
        }
        .details table, .allowances table, .deductions table, .summary table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
           
            color: #333;
        }
        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #007bff;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        .summary td {
            font-weight: bold;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: #888;
        }
    </style>
<!-- End Navbar -->
<?php include("footer.php") ?>


