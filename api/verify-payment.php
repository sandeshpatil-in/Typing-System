<?php
require_once __DIR__ . '/../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

if (!isStudentLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Login required'], 401);
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    jsonResponse(['success' => false, 'message' => 'Invalid security token'], 419);
}

$orderId = trim($_POST['razorpay_order_id'] ?? '');
$paymentId = trim($_POST['razorpay_payment_id'] ?? '');
$signature = trim($_POST['razorpay_signature'] ?? '');

if ($orderId === '' || $paymentId === '' || $signature === '') {
    jsonResponse(['success' => false, 'message' => 'Incomplete payment data'], 422);
}

if (!verifyRazorpaySignature($orderId, $paymentId, $signature)) {
    jsonResponse(['success' => false, 'message' => 'Payment signature verification failed'], 400);
}

$studentId = getStudentId();
$plan = null;

$stmt = $conn->prepare(
    "SELECT * FROM plans
     WHERE student_id = ? AND razorpay_order_id = ?
     ORDER BY id DESC
     LIMIT 1"
);
$stmt->bind_param('is', $studentId, $orderId);
$stmt->execute();
$plan = $stmt->get_result()->fetch_assoc() ?: null;
$stmt->close();

if (!$plan) {
    jsonResponse(['success' => false, 'message' => 'Plan order not found'], 404);
}

$startDate = date('Y-m-d');
$expiryDate = date('Y-m-d', strtotime('+' . PLAN_DURATION_DAYS . ' days'));

$stmt = $conn->prepare(
    "UPDATE plans
     SET razorpay_payment_id = ?, razorpay_signature = ?, payment_status = 'paid',
         start_date = ?, expiry_date = ?, paid_at = NOW()
     WHERE id = ?"
);
$stmt->bind_param('ssssi', $paymentId, $signature, $startDate, $expiryDate, $plan['id']);
$stmt->execute();
$stmt->close();

$stmt = $conn->prepare(
    "UPDATE students
     SET status = 1, plan_start_date = ?, expiry_date = ?
     WHERE id = ?"
);
$stmt->bind_param('ssi', $startDate, $expiryDate, $studentId);
$stmt->execute();
$stmt->close();

setFlash('auth_message', 'Payment successful. Your 30-day plan is now active.');

jsonResponse([
    'success' => true,
    'redirect' => BASE_URL . 'account/dashboard.php'
]);
