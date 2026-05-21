<?php
require 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ids'])) {
    $ids = $_POST['ids'];

    if (!empty($ids)) {
        // Prepare placeholders for the IN clause
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        // Retrieve selfie paths for the selected records
        $query = "SELECT selfie_in, selfie_out FROM attendance WHERE id IN ($placeholders)";
        $stmt = $conn->prepare($query);

        // Bind the IDs dynamically
        $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
        $stmt->execute();
        $result = $stmt->get_result();

        // Define the directory for selfie files
        $target_dir = "";

        // Loop through the records and delete the files
        while ($record = $result->fetch_assoc()) {
            // Delete the selfie_in file if it exists
            if (!empty($record['selfie_in']) && file_exists($target_dir . $record['selfie_in'])) {
                unlink($target_dir . $record['selfie_in']);
            }
            // Delete the selfie_out file if it exists
            if (!empty($record['selfie_out']) && file_exists($target_dir . $record['selfie_out'])) {
                unlink($target_dir . $record['selfie_out']);
            }
        }
        // Delete the attendance records
        $delete_query = "DELETE FROM attendance WHERE id IN ($placeholders)";
        $delete_stmt = $conn->prepare($delete_query);

        // Bind the IDs dynamically again
        $delete_stmt->bind_param(str_repeat('i', count($ids)), ...$ids);

        if ($delete_stmt->execute()) {
            // Success: Redirect with a success message
            echo "Selected attendance records and associated selfies deleted successfully!";
            header("Location: attendance_record");
            exit();
        } else {
            echo "Error deleting records: " . $delete_stmt->error;
        }
    } else {
        echo "No records selected.";
    }
} else {
    echo "Invalid request.";
}
?>
