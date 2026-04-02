<?php
if (!isAdminLoggedIn()) {
    redirect('admin/login.php');
}

$id = (int) getSafeGet('id', 0);

if ($id <= 0) {
    redirect('admin/dashboard.php?page=paragraphs');
}

if (function_exists('dbTableExists') && dbTableExists($conn, 'passages')) {
    $stmt = $conn->prepare("DELETE FROM passages WHERE id = ?");
} elseif (function_exists('dbTableExists') && dbTableExists($conn, 'paragraphs')) {
    $stmt = $conn->prepare("DELETE FROM paragraphs WHERE id = ?");
} else {
    redirect('admin/dashboard.php?page=paragraphs');
}

if ($stmt) {
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

redirect('admin/dashboard.php?page=paragraphs');
