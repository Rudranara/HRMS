<?php
require 'header.php';
date_default_timezone_set('Asia/Kolkata');
// Check if an ID is passed via GET
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $attendance_id = $_GET['id'];
    // Fetch the attendance record based on the ID
    $stmt = $conn->prepare("
        SELECT 
            a.id, 
            e.name AS employee_name, 
            e.employee_id, 
            a.punch_in_time, 
            a.punch_out_time, 
            a.location_in, 
            a.location_out, 
            a.current_location, 
            a.selfie_in, 
            a.selfie_out, 
            a.working_hours, 
            a.status 
        FROM attendance a
        JOIN employees e 
        ON a.employee_id = e.id
        WHERE a.id = ?
    ");
    $stmt->bind_param("i", $attendance_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $attendance = $result->fetch_assoc();
    } else {
        echo "Attendance record not found.";
        exit;
    }
} else {
    echo "Invalid attendance ID.";
    exit;
}
// Update the attendance record if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $punch_in_time = $_POST['punch_in_time'];
    $punch_out_time = $_POST['punch_out_time'];
    $location_in = $_POST['location_in'];
    $location_out = $_POST['location_out'];
    $current_location = $_POST['current_location'];
    $working_hours = $_POST['working_hours'];
    $status = $_POST['status'];
    // Decode and save the selfie images
    $target_dir = "../uploads/attendance_selfie/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true); // Ensure the directory exists
    }
    // Process Selfie In
    if (!empty($_POST['selfie_in']) && strpos($_POST['selfie_in'], 'data:image/') === 0) {
        $selfie_in_data = $_POST['selfie_in'];
        $selfie_in_image = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $selfie_in_data));
        $selfie_in_file = uniqid() . ".jpg";
        $selfie_in_path = $target_dir . $selfie_in_file;
        file_put_contents($selfie_in_path, $selfie_in_image);
    } else {
        $selfie_in_path = $attendance['selfie_in']; // Retain old value if not updated
    }

    // Process Selfie Out
    if (!empty($_POST['selfie_out']) && strpos($_POST['selfie_out'], 'data:image/') === 0) {
        $selfie_out_data = $_POST['selfie_out'];
        $selfie_out_image = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $selfie_out_data));
        $selfie_out_file = uniqid() . ".jpg";
        $selfie_out_path = $target_dir . $selfie_out_file;
        file_put_contents($selfie_out_path, $selfie_out_image);
    } else {
        $selfie_out_path = $attendance['selfie_out']; // Retain old value if not updated
    }

    // Update the record in the database
    $update_stmt = $conn->prepare("
        UPDATE attendance 
        SET punch_in_time = ?, punch_out_time = ?, location_in = ?, location_out = ?, 
            current_location = ?, selfie_in = ?, selfie_out = ?, working_hours = ?, status = ? 
        WHERE id = ?
    ");
    $update_stmt->bind_param(
        "sssssssdsi",
        $punch_in_time,
        $punch_out_time,
        $location_in,
        $location_out,
        $current_location,
        $selfie_in_path,
        $selfie_out_path,
        $working_hours,
        $status,
        $attendance_id
    );

    if ($update_stmt->execute()) {
        echo "<script>alert('Attendance record updated successfully!'); window.location.href = 'manage_attendance';</script>";
    } else {
        echo "Error updating record: " . $update_stmt->error;
    }
}
?>
<style>
.edit-attendance-page {
    padding-top: 2rem;
    padding-bottom: 2.5rem;
}

.edit-attendance-shell,
.edit-attendance-modal .modal-content {
    border: 1px solid #e5eaf1;
    border-radius: 26px;
    background: #ffffff;
    box-shadow: 0 28px 60px rgba(15, 23, 42, 0.07);
}

.edit-attendance-shell .card-body {
    padding: 1.5rem;
}

