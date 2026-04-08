<?php
/**
 * Typing level helpers built on top of the existing exam_types table.
 */

function getTypingLevelDefinitions() {
    return [
        'skill_test' => [
            'label' => 'Skill Test',
            'wpm' => 30,
            'time_limit' => 300
        ],
        'intermediate' => [
            'label' => 'Intermediate',
            'wpm' => 40,
            'time_limit' => 300
        ],
        'expert' => [
            'label' => 'Expert',
            'wpm' => 50,
            'time_limit' => 300
        ]
    ];
}

function normalizeTypingLevelSlug($value) {
    $normalized = strtolower(trim((string) $value));
    $normalized = str_replace(['-', ' '], '_', $normalized);

    if (in_array($normalized, ['skill', 'skill_test', 'skilltest', 'beginner'], true)) {
        return 'skill_test';
    }

    if (in_array($normalized, ['intermediate', 'inter', 'middle', 'mid'], true)) {
        return 'intermediate';
    }

    if (in_array($normalized, ['expert', 'advanced'], true)) {
        return 'expert';
    }

    return '';
}

function getTypingLevelLabel($levelSlug) {
    $definitions = getTypingLevelDefinitions();
    $levelSlug = normalizeTypingLevelSlug($levelSlug);

    return $definitions[$levelSlug]['label'] ?? 'Skill Test';
}

function detectTypingLevelSlugFromExamType($examTypeName, $wpm = 0) {
    $normalizedName = strtolower(trim((string) $examTypeName));
    $wpm = (int) $wpm;

    if (
        str_contains($normalizedName, 'expert')
        || str_contains($normalizedName, 'advanced')
        || preg_match('/(^|[^0-9])(5[0-9]|[6-9][0-9]?)([^0-9]|$)/', $normalizedName)
        || $wpm >= 50
    ) {
        return 'expert';
    }

    if (
        str_contains($normalizedName, 'intermediate')
        || str_contains($normalizedName, 'inter')
        || preg_match('/(^|[^0-9])4[0-9]?([^0-9]|$)/', $normalizedName)
        || ($wpm >= 40 && $wpm < 50)
    ) {
        return 'intermediate';
    }

    return 'skill_test';
}

function ensureTypingLevelsForLanguage($conn, $languageId) {
    $languageId = (int) $languageId;

    if (
        $languageId <= 0
        || !dbTableExists($conn, 'exam_types')
        || !dbColumnExists($conn, 'exam_types', 'language_id')
        || !dbColumnExists($conn, 'exam_types', 'name')
    ) {
        return;
    }

    $definitions = getTypingLevelDefinitions();

    foreach ($definitions as $definition) {
        $stmt = $conn->prepare(
            "SELECT id
             FROM exam_types
             WHERE language_id = ? AND LOWER(name) = LOWER(?)
             LIMIT 1"
        );

        if (!$stmt) {
            continue;
        }

        $label = $definition['label'];
        $stmt->bind_param('is', $languageId, $label);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();

        if ($existing) {
            continue;
        }

        $wpm = (int) $definition['wpm'];
        $timeLimit = (int) $definition['time_limit'];
        $stmt = $conn->prepare(
            "INSERT INTO exam_types (language_id, name, wpm, time_limit)
             VALUES (?, ?, ?, ?)"
        );

        if (!$stmt) {
            continue;
        }

        $stmt->bind_param('isii', $languageId, $label, $wpm, $timeLimit);
        $stmt->execute();
        $stmt->close();
    }
}

function ensureTypingLevelsForAllLanguages($conn) {
    if (!dbTableExists($conn, 'languages')) {
        return;
    }

    $result = $conn->query("SELECT id FROM languages ORDER BY id ASC");

    if (!$result) {
        return;
    }

    while ($row = $result->fetch_assoc()) {
        ensureTypingLevelsForLanguage($conn, (int) $row['id']);
    }

    $result->free();
}

function getTypingLevelOptions($conn, $languageId) {
    $languageId = (int) $languageId;
    ensureTypingLevelsForLanguage($conn, $languageId);
    $definitions = getTypingLevelDefinitions();
    $options = [];

    foreach ($definitions as $slug => $definition) {
        $options[] = [
            'slug' => $slug,
            'label' => $definition['label'],
            'wpm' => (int) $definition['wpm'],
            'time_limit' => (int) $definition['time_limit']
        ];
    }

    return $options;
}

function getTypingLevelExamTypeIds($conn, $languageId, $levelSlug) {
    $languageId = (int) $languageId;
    $levelSlug = normalizeTypingLevelSlug($levelSlug);

    if ($languageId <= 0 || $levelSlug === '' || !dbTableExists($conn, 'exam_types')) {
        return [];
    }

    ensureTypingLevelsForLanguage($conn, $languageId);
    $stmt = $conn->prepare(
        "SELECT id, name, wpm
         FROM exam_types
         WHERE language_id = ?"
    );

    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('i', $languageId);
    $stmt->execute();
    $result = $stmt->get_result();
    $ids = [];

    while ($row = $result->fetch_assoc()) {
        if (detectTypingLevelSlugFromExamType($row['name'] ?? '', (int) ($row['wpm'] ?? 0)) === $levelSlug) {
            $ids[] = (int) $row['id'];
        }
    }

    $stmt->close();

    return array_values(array_unique(array_filter($ids)));
}

function getTypingLevelExamTypeId($conn, $languageId, $levelSlug) {
    $languageId = (int) $languageId;
    $levelSlug = normalizeTypingLevelSlug($levelSlug);

    if ($languageId <= 0 || $levelSlug === '') {
        return 0;
    }

    ensureTypingLevelsForLanguage($conn, $languageId);
    $label = getTypingLevelLabel($levelSlug);
    $stmt = $conn->prepare(
        "SELECT id
         FROM exam_types
         WHERE language_id = ? AND LOWER(name) = LOWER(?)
         LIMIT 1"
    );

    if (!$stmt) {
        return 0;
    }

    $stmt->bind_param('is', $languageId, $label);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();

    return (int) ($row['id'] ?? 0);
}
