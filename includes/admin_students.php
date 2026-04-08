<?php
/**
 * Admin student management helpers.
 */

function adminGetFirstExistingColumn($conn, $tableName, $candidates) {
    foreach ($candidates as $candidate) {
        if (dbColumnExists($conn, $tableName, $candidate)) {
            return $candidate;
        }
    }

    return null;
}

function getStudentContactColumn($conn) {
    return adminGetFirstExistingColumn($conn, 'students', [
        'contact_number',
        'contact_no',
        'contact',
        'phone',
        'phone_number',
        'mobile',
        'mobile_number',
        'whatsapp_number'
    ]);
}

function getAdminStudentContactValue($student) {
    foreach ([
        'contact_number',
        'contact_no',
        'contact',
        'phone',
        'phone_number',
        'mobile',
        'mobile_number',
        'whatsapp_number'
    ] as $field) {
        $value = trim((string) ($student[$field] ?? ''));

        if ($value !== '') {
            return $value;
        }
    }

    return '';
}

function formatAdminStudentContactValue($contactValue) {
    $contactValue = trim((string) $contactValue);

    if ($contactValue === '') {
        return '';
    }

    $digits = preg_replace('/\D+/', '', $contactValue);

    if ($digits === '') {
        return $contactValue;
    }

    if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
        $digits = substr($digits, 2);
    }

    if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
        $digits = substr($digits, 1);
    }

    return $digits;
}

function getAdminStudentDisplayContactValue($student) {
    return formatAdminStudentContactValue(getAdminStudentContactValue($student));
}

function getAdminStudentPaymentModeLabel($student) {
    $paymentLabel = strtolower((string) getPlanPaymentLabel($student));

    if (in_array($paymentLabel, ['Manual Pay', 'manual activation'], true)) {
        return 'Manual Pay';
    }

    if ($paymentLabel === 'razorpay') {
        return 'Online Pay';
    }

    return 'Not Paid';
}

function getAdminStudentListQuery($conn) {
    $hasExpiryDate = dbColumnExists($conn, 'students', 'expiry_date');
    $hasPlans = dbTableExists($conn, 'plans');
    $hasPlanName = $hasPlans && dbColumnExists($conn, 'plans', 'plan_name');
    $hasPlanAmount = $hasPlans && dbColumnExists($conn, 'plans', 'amount');
    $hasRazorpayOrderId = $hasPlans && dbColumnExists($conn, 'plans', 'razorpay_order_id');
    $hasRazorpayPaymentId = $hasPlans && dbColumnExists($conn, 'plans', 'razorpay_payment_id');
    $hasPaymentMethod = $hasPlans && dbColumnExists($conn, 'plans', 'payment_method');
    $hasAttemptAccessType = dbTableExists($conn, 'test_attempts') && dbColumnExists($conn, 'test_attempts', 'access_type');
    $contactColumn = getStudentContactColumn($conn);
    $planCondition = $hasPlans ? getPaidPlanCondition($conn, 'p') : '1 = 0';
    $expirySelect = $hasExpiryDate
        ? 's.expiry_date'
        : ($hasPlans
            ? "(SELECT p.expiry_date FROM plans p WHERE p.student_id = s.id AND {$planCondition} ORDER BY COALESCE(p.expiry_date, '0000-00-00') DESC, p.id DESC LIMIT 1)"
            : 'NULL');

    $attemptCountSql = '0';
    $guestAttemptCountSql = '0';
    $paidAttemptCountSql = '0';

    if (dbTableExists($conn, 'test_attempts') && dbColumnExists($conn, 'test_attempts', 'student_id')) {
        $attemptCountSql = '(SELECT COUNT(*) FROM test_attempts ta WHERE ta.student_id = s.id)';

        if ($hasAttemptAccessType) {
            $guestAttemptCountSql = "(SELECT COUNT(*) FROM test_attempts ta WHERE ta.student_id = s.id AND ta.access_type = 'guest')";
            $paidAttemptCountSql = "(SELECT COUNT(*) FROM test_attempts ta WHERE ta.student_id = s.id AND ta.access_type = 'paid')";
        } else {
            $paidAttemptCountSql = '(SELECT COUNT(*) FROM test_attempts ta WHERE ta.student_id = s.id)';
        }
    } elseif (dbTableExists($conn, 'results') && dbColumnExists($conn, 'results', 'student_id')) {
        $attemptCountSql = '(SELECT COUNT(*) FROM results r WHERE r.student_id = s.id)';
        $paidAttemptCountSql = '(SELECT COUNT(*) FROM results r WHERE r.student_id = s.id)';
    }

    return "
        SELECT
            s.id,
            s.name,
            s.email,
            s.status,
            " . ($contactColumn ? "s.{$contactColumn}" : 'NULL') . " AS contact_number,
            " . (dbColumnExists($conn, 'students', 'created_at') ? 's.created_at' : 'NULL') . " AS created_at,
            {$expirySelect} AS expiry_date,
            {$attemptCountSql} AS attempts_count,
            {$guestAttemptCountSql} AS guest_attempts_count,
            {$paidAttemptCountSql} AS paid_attempts_count,
            " . ($hasPlans && $hasPlanName ? "(SELECT p.plan_name FROM plans p WHERE p.student_id = s.id AND {$planCondition} ORDER BY COALESCE(p.expiry_date, '0000-00-00') DESC, p.id DESC LIMIT 1)" : 'NULL') . " AS plan_name,
            " . ($hasPlans && $hasPlanAmount ? "(SELECT p.amount FROM plans p WHERE p.student_id = s.id AND {$planCondition} ORDER BY COALESCE(p.expiry_date, '0000-00-00') DESC, p.id DESC LIMIT 1)" : '0') . " AS amount,
            " . ($hasPlans && $hasRazorpayOrderId ? "(SELECT p.razorpay_order_id FROM plans p WHERE p.student_id = s.id AND {$planCondition} ORDER BY COALESCE(p.expiry_date, '0000-00-00') DESC, p.id DESC LIMIT 1)" : 'NULL') . " AS razorpay_order_id,
            " . ($hasPlans && $hasRazorpayPaymentId ? "(SELECT p.razorpay_payment_id FROM plans p WHERE p.student_id = s.id AND {$planCondition} ORDER BY COALESCE(p.expiry_date, '0000-00-00') DESC, p.id DESC LIMIT 1)" : 'NULL') . " AS razorpay_payment_id,
            " . ($hasPlans && $hasPaymentMethod ? "(SELECT p.payment_method FROM plans p WHERE p.student_id = s.id AND {$planCondition} ORDER BY COALESCE(p.expiry_date, '0000-00-00') DESC, p.id DESC LIMIT 1)" : 'NULL') . " AS payment_method
        FROM students s
        ORDER BY s.id DESC
    ";
}

