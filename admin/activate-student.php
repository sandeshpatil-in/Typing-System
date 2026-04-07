<?php
if (!defined('APP_INITIALIZED')) {
    require_once __DIR__ . '/../includes/init.php';
}

if (!isAdminLoggedIn()) {
    redirect('admin/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlash('admin_student_message', 'Activation must be submitted from the students page.');
    setFlash('admin_student_message_type', 'warning');
    redirect('admin/dashboard.php?page=students');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlash('admin_student_message', 'Activation failed because the session expired. Please try again.');
    setFlash('admin_student_message_type', 'error');
    redirect('admin/dashboard.php?page=students');
}

$id = (int) getSafePost('id', 0);

if ($id <= 0) {
    setFlash('admin_student_message', 'Invalid student selected.');
    setFlash('admin_student_message_type', 'error');
    redirect('admin/dashboard.php?page=students');
}

$student = getStudentById($conn, $id);

if (!$student) {
    setFlash('admin_student_message', 'Student not found.');
    setFlash('admin_student_message_type', 'error');
    redirect('admin/dashboard.php?page=students');
}

$dates = calculatePlanDates($student['expiry_date'] ?? null);
$startDate = $dates['start_date'];
$expiryDate = $dates['expiry_date'];

if (function_exists('dbTableExists') && dbTableExists($conn, 'plans')) {
    $hasPlanName = function_exists('dbColumnExists') && dbColumnExists($conn, 'plans', 'plan_name');
    $hasCurrency = function_exists('dbColumnExists') && dbColumnExists($conn, 'plans', 'currency');
    $hasPaymentStatus = function_exists('dbColumnExists') && dbColumnExists($conn, 'plans', 'payment_status');
    $hasPaymentMethod = function_exists('dbColumnExists') && dbColumnExists($conn, 'plans', 'payment_method');
    $hasPaidAt = function_exists('dbColumnExists') && dbColumnExists($conn, 'plans', 'paid_at');
    $hasCreatedAt = function_exists('dbColumnExists') && dbColumnExists($conn, 'plans', 'created_at');
    $hasPaymentId = function_exists('dbColumnExists') && dbColumnExists($conn, 'plans', 'payment_id');
    $hasStatus = function_exists('dbColumnExists') && dbColumnExists($conn, 'plans', 'status');

    if ($hasPlanName && $hasPaymentStatus) {
        $columns = ['student_id', 'plan_name', 'amount', 'start_date', 'expiry_date'];
        $placeholders = ['?', '?', '?', '?', '?'];
        $types = 'isdss';
        $values = [$id, 'Manual Pay Activation', 0.00, $startDate, $expiryDate];

        if ($hasCurrency) {
            $columns[] = 'currency';
            $placeholders[] = '?';
            $types .= 's';
            $values[] = PAYMENT_CURRENCY;
        }

        if ($hasPaymentMethod) {
            $columns[] = 'payment_method';
            $placeholders[] = '?';
            $types .= 's';
            $values[] = 'hand_cash';
        }

        $columns[] = 'payment_status';
        $placeholders[] = "'paid'";

        if ($hasPaidAt) {
            $columns[] = 'paid_at';
            $placeholders[] = 'NOW()';
        }

        if ($hasCreatedAt) {
            $columns[] = 'created_at';
            $placeholders[] = 'NOW()';
        }

        $stmt = $conn->prepare(
            "INSERT INTO plans (" . implode(', ', $columns) . ")
             VALUES (" . implode(', ', $placeholders) . ")"
        );

        if ($stmt) {
            $stmt->bind_param($types, ...$values);
            $stmt->execute();
            $stmt->close();
        }
    } elseif ($hasPlanName) {
        $columns = ['student_id', 'plan_name', 'amount', 'start_date', 'expiry_date'];
        $placeholders = ['?', '?', '?', '?', '?'];
        $types = 'isdss';
        $values = [$id, 'Manual Pay Activation', 0.00, $startDate, $expiryDate];

        if ($hasCurrency) {
            $columns[] = 'currency';
            $placeholders[] = '?';
            $types .= 's';
            $values[] = PAYMENT_CURRENCY;
        }

        if ($hasPaymentMethod) {
            $columns[] = 'payment_method';
            $placeholders[] = '?';
            $types .= 's';
            $values[] = 'hand_cash';
        }

        if ($hasPaidAt) {
            $columns[] = 'paid_at';
            $placeholders[] = 'NOW()';
        }

        if ($hasCreatedAt) {
            $columns[] = 'created_at';
            $placeholders[] = 'NOW()';
        }

        $stmt = $conn->prepare(
            "INSERT INTO plans (" . implode(', ', $columns) . ")
             VALUES (" . implode(', ', $placeholders) . ")"
        );

        if ($stmt) {
            $stmt->bind_param($types, ...$values);
            $stmt->execute();
            $stmt->close();
        }
    } elseif ($hasPaymentId || $hasStatus) {
        $columns = ['student_id'];
        $placeholders = ['?'];
        $types = 'i';
        $values = [$id];

        if ($hasPaymentId) {
            $columns[] = 'payment_id';
            $placeholders[] = '?';
            $types .= 's';
            $values[] = 'admin_' . time();
        }

        $columns[] = 'amount';
        $placeholders[] = '?';
        $types .= 'd';
        $values[] = 0.00;

        if ($hasPaymentStatus) {
            $columns[] = 'payment_status';
            $placeholders[] = "'paid'";
        }

        if ($hasPaymentMethod) {
            $columns[] = 'payment_method';
            $placeholders[] = '?';
            $types .= 's';
            $values[] = 'hand_cash';
        }

        if ($hasStatus) {
            $columns[] = 'status';
            $placeholders[] = "'active'";
        }

        $columns[] = 'start_date';
        $placeholders[] = '?';
        $types .= 's';
        $values[] = $startDate;

        $columns[] = 'expiry_date';
        $placeholders[] = '?';
        $types .= 's';
        $values[] = $expiryDate;

        if ($hasPaidAt) {
            $columns[] = 'paid_at';
            $placeholders[] = 'NOW()';
        }

        if ($hasCreatedAt) {
            $columns[] = 'created_at';
            $placeholders[] = 'NOW()';
        }

        $sql = "INSERT INTO plans (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param($types, ...$values);
            $stmt->execute();
            $stmt->close();
        }
    }
}

if (updateStudentPlanAccess($conn, $id, true, $startDate, $expiryDate)) {
    setFlash('admin_student_message', 'Manual Pay activation saved. Student access is active for the next 30 days.');
    setFlash('admin_student_message_type', 'success');
} else {
    setFlash('admin_student_message', 'Unable to activate the student.');
    setFlash('admin_student_message_type', 'error');
}

redirect('admin/dashboard.php?page=students');
