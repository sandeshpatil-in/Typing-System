<?php
/**
 * Authentication security helpers for captcha and password resets.
 */

function getCaptchaSessionKey($scope) {
    return 'captcha_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', (string) $scope);
}

function isRecaptchaEnabled() {
    return trim((string) RECAPTCHA_SITE_KEY) !== '' && trim((string) RECAPTCHA_SECRET_KEY) !== '';
}

function getRecaptchaSiteKey() {
    return trim((string) RECAPTCHA_SITE_KEY);
}

function buildCaptchaChallenge() {
    $left = random_int(2, 9);
    $right = random_int(1, 9);
    $operator = random_int(0, 1) === 1 ? '+' : '-';

    if ($operator === '-' && $left < $right) {
        [$left, $right] = [$right, $left];
    }

    $answer = $operator === '+' ? ($left + $right) : ($left - $right);

    return [
        'question' => "What is {$left} {$operator} {$right}?",
        'answer' => (string) $answer,
        'created_at' => time()
    ];
}

function getCaptchaChallenge($scope, $forceRefresh = false) {
    initSession();
    $key = getCaptchaSessionKey($scope);
    $challenge = $_SESSION[$key] ?? null;

    if (
        $forceRefresh
        || !is_array($challenge)
        || empty($challenge['question'])
        || !isset($challenge['answer'])
        || (time() - (int) ($challenge['created_at'] ?? 0)) > CAPTCHA_EXPIRY_SECONDS
    ) {
        $challenge = buildCaptchaChallenge();
        $_SESSION[$key] = $challenge;
    }

    return $challenge;
}

function refreshCaptchaChallenge($scope) {
    return getCaptchaChallenge($scope, true);
}

function clearCaptchaChallenge($scope) {
    initSession();
    unset($_SESSION[getCaptchaSessionKey($scope)]);
}

function verifyCaptchaAnswer($scope, $answer) {
    initSession();
    $challenge = getCaptchaChallenge($scope);
    $providedAnswer = trim((string) $answer);

    if ($providedAnswer === '') {
        return false;
    }

    return hash_equals((string) $challenge['answer'], $providedAnswer);
}

function verifyRecaptchaToken($token) {
    if (!isRecaptchaEnabled()) {
        return false;
    }

    $token = trim((string) $token);

    if ($token === '') {
        return false;
    }

    $payload = http_build_query([
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $token,
        'remoteip' => getClientIp()
    ]);
    $responseBody = '';

    if (function_exists('curl_init')) {
        $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded'
            ]
        ]);
        $responseBody = (string) curl_exec($ch);
        curl_close($ch);
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $payload,
                'timeout' => 15
            ]
        ]);
        $responseBody = (string) @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
    }

    if ($responseBody === '') {
        if (function_exists('logError')) {
            logError('reCAPTCHA verification request failed.', 'AUTH');
        }

        return false;
    }

    $decoded = json_decode($responseBody, true);

    if (!is_array($decoded) || empty($decoded['success'])) {
        if (function_exists('logError')) {
            $errors = isset($decoded['error-codes']) && is_array($decoded['error-codes'])
                ? implode(', ', $decoded['error-codes'])
                : 'unknown';
            logError('reCAPTCHA verification failed: ' . $errors, 'AUTH');
        }

        return false;
    }

    $currentHost = strtolower(trim((string) ($_SERVER['HTTP_HOST'] ?? '')));
    $currentHost = preg_replace('/:\d+$/', '', $currentHost);
    $responseHost = strtolower(trim((string) ($decoded['hostname'] ?? '')));

    if ($currentHost !== '' && $responseHost !== '' && !hash_equals($currentHost, $responseHost)) {
        if (function_exists('logError')) {
            logError('reCAPTCHA hostname mismatch: expected ' . $currentHost . ', got ' . $responseHost, 'AUTH');
        }

        return false;
    }

    return true;
}

function verifyHumanVerification($scope, $captchaAnswer = null, $recaptchaToken = null) {
    // Captcha disabled sitewide per current configuration.
    return true;
}

function refreshHumanVerification($scope) {
    return true;
}

function clearHumanVerification($scope) {
    return true;
}

function sanitizeMailHeaderValue($value) {
    return trim((string) preg_replace('/[\r\n]+/', ' ', (string) $value));
}

