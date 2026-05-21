<?php
require 'header.php';

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
    $selfie_in = $_POST['selfie_in'];
    $selfie_out = $_POST['selfie_out'];
    $working_hours = $_POST['working_hours'];
    $status = $_POST['status'];

    // Update the record in the database
    $update_stmt = $conn->prepare("
        UPDATE attendance 
        SET punch_in_time = ?, punch_out_time = ?, location_in = ?, location_out = ?, 
            current_location = ?, selfie_in = ?, selfie_out = ?, working_hours = ?, status = ? 
        WHERE id = ?
    ");
    $update_stmt->bind_param(
        "sssssssisi",
        $punch_in_time,
        $punch_out_time,
        $location_in,
        $location_out,
        $current_location,
        $selfie_in,
        $selfie_out,
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