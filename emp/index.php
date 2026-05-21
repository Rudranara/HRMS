<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.all.min.js"></script>

<?php
// Session lifetime: 48 hours
$session_lifetime = 60 * 60 * 48;

// Optional but recommended GC tuning
ini_set('session.gc_maxlifetime', $session_lifetime);
ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 100);

// Cookie settings MUST be before session_start
session_set_cookie_params([
    'lifetime' => $session_lifetime,
    'path' => '/',
    'domain' => '',
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();



require 'db_connection.php';


// Check if the user is already logged in
if (isset($_SESSION['employee_logged_in']) && $_SESSION['employee_logged_in'] === true) {
    header("Location: add_attendance");
    exit;
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employee_id = $_POST['employee_id'];
    $password = $_POST['password'];

    // Validate credentials
    $stmt = $conn->prepare("SELECT * FROM employees WHERE employee_id = ?");
    $stmt->bind_param("s", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $employee_data = $result->fetch_assoc();

        // Check if the employee is Active
        if ($employee_data['status'] !== 'Active') {
            echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Access Denied!',
                    text: 'You are not Active by your manager.',
                    icon: 'warning',
                    timer: 3000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = 'index';
                });
            });
            </script>";
            exit;
        }

        // Verify password
        if (password_verify($password, $employee_data['password'])) {

            session_regenerate_id(true);
            // Set session variables
            $_SESSION['employee_logged_in'] = true;
            $_SESSION['employee_id'] = $employee_data['id'];
            $_SESSION['employee_unique_id'] = $employee_data['employee_id'];
            $_SESSION['employee_name'] = $employee_data['name'];
            $_SESSION['employee_email'] = $employee_data['email'];
            $_SESSION['employee_role'] = $employee_data['role'];
            $_SESSION['employee_designation'] = $employee_data['designation'];
            $_SESSION['employee_photo'] = $employee_data['photo'];

            $_SESSION['remember_me'] = !empty($_POST['remember_me']);

            $_SESSION['LOGIN_TIME'] = time();
            $_SESSION['LAST_ACTIVITY'] = time();

            // Redirect to dashboard
            header("Location: add_attendance");
            exit;
        } else {
            echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Invalid password!',
                    text: 'Try with the correct details.',
                    icon: 'error',
                    timer: 3000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = 'index';
                });
            });
            </script>";
        }
    } else {
        echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Employee ID not found!',
                text: 'Try with your correct employee ID.',
                icon: 'error',
                timer: 3000,
                showConfirmButton: false
            }).then(() => {
                window.location.href = 'index';
            });
        });
        </script>";
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
    My Attendance System Employee Panel
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
                </div>
                <div class="card-body">
                  <form role="form" method="POST" >
                    <label>Enter Employee ID</label>
                    <div class="mb-3">
                      <input type="text" name="employee_id" id="employee_id" class="form-control" placeholder="Enter your Employee id" required>
                    </div>
                    <label>Enter Password</label>
                    <div class="mb-3">
                      <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required>
                    </div>
                    
                    <div class="form-check form-switch">
                      <input class="form-check-input" type="checkbox" id="rememberMe" name="remember_me" value="1">
                      <label class="form-check-label" for="rememberMe">Remember me</label>
                    </div>

                    <div class="text-center">
                      <button type="submit" class="btn bg-gradient-info w-100 mt-4 mb-0">Log in</button>
                    </div>
                  </form>
                </div>
                <div class="card-footer text-center pt-0 px-lg-2 px-1">
                  <p class="mb-4 text-sm mx-auto">
                   Forgot Employee ID ? 
                    <a href="" class="text-info text-gradient font-weight-bold">Contact Your HR/Manager</a>
                   
            <a href="register" class="btn btn-sm btn-round mb-0 me-1 bg-gradient-primary">Register</a>
       
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
      @media (max-width: 991px) {
          .navbar-toggler {
              display: none !important;
          }
      }
    </style>

</body>
</html>