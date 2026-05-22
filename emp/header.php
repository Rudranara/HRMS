<?php
require_once 'session_check.php';
require 'db_connection.php';
date_default_timezone_set('Asia/Kolkata');


// if (!isset($_SESSION['employee_logged_in']) || $_SESSION['employee_logged_in'] !== true) {
//   header("Location: ../index.php");
//   exit;
// }
$current_page = basename($_SERVER['PHP_SELF']); // Gets the current page name
$current_view = $_GET['view'] ?? 'my-dashboard';
// Retrieve logged-in employee data
// Retrieve logged-in employee data with fallback values
$employee_name = $_SESSION['employee_name'] ?? 'Unknown';
$employee_id = $_SESSION['employee_id'] ?? 0;
$employee_unique_id = $_SESSION['employee_unique_id'] ?? 'Not Set';
$employee_email = $_SESSION['employee_email'] ?? 'No Email';
$employee_role = $_SESSION['employee_role'] ?? 'No Role';
$employee_designation = $_SESSION['employee_designation'] ?? 'No Designation';
$employee_photo = $_SESSION['employee_photo'] ?? 'assets/img/logos/user.png';
$portal_role = strtolower($_SESSION['role'] ?? $_SESSION['employee_role'] ?? 'employee');

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
   <?= $org['name'] ?> My Attendance System
  </title>

  <!-- google map api key -->
  <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCH2j-8_qFXr-AwOdr9sgaEa0jQQHp0YZU&libraries=places"></script>

  <!-- Fonts and icons -->
  <link href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,800" rel="stylesheet" />
  <link href="https://demos.creative-tim.com/soft-ui-dashboard/assets/css/nucleo-icons.css" rel="stylesheet" />
  <link href="https://demos.creative-tim.com/soft-ui-dashboard/assets/css/nucleo-svg.css" rel="stylesheet" />
  <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link id="pagestyle" href="assets/css/soft-ui-dashboard.css?v=1.1.0" rel="stylesheet" />

  
  
  

</head>
<style>
:root {
  --sidebar-bg: linear-gradient(180deg, #f2f4f7 0%, #e3e7ec 100%);
  --sidebar-border: rgba(87, 96, 108, 0.18);
  --sidebar-link: #6d7682;
  --sidebar-link-hover: #3f4752;
  --sidebar-active-bg: linear-gradient(135deg, #ffffff 0%, #d9dfe7 100%);
  --sidebar-active-text: #232a32;
  --sidebar-icon-bg: rgba(255, 255, 255, 0.94);
  --sidebar-icon-active-bg: linear-gradient(135deg, #4b5563 0%, #2f3742 100%);
}

.collapse {
  transition: height 0.55s cubic-bezier(0.4, 0, 0.2, 1);
}

#sidenav-main {
  width: 18rem !important;
  max-height: calc(100vh - 1.5rem);
  background: var(--sidebar-bg) !important;
  border: 1px solid var(--sidebar-border) !important;
  border-radius: 28px !important;
  box-shadow: 0 24px 52px rgba(31, 41, 55, 0.14);
  overflow: hidden;
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
}

#sidenav-main .sidenav-header {
  position: sticky;
  top: 0;
  z-index: 3;
  padding: 1rem 1rem 0.55rem;
  margin-bottom: 0.2rem;
  background: linear-gradient(180deg, rgba(242, 244, 247, 0.98) 0%, rgba(242, 244, 247, 0.9) 100%);
  backdrop-filter: blur(14px);
}

#sidenav-main .navbar-brand {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  min-height: 64px;
  padding-left: 0.45rem;
  padding-right: 0.45rem;
  border-radius: 18px;
}

#sidenav-main .navbar-brand-img {
  max-height: 52px;
  width: auto;
  object-fit: contain;
}

#sidenav-main .horizontal.dark {
  background: rgba(87, 96, 108, 0.16);
  margin: 0 1rem;
}

#sidenav-collapse-main {
  height: calc(100vh - 110px) !important;
  padding: 0 0.45rem 1rem;
  overflow-y: auto;
  overflow-x: hidden;
  scrollbar-width: thin;
  scrollbar-color: rgba(100, 116, 139, 0.55) transparent;
}

#sidenav-collapse-main::-webkit-scrollbar {
  width: 8px;
}

#sidenav-collapse-main::-webkit-scrollbar-track {
  background: transparent;
}

#sidenav-collapse-main::-webkit-scrollbar-thumb {
  border-radius: 999px;
  background: linear-gradient(180deg, rgba(148, 163, 184, 0.7) 0%, rgba(100, 116, 139, 0.78) 100%);
}

