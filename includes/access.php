<?php
/**
 * Access control, plan checks, guest limits, and payment helpers
 */

function dbTableExists($conn, $tableName) {
    $databaseName = DB_NAME;
    $stmt = $conn->prepare(
        "SELECT 1
         FROM information_schema.tables
         WHERE table_schema = ? AND table_name = ?
         LIMIT 1"
    );

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('ss', $databaseName, $tableName);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    return $exists;
}

function dbColumnExists($conn, $tableName, $columnName) {
    $databaseName = DB_NAME;
    $stmt = $conn->prepare(
        "SELECT 1
         FROM information_schema.columns
         WHERE table_schema = ? AND table_name = ? AND column_name = ?
         LIMIT 1"
    );

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('sss', $databaseName, $tableName, $columnName);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    return $exists;
}

function dbColumnDetails($conn, $tableName, $columnName) {
    $databaseName = DB_NAME;
    $stmt = $conn->prepare(
        "SELECT data_type, column_type
         FROM information_schema.columns
         WHERE table_schema = ? AND table_name = ? AND column_name = ?
         LIMIT 1"
    );

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('sss', $databaseName, $tableName, $columnName);
    $stmt->execute();
    $details = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();

    return $details;
}

function planTableExists($conn) {
    return dbTableExists($conn, 'plans');
}

function getPaidPlanCondition($conn, $tableAlias = '') {
    $prefix = $tableAlias !== '' ? $tableAlias . '.' : '';

    if (!planTableExists($conn)) {
        return '1 = 0';
    }

    if (dbColumnExists($conn, 'plans', 'payment_status')) {
        return $prefix . "payment_status = 'paid'";
    }

    if (dbColumnExists($conn, 'plans', 'status')) {
        return $prefix . "status = 'active'";
    }

    return '1 = 1';
}

function isPlanExpiryActive($expiryDate) {
    if (empty($expiryDate)) {
        return false;
    }

    $expiry = trim((string) $expiryDate);
    $timestamp = strtotime($expiry);

    if ($timestamp === false) {
        return false;
    }

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiry)) {
        $timestamp = strtotime($expiry . ' 23:59:59');
    }

    return $timestamp >= time();
}

function calculatePlanDates($currentExpiryDate = null) {
    $startTimestamp = strtotime(date('Y-m-d 00:00:00'));

    if (isPlanExpiryActive($currentExpiryDate)) {
        $currentTimestamp = strtotime((string) $currentExpiryDate);

        if ($currentTimestamp !== false) {
            $startTimestamp = strtotime(date('Y-m-d 00:00:00', $currentTimestamp) . ' +1 day');
        }
    }

    $expiryTimestamp = strtotime('+' . max(PLAN_DURATION_DAYS - 1, 0) . ' days', $startTimestamp);

    return [
        'start_date' => date('Y-m-d 00:00:00', $startTimestamp),
        'expiry_date' => date('Y-m-d 23:59:59', $expiryTimestamp)
    ];
}

function getStudentStatusValue($conn, $isActive) {
    $details = dbColumnDetails($conn, 'students', 'status');
    $columnType = strtolower((string) ($details['column_type'] ?? ''));

    if (str_contains($columnType, "'active'") || str_contains($columnType, "'inactive'")) {
        return $isActive ? 'active' : 'inactive';
    }

    return $isActive ? 1 : 0;
}

function getStudentStatusBindType($conn) {
    $value = getStudentStatusValue($conn, true);
    return is_int($value) ? 'i' : 's';
}

function getPendingPlanStatusValue($conn) {
    $details = dbColumnDetails($conn, 'plans', 'payment_status');
    $columnType = strtolower((string) ($details['column_type'] ?? ''));

    if (str_contains($columnType, "'created'")) {
        return 'created';
    }

    return 'pending';
}

