<?php
session_start();
require 'db_connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

// Dynamic page detection
$current_page = basename($_SERVER['PHP_SELF']); // Gets the current page name

// Admin details (replace with actual session variable names storing admin details)
$admin_id = $_SESSION['admin_id'] ?? '123';
$admin_name = $_SESSION['admin_name'] ?? 'Admin';
$employee_id = $_SESSION['admin_employee_id'] ?? '00000';
$admin_roll = $_SESSION['admin_roll'] ?? 'Admin';
$admin_email = $_SESSION['admin_email'] ?? 'Not Set';
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
  <!-- Fonts and icons -->
  <link href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,800" rel="stylesheet" />
  <link href="https://demos.creative-tim.com/soft-ui-dashboard/assets/css/nucleo-icons.css" rel="stylesheet" />
  <link href="https://demos.creative-tim.com/soft-ui-dashboard/assets/css/nucleo-svg.css" rel="stylesheet" />
  <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link id="pagestyle" href="assets/css/soft-ui-dashboard.css?v=1.1.0" rel="stylesheet" />
</head>
<style>
#navbarBlur .container-fluid {
  min-height: 62px;
  padding: 0.58rem 0.9rem !important;
  border: 1px solid rgba(87, 96, 108, 0.12);
  border-radius: 24px;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.96) 0%, rgba(246, 248, 251, 0.96) 100%);
  box-shadow: 0 18px 38px rgba(15, 23, 42, 0.06);
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
}

.admin-header-back {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 12px;
  background: linear-gradient(135deg, #123b76 0%, #1f4c8f 100%);
  box-shadow: 0 14px 28px rgba(18, 59, 118, 0.2);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.admin-header-back:hover {
  transform: translateY(-1px);
  box-shadow: 0 18px 30px rgba(18, 59, 118, 0.24);
}

.admin-header-back i {
  color: #ffffff;
  font-size: 0.92rem;
  line-height: 1;
}

.admin-header-toolbar {
  gap: 1rem;
}

.admin-header-search-wrap {
  width: 100%;
  max-width: 360px;
}

.admin-header-search {
  display: flex;
  align-items: center;
  min-height: 42px;
  padding: 0 0.25rem;
  border: 1px solid #d8e0ea;
  border-radius: 16px;
  background: #ffffff;
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8), 0 8px 20px rgba(148, 163, 184, 0.08);
}

.admin-header-search .input-group-text,
.admin-header-search .form-control {
  border: 0 !important;
  background: transparent !important;
  box-shadow: none !important;
}

.admin-header-search .input-group-text {
  color: #94a3b8;
  padding-left: 0.55rem;
}

.admin-header-search .form-control {
  color: #334155;
  padding-right: 0.55rem;
}

.admin-header-search .form-control::placeholder {
  color: #94a3b8;
}

.admin-header-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.75rem;
}

.admin-header-profile-link {
  display: inline-flex !important;
  align-items: center;
  gap: 0.75rem;
  padding: 0.22rem 0.34rem 0.22rem 0.62rem !important;
  border: 1px solid #dbe3ed;
  border-radius: 18px;
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  box-shadow: 0 10px 22px rgba(148, 163, 184, 0.1);
}

.admin-header-profile-meta {
  display: flex;
  flex-direction: column;
  min-width: 0;
  text-align: right;
}

.admin-header-profile-name {
  color: #111827;
  font-size: 0.86rem;
  font-weight: 700;
  line-height: 1.15;
}

.admin-header-profile-role {
  color: #94a3b8;
  font-size: 0.74rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.admin-header-avatar {
  width: 30px !important;
  height: 30px !important;
  margin-right: 0 !important;
  border: 2px solid #ffffff;
  box-shadow: 0 8px 16px rgba(76, 175, 80, 0.18);
}

.admin-header-dropdown {
  min-width: 230px;
  margin-top: 0.9rem !important;
  border: 1px solid rgba(87, 96, 108, 0.12);
  border-radius: 18px;
  box-shadow: 0 22px 48px rgba(15, 23, 42, 0.12);
}

.admin-header-dropdown .dropdown-item {
  border-radius: 14px;
}

@media (max-width: 991.98px) {
  #navbarBlur {
    margin: 0.85rem 1rem 0;
  }

  .admin-header-toolbar {
    gap: 0.8rem;
  }

  .admin-header-search-wrap {
    max-width: none;
  }
}

