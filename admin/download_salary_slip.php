<?php
require 'dompdf/vendor/autoload.php';// Include Dompdf autoload
use Dompdf\Dompdf;
// Include database connection
require 'db_connection.php';
// Fetch salary details
$salary_id = $_GET['id'] ?? null;
if (!$salary_id) {
    die("Invalid salary ID.");
}
$stmt = $conn->prepare("
    SELECT 
        s.*,
        e.name AS employee_name,
        e.employee_id,
        e.designation,
        e.epf_number,
        e.esic,
        e.bank_account,
        e.ifsc_code,
        e.pan_number,
        e.father_name,
        e.department
    FROM salary s
    JOIN employees e ON s.employee_id = e.id
    WHERE s.id = ?
");
$stmt->bind_param("i", $salary_id);
$stmt->execute();
$salary = $stmt->get_result()->fetch_assoc();
if (!$salary) {
    die("Salary record not found.");
}

$company_name = "Maison Technology";
$contact = "Plot No.167, Saheed Nagar Bhubaneswar, Odisha, 751007, India.";
$company_logo ="https://myattendance.co.in/maison/uploads/org/1767606492_maison-logo.png"; // Replace with your logo path

// Total Expenses (Arrear / Advance / Expenses If Any)
$total_expenses =
    ($salary['retention_bonus'] ?? 0) +
    ($salary['leave_encashment'] ?? 0);

$otherAllowancesTotal =
    ($salary['other_allowances'] ?? 0) +
    ($salary['canteen_allowance'] ?? 0) +
    ($salary['washing_allowance'] ?? 0) +
    ($salary['medical_allowance'] ?? 0) +
    ($salary['special_allowance'] ?? 0);

$otherDeductionsTotal =
    ($salary['other_deductions'] ?? 0) +
    ($salary['advance'] ?? 0) +
    ($salary['insurance_premium'] ?? 0);

$summaryTotalDeductions =
    ($salary['epf_employee'] ?? 0) +
    ($salary['esic_employee'] ?? 0) +
    ($salary['professional_tax'] ?? 0) +
    ($salary['income_tax'] ?? 0) +
    $otherDeductionsTotal;



// Create HTML for the salary slip
$html = "
<!DOCTYPE html>
<html>
<head>
    <title>Salary Slip</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
          background:rgba(223, 230, 238, 0.65);
        }
        .container {
            width: 100%;
            margin: 3px auto;
           
            padding: 1px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        .header {
            text-align: center;
            padding: 5px 0;
            border-bottom: 2px solid #007bff;
        }
        .header img {
            width: 120px;
            height: auto;
            margin:0px;
        }
        .header h2 {
            margin: 2px 0 0;
            color: #333;
        }
              .header h4 {
            margin: 2px 0 0;
            color: #333;

        }

          .header h5 {
            margin: 3px 0 0;
            color: #333;
        }
   .section-title {
    font-size: 14px;
    font-weight: bold;
    color: #007bff;
    margin: 5px 15px 10px 15px;
}

.details, .allowances, .deductions, .summary {
    margin-left: 15px;
    margin-right: 15px;
}

.details table, .allowances table, .deductions table, .summary table {
    width: 100%;
    border-collapse: collapse;
    margin-left: 15px;
    margin-right: 15px;
}

        th, td {
            padding: 7px;
            text-align: left;
            border: 1px solid #ddd;
            font-size: 10px;
          
            
        }
        th {
            margin-left:15px;
                 margin-right:15px;
            color: #333;
        }
   
        .summary td {
            font-weight: bold;
        }
        .footer {
            text-align: center;
            margin-top: 5px;
            font-size: 10px;
            color: #888;
        }


        .details table {
            table-layout: fixed;   /* Important for equal column control */
        }

        .details th:nth-child(1),
        .details th:nth-child(3) {
            width: 20%;
        }

        .details td:nth-child(2),
        .details td:nth-child(4) {
            width: 30%;
        }

    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
           <img src='$company_logo' alt='Company Logo'>
            <h4>$company_name</h4>
            <h5>$contact</h5>
            <p>Salary Slip for " . date("F Y", mktime(0, 0, 0, $salary['month'], 10, $salary['year'])) . "</p>
        </div>

        <div class='details'>
            <h3 class='section-title'>Employee Details</h3>
            <table>
                <tr>
                    <th>Employee Name</th>
                    <td>{$salary['employee_name']}</td>
                    <th>PAN</th>
                    <td>{$salary['pan_number']}</td>
                </tr>

                <tr>
                    <th>UAN</th>
                    <td>{$salary['epf_number']}</td>
                    <th>ESIC</th>
                    <td>{$salary['esic']}</td>
                </tr>

                <tr>
                    <th>Employee ID</th>
                    <td>{$salary['employee_id']}</td>
                    <th>Account No.</th>
                    <td>{$salary['bank_account']}</td>
                </tr>

                <tr>
                    <th>Designation</th>
                    <td>{$salary['designation']}</td>
                    <th>IFSC</th>
                    <td>{$salary['ifsc_code']}</td>
                </tr>

                <tr>
                    <th>Department</th>
                    <td>{$salary['department']}</td>
                    <th></th>
                    <td></td>
                </tr>
            </table>

        </div>

        <div class='allowances'>
    
     <table style=' border: none'>
     <tr style=' border: none'>
                    <th style=' border: none;text-align: center'><h3 class='section-title mb-0'>Allowances</h3></th>
                   
                    <th style=' border: none; text-align: center'> <h3 class='section-title mb-0'>Deductions</h3></th>
                   
                </tr>
                 </table>
                <table>
                    <tr>
                        <th>Basic</th>
                        <td>" . number_format($salary['basic'] + $salary['da'], 2) . "</td>
                        <th>EPF</th>
                        <td>" . number_format($salary['epf_employee'], 2) . "</td>
                    </tr>

                    <tr>
                        <th>HRA</th>
                        <td>" . number_format($salary['hra'], 2) . "</td>
                        <th>ESIC</th>
                        <td>" . number_format($salary['esic_employee'] ?? 0, 2) . "</td>
                    </tr>

                    <tr>
                        <th>Conveyance</th>
                        <td>" . number_format($salary['conveyance'], 2) . "</td>
                        <th>Professional Tax</th>
                        <td>" . number_format($salary['professional_tax'], 2) . "</td>
                    </tr>

                    <tr>
                        <th>Bonus Advance</th>
                        <td>" . number_format($salary['performance_bonus'], 2) . "</td>
                        <th>Income Tax</th>
                        <td>" . number_format($salary['income_tax'], 2) . "</td>
                    </tr>

                    <tr>
                        <th>Other Allowances</th>
                        <td>" . number_format($otherAllowancesTotal, 2) . "</td>
                        <th>Other Deductions</th>
                        <td>" . number_format($otherDeductionsTotal, 2) . "</td>
                    </tr>
                </table>

        </div>

        <div class='summary'>
            <h3 class='section-title'>Summary</h3>
            <table>
                <tr>
                    <th>Total Earnings</th>
                    <td>" . number_format($salary['gross_salary'], 2) . "</td>
                </tr>
                <tr>
                    <th>Total Expenses</th>
                    <td>" . number_format($total_expenses, 2) . "</td>
                </tr>
                <tr>
                    <th>Total Deductions</th>
                    <td>" . number_format($summaryTotalDeductions, 2) . "</td>
                </tr>
                <tr>
                    <th>Net Salary</th>
                    <td>" . number_format((float) ($salary['net_salary'] ?? 0) + (float) $total_expenses, 2) . "</td>
                </tr>

            </table>
        </div>


        <div class='summary'>
            <h3 class='section-title'>Attendance Summary</h3>
            <table>
                <tr>
                    <th>Working Days</th>
                    <td>{$salary['present_days']}</td>
                    <th>Leave Days</th>
                    <td>{$salary['leave_days']}</td>
                </tr>
                <tr>
                    <th>Total Paid Days</th>
                    <td>{$salary['present_days']}</td>
                    <th>Absent Days</th>
                    <td>{$salary['absent_days']}</td>
                </tr>
            </table>
        </div>



        <div class='footer'>
            This is a system-generated salary slip. If you have any questions, please contact HR.
        </div>
    </div>
</body>
</html>
";
// Initialize Dompdf and configure options
$dompdf = new Dompdf();
$dompdf->set_option('isRemoteEnabled', true); // Enable remote resources
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait'); // Set paper size and orientation
$dompdf->render();

// Output the PDF for download
$dompdf->stream("Salary_Slip_{$salary['employee_id']}_{$salary['employee_name']}.pdf", ["Attachment" => true]);
?>
