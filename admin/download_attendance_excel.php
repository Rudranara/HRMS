<?php
require 'db_connection.php';
date_default_timezone_set('Asia/Kolkata');

$selected_employee = isset($_GET['employee_id']) ? (int) $_GET['employee_id'] : 0;
$selected_month = isset($_GET['month']) ? (int) $_GET['month'] : (int) date('m');
$selected_year = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');
$selected_office = isset($_GET['office']) ? $_GET['office'] : '';
$decoded_office = $selected_office !== '' ? urldecode($selected_office) : '';

if ($selected_month < 1 || $selected_month > 12) {
    $selected_month = (int) date('m');
}

if ($selected_year < 2000 || $selected_year > 2100) {
    $selected_year = (int) date('Y');
}

$filter_start = sprintf('%04d-%02d-01', $selected_year, $selected_month);
$filter_end_date = date('Y-m-t', strtotime($filter_start));
$days_in_month = (int) date('t', strtotime($filter_start));
$today_date = date('Y-m-d');

$employee_query = "SELECT id, employee_id, name, office FROM employees WHERE 1=1";
$employee_params = [];
$employee_types = "";

if ($selected_employee > 0) {
    $employee_query .= " AND id = ?";
    $employee_params[] = $selected_employee;
    $employee_types .= "i";
}

if ($decoded_office !== '') {
    $employee_query .= " AND office = ?";
    $employee_params[] = $decoded_office;
    $employee_types .= "s";
}

$employee_query .= " ORDER BY name ASC";
$employees_stmt = $conn->prepare($employee_query);
if (!empty($employee_params)) {
    $employees_stmt->bind_param($employee_types, ...$employee_params);
}
$employees_stmt->execute();
$employees = $employees_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$employees_stmt->close();

$employee_ids = array_map(static fn($employee) => (int) $employee['id'], $employees);
$attendance_by_employee = [];
$leave_by_employee = [];
$event_by_date = [];

function buildInClause(array $ids): string
{
    return implode(',', array_fill(0, count($ids), '?'));
}

if (!empty($employee_ids)) {
    $placeholders = buildInClause($employee_ids);
    $attendance_sql = "
        SELECT employee_id, DATE(COALESCE(punch_in_time, punch_out_time)) AS attendance_day, status
        FROM attendance
        WHERE employee_id IN ($placeholders)
          AND DATE(COALESCE(punch_in_time, punch_out_time)) BETWEEN ? AND ?
        ORDER BY COALESCE(punch_in_time, punch_out_time) ASC, id ASC
    ";
    $attendance_types = str_repeat('i', count($employee_ids)) . "ss";
    $attendance_params = array_merge($employee_ids, [$filter_start, $filter_end_date]);
    $attendance_stmt = $conn->prepare($attendance_sql);
    $attendance_stmt->bind_param($attendance_types, ...$attendance_params);
    $attendance_stmt->execute();
    $attendance_result = $attendance_stmt->get_result();
    while ($row = $attendance_result->fetch_assoc()) {
        if ($row['attendance_day'] !== null) {
            $attendance_by_employee[(int) $row['employee_id']][$row['attendance_day']] = $row['status'];
        }
    }
    $attendance_stmt->close();

    $leave_sql = "
        SELECT employee_id, start_date, end_date
        FROM leave_requests
        WHERE employee_id IN ($placeholders)
          AND status = 'Approved'
          AND start_date <= ?
          AND end_date >= ?
    ";
    $leave_types = str_repeat('i', count($employee_ids)) . "ss";
    $leave_params = array_merge($employee_ids, [$filter_end_date, $filter_start]);
    $leave_stmt = $conn->prepare($leave_sql);
    $leave_stmt->bind_param($leave_types, ...$leave_params);
    $leave_stmt->execute();
    $leave_result = $leave_stmt->get_result();
    while ($row = $leave_result->fetch_assoc()) {
        $leave_start = max(strtotime($row['start_date']), strtotime($filter_start));
        $leave_end = min(strtotime($row['end_date']), strtotime($filter_end_date));
        for ($time = $leave_start; $time <= $leave_end; $time = strtotime('+1 day', $time)) {
            $leave_by_employee[(int) $row['employee_id']][date('Y-m-d', $time)] = true;
        }
    }
    $leave_stmt->close();
}

