<!-- SweetAlert2 CSS and JS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.all.min.js"></script>
<?php
require 'header.php';
date_default_timezone_set('Asia/Kolkata'); // Set PHP timezone to IST
$employee_id = $_SESSION['employee_id'];
$message = '';
$message_type = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require 'db_connection.php';
    // Ensure MySQL uses the correct time zone
    $conn->query("SET time_zone = '+05:30'");
    $action = $_POST['action'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $selfie_data = $_POST['selfie_data'];
    // Decode and save the selfie image
    $target_dir = "../uploads/attendance_selfie/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true); // Ensure the directory exists
    }
    $selfie_image = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $selfie_data));
    $target_file = $target_dir . uniqid() . ".jpg";
    file_put_contents($target_file, $selfie_image);

      // Get employee details (office and restriction status)
$stmt = $conn->prepare("
SELECT office, restriction_status 
FROM employees 
WHERE id = ?
");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$employee = $stmt->get_result()->fetch_assoc();
$stmt->close();

$restriction_status = strtolower($employee['restriction_status']); // Normalize value
$employee_office = $employee['office']; // Stored as "office_name_state_name"

// Check if the employee has an attendance record for today
$attendance_stmt = $conn->prepare("
    SELECT id FROM attendance 
    WHERE employee_id = ? AND DATE(punch_in_time) = CURDATE()
");
$attendance_stmt->bind_param("i", $employee_id);
$attendance_stmt->execute();
$attendance_result = $attendance_stmt->get_result();
$attendance_stmt->close();

// If no attendance record is found, prevent break attendance
if ($attendance_result->num_rows === 0) {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'You are not Punch In Yet for Today!',
                text: 'First punch in for today and try again to take a break.',
                icon: 'warning',
                timer: 3000,
                showConfirmButton: false
            }).then(() => {
                window.location.href = 'add_break_attendance';
            });
        });
    </script>";
    exit;
}

// Fetch office latitude, longitude, and radius from `offices` table
$stmt = $conn->prepare("
SELECT latitude, longitude, radius 
FROM offices 
WHERE CONCAT(office_name, '_', state_name) = ?
");
$stmt->bind_param("s", $employee_office);
$stmt->execute();
$office = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Validate fetched office data
if (!$office) {
die("Office details not found in the database.");
}

$office_lat = (float) $office['latitude'];
$office_lng = (float) $office['longitude'];
$office_radius = (float) $office['radius']; // Office radius in meters

// Get Employee's Live Location from Form Submission
$emp_lat = isset($_POST['latitude']) ? (float) $_POST['latitude'] : null;
$emp_lng = isset($_POST['longitude']) ? (float) $_POST['longitude'] : null;

// Ensure latitude & longitude are received
if (is_null($emp_lat) || is_null($emp_lng)) {
die("Location data is missing. Please enable GPS and try again.");
}

// Function to calculate the distance between two coordinates
function getDistance($lat1, $lng1, $lat2, $lng2) {
$earth_radius = 6371000; // Radius of Earth in meters
$dLat = deg2rad($lat2 - $lat1);
$dLng = deg2rad($lng2 - $lng1);
$a = sin($dLat / 2) * sin($dLat / 2) +
     cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
     sin($dLng / 2) * sin($dLng / 2);
$c = 2 * atan2(sqrt($a), sqrt(1 - $a));
return $earth_radius * $c; // Distance in meters
}

// Check location restriction if enabled
if ($restriction_status == 'yes') {
$distance = getDistance($emp_lat, $emp_lng, $office_lat, $office_lng);

if ($distance > $office_radius) { 
    // If outside the allowed office radius
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Location Error!',
                text: 'You are outside the allowed {$office_radius}m radius.',
                icon: 'error',
                timer: 3000,
                showConfirmButton: false
            }).then(() => {
                window.location.href = 'add_break_attendance';
            });
        });
    </script>";
    exit;
}
}
    // Fetch the employee's expected punch-in and punch-out times
    $stmt = $conn->prepare("SELECT punchin_time, punchout_time FROM employees WHERE id = ?");
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $stmt->bind_result($expected_punch_in, $expected_punch_out);
    $stmt->fetch();
    $stmt->close();
    $stmt = $conn->prepare("SELECT office FROM employees WHERE id = ?");
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $stmt->bind_result($employee_office);
    $stmt->fetch();
    $stmt->close();
    $current_time = date('H:i:s');
    $late_punch_in = false;
    $early_punch_out = false;
    if ($action === 'punch_in' && $current_time > $expected_punch_in) {
        $late_punch_in = true;
        $message = "You are performing a late punch-in!";
        $message_type = 'warning';
    } elseif ($action === 'punch_out' && $current_time < $expected_punch_out) {
        $early_punch_out = true;
        $message = "You are performing an early punch-out!";
        $message_type = 'warning';
    }
   // Check the last punch-out date
