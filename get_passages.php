<?php
require_once __DIR__ . '/includes/init.php';

if (!dbTableExists($conn, 'passages')) {
    jsonResponse(['success' => false, 'message' => 'Passage table is missing.'], 500);
}

$languageId = (int) getSafeGet('language_id', 0);
$levelSlug = normalizeTypingLevelSlug(getSafeGet('level', ''));
$examTypeId = (int) getSafeGet('exam_type_id', 0);

if ($languageId <= 0) {
    jsonResponse(['success' => false, 'message' => 'Invalid language selected.'], 422);
}

$examTypeIds = [];

if ($levelSlug !== '') {
    $examTypeIds = getTypingLevelExamTypeIds($conn, $languageId, $levelSlug);
} elseif ($examTypeId > 0) {
    $examTypeIds = [$examTypeId];
}

$examTypeIds = array_values(array_unique(array_filter(array_map('intval', $examTypeIds))));

if (empty($examTypeIds)) {
    jsonResponse(['success' => true, 'passages' => []]);
}

$placeholders = implode(', ', array_fill(0, count($examTypeIds), '?'));
$types = 'i' . str_repeat('i', count($examTypeIds));
$values = array_merge([$languageId], $examTypeIds);
$stmt = $conn->prepare(
    "SELECT id, content
     FROM passages
     WHERE language_id = ? AND exam_type_id IN ({$placeholders})
     ORDER BY id ASC"
);

if (!$stmt) {
    jsonResponse(['success' => false, 'message' => 'Unable to load passages.'], 500);
}

$stmt->bind_param($types, ...$values);
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
