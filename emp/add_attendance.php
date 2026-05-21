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

// Get employee details (office, restriction status, and auto punch-out preference)
$stmt = $conn->prepare("
SELECT office, restriction_status, disable_auto_punchout
FROM employees 
WHERE id = ?
");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$employee = $stmt->get_result()->fetch_assoc();
$stmt->close();

$restriction_status = strtolower($employee['restriction_status']); // Normalize value
$employee_office = $employee['office']; // Stored as "office_name_state_name"
$disable_auto_punchout = !empty($employee['disable_auto_punchout']);

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
                window.location.href = 'add_attendance';
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
FROM attendance 
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
    $existingAttendanceStmt = $conn->prepare("
        SELECT id
        FROM attendance
        WHERE employee_id = ? AND DATE(punch_in_time) = ?
        LIMIT 1
    ");
    $existingAttendanceStmt->bind_param("is", $employee_id, $missing_date);
    $existingAttendanceStmt->execute();
    $existingAttendanceStmt->store_result();
    $attendanceExists = $existingAttendanceStmt->num_rows > 0;
    $existingAttendanceStmt->close();

    if ($attendanceExists) {
        $missing_date = date('Y-m-d', strtotime($missing_date . ' +1 day'));
        continue;
    }

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
        INSERT INTO attendance (employee_id, punch_in_time, punch_out_time, working_hours, office, status) 
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
            FROM attendance 
            WHERE employee_id = ? AND DATE(punch_in_time) = ?
        ");
        $stmt->bind_param("is", $employee_id, $today_date);
        $stmt->execute();
        $stmt->bind_result($punch_in_time);
        $stmt->fetch();
        $stmt->close();
        if ($punch_in_time) {
            $message = "You have already punched in for today!";
            $message_type = 'danger';
        } else {
            $location_in = $latitude . "," . $longitude;
            $stmt = $conn->prepare("
                INSERT INTO attendance (employee_id, punch_in_time, location_in, selfie_in, current_location, current_location_updated_at, office, status) 
                VALUES (?, NOW(), ?, ?, ?, NOW(), ?, 'Present')
            ");
            $stmt->bind_param("issss", $employee_id, $location_in, $target_file, $location_in, $employee_office);
            $stmt->execute();
            $stmt->close();
            $message = "Punch-in successful!";
            $message_type = 'success';
        }
    } elseif ($action === 'punch_out') {
        $stmt = $conn->prepare("
            SELECT punch_in_time 
            FROM attendance 
            WHERE employee_id = ? AND punch_out_time IS NULL
        ");
        $stmt->bind_param("i", $employee_id);
        $stmt->execute();
        $stmt->bind_result($punch_in_time);
        $stmt->fetch();
        $stmt->close();
        if (!$punch_in_time) {
            $message = "You have not punched in or already punched out!";
            $message_type = 'danger';
        } 
        $punch_in_date = date('Y-m-d', strtotime($punch_in_time));
        $current_date = date('Y-m-d');
        if ($punch_in_date !== $current_date) {
            $location_out = $latitude . "," . $longitude;

            if ($disable_auto_punchout) {
                $punch_out_time = date('Y-m-d H:i:s');
                $diff_seconds = strtotime($punch_out_time) - strtotime($punch_in_time);
                $hours = floor($diff_seconds / 3600);
                $minutes = round(($diff_seconds % 3600) / 60);
                $working_hours = max(0, $hours + ($minutes / 60));
                $status = 'Present';

                $stmt = $conn->prepare("
                    UPDATE attendance 
                    SET punch_out_time = ?, location_out = ?, selfie_out = ?, 
                        working_hours = ?, current_location = NULL, status = ?
                    WHERE employee_id = ? AND punch_out_time IS NULL
                ");
                $stmt->bind_param("sssdsi", $punch_out_time, $location_out, $target_file, $working_hours, $status, $employee_id);
                $message = "Previous day's attendance punched out successfully!";
                $message_type = 'success';
            } else {
                $punch_out_time = $punch_in_date . " 00:00:00";
                $stmt = $conn->prepare("
                    UPDATE attendance 
                    SET punch_out_time = ?, location_out = ?, selfie_out = ?, 
                        working_hours = 0, current_location = NULL, status = 'Absent'
                    WHERE employee_id = ? AND punch_out_time IS NULL
                ");
                $stmt->bind_param("sssi", $punch_out_time, $location_out, $target_file, $employee_id);
            }

            $stmt->execute();
            $stmt->close();
         } else {
            $location_out = $latitude . "," . $longitude;
            $punch_out_time = date('Y-m-d H:i:s');
            $diff_seconds = strtotime($punch_out_time) - strtotime($punch_in_time);
            $hours = floor($diff_seconds / 3600);
            $minutes = round(($diff_seconds % 3600) / 60);
            $working_hours = $hours + ($minutes / 60);
            $stmt = $conn->prepare("
                UPDATE attendance 
                SET punch_out_time = NOW(), location_out = ?, selfie_out = ?, 
                    working_hours = ?, current_location = NULL, status = 'Present'
                WHERE employee_id = ? AND punch_out_time IS NULL
            ");
            $stmt->bind_param("ssdi", $location_out, $target_file, $working_hours, $employee_id);
            $stmt->execute();
            $stmt->close();
            $message = "Punch-out successful!";
            $message_type = 'success';
        }
    }
}
?>
<!-- End Navbar -->
<style>
    /* Make card fill screen like camera view */
.camera-card {
    position: relative;
    height: 70vh;
    background-color: transparent;
    overflow: hidden;

    padding: 0;
    animation: fadeInCamera 0.5s ease-in-out;
}

/* Top fixed name & ID */
.camera-header {
    position: absolute;
    top: 10px;
    left: 0;
    width: 100%;
    color: #fff;
    text-align: center;
    z-index: 10;
    font-size: 18px;
    font-weight: bold;
    animation: slideDown 0.6s ease-out;
}

.camera-header .employee-name {
    font-size: 20px;
}

.camera-header .employee-id {
    font-size: 16px;
    opacity: 0.85;
}

/* Camera container fills body */
#camera-container {
    width: 100%;
    height: 100%;
    position: relative;
    background: transparent;
}

#video {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Optional frame */
.camera-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border: 2px dashed rgba(255, 255, 255, 0.25);
    border-radius: 0px;
    pointer-events: none;
    animation: fadeInOverlay 1s ease-in-out;
}

/* Sticky punch buttons */
#punchInDiv,
#punchOutDiv {
    position: absolute;
    bottom: 20px;
    width: 100%;
    text-align: center;
    z-index: 15;
    animation: fadeUp 0.5s ease-in-out;
}

#punchInBtn,
#punchOutBtn {
    font-size: 20px;
    padding: 12px 24px;
    border-radius: 30px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
    transition: transform 0.2s ease-in-out;
}

#punchInBtn:active,
#punchOutBtn:active {
    transform: scale(0.95);
}

