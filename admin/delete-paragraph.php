<?php
require_once __DIR__ . '/../includes/init.php';

// Always send admins back to the Paragraphs tab after this action.
$paragraphsPage = 'admin/dashboard.php?page=paragraphs';

if (!isAdminLoggedIn()) {
    redirect('admin/login.php');
}

$id = (int) getSafeGet('id', 0);

if ($id <= 0) {
    redirect($paragraphsPage);
}

if (function_exists('dbTableExists') && dbTableExists($conn, 'passages')) {
    $stmt = $conn->prepare("DELETE FROM passages WHERE id = ?");
} elseif (function_exists('dbTableExists') && dbTableExists($conn, 'paragraphs')) {
    $stmt = $conn->prepare("DELETE FROM paragraphs WHERE id = ?");
} else {
    redirect($paragraphsPage);
}

if ($stmt) {
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

redirect($paragraphsPage);
