<?php

if (!function_exists('ensureAdvanceSalaryRequestTable')) {
    function ensureAdvanceSalaryRequestTable(mysqli $conn): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS advance_salary_requests (
                id INT AUTO_INCREMENT PRIMARY KEY,
                employee_id INT NOT NULL,
                request_year SMALLINT NOT NULL,
                request_month TINYINT NOT NULL,
                request_type VARCHAR(20) NOT NULL DEFAULT 'monthly',
                amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                reason TEXT DEFAULT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'Pending',
                applied_at DATETIME NOT NULL,
                approved_rejected_at DATETIME DEFAULT NULL,
                approved_by_id INT DEFAULT NULL,
                approved_by_name VARCHAR(255) DEFAULT NULL,
                approved_by_type VARCHAR(50) DEFAULT NULL,
                reject_reason TEXT DEFAULT NULL,
                payroll_applied_at DATETIME DEFAULT NULL,
                payroll_salary_id INT DEFAULT NULL,
                KEY idx_employee_month (employee_id, request_year, request_month),
                KEY idx_employee_type (employee_id, request_year, request_type),
                KEY idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ";

        $conn->query($sql);

        $requestTypeExists = false;
        $requestTypeResult = $conn->query("SHOW COLUMNS FROM advance_salary_requests LIKE 'request_type'");
        if ($requestTypeResult instanceof mysqli_result) {
            $requestTypeExists = $requestTypeResult->num_rows > 0;
            $requestTypeResult->free();
        }
        if (!$requestTypeExists) {
            $conn->query("ALTER TABLE advance_salary_requests ADD COLUMN request_type VARCHAR(20) NOT NULL DEFAULT 'monthly' AFTER request_month");
        }

        $allocationSql = "
            CREATE TABLE IF NOT EXISTS advance_salary_request_allocations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                request_id INT NOT NULL,
                employee_id INT NOT NULL,
                payroll_year SMALLINT NOT NULL,
                payroll_month TINYINT NOT NULL,
                amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                notes TEXT DEFAULT NULL,
                created_at DATETIME NOT NULL,
                created_by_id INT DEFAULT NULL,
                created_by_name VARCHAR(255) DEFAULT NULL,
                created_by_type VARCHAR(50) DEFAULT NULL,
                payroll_applied_at DATETIME DEFAULT NULL,
                payroll_salary_id INT DEFAULT NULL,
                UNIQUE KEY uniq_request_month (request_id, payroll_year, payroll_month),
                KEY idx_employee_payroll (employee_id, payroll_year, payroll_month),
                KEY idx_request_id (request_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ";

        $conn->query($allocationSql);
    }
}

if (!function_exists('advanceSalaryPayrollExists')) {
    function advanceSalaryPayrollExists(mysqli $conn, int $employeeId, int $year, int $month): bool
    {
        $stmt = $conn->prepare("
            SELECT id
            FROM salary
            WHERE employee_id = ?
              AND year = ?
              AND month = ?
            LIMIT 1
        ");
        $stmt->bind_param("iii", $employeeId, $year, $month);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result && $result->num_rows > 0;
        $stmt->close();

        return $exists;
    }
}

if (!function_exists('getApprovedAdvanceSalaryAmount')) {
    function getApprovedAdvanceSalaryAmount(mysqli $conn, int $employeeId, int $year, int $month): float
    {
        ensureAdvanceSalaryRequestTable($conn);

        $totalAmount = 0.0;

        $monthlyStmt = $conn->prepare("
            SELECT COALESCE(SUM(amount), 0) AS total_amount
            FROM advance_salary_requests
            WHERE employee_id = ?
              AND request_year = ?
              AND request_month = ?
              AND status = 'Approved'
              AND request_type = 'monthly'
        ");
        $monthlyStmt->bind_param("iii", $employeeId, $year, $month);
        $monthlyStmt->execute();
        $monthlyResult = $monthlyStmt->get_result();
        $monthlyRow = $monthlyResult ? $monthlyResult->fetch_assoc() : null;
        $monthlyStmt->close();
        $totalAmount += (float) ($monthlyRow['total_amount'] ?? 0);

        $allocationStmt = $conn->prepare("
            SELECT COALESCE(SUM(a.amount), 0) AS total_amount
            FROM advance_salary_request_allocations a
            INNER JOIN advance_salary_requests r ON r.id = a.request_id
            WHERE a.employee_id = ?
              AND a.payroll_year = ?
              AND a.payroll_month = ?
              AND r.status = 'Approved'
              AND r.request_type = 'yearly'
        ");
        $allocationStmt->bind_param("iii", $employeeId, $year, $month);
        $allocationStmt->execute();
        $allocationResult = $allocationStmt->get_result();
        $allocationRow = $allocationResult ? $allocationResult->fetch_assoc() : null;
        $allocationStmt->close();
        $totalAmount += (float) ($allocationRow['total_amount'] ?? 0);

        return round($totalAmount, 2);
    }
}

if (!function_exists('hasApprovedAdvanceSalaryRequest')) {
    function hasApprovedAdvanceSalaryRequest(mysqli $conn, int $employeeId, int $year, int $month): bool
    {
        return getApprovedAdvanceSalaryAmount($conn, $employeeId, $year, $month) > 0;
    }
}

if (!function_exists('getEffectiveAdvanceSalaryAmount')) {
    function getEffectiveAdvanceSalaryAmount(
        mysqli $conn,
        int $employeeId,
        int $year,
        int $month,
        float $defaultAdvance = 0.0
    ): float {
        if (hasApprovedAdvanceSalaryRequest($conn, $employeeId, $year, $month)) {
            return getApprovedAdvanceSalaryAmount($conn, $employeeId, $year, $month);
        }

        return round($defaultAdvance, 2);
    }
}

if (!function_exists('markAdvanceSalaryRequestsApplied')) {
    function markAdvanceSalaryRequestsApplied(mysqli $conn, int $employeeId, int $year, int $month, int $salaryId): void
    {
        ensureAdvanceSalaryRequestTable($conn);

        $appliedAt = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("
            UPDATE advance_salary_requests
            SET payroll_applied_at = ?,
                payroll_salary_id = ?
            WHERE employee_id = ?
              AND request_year = ?
              AND request_month = ?
              AND status = 'Approved'
        ");
        $stmt->bind_param("siiii", $appliedAt, $salaryId, $employeeId, $year, $month);
        $stmt->execute();
        $stmt->close();

        $allocationStmt = $conn->prepare("
            UPDATE advance_salary_request_allocations
            SET payroll_applied_at = ?,
                payroll_salary_id = ?
            WHERE employee_id = ?
              AND payroll_year = ?
              AND payroll_month = ?
        ");
        $allocationStmt->bind_param("siiii", $appliedAt, $salaryId, $employeeId, $year, $month);
        $allocationStmt->execute();
        $allocationStmt->close();
    }
}

if (!function_exists('getAdvanceSalaryAllocatedTotal')) {
    function getAdvanceSalaryAllocatedTotal(mysqli $conn, int $requestId): float
    {
        ensureAdvanceSalaryRequestTable($conn);

        $stmt = $conn->prepare("
            SELECT COALESCE(SUM(amount), 0) AS total_amount
            FROM advance_salary_request_allocations
            WHERE request_id = ?
        ");
        $stmt->bind_param("i", $requestId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return round((float) ($row['total_amount'] ?? 0), 2);
    }
}