.edit-attendance-title {
    margin: 0 0 1.35rem;
    color: #0f172a;
    font-size: 1.45rem;
    font-weight: 800;
    letter-spacing: -0.03em;
}

.edit-attendance-form .row {
    --bs-gutter-x: 1rem;
    --bs-gutter-y: 1rem;
}

.edit-attendance-field {
    margin-bottom: 0.1rem;
}

.edit-attendance-field .form-label {
    display: block;
    margin-bottom: 0.45rem;
    color: #64748b;
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.09em;
    text-transform: uppercase;
}

.edit-attendance-field .form-control,
.edit-attendance-field .form-select {
    min-height: 46px;
    border: 1px solid #d7deea;
    border-radius: 14px;
    padding: 0.7rem 0.9rem;
    color: #0f172a;
    background: #f8fafc;
    box-shadow: none;
    font-size: 0.92rem;
}

.edit-attendance-field .form-control:focus,
.edit-attendance-field .form-select:focus {
    border-color: #9aa8bc;
    background: #ffffff;
    box-shadow: 0 0 0 0.2rem rgba(148, 163, 184, 0.16);
}

.edit-attendance-field .form-control[disabled] {
    background: #f4f7fb;
    color: #475569;
    opacity: 1;
}

.edit-attendance-capture-btn,
.edit-attendance-submit-btn,
.edit-attendance-modal .btn-primary,
.edit-attendance-modal .btn-secondary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 46px;
    border-radius: 14px;
    font-size: 0.78rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.edit-attendance-capture-btn,
.edit-attendance-submit-btn,
.edit-attendance-modal .btn-primary {
    background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
    border: 1px solid #111827;
    color: #ffffff;
    box-shadow: 0 18px 32px rgba(15, 23, 42, 0.16);
}

.edit-attendance-modal .btn-secondary {
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    border: 1px solid #d7deea;
    color: #475569;
}

.edit-attendance-preview {
    margin-top: 0.75rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 112px;
    min-width: 112px;
    padding: 0.28rem;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    background: #f8fafc;
}

.edit-attendance-preview img {
    width: 112px;
    height: 112px;
    object-fit: cover;
    border-radius: 12px;
    margin-top: 0 !important;
}

.edit-attendance-submit-row {
    margin-top: 0.35rem;
}

.edit-attendance-modal .modal-dialog {
    max-width: 860px;
}

.edit-attendance-modal .modal-content {
    overflow: hidden;
    box-shadow: 0 32px 70px rgba(15, 23, 42, 0.18);
}

.edit-attendance-modal .modal-header,
.edit-attendance-modal .modal-footer {
    padding: 1.15rem 1.35rem;
    border-color: #e9eef5;
}

.edit-attendance-modal .modal-body {
    padding: 1.25rem 1.35rem 1.35rem;
}

.edit-attendance-modal .modal-title {
    color: #0f172a;
    font-size: 1.15rem;
    font-weight: 800;
    letter-spacing: -0.02em;
}

#camera-container {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 420px;
    border: 1px solid #e5eaf1;
    border-radius: 22px;
    background: radial-gradient(circle at top, rgba(148, 163, 184, 0.12), transparent 40%), #f8fafc;
    overflow: hidden;
}

#videoIn,
#videoOut {
    width: 100%;
    max-height: 520px;
    object-fit: cover;
    border-radius: 18px;
}

@media (max-width: 767.98px) {
    .edit-attendance-page {
        padding-top: 1.25rem;
    }

    .edit-attendance-shell .card-body,
    .edit-attendance-modal .modal-header,
    .edit-attendance-modal .modal-body,
    .edit-attendance-modal .modal-footer {
        padding-left: 1rem;
        padding-right: 1rem;
    }

    #camera-container {
        min-height: 300px;
    }
}
</style>

