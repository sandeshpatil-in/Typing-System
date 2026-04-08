<?php
if (!isAdminLoggedIn()) {
    redirect('admin/login.php');
}

$id = (int) getSafeGet('id', 0);
$error = '';
$success = '';
$schemaReady = function_exists('dbTableExists')
    && dbTableExists($conn, 'passages')
    && dbTableExists($conn, 'languages')
    && dbTableExists($conn, 'exam_types');

if ($id <= 0) {
    redirect('admin/dashboard.php?page=paragraphs');
}

$paragraph = null;
$languages = [];
$levelDefinitions = getTypingLevelDefinitions();

if ($schemaReady) {
    ensureTypingLevelsForAllLanguages($conn);

    if ($result = $conn->query("SELECT id, name FROM languages ORDER BY name ASC")) {
        while ($row = $result->fetch_assoc()) {
            $languages[] = $row;
        }
    }

    $stmt = $conn->prepare(
        "SELECT p.id, p.language_id, p.exam_type_id, p.content, l.name AS language_name, e.name AS exam_type_name, e.wpm AS exam_type_wpm
         FROM passages p
         INNER JOIN languages l ON l.id = p.language_id
         INNER JOIN exam_types e ON e.id = p.exam_type_id
         WHERE p.id = ?"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $paragraph = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($paragraph) {
        $paragraph['level'] = detectTypingLevelSlugFromExamType($paragraph['exam_type_name'] ?? '', (int) ($paragraph['exam_type_wpm'] ?? 0));
    }
} elseif (function_exists('dbTableExists') && dbTableExists($conn, 'paragraphs')) {
    $stmt = $conn->prepare("SELECT id, language, content FROM paragraphs WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $paragraph = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if (!$paragraph) {
    redirect('admin/dashboard.php?page=paragraphs');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = 'Session expired. Please try again.';
    } elseif ($schemaReady) {
        $languageId = (int) getSafePost('language_id', $paragraph['language_id']);
        $levelSlug = normalizeTypingLevelSlug(getSafePost('level', $paragraph['level'] ?? 'skill_test'));
        $content = trim($_POST['content'] ?? '');

        if ($languageId <= 0 || $levelSlug === '' || $content === '') {
            $error = 'Language, level, and content are required.';
        } else {
            $examTypeId = getTypingLevelExamTypeId($conn, $languageId, $levelSlug);
            $stmt = $examTypeId > 0
                ? $conn->prepare("UPDATE passages SET language_id = ?, exam_type_id = ?, content = ? WHERE id = ?")
                : false;

            if ($stmt) {
                $stmt->bind_param("iisi", $languageId, $examTypeId, $content, $id);
                $stmt->execute();
                $stmt->close();
                $success = 'Paragraph updated successfully.';
            } else {
                $error = 'Unable to update paragraph.';
            }
        }
    } else {
        $language = getSafePost('legacy_language', $paragraph['language'] ?? 'english');
        $content = trim($_POST['content'] ?? '');

        if ($language === '' || $content === '') {
            $error = 'Language and content are required.';
        } else {
            $stmt = $conn->prepare("UPDATE paragraphs SET language = ?, content = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("ssi", $language, $content, $id);
                $stmt->execute();
                $stmt->close();
                $success = 'Paragraph updated successfully.';
            } else {
                $error = 'Unable to update paragraph.';
            }
        }
    }

    if ($schemaReady) {
        $stmt = $conn->prepare(
            "SELECT p.id, p.language_id, p.exam_type_id, p.content, l.name AS language_name, e.name AS exam_type_name, e.wpm AS exam_type_wpm
             FROM passages p
             INNER JOIN languages l ON l.id = p.language_id
             INNER JOIN exam_types e ON e.id = p.exam_type_id
             WHERE p.id = ?"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $paragraph = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($paragraph) {
            $paragraph['level'] = detectTypingLevelSlugFromExamType($paragraph['exam_type_name'] ?? '', (int) ($paragraph['exam_type_wpm'] ?? 0));
        }
    } else {
        $stmt = $conn->prepare("SELECT id, language, content FROM paragraphs WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $paragraph = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}
?>

<div class="container-fluid px-0">
    <div class="bg-white shadow-sm rounded-3 p-4">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
            <div>
                <h3 class="mb-1">Edit Paragraph</h3>
                <p class="text-muted mb-0">Update the paragraph for the selected language and level.</p>
            </div>
            <a href="dashboard.php?page=paragraphs" class="btn btn-outline-dark btn-sm">Back</a>
        </div>

        <?php if (!empty($success)) echo successAlert(htmlspecialchars($success)); ?>
        <?php if (!empty($error)) echo errorAlert(htmlspecialchars($error)); ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">

            <?php if ($schemaReady) { ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Language</label>
                        <select name="language_id" class="form-select border-dark">
                            <?php foreach ($languages as $language) { ?>
                                <option value="<?php echo (int) $language['id']; ?>" <?php echo (int) $paragraph['language_id'] === (int) $language['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($language['name']); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Level</label>
                        <select name="level" class="form-select border-dark">
                            <?php foreach ($levelDefinitions as $slug => $definition) { ?>
                                <option value="<?php echo htmlspecialchars($slug); ?>" <?php echo ($paragraph['level'] ?? '') === $slug ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($definition['label']); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
            <?php } else { ?>
                <div class="mb-3">
                    <label class="form-label">Language</label>
                    <select name="legacy_language" class="form-select border-dark">
                        <?php foreach (['english' => 'English', 'marathi' => 'Marathi', 'hindi' => 'Hindi'] as $value => $label) { ?>
                            <option value="<?php echo $value; ?>" <?php echo ($paragraph['language'] ?? '') === $value ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
            <?php } ?>

            <div class="mt-3">
                <label class="form-label">Paragraph Content</label>
                <textarea name="content" rows="8" class="form-control border-dark" required><?php echo htmlspecialchars($paragraph['content']); ?></textarea>
                <div class="form-text text-muted">Line breaks and paragraph spacing are shown in the typing test exactly as entered here.</div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-dark">Update Paragraph</button>
            </div>
        </form>
    </div>
</div>
