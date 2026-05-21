<?php
include("db_connection.php");

function ensureAutoPunchoutColumn(mysqli $conn): void
{
    $check = $conn->query("SHOW COLUMNS FROM employees LIKE 'disable_auto_punchout'");
    if ($check && $check->num_rows === 0) {
        $conn->query("ALTER TABLE employees ADD COLUMN disable_auto_punchout TINYINT(1) NOT NULL DEFAULT 0");
    }
}

ensureAutoPunchoutColumn($conn);

if (!isset($_POST['employee_id'], $_POST['disable_auto_punchout'])) {
    echo "Missing required data.";
    exit;
}

$employee_id = trim((string) $_POST['employee_id']);
$disable_auto_punchout = (int) $_POST['disable_auto_punchout'] ? 1 : 0;

$stmt = $conn->prepare("UPDATE employees SET disable_auto_punchout = ? WHERE employee_id = ?");
$stmt->bind_param("is", $disable_auto_punchout, $employee_id);

if ($stmt->execute()) {
    echo "Auto punch-out status updated successfully!";
} else {
    echo "Failed to update auto punch-out status.";
}

$stmt->close();
$conn->close();
?>
