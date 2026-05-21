<?php

if (!function_exists('ensureAttendanceChangeRequestTable')) {
    function ensureAttendanceChangeRequestTable(mysqli $conn): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS attendance_change_requests (
                id INT AUTO_INCREMENT PRIMARY KEY,
                employee_id INT NOT NULL,
                attendance_date DATE NOT NULL,
                requested_status VARCHAR(50) NOT NULL DEFAULT 'Present',
                requested_punch_in TIME NOT NULL,
                requested_punch_out TIME NOT NULL,
                current_status VARCHAR(50) DEFAULT NULL,
                current_punch_in_time DATETIME DEFAULT NULL,
                current_punch_out_time DATETIME DEFAULT NULL,
                reason TEXT DEFAULT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'Pending',
                applied_at DATETIME NOT NULL,
                approved_rejected_at DATETIME DEFAULT NULL,
                approved_by_id INT DEFAULT NULL,
                approved_by_name VARCHAR(255) DEFAULT NULL,
                approved_by_type VARCHAR(50) DEFAULT NULL,
                reject_reason TEXT DEFAULT NULL,
                UNIQUE KEY uniq_employee_date_pending (employee_id, attendance_date, status),
                KEY idx_employee_date (employee_id, attendance_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ";

        $conn->query($sql);

        $columnCheck = $conn->query("SHOW COLUMNS FROM attendance_change_requests LIKE 'requested_status'");
        if ($columnCheck && $columnCheck->num_rows === 0) {
            $conn->query("ALTER TABLE attendance_change_requests ADD COLUMN requested_status VARCHAR(50) NOT NULL DEFAULT 'Present' AFTER attendance_date");
        }
    }
}

if (!function_exists('attendanceDecimalHours')) {
    function attendanceDecimalHours(string $startDateTime, string $endDateTime): float
    {
        $start = strtotime($startDateTime);
        $end = strtotime($endDateTime);

        if ($start === false || $end === false || $end <= $start) {
            return 0.0;
        }

        return round(($end - $start) / 3600, 2);
    }
}

if (!function_exists('fetchAttendanceForDate')) {
    function fetchAttendanceForDate(mysqli $conn, int $employeeId, string $attendanceDate): ?array
    {
        $stmt = $conn->prepare("
            SELECT *
            FROM attendance
            WHERE employee_id = ?
              AND DATE(punch_in_time) = ?
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->bind_param("is", $employeeId, $attendanceDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc() ?: null;
        $stmt->close();

        return $row;
    }
}
