<!DOCTYPE html>
<html>
<head>
 <link href='https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,800' rel='stylesheet' />
  <link href='https://demos.creative-tim.com/soft-ui-dashboard/assets/css/nucleo-icons.css' rel='stylesheet' />
  <link href='https://demos.creative-tim.com/soft-ui-dashboard/assets/css/nucleo-svg.css' rel='stylesheet' />
  <script src='https://kit.fontawesome.com/42d5adcbca.js' crossorigin='anonymous'></script>
  <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css'>
  <link id='pagestyle' href='assets/css/soft-ui-dashboard.css?v=1.1.0' rel='stylesheet' />
    <title>Salary Slip</title>
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
</head>
<body>
    <div class='container'>
        <div class='header'>
           <img src='file://$company_logo' alt='Company Logo'>

            <h2>$company_name</h2>
            <p>Salary Slip for " . date("F Y", mktime(0, 0, 0, $salary['month'], 10, $salary['year'])) . "</p>
        </div>
  <div class='row'>
 <div class='col-md-3'>
        <div class='details'>
            <h3 class='section-title'>Employee Details</h3>
            <table>
                <tr>
                    <th>Employee Name</th>
                    <td>{$salary['employee_name']}</td>
                </tr>
                <tr>
                    <th>Employee ID</th>
                    <td>{$salary['employee_id']}</td>
                </tr>
                <tr>
                    <th>Designation</th>
                    <td>{$salary['designation']}</td>
                </tr>
                <tr>
                    <th>Department</th>
                    <td>{$salary['department']}</td>
                </tr>
            </table>
        </div>
 </div>
 <div class='col-md-3'>
          <div class='summary'>
            <h3 class='section-title'>Summary</h3>
            <table>
                <tr>
                    <th>Total Earnings</th>
                    <td>" . number_format($salary['gross_salary'], 2) . "</td>
                </tr>
                <tr>
                    <th>Total Deductions</th>
                    <td>" . number_format($salary['total_deductions'], 2) . "</td>
                </tr>
                <tr>
                    <th>Net Salary</th>
                    <td>" . number_format($salary['net_salary'], 2) . "</td>
                </tr>
            </table>
        </div>
  </div>
        </div>
          <div class='row'>
                                <div class='col-md-3'>
        <div class='deductions'>
            <h3 class='section-title'>Deductions</h3>
            <table>
                <tr>
                    <th>EPF (Employer)</th>
                    <td>" . number_format($salary['epf_employer'], 2) . "</td>
                </tr>
                <tr>
                    <th>EPF (Employee)</th>
                    <td>" . number_format($salary['epf_employee'], 2) . "</td>
                </tr>
                <tr>
                    <th>Professional Tax</th>
                    <td>" . number_format($salary['professional_tax'], 2) . "</td>
                </tr>
                <tr>
                    <th>Income Tax</th>
                    <td>" . number_format($salary['income_tax'], 2) . "</td>
                </tr>
                <tr>
                    <th>Other Deductions</th>
                    <td>" . number_format($salary['other_deductions'], 2) . "</td>
                </tr>
            </table>
        </div>
                </div>
<div class='col-md-3'>
     
 <div class='allowances'>
            <h3 class='section-title'>Allowances</h3>
            <table>
                <tr>
                    <th>Basic</th>
                    <td>" . number_format($salary['basic'], 2) . "</td>
                </tr>
                <tr>
                    <th>DA</th>
                    <td>" . number_format($salary['da'], 2) . "</td>
                </tr>
                <tr>
                    <th>HRA</th>
                    <td>" . number_format($salary['hra'], 2) . "</td>
                </tr>
                <tr>
                    <th>Conveyance</th>
                    <td>" . number_format($salary['conveyance'], 2) . "</td>
                </tr>
                <tr>
                    <th>Special Allowance</th>
                    <td>" . number_format($salary['special_allowance'], 2) . "</td>
                </tr>
                <tr>
                    <th>Other Allowances</th>
                    <td>" . number_format($salary['other_allowances'], 2) . "</td>
                </tr>
            </table>
        </div>
          </div>
        </div>

        <div class='footer'>
            This is a system-generated salary slip. If you have any questions, please contact HR.
        </div>
    </div>

      <script src='assets/js/core/popper.min.js'></script>
  <script src='assets/js/core/bootstrap.min.js'></script>
  <script src='assets/js/plugins/perfect-scrollbar.min.js'></script>
  <script src='assets/js/plugins/smooth-scrollbar.min.js'></script>
  <script src='assets/js/plugins/chartjs.min.js'></script>
  <!-- Github buttons -->
  <script async defer src='https://buttons.github.io/buttons.js'></script>
  <!-- Control Center for Soft Dashboard: parallax effects, scripts for the example pages etc -->
  <script src='assets/js/soft-ui-dashboard.min.js?v=1.1.0'></script>
</body>
</html>