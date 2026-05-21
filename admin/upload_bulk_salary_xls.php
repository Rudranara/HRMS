<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

$year = isset($_POST['year']) ? (int) $_POST['year'] : (int) date('Y');
$month = isset($_POST['month']) ? (int) $_POST['month'] : (int) date('m');
$office = isset($_POST['office']) ? trim((string) $_POST['office']) : '';
$employee_id = isset($_POST['employee_id']) ? (int) $_POST['employee_id'] : 0;

function redirectToBulkSalary(int $year, int $month, string $office, int $employee_id): void
{
    $query = http_build_query([
        'year' => $year,
        'month' => $month,
        'office' => $office,
        'employee_id' => $employee_id,
    ]);

    header('Location: bulk_salary?' . $query);
    exit;
}

function setBulkSalaryFlash(string $type, string $message): void
{
    $_SESSION['bulk_salary_xls_flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function normalizeSpreadsheetHeader(string $value): string
{
    $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $value = strtolower(trim($value));
    return preg_replace('/[^a-z0-9]+/', '', $value) ?? '';
}

function parseSpreadsheetNumber($value): float
{
    if (is_numeric($value)) {
        return (float) $value;
    }

    $value = str_replace([',', ' '], '', trim((string) $value));
    return is_numeric($value) ? (float) $value : 0.0;
}

function spreadsheetColumnToIndex(string $reference): int
{
    $column = preg_replace('/[^A-Z]/', '', strtoupper($reference)) ?? '';
    $index = 0;

    for ($i = 0, $length = strlen($column); $i < $length; $i++) {
        $index = ($index * 26) + (ord($column[$i]) - 64);
    }

    return max(0, $index - 1);
}

function parseSpreadsheetXlsxRows(string $tmpFilePath): array
{
    if (!class_exists('ZipArchive')) {
        return [];
    }

    $zip = new ZipArchive();
    if ($zip->open($tmpFilePath) !== true) {
        return [];
    }

    $sharedStrings = [];
    $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($sharedStringsXml !== false) {
        $sharedStringsDom = new DOMDocument();
        libxml_use_internal_errors(true);
        if ($sharedStringsDom->loadXML($sharedStringsXml)) {
            $sharedXpath = new DOMXPath($sharedStringsDom);
            foreach ($sharedXpath->query('//*[local-name()="si"]') as $stringNode) {
                $parts = [];
                foreach ($sharedXpath->query('.//*[local-name()="t"]', $stringNode) as $textNode) {
                    $parts[] = $textNode->textContent;
                }
                $sharedStrings[] = trim(implode('', $parts));
            }
        }
        libxml_clear_errors();
    }

    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();
    if ($sheetXml === false) {
        return [];
    }

    $sheetDom = new DOMDocument();
    libxml_use_internal_errors(true);
    $loaded = $sheetDom->loadXML($sheetXml);
    libxml_clear_errors();
    if (!$loaded) {
        return [];
    }

    $xpath = new DOMXPath($sheetDom);
    $rowNodes = $xpath->query('//*[local-name()="worksheet"]//*[local-name()="sheetData"]/*[local-name()="row"]');
    if (!$rowNodes instanceof DOMNodeList || $rowNodes->length === 0) {
        return [];
    }

    $rows = [];
    foreach ($rowNodes as $rowNode) {
        $cells = [];
        foreach ($xpath->query('./*[local-name()="c"]', $rowNode) as $cellNode) {
            if (!$cellNode instanceof DOMElement) {
                continue;
            }

            $cellReference = $cellNode->getAttribute('r');
            $columnIndex = spreadsheetColumnToIndex($cellReference);
            $cellType = $cellNode->getAttribute('t');
            $valueNode = $xpath->query('./*[local-name()="v"]', $cellNode)->item(0);
            $inlineTextNodes = $xpath->query('./*[local-name()="is"]//*[local-name()="t"]', $cellNode);

            $value = '';
            if ($cellType === 's' && $valueNode) {
                $sharedIndex = (int) $valueNode->textContent;
                $value = $sharedStrings[$sharedIndex] ?? '';
            } elseif ($cellType === 'inlineStr' && $inlineTextNodes instanceof DOMNodeList) {
                $textParts = [];
                foreach ($inlineTextNodes as $inlineTextNode) {
                    $textParts[] = $inlineTextNode->textContent;
                }
                $value = implode('', $textParts);
            } elseif ($valueNode) {
                $value = trim($valueNode->textContent);
            }

            $cells[$columnIndex] = trim((string) $value);
        }

        if (!empty($cells)) {
            ksort($cells);
            $maxIndex = max(array_keys($cells));
            $orderedRow = [];
            for ($index = 0; $index <= $maxIndex; $index++) {
                $orderedRow[] = $cells[$index] ?? '';
            }
            $rows[] = $orderedRow;
        }
    }

    return $rows;
}
function parseSpreadsheetRows(string $content, string $tmpFilePath, string $originalName): array
{
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if ($extension === 'xlsx') {
        $rows = parseSpreadsheetXlsxRows($tmpFilePath);
        if (!empty($rows)) {
            return $rows;
        }
    }

    $rows = parseSpreadsheetXmlRows($content);
    if (!empty($rows)) {
        return $rows;
    }

    return parseSpreadsheetHtmlRows($content);
}

function parseSpreadsheetXmlRows(string $content): array
{
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $loaded = $dom->loadXML($content);
    libxml_clear_errors();

    if (!$loaded) {
        return [];
    }

    $xpath = new DOMXPath($dom);
    $rowNodes = $xpath->query('//*[local-name()="Worksheet"]//*[local-name()="Table"]/*[local-name()="Row"]');
    if (!$rowNodes instanceof DOMNodeList || $rowNodes->length === 0) {
        return [];
    }

    $rows = [];
    foreach ($rowNodes as $rowNode) {
        $cells = [];
        $columnIndex = 0;
        foreach ($xpath->query('./*[local-name()="Cell"]', $rowNode) as $cellNode) {
            if ($cellNode instanceof DOMElement && $cellNode->hasAttributeNS('urn:schemas-microsoft-com:office:spreadsheet', 'Index')) {
                $columnIndex = max($columnIndex, ((int) $cellNode->getAttributeNS('urn:schemas-microsoft-com:office:spreadsheet', 'Index')) - 1);
            }

            $dataNode = $xpath->query('.//*[local-name()="Data"]', $cellNode)->item(0);
            $cells[$columnIndex] = $dataNode ? trim($dataNode->textContent) : '';
            $columnIndex++;
        }

        if (!empty($cells)) {
            ksort($cells);
            $rows[] = array_values($cells);
        }
    }

    return $rows;
}

function parseSpreadsheetHtmlRows(string $content): array
{
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $loaded = $dom->loadHTML($content);
    libxml_clear_errors();

    if (!$loaded) {
        return [];
    }

    $xpath = new DOMXPath($dom);
    $rowNodes = $xpath->query('//table//tr');
    if (!$rowNodes instanceof DOMNodeList || $rowNodes->length === 0) {
        return [];
    }

    $rows = [];
    foreach ($rowNodes as $rowNode) {
        $cells = [];
        foreach ($xpath->query('./th|./td', $rowNode) as $cellNode) {
            $cells[] = trim($cellNode->textContent);
        }

        if (!empty(array_filter($cells, static fn ($value) => $value !== ''))) {
            $rows[] = $cells;
        }
    }

    return $rows;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setBulkSalaryFlash('danger', 'Invalid upload request.');
    redirectToBulkSalary($year, $month, $office, $employee_id);
}

if (empty($_FILES['bulk_salary_xls']['tmp_name']) || !is_uploaded_file($_FILES['bulk_salary_xls']['tmp_name'])) {
    setBulkSalaryFlash('danger', 'Please choose the edited XLS/XLSX file first.');
    redirectToBulkSalary($year, $month, $office, $employee_id);
}

$tmpFilePath = $_FILES['bulk_salary_xls']['tmp_name'];
$originalName = (string) ($_FILES['bulk_salary_xls']['name'] ?? '');
$content = file_get_contents($tmpFilePath);
if ($content === false || trim($content) === '') {
    setBulkSalaryFlash('danger', 'The uploaded XLS/XLSX file is empty or unreadable.');
    redirectToBulkSalary($year, $month, $office, $employee_id);
}

$rows = parseSpreadsheetRows($content, $tmpFilePath, $originalName);
if (count($rows) < 2) {
    setBulkSalaryFlash('danger', 'Could not read the uploaded XLS/XLSX file. Please upload the downloaded bulk salary sheet.');
    redirectToBulkSalary($year, $month, $office, $employee_id);
}

$headerRow = [];
$headerIndex = null;
foreach ($rows as $index => $row) {
    $normalized = array_map('normalizeSpreadsheetHeader', $row);
    if (in_array('employeeid', $normalized, true) && in_array('presentdays', $normalized, true)) {
        $headerRow = $normalized;
        $headerIndex = $index;
        break;
    }
}

if ($headerIndex === null) {
    setBulkSalaryFlash('danger', 'The uploaded file does not match the bulk salary XLS/XLSX format.');
    redirectToBulkSalary($year, $month, $office, $employee_id);
}

$headerMap = [
    'employeeid' => 'employee_code',
    'presentdays' => 'present_days',
    'absentdays' => 'absent_days',
    'leavedays' => 'leave_days',
    'compdays' => 'comp_days',
    'basicsalary' => 'basic',
    'da' => 'da',
    'hra' => 'hra',
    'conveyance' => 'conveyance',
    'specialallowance' => 'special_allowance',
    'bonusadvance' => 'performance_bonus',
    'medicalallowance' => 'medical_allowance',
    'washingallowance' => 'washing_allowance',
    'canteenallowance' => 'canteen_allowance',
    'otherallowances' => 'other_allowances',
    'grosssalary' => 'gross_salary',
    'epfemployer' => 'epf_employer',
    'esicemployer' => 'esic_employer',
    'gmc' => 'gmc',
    'retentionbonus' => 'retention_bonus',
    'leaveencashment' => 'leave_encashment',
    'gratuity' => 'gratuity',
    'totalctc' => 'total_ctc',
    'epfemployee' => 'epf_employee',
    'esicemployee' => 'esic_employee',
    'professionaltax' => 'professional_tax',
    'advance' => 'advance',
    'incometax' => 'income_tax',
    'insurancepremium' => 'insurance_premium',
    'otherdeductions' => 'other_deductions',
    'totaldeductions' => 'total_deductions',
    'netsalary' => 'net_salary',
];

$columnIndexes = [];
foreach ($headerRow as $index => $normalizedHeader) {
    if (isset($headerMap[$normalizedHeader])) {
        $columnIndexes[$headerMap[$normalizedHeader]] = $index;
    }
}

if (!isset($columnIndexes['employee_code'])) {
    setBulkSalaryFlash('danger', 'Employee ID column is missing in the uploaded XLS/XLSX file.');
    redirectToBulkSalary($year, $month, $office, $employee_id);
}

$numericFields = [
    'present_days', 'absent_days', 'leave_days', 'comp_days', 'basic', 'da', 'hra', 'conveyance',
    'special_allowance', 'performance_bonus', 'medical_allowance', 'washing_allowance', 'canteen_allowance',
    'other_allowances', 'gross_salary', 'epf_employer', 'esic_employer', 'gmc', 'retention_bonus',
    'leave_encashment', 'gratuity', 'total_ctc', 'epf_employee', 'esic_employee', 'professional_tax',
    'advance', 'income_tax', 'insurance_premium', 'other_deductions', 'total_deductions', 'net_salary'
];

$importedRows = [];
for ($rowIndex = $headerIndex + 1; $rowIndex < count($rows); $rowIndex++) {
    $row = $rows[$rowIndex];
    $employeeCode = trim((string) ($row[$columnIndexes['employee_code']] ?? ''));
    if ($employeeCode === '') {
        continue;
    }

    $importedRows[$employeeCode] = [];
    foreach ($numericFields as $fieldName) {
        if (!isset($columnIndexes[$fieldName])) {
            continue;
        }

        $importedRows[$employeeCode][$fieldName] = parseSpreadsheetNumber($row[$columnIndexes[$fieldName]] ?? 0);
    }
}

if (empty($importedRows)) {
    setBulkSalaryFlash('danger', 'No editable salary rows were found in the uploaded XLS file.');
    redirectToBulkSalary($year, $month, $office, $employee_id);
}

$_SESSION['bulk_salary_xls_import'] = [
    'year' => $year,
    'month' => $month,
    'office' => $office,
    'employee_id' => $employee_id,
    'rows' => $importedRows,
];

setBulkSalaryFlash('success', 'XLS/XLSX uploaded successfully. Review the updated rows, select the employees, and save the salary summary.');
redirectToBulkSalary($year, $month, $office, $employee_id);
