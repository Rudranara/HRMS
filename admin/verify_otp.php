<?php
// 24-hour session lifetime
ini_set('session.gc_maxlifetime', 172800);
session_set_cookie_params([
    'lifetime' => 172800,
    'path'     => '/',
    'domain'   => '',
    'secure'   => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();
require 'db_connection.php'; // Include database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $otp = $_POST['otp'];
    $admin_id = $_SESSION['otp_admin_id'];

    // Validate OTP
    $stmt = $conn->prepare("SELECT * FROM otp_codes WHERE admin_id = ? AND otp_code = ? AND expires_at > NOW() AND used = 0");
    $stmt->bind_param("is", $admin_id, $otp);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $otp_record = $result->fetch_assoc();

        // Mark OTP as used
        $stmt = $conn->prepare("UPDATE otp_codes SET used = 1 WHERE id = ?");
        $stmt->bind_param("i", $otp_record['id']);
        $stmt->execute();


           // Fetch employee details
           $stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
           $stmt->bind_param("i", $admin_id);
           $stmt->execute();
           $admin_data = $stmt->get_result()->fetch_assoc();
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $admin_data['id'];
        $_SESSION['admin_employee_id'] = $admin_data['employee_id'];
        $_SESSION['admin_name'] = $admin_data['name'];
        $_SESSION['admin_email'] = $admin_data['email'];
        $_SESSION['admin_role'] = $admin_data['role'];
        $_SESSION['ADMIN_LOGIN_TIME'] = time();
        header("Location: dashboard");
        exit;
    } else {
     
        echo "<script>alert('Invalid or expired OTP.');</script>";
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
                <img src="../uploads/org/<?= $org['logo'] ?>" class="navbar-brand-img h-100" style="max-height:55px;" alt="main_logo">
            <?php else: ?>
                <img src="assets/img/att logo.png" class="navbar-brand-img h-100" style="max-height:55px;" alt="main_logo">
            <?php endif; ?>
            </a>
            <button class="navbar-toggler shadow-none ms-2" type="button" data-bs-toggle="collapse" data-bs-target="#navigation" aria-controls="navigation" aria-expanded="false" aria-label="Toggle navigation">
              <span class="navbar-toggler-icon mt-2">
                <span class="navbar-toggler-bar bar1"></span>
                <span class="navbar-toggler-bar bar2"></span>
                <span class="navbar-toggler-bar bar3"></span>
              </span>
            </button>
            <div class="collapse navbar-collapse" id="navigation">
              <ul class="navbar-nav mx-auto ms-xl-auto me-xl-7">
                <li class="nav-item">
                  <a class="nav-link d-flex align-items-center me-2 active" aria-current="page" href="dashboard">
                    <i class="fa fa-chart-pie opacity-6 text-dark me-1"></i>
                    Home
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link me-2" href="profile">
                    <i class="fa fa-user opacity-6 text-dark me-1"></i>
                    Contact
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link me-2" href="sign-up">
                    <i class="fas fa-user-circle opacity-6 text-dark me-1"></i>
                    Features
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link me-2" href="sign-in">
                    <i class="fas fa-key opacity-6 text-dark me-1"></i>
                    About
                  </a>
                </li>
              </ul>
              <li class="nav-item d-flex align-items-center">
                <a class="btn btn-round btn-sm mb-0 btn-outline-primary me-2" target="_blank" href="index">Book Demo</a>
              </li>
              <ul class="navbar-nav d-lg-block d-none">
                <li class="nav-item">
                  <a href="index" class="btn btn-sm btn-round mb-0 me-1 bg-gradient-dark">Admin</a>
                </li>
              </ul>
            </div>
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
                  <p class="mb-0">Check Your Registered Mobile No. You Got a OTP</p>
                </div>
                <div class="card-body">
                  <form role="form" method="POST" >
                    <label>Enter 6 Digit OTP</label>
                    <div class="mb-3">
                      <input type="text" name="otp" autofocus required class="form-control" placeholder="OTP" aria-label="Email" aria-describedby="email-addon">
                    </div>
                  
                   
                    <div class="text-center">
                      <button type="submit" class="btn bg-gradient-info w-100 mt-4 mb-0">Verify OTP</button>
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
            </script>At Maison Technology
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
</body>

</html>