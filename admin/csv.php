<?php
include("db_connection.php"); // your database connection file

function csvValue(array $row, array $headerMap, string $columnName): string
{
    if (!isset($headerMap[$columnName])) {
        return '';
    }

    return trim((string) ($row[$headerMap[$columnName]] ?? ''));
}

function isValidCsvDate(string $value): bool
{
    if ($value === '') {
        return false;
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return false;
    }

    [$year, $month, $day] = array_map('intval', explode('-', $value));
    return checkdate($month, $day, $year);
}

function normalizeCsvDate(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    if (isValidCsvDate($value)) {
        return $value;
    }

    $supportedFormats = [
        'Y-m-d',
        'd-m-Y',
        'd/m/Y',
        'd.m.Y',
        'd-M-Y',
        'd-F-Y',
        'j-M-Y',
        'j-F-Y',
        'Y/m/d',
        'Y.m.d',
        'd-m-y',
        'd/m/y',
        'd.m.y',
        'd-M-y',
        'd-F-y',
        'j-M-y',
        'j-F-y',
        'y-m-d',
        'y/m/d',
        'y.m.d',
    ];

    foreach ($supportedFormats as $format) {
        $dateTime = DateTime::createFromFormat('!' . $format, $value);
        $errors = DateTime::getLastErrors();

        if ($dateTime instanceof DateTime && $errors['warning_count'] === 0 && $errors['error_count'] === 0) {
            return $dateTime->format('Y-m-d');
        }
    }

    if (!preg_match('/[A-Za-z]/', $value)) {
        return '';
    }

    $timestamp = strtotime(str_replace('/', '-', $value));
    if ($timestamp === false) {
        return '';
    }

    return date('Y-m-d', $timestamp);
}

function normalizeOptionalCsvDate(string $value): ?string
{
    $value = trim($value);
    if ($value === '') {
        return null;
    }

    $normalized = normalizeCsvDate($value);
    return $normalized !== '' ? $normalized : false;
}

