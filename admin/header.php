<?php
ob_start();
// 24-hour session lifetime
ini_set('session.gc_maxlifetime', 172800);
session_start();
require 'db_connection.php';
date_default_timezone_set('Asia/Kolkata');
// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index");
    exit;
}
// Auto-logout after 24 hours
if (isset($_SESSION['ADMIN_LOGIN_TIME']) && (time() - $_SESSION['ADMIN_LOGIN_TIME']) > 172800) {
    unset($_SESSION['admin_logged_in'], $_SESSION['admin_id'], $_SESSION['admin_employee_id'],
          $_SESSION['admin_name'], $_SESSION['admin_email'], $_SESSION['admin_role'], $_SESSION['ADMIN_LOGIN_TIME']);
    header("Location: index");
    exit;
}

// Dynamic page detection
$current_page = basename($_SERVER['PHP_SELF']); // Gets the current page name
$current_view = $_GET['view'] ?? 'dashboard';
$session_role = $_SESSION['role'] ?? ($_SESSION['admin_roll'] ?? 'Admin');

// Admin details (replace with actual session variable names storing admin details)
$admin_id = $_SESSION['admin_id'] ?? '123';
$admin_name = $_SESSION['admin_name'] ?? 'Admin';
$employee_id = $_SESSION['admin_employee_id'] ?? '00000';
$admin_roll = $_SESSION['admin_roll'] ?? 'Admin';
$admin_email = $_SESSION['admin_email'] ?? 'Not Set';


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
   <?= $org['name'] ?>  My Attendance System
  </title>
            

  <!-- Fonts and icons -->
  <link href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,800" rel="stylesheet" />
  <link href="https://demos.creative-tim.com/soft-ui-dashboard/assets/css/nucleo-icons.css" rel="stylesheet" />
  <link href="https://demos.creative-tim.com/soft-ui-dashboard/assets/css/nucleo-svg.css" rel="stylesheet" />
  <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link id="pagestyle" href="assets/css/soft-ui-dashboard.css?v=1.1.0" rel="stylesheet" />

  <!-- google map api key -->
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCH2j-8_qFXr-AwOdr9sgaEa0jQQHp0YZU&libraries=places"></script>


</head>