$stmt = $conn->prepare("
SELECT DATE(punch_out_time) AS last_punch_out_date 
FROM break_attendance 
WHERE employee_id = ? AND punch_out_time IS NOT NULL 
ORDER BY punch_out_time DESC 
LIMIT 1
");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$stmt->bind_result($last_punch_out_date);
$stmt->fetch();
$stmt->close();
$today_date = date('Y-m-d');

// Handle missing punch-outs for previous days
if ($last_punch_out_date) {
$missing_date = date('Y-m-d', strtotime($last_punch_out_date . ' +1 day'));

while (strtotime($missing_date) < strtotime($today_date)) {
    $event_type = null;

    // Check if the date is a weekly off or holiday from events table
    $stmt = $conn->prepare("
        SELECT event_type 
        FROM events 
        WHERE start_date = ?
    ");
    $stmt->bind_param("s", $missing_date);
    $stmt->execute();
    $stmt->bind_result($event_type);
    $stmt->fetch();
    $stmt->close();

    // Check if the date falls within an approved leave request
    $leave_status = null;
    $stmt = $conn->prepare("
        SELECT 'On Leave'
        FROM leave_requests 
        WHERE employee_id = ? 
        AND status = 'Approved' 
        AND ? BETWEEN start_date AND end_date
    ");
    $stmt->bind_param("is", $employee_id, $missing_date);
    $stmt->execute();
    $stmt->bind_result($leave_status);
    $stmt->fetch();
    $stmt->close();

    // Determine status
    if ($leave_status === 'On Leave') {
        $status = 'On Leave';
    } elseif ($event_type === 'weekly_off') {
        $status = 'Weekly Off';
    } elseif ($event_type === 'holiday') {
        $status = 'Holiday';
    } else {
        $status = 'Absent';
    }

    // Insert missing attendance record
    $stmt = $conn->prepare("
        INSERT INTO break_attendance (employee_id, punch_in_time, punch_out_time, working_hours, office, status) 
        VALUES (?, ?, ?, 0, ?, ?)
    ");
    $absent_punch_in = $missing_date . " 00:00:00";
    $absent_punch_out = $missing_date . " 00:00:00";
    $stmt->bind_param("issss", $employee_id, $absent_punch_in, $absent_punch_out, $employee_office, $status);
    $stmt->execute();
    $stmt->close();

    $missing_date = date('Y-m-d', strtotime($missing_date . ' +1 day'));
}
}

    if ($action === 'punch_in') {
        $stmt = $conn->prepare("
            SELECT punch_in_time 
            FROM break_attendance 
            WHERE employee_id = ? AND DATE(punch_in_time) = ?
        ");
        $stmt->bind_param("is", $employee_id, $today_date);
        $stmt->execute();
        $stmt->bind_result($punch_in_time);
        $stmt->fetch();
        $stmt->close();
        if ($punch_in_time) {
            $message = "You have already Taken Breake  for today!";
            $message_type = 'danger';
        } else {
            $location_in = $latitude . "," . $longitude;
            $stmt = $conn->prepare("
                INSERT INTO break_attendance (employee_id, punch_in_time, location_in, selfie_in, current_location, current_location_updated_at, office, status) 
                VALUES (?, NOW(), ?, ?, ?, NOW(), ?, 'Present')
            ");
            $stmt->bind_param("issss", $employee_id, $location_in, $target_file, $location_in, $employee_office);
            $stmt->execute();
            $stmt->close();
            $message = "Break Time Started successful!";
            $message_type = 'success';
        }
    } elseif ($action === 'punch_out') {
        $stmt = $conn->prepare("
            SELECT punch_in_time 
            FROM break_attendance 
            WHERE employee_id = ? AND punch_out_time IS NULL
        ");
        $stmt->bind_param("i", $employee_id);
        $stmt->execute();
        $stmt->bind_result($punch_in_time);
        $stmt->fetch();
        $stmt->close();
        if (!$punch_in_time) {
            $message = "You have not Take Break or already Taken !";
            $message_type = 'danger';
        } 
        $punch_in_date = date('Y-m-d', strtotime($punch_in_time));
        $current_date = date('Y-m-d');
        if ($punch_in_date !== $current_date) {
            $punch_out_time = $punch_in_date . " 00:00:00";
            $location_out = $latitude . "," . $longitude;
            $stmt = $conn->prepare("
                UPDATE break_attendance 
                SET punch_out_time = ?, location_out = ?, selfie_out = ?, 
                    working_hours = 0, current_location = NULL, status = 'Absent'
                WHERE employee_id = ? AND punch_out_time IS NULL
            ");
            $stmt->bind_param("sssi", $punch_out_time, $location_out, $target_file, $employee_id);
            $stmt->execute();
            $stmt->close();
         } else {
            $location_out = $latitude . "," . $longitude;
            $punch_out_time = date('Y-m-d H:i:s');
        
            // Calculate Working Hours
            $diff_seconds = strtotime($punch_out_time) - strtotime($punch_in_time);
            $hours = floor($diff_seconds / 3600);
            $minutes = round(($diff_seconds % 3600) / 60);
            $working_hours = $hours + ($minutes / 60);
        
            // Update Break Attendance Table
            $stmt = $conn->prepare("
                UPDATE break_attendance 
                SET punch_out_time = NOW(), location_out = ?, selfie_out = ?, 
                    working_hours = ?, current_location = NULL, status = 'Present'
                WHERE employee_id = ? AND punch_out_time IS NULL
            ");
            $stmt->bind_param("ssdi", $location_out, $target_file, $working_hours, $employee_id);
            $stmt->execute();
            $stmt->close();
        
            // **Update Break Hours in Attendance Table**
            // Find the matching attendance record for the same employee and date
            $attendance_stmt = $conn->prepare("
                UPDATE attendance 
                SET break_hours = IFNULL(break_hours, 0) + ? 
                WHERE employee_id = ? AND DATE(punch_in_time) = CURDATE()
            ");
            $attendance_stmt->bind_param("di", $working_hours, $employee_id);
            $attendance_stmt->execute();
            $attendance_stmt->close();
        
            $message = "Break Time End successful!";
            $message_type = 'success';
        }
        
    }
}
?>
<!-- End Navbar -->
<style>
    :root {
        --break-shell-bg: linear-gradient(180deg, #f8fafc 0%, #eef3f8 100%);
        --break-card-border: rgba(148, 163, 184, 0.18);
        --break-card-shadow: 0 24px 56px rgba(15, 23, 42, 0.12);
        --break-camera-bg: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    }

    .break-attendance-page {
        padding-top: 0.95rem !important;
        padding-bottom: 1.2rem !important;
    }

    .break-attendance-card {
        margin-top: 0.35rem;
        border: 1px solid var(--break-card-border);
        border-radius: 28px;
        background: var(--break-shell-bg);
        box-shadow: var(--break-card-shadow);
        overflow: hidden;
    }

    .break-attendance-card .card-header {
        padding: 1.15rem 1.25rem 0.95rem;
        border-bottom: 1px solid rgba(148, 163, 184, 0.12);
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.74) 0%, rgba(248, 250, 252, 0.92) 100%);
    }

    .break-attendance-title {
        margin: 0;
        color: #0f172a;
        font-size: 1.02rem;
        font-weight: 800;
        letter-spacing: -0.02em;
    }

    .break-attendance-empid {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 44px;
        padding: 0.72rem 1rem;
        border-radius: 16px;
        border: 1px solid #111827;
        background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
        box-shadow: 0 16px 30px rgba(15, 23, 42, 0.14);
        font-size: 0.78rem;
        font-weight: 800;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }

    .break-attendance-card .card-body {
        padding: 1.25rem;
    }

    .break-attendance-alert {
        margin: 0 1.25rem;
        border-radius: 18px;
        border: 1px solid rgba(148, 163, 184, 0.16);
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
        padding: 0.95rem 1rem;
        font-weight: 600;
    }

    .break-camera-stage {
        position: relative;
        overflow: hidden;
        min-height: 58vh;
        border-radius: 24px;
        background: var(--break-camera-bg);
        box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.12);
    }

    .break-camera-stage::before {
        content: "";
        position: absolute;
        inset: 10px;
        border-radius: 18px;
        border: 1px solid rgba(148, 163, 184, 0.22);
        pointer-events: none;
        z-index: 2;
    }

    #camera-container {
        position: relative;
        min-height: 58vh;
        border-radius: 24px;
        overflow: hidden;
    }

    #video {
        width: 100%;
        height: 100%;
        min-height: 58vh;
        object-fit: cover;
        filter: saturate(1.04) contrast(1.02);
    }

    .camera-overlay {
        position: absolute;
        inset: 0;
        pointer-events: none;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.06) 0%, rgba(248, 250, 252, 0.04) 28%, rgba(241, 245, 249, 0.05) 74%, rgba(226, 232, 240, 0.08) 100%);
    }

    .camera-overlay::before {
        content: "";
        position: absolute;
        left: 50%;
        top: 50%;
        width: calc(100% - 34px);
        height: calc(100% - 34px);
        transform: translate(-50%, -50%);
        border-radius: 18px;
        border: 1px dashed rgba(148, 163, 184, 0.3);
    }

    .break-action-row {
        margin-top: 1.05rem;
        display: flex;
        justify-content: center;
    }

    #punchInBtn,
    #punchOutBtn {
        min-width: 220px;
        min-height: 52px;
        padding: 0.78rem 1.15rem;
        border-radius: 18px;
        border: 0;
        box-shadow: 0 18px 36px rgba(15, 23, 42, 0.16);
        font-size: 0.96rem;
        font-weight: 800;
        letter-spacing: 0.03em;
        transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
    }

    #punchInBtn {
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    }

    #punchOutBtn {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    }

    #punchInBtn:hover,
    #punchOutBtn:hover {
        transform: translateY(-2px);
        box-shadow: 0 22px 40px rgba(15, 23, 42, 0.2);
    }

    #punchInBtn:disabled,
    #punchOutBtn:disabled {
        opacity: 0.8;
        cursor: not-allowed;
    }

    .break-action-icon {
        width: 20px;
        height: 20px;
        margin-right: 0.5rem;
        object-fit: contain;
        vertical-align: middle;
        filter: brightness(0) invert(1);
    }

    @media (max-width: 767.98px) {
        .break-attendance-page {
            padding-top: 0.6rem !important;
            padding-left: 0.3rem !important;
            padding-right: 0.3rem !important;
            padding-bottom: 0.8rem !important;
        }

        .break-attendance-card {
            border-radius: 22px;
        }

        .break-attendance-card .card-header {
            padding: 0.95rem 0.95rem 0.85rem;
        }

        .break-attendance-card .card-body {
            padding: 0.95rem;
        }

        .break-attendance-title {
            font-size: 0.94rem;
        }

        .break-attendance-empid {
            min-height: 40px;
            padding: 0.65rem 0.85rem;
            font-size: 0.72rem;
        }

        .break-attendance-alert {
            margin: 0 0.95rem;
            padding: 0.85rem 0.9rem;
        }

        .break-camera-stage,
        #camera-container,
        #video {
            min-height: calc(100dvh - 390px);
            border-radius: 20px;
        }

        .camera-overlay::before {
            width: calc(100% - 22px);
            height: calc(100% - 22px);
            border-radius: 16px;
        }

        .break-action-row {
            margin-top: 0.75rem;
        }

        #punchInBtn,
        #punchOutBtn {
            min-width: 190px;
            min-height: 48px;
            padding: 0.72rem 1rem;
            border-radius: 16px;
            font-size: 0.88rem;
        }
    }
