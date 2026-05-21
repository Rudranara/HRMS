<?php
require 'header.php';
date_default_timezone_set('Asia/Kolkata'); // Set PHP timezone to IST
$employee_id = $_SESSION['employee_id'];
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
    // Fetch the employee's expected punch-in and punch-out times
    $stmt = $conn->prepare("SELECT punchin_time, punchout_time FROM employees WHERE id = ?");
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $stmt->bind_result($expected_punch_in, $expected_punch_out);
    $stmt->fetch();
    $stmt->close();
    $current_time = date('H:i:s');
    $late_punch_in = false;
    $early_punch_out = false;

    if ($action === 'punch_in' && $current_time > $expected_punch_in) {
        $late_punch_in = true;
    } elseif ($action === 'punch_out' && $current_time < $expected_punch_out) {
        $early_punch_out = true;
    }
    // Show alerts for late punch-in or early punch-out
    if ($late_punch_in) {
        echo "<script>alert('You are performing a late punch-in!');</script>";
    }
    if ($early_punch_out) {
        echo "<script>alert('You are performing an early punch-out!');</script>";
    }
    // Continue with the rest of the punch-in or punch-out process
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
 // Handle missing punch-outs for previous days
if ($last_punch_out_date) {
    $missing_date = date('Y-m-d', strtotime($last_punch_out_date . ' +1 day'));
    while (strtotime($missing_date) < strtotime($today_date)) {
        // Reset event_type for each iteration
        $event_type = null;

        // Check if the missing date is a weekly_off or holiday in the events table
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

        // Determine the status based on the event type
        if ($event_type === 'weekly_off') {
            $status = 'Weekly Off';
        } elseif ($event_type === 'holiday') {
            $status = 'Holiday';
        } else {
            $status = 'Absent';
        }

        // Insert the missing day's attendance with the determined status
        $stmt = $conn->prepare("
            INSERT INTO attendance (employee_id, punch_in_time, punch_out_time, working_hours, status) 
            VALUES (?, ?, ?, 0, ?)
        ");
        $absent_punch_in = $missing_date . " 00:00:00";
        $absent_punch_out = $missing_date . " 00:00:00";
        $stmt->bind_param("isss", $employee_id, $absent_punch_in, $absent_punch_out, $status);
        $stmt->execute();
        $stmt->close();

        // Move to the next date
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
            echo "<script>alert('You have already punched in for today!'); window.location.href = 'add_attendance';</script>";
            exit;
        }
        $location_in = $latitude . "," . $longitude;
        $stmt = $conn->prepare("
            INSERT INTO attendance (employee_id, punch_in_time, location_in, selfie_in, current_location, current_location_updated_at, status) 
            VALUES (?, NOW(), ?, ?, ?, NOW(), 'Present')
        ");
        $stmt->bind_param("isss", $employee_id, $location_in, $target_file, $location_in);
        $stmt->execute();
        $stmt->close();
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
            echo "<script>alert('You have not punched in or already punched out!'); window.location.href = 'add_attendance';</script>";
            exit;
        }
        $punch_in_date = date('Y-m-d', strtotime($punch_in_time));
        $current_date = date('Y-m-d');
        if ($punch_in_date !== $current_date) {
            $punch_out_time = $punch_in_date . " 00:00:00";
            $location_out = $latitude . "," . $longitude;
            $stmt = $conn->prepare("
                UPDATE attendance 
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
        }
    }
    echo "<script>window.location.href = 'add_attendance?success=1';</script>";
    exit;
}
?>
<!-- End Navbar -->
<div class="container-fluid py-4">
    <div class="col-md-12 mb-lg-0 mb-4">
        <div class="card mt-4">
            <div class="card-header pb-0 p-3">
                <div class="row">
                    <div class="col-6 d-flex align-items-center">
                        <h6 class="mb-0">Attendance for <?= htmlspecialchars($employee_name) ?></h6>
                    </div>
                    <div class="col-6 text-end">
                        <a class="btn bg-gradient-dark mb-0" href="javascript:;">EMP-ID - <?= htmlspecialchars($employee_id) ?></a>
                    </div>
                </div>
            </div>
          <!-- Success Message Display -->
<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">Attendance updated successfully!</div>
<?php endif; ?>
            <div class="card-body p-4 text-center">
            <form id="attendanceForm" method="POST">
        <input type="hidden" name="latitude" id="latitude" required>
        <input type="hidden" name="longitude" id="longitude" required>
        <input type="hidden" name="action" id="action" required>
        <input type="hidden" name="selfie_data" id="selfie_data" required>

        <div id="camera-container">
            <video id="video" autoplay playsinline ></video>
            <canvas id="canvas"  style="display:none;"></canvas>
            <div class="camera-overlay"></div>
        </div>
        <div class="mt-4">
        <div id="punchInDiv">
            <button type="button" class="btn btn-success btn-lg" onclick="getLocation('punch_in')"><i class="fas fa-sign-in-alt"></i> Punch In</button>
        </div>
        <div id="punchOutDiv" style="display: none;">
            <button type="button" class="btn btn-danger btn-lg" onclick="getLocation('punch_out')">  <i class="fas fa-sign-out-alt"></i> Punch Out</button>
        </div>
        </div>
    </form>
            </div>
        </div>
    </div>
</div>
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
    </script>
<script>
        // Fetch attendance status and toggle punch buttons
        fetch('check_punch_status')
            .then(response => response.json())
            .then(data => {
                if (data.punched_in) {
                    document.getElementById('punchInDiv').style.display = 'none';
                    document.getElementById('punchOutDiv').style.display = 'block';
                } else {
                    document.getElementById('punchInDiv').style.display = 'block';
                    document.getElementById('punchOutDiv').style.display = 'none';
                }
            });
    </script> 
<?php include("footer2.php") ?>

