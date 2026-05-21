<?php
// Include your database connection file
include("db_connection.php"); // Replace with the actual file where your DB connection is defined

// Check if the 'id' parameter is provided in the GET request
if (isset($_GET['id'])) {
    $leave_id = intval($_GET['id']); // Sanitize the input

    // Prepare the SQL statement to fetch leave details
    $stmt = $conn->prepare("SELECT lr.*, e.name AS employee_name 
                            FROM leave_requests lr 
                            JOIN employees e ON lr.employee_id = e.id 
                            WHERE lr.id = ?");
    $stmt->bind_param("i", $leave_id);

    // Execute the statement
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if a record is found
    if ($result->num_rows > 0) {
        $leave_details = $result->fetch_assoc();

        // Return the leave details as a JSON response
        echo json_encode([
            'status' => 'success',
            'leave_type' => $leave_details['leave_type'],
            'leave_reason' => $leave_details['leave_reason'],
            'start_date' => $leave_details['start_date'],
            'end_date' => $leave_details['end_date'],
            'extra_time_from' => $leave_details['extra_time_from'],
            'extra_time_to' => $leave_details['extra_time_to'],
            'leave_apply_date' => $leave_details['leave_apply_date'],
            'leave_approve_reject_date' => $leave_details['leave_approve_reject_date'],
            'reject_reason' => $leave_details['reject_reason'],
            'actual_days' => $leave_details['actual_days'],
            'approved_by_id' => $leave_details['approved_by_id'],
            'approved_by_name' => $leave_details['approved_by_name'],
            'approved_by_type' => $leave_details['approved_by_type'],
            'supporting_document' => $leave_details['supporting_document'], // Ensure this field exists in the database
            'status' => $leave_details['status']
        ]);
    } else {
        // No record found for the given ID
        echo json_encode([
            'status' => 'error',
            'message' => 'Leave request not found.'
        ]);
    }
} else {
    // 'id' parameter is missing in the request
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request. ID parameter is required.'
    ]);
}

// Close the database connection
$conn->close();
?>