<div class="container-fluid edit-attendance-page">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4 edit-attendance-shell">
                <div class="card-body">
                    <h5 class="edit-attendance-title">Edit Attendance Record</h5>
                    <form method="POST" class="edit-attendance-form">
                        <div class="row">
                            <div class="col-6 edit-attendance-field">
                                <label for="employee_name" class="form-label">Employee Name</label>
                                <input type="text" class="form-control" id="employee_name" value="<?= $attendance['employee_name'] ?>" disabled>
                            </div>
                            <div class="col-6 edit-attendance-field">
                                <label for="punch_in_time" class="form-label">Punch In Time</label>
                                <input type="datetime-local" class="form-control" id="punch_in_time" name="punch_in_time" value="<?= date('Y-m-d\TH:i:s', strtotime($attendance['punch_in_time'])) ?>" required>
                            </div>
                            <div class="col-6 edit-attendance-field">
                                <label for="punch_out_time" class="form-label">Punch Out Time</label>
                                <input type="datetime-local" class="form-control" id="punch_out_time" name="punch_out_time" value="<?= date('Y-m-d\TH:i:s', strtotime($attendance['punch_out_time'])) ?>" required>
                            </div>
                            <div class="col-6 edit-attendance-field">
                                <label for="location_in" class="form-label">Location In</label>
                                <input type="text" class="form-control" id="location_in" name="location_in" value="<?= $attendance['location_in'] ?>" >
                            </div>
                            <div class="col-6 edit-attendance-field">
                                <label for="location_out" class="form-label">Location Out</label>
                                <input type="text" class="form-control" id="location_out" name="location_out" value="<?= $attendance['location_out'] ?>" >
                            </div>
                            <div class="col-6 edit-attendance-field">
                                <label for="current_location" class="form-label">Current Location</label>
                                <input type="text" class="form-control" id="current_location" name="current_location" value="<?= $attendance['current_location'] ?>">
                            </div>
                            <div class="col-6 edit-attendance-field">
                                <label for="selfie_in" class="form-label">Selfie In</label>
                                <button type="button" class="btn btn-primary w-100 edit-attendance-capture-btn" data-bs-toggle="modal" data-bs-target="#selfieInModal">Take Selfie In</button>
                                <input type="hidden" id="selfie_in" name="selfie_in" value="<?= $attendance['selfie_in'] ?>">
                                <?php if (!empty($attendance['selfie_in'])): ?>
                                    <div class="edit-attendance-preview">
                                        <img src="<?= $attendance['selfie_in'] ?>" alt="Selfie In" class="img-thumbnail mt-2" width="150">
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-6 edit-attendance-field">
                                <label for="selfie_out" class="form-label">Selfie Out</label>
                                <button type="button" class="btn btn-primary w-100 edit-attendance-capture-btn" data-bs-toggle="modal" data-bs-target="#selfieOutModal">Take Selfie Out</button>
                                <input type="hidden" id="selfie_out" name="selfie_out" value="<?= $attendance['selfie_out'] ?>">
                                <?php if (!empty($attendance['selfie_out'])): ?>
                                    <div class="edit-attendance-preview">
                                        <img src="<?= $attendance['selfie_out'] ?>" alt="Selfie Out" class="img-thumbnail mt-2" width="150">
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="col-6 edit-attendance-field">
                                <label for="working_hours" class="form-label">Working Hours</label>
                                <input type="decimal" class="form-control" id="working_hours" name="working_hours" value="<?= $attendance['working_hours'] ?>" required>
                            </div>
                            <div class="col-6 edit-attendance-field">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-control" id="status" name="status" required>
                                    <option value="Present" <?= $attendance['status'] == 'Present' ? 'selected' : '' ?>>Present</option>
                                    <option value="Absent" <?= $attendance['status'] == 'Absent' ? 'selected' : '' ?>>Absent</option>
                                    <option value="On Leave" <?= $attendance['status'] == 'On Leave' ? 'selected' : '' ?>>On Leave</option>
                                    <option value="Weekly Off" <?= $attendance['status'] == 'Weekly Off' ? 'selected' : '' ?>>Weekly Off</option>
                                    <option value="Holiday" <?= $attendance['status'] == 'Holiday' ? 'selected' : '' ?>>Holiday</option>
                                   
                                </select>
                            </div>
                            <div class="col-6 mt-4 edit-attendance-submit-row">
                                <button type="submit" class="btn btn-primary edit-attendance-submit-btn">Update Attendance</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>