// Download CSV Format
if (isset($_GET['download_csv_format'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=employee_format.csv');
    $output = fopen("php://output", "w");

    fputcsv($output, [
        'Employee ID',
        'Full Name',
        'Phone',
        'Email',
        'Password',
        'Date Of Birth (YYYY-MM-DD)',
        'Anniversary Date (YYYY-MM-DD)',
        'Address',
        'Designation',
        'Date of Joining (YYYY-MM-DD)',
        'Father Name',
        'Bank Account',
        'IFSC Code',
        'Aadhaar Number',
        'PAN Number',
        'Employee UAN',
        'ESIC No'
    ]);
    fclose($output);
    exit();
}

// Handle CSV Upload
if (isset($_POST['upload_csv'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    if (($handle = fopen($file, "r")) !== false) {
        $row = 0;
        $inserted = 0;
        $duplicate_ids = [];
        $invalid_date_rows = [];
        $employees = [];
        $headerMap = [];

        while (($data = fgetcsv($handle, 1000, ",")) !== false) {
            if ($row == 0) {
                foreach ($data as $index => $header) {
                    $normalizedHeader = strtolower(trim((string) $header));
                    if ($normalizedHeader !== '') {
                        $headerMap[$normalizedHeader] = $index;
                    }
                }
                $row++;
                continue;
            }

            $csvRowNumber = $row + 1;
            $row++;

            $employee_id = strtoupper(csvValue($data, $headerMap, 'employee id'));
            $name = csvValue($data, $headerMap, 'full name');
            $password_raw = csvValue($data, $headerMap, 'password');
            $dob = normalizeOptionalCsvDate(csvValue($data, $headerMap, 'date of birth'));
            $date_of_joining = normalizeOptionalCsvDate(csvValue($data, $headerMap, 'date of joining'));
            $anniversary = normalizeOptionalCsvDate(csvValue($data, $headerMap, 'anniversary date'));

            if (empty($employee_id)) {
                continue;
            }

            if ($dob === false || $date_of_joining === false || $anniversary === false) {
                $invalid_date_rows[] = $csvRowNumber;
                continue;
            }

            $check_stmt = $conn->prepare("SELECT id FROM employees WHERE employee_id = ?");
            $check_stmt->bind_param("s", $employee_id);
            $check_stmt->execute();
            $check_stmt->store_result();
            if ($check_stmt->num_rows > 0) {
                $duplicate_ids[] = $employee_id;
                $check_stmt->close();
                continue;
            }
            $check_stmt->close();

            $employees[] = [
                'employee_id' => $employee_id,
                'name' => $name,
                'password_raw' => $password_raw !== '' ? $password_raw : $employee_id,
                'dob' => $dob,
                'anniversary' => $anniversary,
                'date_of_joining' => $date_of_joining,
                'data' => $data
            ];
        }
        fclose($handle);

        if (!empty($invalid_date_rows)) {
            $invalid_rows = implode(', ', array_unique($invalid_date_rows));
            echo "
            <div class='alert alert-danger' style='position: fixed; top: 10px; right: 10px; z-index: 1000;'>
                Upload failed. Invalid date format found in row(s):<br><strong>$invalid_rows</strong><br>
                Please use a valid date for Date Of Birth, Anniversary Date, and Date of Joining. Blank dates are allowed.
            </div>
            <script>
                setTimeout(() => {
                    window.location.href = 'manage_employee';
                }, 4000);
            </script>
            ";
            exit();
        }

        // Handle duplicates
        if (!empty($duplicate_ids)) {
            $duplicate_list = implode(", ", array_unique($duplicate_ids));
            echo "
            <div class='alert alert-danger' style='position: fixed; top: 10px; right: 10px; z-index: 1000;'>
                Upload failed. These Employee IDs already exist:<br><strong>$duplicate_list</strong><br>
                Please correct them and re-upload the file.
            </div>
            <script>
                setTimeout(() => {
                    window.location.href = 'manage_employee';
                }, 4000);
            </script>
            ";
            exit();
        }

        // Insert valid records
        foreach ($employees as $emp) {
            $data = $emp['data'];
            $employee_id = $emp['employee_id'];
            $name = $emp['name'];
            $password = password_hash($emp['password_raw'], PASSWORD_BCRYPT);

            $phone = csvValue($data, $headerMap, 'phone');
            $email = csvValue($data, $headerMap, 'email');
            $dob = $emp['dob'];
            $anniversary = $emp['anniversary'];
            $address = csvValue($data, $headerMap, 'address');
            $designation = csvValue($data, $headerMap, 'designation');
            $date_of_joining = $emp['date_of_joining'];
            $father_name = csvValue($data, $headerMap, 'father name');
            $bank_account = csvValue($data, $headerMap, 'bank account');
            $ifsc_code = csvValue($data, $headerMap, 'ifsc code');
            $adhar_number = csvValue($data, $headerMap, 'aadhaar number');
            $pan_number = csvValue($data, $headerMap, 'pan number');
            $epf_number = csvValue($data, $headerMap, 'employee uan');
            $esic = csvValue($data, $headerMap, 'esic no');
            $status = "Active";

            $stmt = $conn->prepare("INSERT INTO employees (
                employee_id, name, phone, email, password, dob, anniversary,
                address, designation, date_of_joining, father_name, bank_account,
                ifsc_code, adhar_number, pan_number, epf_number, esic, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->bind_param(
                "ssssssssssssssssss",
                $employee_id, $name, $phone, $email, $password, $dob, $anniversary,
                $address, $designation, $date_of_joining, $father_name, $bank_account,
                $ifsc_code, $adhar_number, $pan_number, $epf_number, $esic, $status
            );

            $stmt->execute();
            $stmt->close();
            $inserted++;
        }

        echo "
        <div class='alert alert-success' style='position: fixed; top: 10px; right: 10px; z-index: 1000;'>
            $inserted employees uploaded successfully.
        </div>
        <script>
            setTimeout(() => {
                window.location.href = 'manage_employee';
            }, 2000);
        </script>
        ";
    } else {
        echo "<div class='alert alert-danger'>Failed to open CSV file.</div>";
    }
}

include("header.php");
?>
