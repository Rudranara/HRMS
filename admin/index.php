<?php
session_start();
require 'db_connection.php'; // Include database connection file
date_default_timezone_set('Asia/Kolkata');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $phone = trim($_POST['phone'] ?? '');

    // Check if the admin exists
    $stmt = $conn->prepare("SELECT id FROM admins WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $admin = $result->fetch_assoc();

        // Generate OTP
        $otp = rand(100000, 999999);

        // Calculate expiration time (10 minutes as per DLT template)
        $expires_at = date("Y-m-d H:i:s", strtotime("+10 minutes"));

        // Save OTP to database
        $stmt = $conn->prepare("INSERT INTO otp_codes (admin_id, otp_code, expires_at, used) VALUES (?, ?, ?, 0)");
        $stmt->bind_param("iss", $admin['id'], $otp, $expires_at);
        $stmt->execute();

        // Prepare SMS message using DLT template
        $otp_message = "Dear user, your One Time Password (OTP) for login to MyAttendance (by MAISON) is {$otp}. Please do not share this OTP with anyone. It is valid for 10 minutes.";

        // Clean and normalize phone number
        $digits = preg_replace('/\D+/', '', $phone);
        if (strlen($digits) == 10) {
            $recipient = '91' . $digits;
        } elseif (substr($digits, 0, 2) == '91') {
            $recipient = $digits;
        } else {
            $recipient = '91' . $digits;
        }

        // DigitalSMS API credentials and setup
        $apiToken = 'Bearer 260|TygpJwLYXZOWV5NjsvfraMbhE6ElPl3yfBAPmQV56b30f9e8';
        $sendUrl = 'https://sms.digitalsms.net/api/v3/sendsms';
        $sender_id = 'MTINPL';
        $entity_id = '1701176215013752482';
        $dlt_template_id = '1707176225104504499';

        $payload = array(
            "recipient" => $recipient,
            "sender_id" => $sender_id,
            "entity_id" => $entity_id,
            "type" => "transactional",
            "dlt_template_id" => $dlt_template_id,
            "message" => $otp_message
        );

        // Send SMS using cURL
        $ch = curl_init($sendUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: ' . $apiToken
        ));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_err = curl_error($ch);
        curl_close($ch);

        if ($response && $httpcode >= 200 && $httpcode < 300) {
            $_SESSION['otp_admin_id'] = $admin['id'];
            header("Location: verify_otp");
            exit;
        } else {
            error_log("OTP send failed. HTTP: $httpcode | Error: $curl_err | Response: $response");
            echo "<script>alert('Failed to send OTP. Please try again later.');</script>";
        }
    } else {
        echo "<script>alert('You are Not an Admin');</script>";
    }
}
$sql = "SELECT * FROM organization LIMIT 1";
$result = $conn->query($sql);
$org = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="apple-touch-icon" sizes="76x76" href="assets/img/apple-icon.png">
  <link rel="icon" type="image/png" href="assets/img/favicon.png">
  <title>
    My Attendance System
  </title>
  <!--     Fonts and icons     -->
  <link href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,800" rel="stylesheet" />
  <!-- Nucleo Icons -->
  <link href="https://demos.creative-tim.com/soft-ui-dashboard/assets/css/nucleo-icons.css" rel="stylesheet" />
  <link href="https://demos.creative-tim.com/soft-ui-dashboard/assets/css/nucleo-svg.css" rel="stylesheet" />
  <!-- Font Awesome Icons -->
  <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
  <!-- CSS Files -->
  <link id="pagestyle" href="assets/css/soft-ui-dashboard.css?v=1.1.0" rel="stylesheet" />
  <!-- Nepcha Analytics (nepcha.com) -->
  <!-- Nepcha is a easy-to-use web analytics. No cookies and fully compliant with GDPR, CCPA and PECR. -->
  <script defer data-site="YOUR_DOMAIN_HERE" src="https://api.nepcha.com/js/nepcha-analytics.js"></script>
</head>
<body class="">
  <div class="container position-sticky z-index-sticky top-0">
    <div class="row">
      <div class="col-12">
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg blur blur-rounded top-0 z-index-3 shadow position-absolute my-3 py-2 start-0 end-0 mx-4">
          <div class="container-fluid pe-0">
            <a class="navbar-brand font-weight-bolder ms-lg-0 ms-3 " href="dashboard">
            <?php if (!empty($org['logo']) && file_exists("../uploads/org/" . $org['logo'])): ?>
    <img src="../uploads/org/<?= $org['logo'] ?>" class="navbar-brand-img h-100" style="max-height:70px;" alt="main_logo">