.sidenav .navbar-nav {
  gap: 0.18rem;
}

.sidenav .nav-item {
  list-style: none;
}

.sidenav .sidebar-logout-btn {
  border: 0 !important;
  border-radius: 14px !important;
  background: linear-gradient(180deg, #2f66a6 0%, #174a86 100%) !important;
  color: #ffffff !important;
  font-weight: 800 !important;
  letter-spacing: 0.02em;
  box-shadow: 0 12px 24px rgba(15, 23, 42, 0.12) !important;
  transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease;
}

.sidenav .sidebar-logout-btn:hover {
  transform: translateY(-1px);
  filter: brightness(0.98);
  box-shadow: 0 16px 28px rgba(15, 23, 42, 0.14) !important;
}

/* REMOVE Soft UI default square collapse indicator */
.sidenav .nav-link[data-bs-toggle="collapse"]::after {
  display: none !important;
}

.sidenav .nav-link {
  min-height: 48px;
  display: flex;
  align-items: center;
  gap: 8px;
  position: relative;
  margin: 0.18rem 0.7rem;
  padding: 0.72rem 0.9rem;
  border-radius: 16px;
  line-height: 1.2;
  color: var(--sidebar-link) !important;
  border: 1px solid transparent;
  transition: background-color 0.25s ease, color 0.25s ease, box-shadow 0.25s ease, transform 0.25s ease, border-color 0.25s ease;
}

.sidenav .nav-link:hover {
  background: rgba(255, 255, 255, 0.82);
  color: var(--sidebar-link-hover) !important;
  border-color: rgba(203, 213, 225, 0.9);
  transform: translateX(2px);
}

.sidenav .nav-link.active {
  background: var(--sidebar-active-bg);
  color: var(--sidebar-active-text) !important;
  box-shadow: 0 10px 24px rgba(47, 55, 66, 0.14);
  border-color: rgba(203, 213, 225, 0.85);
}

.sidenav .nav-link.active::before {
  content: "";
  position: absolute;
  top: 50%;
  left: -0.15rem;
  width: 4px;
  height: calc(100% - 18px);
  border-radius: 999px;
  background: linear-gradient(180deg, #123b76 0%, #1f4c8f 100%);
  transform: translateY(-50%);
}

.sidenav .nav-link .nav-link-text,
.sidenav .nav-link:not(.active),
.sidenav .nav-link i:not(.sidebar-arrow) {
  color: inherit;
}

.sidenav .nav-link .icon {
  display: flex;
  align-items: center;
  justify-content: center;
}

.sidenav .nav-link .icon i {
  font-size: 1rem;
  line-height: 1;
}

.sidenav .icon.icon-shape {
  width: 38px !important;
  height: 38px !important;
  min-width: 38px;
  background: var(--sidebar-icon-bg) !important;
  box-shadow: 0 6px 18px rgba(31, 41, 55, 0.08) !important;
  border-radius: 12px !important;
}

.sidenav .icon.icon-shape i {
  color: #76808c !important;
}

.sidenav .nav-link.active .icon.icon-shape {
  background: var(--sidebar-icon-active-bg) !important;
  box-shadow: 0 10px 20px rgba(31, 41, 55, 0.2) !important;
}

.sidenav .nav-link.active .icon.icon-shape i {
  color: #fff !important;
}

.sidebar-arrow {
  margin-left: auto;
  display: flex;
  align-items: center;
  font-size: 0.85rem;
  opacity: 0.6;
  color: currentColor;
  transition: transform 0.35s ease, opacity 0.35s ease;
}

.nav-link[aria-expanded="true"] .sidebar-arrow {
  transform: rotate(180deg);
  opacity: 1;
}

.sidenav .collapse .nav-link {
  margin: 0.08rem 0.4rem 0.08rem 1.2rem;
  width: calc(100% - 1.6rem);
  box-sizing: border-box;
  padding-top: 0.55rem;
  padding-bottom: 0.55rem;
  padding-left: 0.78rem;
  padding-right: 0.78rem;
  font-size: 0.92rem;
  color: #818b97 !important;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.52);
  border: 1px solid rgba(226, 232, 240, 0.74);
  box-shadow: inset 0 1px 0 rgba(255,255,255,0.55);
}

.sidenav .collapse .nav-link:hover,
.sidenav .collapse .nav-link.active {
  color: var(--sidebar-active-text) !important;
  background: rgba(255, 255, 255, 0.92);
}

.sidenav .collapse .navbar-nav {
  gap: 0.12rem;
  padding-top: 0.22rem;
  padding-bottom: 0.3rem;
}

.sidenav .collapse .navbar-nav.ms-4 {
  margin-left: 0 !important;
  padding-left: 0.72rem;
}

.sidenav .collapse .nav-link i {
  color: #94a3b8;
}

.main-content {
  background: linear-gradient(180deg, #f7f9fc 0%, #f3f5f8 100%);
}

#navbarBlur {
  margin: 1rem 1.5rem 0;
}

#navbarBlur .container-fluid {
  min-height: 78px;
  padding: 0.95rem 1.15rem !important;
  border: 1px solid rgba(87, 96, 108, 0.12);
  border-radius: 24px;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.96) 0%, rgba(246, 248, 251, 0.96) 100%);
  box-shadow: 0 18px 38px rgba(15, 23, 42, 0.06);
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
}