</style>
<div class="container-fluid py-4 break-attendance-page">
    <div class="col-md-12 mb-lg-0 mb-4">
        <div class="card mt-4 break-attendance-card">
            <div class="card-header pb-0 p-3">
                <div class="row">
                    <div class="col-6 d-flex align-items-center">
                        <h6 class="mb-0 break-attendance-title">Break Attendance for <?= htmlspecialchars($employee_name) ?></h6>
                    </div>
                    <div class="col-6 text-end">
                        <a class="btn bg-gradient-dark mb-0 break-attendance-empid" href="javascript:;">EMP-ID - <?= htmlspecialchars($employee_unique_id) ?></a>
                    </div>
                </div>
            </div>
          <!-- Success Message Display -->
          <?php if ($message): ?>
    <div class="alert alert-<?= $message_type; ?> break-attendance-alert"><?= $message; ?></div>
<?php endif; ?>

            <div class="card-body p-4 text-center">
            <form id="attendanceForm" method="POST" onsubmit="disableSubmitButton();">
        <input type="hidden" name="latitude" id="latitude" required>
        <input type="hidden" name="longitude" id="longitude" required>
        <input type="hidden" name="action" id="action" required>
        <input type="hidden" name="selfie_data" id="selfie_data" required>

        <div class="break-camera-stage">
        <div id="camera-container">
            <video id="video" autoplay playsinline ></video>
            <canvas id="canvas"  style="display:none;"></canvas>
            <div class="camera-overlay"></div>
        </div>
        </div>
        <div class="mt-4 break-action-row">
        <div id="punchInDiv">
    <button type="button" id="punchInBtn" class="btn btn-success btn-lg" onclick="handlePunch('punch_in')">
    <img class="break-action-icon" src="assets/img/in.png">Start Break
    </button>
