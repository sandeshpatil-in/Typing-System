<?php
require_once __DIR__ . '/../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    jsonResponse(['success' => false, 'message' => 'Invalid security token'], 419);
}

if (!isStudentLoggedIn()) {
    syncGuestAttemptsWithClient($_POST['guest_attempts_used'] ?? null, $conn);
}

$access = getAccessContext($conn);
$attemptAccessType = $access['has_active_plan'] ? 'paid' : 'guest';

if ($access['is_logged_in'] && !$access['has_active_plan'] && $access['free_tests_remaining'] <= 0) {
    jsonResponse([
        'success' => false,
        'message' => 'Your 5 free tests are finished. Activate your plan to continue.',
        'redirect' => BASE_URL . 'payment.php'
    ], 403);
}

if (!$access['is_logged_in'] && !$access['guest_tests_remaining']) {
    jsonResponse([
        'success' => false,
        'message' => 'Your 5 free tests are finished. Create an account and activate your plan to continue.',
        'redirect' => BASE_URL . 'account/register.php'
    ], 403);
}

$language = getSafePost('language', 'english');
$examType = getSafePost('exam_type', 'normal');
$paragraphId = (int) getSafePost('paragraph_id', 0);
$timeLimit = (int) getSafePost('time_limit_seconds', 60);
$wpm = (float) ($_POST['wpm'] ?? 0);
$accuracy = (float) ($_POST['accuracy'] ?? 0);
$typedWords = (int) getSafePost('typed_words', 0);

$attemptId = recordTestAttempt($conn, [
    'student_id' => $access['is_logged_in'] ? (int) $access['student']['id'] : null,
    'guest_session_id' => $access['is_logged_in'] ? null : $access['guest_session_id'],
    'language' => $language,
    'exam_type' => $examType,
    'paragraph_id' => $paragraphId,
    'time_limit_seconds' => $timeLimit,
    'wpm' => $wpm,
    'accuracy' => $accuracy,
    'typed_words' => $typedWords,
    'access_type' => $attemptAccessType
]);

if (!$attemptId) {
    jsonResponse(['success' => false, 'message' => 'Unable to save test attempt'], 500);
}

$remaining = (int) ($access['guest_tests_remaining'] ?? 0);

if (!$access['is_logged_in']) {
    incrementGuestAttemptsUsed($conn);
    $remaining = getGuestTestsRemaining($conn);
} elseif (!$access['has_active_plan'] && !empty($access['student']['id'])) {
    $remaining = getStudentFreeTestsRemaining($conn, (int) $access['student']['id']);
}

jsonResponse([
    'success' => true,
    'attempt_id' => $attemptId,
    'guest_tests_remaining' => $remaining,
    'access_type' => $attemptAccessType
]);