.employee-header-back {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 46px;
  height: 46px;
  border-radius: 15px;
  background: linear-gradient(135deg, #123b76 0%, #1f4c8f 100%);
  box-shadow: 0 14px 28px rgba(18, 59, 118, 0.2);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.employee-header-back:hover {
  transform: translateY(-1px);
  box-shadow: 0 18px 30px rgba(18, 59, 118, 0.24);
}

.employee-header-back i {
  color: #ffffff;
  font-size: 1.1rem;
  line-height: 1;
}

.employee-header-toolbar {
  gap: 1rem;
}

.employee-header-search-wrap {
  width: 100%;
  max-width: 360px;
}

.employee-header-search {
  display: flex;
  align-items: center;
  min-height: 52px;
  padding: 0 0.25rem;
  border: 1px solid #d8e0ea;
  border-radius: 16px;
  background: #ffffff;
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8), 0 8px 20px rgba(148, 163, 184, 0.08);
}

.employee-header-search .input-group-text,
.employee-header-search .form-control {
  border: 0 !important;
  background: transparent !important;
  box-shadow: none !important;
}

.employee-header-search .input-group-text {
  color: #94a3b8;
  padding-left: 0.8rem;
}

.employee-header-search .form-control {
  color: #334155;
  padding-right: 0.8rem;
}

.employee-header-search .form-control::placeholder {
  color: #94a3b8;
}

.employee-header-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.75rem;
}

.employee-header-profile-link {
  display: inline-flex !important;
  align-items: center;
  gap: 0.75rem;
  padding: 0.35rem 0.45rem 0.35rem 0.9rem !important;
  border: 1px solid #dbe3ed;
  border-radius: 18px;
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  box-shadow: 0 10px 22px rgba(148, 163, 184, 0.1);
}

.employee-header-profile-meta {
  display: flex;
  flex-direction: column;
  min-width: 0;
  text-align: right;
}

.employee-header-profile-name {
  color: #111827;
  font-size: 0.86rem;
  font-weight: 700;
  line-height: 1.15;
}

.employee-header-profile-role {
  color: #94a3b8;
  font-size: 0.74rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.employee-header-avatar {
  width: 38px !important;
  height: 38px !important;
  margin-right: 0 !important;
  object-fit: cover;
  border: 2px solid #ffffff;
  box-shadow: 0 8px 16px rgba(15, 23, 42, 0.14);
}

.employee-header-dropdown {
  min-width: 230px;
  margin-top: 0.9rem !important;
  border: 1px solid rgba(87, 96, 108, 0.12);
  border-radius: 18px;
  box-shadow: 0 22px 48px rgba(15, 23, 42, 0.12);
}

.employee-header-dropdown .dropdown-item {
  border-radius: 14px;
}

@media (max-width: 991.98px) {
  #sidenav-main {
    width: min(18.5rem, calc(100vw - 1rem)) !important;
    max-height: calc(100vh - 0.8rem);
    margin: 0.4rem 0 0.4rem 0.45rem !important;
    border-radius: 24px !important;
    z-index: 1040;
  }

  #sidenav-main .sidenav-header {
    padding: 0.85rem 0.85rem 0.45rem;
  }

  #sidenav-collapse-main {
    height: calc(100vh - 96px) !important;
    padding: 0 0.35rem 0.85rem;
  }

  #navbarBlur {
    margin: 0.85rem 1rem 0;
  }

  .employee-header-toolbar {
    gap: 0.8rem;
  }

  .employee-header-search-wrap {
    max-width: none;
  }
}