function updateStudentPlanAccess($conn, $studentId, $isActive, $startDate = null, $expiryDate = null) {
    $columns = ['status = ?'];
    $types = getStudentStatusBindType($conn);
    $values = [getStudentStatusValue($conn, $isActive)];

    if (dbColumnExists($conn, 'students', 'plan_start_date')) {
        $columns[] = 'plan_start_date = ?';
        $types .= 's';
        $values[] = $startDate;
    }

    if (dbColumnExists($conn, 'students', 'expiry_date')) {
        $columns[] = 'expiry_date = ?';
        $types .= 's';
        $values[] = $expiryDate;
    }

    $types .= 'i';
    $values[] = (int) $studentId;

    $stmt = $conn->prepare(
        "UPDATE students
         SET " . implode(', ', $columns) . "
         WHERE id = ?"
    );

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param($types, ...$values);
    $success = $stmt->execute();
    $stmt->close();

    return $success;
}

function getPlanPaymentLabel($plan) {
    if (empty($plan)) {
        return 'Not Activated';
    }

    $paymentMethod = strtolower((string) ($plan['payment_method'] ?? ''));

    if (in_array($paymentMethod, ['hand_cash', 'cash', 'manual', 'manual_cash'], true)) {
        return 'Manual Pay';
    }

    if ($paymentMethod === 'razorpay') {
        return 'Razorpay';
    }

    if ((float) ($plan['amount'] ?? 0) > 0) {
        return 'Razorpay';
    }

    if (!empty($plan['razorpay_payment_id']) || !empty($plan['razorpay_order_id'])) {
        return 'Razorpay';
    }

    $planName = strtolower((string) ($plan['plan_name'] ?? ''));

    if (str_contains($planName, 'cash') || str_contains($planName, 'manual')) {
        return 'Manual Pay';
    }

    return 'Manual Activation';
}

function getStudentSelectFields($conn, $studentAlias = 's') {
    $fields = ["{$studentAlias}.*"];

    if (planTableExists($conn)) {
        $condition = getPaidPlanCondition($conn, 'p');

        if (!dbColumnExists($conn, 'students', 'plan_start_date')) {
            $fields[] = "(SELECT p.start_date
                          FROM plans p
                          WHERE p.student_id = {$studentAlias}.id AND {$condition}
                          ORDER BY COALESCE(p.expiry_date, '0000-00-00') DESC, p.id DESC
                          LIMIT 1) AS plan_start_date";
        }

        if (!dbColumnExists($conn, 'students', 'expiry_date')) {
            $fields[] = "(SELECT p.expiry_date
                          FROM plans p
                          WHERE p.student_id = {$studentAlias}.id AND {$condition}
                          ORDER BY COALESCE(p.expiry_date, '0000-00-00') DESC, p.id DESC
                          LIMIT 1) AS expiry_date";
        }
    } else {
        if (!dbColumnExists($conn, 'students', 'plan_start_date')) {
            $fields[] = 'NULL AS plan_start_date';
        }

        if (!dbColumnExists($conn, 'students', 'expiry_date')) {
            $fields[] = 'NULL AS expiry_date';
        }
    }

    return implode(",\n                ", $fields);
}

function getCurrentStudent($conn) {
    if (!isStudentLoggedIn()) {
        return null;
    }

    return syncStudentPlanStatus($conn, getStudentId());
}

function getStudentByEmail($conn, $email) {
    $stmt = $conn->prepare(
        "SELECT " . getStudentSelectFields($conn, 's') . "
         FROM students s
         WHERE s.email = ?"
    );

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('s', $email);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();

    return $student;
}

function getLatestPaidPlan($conn, $studentId) {
    if (!planTableExists($conn)) {
        return null;
    }

    $condition = getPaidPlanCondition($conn);
    $hasPaymentMethod = dbColumnExists($conn, 'plans', 'payment_method');

    $stmt = $conn->prepare(
        "SELECT *,
                " . ($hasPaymentMethod ? 'payment_method' : "NULL AS payment_method") . "
         FROM plans
         WHERE student_id = ? AND {$condition}
         ORDER BY COALESCE(expiry_date, '0000-00-00') DESC, id DESC
         LIMIT 1"
    );

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $studentId);
    $stmt->execute();
    $plan = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();

    return $plan;
}

function hasActivePlan($student) {
    if (empty($student)) {
        return false;
    }

    return isPlanExpiryActive($student['expiry_date'] ?? null);
}