function sendAppEmail($toEmail, $subject, $message) {
    $toEmail = trim((string) $toEmail);
    $fromAddress = sanitizeMailHeaderValue(MAIL_FROM_ADDRESS);
    $fromName = sanitizeMailHeaderValue(MAIL_FROM_NAME);
    $subject = sanitizeMailHeaderValue($subject);

    if (!isValidEmail($toEmail) || !isValidEmail($fromAddress)) {
        return false;
    }

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . ($fromName !== '' ? $fromName . ' <' . $fromAddress . '>' : $fromAddress),
        'Reply-To: ' . $fromAddress,
        'X-Mailer: PHP/' . phpversion()
    ];

    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $normalizedMessage = str_replace(["\r\n", "\r"], "\n", (string) $message);
    $normalizedMessage = str_replace("\n", "\r\n", $normalizedMessage);

    // Prefer SMTP if configured; otherwise fall back to mail()
    if (SMTP_HOST !== '' && SMTP_USER !== '' && SMTP_PASS !== '') {
        return sendAppEmailSmtp(
            $toEmail,
            $encodedSubject,
            $normalizedMessage,
            $fromAddress,
            $fromName,
            implode("\r\n", $headers)
        );
    }

    if (function_exists('ini_set')) {
        @ini_set('sendmail_from', MAIL_ENVELOPE_FROM);
    }

    $params = '';
    if (stripos(PHP_OS, 'WIN') === false && MAIL_ENVELOPE_FROM !== '') {
        $params = '-f' . escapeshellarg(MAIL_ENVELOPE_FROM);
    }

    return @mail($toEmail, $encodedSubject, $normalizedMessage, implode("\r\n", $headers), $params);
}

function sendAppEmailSmtp($toEmail, $subject, $message, $fromAddress, $fromName, $headerString) {
    $secure = SMTP_SECURE;
    $host = SMTP_HOST;
    $port = SMTP_PORT > 0 ? SMTP_PORT : 587;

    $transport = ($secure === 'ssl') ? 'ssl://' : 'tcp://';
    $socket = @stream_socket_client(
        $transport . $host . ':' . $port,
        $errno,
        $errstr,
        15,
        STREAM_CLIENT_CONNECT,
        stream_context_create([
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'allow_self_signed' => false]
        ])
    );

    if (!$socket) {
        if (function_exists('logError')) {
            logError("SMTP connect failed: {$errstr} ({$errno})", 'EMAIL');
        }
        return false;
    }

    $read = function () use ($socket) {
        return fgets($socket, 515);
    };
    $write = function ($data) use ($socket) {
        fwrite($socket, $data . "\r\n");
    };

    $expect = function ($code) use ($read) {
        $line = $read();
        return is_string($line) && str_starts_with($line, (string) $code);
    };

    $read(); // initial
    $write('EHLO ' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    $bannerOk = $expect(250);

    if ($secure === 'tls') {
        $write('STARTTLS');
        if (!$expect(220) || !stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($socket);
            return false;
        }
        $write('EHLO ' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $bannerOk = $expect(250);
    }

    if (!$bannerOk) {
        fclose($socket);
        return false;
    }

    $write('AUTH LOGIN');
    if (!$expect(334)) { fclose($socket); return false; }
    $write(base64_encode(SMTP_USER));
    if (!$expect(334)) { fclose($socket); return false; }
    $write(base64_encode(SMTP_PASS));
    if (!$expect(235)) { fclose($socket); return false; }

    $write('MAIL FROM: <' . MAIL_ENVELOPE_FROM . '>');
    if (!$expect(250)) { fclose($socket); return false; }

    $write('RCPT TO: <' . $toEmail . '>');
    if (!$expect(250)) { fclose($socket); return false; }

    $write('DATA');
    if (!$expect(354)) { fclose($socket); return false; }

    $write($headerString . "\r\n\r\n" . $message . "\r\n.");
    $accepted = $expect(250);

    $write('QUIT');
    fclose($socket);

    return $accepted;
}

function ensureStudentPasswordResetTable($conn) {
    if (dbTableExists($conn, 'student_password_resets')) {
        return true;
    }

    $sql = "
        CREATE TABLE IF NOT EXISTS student_password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            token_hash CHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            used_at DATETIME DEFAULT NULL,
            requested_ip VARCHAR(45) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_student_password_resets_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
            UNIQUE KEY uq_student_password_reset_token_hash (token_hash),
            KEY idx_student_password_reset_student (student_id),
            KEY idx_student_password_reset_expires (expires_at)
        )
    ";

    return (bool) $conn->query($sql);
}

