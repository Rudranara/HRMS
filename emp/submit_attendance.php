<?php
// submit_attendance.php

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if the action was confirmed
    if ($_POST['confirmed'] !== '1') {
        echo "<script>alert('Action not confirmed!'); window.location.href = 'attendance_form';</script>";
        exit;
    }

    // Get submitted data
    $latitude = $_POST['latitude'] ?? '';
    $longitude = $_POST['longitude'] ?? '';
    $action = $_POST['action'] ?? '';
    $selfie_data = $_POST['selfie_data'] ?? '';

    // Validate required fields
    if (empty($latitude) || empty($longitude) || empty($action) || empty($selfie_data)) {
        echo "<script>alert('All fields are required. Please try again.'); window.location.href = 'attendance_form';</script>";
        exit;
    }

    // Save selfie data as an image file
    $selfie_data = str_replace('data:image/jpeg;base64,', '', $selfie_data);
    $selfie_data = str_replace(' ', '+', $selfie_data);
    $selfie_file = 'selfies/' . uniqid() . '.jpg';

    if (!file_put_contents($selfie_file, base64_decode($selfie_data))) {
        echo "<script>alert('Failed to save selfie. Please try again.'); window.location.href = 'attendance_form';</script>";
        exit;
    }

    // Save data to the database (example with placeholder logic)
    // Assuming you have a database connection `$conn` established
    try {
        $conn = new PDO("mysql:host=localhost;dbname=your_database", "username", "password");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->prepare("INSERT INTO attendance (latitude, longitude, action, selfie_file, timestamp) VALUES (:latitude, :longitude, :action, :selfie_file, NOW())");
        $stmt->execute([
            ':latitude' => $latitude,
            ':longitude' => $longitude,
            ':action' => $action,
            ':selfie_file' => $selfie_file,
        ]);

        echo "<script>alert('Attendance recorded successfully!'); window.location.href = 'attendance_form';</script>";
    } catch (PDOException $e) {
        echo "<script>alert('Database error: " . $e->getMessage() . "'); window.location.href = 'attendance_form';</script>";
    }
}
?>