function syncStudentPlanStatus($conn, $studentId) {
    $plan = getLatestPaidPlan($conn, $studentId);

    if ($plan && isPlanExpiryActive($plan['expiry_date'] ?? null)) {
        updateStudentPlanAccess($conn, $studentId, true, $plan['start_date'] ?? null, $plan['expiry_date'] ?? null);
    } elseif ($plan) {
        updateStudentPlanAccess($conn, $studentId, false, $plan['start_date'] ?? null, $plan['expiry_date'] ?? null);
    } else {
        updateStudentPlanAccess($conn, $studentId, false, null, null);
    }

    return getStudentById($conn, $studentId);
}

function getStudentById($conn, $studentId) {
    $stmt = $conn->prepare(
        "SELECT " . getStudentSelectFields($conn, 's') . "
         FROM students s
         WHERE s.id = ?"
    );

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $studentId);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();

    return $student;
}

function getGuestSessionId() {
    initSession();

    if (empty($_SESSION['guest_session_id'])) {
        $_SESSION['guest_session_id'] = bin2hex(random_bytes(16));
    }

    return $_SESSION['guest_session_id'];
}

function getGuestAttemptCountFromDatabase($conn, $guestSessionId = null) {
    if (
        !$conn
        || !dbTableExists($conn, 'test_attempts')
        || !dbColumnExists($conn, 'test_attempts', 'guest_session_id')
    ) {
        return null;
    }

    $guestSessionId = $guestSessionId ?: getGuestSessionId();

    if ($guestSessionId === '') {
        return 0;
    }

    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS total
         FROM test_attempts
         WHERE guest_session_id = ?"
    );

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('s', $guestSessionId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc() ?: ['total' => 0];
    $stmt->close();

    return (int) ($row['total'] ?? 0);
}

function getGuestAttemptsUsed($conn = null) {
    initSession();
    $sessionCount = (int) ($_SESSION['guest_test_attempts'] ?? 0);
    $databaseCount = getGuestAttemptCountFromDatabase($conn);

    if ($databaseCount !== null) {
        $sessionCount = max($sessionCount, $databaseCount);
        $_SESSION['guest_test_attempts'] = $sessionCount;
    }

    return $sessionCount;
}

function setGuestAttemptsUsed($count) {
    initSession();
    $_SESSION['guest_test_attempts'] = max(0, min(GUEST_TEST_LIMIT, (int) $count));
}

function syncGuestAttemptsWithClient($clientCount, $conn = null) {
    if ($clientCount === null || $clientCount === '') {
        return getGuestAttemptsUsed($conn);
    }

    $clientCount = max(0, min(GUEST_TEST_LIMIT, (int) $clientCount));
    $serverCount = getGuestAttemptsUsed($conn);

    if ($clientCount > $serverCount) {
        setGuestAttemptsUsed($clientCount);
        return $clientCount;
    }

    return $serverCount;
}

function incrementGuestAttemptsUsed($conn = null) {
    $databaseCount = getGuestAttemptCountFromDatabase($conn);

    if ($databaseCount !== null) {
        setGuestAttemptsUsed($databaseCount);
        return $databaseCount;
    }

    $used = getGuestAttemptsUsed() + 1;
    setGuestAttemptsUsed($used);
    return $used;
}

function getGuestTestsRemaining($conn = null) {
    return max(0, GUEST_TEST_LIMIT - getGuestAttemptsUsed($conn));
}

function guestHasTestsRemaining($conn = null) {
    return getGuestTestsRemaining($conn) > 0;
}