function pruneStudentPasswordResets($conn) {
    if (!ensureStudentPasswordResetTable($conn)) {
        return;
    }

    $conn->query(
        "DELETE FROM student_password_resets
         WHERE expires_at < NOW()
            OR (used_at IS NOT NULL AND used_at < DATE_SUB(NOW(), INTERVAL 7 DAY))"
    );
}

function createStudentPasswordResetToken($conn, $studentId) {
    $studentId = (int) $studentId;

    if ($studentId <= 0 || !ensureStudentPasswordResetTable($conn)) {
        return null;
    }

    pruneStudentPasswordResets($conn);
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expiresAt = date('Y-m-d H:i:s', time() + (PASSWORD_RESET_EXPIRY_MINUTES * 60));
    $requestedIp = getClientIp();

    try {
        $conn->begin_transaction();

        $stmt = $conn->prepare(
            "UPDATE student_password_resets
             SET used_at = NOW()
             WHERE student_id = ? AND used_at IS NULL"
        );

        if (!$stmt) {
            throw new RuntimeException('Unable to invalidate previous reset tokens.');
        }

        $stmt->bind_param('i', $studentId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare(
            "INSERT INTO student_password_resets (student_id, token_hash, expires_at, requested_ip)
             VALUES (?, ?, ?, ?)"
        );

        if (!$stmt) {
            throw new RuntimeException('Unable to create password reset token.');
        }

        $stmt->bind_param('isss', $studentId, $tokenHash, $expiresAt, $requestedIp);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        return $token;
    } catch (Throwable $exception) {
        $conn->rollback();

        if (function_exists('logError')) {
            logError('Password reset token creation failed: ' . $exception->getMessage(), 'AUTH');
        }

        return null;
    }
}

function getStudentPasswordResetRecord($conn, $token) {
    $token = trim((string) $token);

    if ($token === '' || !ensureStudentPasswordResetTable($conn)) {
        return null;
    }

    pruneStudentPasswordResets($conn);
    $tokenHash = hash('sha256', $token);
    $stmt = $conn->prepare(
        "SELECT pr.*, s.email, s.name
         FROM student_password_resets pr
         INNER JOIN students s ON s.id = pr.student_id
         WHERE pr.token_hash = ?
         LIMIT 1"
    );

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('s', $tokenHash);
    $stmt->execute();
    $record = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();

    return $record;
}

function isStudentPasswordResetRecordActive($record) {
    if (empty($record)) {
        return false;
    }

    if (!empty($record['used_at'])) {
        return false;
    }

    $expiresAt = strtotime((string) ($record['expires_at'] ?? ''));
    return $expiresAt !== false && $expiresAt >= time();
}

function resetStudentPasswordWithToken($conn, $token, $newPassword) {
    $record = getStudentPasswordResetRecord($conn, $token);

    if (!isStudentPasswordResetRecordActive($record)) {
        return false;
    }

    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $resetId = (int) ($record['id'] ?? 0);
    $studentId = (int) ($record['student_id'] ?? 0);

    if ($resetId <= 0 || $studentId <= 0) {
        return false;
    }

    try {
        $conn->begin_transaction();

        $stmt = $conn->prepare("UPDATE students SET password = ? WHERE id = ?");

        if (!$stmt) {
            throw new RuntimeException('Unable to update student password.');
        }

        $stmt->bind_param('si', $passwordHash, $studentId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare(
            "UPDATE student_password_resets
             SET used_at = NOW()
             WHERE id = ? AND used_at IS NULL"
        );

        if (!$stmt) {
            throw new RuntimeException('Unable to finalize password reset token.');
        }

        $stmt->bind_param('i', $resetId);
        $stmt->execute();
        $affectedRows = (int) $stmt->affected_rows;
        $stmt->close();

        if ($affectedRows !== 1) {
            throw new RuntimeException('Password reset token is no longer valid.');
        }

        $stmt = $conn->prepare(
            "UPDATE student_password_resets
             SET used_at = NOW()
             WHERE student_id = ? AND id <> ? AND used_at IS NULL"
        );

        if ($stmt) {
            $stmt->bind_param('ii', $studentId, $resetId);
            $stmt->execute();
            $stmt->close();
        }

        $conn->commit();
        return true;
    } catch (Throwable $exception) {
        $conn->rollback();

        if (function_exists('logError')) {
            logError('Password reset failed: ' . $exception->getMessage(), 'AUTH');
        }

        return false;
    }
}

function sendStudentPasswordResetEmail($student, $token) {
    if (empty($student) || empty($token)) {
        return false;
    }

    $studentName = trim((string) ($student['name'] ?? 'Student'));
    $studentEmail = trim((string) ($student['email'] ?? ''));
    $resetLink = BASE_URL . 'account/reset-password.php?token=' . urlencode((string) $token);
    $subject = APP_NAME . ' Password Reset';
    $message = "Hello {$studentName},\n\n"
        . "We received a request to reset your " . APP_NAME . " password.\n\n"
        . "Open this link within " . PASSWORD_RESET_EXPIRY_MINUTES . " minutes:\n"
        . $resetLink . "\n\n"
        . "If you did not request this password reset, you can safely ignore this email.\n\n"
        . "Thanks,\n"
        . APP_NAME;

    $sent = sendAppEmail($studentEmail, $subject, $message);

    if (!$sent && function_exists('logError')) {
        logError('Password reset email could not be sent to ' . $studentEmail, 'AUTH');
    }

    return $sent;
}

// ==========================================
// ADMIN PASSWORD RESET
// ==========================================

function ensureAdminPasswordResetTable($conn) {
    if (!($conn instanceof mysqli)) {
        return false;
    }

    return (bool) $conn->query("
        CREATE TABLE IF NOT EXISTS admin_password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT NOT NULL,
            token_hash CHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            used_at DATETIME NULL,
            requested_ip VARCHAR(45) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_token_hash (token_hash),
            INDEX idx_admin (admin_id),
            FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
        )
    ");
}

function pruneAdminPasswordResets($conn) {
    if (!($conn instanceof mysqli) || !ensureAdminPasswordResetTable($conn)) {
        return;
    }

    $conn->query("
        DELETE FROM admin_password_resets
        WHERE expires_at < NOW()
           OR (used_at IS NOT NULL AND used_at < DATE_SUB(NOW(), INTERVAL 7 DAY))
    ");
}

function getAdminByIdentity($conn, $identity) {
    $identity = trim((string) $identity);

    if ($identity === '') {
        return null;
    }

    $hasEmailColumn = function_exists('dbColumnExists') && dbColumnExists($conn, 'admins', 'email');
    $isEmail = $hasEmailColumn && isValidEmail($identity);

    if ($isEmail) {
        $stmt = $conn->prepare("SELECT * FROM admins WHERE email = ? LIMIT 1");
    } else {
        $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
    }

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('s', $identity);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();

    return $admin;
}

function createAdminPasswordResetToken($conn, $adminId) {
    $adminId = (int) $adminId;

    if ($adminId <= 0 || !ensureAdminPasswordResetTable($conn)) {
        return null;
    }

    pruneAdminPasswordResets($conn);
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expiresAt = date('Y-m-d H:i:s', time() + (PASSWORD_RESET_EXPIRY_MINUTES * 60));
    $requestedIp = getClientIp();

    try {
        $conn->begin_transaction();

        $stmt = $conn->prepare(
            "UPDATE admin_password_resets
             SET used_at = NOW()
             WHERE admin_id = ? AND used_at IS NULL"
        );

        if (!$stmt) {
            throw new RuntimeException('Unable to invalidate previous admin reset tokens.');
        }

        $stmt->bind_param('i', $adminId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare(
            "INSERT INTO admin_password_resets (admin_id, token_hash, expires_at, requested_ip)
             VALUES (?, ?, ?, ?)"
        );

        if (!$stmt) {
            throw new RuntimeException('Unable to create admin password reset token.');
        }

        $stmt->bind_param('isss', $adminId, $tokenHash, $expiresAt, $requestedIp);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        return $token;
    } catch (Throwable $exception) {
        $conn->rollback();

        if (function_exists('logError')) {
            logError('Admin password reset token creation failed: ' . $exception->getMessage(), 'AUTH');
        }

        return null;
    }
}

function getAdminPasswordResetRecord($conn, $token) {
    $token = trim((string) $token);

    if ($token === '' || !ensureAdminPasswordResetTable($conn)) {
        return null;
    }

    pruneAdminPasswordResets($conn);
    $tokenHash = hash('sha256', $token);
    $stmt = $conn->prepare(
        "SELECT pr.*, a.username, a.email
         FROM admin_password_resets pr
         INNER JOIN admins a ON a.id = pr.admin_id
         WHERE pr.token_hash = ?
         LIMIT 1"
    );

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('s', $tokenHash);
    $stmt->execute();
    $record = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();

    return $record;
}

function isAdminPasswordResetRecordActive($record) {
    if (empty($record)) {
        return false;
    }

    if (!empty($record['used_at'])) {
        return false;
    }

    $expiresAt = strtotime((string) ($record['expires_at'] ?? ''));
    return $expiresAt !== false && $expiresAt >= time();
}

function resetAdminPasswordWithToken($conn, $token, $newPassword) {
    $record = getAdminPasswordResetRecord($conn, $token);

    if (!isAdminPasswordResetRecordActive($record)) {
        return false;
    }

    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $resetId = (int) ($record['id'] ?? 0);
    $adminId = (int) ($record['admin_id'] ?? 0);

    if ($resetId <= 0 || $adminId <= 0) {
        return false;
    }

    try {
        $conn->begin_transaction();

        $stmt = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");

        if (!$stmt) {
            throw new RuntimeException('Unable to update admin password.');
        }

        $stmt->bind_param('si', $passwordHash, $adminId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare(
            "UPDATE admin_password_resets
             SET used_at = NOW()
             WHERE id = ? AND used_at IS NULL"
        );

        if (!$stmt) {
            throw new RuntimeException('Unable to finalize admin password reset token.');
        }

        $stmt->bind_param('i', $resetId);
        $stmt->execute();
        $affectedRows = (int) $stmt->affected_rows;
        $stmt->close();

        if ($affectedRows !== 1) {
            throw new RuntimeException('Admin password reset token is no longer valid.');
        }

        $stmt = $conn->prepare(
            "UPDATE admin_password_resets
             SET used_at = NOW()
             WHERE admin_id = ? AND id <> ? AND used_at IS NULL"
        );

        if ($stmt) {
            $stmt->bind_param('ii', $adminId, $resetId);
            $stmt->execute();
            $stmt->close();
        }

        $conn->commit();
        return true;
    } catch (Throwable $exception) {
        $conn->rollback();

        if (function_exists('logError')) {
            logError('Admin password reset failed: ' . $exception->getMessage(), 'AUTH');
        }

        return false;
    }
}

function sendAdminPasswordResetEmail($admin, $token) {
    if (empty($admin) || empty($token)) {
        return false;
    }

    $adminEmail = trim((string) ($admin['email'] ?? ''));
    $adminUsername = trim((string) ($admin['username'] ?? 'Admin'));

    if ($adminEmail === '' || !isValidEmail($adminEmail)) {
        if (function_exists('logError')) {
            logError('Admin password reset email could not be sent because email is missing.', 'AUTH');
        }
        return false;
    }

    $resetLink = BASE_URL . 'admin/reset-password.php?token=' . urlencode((string) $token);
    $subject = APP_NAME . ' Admin Password Reset';
    $message = "Hello {$adminUsername},\n\n"
        . "We received a request to reset your " . APP_NAME . " admin password.\n\n"
        . "Open this link within " . PASSWORD_RESET_EXPIRY_MINUTES . " minutes:\n"
        . $resetLink . "\n\n"
        . "If you did not request this password reset, you can safely ignore this email.\n\n"
        . "Thanks,\n"
        . APP_NAME;

    $sent = sendAppEmail($adminEmail, $subject, $message);

    if (!$sent && function_exists('logError')) {
        logError('Admin password reset email could not be sent to ' . $adminEmail, 'AUTH');
    }

    return $sent;
}
