<?php
function get_attendance_stats($conn, $employee_id, $year, $month) {
    $stats = [];

    // Fetch employee info
    $emp_query = $conn->prepare("SELECT * FROM employees WHERE id = ?");
    $emp_query->bind_param("i", $employee_id);
    $emp_query->execute();
    $employee = $emp_query->get_result()->fetch_assoc();

    if (!$employee) return ['error' => 'Employee not found'];

    $punchin_time = date('H:i:s', strtotime($employee['punchin_time']));
    $punchout_time = date('H:i:s', strtotime($employee['punchout_time']));
    $working_hours_per_day = $employee['working_hours'];
    $net_salary = $employee['net_salary'];

    // Get attendance records
    $att_query = $conn->prepare("SELECT * FROM attendance WHERE employee_id = ? AND YEAR(punch_in_time) = ? AND MONTH(punch_in_time) = ?");
    $att_query->bind_param("iss", $employee_id, $year, $month);
    $att_query->execute();
    $attendance = $att_query->get_result()->fetch_all(MYSQLI_ASSOC);

    // Get holidays
    $holiday_query = $conn->prepare("SELECT start_date FROM events WHERE YEAR(start_date) = ? AND MONTH(start_date) = ?");
    $holiday_query->bind_param("ii", $year, $month);
    $holiday_query->execute();
    $holidays = $holiday_query->get_result()->fetch_all(MYSQLI_ASSOC);
    $holiday_dates = array_column($holidays, 'start_date');

    // Calculate total working days excluding Sundays and holidays
    $total_days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $total_working_days = 0;
    for ($day = 1; $day <= $total_days_in_month; $day++) {
        $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
        $day_of_week = date('N', strtotime($date)); // 1=Mon to 7=Sun
        if ($day_of_week < 7 && !in_array($date, $holiday_dates)) {
            $total_working_days++;
        }
    }

    // Initialize counters
    $total_present = $total_absent = $total_on_leave = $total_late = $total_early = $total_working_hours = 0;
    $working_days = $absent_days = $leave_days = 0;

    foreach ($attendance as $record) {
        $punch_in = date('H:i:s', strtotime($record['punch_in_time']));
        $punch_out = date('H:i:s', strtotime($record['punch_out_time']));
        $status = $record['status'];

        if ($status == 'Present') $total_present++;
        if ($status == 'Absent') {
            $total_absent++;
            $absent_days++;
        }
        if ($status == 'On Leave') {
            $total_on_leave++;
            $leave_days++;
        }

        if ($punch_in > $punchin_time) $total_late++;
        if ($punch_out < $punchout_time) $total_early++;

        $total_working_hours += floatval($record['working_hours']);
        $working_days++; // Count all attendance records as working days
    }

    // Leave Summary
    $leave_query = $conn->prepare("SELECT status, COUNT(*) AS count FROM leave_requests WHERE employee_id = ? AND YEAR(leave_apply_date) = ? AND MONTH(leave_apply_date) = ? GROUP BY status");
    $leave_query->bind_param("iii", $employee_id, $year, $month);
    $leave_query->execute();
    $leave_result = $leave_query->get_result();

    $total_approved = $total_pending = $total_rejected = 0;
    while ($row = $leave_result->fetch_assoc()) {
        if ($row['status'] === 'Approved') $total_approved = $row['count'];
        elseif ($row['status'] === 'Pending') $total_pending = $row['count'];
        elseif ($row['status'] === 'Rejected') $total_rejected = $row['count'];
    }

    // Salary and time difference calculation
    $per_day_salary = $total_working_days > 0 ? ($net_salary / $total_working_days) : 0;
    $hourly_salary = $working_hours_per_day > 0 ? ($per_day_salary / $working_hours_per_day) : 0;
    $expected_working_hours = $total_working_days * $working_hours_per_day;
    $working_hour_difference = $total_working_hours - $expected_working_hours;
    $ot_or_time_lost_amount = $working_hour_difference * $hourly_salary;
    $difference_type = $working_hour_difference > 0 ? 'Overtime' : ($working_hour_difference < 0 ? 'Time Lost' : 'Exact Hours');
    if ($difference_type === 'Exact Hours') $ot_or_time_lost_amount = 0;

    $calculated_salary = $total_working_hours * $hourly_salary;

    return [
        'total_working_days' => $total_working_days,
        'working_days' => $working_days,
        'absent_days' => $absent_days,
        'leave_days' => $leave_days,
        'present_days' => $total_present,
        'on_leave_days' => $total_on_leave,
        'late_days' => $total_late,
        'early_out_days' => $total_early,
        'total_working_hours' => $total_working_hours,
        'expected_working_hours' => $expected_working_hours,
        'per_day_salary' => round($per_day_salary, 2),
        'hourly_salary' => round($hourly_salary, 2),
        'calculated_salary' => round($calculated_salary, 2),
        'difference_type' => $difference_type,
        'ot_or_time_lost_amount' => round($ot_or_time_lost_amount, 2),
        'leave_approved' => $total_approved,
        'leave_pending' => $total_pending,
        'leave_rejected' => $total_rejected
    ];
}
?>