@media (max-width: 767.98px) {
  #navbarBlur .container-fluid {
    padding: 0.6rem 0.78rem !important;
  }

  .admin-header-profile-meta {
    display: none !important;
  }

  .admin-header-actions {
    gap: 0.5rem;
  }
}

@media (max-width: 767.98px), (max-width: 991.98px) and (hover: none) and (pointer: coarse) {
  #navbarBlur {
    margin-top: 0.55rem;
    margin-left: 0.35rem !important;
    margin-right: 0.35rem !important;
    width: calc(100% - 0.7rem);
    max-width: none;
  }

  #navbarBlur .container-fluid {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.55rem;
    padding: 0.42rem 0.64rem !important;
    min-height: 44px;
    border-radius: 16px;
    box-shadow: 0 12px 26px rgba(15, 23, 42, 0.05);
    width: 100%;
  }

  #navbarBlur nav[aria-label="breadcrumb"],
  .admin-header-search-wrap,
  .admin-header-profile-meta {
    display: none !important;
  }

  .admin-header-toolbar {
    display: flex !important;
    align-items: center;
    justify-content: flex-end;
    gap: 0.55rem;
    flex: 1 1 auto;
    margin-top: 0 !important;
  }

  .admin-header-actions {
    width: 100%;
    justify-content: flex-end;
    gap: 0.55rem;
    flex-wrap: nowrap;
  }

  .admin-header-actions > .mb-2,
  .admin-header-actions > li:empty {
    display: none !important;
  }

  .admin-header-profile-link {
    padding: 0.2rem !important;
    min-width: 36px;
    min-height: 36px;
    border-radius: 14px;
  }

  .admin-header-avatar {
    width: 26px !important;
    height: 26px !important;
    object-fit: cover;
  }
}
</style>
<body class="g-sidenav-show bg-gray-100">
  <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    <!-- Navbar -->
    <nav class="navbar navbar-main navbar-expand-lg px-0 mx-4 shadow-none border-radius-xl" id="navbarBlur" navbar-scroll="true">
      <div class="container-fluid py-1 px-3">
        <nav aria-label="breadcrumb">
        <a href="javascript:history.back()" class="nav-link text-body p-0 admin-header-back" aria-label="Go back" title="Go back">
        <i class="bi bi-arrow-left"></i></a>
        </nav>
        <div class="collapse navbar-collapse mt-sm-0 mt-2 me-md-0 me-sm-4 admin-header-toolbar" id="navbar">
          <div class="ms-md-auto pe-md-0 d-flex align-items-center input-group-main admin-header-search-wrap">
            <div class="input-group admin-header-search">
              <span class="input-group-text text-body"><i class="fas fa-search" aria-hidden="true"></i></span>
              <input type="text" class="form-control" placeholder="Type here...">
            </div>
          </div>
          <ul class="navbar-nav justify-content-end admin-header-actions">
            <li class="nav-item dropdown pe-2 d-flex align-items-center" >
              <a href="javascript:;" class="nav-link text-body p-0 admin-header-profile-link" id="adminProfileMenu" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="admin-header-profile-meta">
                  <span class="admin-header-profile-name"><?= htmlspecialchars($admin_name) ?></span>
                  <span class="admin-header-profile-role"><?= htmlspecialchars($admin_roll) ?></span>
                </div>
                <img src="assets/img/logos/user.png" class="avatar avatar-sm admin-header-avatar" alt="User">
              </a>
              <ul class="dropdown-menu dropdown-menu-end px-2 py-3 me-sm-n4 admin-header-dropdown" aria-labelledby="adminProfileMenu">
                <li class="mb-2">
                  <a class="dropdown-item border-radius-md" href="javascript:;">
                    <div class="d-flex py-1">
                      <div class="d-flex flex-column justify-content-center">
                        <h6 class="text-sm font-weight-normal mb-1">
                          <span class="font-weight-bold"><?= htmlspecialchars($admin_name) ?></span>
                        </h6>
                        <p class="text-xs text-secondary mb-0 ">
                          <i class="fa fa-clock me-1"></i>
                         Employee ID - <?= htmlspecialchars($employee_id) ?>
                        </p>
                      </div>
                    </div>
                  </a>
                </li>
                <li>
                  <a class="dropdown-item border-radius-md" href="logout.php">
                    <div class="d-flex py-1">
              
                      <div class="d-flex flex-column justify-content-center">
                        <h6 class="text-sm font-weight-normal mb-1">
                          Logout
                        </h6>
                        <p class="text-xs text-secondary mb-0 ">
                          <i class="fa fa-clock me-1"></i>
                          From All Device
                        </p>
                      </div>
                    </div>
                  </a>
                </li>
              </ul>
            </li>
            <li class="mb-2">
            </li>
            <li>