/* Hide overflow if message shows */
.camera-body {
    height: 100%;
    overflow: hidden;
}

/* Responsive tweaks */
@media (max-width: 767px) {
    .camera-card {
        height: 70vh;
        border-radius: 0;
    }
}

/* Animations */
@keyframes fadeInCamera {
    from {
        opacity: 0;
        transform: scale(1.05);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

@keyframes fadeUp {
    from {
        transform: translateY(50px);
        opacity: 0;
    }
    to {
        transform: translateY(0px);
        opacity: 1;
    }
}

@keyframes fadeInOverlay {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes slideDown {
    from {
        transform: translateY(-30px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

</style>
<!-- End Navbar -->
<div class="container-fluid py-4">
        <?php if ($message): ?>
                <div class="alert alert-<?= $message_type; ?>"><?= $message; ?></div>
            <?php endif; ?>
  
        <div class="card camera-card">
            <!-- Top bar showing name and ID -->
            <div class="camera-header">
                <div class="employee-name"><?= htmlspecialchars($employee_name) ?></div>
                <div class="employee-id">EMP-ID: <?= htmlspecialchars($employee_unique_id) ?></div>
            </div>

            <!-- Success Message Display -->
        

            <div class="camera-body text-center">
                <form id="attendanceForm" method="POST" onsubmit="disableSubmitButton();">
                    <input type="hidden" name="latitude" id="latitude" required>
                    <input type="hidden" name="longitude" id="longitude" required>
                    <input type="hidden" name="action" id="action" required>
                    <input type="hidden" name="selfie_data" id="selfie_data" required>

                    <div id="camera-container">
                        <video id="video" autoplay playsinline></video>
                        <canvas id="canvas" style="display:none;"></canvas>
                        <div class="camera-overlay"></div>
                    </div>

                    <div id="punchInDiv">
                        <button type="button" id="punchInBtn" class="btn btn-success btn-lg" onclick="handlePunch('punch_in')">
                            <img style="height: 20px;width:20px" src="assets/img/in.png"> Punch In
                        </button>
                    </div>
                    <div id="punchOutDiv" style="display: none;">
                        <button type="button" id="punchOutBtn" class="btn btn-danger btn-lg" onclick="handlePunch('punch_out')">
                            <img style="height: 20px;width:20px" src="assets/img/out.png"> Punch Out
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<script>
    function handlePunch(action) {
        // Determine the button clicked (Punch In or Punch Out)
        const buttonId = action === 'punch_in' ? 'punchInBtn' : 'punchOutBtn';
        const button = document.getElementById(buttonId);

        // Disable the button and show loading animation
        button.disabled = true;
        button.innerHTML = `
            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            Submitting...`;

        // Simulate form submission or async action (replace with actual logic)
        getLocation(action).then(() => {
            // Re-enable the button and restore original text
            button.disabled = false;
            button.innerHTML = action === 'punch_in'
                ? `<i class="fas fa-sign-in-alt"></i> Punch In`
                : `<i class="fas fa-sign-out-alt"></i> Punch Out`;
        }).catch(() => {
            // Handle errors (optional)
            alert('Something went wrong!');
            button.disabled = false;
            button.innerHTML = action === 'punch_in'
                ? `<i class="fas fa-sign-in-alt"></i> Punch In`
                : `<i class="fas fa-sign-out-alt"></i> Punch Out`;
        });
    }

    // Simulated getLocation function (replace with your actual implementation)
    function getLocation(action) {
        return new Promise((resolve) => {
            setTimeout(() => {
                console.log(`Action: ${action} completed`);
                resolve();
            }, 1000); // Simulates a 2-second delay
        });
    }
</script>

<!--<script>-->
<!--window.addEventListener('load', function () {-->

<!--    if (!sessionStorage.getItem("washroom_modal_shown")) {-->

<!--        Swal.fire({-->
<!--            width: 900,-->
<!--            background: 'linear-gradient(135deg, #5f8f7b, #7fb29c)',-->
<!--            showConfirmButton: true,-->
<!--            confirmButtonText: 'Close',-->
<!--            allowOutsideClick: false,-->
<!--            html: `-->
<!--                <div style="color:#fff; font-family: sans-serif;">-->

<!--                    <h2 style="text-align:center; font-weight:bold;">-->
<!--                        🚽 OFFICE WASHROOM USAGE LEADERBOARD 🚽-->
<!--                    </h2>-->

<!--                    <h3>🏆 Bathroom Champions of the Day</h3>-->

<!--                    <table style="width:100%; border-collapse:collapse; overflow:hidden; border-radius:10px;">-->
<!--                        <thead style="background:#1e3d34; color:#fff;">-->
<!--                            <tr>-->
<!--                                <th style="padding:10px;">Rank</th>-->
<!--                                <th>Employee</th>-->
<!--                                <th>Visits 🚶</th>-->
<!--                                <th>Time ⏱</th>-->
<!--                                <th>Mobile 📱</th>-->
<!--                                <th>Water 💧</th>-->
<!--                            </tr>-->
<!--                        </thead>-->
<!--                        <tbody style="background:#f5f5f5; color:#333;">-->
<!--                            <tr style="background:#ffd700;">-->
<!--                                <td style="padding:10px;">1</td>-->
<!--                                <td>Arpita</td>-->
<!--                                <td>5</td>-->
<!--                                <td>35 min</td>-->
<!--                                <td>No</td>-->
<!--                                <td>10</td>-->
<!--                            </tr>-->
<!--                            <tr style="background:#c0c0c0;">-->
<!--                                <td style="padding:10px;">2</td>-->
<!--                                <td>Pramita</td>-->
<!--                                <td>4</td>-->
<!--                                <td>30 min</td>-->
<!--                                <td>Yes</td>-->
<!--                                <td>8</td>-->
<!--                            </tr>-->
<!--                            <tr style="background:#cd7f32; color:#fff;">-->
<!--                                <td style="padding:10px;">3</td>-->
<!--                                <td>Shubham</td>-->
<!--                                <td>4</td>-->
<!--                                <td>28 min</td>-->
<!--                                <td>Yes</td>-->
<!--                                <td>4</td>-->
<!--                            </tr>-->
<!--                            <tr>-->
<!--                                <td style="padding:10px;">4</td>-->
<!--                                <td>Sambhu</td>-->
<!--                                <td>3</td>-->
<!--                                <td>25 min</td>-->
<!--                                <td>Yes</td>-->
<!--                                <td>0</td>-->
<!--                            </tr>-->
<!--                            <tr>-->
<!--                                <td style="padding:10px;">5</td>-->
<!--                                <td>Himansu</td>-->
<!--                                <td>3</td>-->
<!--                                <td>21 min</td>-->
<!--                                <td>No</td>-->
<!--                                <td>2</td>-->
<!--                            </tr>-->
<!--                        </tbody>-->
<!--                    </table>-->

<!--                    <br>-->

<!--                    <h3>😂 Special Awards</h3>-->

<!--                    <div style="background:#ecf0f1; padding:10px; border-radius:8px; margin-bottom:8px; color:#333;">-->
<!--                        🐢 Longest Stay Award: <b>Arpita & Kabir Bhai</b>-->
<!--                    </div>-->

<!--                    <div style="background:#ecf0f1; padding:10px; border-radius:8px; margin-bottom:8px; color:#333;">-->
<!--                        🚀 Quick Escape Award: <b>Karma</b>-->
<!--                    </div>-->

<!--                    <div style="background:#ecf0f1; padding:10px; border-radius:8px; margin-bottom:8px; color:#333;">-->
<!--                        📱 Mobile Addict Award: <b>Sambhu</b>-->
<!--                    </div>-->

<!--                    <div style="background:#ecf0f1; padding:10px; border-radius:8px; margin-bottom:8px; color:#333;">-->
<!--                        💧 Water Warrior: <b>Pramita & Arpita</b>-->
<!--                    </div>-->

<!--                    <div style="background:#ecf0f1; padding:10px; border-radius:8px; margin-bottom:8px; color:#333;">-->
<!--                        🚶 Frequent Visitor: <b>Shubham</b>-->
<!--                    </div>-->

<!--                    <br>-->

<!--                    <h3>💧 Awareness Corner</h3>-->

<!--                    <div style="background:#f5b7b1; padding:12px; border-radius:10px; color:#333;">-->
<!--                        ⚠️ For your 50 ml urine, approximately 2 buckets of water are wasted! 😎-->
<!--                    </div>-->

<!--                    <br>-->

<!--                    <div style="text-align:center; font-weight:bold;">-->
<!--                        "Work hard, flush smart, and don’t turn the washroom into your second office!" 🚽 😆-->
<!--                    </div>-->

<!--                </div>-->
<!--            `-->
<!--        });-->

<!--        sessionStorage.setItem("washroom_modal_shown", "true");-->
<!--    }-->

<!--});-->
<!--</script>-->

  
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
                });
            } else {
                alert("Geolocation is not supported by this browser.");
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
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting...'; // Optional: change the button text
    }
    </script>
<script>
    // Fetch attendance status and toggle punch buttons
fetch('check_punch_status')
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


