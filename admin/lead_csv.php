<?php
session_start();
require 'db_connection.php';

/* =========================
   ADMIN AUTH CHECK
========================= */
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    exit("Unauthorized");
}

/* =========================
   DOWNLOAD CSV FORMAT
========================= */
if (isset($_GET['download_csv'])) {

    if (ob_get_length()) ob_end_clean();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=HKO/admin/followup_monitor');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');

    fputcsv($output, [
        'lead_name',
        'company_name',
        'phone',
        'email',
        'lead_source',
        'lead_status',
        'expected_value',
        'notes'
    ]);

    fputcsv($output, [
        'John Doe',
        'ABC Pvt Ltd',
        '9876543210',
        'john@example.com',
        'Website',
        'New',
        '50000',
        'Interested in demo'
    ]);

    fclose($output);
    exit;
}

/* =========================
   UPLOAD CSV
========================= */
if (isset($_POST['upload_csv'])) {

    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== 0) {
        exit("Invalid CSV file");
    }

    $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
    if (!$file) exit("Unable to read file");

    // Skip CSV header
    fgetcsv($file);

    // Admin is creator, lead unassigned initially
    $created_by  = (int)$_SESSION['admin_id'];
    $assigned_to = null;

    $allowedSources = [
        'Website','Phone Call','Referral','Email',
        'Walk-in','Social Media','Other'
    ];

    $allowedStatus = [
        'New','Contacted','Follow-up',
        'Interested','Not Interested',
        'Converted','Lost'
    ];

    /* INSERT LEAD */
    $insert = $conn->prepare("
        INSERT INTO leads
        (
            lead_name,
            company_name,
            phone,
            email,
            lead_source,
            lead_status,
            expected_value,
            notes,
            assigned_to,
            created_by
        )
        VALUES (?,?,?,?,?,?,?,?,?,?)
    ");

    /* ACTIVITY LOG */
    $activity = $conn->prepare("
        INSERT INTO lead_activities
        (lead_id, activity_type, activity_text, created_by)
        VALUES (?,?,?,?)
    ");

    $inserted = 0;
    $skipped  = 0;

    while (($row = fgetcsv($file)) !== false) {

        if (count($row) < 8) {
            $skipped++;
            continue;
        }

        [
            $lead_name,
            $company_name,
            $phone,
            $email,
            $lead_source,
            $lead_status,
            $expected_value,
            $notes
        ] = array_map('trim', $row);

        /* REQUIRED FIELDS */
        if ($lead_name === '' || $phone === '') {
            $skipped++;
            continue;
        }

        /* NORMALIZE */
        if (!in_array($lead_source, $allowedSources)) {
            $lead_source = 'Other';
        }

        if (!in_array($lead_status, $allowedStatus)) {
            $lead_status = 'New';
        }

        $expected_value = is_numeric($expected_value) ? $expected_value : 0;

        /* =========================
           DUPLICATE CHECK
        ========================= */
        $dup = $conn->prepare("
            SELECT id
            FROM leads
            WHERE phone = ?
            OR (email <> '' AND email = ?)
            LIMIT 1
        ");
        $dup->bind_param("ss", $phone, $email);
        $dup->execute();
        $dup->store_result();

        if ($dup->num_rows > 0) {
            $skipped++;
            $dup->close();
            continue;
        }
        $dup->close();

        /* INSERT LEAD */
        $insert->bind_param(
            "ssssssdsii",
            $lead_name,
            $company_name,
            $phone,
            $email,
            $lead_source,
            $lead_status,
            $expected_value,
            $notes,
            $assigned_to,
            $created_by
        );

        if ($insert->execute()) {

            $lead_id = $insert->insert_id;

            /* ACTIVITY */
            $type = 'Lead Created';
            $text = 'Lead created via bulk upload';

            $activity->bind_param(
                "issi",
                $lead_id,
                $type,
                $text,
                $created_by
            );
            $activity->execute();

            $inserted++;
        } else {
            $skipped++;
        }
    }

    fclose($file);

    header("Location: add_lead?bulk_success=1&inserted=$inserted&skipped=$skipped");
    exit;
}