@media (max-width: 767.98px) {
  #sidenav-main {
    width: min(17.8rem, calc(100vw - 0.85rem)) !important;
    margin-left: 0.3rem !important;
    border-radius: 22px !important;
  }

  #sidenav-main .navbar-brand-img {
    max-height: 46px;
  }

  .sidenav .nav-link {
    margin: 0.12rem 0.38rem;
    padding: 0.68rem 0.8rem;
    border-radius: 16px;
  }

  .sidenav .nav-link .nav-link-text,
  .sidenav .nav-link {
    font-size: 0.96rem;
  }

  .sidenav .icon.icon-shape {
    width: 34px !important;
    height: 34px !important;
    min-width: 34px;
    border-radius: 11px !important;
  }

  .sidenav .collapse .nav-link {
    margin: 0.08rem 0.28rem 0.08rem 0.72rem;
    width: calc(100% - 1rem);
    padding-top: 0.56rem;
    padding-bottom: 0.56rem;
    padding-left: 0.68rem;
    padding-right: 0.68rem;
    font-size: 0.8rem;
    border-radius: 12px;
  }

  .sidenav .collapse .nav-link .me-2 {
    margin-right: 0.45rem !important;
  }

  .sidenav .collapse .navbar-nav.ms-4 {
    padding-left: 0.28rem;
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
    justify-content: flex-end;
    gap: 0.45rem;
    padding: 0.55rem 0.8rem !important;
    min-height: 52px;
    border-radius: 16px;
    box-shadow: 0 12px 26px rgba(15, 23, 42, 0.05);
    width: 100%;
  }

  #navbarBlur nav[aria-label="breadcrumb"] {
    display: none;
  }

  #navbar {
    display: flex !important;
    flex: 1 1 auto;
    align-items: center;
    justify-content: stretch;
    margin: 0 !important;
    min-width: 0;
  }

  .employee-header-toolbar {
    gap: 0.35rem;
    width: 100%;
  }

  .employee-header-search-wrap {
    display: none !important;
  }

  .employee-header-actions {
    display: flex;
    flex-direction: row !important;
    align-items: center;
    width: 100%;
    min-width: 0;
    margin: 0;
    padding: 0;
    justify-content: flex-end;
    gap: 0.4rem;
  }

  .employee-header-actions > .nav-item:first-child {
    margin-right: auto;
  }

  .employee-header-actions > .nav-item {
    display: flex;
    align-items: center;
    margin-bottom: 0;
    padding-left: 0 !important;
  }

  .employee-header-profile-link {
    width: 42px;
    height: 42px;
    min-width: 42px;
    padding: 0 !important;
    gap: 0;
    border-radius: 14px;
    justify-content: center;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    box-shadow: 0 8px 16px rgba(148, 163, 184, 0.08);
  }

  .employee-header-profile-meta {
    display: none;
  }

  .employee-header-avatar {
    width: 30px !important;
    height: 30px !important;
    border-width: 1.5px;
  }

  .employee-header-mobile-back {
    display: flex !important;
  }

  .employee-header-mobile-menu {
    padding-left: 0 !important;
    margin-left: 0.18rem;
  }

  .employee-header-mobile-back,
  .employee-header-mobile-menu {
    flex: 0 0 auto;
    padding-left: 0 !important;
  }

  .employee-header-mobile-back .nav-link,
  .employee-header-mobile-menu .nav-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    padding: 0 !important;
    border-radius: 10px;
  }

  .employee-header-mobile-back .nav-link {
    background: linear-gradient(135deg, #123b76 0%, #1f4c8f 100%);
    box-shadow: 0 8px 14px rgba(18, 59, 118, 0.16);
  }

  .employee-header-mobile-back .nav-link i {
    color: #fff !important;
    font-size: 0.88rem !important;
  }

  .employee-header-mobile-menu .sidenav-toggler-inner {
    margin-top: 0;
  }

  .employee-header-mobile-menu .nav-link {
    color: #64748b !important;
    background: rgba(255, 255, 255, 0.75);
  }

  .employee-header-mobile-menu .sidenav-toggler-line {
    width: 15px;
  }
}

@media (max-width: 360px) {
  #navbarBlur {
    margin-left: 0.28rem !important;
    margin-right: 0.28rem !important;
    width: calc(100% - 0.56rem);
  }

  #navbarBlur .container-fluid {
    min-height: 50px;
    padding: 0.48rem 0.62rem !important;
    gap: 0.35rem;
  }

  .employee-header-actions {
    gap: 0.32rem;
  }

  .employee-header-profile-link {
    width: 38px;
    height: 38px;
    min-width: 38px;
    border-radius: 12px;
  }

  .employee-header-avatar {
    width: 27px !important;
    height: 27px !important;
  }

  .employee-header-mobile-menu {
    margin-left: 0.12rem;
  }

  .employee-header-mobile-back .nav-link,
  .employee-header-mobile-menu .nav-link {
    width: 30px;
    height: 30px;
  }
}
</style>