function getStudentFreeAttemptCount($conn, $studentId) {
    $studentId = (int) $studentId;

    if ($studentId <= 0) {
        return 0;
    }

    if (dbTableExists($conn, 'test_attempts') && dbColumnExists($conn, 'test_attempts', 'student_id')) {
        $hasAccessType = dbColumnExists($conn, 'test_attempts', 'access_type');
        $sql = $hasAccessType
            ? "SELECT COUNT(*) AS total
               FROM test_attempts
               WHERE student_id = ? AND access_type = 'guest'"
            : "SELECT COUNT(*) AS total
               FROM test_attempts
               WHERE student_id = ?";

        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param('i', $studentId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc() ?: ['total' => 0];
            $stmt->close();

            return (int) ($row['total'] ?? 0);
        }
    }

    if (dbTableExists($conn, 'results') && dbColumnExists($conn, 'results', 'student_id')) {
        $stmt = $conn->prepare(
            "SELECT COUNT(*) AS total
             FROM results
             WHERE student_id = ?"
        );

        if ($stmt) {
            $stmt->bind_param('i', $studentId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc() ?: ['total' => 0];
            $stmt->close();

            return (int) ($row['total'] ?? 0);
        }
    }

    return 0;
}

function getStudentFreeTestsRemaining($conn, $studentId) {
    return max(0, GUEST_TEST_LIMIT - getStudentFreeAttemptCount($conn, $studentId));
}

function getTypingResultCleanupStateFile() {
    $directory = ROOT_PATH . '/logs/maintenance';

    if (!is_dir($directory)) {
        @mkdir($directory, 0755, true);
    }

    return $directory . '/typing-result-retention.json';
}

function shouldRunTypingResultCleanup($force = false) {
    if ($force || ATTEMPT_RETENTION_DAYS <= 0) {
        return true;
    }

    $stateFile = getTypingResultCleanupStateFile();

    if (!is_file($stateFile)) {
        return true;
    }

    $state = json_decode((string) @file_get_contents($stateFile), true);
    $lastRun = is_array($state) ? (int) ($state['last_run'] ?? 0) : 0;

    return $lastRun <= 0 || (time() - $lastRun) >= ATTEMPT_RETENTION_CLEANUP_INTERVAL;
}

function markTypingResultCleanupRun($deletedCounts) {
    $stateFile = getTypingResultCleanupStateFile();
    @file_put_contents($stateFile, json_encode([
        'last_run' => time(),
        'deleted' => $deletedCounts
    ], JSON_UNESCAPED_SLASHES), LOCK_EX);
}

function pruneExpiredTypingResults($conn, $force = false) {
    if (!$conn || ATTEMPT_RETENTION_DAYS <= 0 || !shouldRunTypingResultCleanup($force)) {
        return;
    }

    $cutoff = date('Y-m-d H:i:s', strtotime('-' . ATTEMPT_RETENTION_DAYS . ' days'));
    $deletedCounts = [
        'test_attempts' => 0,
        'results' => 0
    ];

    if (dbTableExists($conn, 'test_attempts') && dbColumnExists($conn, 'test_attempts', 'created_at')) {
        $stmt = $conn->prepare("DELETE FROM test_attempts WHERE created_at < ?");

        if ($stmt) {
            $stmt->bind_param('s', $cutoff);
            $stmt->execute();
            $deletedCounts['test_attempts'] = max(0, (int) $stmt->affected_rows);
            $stmt->close();
        }
    }

    if (dbTableExists($conn, 'results') && dbColumnExists($conn, 'results', 'created_at')) {
        $stmt = $conn->prepare("DELETE FROM results WHERE created_at < ?");

        if ($stmt) {
            $stmt->bind_param('s', $cutoff);
            $stmt->execute();
            $deletedCounts['results'] = max(0, (int) $stmt->affected_rows);
            $stmt->close();
        }
    }

    markTypingResultCleanupRun($deletedCounts);

    if (($deletedCounts['test_attempts'] + $deletedCounts['results']) > 0) {
        logError(
            'Deleted expired typing records older than ' . ATTEMPT_RETENTION_DAYS . ' days. '
            . 'test_attempts=' . $deletedCounts['test_attempts']
            . ', results=' . $deletedCounts['results'],
            'MAINTENANCE'
        );
    }
}

function linkGuestAttemptsToStudent($conn, $studentId) {
    if (
        !$conn
        || !dbTableExists($conn, 'test_attempts')
        || !dbColumnExists($conn, 'test_attempts', 'guest_session_id')
        || !dbColumnExists($conn, 'test_attempts', 'student_id')
    ) {
        return 0;
    }

    $studentId = (int) $studentId;
    $guestSessionId = getGuestSessionId();

    if ($studentId <= 0 || $guestSessionId === '') {
        return 0;
    }

    $stmt = $conn->prepare(
        "UPDATE test_attempts
         SET student_id = ?
         WHERE guest_session_id = ?
           AND (student_id IS NULL OR student_id = 0)"
    );

    if (!$stmt) {
        return 0;
    }

    $stmt->bind_param('is', $studentId, $guestSessionId);
    $stmt->execute();
    $affectedRows = $stmt->affected_rows;
    $stmt->close();

    return max(0, (int) $affectedRows);
}

function getAccessContext($conn) {
    $student = null;
    $isLoggedIn = isStudentLoggedIn();
    $guestAttemptsUsed = getGuestAttemptsUsed($conn);
    $freeAttemptsUsed = $guestAttemptsUsed;
    $freeTestsRemaining = max(0, GUEST_TEST_LIMIT - $guestAttemptsUsed);
    $hasActivePlan = false;

    if ($isLoggedIn) {
        $student = syncStudentPlanStatus($conn, getStudentId());

        if ($student) {
            $hasActivePlan = hasActivePlan($student);
            $freeAttemptsUsed = getStudentFreeAttemptCount($conn, (int) $student['id']);
            $freeTestsRemaining = getStudentFreeTestsRemaining($conn, (int) $student['id']);
        }
    }

    return [
        'is_logged_in' => $isLoggedIn,
        'student' => $student,
        'has_active_plan' => $hasActivePlan,
        'free_attempts_used' => $freeAttemptsUsed,
        'free_tests_remaining' => $freeTestsRemaining,
        'guest_attempts_used' => $guestAttemptsUsed,
        'guest_tests_remaining' => $freeTestsRemaining,
        'guest_session_id' => getGuestSessionId()
    ];
}

function requireTypingAccess($conn) {
    $context = getAccessContext($conn);

    if ($context['is_logged_in'] && !$context['has_active_plan'] && $context['free_tests_remaining'] <= 0) {
        setFlash('auth_message', 'Your 5 free tests are finished. Activate your plan to continue.');
        redirect('payment.php');
    }

    if (!$context['is_logged_in'] && !$context['guest_tests_remaining']) {
        setFlash('auth_message', 'Your 5 free tests are finished. Create an account and activate your plan to continue.');
        redirect('account/register.php');
    }

    return $context;
}

function recordTestAttempt($conn, $data) {
    if (!dbTableExists($conn, 'test_attempts')) {
        if ($data['student_id'] !== null && dbTableExists($conn, 'results')) {
            $stmt = $conn->prepare(
                "INSERT INTO results (student_id, language, wpm, accuracy)
                 VALUES (?, ?, ?, ?)"
            );

            if (!$stmt) {
                return false;
            }

            $studentId = (int) $data['student_id'];
            $language = $data['language'];
            $wpm = $data['wpm'];
            $accuracy = $data['accuracy'];
            $stmt->bind_param('isdd', $studentId, $language, $wpm, $accuracy);
            $success = $stmt->execute();
            $attemptId = $success ? $stmt->insert_id : 0;
            $stmt->close();

            return $success ? $attemptId : false;
        }

        logError('test_attempts table missing; guest attempt stored in session only', 'SCHEMA');
        return time();
    }

    $language = $data['language'];
    $examType = $data['exam_type'];
    $paragraphId = $data['paragraph_id'];
    $timeLimit = $data['time_limit_seconds'];
    $wpm = $data['wpm'];
    $accuracy = $data['accuracy'];
    $hasTypedWords = dbColumnExists($conn, 'test_attempts', 'typed_words');
    $hasExamType = dbColumnExists($conn, 'test_attempts', 'exam_type');
    $hasGuestSessionId = dbColumnExists($conn, 'test_attempts', 'guest_session_id');
    $hasAccessType = dbColumnExists($conn, 'test_attempts', 'access_type');
    $typedWords = $hasTypedWords ? $data['typed_words'] : 0;
    $accessType = $data['access_type'];
    $columns = [];
    $placeholders = [];
    $types = '';
    $values = [];

    if (dbColumnExists($conn, 'test_attempts', 'student_id')) {
        $columns[] = 'student_id';
        if ($data['student_id'] !== null) {
            $placeholders[] = '?';
            $types .= 'i';
            $values[] = (int) $data['student_id'];
        } else {
            $placeholders[] = 'NULL';
        }
    }

    if ($hasGuestSessionId) {
        $columns[] = 'guest_session_id';
        if ($data['student_id'] === null) {
            $placeholders[] = '?';
            $types .= 's';
            $values[] = (string) $data['guest_session_id'];
        } else {
            $placeholders[] = 'NULL';
        }
    }

    $columns[] = 'language';
    $placeholders[] = '?';
    $types .= 's';
    $values[] = $language;

    if ($hasExamType) {
        $columns[] = 'exam_type';
        $placeholders[] = '?';
        $types .= 's';
        $values[] = $examType;
    }

    if (dbColumnExists($conn, 'test_attempts', 'paragraph_id')) {
        $columns[] = 'paragraph_id';
        $placeholders[] = '?';
        $types .= 'i';
        $values[] = $paragraphId;
    }

    if (dbColumnExists($conn, 'test_attempts', 'time_limit_seconds')) {
        $columns[] = 'time_limit_seconds';
        $placeholders[] = '?';
        $types .= 'i';
        $values[] = $timeLimit;
    }

    $columns[] = 'wpm';
    $placeholders[] = '?';
    $types .= 'd';
    $values[] = $wpm;

    $columns[] = 'accuracy';
    $placeholders[] = '?';
    $types .= 'd';
    $values[] = $accuracy;

    if ($hasTypedWords) {
        $columns[] = 'typed_words';
        $placeholders[] = '?';
        $types .= 'i';
        $values[] = $typedWords;
    }

    if ($hasAccessType) {
        $columns[] = 'access_type';
        $placeholders[] = '?';
        $types .= 's';
        $values[] = $accessType;
    }

    if (dbColumnExists($conn, 'test_attempts', 'created_at')) {
        $columns[] = 'created_at';
        $placeholders[] = 'NOW()';
    }

    $stmt = $conn->prepare(
        "INSERT INTO test_attempts (" . implode(', ', $columns) . ")
         VALUES (" . implode(', ', $placeholders) . ")"
    );

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param($types, ...$values);

    $success = $stmt->execute();
    $attemptId = $success ? $stmt->insert_id : 0;
    $stmt->close();

    return $success ? $attemptId : false;
}

function createRazorpayOrder($receipt, $amountPaise) {
    if (!function_exists('curl_init')) {
        logError('cURL extension is not available for Razorpay order creation.', 'PAYMENT');

        return [
            'error_message' => 'Payment gateway is not available on this server.'
        ];
    }

    $payload = json_encode([
        'amount' => $amountPaise,
        'currency' => PAYMENT_CURRENCY,
        'receipt' => $receipt,
        'payment_capture' => 1
    ]);

    $ch = curl_init('https://api.razorpay.com/v1/orders');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json'
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => APP_NAME . '/' . APP_VERSION,
        CURLOPT_USERPWD => RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error || $httpCode >= 400) {
        $decodedResponse = json_decode((string) $response, true);
        $errorMessage = $decodedResponse['error']['description'] ?? $error ?? 'Unable to create Razorpay order';
        logError('Razorpay order creation failed: ' . $error . ' | ' . $response, 'PAYMENT');

        return [
            'error_message' => $errorMessage,
            'http_code' => $httpCode
        ];
    }

    return json_decode($response, true);
}