function getAdminStudentListRows($conn) {
    $result = $conn->query(getAdminStudentListQuery($conn));

    if (!$result) {
        return [];
    }

    $rows = [];

    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    $result->free();

    return $rows;
}

function deactivateStudentAccess($conn, $studentId) {
    $studentId = (int) $studentId;

    if ($studentId <= 0) {
        return false;
    }

    $expiredAt = date('Y-m-d H:i:s', time() - 60);

    if (planTableExists($conn) && dbColumnExists($conn, 'plans', 'student_id')) {
        $setClauses = [];
        $types = '';
        $values = [];

        if (dbColumnExists($conn, 'plans', 'expiry_date')) {
            $setClauses[] = 'expiry_date = ?';
            $types .= 's';
            $values[] = $expiredAt;
        }

        if (dbColumnExists($conn, 'plans', 'status')) {
            $setClauses[] = "status = 'expired'";
        }

        if (!empty($setClauses)) {
            $types .= 'i';
            $values[] = $studentId;
            $stmt = $conn->prepare(
                "UPDATE plans
                 SET " . implode(', ', $setClauses) . "
                 WHERE student_id = ? AND " . getPaidPlanCondition($conn)
            );

            if ($stmt) {
                $stmt->bind_param($types, ...$values);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    $student = getStudentById($conn, $studentId);
    $planStartDate = $student['plan_start_date'] ?? null;

    return updateStudentPlanAccess($conn, $studentId, false, $planStartDate, $expiredAt);
}

function deleteStudentWithRelations($conn, $studentId) {
    $studentId = (int) $studentId;

    if ($studentId <= 0) {
        return false;
    }

    try {
        $conn->begin_transaction();

        foreach (['test_attempts', 'results', 'plans'] as $tableName) {
            if (!dbTableExists($conn, $tableName) || !dbColumnExists($conn, $tableName, 'student_id')) {
                continue;
            }

            $stmt = $conn->prepare("DELETE FROM {$tableName} WHERE student_id = ?");

            if (!$stmt) {
                throw new RuntimeException('Failed to prepare delete for ' . $tableName);
            }

            $stmt->bind_param('i', $studentId);
            $stmt->execute();
            $stmt->close();
        }

        $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");

        if (!$stmt) {
            throw new RuntimeException('Failed to prepare student delete');
        }

        $stmt->bind_param('i', $studentId);
        $stmt->execute();
        $deletedRows = (int) $stmt->affected_rows;
        $stmt->close();

        if ($deletedRows < 1) {
            throw new RuntimeException('Student record not found during delete');
        }

        $conn->commit();
        return true;
    } catch (Throwable $exception) {
        $conn->rollback();

        if (function_exists('logError')) {
            logError('Student delete failed: ' . $exception->getMessage(), 'ADMIN');
        }

        return false;
    }
}