<?php else: ?>
    <img src="assets/img/att logo.png" class="navbar-brand-img h-100" style="max-height:70px;" alt="main_logo">
<?php endif; ?>
            </a>
            <button class="navbar-toggler shadow-none ms-2" type="button" data-bs-toggle="collapse" data-bs-target="#navigation" aria-controls="navigation" aria-expanded="false" aria-label="Toggle navigation">
              <span class="navbar-toggler-icon mt-2">
                <span class="navbar-toggler-bar bar1"></span>
                <span class="navbar-toggler-bar bar2"></span>
                <span class="navbar-toggler-bar bar3"></span>
              </span>
            </button>
        
          </div>
        </nav>
        <!-- End Navbar -->
      </div>
    </div>
  </div>
  <main class="main-content  mt-0">
    <section>
      <div class="page-header min-vh-75">
        <div class="container">
          <div class="row">
            <div class="col-xl-4 col-lg-5 col-md-6 d-flex flex-column mx-auto">
              <div class="card card-plain mt-8">
                <div class="card-header pb-0 text-left bg-transparent">
                  <h3 class="font-weight-bolder text-info text-gradient">Welcome back</h3>
                  <p class="mb-0">You Will Receive A Six Digit OTP On Your Registered Mobile No.</p>
                </div>
                <div class="card-body">
                  <form role="form" method="POST" >
                    <label>Enter Mobile No</label>
                    <div class="mb-3">
                      <input type="text" name="phone" autofocus required class="form-control" placeholder="Mobile Number" aria-label="Email" aria-describedby="email-addon">
                    </div>
                  
                    <div class="form-check form-switch">
                      <input class="form-check-input" type="checkbox" id="rememberMe" checked="">
                      <label class="form-check-label" for="rememberMe">Remember me</label>
                    </div>
                    <div class="text-center">
                      <button type="submit" class="btn bg-gradient-info w-100 mt-4 mb-0">Send OTP</button>
                    </div>
                  </form>
                </div>
                <div class="card-footer text-center pt-0 px-lg-2 px-1">
                  <p class="mb-4 text-sm mx-auto">
                    Don't have an account?
                    <a href="javascript:;" class="text-info text-gradient font-weight-bold">Sign up</a>
                  </p>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="oblique position-absolute top-0 h-100 d-md-block d-none me-n8">
                <div class="oblique-image bg-cover position-absolute fixed-top ms-auto h-100 z-index-0 ms-n6" style="background-image:url('assets/img/login\ bg.jpg')"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>
  <!-- -------- START FOOTER 3 w/ COMPANY DESCRIPTION WITH LINKS & SOCIAL ICONS & COPYRIGHT ------- -->
  <footer class="footer py-5">
    <div class="container">

      <div class="row">
        <div class="col-8 mx-auto text-center mt-1">
          <p class="mb-0 text-secondary">
            Copyright © <script>
              document.write(new Date().getFullYear())
            </script>, Maison Technology All Rights Reserved.
          </p>
        </div>
      </div>
    </div>
  </footer>
  <!-- -------- END FOOTER 3 w/ COMPANY DESCRIPTION WITH LINKS & SOCIAL ICONS & COPYRIGHT ------- -->
  <!--   Core JS Files   -->
  <script src="assets/js/core/popper.min.js"></script>
  <script src="assets/js/core/bootstrap.min.js"></script>
  <script src="assets/js/plugins/perfect-scrollbar.min.js"></script>
  <script src="assets/js/plugins/smooth-scrollbar.min.js"></script>
  <script>
    var win = navigator.platform.indexOf('Win') > -1;
    if (win && document.querySelector('#sidenav-scrollbar')) {
      var options = {
        damping: '0.5'
      }
      Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
    }
  </script>
  <!-- Github buttons -->
  <script async defer src="https://buttons.github.io/buttons.js"></script>
  <!-- Control Center for Soft Dashboard: parallax effects, scripts for the example pages etc -->
  <script src="assets/js/soft-ui-dashboard.min.js?v=1.1.0"></script>
  <style>
      @media (max-width: 991px){
          .navbar-toggler{
              display: none !important;
          }
      }
  </style>
</body>

</html>
