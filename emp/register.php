<?php
require 'db_connection.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Function to generate Employee ID
function generateEmployeeID($conn)
{
    $prefix = "GCPL";
    // Get the maximum existing employee ID from the database
    $stmt = $conn->prepare("SELECT MAX(employee_id) AS max_id FROM employees WHERE employee_id LIKE ?");
    $likePrefix = $prefix . "%";
    $stmt->bind_param("s", $likePrefix);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    if ($row['max_id']) {
        // Extract the numeric part from the max_id (e.g., GCPL0004 -> 4)
        $lastNumber = (int)substr($row['max_id'], strlen($prefix));
        $nextNumber = $lastNumber + 1;
    } else {
        // If no records exist, start with 1
        $nextNumber = 1;
    }
    // Format the number with leading zeros (e.g., 1 -> 0001)
    $formattedNumber = str_pad($nextNumber, 4, "0", STR_PAD_LEFT);
    // Combine the prefix and the formatted number
    $employeeID = $prefix . $formattedNumber;
    return $employeeID;
}

function generateUniqueEmployeeID(mysqli $conn, int $maxAttempts = 5): string
{
    for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
        $employeeID = generateEmployeeID($conn);
        $checkStmt = $conn->prepare("SELECT 1 FROM employees WHERE employee_id = ? LIMIT 1");
        $checkStmt->bind_param("s", $employeeID);
        $checkStmt->execute();
        $exists = $checkStmt->get_result()->num_rows > 0;
        $checkStmt->close();

        if (!$exists) {
            return $employeeID;
        }

        usleep(100000);
    }

    throw new RuntimeException('Unable to generate a unique employee ID. Please try again.');
}

function getUniqueUploadPath(string $targetDir, string $originalName): string
{
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    $baseName = pathinfo($originalName, PATHINFO_FILENAME);
    $safeBaseName = preg_replace('/[^A-Za-z0-9_-]/', '_', $baseName) ?: 'file';
    $uniqueSuffix = bin2hex(random_bytes(6));

    return $targetDir . $safeBaseName . '_' . $uniqueSuffix . ($extension ? '.' . strtolower($extension) : '');
}

function duplicateEmployeeFieldExists(mysqli $conn, string $field, string $value): bool
{
    $allowedFields = ['phone', 'email', 'adhar_number', 'pan_number'];
    if (!in_array($field, $allowedFields, true) || $value === '') {
        return false;
    }

    $stmt = $conn->prepare("SELECT 1 FROM employees WHERE {$field} = ? LIMIT 1");
    $stmt->bind_param("s", $value);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    return $exists;
}

function ensureCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function isValidDateValue(?string $value): bool
{
    if ($value === null || $value === '') {
        return true;
    }

    $date = DateTime::createFromFormat('Y-m-d', $value);
    return $date instanceof DateTime && $date->format('Y-m-d') === $value;
}

function validateUploadedFile(array $file, array $allowedExtensions, array $allowedMimeTypes, int $maxBytes, string $label): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return "Failed to upload {$label}.";
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        return "Invalid {$label} upload.";
    }

    if (($file['size'] ?? 0) > $maxBytes) {
        return "{$label} exceeds the allowed size limit.";
    }

    $extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true)) {
        return "{$label} file type is not allowed.";
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = $finfo ? finfo_file($finfo, $file['tmp_name']) : '';
    if ($finfo) {
        finfo_close($finfo);
    }

    if (!in_array($mimeType, $allowedMimeTypes, true)) {
        return "{$label} content type is not allowed.";
    }

    return null;
}

function old(string $key, string $default = ''): string
{
    return htmlspecialchars($_POST[$key] ?? $default, ENT_QUOTES, 'UTF-8');
}