function verifyRazorpaySignature($orderId, $paymentId, $signature) {
    $generated = hash_hmac('sha256', $orderId . '|' . $paymentId, RAZORPAY_KEY_SECRET);
    return hash_equals($generated, $signature);
}

function getStudentAttemptsResult($conn, $studentId, $limit = 20) {
    $limit = max(1, (int) $limit);

    if (dbTableExists($conn, 'test_attempts')) {
        $hasExamType = dbColumnExists($conn, 'test_attempts', 'exam_type');
        $hasTypedWords = dbColumnExists($conn, 'test_attempts', 'typed_words');
        $hasTimeLimitSeconds = dbColumnExists($conn, 'test_attempts', 'time_limit_seconds');
        $hasAccessType = dbColumnExists($conn, 'test_attempts', 'access_type');
        $typedWordsSelect = "0 AS typed_words";

        if ($hasTypedWords && $hasTimeLimitSeconds) {
            $typedWordsSelect = "COALESCE(NULLIF(typed_words, 0), CASE WHEN time_limit_seconds > 0 THEN ROUND((wpm * time_limit_seconds) / 60) ELSE 0 END) AS typed_words";
        } elseif ($hasTypedWords) {
            $typedWordsSelect = "typed_words";
        } elseif ($hasTimeLimitSeconds) {
            $typedWordsSelect = "CASE WHEN time_limit_seconds > 0 THEN ROUND((wpm * time_limit_seconds) / 60) ELSE 0 END AS typed_words";
        }

        $sql = "SELECT language, "
            . ($hasExamType ? "exam_type" : "'typing' AS exam_type")
            . ", wpm, accuracy, "
            . $typedWordsSelect
            . ", " . ($hasAccessType ? "access_type" : "'paid' AS access_type")
            . ", created_at
               FROM test_attempts
               WHERE student_id = ?
               ORDER BY id DESC
               LIMIT {$limit}";

        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param('i', $studentId);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
            return $result;
        }
    }

    if (dbTableExists($conn, 'results')) {
        $hasTypedWords = dbColumnExists($conn, 'results', 'typed_words');
        $hasTimeLimitSeconds = dbColumnExists($conn, 'results', 'time_limit_seconds');
        $typedWordsSelect = "0 AS typed_words";

        if ($hasTypedWords && $hasTimeLimitSeconds) {
            $typedWordsSelect = "COALESCE(NULLIF(typed_words, 0), CASE WHEN time_limit_seconds > 0 THEN ROUND((wpm * time_limit_seconds) / 60) ELSE 0 END) AS typed_words";
        } elseif ($hasTypedWords) {
            $typedWordsSelect = "typed_words";
        } elseif ($hasTimeLimitSeconds) {
            $typedWordsSelect = "CASE WHEN time_limit_seconds > 0 THEN ROUND((wpm * time_limit_seconds) / 60) ELSE 0 END AS typed_words";
        }

        $sql = "SELECT language, 'typing' AS exam_type, wpm, accuracy, {$typedWordsSelect}, 'paid' AS access_type, created_at
                FROM results
                WHERE student_id = ?
                ORDER BY id DESC
                LIMIT {$limit}";

        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param('i', $studentId);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
            return $result;
        }
    }

    return false;
}