$event_stmt = $conn->prepare("
    SELECT start_date, event_type
    FROM events
    WHERE start_date BETWEEN ? AND ?
");
$event_stmt->bind_param("ss", $filter_start, $filter_end_date);
$event_stmt->execute();
$event_result = $event_stmt->get_result();
while ($row = $event_result->fetch_assoc()) {
    $event_by_date[$row['start_date']] = $row['event_type'];
}
$event_stmt->close();

function statusCode(string $status): string
{
    $normalized = strtolower(trim(str_replace('_', ' ', $status)));
    return match ($normalized) {
        'present' => 'P',
        'absent' => 'A',
        'on leave', 'leave' => 'L',
        'holiday' => 'H',
        'weekly off', 'week off', 'weekoff', 'weakoff' => 'WO',
        default => strtoupper($status),
    };
}

function statusClass(string $code): string
{
    return match ($code) {
        'P' => 'present',
        'L' => 'leave',
        'H' => 'holiday',
        'WO' => 'weekoff',
        default => 'absent',
    };
}

function statusStyle(string $code): string
{
    $cell_style = 'width:16px;mso-width-source:userset;mso-width-alt:585;height:9px;mso-height-source:userset;line-height:8px;padding:0;border:1px solid #000;text-align:center;vertical-align:middle;white-space:nowrap;font-size:7px;';

    return match ($code) {
        'P' => $cell_style . 'background-color:#ffffff;color:#000000;',
        'L' => $cell_style . 'background-color:#ffff00;color:#000000;',
        'H' => $cell_style . 'background-color:#d9e1f2;color:#1f4e79;',
        'WO' => $cell_style . 'background-color:#92d050;color:#006100;',
        default => $cell_style . 'background-color:#ffc7ce;color:#9c0006;',
    };
}

function statusBg(string $code): string
{
    return match ($code) {
        'P' => '#ffffff',
        'L' => '#ffff00',
        'H' => '#d9e1f2',
        'WO' => '#92d050',
        default => '#ffc7ce',
    };
}

function excelStyleId(string $code): string
{
    return match ($code) {
        '' => 'statusBlank',
        'P' => 'statusPresent',
        'L' => 'statusLeave',
        'H' => 'statusHoliday',
        'WO' => 'statusWeekoff',
        default => 'statusAbsent',
    };
}

function xmlText(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

$month_name = date('F', strtotime($filter_start));
$filename = "attendance_matrix_{$month_name}_{$selected_year}.xls";

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");
?>
<?xml version="1.0" encoding="UTF-8"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
          xmlns:o="urn:schemas-microsoft-com:office:office"
          xmlns:x="urn:schemas-microsoft-com:office:excel"
          xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
          xmlns:html="http://www.w3.org/TR/REC-html40">
    <Styles>
        <Style ss:ID="header">
            <Font ss:FontName="Arial" ss:Size="8" ss:Bold="1"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>
            <Interior ss:Color="#E2F0D9" ss:Pattern="Solid"/>
            <Borders>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
                <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
                <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
                <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
            </Borders>
        </Style>
        <Style ss:ID="base">
            <Font ss:FontName="Arial" ss:Size="8"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
            <Borders>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
                <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
                <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
                <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
            </Borders>
        </Style>
        <Style ss:ID="statusPresent" ss:Parent="base">
            <Interior ss:Color="#FFFFFF" ss:Pattern="Solid"/>
        </Style>
        <Style ss:ID="statusBlank" ss:Parent="base">
            <Interior ss:Color="#FFFFFF" ss:Pattern="Solid"/>
        </Style>
        <Style ss:ID="statusAbsent" ss:Parent="base">
            <Font ss:FontName="Arial" ss:Size="8" ss:Color="#9C0006"/>
            <Interior ss:Color="#FFC7CE" ss:Pattern="Solid"/>
        </Style>
        <Style ss:ID="statusLeave" ss:Parent="base">
            <Interior ss:Color="#FFFF00" ss:Pattern="Solid"/>
        </Style>
        <Style ss:ID="statusHoliday" ss:Parent="base">
            <Font ss:FontName="Arial" ss:Size="8" ss:Color="#1F4E79"/>
            <Interior ss:Color="#D9E1F2" ss:Pattern="Solid"/>
        </Style>
        <Style ss:ID="statusWeekoff" ss:Parent="base">
            <Font ss:FontName="Arial" ss:Size="8" ss:Color="#006100"/>
            <Interior ss:Color="#92D050" ss:Pattern="Solid"/>
        </Style>
    </Styles>
    <Worksheet ss:Name="<?= xmlText($month_name . ' ' . $selected_year) ?>">
        <Table ss:DefaultRowHeight="14">
            <Column ss:Width="24"/>
            <Column ss:Width="55"/>
            <Column ss:Width="120"/>
            <?php for ($day = 1; $day <= $days_in_month; $day++): ?>
                <Column ss:Width="22"/>
            <?php endfor; ?>
            <Column ss:Width="70"/>
            <Column ss:Width="70"/>
            <Column ss:Width="70"/>
            <Row ss:Height="24">
                <Cell ss:StyleID="header"><Data ss:Type="String">SN</Data></Cell>
                <Cell ss:StyleID="header"><Data ss:Type="String">EMP&#10;CODE</Data></Cell>
                <Cell ss:StyleID="header"><Data ss:Type="String">EMPLOYEE NAME</Data></Cell>
                <?php for ($day = 1; $day <= $days_in_month; $day++): ?>
                    <Cell ss:StyleID="header"><Data ss:Type="Number"><?= $day ?></Data></Cell>
                <?php endfor; ?>
                <Cell ss:StyleID="header"><Data ss:Type="String">TOTAL PRESENT DAYS</Data></Cell>
                <Cell ss:StyleID="header"><Data ss:Type="String">TOTAL ABSENT DAYS</Data></Cell>
                <Cell ss:StyleID="header"><Data ss:Type="String">TOTAL LEAVE DAYS</Data></Cell>
            </Row>
            <?php foreach ($employees as $index => $employee): ?>
                <Row ss:Height="14">
                    <Cell ss:StyleID="base"><Data ss:Type="Number"><?= $index + 1 ?></Data></Cell>
                    <Cell ss:StyleID="base"><Data ss:Type="String"><?= xmlText((string) $employee['employee_id']) ?></Data></Cell>
                    <Cell ss:StyleID="base"><Data ss:Type="String"><?= xmlText((string) $employee['name']) ?></Data></Cell>
                    <?php
                    $present_days = 0;
                    $absent_days = 0;
                    $leave_days = 0;
                    ?>
                    <?php for ($day = 1; $day <= $days_in_month; $day++): ?>
                        <?php
                        $date = sprintf('%04d-%02d-%02d', $selected_year, $selected_month, $day);
                        $employee_id = (int) $employee['id'];
                        if (isset($attendance_by_employee[$employee_id][$date])) {
                            $code = statusCode((string) $attendance_by_employee[$employee_id][$date]);
                        } elseif (isset($leave_by_employee[$employee_id][$date])) {
                            $code = 'L';
                        } elseif (isset($event_by_date[$date]) && $event_by_date[$date] === 'holiday') {
                            $code = 'H';
                        } elseif (isset($event_by_date[$date]) && $event_by_date[$date] === 'weekly_off') {
                            $code = 'WO';
                        } elseif ($date > $today_date) {
                            $code = '';
                        } else {
                            $code = 'A';
                        }

                        if ($code === 'P') {
                            $present_days++;
                        } elseif ($code === 'A') {
                            $absent_days++;
                        } elseif ($code === 'L') {
                            $leave_days++;
                        }
                        ?>
                        <Cell ss:StyleID="<?= excelStyleId($code) ?>"><Data ss:Type="String"><?= xmlText($code) ?></Data></Cell>
                    <?php endfor; ?>
                    <Cell ss:StyleID="base"><Data ss:Type="Number"><?= $present_days ?></Data></Cell>
                    <Cell ss:StyleID="base"><Data ss:Type="Number"><?= $absent_days ?></Data></Cell>
                    <Cell ss:StyleID="base"><Data ss:Type="Number"><?= $leave_days ?></Data></Cell>
                </Row>
            <?php endforeach; ?>
        </Table>
        <WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">
            <FreezePanes/>
            <FrozenNoSplit/>
            <SplitHorizontal>1</SplitHorizontal>
            <TopRowBottomPane>1</TopRowBottomPane>
            <SplitVertical>3</SplitVertical>
            <LeftColumnRightPane>3</LeftColumnRightPane>
        </WorksheetOptions>
    </Worksheet>
</Workbook>
