<?php
require_once __DIR__ . '/../includes/advance_salary_request_helper.php';

function get_attendance_stats($conn, $employee_id, $year, $month) {
    $stats = [];

    // Fetch employee info
    $emp_query = $conn->prepare("SELECT * FROM employees WHERE id = ?");
    $emp_query->bind_param("i", $employee_id);
    $emp_query->execute();
    $employee = $emp_query->get_result()->fetch_assoc();
    if (!$employee) return ['error' => 'Employee not found'];

    // Extract working details
    $working_hours_per_day = $employee['working_hours'];
    $punchin_time = date('H:i:s', strtotime($employee['punchin_time']));
    $punchout_time = date('H:i:s', strtotime($employee['punchout_time']));

    // Extract all salary components
    $salary_fields = [
        'basic', 'da', 'hra', 'net_salary', 'conveyance', 'special_allowance',
        'performance_bonus', 'medical_allowance', 'washing_allowance', 'canteen_allowance',
        'other_allowances', 'gross_salary', 'epf_employer', 'esic_employer', 'gmc', 'retention_bonus',
        'leave_encashment', 'gratuity', 'total_ctc', 'epf_employee', 'esic_employee', 'professional_tax',
        'income_tax', 'insurance_premium', 'advance', 'other_deductions', 'total_deductions', 'total_leave'
    ];

    $salaries = [];
    foreach ($salary_fields as $field) {
        $salaries[$field] = isset($employee[$field]) ? floatval($employee[$field]) : 0;
    }

    $salaries['advance'] = getEffectiveAdvanceSalaryAmount(
        $conn,
        (int) $employee_id,
        (int) $year,
        (int) $month,
        (float) ($employee['advance'] ?? 0)
    );

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

    // Calculate total working days (excluding Sundays and holidays)
    $total_days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $total_working_days = 0;
    for ($day = 1; $day <= $total_days_in_month; $day++) {
        $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
        $day_of_week = date('N', strtotime($date));
        if ($day_of_week < 7 && !in_array($date, $holiday_dates)) {
            $total_working_days++;
        }
    }

    // Initialize counters
    $present_days = $absent_days = $leave_days = $late_days = $early_out_days = $total_working_hours = 0;

    foreach ($attendance as $record) {
        $status = $record['status'];
        $punch_in = date('H:i:s', strtotime($record['punch_in_time']));
        $punch_out = date('H:i:s', strtotime($record['punch_out_time']));
        $total_working_hours += floatval($record['working_hours']);

        if ($status == 'Present') $present_days++;
        elseif ($status == 'Absent') $absent_days++;
        elseif ($status == 'On Leave') $leave_days++;

        if ($punch_in > $punchin_time) $late_days++;
        if ($punch_out < $punchout_time) $early_out_days++;
    }

    // Leave summary
    $leave_query = $conn->prepare("
        SELECT status, SUM(actual_days) AS total_days 
        FROM leave_requests 
        WHERE employee_id = ? 
        AND YEAR(start_date) = ? 
        AND MONTH(start_date) = ? 
        GROUP BY status
    ");
    
    $leave_query->bind_param("iii", $employee_id, $year, $month);
    $leave_query->execute();
    $leave_result = $leave_query->get_result();
    $leave_summary = ['Approved' => 0, 'Pending' => 0, 'Rejected' => 0];

    while ($row = $leave_result->fetch_assoc()) {
        $leave_summary[$row['status']] = $row['total_days'] ?? 0;
    }

    $payable_days = $present_days + (float) $leave_summary['Approved'];

    // Compute per-day and calculated salary values
    $result = [
        'total_working_days' => $total_working_days,
        'present_days' => $present_days,
        'payable_days' => $payable_days,
        'absent_days' => $absent_days,
        'leave_days' => $leave_days,
        'late_days' => $late_days,
        'early_out_days' => $early_out_days,
        'total_working_hours' => round($total_working_hours, 2),
        'expected_working_hours' => round($total_working_days * $working_hours_per_day, 2),
        'leave_approved' => $leave_summary['Approved'],
        'leave_pending' => $leave_summary['Pending'],
        'leave_rejected' => $leave_summary['Rejected'],
        'include_epf' => !empty($employee['include_epf']) ? 1 : 0,
        'include_pf_ceiling' => !empty($employee['include_pf_ceiling']) ? 1 : 0,
        'include_pt' => !empty($employee['include_pt']) ? 1 : 0,
        'difference_type' => '', // Optional: set logic for overtime/shortage
        'ot_or_time_lost_amount' => 0 // Optional: calculate if needed
    ];

    // Add original, per-day, and calculated salary fields
    foreach ($salaries as $key => $amount) {
        $per_day = $total_working_days > 0 ? ($amount / $total_working_days) : 0;
        $calculated = $payable_days * $per_day;
        $result[$key] = round($amount, 2);
        $result["per_day_$key"] = round($per_day, 2);
        $result["calculated_$key"] = round($calculated, 2);
    }

    return $result;
}
?>