<body class="g-sidenav-show bg-gray-100">
  <aside class="sidenav navbar navbar-vertical navbar-expand-xs border-0 border-radius-xl my-3 fixed-start ms-3" id="sidenav-main">
    <div class="sidenav-header">
      <i class="fas fa-times p-3 cursor-pointer text-secondary opacity-5 position-absolute end-0 top-0 d-none d-xl-none" aria-hidden="true" id="iconSidenav"></i>
      <a class="navbar-brand m-0" href="dashboard">

            <?php if (!empty($org['logo']) && file_exists("../uploads/org/" . $org['logo'])): ?>
    <img src="../uploads/org/<?= $org['logo'] ?>" class="navbar-brand-img" alt="main_logo">
<?php else: ?>
    <img src="assets/img/att logo.png" class="navbar-brand-img" alt="main_logo">
<?php endif; ?>
        
      </a>
    </div>
    <hr class="horizontal dark mt-0">
    <div class="collapse navbar-collapse w-auto" id="sidenav-collapse-main" style="height: 100%;">
     <ul class="navbar-nav">

<!-- DASHBOARD -->
<li class="nav-item">
  <a class="nav-link <?= $current_page=='dashboard.php'?'active':'' ?>" href="dashboard">
    <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2">
      <i class="bi bi-house-gear-fill text-dark"></i>
    </div>
    Dashboard
  </a>
</li>

<?php
$performance_portal_pages = ['performance.php'];
$performance_portal_views = [
  'my-dashboard' => ['label' => 'My Dashboard', 'icon' => 'bi bi-speedometer2'],
  'my-goals' => ['label' => 'My Goals', 'icon' => 'bi bi-bullseye'],
  'my-checkins' => ['label' => 'My Check-Ins', 'icon' => 'bi bi-journal-check'],
  'my-self-reviews' => ['label' => 'My Self Reviews', 'icon' => 'bi bi-person-check'],
  'my-feedback' => ['label' => 'My Feedback', 'icon' => 'bi bi-chat-left-dots'],
  'my-recognition' => ['label' => 'My Recognition', 'icon' => 'bi bi-award'],
  'my-history' => ['label' => 'My Performance', 'icon' => 'bi bi-graph-up']
];

if ($portal_role === 'manager' || $portal_role === 'supervisor') {
  $performance_portal_views['team-dashboard'] = ['label' => 'Team Performance Dashboard', 'icon' => 'bi bi-speedometer'];
  $performance_portal_views['team-goals'] = ['label' => 'Team Goals', 'icon' => 'bi bi-bullseye'];
  $performance_portal_views['team-reviews'] = ['label' => 'Team Reviews', 'icon' => 'bi bi-people'];
  $performance_portal_views['pending-approvals'] = ['label' => 'Pending Approvals', 'icon' => 'bi bi-hourglass-split'];
  $performance_portal_views['employee-feedback'] = ['label' => 'Employee Feedback', 'icon' => 'bi bi-chat-left-dots'];
  $performance_portal_views['team-analytics'] = ['label' => 'Team Analytics', 'icon' => 'bi bi-bar-chart'];
  $performance_portal_views['goal-assignment'] = ['label' => 'Goal Assignment', 'icon' => 'bi bi-kanban'];
  $performance_portal_views['performance-monitoring'] = ['label' => 'Performance Monitoring', 'icon' => 'bi bi-activity'];
}
?>

<li class="nav-item">
  <a class="nav-link <?= in_array($current_page, $performance_portal_pages, true)?'active':'' ?>"
   href="#performancePortalMenu"
   data-bs-toggle="collapse"
   role="button"
   aria-expanded="<?= in_array($current_page, $performance_portal_pages, true)?'true':'false' ?>">

    <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2">
      <i class="bi bi-graph-up-arrow text-dark"></i>
    </div>
    Performance
    <i class="bi bi-chevron-down sidebar-arrow"></i>
  </a>

  <div class="collapse <?= in_array($current_page, $performance_portal_pages, true)?'show':'' ?>" id="performancePortalMenu">
    <ul class="navbar-nav ms-4">
      <?php foreach ($performance_portal_views as $performance_key => $performance_item): ?>
        <li class="nav-item">
          <a class="nav-link <?= $current_page=='performance.php' && $current_view==$performance_key ? 'active' : '' ?>" href="performance?view=<?= $performance_key ?>">
            <i class="<?= $performance_item['icon'] ?> me-2"></i> <?= $performance_item['label'] ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</li>