</div>
<div id="punchOutDiv" style="display: none;">
    <button type="button" id="punchOutBtn" class="btn btn-danger btn-lg" onclick="handlePunch('punch_out')">
    <img class="break-action-icon" src="assets/img/out.png">End Break
    </button>
</div>

<script>
    function getBreakButtonMarkup(action) {
        return action === 'punch_in'
            ? '<img class="break-action-icon" src="assets/img/in.png">Start Break'
            : '<img class="break-action-icon" src="assets/img/out.png">End Break';
    }

    function handlePunch(action) {
        const buttonId = action === 'punch_in' ? 'punchInBtn' : 'punchOutBtn';
        const button = document.getElementById(buttonId);

        button.disabled = true;
        button.innerHTML = `
            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            Submitting...`;

        try {
            getLocation(action);
        } catch (error) {
            alert('Something went wrong!');
            button.disabled = false;
            button.innerHTML = getBreakButtonMarkup(action);
        }
    }
</script>

        </div>
    </form>
            </div>
        </div>
    </div>
</div>
<?php include("footer.php") ?>
<script>
        function getLocation(action) {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(position => {
                    document.getElementById("latitude").value = position.coords.latitude;
                    document.getElementById("longitude").value = position.coords.longitude;
                    captureSelfie(action);
                }, error => {
                    alert("Error fetching location: " + error.message);
                    const buttonId = action === 'punch_in' ? 'punchInBtn' : 'punchOutBtn';
                    const button = document.getElementById(buttonId);
                    if (button) {
                        button.disabled = false;
                        button.innerHTML = getBreakButtonMarkup(action);
                    }
                });
            } else {
                alert("Geolocation is not supported by this browser.");
                const buttonId = action === 'punch_in' ? 'punchInBtn' : 'punchOutBtn';
                const button = document.getElementById(buttonId);
                if (button) {
                    button.disabled = false;
                    button.innerHTML = getBreakButtonMarkup(action);
                }
            }
        }
        function captureSelfie(action) {
            const video = document.getElementById('video');
            const canvas = document.getElementById('canvas');
            const context = canvas.getContext('2d');

            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            const selfieData = canvas.toDataURL('image/jpeg');
            document.getElementById('selfie_data').value = selfieData;

            document.getElementById('action').value = action;
            document.getElementById('attendanceForm').submit();
        }
        function startCamera() {
            navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } })
                .then(stream => {
                    const video = document.getElementById('video');
                    video.srcObject = stream;
                })
                .catch(error => {
                    alert("Error accessing the camera: " + error.message);
                });
        }
        // Show success popup if redirected with success
        function checkSuccess() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('success')) {
                alert('Attendance successfully recorded!');
                window.location.href = window.location.pathname; // Refresh page without query params
            }
        }
        window.onload = () => {
            startCamera();
            checkSuccess();
        };

        function disableSubmitButton() {
        var submitBtn = document.getElementById('submitBtn');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';
        }
    }
    </script>
<script>
    // Fetch attendance status and toggle punch buttons
fetch('check_break_punch_status')
    .then(response => response.json())
    .then(data => {
        if (data.punched_in) {
            document.getElementById('punchInDiv').style.display = 'none';
            document.getElementById('punchOutDiv').style.display = 'block';

            // Enable or disable Punch Out button based on 60-minute condition
            document.getElementById('punchOutBtn').disabled = !data.enable_punch_out;
        } else {
            document.getElementById('punchInDiv').style.display = 'block';
            document.getElementById('punchOutDiv').style.display = 'none';

            // Disable Punch-In button if punch-out was recent (within 60 minutes)
            if (data.disable_punch_in) {
                document.getElementById('punchInBtn').disabled = true;
            } else {
                document.getElementById('punchInBtn').disabled = false;
            }
        }
    });

    </script> 


