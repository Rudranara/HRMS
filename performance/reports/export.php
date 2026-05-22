<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    exit('Unauthorized');
}

require dirname(__DIR__) . '/../admin/db_connection.php';
require dirname(__DIR__) . '/includes/helpers.php';
require dirname(__DIR__) . '/includes/data.php';
require dirname(__DIR__) . '/../admin/dompdf/vendor/autoload.php';

use Dompdf\Dompdf;

$payload = performance_load_data($conn, 'admin', 'admin', (int) ($_SESSION['admin_id'] ?? 0));
$format = strtolower(trim($_GET['format'] ?? 'excel'));
$filename = 'performance_report_' . date('Ymd_His');

$rows = array();
foreach ($payload['results'] as $result) {
    $rows[] = array(
        'Employee' => $result['employee'],
        'Department' => $result['department'],
        'KPI Score' => $result['kpi_score'],
        'Manager Score' => $result['manager_score'],
        'Attendance Score' => $result['attendance_score'],
        'Self Review Score' => $result['self_review_score'],
        'Final Score' => $result['final_score'],
        'Final Rating' => $result['final_rating']
    );
}

if ($format === 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename=' . $filename . '.xls');
    $output = fopen('php://output', 'wb');
    if (!empty($rows)) {
        fputcsv($output, array_keys($rows[0]), "\t");
        foreach ($rows as $row) {
            fputcsv($output, $row, "\t");
        }
    }
    fclose($output);
    exit;
}

$html = '<html><head><style>body{font-family:DejaVu Sans,sans-serif;color:#1f2937;font-size:12px;}h2{color:#123b76;}table{width:100%;border-collapse:collapse;margin-top:18px;}th,td{border:1px solid #dbe3ed;padding:8px 10px;text-align:left;}th{background:#eff6ff;color:#123b76;font-size:11px;text-transform:uppercase;}tr:nth-child(even){background:#f8fafc;}</style></head><body>';
$html .= '<h2>Performance Management Report</h2><p>Generated on ' . date('d M Y h:i A') . '</p><table><thead><tr>';
foreach (array_keys($rows[0]) as $heading) {
    $html .= '<th>' . performance_escape($heading) . '</th>';
}
$html .= '</tr></thead><tbody>';
foreach ($rows as $row) {
    $html .= '<tr>';
    foreach ($row as $cell) {
        $html .= '<td>' . performance_escape($cell) . '</td>';
    }
    $html .= '</tr>';
}
$html .= '</tbody></table></body></html>';

$dompdf = new Dompdf();
$dompdf->set_option('isRemoteEnabled', true);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream($filename . '.pdf', array('Attachment' => true));
exit;
