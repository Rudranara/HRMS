<?php
include 'db_connection.php'; // Include your database connection file
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $field = $_POST['field']; // Field to validate (e.g., phone, email)
    $value = $_POST['value']; // Value to validate

    // Map the field names to database columns
    $validFields = [
        'phone' => 'phone',
        'email' => 'email',
        'adhar_number' => 'adhar_number',
        'pan_number' => 'pan_number'
    ];

    if (isset($validFields[$field])) {
        $column = $validFields[$field];

        // Query to check for duplicate
        $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM employees WHERE $column = ?");
        $stmt->bind_param("s", $value);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row['count'] > 0) {
            echo 'exists'; // Duplicate found
        } else {
            echo 'available'; // No duplicate
        }
    }
}
?>
