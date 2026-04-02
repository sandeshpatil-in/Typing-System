<?php
require_once __DIR__ . '/includes/init.php';

if (!dbTableExists($conn, 'exam_types') || !dbTableExists($conn, 'languages')) {
    jsonResponse(['success' => false, 'message' => 'Typing preference tables are missing.'], 500);
}

$languageId = (int) getSafeGet('language_id', 0);

if ($languageId <= 0) {
    jsonResponse(['success' => false, 'message' => 'Invalid language selected.'], 422);
}

$stmt = $conn->prepare(
    "SELECT id, name, wpm, time_limit
     FROM exam_types
     WHERE language_id = ?
     ORDER BY wpm ASC, name ASC"
);

if (!$stmt) {
    jsonResponse(['success' => false, 'message' => 'Unable to load exam types.'], 500);
}

$stmt->bind_param('i', $languageId);
$stmt->execute();
$result = $stmt->get_result();
$examTypes = [];

while ($row = $result->fetch_assoc()) {
    $examTypes[] = [
        'id' => (int) $row['id'],
        'name' => $row['name'],
        'wpm' => (int) $row['wpm'],
        'time_limit' => (int) $row['time_limit']
    ];
}

$stmt->close();

jsonResponse([
    'success' => true,
    'exam_types' => $examTypes
]);
