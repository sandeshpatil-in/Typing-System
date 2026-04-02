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
    $errorMessage = is_array($order) ? ($order['error_message'] ?? null) : null;
    jsonResponse([
        'success' => false,
        'message' => $errorMessage ?: 'Unable to create Razorpay order'
    ], 500);
}

$hasPlanName = dbColumnExists($conn, 'plans', 'plan_name');
$hasCurrency = dbColumnExists($conn, 'plans', 'currency');
$hasRazorpayOrderId = dbColumnExists($conn, 'plans', 'razorpay_order_id');
$hasPaymentId = dbColumnExists($conn, 'plans', 'payment_id');
$hasPaymentStatus = dbColumnExists($conn, 'plans', 'payment_status');
$hasPaymentMethod = dbColumnExists($conn, 'plans', 'payment_method');
$hasCreatedAt = dbColumnExists($conn, 'plans', 'created_at');
$pendingStatus = getPendingPlanStatusValue($conn);

$columns = ['student_id', 'amount'];
$placeholders = ['?', '?'];
$types = 'id';
$values = [(int) $student['id'], PLAN_PRICE];

if ($hasPlanName) {
    $columns[] = 'plan_name';
    $placeholders[] = '?';
    $types .= 's';
    $values[] = PLAN_NAME;
}

if ($hasCurrency) {
    $columns[] = 'currency';
    $placeholders[] = '?';
    $types .= 's';
    $values[] = PAYMENT_CURRENCY;
}

if ($hasRazorpayOrderId) {
    $columns[] = 'razorpay_order_id';
    $placeholders[] = '?';
    $types .= 's';
    $values[] = $order['id'];
} elseif ($hasPaymentId) {
    $columns[] = 'payment_id';
    $placeholders[] = '?';
    $types .= 's';
    $values[] = $order['id'];
}

if ($hasPaymentStatus) {
    $columns[] = 'payment_status';
    $placeholders[] = "'" . $conn->real_escape_string($pendingStatus) . "'";
}

if ($hasPaymentMethod) {
    $columns[] = 'payment_method';
    $placeholders[] = "'razorpay'";
}

if ($hasCreatedAt) {
    $columns[] = 'created_at';
    $placeholders[] = 'NOW()';
}

$stmt = $conn->prepare(
    "INSERT INTO plans (" . implode(', ', $columns) . ")
     VALUES (" . implode(', ', $placeholders) . ")"
);

if (!$stmt) {
    jsonResponse(['success' => false, 'message' => 'Unable to save payment order'], 500);
}

$stmt->bind_param($types, ...$values);
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
