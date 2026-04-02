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
$lookupColumn = dbColumnExists($conn, 'plans', 'razorpay_order_id')
    ? 'razorpay_order_id'
    : (dbColumnExists($conn, 'plans', 'payment_id') ? 'payment_id' : null);

if ($lookupColumn === null) {
    jsonResponse(['success' => false, 'message' => 'Plan payment tracking is not configured'], 500);
}

$stmt = $conn->prepare(
    "SELECT * FROM plans
     WHERE student_id = ? AND {$lookupColumn} = ?
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

$currentStudent = syncStudentPlanStatus($conn, $studentId);
$renewalBaseExpiry = ($currentStudent && hasActivePlan($currentStudent))
    ? ($currentStudent['expiry_date'] ?? null)
    : ($plan['expiry_date'] ?? null);

if (($plan['payment_status'] ?? '') === 'paid' && !empty($plan['expiry_date'])) {
    updateStudentPlanAccess(
        $conn,
        $studentId,
        isPlanExpiryActive($plan['expiry_date']),
        $plan['start_date'] ?? null,
        $plan['expiry_date']
    );

    setFlash('auth_message', 'Payment already verified. Your plan is active.');

    jsonResponse([
        'success' => true,
        'redirect' => BASE_URL . 'account/dashboard.php'
    ]);
}

$dates = calculatePlanDates($renewalBaseExpiry);
$startDate = $dates['start_date'];
$expiryDate = $dates['expiry_date'];

$planUpdates = [
    'start_date = ?',
    'expiry_date = ?'
];
$types = 'ss';
$values = [$startDate, $expiryDate];

if (dbColumnExists($conn, 'plans', 'razorpay_payment_id')) {
    array_unshift($planUpdates, 'razorpay_payment_id = ?');
    array_unshift($values, $paymentId);
    $types = 's' . $types;
}

if (dbColumnExists($conn, 'plans', 'razorpay_signature')) {
    $planUpdates[] = 'razorpay_signature = ?';
    $types .= 's';
    $values[] = $signature;
}

if (dbColumnExists($conn, 'plans', 'payment_status')) {
    $planUpdates[] = "payment_status = 'paid'";
}

if (dbColumnExists($conn, 'plans', 'status')) {
    $planUpdates[] = "status = 'active'";
}

if (dbColumnExists($conn, 'plans', 'paid_at')) {
    $planUpdates[] = 'paid_at = NOW()';
}

if (dbColumnExists($conn, 'plans', 'payment_method')) {
    $planUpdates[] = 'payment_method = ?';
    $types .= 's';
    $values[] = 'razorpay';
}

$types .= 'i';
$values[] = (int) $plan['id'];

$stmt = $conn->prepare(
    "UPDATE plans
     SET " . implode(', ', $planUpdates) . "
     WHERE id = ?"
);
$stmt->bind_param($types, ...$values);
$stmt->execute();
$stmt->close();

updateStudentPlanAccess($conn, $studentId, true, $startDate, $expiryDate);

setFlash('auth_message', 'Payment successful. Your 30-day plan is now active.');

jsonResponse([
    'success' => true,
    'redirect' => BASE_URL . 'account/dashboard.php'
]);
