<?php
include("db_connection.php");
session_start();

/* ===============================
   ADMIN SECURITY CHECK
=============================== */
if (!isset($_SESSION['admin_logged_in'])) {
    die("Unauthorized");
}

/* ===============================
   VALIDATE INPUT
=============================== */
$journey_id = intval($_GET['journey_id'] ?? 0);


if (!$journey_id) {
    die("Invalid Journey ID");
}

/* ===============================
   START TRANSACTION (SAFE DELETE)
=============================== */
$conn->begin_transaction();

try {

    /* 1️⃣ Delete tracking data */
    $stmt1 = $conn->prepare("
        DELETE FROM journey_tracking WHERE journey_id = ?
    ");
    $stmt1->bind_param("i", $journey_id);
    $stmt1->execute();
    $stmt1->close();

    /* 2️⃣ Delete visits */
    $stmt2 = $conn->prepare("
        DELETE FROM visits WHERE journey_id = ?
    ");
    $stmt2->bind_param("i", $journey_id);
    $stmt2->execute();
    $stmt2->close();

    /* 3️⃣ Delete main journey */
    $stmt3 = $conn->prepare("
        DELETE FROM journey_start WHERE id = ?
    ");
    $stmt3->bind_param("i", $journey_id);
    $stmt3->execute();
    $stmt3->close();

    /* ✅ Commit */
    $conn->commit();

} catch (Exception $e) {

    /* ❌ Rollback if error */
    $conn->rollback();
    die("Delete failed: " . $e->getMessage());
}

/* ===============================
   REDIRECT
=============================== */
header("Location: admin_home?msg=journey_deleted");
exit;