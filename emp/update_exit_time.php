<?php
// update_exit_time.php
include("db_connection.php");
session_start();
header('Content-Type: application/json; charset=utf-8');

date_default_timezone_set('Asia/Kolkata');



try {
    if (!isset($_SESSION['employee_id'])) throw new Exception("Not authenticated");
    $user_id = intval($_SESSION['employee_id']);
    $now = date("Y-m-d H:i:s");

    // Prefer to use last_visit_id stored in session (reliable)
    if (!empty($_SESSION['last_visit_id'])) {
        $visit_id = intval($_SESSION['last_visit_id']);

        $stmt = $conn->prepare("UPDATE visits SET exit_time = ? WHERE visit_id = ? AND user_id = ?");
        $stmt->bind_param("sii", $now, $visit_id, $user_id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

    } else {
        // fallback: update the most recent visit for this user with NULL exit_time
        $stmt = $conn->prepare("UPDATE visits SET exit_time = ? WHERE user_id = ? AND (exit_time IS NULL OR exit_time = '') ORDER BY visit_id DESC LIMIT 1");
        $stmt->bind_param("si", $now, $user_id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
    }

    // clear session locks
    unset($_SESSION['journey_reached']);
    unset($_SESSION['last_visit_id']);

    echo json_encode(["status"=>"success","updated_rows"=>$affected]);
    exit;

} catch (Throwable $e) {
    echo json_encode(["status"=>"error","message"=>"Exception: " . $e->getMessage()]);
    exit;
}
?>
