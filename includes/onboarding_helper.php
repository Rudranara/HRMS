<?php

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

if (file_exists(__DIR__ . '/../emp/vendor/autoload.php')) {
    require_once __DIR__ . '/../emp/vendor/autoload.php';
}

if (!function_exists('onboardingNow')) {
    function onboardingNow(): string
    {
        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('onboardingEnsureSchema')) {
    function onboardingEnsureSchema(mysqli $conn): void
    {
        $conn->query("
            CREATE TABLE IF NOT EXISTS onboarding_records (
                id INT AUTO_INCREMENT PRIMARY KEY,
                onboarding_code VARCHAR(40) NOT NULL,
                invitation_token VARCHAR(80) NOT NULL,
                candidate_name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                joining_date DATE NOT NULL,
                department VARCHAR(255) DEFAULT NULL,
                role_title VARCHAR(255) DEFAULT NULL,
                office_name VARCHAR(255) DEFAULT NULL,
                access_role VARCHAR(50) NOT NULL DEFAULT 'Employee',
                status VARCHAR(30) NOT NULL DEFAULT 'Invited',
                temp_password_hash VARCHAR(255) NOT NULL,
                portal_password_hash VARCHAR(255) DEFAULT NULL,
                first_login_completed TINYINT(1) NOT NULL DEFAULT 0,
                progress_percent INT NOT NULL DEFAULT 0,
                current_step VARCHAR(50) DEFAULT 'personal',
                full_name VARCHAR(255) DEFAULT NULL,
                date_of_birth DATE DEFAULT NULL,
                gender VARCHAR(30) DEFAULT NULL,
                marital_status VARCHAR(30) DEFAULT NULL,
                phone VARCHAR(20) DEFAULT NULL,
                alternate_email VARCHAR(255) DEFAULT NULL,
                permanent_address TEXT DEFAULT NULL,
                current_address TEXT DEFAULT NULL,
                bank_account_number VARCHAR(50) DEFAULT NULL,
                bank_ifsc_code VARCHAR(20) DEFAULT NULL,
                bank_name VARCHAR(255) DEFAULT NULL,
                pan_number VARCHAR(20) DEFAULT NULL,
                aadhaar_number VARCHAR(20) DEFAULT NULL,
                uan_number VARCHAR(30) DEFAULT NULL,
                review_comment TEXT DEFAULT NULL,
                submitted_at DATETIME DEFAULT NULL,
                reviewed_at DATETIME DEFAULT NULL,
                activated_at DATETIME DEFAULT NULL,
                activated_employee_id INT DEFAULT NULL,
                invited_at DATETIME NOT NULL,
                last_saved_at DATETIME DEFAULT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME DEFAULT NULL,
                created_by_admin_id INT DEFAULT NULL,
                created_by_name VARCHAR(255) DEFAULT NULL,
                UNIQUE KEY uniq_onboarding_code (onboarding_code),
                UNIQUE KEY uniq_onboarding_token (invitation_token),
                KEY idx_onboarding_status (status),
                KEY idx_onboarding_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $conn->query("
            CREATE TABLE IF NOT EXISTS onboarding_documents (
                id INT AUTO_INCREMENT PRIMARY KEY,
                onboarding_id INT NOT NULL,
                document_key VARCHAR(50) NOT NULL,
                document_label VARCHAR(100) NOT NULL,
                file_path VARCHAR(255) DEFAULT NULL,
                upload_status VARCHAR(20) NOT NULL DEFAULT 'Pending',
                verification_status VARCHAR(20) NOT NULL DEFAULT 'Pending',
                verification_comment TEXT DEFAULT NULL,
                uploaded_at DATETIME DEFAULT NULL,
                verified_at DATETIME DEFAULT NULL,
                UNIQUE KEY uniq_onboarding_document (onboarding_id, document_key),
                KEY idx_onboarding_document (onboarding_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $conn->query("
            CREATE TABLE IF NOT EXISTS onboarding_tasks (
                id INT AUTO_INCREMENT PRIMARY KEY,
                onboarding_id INT NOT NULL,
                owner_type VARCHAR(20) NOT NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT DEFAULT NULL,
                status VARCHAR(30) NOT NULL DEFAULT 'Pending',
                due_date DATE DEFAULT NULL,
                completed_at DATETIME DEFAULT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME DEFAULT NULL,
                KEY idx_onboarding_task (onboarding_id),
                KEY idx_owner_type (owner_type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }
}

if (!function_exists('onboardingDocumentCatalog')) {
    function onboardingDocumentCatalog(): array
    {
        return [
            'aadhaar' => 'Aadhaar Card',
            'pan' => 'PAN Card',
            'resume' => 'Resume',
            'photo' => 'Photo',
            'certificates' => 'Certificates',
        ];
    }
}

if (!function_exists('onboardingTaskTemplates')) {
    function onboardingTaskTemplates(string $joiningDate): array
    {
        return [
            [
                'owner_type' => 'Employee',
                'title' => 'Submit onboarding documents',
                'description' => 'Upload Aadhaar, PAN, resume, photo, and certificates in the onboarding portal.',
                'due_date' => $joiningDate,
            ],
            [
                'owner_type' => 'HR',
                'title' => 'Verify documents',
                'description' => 'Review employee details, bank details, statutory information, and uploaded documents.',
                'due_date' => $joiningDate,
            ],
            [
                'owner_type' => 'IT',
                'title' => 'Setup laptop and company email',
                'description' => 'Prepare device allocation and create official communication access.',
                'due_date' => $joiningDate,
            ],
            [
                'owner_type' => 'Manager',
                'title' => 'Team onboarding',
                'description' => 'Introduce the employee to the team, manager, and day-one expectations.',
                'due_date' => $joiningDate,
            ],
        ];
    }
}

if (!function_exists('onboardingGenerateCode')) {
    function onboardingGenerateCode(): string
    {
        return 'ONB' . date('YmdHis') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    }
}

if (!function_exists('onboardingGenerateToken')) {
    function onboardingGenerateToken(): string
    {
        return bin2hex(random_bytes(24));
    }
}

if (!function_exists('onboardingGenerateTempPassword')) {
    function onboardingGenerateTempPassword(): string
    {
        return 'ONB' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    }
}

if (!function_exists('onboardingNormalizeEmail')) {
    function onboardingNormalizeEmail(?string $email): string
    {
        return strtolower(trim((string) $email));
    }
}

if (!function_exists('onboardingNormalizePhone')) {
    function onboardingNormalizePhone(?string $phone): string
    {
        return preg_replace('/\D+/', '', trim((string) $phone)) ?? '';
    }
}

if (!function_exists('onboardingValidateInviteData')) {
    function onboardingValidateInviteData(array $data): array
    {
        $candidateName = trim((string) ($data['candidate_name'] ?? ''));
        $email = onboardingNormalizeEmail($data['email'] ?? '');
        $joiningDate = trim((string) ($data['joining_date'] ?? ''));
        $department = trim((string) ($data['department'] ?? ''));
        $roleTitle = trim((string) ($data['role_title'] ?? ''));

        if ($candidateName === '' || mb_strlen($candidateName) > 255) {
            throw new RuntimeException('Please enter a valid candidate name.');
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) {
            throw new RuntimeException('Please enter a valid email address.');
        }

        $parsedDate = DateTime::createFromFormat('Y-m-d', $joiningDate);
        if (!$parsedDate || $parsedDate->format('Y-m-d') !== $joiningDate) {
            throw new RuntimeException('Please enter a valid joining date.');
        }

        return [
            'candidate_name' => $candidateName,
            'email' => $email,
            'joining_date' => $joiningDate,
            'department' => mb_substr($department, 0, 255),
            'role_title' => mb_substr($roleTitle, 0, 255),
        ];
    }
}

if (!function_exists('onboardingAssertNoOpenInviteDuplicate')) {
    function onboardingAssertNoOpenInviteDuplicate(mysqli $conn, string $email, int $excludeId = 0): void
    {
        $stmt = $conn->prepare("
            SELECT onboarding_code, status
            FROM onboarding_records
            WHERE email = ?
              AND id <> ?
              AND status IN ('Invited', 'In Progress', 'Submitted', 'Approved')
            LIMIT 1
        ");
        $stmt->bind_param("si", $email, $excludeId);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existing) {
            throw new RuntimeException(
                'An onboarding record already exists for this email in status ' .
                $existing['status'] . ' (' . $existing['onboarding_code'] . ').'
            );
        }
    }
}

if (!function_exists('onboardingAssertOnboardingFieldUnique')) {
    function onboardingAssertOnboardingFieldUnique(mysqli $conn, string $field, string $value, int $excludeId, string $label): void
    {
        $allowedFields = ['email', 'phone', 'aadhaar_number', 'pan_number'];
        if ($value === '' || !in_array($field, $allowedFields, true)) {
            return;
        }

        $sql = "
            SELECT onboarding_code, status
            FROM onboarding_records
            WHERE {$field} = ?
              AND id <> ?
              AND status IN ('Invited', 'In Progress', 'Submitted', 'Approved', 'Active')
            LIMIT 1
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $value, $excludeId);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existing) {
            throw new RuntimeException(
                $label . ' already exists in onboarding record ' . $existing['onboarding_code'] .
                ' (' . $existing['status'] . ').'
            );
        }
    }
}

if (!function_exists('onboardingAssertEmployeeFieldUnique')) {
    function onboardingAssertEmployeeFieldUnique(mysqli $conn, string $field, string $value, string $label): void
    {
        $allowedFields = ['email', 'phone', 'adhar_number', 'pan_number', 'employee_id'];
        if ($value === '' || !in_array($field, $allowedFields, true)) {
            return;
        }

        $sql = "SELECT id FROM employees WHERE {$field} = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $value);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existing) {
            throw new RuntimeException($label . ' already exists in employee records.');
        }
    }
}

if (!function_exists('onboardingInferAccessRole')) {
    function onboardingInferAccessRole(?string $roleTitle): string
    {
        $roleTitle = strtolower(trim((string) $roleTitle));
        if ($roleTitle !== '' && (strpos($roleTitle, 'manager') !== false || strpos($roleTitle, 'supervisor') !== false)) {
            return 'Manager';
        }

        return 'Employee';
    }
}

if (!function_exists('onboardingAppBasePath')) {
    function onboardingAppBasePath(): string
    {
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $dir = str_replace('\\', '/', dirname($scriptName));
        $dir = rtrim($dir, '/');

        if (preg_match('#/(admin|emp|includes)$#', $dir)) {
            $dir = dirname($dir);
        }

        if ($dir === '/' || $dir === '\\' || $dir === '.') {
            return '';
        }

        return rtrim(str_replace('\\', '/', $dir), '/');
    }
}

if (!function_exists('onboardingPortalUrl')) {
    function onboardingPortalUrl(string $token): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $basePath = onboardingAppBasePath();
        return $scheme . '://' . $host . $basePath . '/onboarding?token=' . urlencode($token);
    }
}

if (!function_exists('onboardingSendEmail')) {
    function onboardingSendEmail(string $toEmail, string $toName, string $subject, string $body): bool
    {
        if (!class_exists(PHPMailer::class)) {
            return false;
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'amaresh.sahoo101@gmail.com';
            $mail->Password = 'hwzfavtumiqhcwtu';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('amaresh.sahoo101@gmail.com', 'HR Team');
            $mail->addAddress($toEmail, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->send();

            return true;
        } catch (Exception $e) {
            error_log('Onboarding mail error: ' . $mail->ErrorInfo);
            return false;
        }
    }
}

if (!function_exists('onboardingSeedDocuments')) {
    function onboardingSeedDocuments(mysqli $conn, int $onboardingId): void
    {
        $stmt = $conn->prepare("
            INSERT INTO onboarding_documents (onboarding_id, document_key, document_label)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE document_label = VALUES(document_label)
        ");

        foreach (onboardingDocumentCatalog() as $key => $label) {
            $stmt->bind_param("iss", $onboardingId, $key, $label);
            $stmt->execute();
        }

        $stmt->close();
    }
}

if (!function_exists('onboardingSeedTasks')) {
    function onboardingSeedTasks(mysqli $conn, int $onboardingId, string $joiningDate): void
    {
        $check = $conn->prepare("SELECT COUNT(*) AS total FROM onboarding_tasks WHERE onboarding_id = ?");
        $check->bind_param("i", $onboardingId);
        $check->execute();
        $existing = (int) ($check->get_result()->fetch_assoc()['total'] ?? 0);
        $check->close();

        if ($existing > 0) {
            return;
        }

        $stmt = $conn->prepare("
            INSERT INTO onboarding_tasks (onboarding_id, owner_type, title, description, status, due_date, created_at)
            VALUES (?, ?, ?, ?, 'Pending', ?, ?)
        ");

        $now = onboardingNow();
        foreach (onboardingTaskTemplates($joiningDate) as $task) {
            $stmt->bind_param(
                "isssss",
                $onboardingId,
                $task['owner_type'],
                $task['title'],
                $task['description'],
                $task['due_date'],
                $now
            );
            $stmt->execute();
        }

        $stmt->close();
    }
}

if (!function_exists('onboardingCreateInvitation')) {
    function onboardingCreateInvitation(mysqli $conn, array $data, array $adminContext = []): array
    {
        $data = onboardingValidateInviteData($data);
        $onboardingCode = onboardingGenerateCode();
        $token = onboardingGenerateToken();
        $tempPassword = onboardingGenerateTempPassword();
        $tempPasswordHash = password_hash($tempPassword, PASSWORD_BCRYPT);
        $now = onboardingNow();
        $candidateName = $data['candidate_name'];
        $email = $data['email'];
        $joiningDate = $data['joining_date'];
        $department = $data['department'];
        $roleTitle = $data['role_title'];
        $officeName = trim($data['office_name'] ?? '');
        $accessRole = trim($data['access_role'] ?? onboardingInferAccessRole($roleTitle));
        $createdByAdminId = isset($adminContext['admin_id']) ? (int) $adminContext['admin_id'] : null;
        $createdByName = trim($adminContext['admin_name'] ?? '');

        onboardingAssertNoOpenInviteDuplicate($conn, $email);
        onboardingAssertEmployeeFieldUnique($conn, 'email', $email, 'Email');

        $stmt = $conn->prepare("
            INSERT INTO onboarding_records (
                onboarding_code, invitation_token, candidate_name, email, joining_date,
                department, role_title, office_name, access_role, status, temp_password_hash,
                invited_at, created_at, updated_at, created_by_admin_id, created_by_name
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Invited', ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "sssssssssssssis",
            $onboardingCode,
            $token,
            $candidateName,
            $email,
            $joiningDate,
            $department,
            $roleTitle,
            $officeName,
            $accessRole,
            $tempPasswordHash,
            $now,
            $now,
            $now,
            $createdByAdminId,
            $createdByName
        );
        $stmt->execute();
        $onboardingId = (int) $stmt->insert_id;
        $stmt->close();

        onboardingSeedDocuments($conn, $onboardingId);
        onboardingSeedTasks($conn, $onboardingId, $joiningDate);

        $portalUrl = onboardingPortalUrl($token);
        $emailSent = onboardingSendEmail(
            $email,
            $candidateName,
            'Your onboarding invitation',
            "
                <p>Dear " . htmlspecialchars($candidateName) . ",</p>
                <p>Your onboarding has been started. Please use the secure link below to complete your details.</p>
                <p><strong>Joining Date:</strong> " . htmlspecialchars($joiningDate) . "<br>
                <strong>Department:</strong> " . htmlspecialchars($department ?: 'Not assigned yet') . "<br>
                <strong>Role:</strong> " . htmlspecialchars($roleTitle ?: 'Employee') . "</p>
                <p><strong>Onboarding Link:</strong><br><a href=\"" . htmlspecialchars($portalUrl) . "\">" . htmlspecialchars($portalUrl) . "</a></p>
                <p><strong>Temporary Password:</strong> " . htmlspecialchars($tempPassword) . "</p>
                <p>At first login, you will be asked to set your own password. You can save your progress and continue anytime.</p>
                <p>Regards,<br>HR Team</p>
            "
        );

        return [
            'id' => $onboardingId,
            'onboarding_code' => $onboardingCode,
            'token' => $token,
            'portal_url' => $portalUrl,
            'temp_password' => $tempPassword,
            'email_sent' => $emailSent,
        ];
    }
}

if (!function_exists('onboardingGetRecordById')) {
    function onboardingGetRecordById(mysqli $conn, int $id): ?array
    {
        $stmt = $conn->prepare("SELECT * FROM onboarding_records WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $record = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();
        return $record;
    }
}

if (!function_exists('onboardingGetRecordByToken')) {
    function onboardingGetRecordByToken(mysqli $conn, string $token): ?array
    {
        $stmt = $conn->prepare("SELECT * FROM onboarding_records WHERE invitation_token = ? LIMIT 1");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $record = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();
        return $record;
    }
}

if (!function_exists('onboardingGetDocuments')) {
    function onboardingGetDocuments(mysqli $conn, int $onboardingId): array
    {
        onboardingSeedDocuments($conn, $onboardingId);
        $stmt = $conn->prepare("SELECT * FROM onboarding_documents WHERE onboarding_id = ? ORDER BY id ASC");
        $stmt->bind_param("i", $onboardingId);
        $stmt->execute();
        $documents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $documents;
    }
}

if (!function_exists('onboardingGetTasks')) {
    function onboardingGetTasks(mysqli $conn, int $onboardingId): array
    {
        $record = onboardingGetRecordById($conn, $onboardingId);
        if ($record) {
            onboardingSeedTasks($conn, $onboardingId, $record['joining_date']);
        }

        $stmt = $conn->prepare("SELECT * FROM onboarding_tasks WHERE onboarding_id = ? ORDER BY id ASC");
        $stmt->bind_param("i", $onboardingId);
        $stmt->execute();
        $tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $tasks;
    }
}

if (!function_exists('onboardingIsSectionComplete')) {
    function onboardingIsSectionComplete(array $record, string $section, array $documents = []): bool
    {
        switch ($section) {
            case 'personal':
                return !empty($record['full_name']) && !empty($record['date_of_birth']) && !empty($record['gender']) && !empty($record['marital_status']);
            case 'contact':
                return !empty($record['phone']) && !empty($record['email']) && !empty($record['permanent_address']) && !empty($record['current_address']);
            case 'bank':
                return !empty($record['bank_account_number']) && !empty($record['bank_ifsc_code']) && !empty($record['bank_name']);
            case 'statutory':
                return !empty($record['pan_number']) && !empty($record['aadhaar_number']) && !empty($record['uan_number']);
            case 'documents':
                $required = array_keys(onboardingDocumentCatalog());
                $uploaded = [];
                foreach ($documents as $document) {
                    if ($document['upload_status'] === 'Uploaded') {
                        $uploaded[] = $document['document_key'];
                    }
                }
                return count(array_diff($required, $uploaded)) === 0;
        }

        return false;
    }
}

if (!function_exists('onboardingSectionStates')) {
    function onboardingSectionStates(array $record, array $documents): array
    {
        $sections = ['personal', 'contact', 'bank', 'statutory', 'documents'];
        $states = [];
        foreach ($sections as $section) {
            $states[$section] = onboardingIsSectionComplete($record, $section, $documents);
        }
        return $states;
    }
}

if (!function_exists('onboardingCalculateProgress')) {
    function onboardingCalculateProgress(array $record, array $documents): int
    {
        $states = onboardingSectionStates($record, $documents);
        $completed = count(array_filter($states));
        return (int) round(($completed / max(count($states), 1)) * 100);
    }
}

if (!function_exists('onboardingPersistProgress')) {
    function onboardingPersistProgress(mysqli $conn, int $onboardingId): array
    {
        $record = onboardingGetRecordById($conn, $onboardingId);
        $documents = onboardingGetDocuments($conn, $onboardingId);
        $progress = onboardingCalculateProgress($record, $documents);
        $states = onboardingSectionStates($record, $documents);

        $currentStep = 'personal';
        foreach (['personal', 'contact', 'bank', 'statutory', 'documents'] as $step) {
            if (empty($states[$step])) {
                $currentStep = $step;
                break;
            }
            $currentStep = $step;
        }

        $status = $record['status'];
        if ($status === 'Invited' && ($record['first_login_completed'] || $progress > 0)) {
            $status = 'In Progress';
        }

        $now = onboardingNow();
        $stmt = $conn->prepare("
            UPDATE onboarding_records
            SET progress_percent = ?, current_step = ?, status = ?, last_saved_at = ?, updated_at = ?
            WHERE id = ?
        ");
        $stmt->bind_param("issssi", $progress, $currentStep, $status, $now, $now, $onboardingId);
        $stmt->execute();
        $stmt->close();

        return [
            'progress' => $progress,
            'current_step' => $currentStep,
            'status' => $status,
            'sections' => $states,
        ];
    }
}

if (!function_exists('onboardingSaveSection')) {
    function onboardingSaveSection(mysqli $conn, int $onboardingId, string $section, array $payload): array
    {
        $allowedFields = [
            'personal' => ['full_name', 'date_of_birth', 'gender', 'marital_status'],
            'contact' => ['phone', 'alternate_email', 'permanent_address', 'current_address'],
            'bank' => ['bank_account_number', 'bank_ifsc_code', 'bank_name'],
            'statutory' => ['pan_number', 'aadhaar_number', 'uan_number'],
        ];

        if (!isset($allowedFields[$section])) {
            throw new RuntimeException('Invalid onboarding section.');
        }

        $sets = [];
        $values = [];
        $types = '';

        foreach ($allowedFields[$section] as $field) {
            $value = trim((string) ($payload[$field] ?? ''));
            if ($field === 'phone') {
                $value = onboardingNormalizePhone($value);
                if ($value !== '' && strlen($value) < 10) {
                    throw new RuntimeException('Please enter a valid phone number.');
                }
            }
            if ($field === 'alternate_email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Please enter a valid alternate email address.');
            }
            if (in_array($field, ['pan_number', 'aadhaar_number', 'uan_number'], true)) {
                $value = strtoupper($value);
            }
            $sets[] = "{$field} = ?";
            $values[] = $value;
            $types .= 's';
        }

        if ($section === 'contact') {
            $phone = onboardingNormalizePhone($payload['phone'] ?? '');
            onboardingAssertOnboardingFieldUnique($conn, 'phone', $phone, $onboardingId, 'Phone number');
            onboardingAssertEmployeeFieldUnique($conn, 'phone', $phone, 'Phone number');
        }

        if ($section === 'statutory') {
            $panNumber = strtoupper(trim((string) ($payload['pan_number'] ?? '')));
            $aadhaarNumber = strtoupper(trim((string) ($payload['aadhaar_number'] ?? '')));
            onboardingAssertOnboardingFieldUnique($conn, 'pan_number', $panNumber, $onboardingId, 'PAN number');
            onboardingAssertOnboardingFieldUnique($conn, 'aadhaar_number', $aadhaarNumber, $onboardingId, 'Aadhaar number');
            onboardingAssertEmployeeFieldUnique($conn, 'pan_number', $panNumber, 'PAN number');
            onboardingAssertEmployeeFieldUnique($conn, 'adhar_number', $aadhaarNumber, 'Aadhaar number');
        }

        $now = onboardingNow();
        $record = onboardingGetRecordById($conn, $onboardingId);
        if ($record && $record['status'] === 'Rejected') {
            $sets[] = "status = ?";
            $values[] = 'In Progress';
            $types .= 's';
        }

        $sets[] = "updated_at = ?";
        $values[] = $now;
        $types .= 's';
        $sets[] = "last_saved_at = ?";
        $values[] = $now;
        $types .= 's';

        $values[] = $onboardingId;
        $types .= 'i';

        $sql = "UPDATE onboarding_records SET " . implode(', ', $sets) . " WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        $stmt->close();

        return onboardingPersistProgress($conn, $onboardingId);
    }
}

if (!function_exists('onboardingUploadDocument')) {
    function onboardingUploadDocument(mysqli $conn, int $onboardingId, string $documentKey, array $file): array
    {
        $catalog = onboardingDocumentCatalog();
        if (!isset($catalog[$documentKey])) {
            throw new RuntimeException('Unsupported document type.');
        }

        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('Please choose a valid file.');
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
        if (!in_array($ext, $allowed, true)) {
            throw new RuntimeException('Only PDF, JPG, JPEG, and PNG files are allowed.');
        }

        $relativeDir = 'uploads/onboarding_documents';
        $absoluteDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'onboarding_documents';
        if (!is_dir($absoluteDir)) {
            mkdir($absoluteDir, 0777, true);
        }

        $filename = $onboardingId . '_' . $documentKey . '_' . time() . '.' . $ext;
        $absolutePath = $absoluteDir . DIRECTORY_SEPARATOR . $filename;
        $relativePath = $relativeDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $absolutePath)) {
            throw new RuntimeException('Document upload failed.');
        }

        $now = onboardingNow();
        $record = onboardingGetRecordById($conn, $onboardingId);

        $stmt = $conn->prepare("
            UPDATE onboarding_documents
            SET file_path = ?, upload_status = 'Uploaded', verification_status = 'Pending',
                verification_comment = NULL, uploaded_at = ?, verified_at = NULL
            WHERE onboarding_id = ? AND document_key = ?
        ");
        $stmt->bind_param("ssis", $relativePath, $now, $onboardingId, $documentKey);
        $stmt->execute();
        $stmt->close();

        if ($record && $record['status'] === 'Rejected') {
            $status = 'In Progress';
            $stmt = $conn->prepare("UPDATE onboarding_records SET status = ?, updated_at = ?, last_saved_at = ? WHERE id = ?");
            $stmt->bind_param("sssi", $status, $now, $now, $onboardingId);
            $stmt->execute();
            $stmt->close();
        }

        return onboardingPersistProgress($conn, $onboardingId);
    }
}

if (!function_exists('onboardingSetPortalPassword')) {
    function onboardingSetPortalPassword(mysqli $conn, int $onboardingId, string $password): void
    {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $now = onboardingNow();
        $status = 'In Progress';
        $stmt = $conn->prepare("
            UPDATE onboarding_records
            SET portal_password_hash = ?, first_login_completed = 1, status = ?, updated_at = ?, last_saved_at = ?
            WHERE id = ?
        ");
        $stmt->bind_param("ssssi", $hash, $status, $now, $now, $onboardingId);
        $stmt->execute();
        $stmt->close();
        onboardingPersistProgress($conn, $onboardingId);
    }
}

if (!function_exists('onboardingVerifyPortalPassword')) {
    function onboardingVerifyPortalPassword(array $record, string $password): bool
    {
        if (!empty($record['portal_password_hash'])) {
            return password_verify($password, $record['portal_password_hash']);
        }

        return password_verify($password, $record['temp_password_hash']);
    }
}

if (!function_exists('onboardingSubmitForApproval')) {
    function onboardingSubmitForApproval(mysqli $conn, int $onboardingId): array
    {
        $state = onboardingPersistProgress($conn, $onboardingId);
        if ((int) $state['progress'] < 100) {
            throw new RuntimeException('Complete all onboarding sections before submitting.');
        }

        $status = 'Submitted';
        $now = onboardingNow();
        $stmt = $conn->prepare("
            UPDATE onboarding_records
            SET status = ?, submitted_at = ?, updated_at = ?
            WHERE id = ?
        ");
        $stmt->bind_param("sssi", $status, $now, $now, $onboardingId);
        $stmt->execute();
        $stmt->close();

        return onboardingPersistProgress($conn, $onboardingId);
    }
}

if (!function_exists('onboardingUpdateDocumentVerification')) {
    function onboardingUpdateDocumentVerification(mysqli $conn, int $documentId, string $status, string $comment = ''): void
    {
        $allowed = ['Pending', 'Verified', 'Rejected'];
        if (!in_array($status, $allowed, true)) {
            throw new RuntimeException('Invalid verification status.');
        }

        $now = onboardingNow();
        $stmt = $conn->prepare("
            UPDATE onboarding_documents
            SET verification_status = ?, verification_comment = ?, verified_at = ?, upload_status = IF(file_path IS NULL, 'Pending', upload_status)
            WHERE id = ?
        ");
        $stmt->bind_param("sssi", $status, $comment, $now, $documentId);
        $stmt->execute();
        $stmt->close();
    }
}

if (!function_exists('onboardingUpdateTaskStatus')) {
    function onboardingUpdateTaskStatus(mysqli $conn, int $taskId, string $status): void
    {
        $allowed = ['Pending', 'In Progress', 'Completed'];
        if (!in_array($status, $allowed, true)) {
            throw new RuntimeException('Invalid task status.');
        }

        $completedAt = $status === 'Completed' ? onboardingNow() : null;
        $updatedAt = onboardingNow();
        $stmt = $conn->prepare("
            UPDATE onboarding_tasks
            SET status = ?, completed_at = ?, updated_at = ?
            WHERE id = ?
        ");
        $stmt->bind_param("sssi", $status, $completedAt, $updatedAt, $taskId);
        $stmt->execute();
        $stmt->close();
    }
}

if (!function_exists('onboardingResolveOffice')) {
    function onboardingResolveOffice(mysqli $conn, ?string $preferredOffice = null): string
    {
        $preferredOffice = trim((string) $preferredOffice);
        if ($preferredOffice !== '') {
            return $preferredOffice;
        }

        $result = $conn->query("SELECT office_name, state_name FROM offices ORDER BY id ASC LIMIT 1");
        if ($result && ($office = $result->fetch_assoc())) {
            return $office['office_name'] . '_' . $office['state_name'];
        }

        return 'Head Office_Default';
    }
}

if (!function_exists('onboardingGenerateEmployeeCode')) {
    function onboardingGenerateEmployeeCode(mysqli $conn): string
    {
        $result = $conn->query("SELECT MAX(id) AS max_id FROM employees");
        $nextId = 1;
        if ($result && ($row = $result->fetch_assoc())) {
            $nextId = ((int) ($row['max_id'] ?? 0)) + 1;
        }

        do {
            $employeeCode = 'EMP' . str_pad((string) $nextId, 4, '0', STR_PAD_LEFT);
            $stmt = $conn->prepare("SELECT id FROM employees WHERE employee_id = ? LIMIT 1");
            $stmt->bind_param("s", $employeeCode);
            $stmt->execute();
            $exists = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $nextId++;
        } while ($exists);

        return $employeeCode;
    }
}

if (!function_exists('onboardingAssertActivationDuplicates')) {
    function onboardingAssertActivationDuplicates(mysqli $conn, array $record): void
    {
        onboardingAssertEmployeeFieldUnique($conn, 'email', onboardingNormalizeEmail($record['email'] ?? ''), 'Email');
        onboardingAssertEmployeeFieldUnique($conn, 'phone', onboardingNormalizePhone($record['phone'] ?? ''), 'Phone number');
        onboardingAssertEmployeeFieldUnique($conn, 'adhar_number', trim((string) ($record['aadhaar_number'] ?? '')), 'Aadhaar number');
        onboardingAssertEmployeeFieldUnique($conn, 'pan_number', strtoupper(trim((string) ($record['pan_number'] ?? ''))), 'PAN number');
    }
}

if (!function_exists('onboardingActivateEmployee')) {
    function onboardingActivateEmployee(mysqli $conn, int $onboardingId): string
    {
        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare("SELECT * FROM onboarding_records WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $onboardingId);
            $stmt->execute();
            $record = $stmt->get_result()->fetch_assoc() ?: null;
            $stmt->close();

            if (!$record) {
                throw new RuntimeException('Onboarding record not found.');
            }

            if (empty($record['portal_password_hash'])) {
                throw new RuntimeException('Employee password setup is incomplete.');
            }

            if ((int) $record['activated_employee_id'] > 0) {
                $conn->commit();
                return (string) $record['activated_employee_id'];
            }

            onboardingAssertActivationDuplicates($conn, $record);

            $employeeCode = onboardingGenerateEmployeeCode($conn);
            onboardingAssertEmployeeFieldUnique($conn, 'employee_id', $employeeCode, 'Employee ID');

            $employeeName = $record['full_name'] ?: $record['candidate_name'];
            $officeName = onboardingResolveOffice($conn, $record['office_name']);
            $manager = 'Not Assigned';
            $designation = $record['role_title'] ?: 'Employee';
            $role = $record['access_role'] ?: onboardingInferAccessRole($record['role_title']);
            $address = $record['current_address'] ?: ($record['permanent_address'] ?: 'Not Provided');
            $phone = onboardingNormalizePhone($record['phone'] ?: '0000000000');
            $email = onboardingNormalizeEmail($record['email']);
            $passwordHash = $record['portal_password_hash'];
            $punchIn = '09:30:00';
            $punchOut = '18:30:00';
            $breakTime = 60;
            $zeroAmount = 0.00;
            $salaryType = 'Monthly';
            $status = 'Active';
            $esic = '';
            $joinDate = $record['joining_date'];
            $department = $record['department'] ?: null;

            $documents = onboardingGetDocuments($conn, $onboardingId);
            $photoPath = null;
            $aadhaarFile = null;
            $panFile = null;
            foreach ($documents as $document) {
                if ($document['document_key'] === 'photo') {
                    $photoPath = $document['file_path'];
                } elseif ($document['document_key'] === 'aadhaar') {
                    $aadhaarFile = $document['file_path'];
                } elseif ($document['document_key'] === 'pan') {
                    $panFile = $document['file_path'];
                }
            }

            $stmt = $conn->prepare("
                INSERT INTO employees (
                    employee_id, username, password, name, email, phone, role, manager,
                    address, designation, office, punchin_time, punchout_time, break_time,
                    hourly_salary, daily_salary, salary, salary_type, status, esic,
                    date_of_joining, department, bank_account, ifsc_code, adhar_number,
                    pan_number, epf_number, photo, adhar_file, pan_file, dob
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )
            ");
            $aadhaarNumber = strtoupper(trim((string) ($record['aadhaar_number'] ?? '')));
            $panNumber = strtoupper(trim((string) ($record['pan_number'] ?? '')));
            $uanNumber = strtoupper(trim((string) ($record['uan_number'] ?? '')));
            $stmt->bind_param(
                "sssssssssssssidddssssssssssssss",
                $employeeCode,
                $employeeCode,
                $passwordHash,
                $employeeName,
                $email,
                $phone,
                $role,
                $manager,
                $address,
                $designation,
                $officeName,
                $punchIn,
                $punchOut,
                $breakTime,
                $zeroAmount,
                $zeroAmount,
                $zeroAmount,
                $salaryType,
                $status,
                $esic,
                $joinDate,
                $department,
                $record['bank_account_number'],
                $record['bank_ifsc_code'],
                $aadhaarNumber,
                $panNumber,
                $uanNumber,
                $photoPath,
                $aadhaarFile,
                $panFile,
                $record['date_of_birth']
            );
            $stmt->execute();
            $employeePk = (int) $stmt->insert_id;
            $stmt->close();

            $approvedAt = onboardingNow();
            $approvedStatus = 'Approved';
            $stmt = $conn->prepare("
                UPDATE onboarding_records
                SET status = ?, reviewed_at = ?, updated_at = ?
                WHERE id = ?
            ");
            $stmt->bind_param("sssi", $approvedStatus, $approvedAt, $approvedAt, $onboardingId);
            $stmt->execute();
            $stmt->close();

            $activeStatus = 'Active';
            $stmt = $conn->prepare("
                UPDATE onboarding_records
                SET status = ?, activated_at = ?, activated_employee_id = ?, updated_at = ?
                WHERE id = ?
            ");
            $stmt->bind_param("ssisi", $activeStatus, $approvedAt, $employeePk, $approvedAt, $onboardingId);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            return $employeeCode;
        } catch (Throwable $e) {
            $conn->rollback();
            throw $e;
        }
    }
}

if (!function_exists('onboardingReviewDecision')) {
    function onboardingReviewDecision(mysqli $conn, int $onboardingId, string $decision, string $comment = ''): string
    {
        $decision = strtolower(trim($decision));
        $now = onboardingNow();

        if ($decision === 'reject') {
            $status = 'Rejected';
            $stmt = $conn->prepare("
                UPDATE onboarding_records
                SET status = ?, review_comment = ?, reviewed_at = ?, updated_at = ?
                WHERE id = ?
            ");
            $stmt->bind_param("ssssi", $status, $comment, $now, $now, $onboardingId);
            $stmt->execute();
            $stmt->close();
            return $status;
        }

        if ($decision === 'approve') {
            $stmt = $conn->prepare("UPDATE onboarding_records SET review_comment = ?, updated_at = ? WHERE id = ?");
            $stmt->bind_param("ssi", $comment, $now, $onboardingId);
            $stmt->execute();
            $stmt->close();
            onboardingActivateEmployee($conn, $onboardingId);
            return 'Active';
        }

        throw new RuntimeException('Invalid review decision.');
    }
}