<?php
$attendance_pages = [
  'add_attendance.php',
  'add_break_attendance.php',
  'manage_attendance.php',
  'attendance_record.php',
  'manage_break_attendance.php',
  'apply_attendance_change.php',
  'manage_attendance_change.php'
];
?>

<!-- ATTENDANCE (EXPANDABLE) -->
<li class="nav-item">
  <a class="nav-link <?= in_array($current_page,$attendance_pages)?'active':'' ?>"
   href="#attendanceMenu"
   data-bs-toggle="collapse"
   role="button"
   aria-expanded="<?= in_array($current_page,$attendance_pages)?'true':'false' ?>">



    <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2">
      <i class="bi bi-fingerprint text-dark"></i>
    </div>
    Attendance
    <i class="bi bi-chevron-down sidebar-arrow"></i>
    </a>


  <div class="collapse <?= in_array($current_page,$attendance_pages)?'show':'' ?>" id="attendanceMenu">
    <ul class="navbar-nav ms-4">

      <li class="nav-item">
        <a class="nav-link <?= $current_page=='add_attendance.php'?'active':'' ?>" href="add_attendance">
          <i class="bi bi-box-arrow-in-right me-2"></i> Punch
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link <?= $current_page=='add_break_attendance.php'?'active':'' ?>" href="add_break_attendance">
          <i class="bi bi-cup-hot me-2"></i> Break
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link <?= $current_page=='manage_attendance.php'?'active':'' ?>" href="manage_attendance">
          <i class="bi bi-calendar-check me-2"></i> My Attendance
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link <?= $current_page=='attendance_record.php'?'active':'' ?>" href="attendance_record">
          <i class="bi bi-people me-2"></i> Team Attendance
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link <?= $current_page=='manage_break_attendance.php'?'active':'' ?>" href="manage_break_attendance">
          <i class="bi bi-clock-history me-2"></i> Break Records
        </a>
      </li>
      
      <li class="nav-item">
        <a class="nav-link <?= $current_page=='apply_attendance_change.php'?'active':'' ?>" href="apply_attendance_change">
          <i class="bi bi-pencil-square me-2"></i> Apply Change
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link <?= $current_page=='manage_attendance_change.php'?'active':'' ?>" href="manage_attendance_change">
          <i class="bi bi-card-checklist me-2"></i> Change Requests
        </a>
      </li>

    </ul>
  </div>
</li>



<?php
$visit_pages = ['visit_details.php','journey.php','near_by_me.php'];
?>

<li class="nav-item">
  <a class="nav-link <?= in_array($current_page,$visit_pages)?'active':'' ?>"
   href="#visitMenu"
   data-bs-toggle="collapse"
   role="button"
   aria-expanded="<?= in_array($current_page,$visit_pages)?'true':'false' ?>">


    <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2">
      <i class="bi bi-signpost-split text-dark"></i>
    </div>
    Visits
    <i class="bi bi-chevron-down sidebar-arrow"></i>
  </a>

  <div class="collapse <?= in_array($current_page,$visit_pages)?'show':'' ?>" id="visitMenu">
    <ul class="navbar-nav ms-4">

      <li class="nav-item">
        <a class="nav-link <?= $current_page=='visit_details.php'?'active':'' ?>" href="visit_details">
          <i class="bi bi-geo-alt me-2"></i> Today Visits
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link <?= $current_page=='journey.php'?'active':'' ?>" href="journey">
          <i class="bi bi-map me-2"></i> My Visits
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link <?= $current_page=='near_by_me.php'?'active':'' ?>" href="near_by_me">
          <i class="bi bi-compass me-2"></i> Near Me
        </a>
      </li>

    </ul>
  </div>
</li>


<!-- EXPENSES -->
<li class="nav-item">
  <a class="nav-link <?= $current_page=='manage_expenses.php'?'active':'' ?>" href="manage_expenses">
    <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2">
      <i class="bi bi-person-workspace text-dark"></i>
    </div>
    Expenses
  </a>
</li>



<?php
$work_pages = ['manage_task.php','assigned_task.php','worksheets.php'];
?>

