<?php
require_once __DIR__ . '/includes/init.php';

if (!dbTableExists($conn, 'exam_types') || !dbTableExists($conn, 'languages')) {
    jsonResponse(['success' => false, 'message' => 'Typing preference tables are missing.'], 500);
}

$languageId = (int) getSafeGet('language_id', 0);

if ($languageId <= 0) {
    jsonResponse(['success' => false, 'message' => 'Invalid language selected.'], 422);
}

$levels = getTypingLevelOptions($conn, $languageId);
$examTypes = [];

foreach ($levels as $level) {
    $examTypes[] = [
        'id' => $level['slug'],
        'slug' => $level['slug'],
        'name' => $level['label'],
        'wpm' => (int) $level['wpm'],
        'time_limit' => (int) $level['time_limit']
    ];
}

jsonResponse([
    'success' => true,
    'exam_types' => $examTypes,
    'levels' => $examTypes
]);
