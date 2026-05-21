<?php
require 'db_connection.php';
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $attendance_id = $_GET['id'];
    // Retrieve selfie paths for the selected record
    $stmt = $conn->prepare("SELECT selfie_in, selfie_out FROM attendance WHERE id = ?");
    $stmt->bind_param("i", $attendance_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $record = $result->fetch_assoc();
        // Define the directory for selfie files
        $target_dir = "";
        // Delete the selfie_in file if it exists
        if (!empty($record['selfie_in']) && file_exists($target_dir . $record['selfie_in'])) {
            unlink($target_dir . $record['selfie_in']);
        }
        // Delete the selfie_out file if it exists
        if (!empty($record['selfie_out']) && file_exists($target_dir . $record['selfie_out'])) {
            unlink($target_dir . $record['selfie_out']);
        }
        // Delete the attendance record
        $delete_stmt = $conn->prepare("DELETE FROM attendance WHERE id = ?");
        $delete_stmt->bind_param("i", $attendance_id);
        if ($delete_stmt->execute()) {
            // Success: Redirect with a success message
            echo "Attendance record and associated selfies deleted successfully!";
            header("Location: manage_attendance");
            exit();
        } else {
            echo "Error deleting record: " . $delete_stmt->error;
        }
    } else {
        echo "Attendance record not found.";
    }
} else {
    echo "Invalid attendance ID.";
}
?>