<li class="nav-item">
  <a class="nav-link <?= in_array($current_page,$work_pages)?'active':'' ?>"
   href="#workMenu"
   data-bs-toggle="collapse"
   role="button"
   aria-expanded="<?= in_array($current_page,$work_pages)?'true':'false' ?>">


    <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2">
      <i class="bi bi-person-workspace text-dark"></i>
    </div>
    Work
    <i class="bi bi-chevron-down sidebar-arrow"></i>
  </a>

  <div class="collapse <?= in_array($current_page,$work_pages)?'show':'' ?>" id="workMenu">
    <ul class="navbar-nav ms-4">

      <li class="nav-item">
        <a class="nav-link <?= $current_page=='manage_task.php'?'active':'' ?>" href="manage_task">
          <i class="bi bi-journal-text me-2"></i> Daily Report
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link <?= $current_page=='assigned_task.php'?'active':'' ?>" href="assigned_task">
          <i class="bi bi-list-check me-2"></i> Assigned Task
        </a>
      </li>


      <li class="nav-item">
        <a class="nav-link <?= $current_page=='worksheets.php'?'active':'' ?>" href="worksheets">
          <i class="bi bi-table me-2"></i> Worksheet
        </a>
      </li>

    </ul>
  </div>
</li>

<?php
$current_page = basename($_SERVER['PHP_SELF']);
$salary_pages = ['manage_salary.php','apply_advance_salary.php','apply_advance_salary_yearly.php'];
?>

<li class="nav-item">
  <a class="nav-link <?= in_array($current_page,$salary_pages)?'active':'' ?>"
     href="#salaryMenu"
     data-bs-toggle="collapse"
     role="button"
     aria-expanded="<?= in_array($current_page,$salary_pages)?'true':'false' ?>">

    <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2">
      <i class="bi bi-cash-stack text-dark"></i>
    </div>
    Salary
    <i class="bi bi-chevron-down sidebar-arrow"></i>
  </a>

  <div class="collapse <?= in_array($current_page,$salary_pages)?'show':'' ?>" id="salaryMenu">
    <ul class="navbar-nav ms-4">

      <li class="nav-item">
        <a class="nav-link <?= $current_page=='manage_salary.php'?'active':'' ?>" href="manage_salary">
          <i class="bi bi-cash-stack me-2"></i> Manage Salary
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link <?= $current_page=='apply_advance_salary.php'?'active':'' ?>" href="apply_advance_salary">
          <i class="bi bi-wallet2 me-2"></i> Monthly Advance
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link <?= $current_page=='apply_advance_salary_yearly.php'?'active':'' ?>" href="apply_advance_salary_yearly">
          <i class="bi bi-calendar-range me-2"></i> Yearly Advance
        </a>
      </li>

    </ul>
  </div>
</li>

<li class="nav-item">
  <a class="nav-link <?= $current_page=='hierarchy_view.php'?'active':'' ?>" href="hierarchy_view">
    <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2">
      <i class="bi bi-people-fill text-dark"></i>
    </div>
    My Team
  </a>
</li>

<li class="nav-item">
  <a class="nav-link <?= $current_page=='manage_leave.php'?'active':'' ?>" href="manage_leave">
    <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2">
      <i class="bi bi-calendar-check text-dark"></i>
    </div>
    Apply Leave
  </a>
</li>



<li class="nav-item">
  <a class="nav-link <?= $current_page=='manage_tickets.php'?'active':'' ?>" href="manage_tickets">
    <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2">
      <i class="bi bi-ticket-perforated text-dark"></i>
    </div>
    Tickets
  </a>
</li>


<?php
$lead_pages = [
    'my_leads.php',
    'followup_reminders.php',
    'lead_aging.php',
    'add_lead.php' 
];
?>

<li class="nav-item">
  <a class="nav-link <?= in_array($current_page, $lead_pages) ? 'active' : '' ?>"
     href="#leadMenu"
     data-bs-toggle="collapse"
     role="button"
     aria-expanded="<?= in_array($current_page, $lead_pages) ? 'true' : 'false' ?>">

    <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2">
      <i class="bi bi-person-lines-fill text-dark"></i>
    </div>
    Leads
    <i class="bi bi-chevron-down sidebar-arrow"></i>
  </a>

  <div class="collapse <?= in_array($current_page, $lead_pages) ? 'show' : '' ?>" id="leadMenu">
    <ul class="navbar-nav ms-4">

      <li class="nav-item">
        <a class="nav-link <?= $current_page=='my_leads.php'?'active':'' ?>"
           href="my_leads">
          <i class="bi bi-list-check me-2"></i> My Leads
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link <?= $current_page=='followup_reminders.php'?'active':'' ?>"
           href="followup_reminders">
          <i class="bi bi-alarm me-2"></i> Follow-up
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link <?= $current_page=='lead_aging.php'?'active':'' ?>"
           href="lead_aging">
          <i class="bi bi-bar-chart-line me-2"></i> Lead Aging
        </a>
      </li>

    </ul>
  </div>
