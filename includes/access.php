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

function getCurrentStudent($conn) {
    if (!isStudentLoggedIn()) {
        return null;
    }

    $studentId = getStudentId();
    $stmt = $conn->prepare(
        "SELECT s.*,
                (SELECT MAX(expiry_date) FROM plans p WHERE p.student_id = s.id AND p.payment_status = 'paid') AS latest_plan_expiry
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

function getStudentByEmail($conn, $email) {
    $stmt = $conn->prepare("SELECT * FROM students WHERE email = ?");

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
    if (!dbTableExists($conn, 'plans')) {
        return null;
    }

    $stmt = $conn->prepare(
        "SELECT * FROM plans
         WHERE student_id = ? AND payment_status = 'paid'
         ORDER BY expiry_date DESC, id DESC
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

    $expiryDate = $student['expiry_date'] ?? null;

    if (empty($expiryDate)) {
        return false;
    }

    return strtotime($expiryDate . ' 23:59:59') >= time();
}

function syncStudentPlanStatus($conn, $studentId) {
    $plan = getLatestPaidPlan($conn, $studentId);

    if ($plan && strtotime($plan['expiry_date'] . ' 23:59:59') >= time()) {
        $stmt = $conn->prepare(
            "UPDATE students
             SET status = 1, plan_start_date = ?, expiry_date = ?
             WHERE id = ?"
        );
        $stmt->bind_param('ssi', $plan['start_date'], $plan['expiry_date'], $studentId);
        $stmt->execute();
        $stmt->close();
    } elseif ($plan) {
        $stmt = $conn->prepare(
            "UPDATE students
             SET status = 1, plan_start_date = ?, expiry_date = ?
             WHERE id = ?"
        );
        $stmt->bind_param('ssi', $plan['start_date'], $plan['expiry_date'], $studentId);
        $stmt->execute();
        $stmt->close();
    }

    return getStudentById($conn, $studentId);
}

function getStudentById($conn, $studentId) {
    $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");

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

function getGuestAttemptsUsed() {
    initSession();
    return (int) ($_SESSION['guest_test_attempts'] ?? 0);
}

function setGuestAttemptsUsed($count) {
    initSession();
    $_SESSION['guest_test_attempts'] = max(0, (int) $count);
}

function syncGuestAttemptsWithClient($clientCount) {
    if ($clientCount === null || $clientCount === '') {
        return getGuestAttemptsUsed();
    }

    $clientCount = max(0, min(GUEST_TEST_LIMIT, (int) $clientCount));
    $serverCount = getGuestAttemptsUsed();

    if ($clientCount > $serverCount) {
        setGuestAttemptsUsed($clientCount);
        return $clientCount;
    }

    return $serverCount;
}

function incrementGuestAttemptsUsed() {
    $used = getGuestAttemptsUsed() + 1;
    setGuestAttemptsUsed($used);
    return $used;
}

function getGuestTestsRemaining() {
    return max(0, GUEST_TEST_LIMIT - getGuestAttemptsUsed());
}

function guestHasTestsRemaining() {
    return getGuestTestsRemaining() > 0;
}

function getAccessContext($conn) {
    $student = null;
    $isLoggedIn = isStudentLoggedIn();

    if ($isLoggedIn) {
        $student = syncStudentPlanStatus($conn, getStudentId());
    }

    return [
        'is_logged_in' => $isLoggedIn,
        'student' => $student,
        'has_active_plan' => $isLoggedIn && hasActivePlan($student),
        'guest_attempts_used' => getGuestAttemptsUsed(),
        'guest_tests_remaining' => getGuestTestsRemaining(),
        'guest_session_id' => getGuestSessionId()
    ];
}

function requireTypingAccess($conn) {
    $context = getAccessContext($conn);

    if ($context['is_logged_in'] && !$context['has_active_plan']) {
        setFlash('auth_message', 'Your plan is inactive or expired. Please renew to continue.');
        redirect('payment.php');
    }

    if (!$context['is_logged_in'] && !$context['guest_tests_remaining']) {
        setFlash('auth_message', 'Your 5 free guest tests are finished. Create an account to continue.');
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
    $typedWords = $hasTypedWords ? $data['typed_words'] : 0;
    $accessType = $data['access_type'];

    if ($data['student_id'] !== null) {
        $sql = $hasTypedWords && $hasExamType
            ? "INSERT INTO test_attempts
               (student_id, guest_session_id, language, exam_type, paragraph_id, time_limit_seconds, wpm, accuracy, typed_words, access_type, created_at)
               VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
            : "INSERT INTO test_attempts
               (student_id, guest_session_id, language, paragraph_id, time_limit_seconds, wpm, accuracy, access_type, created_at)
               VALUES (?, NULL, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $studentId = (int) $data['student_id'];
        if ($hasTypedWords && $hasExamType) {
            $stmt->bind_param(
                'issiiddis',
                $studentId,
                $language,
                $examType,
                $paragraphId,
                $timeLimit,
                $wpm,
                $accuracy,
                $typedWords,
                $accessType
            );
        } else {
            $stmt->bind_param(
                'isiidds',
                $studentId,
                $language,
                $paragraphId,
                $timeLimit,
                $wpm,
                $accuracy,
                $accessType
            );
        }
    } else {
        $sql = $hasTypedWords && $hasExamType
            ? "INSERT INTO test_attempts
               (student_id, guest_session_id, language, exam_type, paragraph_id, time_limit_seconds, wpm, accuracy, typed_words, access_type, created_at)
               VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
            : "INSERT INTO test_attempts
               (student_id, guest_session_id, language, paragraph_id, time_limit_seconds, wpm, accuracy, access_type, created_at)
               VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $guestSessionId = $data['guest_session_id'];
        if ($hasTypedWords && $hasExamType) {
            $stmt->bind_param(
                'sssiiddis',
                $guestSessionId,
                $language,
                $examType,
                $paragraphId,
                $timeLimit,
                $wpm,
                $accuracy,
                $typedWords,
                $accessType
            );
        } else {
            $stmt->bind_param(
                'ssiidds',
                $guestSessionId,
                $language,
                $paragraphId,
                $timeLimit,
                $wpm,
                $accuracy,
                $accessType
            );
        }
    }

    $success = $stmt->execute();
    $attemptId = $success ? $stmt->insert_id : 0;
    $stmt->close();

    return $success ? $attemptId : false;
}

function createRazorpayOrder($receipt, $amountPaise) {
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
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json'
        ],
        CURLOPT_USERPWD => RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error || $httpCode >= 400) {
        logError('Razorpay order creation failed: ' . $error . ' | ' . $response, 'PAYMENT');
        return false;
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

        $sql = "SELECT language, "
            . ($hasExamType ? "exam_type" : "'typing' AS exam_type")
            . ", wpm, accuracy, "
            . ($hasTypedWords ? "typed_words" : "0 AS typed_words")
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
        $sql = "SELECT language, 'typing' AS exam_type, wpm, accuracy, 0 AS typed_words, created_at
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