</li>
<li class="nav-item d-xl-none ps-0 d-flex align-items-center">
<a href="javascript:history.back()" class="nav-link text-body p-0 admin-header-back" aria-label="Go back" title="Go back">
<i class="bi bi-arrow-left"></i></a>
            </li>
          </ul>
        </div>
      </div>
    </nav>
<?php
// Get admin subscription details
$admin_id = $_SESSION['admin_id'];
$check_sub = $conn->prepare("SELECT subscription_expire_date FROM admins WHERE id = ?");
$check_sub->bind_param("i", $admin_id);
$check_sub->execute();
$sub_result = $check_sub->get_result();
$sub_data = $sub_result->fetch_assoc();

$today = date('Y-m-d');
$expire_date = $sub_data['subscription_expire_date'];
$expired = false;
$show_reminder_modal = false;
$reminder_message = '';

// Check if subscription expired
if (empty($expire_date) || $expire_date < $today) {
    $expired = true;
} else {
    // Calculate days left until expiration
    $days_left = (strtotime($expire_date) - strtotime($today)) / (60 * 60 * 24);

    // Set specific reminder messages
    if ($days_left == 1) {
        $reminder_message = '⏳ Your subscription will expire tomorrow. Please renew to avoid service interruption.';
        $show_reminder_modal = !isset($_SESSION['reminder_shown']);
    } elseif ($days_left == 0) {
        $reminder_message = '⚠️ Your subscription will expire today. Please renew now to avoid losing access.';
        $show_reminder_modal = !isset($_SESSION['reminder_shown']);
    } elseif ($days_left <= 10 && $days_left > 1) {
        $reminder_message = "🔔 Your subscription will expire in $days_left days. Please renew soon.";
        $show_reminder_modal = !isset($_SESSION['reminder_shown']);
    }
}
?>

<!-- Expired Modal -->
<?php if ($expired): ?>
<div class="modal fade show" id="renewalModal" tabindex="-1" style="display: block; background: rgba(0, 0, 0, 0.5);" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Subscription Expired</h5>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <h6>Your subscription has expired. Please renew to continue accessing the dashboard.</h6>
                </div>
            </div>
            <div class="modal-footer">
                <a href="subscription_plans.php" class="btn btn-primary">Proceed to Choose Plan</a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Reminder Modal (Shows only once before expiration) -->
<?php if ($show_reminder_modal && !$expired): ?>
<div id="reminderModal" style="display: block;">
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <strong><?= $reminder_message ?></strong>
        <button class="btn btn-info" onclick="closeReminderModal()">Okay, Got It</button>
        <a href="subscription_plans.php" class="btn btn-primary">Renew Now</a>
    </div>
</div>
<script>
function closeReminderModal() {
    document.getElementById('reminderModal').style.display = 'none';
    fetch('dismiss_reminder.php'); // Mark reminder as seen
}
</script>
<?php $_SESSION['reminder_shown'] = true; ?>
<?php endif; ?>
