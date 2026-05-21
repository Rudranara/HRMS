<?php
session_start();
require 'db_connection.php';

header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 1);

$data = [];

function isValidCoordinatePair($lat, $lng)
{
    return $lat !== null && $lng !== null && $lat !== '' && $lng !== '';
}

function buildLocationString($lat, $lng)
{
    if (!isValidCoordinatePair($lat, $lng)) {
        return null;
    }

    return trim($lat) . ',' . trim($lng);
}

$attendanceSql = "
    SELECT
        e.id AS employee_id,
        e.name,
        a.id AS attendance_id,
        a.punch_in_time,
        a.punch_out_time,
        a.location_in,
        a.location_out,
        a.current_location
    FROM employees e
    INNER JOIN attendance a
        ON a.employee_id = e.id
    INNER JOIN (
        SELECT employee_id, MAX(punch_in_time) AS latest_punch_in
        FROM attendance
        WHERE DATE(punch_in_time) = CURDATE()
           OR DATE(punch_out_time) = CURDATE()
           OR punch_out_time IS NULL
        GROUP BY employee_id
    ) latest_attendance
        ON latest_attendance.employee_id = a.employee_id
       AND latest_attendance.latest_punch_in = a.punch_in_time
    ORDER BY a.punch_in_time DESC
";

$attendanceResult = mysqli_query($conn, $attendanceSql);

if (!$attendanceResult) {
    echo json_encode([
        'error' => mysqli_error($conn)
    ]);
    exit;
}

$journeyStmt = $conn->prepare(
    "SELECT id, start_time, start_lat, start_lng, end_time, end_lat, end_lng, status
     FROM journey_start
     WHERE user_id = ?
       AND DATE(start_time) = CURDATE()
       AND start_time >= ?
     ORDER BY start_time DESC, id DESC
     LIMIT 1"
);

$visitStmt = $conn->prepare(
    "SELECT lat, lng, created_at
     FROM visits
     WHERE journey_id = ?
    ORDER BY created_at DESC, visit_id DESC
     LIMIT 1"
);

if (!$journeyStmt || !$visitStmt) {
    echo json_encode([
        'error' => mysqli_error($conn)
    ]);
    exit;
}

while ($attendance = mysqli_fetch_assoc($attendanceResult)) {
    $selectedLocation = $attendance['current_location'] ?: $attendance['location_in'];
    $selectedStage = 'punch_in';
    $selectedTime = $attendance['punch_in_time'];

    $journey = null;
    $journeyStmt->bind_param('is', $attendance['employee_id'], $attendance['punch_in_time']);
    $journeyStmt->execute();
    $journeyResult = $journeyStmt->get_result();

    if ($journeyResult) {
        while ($candidate = $journeyResult->fetch_assoc()) {
            if (
                !empty($attendance['punch_out_time']) &&
                !empty($candidate['start_time']) &&
                strtotime($candidate['start_time']) > strtotime($attendance['punch_out_time'])
            ) {
                continue;
            }

            $journey = $candidate;
            break;
        }
    }

    if ($journey) {
        $journeyStartLocation = buildLocationString($journey['start_lat'], $journey['start_lng']);
        if ($journeyStartLocation) {
            $selectedLocation = $journeyStartLocation;
            $selectedStage = 'journey_start';
            $selectedTime = $journey['start_time'];
        }

        $visitStmt->bind_param('i', $journey['id']);
        $visitStmt->execute();
        $visitResult = $visitStmt->get_result();
        $visit = $visitResult ? $visitResult->fetch_assoc() : null;

        if ($visit) {
            $visitLocation = buildLocationString($visit['lat'], $visit['lng']);
            if ($visitLocation) {
                $selectedLocation = $visitLocation;
                $selectedStage = 'visit';
                $selectedTime = $visit['created_at'];
            }
        }

        $journeyEndLocation = buildLocationString($journey['end_lat'], $journey['end_lng']);
        if (!empty($journey['end_time']) && $journeyEndLocation) {
            $selectedLocation = $journeyEndLocation;
            $selectedStage = 'journey_end';
            $selectedTime = $journey['end_time'];
        }
    }

    if (!empty($attendance['punch_out_time']) && !empty($attendance['location_out'])) {
        $selectedLocation = $attendance['location_out'];
        $selectedStage = 'punch_out';
        $selectedTime = $attendance['punch_out_time'];
    }

    if (!$selectedLocation) {
        continue;
    }

    $data[] = [
        'id' => $attendance['employee_id'],
        'name' => $attendance['name'],
        'current_location' => $selectedLocation,
        'tracking_stage' => $selectedStage,
        'tracking_time' => $selectedTime
    ];
}

$journeyStmt->close();
$visitStmt->close();

echo json_encode($data);