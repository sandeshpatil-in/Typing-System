<?php
require_once __DIR__ . '/../includes/init.php';

$language = getSafeGet('lang', 'english');

$stmt = $conn->prepare("SELECT content FROM paragraphs WHERE language = ? ORDER BY RAND() LIMIT 1");

if (!$stmt) {
    jsonResponse(['success' => false, 'message' => 'Unable to load paragraph'], 500);
}

$stmt->bind_param("s", $language);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

jsonResponse([
    'success' => true,
    'content' => $data['content'] ?? 'No paragraph found.'
]);
