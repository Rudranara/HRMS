<?php
require 'header.php';
date_default_timezone_set('Asia/Kolkata');
// Get the employee ID from the query string
if (!isset($_GET['employee_id']) || empty($_GET['employee_id'])) {
    die("Employee ID not provided.");
}
$employee_id = intval($_GET['employee_id']);

// Fetch the employee's name and details
$stmt = $conn->prepare("SELECT id, name FROM employees WHERE id = ?");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$employee = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$employee) {
    die("Invalid Employee ID.");
}
// Check attendance status for the employee
$stmt = $conn->prepare("SELECT id, punch_in_time, punch_out_time, location_in, selfie_in FROM attendance WHERE employee_id = ? AND punch_out_time IS NULL LIMIT 1");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$attendance = $stmt->get_result()->fetch_assoc();
$stmt->close();
$is_punched_in = $attendance ? true : false;
// Handle Punch In or Punch Out
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $selfie_data = $_POST['selfie_data'];

    // Decode and save the selfie image
    $target_dir = "../uploads/attendance_selfie/";
    $selfie_image = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $selfie_data));
    $selfie_file = $target_dir . uniqid() . ".jpg";
    file_put_contents($selfie_file, $selfie_image);

    $current_location = $latitude . "," . $longitude;

    if ($action === 'punch_in') {
        // Insert Punch-In record
        $stmt = $conn->prepare("
            INSERT INTO attendance (employee_id, punch_in_time, location_in, selfie_in, current_location, current_location_updated_at) 
            VALUES (?, NOW(), ?, ?, ?, NOW())
        ");
        $stmt->bind_param("isss", $employee_id, $current_location, $selfie_file, $current_location);
        $stmt->execute();
        $stmt->close();

        // Update the employee's current location
        $update_stmt = $conn->prepare("
            UPDATE employees 
            SET current_location = ?, current_location_updated_at = NOW()
            WHERE id = ?
        ");
        $update_stmt->bind_param("si", $current_location, $employee_id);
        $update_stmt->execute();
        $update_stmt->close();

        $message = "Punch In successful!";
    } elseif ($action === 'punch_out' && $is_punched_in) {
        // Calculate working hours and update Punch-Out record
        $punch_in_time = $attendance['punch_in_time'];
        $punch_out_time = date('Y-m-d H:i:s');
        $diff_seconds = strtotime($punch_out_time) - strtotime($punch_in_time);
        $hours = floor($diff_seconds / 3600);
        $minutes = round(($diff_seconds % 3600) / 60);
        $working_hours = $hours + ($minutes / 60);

        $stmt = $conn->prepare("
            UPDATE attendance 
            SET punch_out_time = NOW(), location_out = ?, selfie_out = ?, working_hours = ?, current_location = NULL 
            WHERE id = ?
        ");
        $stmt->bind_param("ssdi", $current_location, $selfie_file, $working_hours, $attendance['id']);
        $stmt->execute();
        $stmt->close();

        // Update the employee's current location
        $update_stmt = $conn->prepare("
            UPDATE employees 
            SET current_location = NULL, current_location_updated_at = NOW()
            WHERE id = ?
        ");
        $update_stmt->bind_param("i", $employee_id);
        $update_stmt->execute();
        $update_stmt->close();

        $message = "Punch Out successful!";
    }

    // Redirect back with success message
    echo "
    <script>
        alert('$message');
        window.location.href = 'add_attendance_admin?employee_id=$employee_id&success=1';
    </script>
    ";
    exit;
}
?>
<!-- End Navbar -->
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
        window.onload = startCamera;
    </script>
<div class="container-fluid py-4">
    <div class="col-md-12 mb-lg-0 mb-4">
        <div class="card mt-4">
            <div class="card-header pb-0 p-3">
                <div class="row">
                    <div class="col-6 d-flex align-items-center">
                        <h6 class="mb-0">Attendance for <?= htmlspecialchars($employee['name']) ?></h6>
                    </div>
                    <div class="col-6 text-end">
                        <a class="btn bg-gradient-dark mb-0" href="javascript:;">EMP-ID -<?= $employee['id'] ?></a>
                    </div>
                </div>
            </div>
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
                        <video id="video" autoplay playsinline></video>
                        <canvas id="canvas"></canvas>
                        <div class="camera-overlay"></div>
                    </div>

                    <div class="mt-4">
                        <?php if ($is_punched_in): ?>
                            <button type="button" class="btn btn-danger btn-lg" onclick="getLocation('punch_out')">
                                <i class="fas fa-sign-out-alt"></i> Punch Out
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-success btn-lg" onclick="getLocation('punch_in')">
                                <i class="fas fa-sign-in-alt"></i> Punch In
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include("footer2.php") ?>

