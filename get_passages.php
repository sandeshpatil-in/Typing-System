<?php
require_once __DIR__ . '/includes/init.php';

if (!dbTableExists($conn, 'passages')) {
    jsonResponse(['success' => false, 'message' => 'Passage table is missing.'], 500);
}

$languageId = (int) getSafeGet('language_id', 0);
$examTypeId = (int) getSafeGet('exam_type_id', 0);

if ($languageId <= 0 || $examTypeId <= 0) {
    jsonResponse(['success' => false, 'message' => 'Invalid language or exam type selected.'], 422);
}

$stmt = $conn->prepare(
    "SELECT id, content
     FROM passages
     WHERE language_id = ? AND exam_type_id = ?
     ORDER BY id ASC"
);

if (!$stmt) {
    jsonResponse(['success' => false, 'message' => 'Unable to load passages.'], 500);
}

$stmt->bind_param('ii', $languageId, $examTypeId);
$stmt->execute();
$result = $stmt->get_result();
$passages = [];

while ($row = $result->fetch_assoc()) {
    $preview = preg_replace('/\s+/', ' ', trim($row['content']));
    $previewLength = 60;
    $preview = function_exists('mb_substr') ? mb_substr($preview, 0, $previewLength) : substr($preview, 0, $previewLength);

    $passages[] = [
        'id' => (int) $row['id'],
        'label' => 'Passage #' . $row['id'] . ' - ' . $preview . ((function_exists('mb_strlen') ? mb_strlen(trim($row['content'])) : strlen(trim($row['content']))) > $previewLength ? '...' : '')
    ];
}

$stmt->close();

jsonResponse([
    'success' => true,
    'passages' => $passages
]);