<style>
/* Smooth collapse animation */
.collapse {
  transition: height 0.45s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Remove Soft UI default square indicator */
.sidenav .nav-link[data-bs-toggle="collapse"]::after {
  display: none !important;
}

/* Ensure sidebar link layout */
.sidenav .nav-link {
  display: flex;
  align-items: center;
}

/* Icon box fix (perfect centering) */
.icon.icon-shape {
  display: flex !important;
  align-items: center;
  justify-content: center;
}

/* Chevron arrow styling */
.sidebar-arrow {
  margin-left: auto;
  font-size: 0.85rem;
  opacity: 0.6;
  transition: transform 0.35s ease, opacity 0.35s ease;
}

/* Rotate arrow when open */
.nav-link[aria-expanded="true"] .sidebar-arrow {
  transform: rotate(180deg);
  opacity: 1;
}

/* === PERFECT ICON CENTERING FIX (Soft UI override) === */

/* Icon container */
.sidenav .icon.icon-shape {
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  padding: 0 !important;
}

/* Actual icon inside */
.sidenav .icon.icon-shape i {
  display: flex !important;
  align-items: center;
  justify-content: center;
  line-height: 1 !important;   /* 🔥 MOST IMPORTANT */
  font-size: 1rem;
}

/* Ensure nav-link height consistency */
.sidenav .nav-link {
  min-height: 48px;
  display: flex;
  align-items: center;
}

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

.sidenav .sidebar-subscription-btn,
.sidenav .sidebar-logout-btn {
  border: 0 !important;
  border-radius: 14px !important;
  font-weight: 800 !important;
  letter-spacing: 0.02em;
  box-shadow: 0 12px 24px rgba(15, 23, 42, 0.12) !important;
  transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease;
}

.sidenav .sidebar-subscription-btn {
  background: linear-gradient(180deg, #2f66a6 0%, #174a86 100%) !important;
  color: #ffffff !important;
}

.sidenav .sidebar-logout-btn {
  background: linear-gradient(180deg, #ff7d7d 0%, #f24848 100%) !important;
  color: #ffffff !important;
}

.sidenav .sidebar-subscription-btn:hover,
.sidenav .sidebar-logout-btn:hover {
  transform: translateY(-1px);
  filter: brightness(0.98);
  box-shadow: 0 16px 28px rgba(15, 23, 42, 0.14) !important;
}

.sidenav .nav-link {
  position: relative;
  margin: 0.14rem 0.55rem;
  padding: 0.76rem 0.92rem;
  border-radius: 18px;
  color: var(--sidebar-link) !important;
  transition: background-color 0.25s ease, color 0.25s ease, box-shadow 0.25s ease, transform 0.25s ease, border-color 0.25s ease;
  border: 1px solid transparent;
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

.sidenav .nav-link .nav-link-text {
  font-size: 1rem;
  font-weight: 700;
  letter-spacing: -0.01em;
}

.sidebar-arrow {
  color: currentColor;
  font-size: 0.78rem;
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

.sidenav .collapse .nav-link {
  margin: 0.08rem 0.4rem 0.08rem 1.2rem;
  width: calc(100% - 1.6rem);
  box-sizing: border-box;
  padding-top: 0.62rem;
  padding-bottom: 0.62rem;
  padding-left: 0.78rem;
  padding-right: 0.78rem;
  font-size: 0.9rem;
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

.admin-header-back {
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

.admin-header-back:hover {
  transform: translateY(-1px);
  box-shadow: 0 18px 30px rgba(18, 59, 118, 0.24);
}

.admin-header-back i {
  color: #ffffff;
  font-size: 1.1rem;
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
  min-height: 52px;
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
  padding-left: 0.8rem;
}

.admin-header-search .form-control {
  color: #334155;
  padding-right: 0.8rem;
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

.admin-header-icon-btn {
  display: inline-flex !important;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  border: 1px solid #dbe3ed;
  border-radius: 14px;
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  color: #64748b !important;
  box-shadow: 0 8px 18px rgba(148, 163, 184, 0.08);
}

.admin-header-icon-btn:hover {
  color: #1f2937 !important;
}

.admin-header-profile-link {
  display: inline-flex !important;
  align-items: center;
  gap: 0.75rem;
  padding: 0.35rem 0.45rem 0.35rem 0.9rem !important;
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
  width: 38px !important;
  height: 38px !important;
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

  .admin-header-toolbar {
    gap: 0.8rem;
  }

  .admin-header-search-wrap {
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

  .sidenav .nav-link .nav-link-text {
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

  #navbarBlur .container-fluid {
    padding: 0.85rem 0.9rem !important;
  }

  .admin-header-profile-meta,
  .admin-header-icon-btn {
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
    padding: 0.55rem 0.8rem !important;
    min-height: 52px;
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
    justify-content: space-between;
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

  .admin-header-actions > .nav-item:first-child {
    margin-right: auto;
  }

  .admin-header-actions > .mb-2,
  .admin-header-actions > li:empty {
    display: none !important;
  }

  .admin-header-profile-link {
    padding: 0.2rem !important;
    min-width: 44px;
    min-height: 44px;
    border-radius: 14px;
    border: 1px solid #dbe3ed;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    box-shadow: 0 8px 18px rgba(148, 163, 184, 0.08);
  }

  .admin-header-avatar {
    width: 34px !important;
    height: 34px !important;
    object-fit: cover;
  }

  .admin-header-mobile-icon {
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 42px;
    padding: 0 !important;
    border: 1px solid #dbe3ed;
    border-radius: 14px;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    box-shadow: 0 8px 18px rgba(148, 163, 184, 0.08);
  }

  .admin-header-mobile-back {
    background: linear-gradient(135deg, #123b76 0%, #1f4c8f 100%) !important;
    border-color: transparent !important;
    box-shadow: 0 12px 22px rgba(18, 59, 118, 0.18);
  }

  .admin-header-mobile-back i {
    color: #ffffff !important;
    font-size: 0.95rem !important;
  }

  .admin-header-mobile-menu .sidenav-toggler-inner {
    margin-top: 0 !important;
  }

  .admin-header-mobile-menu .sidenav-toggler-line {
    background: #64748b !important;
  }

  .admin-header-mobile-menu .sidenav-toggler-inner {
    width: 18px;
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
    <img src="https://arklifediagnostics.in/wp-content/uploads/2023/10/ARK-LOGO-2048x1058.png" class="navbar-brand-img" alt="main_logo">
<?php endif; ?>

     
      </a>
    </div>
    <hr class="horizontal dark mt-0">
    <div class="collapse navbar-collapse w-auto" id="sidenav-collapse-main" style="height: 100%;">
      <ul class="navbar-nav">
        
        
        <li class="nav-item">
          <a class="nav-link <?= $current_page=='dashboard.php'?'active':'' ?>" href="dashboard">
            <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="bi bi-house-gear-fill text-dark"></i>
            </div>
            <span class="nav-link-text ms-1">Dashboard</span>
          </a>
        </li>

        <?php
      $attendance_pages = [
        'manage_attendance.php',
        'attendance_record.php',
        'break_attendance_record.php',
        'manage_attendance_change.php'
      ];
      ?>

      <li class="nav-item">
        <a class="nav-link <?= in_array($current_page,$attendance_pages)?'active':'' ?>"
           href="#attendanceMenu"
           data-bs-toggle="collapse"
           aria-expanded="<?= in_array($current_page,$attendance_pages)?'true':'false' ?>">

          <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
            <i class="bi bi-fingerprint text-dark"></i>
          </div>

          <span class="nav-link-text ms-1">Attendance</span>
          <i class="bi bi-chevron-down sidebar-arrow"></i>
        </a>

        <div class="collapse <?= in_array($current_page,$attendance_pages)?'show':'' ?>" id="attendanceMenu">
          <ul class="navbar-nav ms-4">
            <li class="nav-item">
              <a class="nav-link <?= $current_page=='manage_attendance.php'?'active':'' ?>" href="manage_attendance">
                <i class="bi bi-clock-history me-2"></i>
                Today Attendance
              </a>
            </li>

            <li class="nav-item">
              <a class="nav-link <?= $current_page=='attendance_record.php'?'active':'' ?>" href="attendance_record">
                <i class="bi bi-list-check me-2"></i>
                Attendance Record
              </a>
            </li>

            <li class="nav-item">
              <a class="nav-link <?= $current_page=='break_attendance_record.php'?'active':'' ?>" href="break_attendance_record">
                <i class="bi bi-cup-hot me-2"></i>
                Break Record
              </a>
            </li>
            
            <li class="nav-item">
              <a class="nav-link <?= $current_page=='manage_attendance_change.php'?'active':'' ?>" href="manage_attendance_change">
                <i class="bi bi-pencil-square me-2"></i>
                Change Requests
              </a>
            </li>
            
          </ul>
        </div>
      </li>


      <li class="nav-item">
          <a class="nav-link <?= $current_page=='admin_home.php'?'active':'' ?>" href="admin_home">
            <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="bi bi-signpost-split text-dark"></i>
            </div>
            <span class="nav-link-text ms-1">Visits</span>
          </a>
        </li>

      <?php
      $employee_pages = [
        'manage_employee.php',
        'add_attendance_admin.php'
      ];
      ?>
      
      <li class="nav-item">
        <a class="nav-link <?= in_array($current_page,['onboarding.php','onboarding_review.php'])?'active':'' ?>"
           href="onboarding">

          <div class="icon icon-shape icon-sm shadow border-radius-md bg-white me-2 d-flex align-items-center justify-content-center">
            <i class="bi bi-person-workspace text-dark"></i>
          </div>

          <span class="nav-link-text ms-1">Onboarding</span>
        </a>
      </li>
      
      <li class="nav-item">
        <a class="nav-link <?= $current_page=='manage_employee.php'?'active':'' ?>"
           href="manage_employee">

          <div class="icon icon-shape icon-sm shadow border-radius-md bg-white me-2 d-flex align-items-center justify-content-center">
            <i class="bi bi-people-fill text-dark"></i>
          </div>

          <span class="nav-link-text ms-1">Employee</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link <?= $current_page=='admin_worksheet.php'?'active':'' ?>"
           href="admin_worksheet">

          <div class="icon icon-shape icon-sm shadow border-radius-md bg-white me-2 d-flex align-items-center justify-content-center">
            <i class="bi bi-table text-dark"></i>
          </div>

          <span class="nav-link-text ms-1">Worksheet</span>
        </a>
      </li>


      <?php
      $payroll_pages = [
        'manage_salary.php',
        'salary_dashboard.php',
        'manage_advance_salary.php'
      ];
      ?>

      <li class="nav-item">
        <a class="nav-link <?= in_array($current_page,$payroll_pages)?'active':'' ?>"
           href="#payrollMenu"
           data-bs-toggle="collapse"
           aria-expanded="<?= in_array($current_page,$payroll_pages)?'true':'false' ?>">

          <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
            <i class="bi bi-currency-rupee text-dark"></i>
          </div>

          <span class="nav-link-text ms-1">Payroll</span>
          <i class="bi bi-chevron-down sidebar-arrow"></i>
        </a>

        <div class="collapse <?= in_array($current_page,$payroll_pages)?'show':'' ?>" id="payrollMenu">
          <ul class="navbar-nav ms-4">
            <li class="nav-item">
              <a class="nav-link <?= $current_page=='manage_salary.php'?'active':'' ?>" href="manage_salary">
                <i class="bi bi-cash-stack me-2"></i>
                Salary
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?= $current_page=='salary_dashboard.php'?'active':'' ?>" href="salary_dashboard">
                <i class="bi bi-bar-chart me-2"></i>
                Summary
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?= $current_page=='manage_advance_salary.php'?'active':'' ?>" href="manage_advance_salary">
                <i class="bi bi-wallet2 me-2"></i>
                Advance Requests
              </a>
            </li>
          </ul>
        </div>
      </li>

      <?php
      $performance_pages = ['performance.php'];
      $performance_views = [
        'dashboard' => ['label' => 'Dashboard', 'icon' => 'bi bi-speedometer2'],
        'review-cycles' => ['label' => 'Review Cycles', 'icon' => 'bi bi-calendar3'],
        'goals-kpis' => ['label' => 'Goals & KPIs', 'icon' => 'bi bi-bullseye'],
        'feedback' => ['label' => 'Feedback', 'icon' => 'bi bi-chat-left-dots'],
        'check-ins' => ['label' => 'Check-Ins', 'icon' => 'bi bi-journal-check'],
        'self-reviews' => ['label' => 'Self Reviews', 'icon' => 'bi bi-person-check'],
        'manager-reviews' => ['label' => 'Manager Reviews', 'icon' => 'bi bi-people'],
        'reports' => ['label' => 'Reports', 'icon' => 'bi bi-bar-chart'],
        'recognition' => ['label' => 'Recognition', 'icon' => 'bi bi-award'],
        'pip' => ['label' => 'PIP', 'icon' => 'bi bi-activity'],
        'settings' => ['label' => 'Settings', 'icon' => 'bi bi-gear']
      ];
      ?>

      <li class="nav-item">
        <a class="nav-link <?= in_array($current_page, $performance_pages, true)?'active':'' ?>"
           href="#performanceMenu"
           data-bs-toggle="collapse"
           aria-expanded="<?= in_array($current_page, $performance_pages, true)?'true':'false' ?>">

          <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
            <i class="bi bi-graph-up-arrow text-dark"></i>
          </div>

          <span class="nav-link-text ms-1">Performance</span>
          <i class="bi bi-chevron-down sidebar-arrow"></i>
        </a>

        <div class="collapse <?= in_array($current_page, $performance_pages, true)?'show':'' ?>" id="performanceMenu">
          <ul class="navbar-nav ms-4">
            <?php foreach ($performance_views as $performance_key => $performance_item): ?>
              <li class="nav-item">
                <a class="nav-link <?= $current_page=='performance.php' && $current_view==$performance_key ? 'active' : '' ?>" href="performance?view=<?= $performance_key ?>">
                  <i class="<?= $performance_item['icon'] ?> me-2"></i>
                  <?= $performance_item['label'] ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </li>


      <li class="nav-item">
        <a class="nav-link <?= $current_page=='manage_expenses.php'?'active':'' ?>" href="manage_expenses">
          <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
            <i class="bi bi-person-workspace text-dark"></i>
          </div>
          <span class="nav-link-text ms-1">Expenses</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link <?= $current_page=='manage_site.php'?'active':'' ?>" href="manage_site">
          <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
            <i class="bi bi-globe text-dark"></i>
          </div>
          <span class="nav-link-text ms-1">Site</span>
        </a>
      </li>



       <?php
      $task_pages = [
        'manage_task.php',
        'manage_dailytask.php'
      ];
      ?>

      <li class="nav-item">
        <a class="nav-link <?= in_array($current_page,$task_pages)?'active':'' ?>"
           href="#taskMenu"
           data-bs-toggle="collapse"
           aria-expanded="<?= in_array($current_page,$task_pages)?'true':'false' ?>">

          <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
            <i class="bi bi-list-check text-dark"></i>
          </div>

          <span class="nav-link-text ms-1">Tasks</span>
          <i class="bi bi-chevron-down sidebar-arrow"></i>
        </a>

        <div class="collapse <?= in_array($current_page,$task_pages)?'show':'' ?>" id="taskMenu">
          <ul class="navbar-nav ms-4">
            <li class="nav-item">
              <a class="nav-link <?= $current_page=='manage_task.php'?'active':'' ?>" href="manage_task">
                <i class="bi bi-journal-text me-2"></i>
                Task
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?= $current_page=='manage_dailytask.php'?'active':'' ?>" href="manage_dailytask">
                <i class="bi bi-calendar2-check me-2"></i>
                Daily Task
              </a>
            </li>
          </ul>
        </div>
      </li>

      <li class="nav-item">
        <a class="nav-link <?= $current_page=='track_employees.php'?'active':'' ?>" href="track_employees">
          <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
            <i class="bi bi-geo-alt-fill text-dark"></i>
          </div>
          <span class="nav-link-text ms-1">Track Employees</span>
        </a>
      </li>


      <?php
      $leave_pages = [
        'manage_leave.php',
        'manage_leave_types.php'
      ];
      ?>

      <li class="nav-item">
        <a class="nav-link <?= in_array($current_page,$leave_pages)?'active':'' ?>"
           href="#leaveMenu"
           data-bs-toggle="collapse"
           aria-expanded="<?= in_array($current_page,$leave_pages)?'true':'false' ?>">

          <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
            <i class="bi bi-calendar-check text-dark"></i>
          </div>

          <span class="nav-link-text ms-1">Leave</span>
          <i class="bi bi-chevron-down sidebar-arrow"></i>
        </a>

        <div class="collapse <?= in_array($current_page,$leave_pages)?'show':'' ?>" id="leaveMenu">
          <ul class="navbar-nav ms-4">
            <li class="nav-item">
              <a class="nav-link <?= $current_page=='manage_leave.php'?'active':'' ?>" href="manage_leave">
                <i class="bi bi-envelope-paper me-2"></i>
                Leave Requests
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?= $current_page=='manage_leave_types.php'?'active':'' ?>" href="manage_leave_types">
                <i class="bi bi-tags me-2"></i>
                Leave Types
              </a>
            </li>
          </ul>
        </div>
      </li>

        <li class="nav-item">
          <a class="nav-link <?= $current_page=='schedule_calendar.php'?'active':'' ?>" href="schedule_calendar">
            <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="bi bi-calendar-day text-dark"></i>
            </div>
            <span class="nav-link-text ms-1">Calendar</span>
          </a>
        </li>


        <li class="nav-item">
          <a class="nav-link <?= $current_page=='manage_user.php'?'active':'' ?>" href="manage_user">
            <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="bi bi-people text-dark"></i>
            </div>
            <span class="nav-link-text ms-1">Users</span>
          </a>
        </li>


        <li class="nav-item">
          <a class="nav-link <?= $current_page=='manage_organization.php'?'active':'' ?>" href="manage_organization">
            <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="bi bi-building text-dark"></i>
            </div>
            <span class="nav-link-text ms-1">Organization</span>
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link <?= $current_page=='tickets.php'?'active':'' ?>" href="tickets">
            <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="bi bi-ticket-perforated text-dark"></i>
            </div>
            <span class="nav-link-text ms-1">Tickets</span>
          </a>
        </li>

        <?php
        $lead_pages = [
            'lead_dashboard.php',
            'manage_leads.php',
            'add_lead.php',
            'followup_monitor.php'
        ];
        ?>

        <li class="nav-item">
          <a class="nav-link <?= in_array($current_page,$lead_pages)?'active':'' ?>"
             href="#leadMenu"
             data-bs-toggle="collapse"
             role="button"
             aria-expanded="<?= in_array($current_page,$lead_pages)?'true':'false' ?>">

            <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2">
              <i class="bi bi-graph-up text-dark"></i>
            </div>
            Leads
            <i class="bi bi-chevron-down sidebar-arrow"></i>
          </a>

          <div class="collapse <?= in_array($current_page,$lead_pages)?'show':'' ?>" id="leadMenu">
            <ul class="navbar-nav ms-4">

              <li class="nav-item">
                <a class="nav-link <?= $current_page=='lead_dashboard.php'?'active':'' ?>"
                   href="lead_dashboard">
                  <i class="bi bi-speedometer2 me-2"></i> Lead Dashboard
                </a>
              </li>

              <li class="nav-item">
                <a class="nav-link <?= $current_page=='manage_leads.php'?'active':'' ?>"
                   href="manage_leads">
                  <i class="bi bi-list-task me-2"></i> Manage Leads
                </a>
              </li>

              <li class="nav-item">
                <a class="nav-link <?= $current_page=='add_lead.php'?'active':'' ?>"
                   href="add_lead">
                  <i class="bi bi-upload me-2"></i> Bulk Lead Upload
                </a>
              </li>

              <li class="nav-item">
                <a class="nav-link <?= $current_page=='followup_monitor.php'?'active':'' ?>"
                   href="followup_monitor">
                    <i class="bi bi-graph-up-arrow me-2"></i>
                    Follow-up Monitor
                </a>
            </li>

            </ul>
          </div>
        </li>
        
        
        <?php
      $asset_pages = [
        'assets_manage.php',
        'asset_documents.php',
        'asset_recovery_manage.php'
      ];
      ?>

      <li class="nav-item">
        <a class="nav-link <?= in_array($current_page,$asset_pages)?'active':'' ?>"
           href="#assetMenu"
           data-bs-toggle="collapse"
           aria-expanded="<?= in_array($current_page,$asset_pages)?'true':'false' ?>">

          <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
            <i class="bi bi-laptop text-dark"></i>
          </div>

          <span class="nav-link-text ms-1">Assets</span>
          <i class="bi bi-chevron-down sidebar-arrow"></i>
        </a>

        <div class="collapse <?= in_array($current_page,$asset_pages)?'show':'' ?>" id="assetMenu">
          <ul class="navbar-nav ms-4">

            <li class="nav-item">
              <a class="nav-link <?= $current_page=='assets_manage.php'?'active':'' ?>" href="assets_manage">
                <i class="bi bi-box-seam me-2"></i>
                Manage Assets
              </a>
            </li>


            <li class="nav-item">
              <a class="nav-link <?= $current_page=='asset_documents.php'?'active':'' ?>" href="asset_documents">
                <i class="bi bi-file-earmark-check me-2"></i>
                Asset Verification
              </a>
            </li>

            <!-- ✅ Asset Recovery (NEW) -->
            <li class="nav-item">
              <a class="nav-link <?= $current_page=='asset_recovery_manage.php'?'active':'' ?>" href="asset_recovery_manage">
                <i class="bi bi-cash-coin me-2"></i>
                Asset Recovery
              </a>
            </li>

          </ul>
        </div>
      </li>
        
        
        


        <li class="nav-item">
          <a class="nav-link <?= $current_page=='edit_profile.php'?'active':'' ?>" href="edit_profile">
            <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="bi bi-person-circle text-dark"></i>
            </div>
            <span class="nav-link-text ms-1">Profile</span>
          </a>
        </li>
        
        <li class="nav-item">
          <a class="nav-link <?= $current_page=='client_manual.php'?'active':'' ?>" href="client_manual">
            <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="bi bi-journal-richtext text-dark"></i>
            </div>
            <span class="nav-link-text ms-1">Guide Manual</span>
          </a>
        </li>


        <li class="nav-item">
          <a class="btn btn-primary mt-3 w-100 sidebar-subscription-btn" href="subscription_plans">
            Subscription
          </a>
        </li>

        <li class="nav-item">
          <a class="btn btn-danger mt-1 w-100 sidebar-logout-btn" href="logout">
            LOGOUT
          </a>
        </li>

      </ul>
    </div>
    
  </aside>
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
            <li class="mb-2">
            </li>
            
            <li>

</li>
<li class="nav-item d-xl-none ps-0 d-flex align-items-center">
<a href="javascript:history.back()" class="nav-link text-body p-0 admin-header-mobile-icon admin-header-mobile-back" aria-label="Go back" title="Go back">
<i class="bi bi-arrow-left-circle-fill" style="font-size: 1.15rem; color: #1f4c8f;"></i></a>
</li>
            <li class="nav-item d-xl-none ps-0 d-flex align-items-center">
              <a href="javascript:;" class="nav-link text-body p-0 admin-header-mobile-icon admin-header-mobile-menu" id="iconNavbarSidenav">
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
                <a href="subscription_plans" class="btn btn-primary">Proceed to Choose Plan</a>
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
        <a href="subscription_plans" class="btn btn-primary">Renew Now</a>
    </div>
</div>

<script>
function closeReminderModal() {
    document.getElementById('reminderModal').style.display = 'none';
    fetch('dismiss_reminder'); // Mark reminder as seen
}
</script>
<?php $_SESSION['reminder_shown'] = true; ?>
<?php endif; ?>
