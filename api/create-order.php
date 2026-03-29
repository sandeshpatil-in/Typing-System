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

if (empty(RAZORPAY_KEY_ID) || empty(RAZORPAY_KEY_SECRET)) {
    jsonResponse(['success' => false, 'message' => 'Razorpay is not configured yet'], 500);
}

$student = syncStudentPlanStatus($conn, getStudentId());

if (!$student) {
    jsonResponse(['success' => false, 'message' => 'Student not found'], 404);
}

if (hasActivePlan($student)) {
    jsonResponse(['success' => false, 'message' => 'Plan already active'], 400);
}

$receipt = 'plan_' . $student['id'] . '_' . time();
$order = createRazorpayOrder($receipt, PLAN_PRICE_PAISE);

if (!$order || empty($order['id'])) {
    jsonResponse(['success' => false, 'message' => 'Unable to create Razorpay order'], 500);
}

$stmt = $conn->prepare(
    "INSERT INTO plans
     (student_id, plan_name, amount, currency, razorpay_order_id, payment_status, created_at)
     VALUES (?, ?, ?, ?, ?, 'created', NOW())"
);

if (!$stmt) {
    jsonResponse(['success' => false, 'message' => 'Unable to save payment order'], 500);
}

$planName = PLAN_NAME;
$amount = PLAN_PRICE;
$currency = PAYMENT_CURRENCY;
$orderId = $order['id'];
$studentId = (int) $student['id'];
$stmt->bind_param('isdss', $studentId, $planName, $amount, $currency, $orderId);
$stmt->execute();
$stmt->close();

jsonResponse([
    'success' => true,
    'key_id' => RAZORPAY_KEY_ID,
    'order_id' => $order['id'],
    'amount' => PLAN_PRICE_PAISE,
    'currency' => PAYMENT_CURRENCY,
    'name' => APP_NAME,
    'description' => PLAN_NAME . ' - ' . PLAN_DURATION_DAYS . ' day access',
    'prefill' => [
        'name' => $student['name'],
        'email' => $student['email']
    ]
]);