</li>

<li class="nav-item">
  <a class="nav-link <?= $current_page=='my_assets.php'?'active':'' ?>" href="my_assets">
    <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2">
      <i class="bi bi-box text-dark"></i>
    </div>
    Assigned Assets
  </a>
</li>

<li class="nav-item">
  <a class="nav-link <?= $current_page=='client_manual.php'?'active':'' ?>" href="client_manual">
    <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2">
      <i class="bi bi-journal-richtext text-dark"></i>
    </div>
     Guide Manual
  </a>
</li>

<!-- LOGOUT -->
<li class="nav-item">
  <a class="btn btn-primary mt-3 w-100 sidebar-logout-btn" href="logout">LOGOUT</a>
</li>

</ul>



    </div>
    
  </aside>
  <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    <!-- Navbar -->
    <nav class="navbar navbar-main navbar-expand-lg px-0 mx-4 shadow-none border-radius-xl" id="navbarBlur" navbar-scroll="true">
      <div class="container-fluid py-1 px-3">
        <nav aria-label="breadcrumb">
        <a href="javascript:history.back()" class="nav-link text-body p-0 employee-header-back" aria-label="Go back" title="Go back">
        <i class="bi bi-arrow-left"></i></a>
        </nav>
        <div class="collapse navbar-collapse mt-sm-0 mt-2 me-md-0 me-sm-4 employee-header-toolbar" id="navbar">
          <div class="ms-md-auto pe-md-0 d-flex align-items-center input-group-main employee-header-search-wrap">
            <div class="input-group employee-header-search">
              <span class="input-group-text text-body"><i class="fas fa-search" aria-hidden="true"></i></span>
              <input type="text" class="form-control" placeholder="Type here..."   id="searchInput"
              onkeyup="searchTable()">
            </div>
          </div>
          <ul class="navbar-nav justify-content-end employee-header-actions">
            <li class="nav-item dropdown pe-2 d-flex align-items-center" >
              <a href="javascript:;" class="nav-link text-body p-0 employee-header-profile-link" id="employeeProfileMenu" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="employee-header-profile-meta">
                  <span class="employee-header-profile-name"><?= htmlspecialchars($employee_name) ?></span>
                  <span class="employee-header-profile-role"><?= htmlspecialchars($employee_role) ?></span>
                </div>
                <img src="<?= htmlspecialchars($employee_photo) ?>" class="avatar avatar-sm employee-header-avatar" alt="Employee">
              </a>
              <ul class="dropdown-menu dropdown-menu-end px-2 py-3 me-sm-n4 employee-header-dropdown" aria-labelledby="employeeProfileMenu">
                <li class="mb-2">
                  <a class="dropdown-item border-radius-md" href="edit_profile">
                    <div class="d-flex py-1">
                      <div class="d-flex flex-column justify-content-center">
                        <h6 class="text-sm font-weight-normal mb-1">
                          <span class="font-weight-bold"> <?= htmlspecialchars($employee_name) ?></span>
                        </h6>
                        <p class="text-xs text-secondary mb-0 ">
                          <i class="fa fa-clock me-1"></i>
                         Employee ID - <?= htmlspecialchars($employee_unique_id) ?>
                        </p>
                      </div>
                    </div>
                  </a>
                </li>
                <li>
                  <a class="dropdown-item border-radius-md" href="logout">
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
            <li class="nav-item d-xl-none ps-3 d-flex align-items-center employee-header-mobile-back">
            <a href="javascript:history.back()" class="nav-link text-body p-0" aria-label="Go back" title="Go back">
            <i class="bi bi-arrow-left-circle-fill" style="font-size: 1.15rem; color: #1f4c8f;"></i></a>
            </li>
            <li class="nav-item d-xl-none ps-3 d-flex align-items-center employee-header-mobile-menu">
              <a href="javascript:;" class="nav-link text-body p-0" id="iconNavbarSidenav">
                <div class="sidenav-toggler-inner">
                  <i class="sidenav-toggler-line"></i>
                  <i class="sidenav-toggler-line"></i>
                  <i class="sidenav-toggler-line"></i>
                </div>
              </a>
            </li>
          </ul>
        </div>
      </div>
    </nav>
 