<!-- Selfie In Modal -->
<div class="modal fade edit-attendance-modal" id="selfieInModal" tabindex="-1" aria-labelledby="selfieInModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="selfieInModalLabel">Take Selfie In</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="camera-container">
                    <video id="videoIn" autoplay playsinline></video>
                    <canvas id="canvasIn" style="display: none;"></canvas>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="captureIn">Capture</button>
            </div>
        </div>
    </div>
</div>

<!-- Selfie Out Modal -->
<div class="modal fade edit-attendance-modal" id="selfieOutModal" tabindex="-1" aria-labelledby="selfieOutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="selfieOutModalLabel">Take Selfie Out</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="camera-container">
                    <video id="videoOut" autoplay playsinline></video>
                    <canvas id="canvasOut" style="display: none;"></canvas>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="captureOut">Capture</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Function to calculate working hours
    function calculateWorkingHours() {
        const punchIn = document.getElementById('punch_in_time').value;
        const punchOut = document.getElementById('punch_out_time').value;

        if (punchIn && punchOut) {
            const punchInTime = new Date(punchIn);
            const punchOutTime = new Date(punchOut);

            if (punchOutTime > punchInTime) {
                // Calculate the difference in hours as a decimal
                const diffInHours = (punchOutTime - punchInTime) / (1000 * 60 * 60);
                const formattedHours = diffInHours.toFixed(2); // Format to 2 decimal places

                document.getElementById('working_hours').value = formattedHours;
            } else {
                alert("Punch-out time must be after Punch-in time.");
                document.getElementById('working_hours').value = '';
            }
        }
    }

    // Attach event listeners to the datetime-local fields
    document.getElementById('punch_in_time').addEventListener('change', calculateWorkingHours);
    document.getElementById('punch_out_time').addEventListener('change', calculateWorkingHours);
</script>

<script>
    // Initialize the camera for both modals
function initializeCamera(videoElement) {
    navigator.mediaDevices
        .getUserMedia({ video: true })
        .then((stream) => {
            videoElement.srcObject = stream;
        })
        .catch((err) => {
            console.error("Error accessing the camera: ", err);
            alert("Unable to access the camera.");
        });
}

// Capture logic for Selfie In
document.getElementById("selfieInModal").addEventListener("shown.bs.modal", () => {
    initializeCamera(document.getElementById("videoIn"));
});
document.getElementById("captureIn").addEventListener("click", () => {
    const video = document.getElementById("videoIn");
    const canvas = document.getElementById("canvasIn");
    const selfieInInput = document.getElementById("selfie_in");

    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext("2d").drawImage(video, 0, 0);
    const imageData = canvas.toDataURL("image/png");
    selfieInInput.value = imageData;

    alert("Selfie In captured successfully!");
    document.getElementById("selfieInModal").click();
});

// Capture logic for Selfie Out
document.getElementById("selfieOutModal").addEventListener("shown.bs.modal", () => {
    initializeCamera(document.getElementById("videoOut"));
});
document.getElementById("captureOut").addEventListener("click", () => {
    const video = document.getElementById("videoOut");
    const canvas = document.getElementById("canvasOut");
    const selfieOutInput = document.getElementById("selfie_out");

    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext("2d").drawImage(video, 0, 0);
    const imageData = canvas.toDataURL("image/png");
    selfieOutInput.value = imageData;

    alert("Selfie Out captured successfully!");
    document.getElementById("selfieOutModal").click();
});

</script>
<?php include("footer.php") ?>