$registrationErrors = [];
$csrfToken = ensureCsrfToken();
// Handle add employee action
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_employee'])) {
        $postedCsrfToken = $_POST['csrf_token'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $postedCsrfToken)) {
            $registrationErrors[] = 'Invalid form submission. Please refresh the page and try again.';
        }

        $name = trim($_POST['name'] ?? '');
        $dob = $_POST['dob'] ?? '';
        $anniversary = $_POST['anniversary'] ?? '';
        $passwordRaw = $_POST['password'] ?? '';
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $designation = trim($_POST['designation'] ?? '');
        $role = $_POST['role'] ?? '';
        $manager = $_POST['manager'] ?? '';
        $salary_type = $_POST['salary_type'] ?? '';
        $office = $_POST['office'] ?? '';
        $latitude = trim($_POST['latitude'] ?? '');
        $longitude = trim($_POST['longitude'] ?? '');
        $punchin_time = $_POST['punchin_time'] ?? '';
        $punchout_time = $_POST['punchout_time'] ?? '';
        $break_time = $_POST['break_time'] ?? '';
        $working_hours = $_POST['working_hours'] ?? '';
        $hourly_salary = $_POST['hourly_salary'] ?? '';
        $daily_salary = $_POST['daily_salary'] ?? '';
        $date_of_joining = $_POST['date_of_joining'] ?? '';
        $department = trim($_POST['department'] ?? '');
        $emergency_contact = trim($_POST['emergency_contact'] ?? '');
        $emergency_relation = trim($_POST['emergency_relation'] ?? '');
        $bank_account = trim($_POST['bank_account'] ?? '');
        $ifsc_code = trim($_POST['ifsc_code'] ?? '');
        $adhar_number = trim($_POST['adhar_number'] ?? '');
        $pan_number = trim($_POST['pan_number'] ?? '');
        $epf_number = trim($_POST['epf_number'] ?? '');
        $esic = trim($_POST['esic'] ?? '');
        $status = $_POST['status'] ?? 'Pending';

        if ($name === '' || mb_strlen($name) > 120) {
            $registrationErrors[] = 'Please enter a valid full name.';
        }

        if ($passwordRaw === '' || strlen($passwordRaw) < 8) {
            $registrationErrors[] = 'Password must be at least 8 characters.';
        }

        if ($phone === '' || !preg_match('/^[0-9]{10,15}$/', $phone)) {
            $registrationErrors[] = 'Please enter a valid phone number.';
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $registrationErrors[] = 'Please enter a valid email address.';
        }

        if (!isValidDateValue($dob) || !isValidDateValue($anniversary) || !isValidDateValue($date_of_joining)) {
            $registrationErrors[] = 'One or more date fields are invalid.';
        }

        if (!in_array($salary_type, ['Monthly', 'Daily'], true)) {
            $registrationErrors[] = 'Please select a valid salary type.';
        }

        if ($role !== 'Employee') {
            $registrationErrors[] = 'Public registration is allowed for employees only.';
        }

        if (!preg_match('/^\d{2}:\d{2}$/', $punchin_time) || !preg_match('/^\d{2}:\d{2}$/', $punchout_time)) {
            $registrationErrors[] = 'Please enter valid punch in and punch out times.';
        }

        if (!is_numeric($break_time) || (float) $break_time < 0 || (float) $break_time > 600) {
            $registrationErrors[] = 'Please enter a valid break time.';
        }

        $officeStmt = $conn->prepare("SELECT 1 FROM offices WHERE CONCAT(office_name, '_', state_name) = ? LIMIT 1");
        $officeStmt->bind_param("s", $office);
        $officeStmt->execute();
        $officeExists = $officeStmt->get_result()->num_rows > 0;
        $officeStmt->close();
        if (!$officeExists) {
            $registrationErrors[] = 'Please select a valid office.';
        }

        if ($manager !== '') {
            if (!ctype_digit((string) $manager)) {
                $registrationErrors[] = 'Please select a valid manager.';
            } else {
                $managerId = (int) $manager;
                $managerStmt = $conn->prepare("SELECT 1 FROM employees WHERE id = ? AND role = 'Manager' AND status = 'Active' LIMIT 1");
                $managerStmt->bind_param("i", $managerId);
                $managerStmt->execute();
                $managerExists = $managerStmt->get_result()->num_rows > 0;
                $managerStmt->close();
                if (!$managerExists) {
                    $registrationErrors[] = 'Please select a valid active manager.';
                }
            }
        }

        $duplicateChecks = [
            'phone' => $phone,
            'email' => $email,
            'adhar_number' => $adhar_number,
            'pan_number' => $pan_number,
        ];

        foreach ($duplicateChecks as $field => $value) {
            if (duplicateEmployeeFieldExists($conn, $field, $value)) {
                $registrationErrors[] = 'The ' . str_replace('_', ' ', $field) . ' is already registered.';
            }
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        $maxUploadBytes = 5 * 1024 * 1024;

        foreach ([
            'photo' => 'Photo',
            'adhar_file' => 'Aadhaar file',
            'pan_file' => 'PAN file',
        ] as $field => $label) {
            $uploadError = validateUploadedFile($_FILES[$field] ?? [], $allowedExtensions, $allowedMimeTypes, $maxUploadBytes, $label);
            if ($uploadError !== null) {
                $registrationErrors[] = $uploadError;
            }
        }

        if ($registrationErrors) {
            goto render_register_page;
        }

        try {
            $employee_id = generateUniqueEmployeeID($conn);
        } catch (RuntimeException $e) {
            $registrationErrors[] = $e->getMessage();
            goto render_register_page;
        }

        $password = password_hash($passwordRaw, PASSWORD_BCRYPT);
        // File uploads (Aadhaar, PAN, photo)
        $target_dir = "../uploads/";
        $photo_path = null;
        $adhar_path = null;
        $pan_path = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
            $photo_path = getUniqueUploadPath($target_dir, $_FILES['photo']['name']);
            move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path);
        }

        if (isset($_FILES['adhar_file']) && $_FILES['adhar_file']['error'] == UPLOAD_ERR_OK) {
            $adhar_path = getUniqueUploadPath($target_dir, $_FILES['adhar_file']['name']);
            move_uploaded_file($_FILES['adhar_file']['tmp_name'], $adhar_path);
        }

        if (isset($_FILES['pan_file']) && $_FILES['pan_file']['error'] == UPLOAD_ERR_OK) {
            $pan_path = getUniqueUploadPath($target_dir, $_FILES['pan_file']['name']);
            move_uploaded_file($_FILES['pan_file']['tmp_name'], $pan_path);
        }
        // Insert employee data into the database
        $stmt = $conn->prepare("INSERT INTO employees (employee_id, name, dob, anniversary, password, phone, email, address, designation, role, manager, salary_type, office, latitude, longitude, punchin_time, punchout_time, break_time, working_hours, hourly_salary, daily_salary, date_of_joining, department, emergency_contact, emergency_relation, bank_account, ifsc_code, adhar_number, pan_number, epf_number, esic,  photo, adhar_file, pan_file,status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssssssssssssssssssssssssssssss", $employee_id, $name, $dob, $anniversary, $password, $phone, $email, $address, $designation, $role, $manager, $salary_type, $office, $latitude, $longitude, $punchin_time, $punchout_time,  $break_time, $working_hours, $hourly_salary, $daily_salary, $date_of_joining, $department, $emergency_contact, $emergency_relation, $bank_account, $ifsc_code, $adhar_number, $pan_number, $epf_number, $esic, $photo_path, $adhar_path, $pan_path, $status);
        if (!$stmt->execute()) {
            echo "<div class='alert alert-danger'>Unable to complete registration right now. Please try again.</div>";
            $stmt->close();
            exit;
        }
        $stmt->close();
        echo "
        <div class='alert alert-success' >
           Employee Registration successfully Complete. Employee ID: $employee_id waiting for Manager Approval
        </div>
        <script>
            // Wait for 3 seconds and then redirect
            setTimeout(function() {
                window.location.href = 'index';
            }, 4000);
        </script>
        ";
    }
}
render_register_page:


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
    <style>
        body.register-page {
            min-height: 100vh;
            background: #ffffff;
            color: #0f172a;
        }

        .register-nav-wrap {
            padding-top: 0.4rem;
        }

        .register-navbar {
            margin: 0.9rem auto 0;
            padding: 0.8rem 1rem !important;
            border: 1px solid rgba(148, 163, 184, 0.18);
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.88) !important;
            box-shadow: 0 22px 50px rgba(15, 23, 42, 0.08);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }

        .register-navbar .navbar-brand-img {
            max-height: 60px !important;
            width: auto;
            object-fit: contain;
        }

        .register-shell {
            position: relative;
            padding: 8rem 0 2.5rem !important;
        }

        .register-page-column {
            margin-bottom: 0 !important;
        }

        .register-card {
            overflow: hidden;
            border: 1px solid rgba(148, 163, 184, 0.18);
            border-radius: 32px;
            background: rgba(255, 255, 255, 0.94);
            box-shadow: 0 30px 80px rgba(15, 23, 42, 0.10);
        }

        .register-card-body {
            position: relative;
            padding: 2rem 2rem 2.15rem !important;
            margin-top: 0 !important;
        }

        .register-card-body::before {
            content: "";
            position: absolute;
            inset: 0 0 auto;
            height: 5px;
            background: #ffffff;
        }

        .register-form {
            position: relative;
            z-index: 1;
        }

        .register-grid {
            --bs-gutter-x: 1.25rem;
            --bs-gutter-y: 1.15rem;
            align-items: flex-start;
        }

        .register-grid > [class*="col-"] {
            margin-top: 0 !important;
        }

        .register-title {
            margin: 0;
            text-align: center;
            color: #111111 !important;
            font-size: clamp(1.75rem, 2.2vw, 2.25rem);
            font-weight: 800;
            letter-spacing: -0.03em;
        }

        .register-form .form-label {
            margin-bottom: 0.55rem;
            color: #475569;
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .register-form .form-control,
        .register-form select.form-control,
        .register-form textarea.form-control {
            min-height: 52px;
            border: 1px solid #d8e2ec;
            border-radius: 18px;
            background: #fbfdff;
            color: #0f172a;
            box-shadow: none;
            padding: 0.78rem 0.95rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease, transform 0.2s ease;
        }

        .register-form textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .register-form input[type="file"].form-control {
            padding-top: 0.78rem;
            padding-bottom: 0.78rem;
        }

        .register-form .form-control::placeholder {
            color: #94a3b8;
        }

        .register-form .form-control:focus,
        .register-form select.form-control:focus,
        .register-form textarea.form-control:focus {
            border-color: #45c736;
            background: #ffffff;
            box-shadow: 0 0 0 0.24rem rgba(69, 199, 54, 0.12);
            transform: translateY(-1px);
        }

        .register-form .alert {
            border: 0;
            border-radius: 18px;
            padding: 0.95rem 1rem;
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
        }

        .register-form .text-danger {
            font-size: 0.78rem;
            font-weight: 700;
        }

        .register-submit-wrap {
            margin-top: 1.65rem !important;
        }

        .register-submit-btn {
            min-width: 200px;
            min-height: 52px;
            padding: 0.85rem 1.6rem;
            border: 0;
            border-radius: 18px;
            background: linear-gradient(135deg, #111827 0%, #1f2937 100%) !important;
            box-shadow: 0 18px 36px rgba(15, 23, 42, 0.18);
            font-size: 0.92rem;
            font-weight: 800;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }

        .register-submit-btn:hover,
        .register-submit-btn:focus {
            transform: translateY(-1px);
            box-shadow: 0 22px 40px rgba(15, 23, 42, 0.2);
        }

        @media (max-width: 991.98px) {
            .register-shell {
                padding-top: 7.4rem !important;
            }

            .register-card-body {
                padding: 1.65rem 1.4rem 1.8rem !important;
            }

            .register-grid {
                --bs-gutter-x: 1rem;
                --bs-gutter-y: 1rem;
            }
        }

        @media (max-width: 767.98px) {
            .register-nav-wrap {
                padding-top: 0.2rem;
            }

            .register-navbar {
                margin-top: 0.35rem;
                margin-left: 0.65rem;
                margin-right: 0.65rem;
                padding: 0.7rem 0.85rem !important;
                border-radius: 20px;
            }

            .register-navbar .navbar-brand {
                margin-left: 0 !important;
            }

            .register-navbar .navbar-brand-img {
                max-height: 44px !important;
            }

            .register-shell {
                padding-top: 4.8rem !important;
                padding-left: 0.35rem;
                padding-right: 0.35rem;
            }

            .register-card {
                border-radius: 24px;
            }

            .register-card-body {
                padding: 1.25rem 1rem 1.5rem !important;
            }

            .register-grid {
                --bs-gutter-x: 0.8rem;
                --bs-gutter-y: 0.85rem;
            }

            .register-title {
                font-size: 1.45rem;
                line-height: 1.2;
            }

            .register-form .form-control,
            .register-form select.form-control,
            .register-form textarea.form-control {
                min-height: 48px;
                border-radius: 16px;
                padding: 0.72rem 0.85rem;
                font-size: 0.95rem;
            }

            .register-form textarea.form-control {
                min-height: 108px;
            }

            .register-submit-wrap {
                margin-top: 1.25rem !important;
            }

            .register-submit-btn {
                width: 100%;
                min-width: 0;
                min-height: 48px;
                border-radius: 16px;
            }
        }
    </style>
</head>
<body class="register-page">
    <div class="container position-sticky z-index-sticky top-0 register-nav-wrap">
    <div class="row">
      <div class="col-12">
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg blur blur-rounded top-0 z-index-3 shadow position-absolute start-0 end-0 mx-4 register-navbar">
          <div class="container-fluid pe-0">
            <a class="navbar-brand font-weight-bolder ms-lg-0 ms-3 " href="dashboard">
                <?php if (!empty($org['logo']) && file_exists("../uploads/org/" . $org['logo'])): ?>
                    <img src="../uploads/org/<?= $org['logo'] ?>" class="navbar-brand-img h-100" style="max-height:55px;" alt="main_logo">
                <?php else: ?>
                    <img src="assets/img/att logo.png" class="navbar-brand-img h-100" style="max-height:55px;" alt="main_logo">
                <?php endif; ?>
            </a>
          </div>
        </nav>
        <!-- End Navbar -->
<!-- HTML Form -->
<div class="container-fluid py-4 register-shell">
    <div class="col-md-12 mb-lg-0 mb-4 register-page-column">
        <div class="card mt-4 register-card">
            <div class="card-body register-card-body">
                <form method="POST" enctype="multipart/form-data" id="employeeForm" class="register-form" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <div class="row register-grid">
                        <h4 class="register-title"><span style="color: #111111;">Employee </span> Registration</h4>
                        <?php if (!empty($registrationErrors)): ?>
                            <div class="col-12 mt-3">
                                <div class="alert alert-danger">
                                    <?= htmlspecialchars(implode(' ', $registrationErrors)) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="col-md-4 mt-4">
                            <label for="name" class="form-label">Full Name</label>
                            <input class="form-control" type="text" name="name" placeholder="Full Name" value="<?= old('name') ?>" autocomplete="name" required>
                        </div>
                        <!-- Phone -->
                        <div class="col-md-4 mt-4">
                            <label for="phone" class="form-label">Phone</label>
                            <input class="form-control" type="text" name="phone" id="phone" onkeyup="checkDuplicate('phone', this.value)" placeholder="Phone" value="<?= old('phone') ?>" autocomplete="tel" required>
                            <div id="phoneError" class="text-danger mt-1"></div>
                        </div>

                        <div class="col-md-4 mt-4">
                            <label for="email" class="form-label">Email (Optional)</label>
                            <input class="form-control" type="email" name="email" id="email" onkeyup="checkDuplicate('email', this.value)" placeholder="Email" value="<?= old('email') ?>" autocomplete="email">
                            <div id="emailError" class="text-danger mt-1"></div>
                        </div>
                        <!-- Password -->
                        <div class="col-md-4 mt-4">
                            <label for="password" class="form-label">Password</label>
                            <input class="form-control" type="password" name="password" placeholder="Password" autocomplete="new-password" required>
                        </div>
                        <!-- dob -->
                        <div class="col-md-4 mt-4">
                            <label for="dob" class="form-label">Date Of Birth</label>
                            <input class="form-control" type="date" name="dob" placeholder="Date Of Birth" value="<?= old('dob') ?>" >
                        </div>

                        <div class="col-md-4 mt-4">
                            <label for="anniversary" class="form-label">Anniversary Date</label>
                            <input class="form-control" type="date" name="anniversary" placeholder="Anniversary Date" value="<?= old('anniversary') ?>">
                        </div>
                      <!-- Office -->
                      <div class="col-md-4 mt-4">
    <label for="office" class="form-label">Office</label>
    <select class="form-control" name="office" id="office" required onchange="fetchOfficeDetails(this.value)">
        <option value="">Select Office</option>
        <?php
        // Fetch offices from the database
        $stmt = $conn->prepare("SELECT office_name, state_name FROM offices ORDER BY office_name ASC");
        $stmt->execute();
        $result = $stmt->get_result();
        $selectedOffice = $_POST['office'] ?? '';

        while ($office = $result->fetch_assoc()) {
            $value = $office['office_name'] . '_' . $office['state_name']; // Format: office_name_state_name
            $selected = $selectedOffice === $value ? 'selected' : '';
            echo "<option value='" . htmlspecialchars($value) . "' {$selected}>" . htmlspecialchars($office['office_name']) . " (" . htmlspecialchars($office['state_name']) . ")</option>";
        }
        ?>
    </select>
</div>
    <input class="form-control" type="hidden" name="latitude" id="latitude" value="<?= old('latitude') ?>" readonly >
    <input class="form-control" type="hidden" name="longitude" id="longitude" value="<?= old('longitude') ?>" readonly >
                        <!-- Address -->
                        <div class="col-md-4 mt-4">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" name="address" placeholder="Address" rows="3" ><?= old('address') ?></textarea>
                        </div>

                        <!-- Designation -->
                        <div class="col-md-4 mt-4">
                            <label for="designation" class="form-label">Designation</label>
                            <input class="form-control" type="text" name="designation" placeholder="Designation" value="<?= old('designation') ?>" >
                        </div>
                        <!-- Department -->
                        <div class="col-md-4 mt-4">
                            <label for="department" class="form-label">Department (Optional)</label>
                            <input class="form-control" type="text" name="department" placeholder="Department" value="<?= old('department') ?>">
                        </div>
                        <!-- Select Role -->
        <div class="col-md-4 mt-4">
            <label for="role" class="form-label">Select Role</label>
            <select class="form-control" name="role" id="role" required>
                <option value="Employee" selected>Employee</option>
            </select>
        </div>

        <!-- Select Manager -->
        <div class="col-md-4 mt-4">
            <label for="manager" class="form-label">Select Manager</label>
            <select class="form-control" name="manager" id="manager">
                <option value="">Select Manager</option>
                <?php
                // Fetch managers with role 'manager' and status 'Active'
                $stmt = $conn->prepare("SELECT id, name FROM employees WHERE role = 'Manager' AND status = 'Active' ORDER BY name ASC");
                $stmt->execute();
                $result = $stmt->get_result();
                $selectedManager = $_POST['manager'] ?? '';

                while ($manager = $result->fetch_assoc()) {
                    $selected = $selectedManager === (string) $manager['id'] ? 'selected' : '';
                    echo "<option value='" . htmlspecialchars($manager['id']) . "' {$selected}>" . htmlspecialchars($manager['name']) . "</option>";
                }
                ?>
            </select>
        </div>
                        <div class="col-md-4 mt-4">
                            <label for="date_of_joining" class="form-label">Date of Joining</label>
                            <input class="form-control" type="date" name="date_of_joining" value="<?= old('date_of_joining') ?>" >
                        </div>
                        <div class="col-md-4 mt-4">
                            <label for="bank_account" class="form-label">Bank Account</label>
                            <input class="form-control" type="text" name="bank_account" placeholder="Bank Account Details" value="<?= old('bank_account') ?>" >
                        </div>

                        <div class="col-md-4 mt-4">
                            <label for="ifsc_code" class="form-label">IFSC Code</label>
                            <input class="form-control" type="text" name="ifsc_code" placeholder="IFSC Code" value="<?= old('ifsc_code') ?>" >
                        </div>

                        <div class="col-md-4 mt-4">
                            <label for="adhar_number" class="form-label">Aadhaar Number</label>
                            <input class="form-control" type="text" name="adhar_number" id="adhar_number" onkeyup="checkDuplicate('adhar_number', this.value)" placeholder="Aadhaar Number" value="<?= old('adhar_number') ?>" >
                            <div id="adharError" class="text-danger mt-1"></div>
                        </div>
                        <div class="col-md-4 mt-4">
                            <label for="pan_number" class="form-label">PAN Number (Optional)</label>
                            <input class="form-control" type="text" name="pan_number" id="pan_number" onkeyup="checkDuplicate('pan_number', this.value)" placeholder="PAN Number" value="<?= old('pan_number') ?>">
                            <div id="panError" class="text-danger mt-1"></div>
                        </div>
                        <div class="col-md-4 mt-3">
                            <label for="epf_number" class="form-label">Employee UAN (Optional)</label>
                            <input class="form-control" type="text" name="epf_number" value="<?= old('epf_number') ?>">
                        </div>
                        <div class="col-md-4 mt-4">
                            <label for="esic" class="form-label">ESIC No. (Optional)</label>
                            <input class="form-control" type="text" name="esic" value="<?= old('esic') ?>">
                        </div>
                        <div class="col-md-4 mt-4">
                            <label for="emergency_contact" class="form-label">Emergency Contact (Optional)</label>
                            <input class="form-control" type="text" name="emergency_contact" value="<?= old('emergency_contact') ?>">
                        </div>
                        <div class="col-md-4 mt-4">
                            <label for="emergency_relation" class="form-label">Relation With Emergency Contact </label>
                            <input class="form-control" type="text" name="emergency_relation" value="<?= old('emergency_relation') ?>">
                        </div>
                        <div class="col-md-4 mt-4">
                            <label for="photo" class="form-label">Photo (Optional)</label>
                            <input class="form-control" type="file" name="photo" accept=".jpg,.jpeg,.png,.pdf">
                        </div>
                        <div class="col-md-4 mt-4">
                            <label for="adhar_file" class="form-label">Upload Aadhaar (Optional)</label>
                            <input class="form-control" type="file" name="adhar_file" accept=".jpg,.jpeg,.png,.pdf">
                        </div>
                        <div class="col-md-4 mt-4">
                            <label for="pan_file" class="form-label">Upload PAN (Optional)</label>
                            <input class="form-control" type="file" name="pan_file" accept=".jpg,.jpeg,.png,.pdf">
                        </div>
                           
                        <div class="col-md-4">
                            <label for="salary_type" class="form-label">Salary Type</label>
                            <select class="form-control" name="salary_type" id="salary_type" required>
                                <option value="">Select Salary Type</option>
                                <option value="Monthly" <?= old('salary_type') === 'Monthly' ? 'selected' : '' ?>>Monthly</option>
                                <option value="Daily" <?= old('salary_type') === 'Daily' ? 'selected' : '' ?>>Daily</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="salary_type" class="form-label">Punchin Time</label>
                            <input class="form-control" type="time" name="punchin_time" id="punchin_time" value="<?= old('punchin_time') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="salary_type" class="form-label">Punchout Time</label>
                            <input class="form-control" type="time" name="punchout_time" id="punchout_time" value="<?= old('punchout_time') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="salary_type" class="form-label">Break Time (in minutes)</label>
                            <input class="form-control" type="number" name="break_time" id="break_time" placeholder="Break Time (in minutes)" value="<?= old('break_time') ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label for="working_hours" class="form-label">Working Hours</label>
                            <input class="form-control" type="text" name="working_hours" id="working_hours" placeholder="Working Hours" value="<?= old('working_hours') ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label for="hourly_salary" class="form-label">Hourly Salary</label>
                            <input class="form-control" type="text" name="hourly_salary" id="hourly_salary" placeholder="Hourly Salary" value="<?= old('hourly_salary') ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label for="daily_salary" class="form-label">Daily Salary</label>
                            <input class="form-control" type="text" name="daily_salary" id="daily_salary" placeholder="Daily Salary" value="<?= old('daily_salary') ?>" readonly>
                        </div>
                        <input type="hidden" name="net_salary" id="net_salary" value="<?= old('net_salary') ?>">
                        <input  type="hidden" name="status" id="status" value="Pending" >
                    </div>
                    <div class="col-md-12 mt-4 register-submit-wrap">
                        <button id="submitBtn" class="btn bg-gradient-dark mb-0 register-submit-btn" type="submit" name="add_employee">Add Employee</button>
                    </div>
                   
            </div>
            </form>
        </div>
    </div>
</div>
</div>
<!-- View Details Modal -->

<?php include("footer.php") ?>
<script>
function fetchOfficeDetails(officeId) {
    if (!officeId) {
        document.getElementById('latitude').value = '';
        document.getElementById('longitude').value = '';
        return;
    }

    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'get_office_details', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onload = function() {
        if (this.status === 200) {
            const response = JSON.parse(this.responseText);
            if (response.success) {
                document.getElementById('latitude').value = response.latitude;
                document.getElementById('longitude').value = response.longitude;
            } else {
                alert(response.message);
            }
        }
    };

    xhr.send('office_id=' + officeId);
}
</script>

<script>
    // Calculate Working Hours, Hourly Salary, and Daily Salary
    document.getElementById('employeeForm').addEventListener('input', function() {
        const punchin = document.getElementById('punchin_time').value;
        const punchout = document.getElementById('punchout_time').value;
        const salary = parseFloat(document.getElementById('net_salary').value) || 0;
        const salaryType = document.getElementById('salary_type').value;
        const breakTime = parseFloat(document.getElementById('break_time').value) || 0; // Break time in minutes
        if (punchin && punchout) {
            const [punchinH, punchinM] = punchin.split(':').map(Number);
            const [punchoutH, punchoutM] = punchout.split(':').map(Number);

            // Calculate total working hours before deducting break time
            let workingHours = ((punchoutH + punchoutM / 60) - (punchinH + punchinM / 60)).toFixed(2);
            workingHours = Math.max(0, workingHours - (breakTime / 60)); // Deduct break time in hours

            document.getElementById('working_hours').value = workingHours > 0 ? workingHours.toFixed(2) : 0;

            if (salaryType === 'Monthly') {
                const hourlySalary = workingHours > 0 ? (salary / 30 / workingHours).toFixed(2) : 0;
                document.getElementById('hourly_salary').value = hourlySalary;
                document.getElementById('daily_salary').value = (salary / 30).toFixed(2);
            } else if (salaryType === 'Daily') {
                document.getElementById('hourly_salary').value = workingHours > 0 ? (salary / workingHours).toFixed(2) : 0;
                document.getElementById('daily_salary').value = salary.toFixed(2);
            }
        }
    });

    function disableSubmitButton() {
        var submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting...'; // Optional: change the button text
    }
</script>
<script>
    function checkDuplicate(field, value) {
        if (!value) return; // Skip validation if the field is empty

        $.ajax({
            url: 'duplicate_check',
            type: 'POST',
            data: {
                field: field,
                value: value
            },
            success: function(response) {
                const errorField = $(`#${field}Error`);
                const inputField = $(`#${field}`);

                if (response === 'exists') {
                    // Highlight the field and display the error message
                    inputField.addClass('border-danger');
                    errorField.text(`This ${field.replace('_', ' ')} is already taken.`);
                } else {
                    // Remove the error highlight and clear the message
                    inputField.removeClass('border-danger');
                    errorField.text('');
                }
            }
        });
    }
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
