<?php
if (!isAdminLoggedIn()) {
    redirect('admin/login.php');
}

$id = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        setFlash('admin_student_message', 'Activation failed because the session expired. Please try again.');
        redirect('admin/dashboard.php?page=students');
    }

    $id = (int) getSafePost('id', 0);
} else {
    $id = (int) getSafeGet('id', 0);
}

if ($id <= 0) {
    setFlash('admin_student_message', 'Invalid student selected.');
    redirect('admin/dashboard.php?page=students');
}

$startDate = date('Y-m-d');
$expiryDate = date('Y-m-d', strtotime('+' . PLAN_DURATION_DAYS . ' days'));

if (function_exists('dbTableExists') && dbTableExists($conn, 'plans')) {
    $hasPlanName = function_exists('dbColumnExists') && dbColumnExists($conn, 'plans', 'plan_name');
    $hasCurrency = function_exists('dbColumnExists') && dbColumnExists($conn, 'plans', 'currency');
    $hasPaymentStatus = function_exists('dbColumnExists') && dbColumnExists($conn, 'plans', 'payment_status');
    $hasPaidAt = function_exists('dbColumnExists') && dbColumnExists($conn, 'plans', 'paid_at');
    $hasCreatedAt = function_exists('dbColumnExists') && dbColumnExists($conn, 'plans', 'created_at');
    $hasPaymentId = function_exists('dbColumnExists') && dbColumnExists($conn, 'plans', 'payment_id');
    $hasStatus = function_exists('dbColumnExists') && dbColumnExists($conn, 'plans', 'status');

    if ($hasPlanName) {
        $stmt = $conn->prepare(
            "INSERT INTO plans
             (student_id, plan_name, amount, currency, payment_status, start_date, expiry_date, paid_at, created_at)
             VALUES (?, ?, ?, ?, 'paid', ?, ?, NOW(), NOW())"
        );

        if ($stmt) {
            $planName = 'Manual Admin Activation';
            $amount = 0.00;
            $currency = PAYMENT_CURRENCY;
            $stmt->bind_param('isdsss', $id, $planName, $amount, $currency, $startDate, $expiryDate);
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

$hasPlanStartDate = function_exists('dbColumnExists') && dbColumnExists($conn, 'students', 'plan_start_date');
$hasExpiryDate = function_exists('dbColumnExists') && dbColumnExists($conn, 'students', 'expiry_date');
$studentStmt = null;

if ($hasPlanStartDate && $hasExpiryDate) {
    $studentStmt = $conn->prepare(
        "UPDATE students
         SET status = 1, plan_start_date = ?, expiry_date = ?
         WHERE id = ?"
    );

    if ($studentStmt) {
        $studentStmt->bind_param('ssi', $startDate, $expiryDate, $id);
    }
} elseif ($hasExpiryDate) {
    $studentStmt = $conn->prepare(
        "UPDATE students
         SET status = 1, expiry_date = ?
         WHERE id = ?"
    );

    if ($studentStmt) {
        $studentStmt->bind_param('si', $expiryDate, $id);
    }
} else {
    $studentStmt = $conn->prepare(
        "UPDATE students
         SET status = 1
         WHERE id = ?"
    );

    if ($studentStmt) {
        $studentStmt->bind_param('i', $id);
    }
}

if ($studentStmt) {
    $studentStmt->execute();
    $studentStmt->close();
    setFlash('admin_student_message', 'Student activated successfully and status changed to Active.');
} else {
    setFlash('admin_student_message', 'Unable to activate the student.');
}

redirect('admin/dashboard.php?page=students');