function getStudentAttemptSummary($conn, $studentId) {
    $summary = [
        'total' => 0,
        'guest_total' => 0,
        'paid_total' => 0
    ];

    if (dbTableExists($conn, 'test_attempts')) {
        $hasAccessType = dbColumnExists($conn, 'test_attempts', 'access_type');
        $sql = $hasAccessType
            ? "SELECT COUNT(*) AS total,
                      SUM(CASE WHEN access_type = 'guest' THEN 1 ELSE 0 END) AS guest_total,
                      SUM(CASE WHEN access_type = 'paid' THEN 1 ELSE 0 END) AS paid_total
               FROM test_attempts
               WHERE student_id = ?"
            : "SELECT COUNT(*) AS total, 0 AS guest_total, COUNT(*) AS paid_total
               FROM test_attempts
               WHERE student_id = ?";

        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param('i', $studentId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc() ?: $summary;
            $stmt->close();

            return [
                'total' => (int) ($row['total'] ?? 0),
                'guest_total' => (int) ($row['guest_total'] ?? 0),
                'paid_total' => (int) ($row['paid_total'] ?? 0)
            ];
        }
    }

    if (dbTableExists($conn, 'results')) {
        $stmt = $conn->prepare(
            "SELECT COUNT(*) AS total
             FROM results
             WHERE student_id = ?"
        );

        if ($stmt) {
            $stmt->bind_param('i', $studentId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc() ?: ['total' => 0];
            $stmt->close();

            $summary['total'] = (int) ($row['total'] ?? 0);
            $summary['paid_total'] = $summary['total'];
        }
    }

    return $summary;
}

function getStudentAttemptCount($conn, $studentId) {
    $summary = getStudentAttemptSummary($conn, $studentId);
    return (int) ($summary['total'] ?? 0);
}
