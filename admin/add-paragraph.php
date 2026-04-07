<?php
if (!isAdminLoggedIn()) {
    redirect('admin/login.php');
}

$schemaReady = function_exists('dbTableExists')
    && dbTableExists($conn, 'passages')
    && dbTableExists($conn, 'languages')
    && dbTableExists($conn, 'exam_types');

$error = '';
$success = '';
$formData = [
    'language_id' => '',
    'level' => '',
    'content' => ''
];
$languages = [];
$levelDefinitions = getTypingLevelDefinitions();

if ($schemaReady) {
    ensureTypingLevelsForAllLanguages($conn);

    if ($result = $conn->query("SELECT id, name FROM languages ORDER BY name ASC")) {
        while ($row = $result->fetch_assoc()) {
            $languages[] = $row;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['language_id'] = getSafePost('language_id', '');
    $formData['level'] = normalizeTypingLevelSlug(getSafePost('level', ''));
    $formData['content'] = trim($_POST['content'] ?? '');

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = 'Session expired. Please try again.';
    } elseif ($schemaReady) {
        $languageId = (int) $formData['language_id'];
        $levelSlug = $formData['level'];

        if ($languageId <= 0 || $levelSlug === '' || $formData['content'] === '') {
            $error = 'Language, level, and content are required.';
        } else {
            $examTypeId = getTypingLevelExamTypeId($conn, $languageId, $levelSlug);
            $stmt = $examTypeId > 0
                ? $conn->prepare("INSERT INTO passages (language_id, exam_type_id, content) VALUES (?, ?, ?)")
                : false;

            if ($stmt) {
                $stmt->bind_param("iis", $languageId, $examTypeId, $formData['content']);
                $stmt->execute();
                $stmt->close();
                $success = 'Paragraph saved successfully.';
                $formData = ['language_id' => '', 'level' => '', 'content' => ''];
            } else {
                $error = 'Unable to save paragraph.';
            }
        }
    } elseif (function_exists('dbTableExists') && dbTableExists($conn, 'paragraphs')) {
        $language = getSafePost('legacy_language', '');

        if ($language === '' || $formData['content'] === '') {
            $error = 'Language and content are required.';
        } else {
            $stmt = $conn->prepare("INSERT INTO paragraphs (language, content) VALUES (?, ?)");
            if ($stmt) {
                $stmt->bind_param("ss", $language, $formData['content']);
                $stmt->execute();
                $stmt->close();
                $success = 'Paragraph saved successfully.';
                $formData = ['language_id' => '', 'level' => '', 'content' => ''];
            } else {
                $error = 'Unable to save paragraph.';
            }
        }
    } else {
        $error = 'No compatible paragraph table exists in the database.';
    }
}
?>

<div class="container-fluid px-0">
    <div class="bg-white shadow-sm rounded-3 p-4">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
            <div>
                <h3 class="mb-1">Add Paragraph</h3>
                <p class="text-muted mb-0">Save a paragraph for the selected language and level.</p>
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
                            <option value="">Select language</option>
                            <?php foreach ($languages as $language) { ?>
                                <option value="<?php echo (int) $language['id']; ?>" <?php echo (string) $formData['language_id'] === (string) $language['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($language['name']); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Level</label>
                        <select name="level" class="form-select border-dark">
                            <option value="">Select level</option>
                            <?php foreach ($levelDefinitions as $slug => $definition) { ?>
                                <option value="<?php echo htmlspecialchars($slug); ?>" <?php echo $formData['level'] === $slug ? 'selected' : ''; ?>>
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
                        <option value="english">English</option>
                        <option value="marathi">Marathi</option>
                        <option value="hindi">Hindi</option>
                    </select>
                </div>
            <?php } ?>

            <div class="mt-3">
                <label class="form-label">Paragraph Content</label>
                <textarea name="content" rows="8" class="form-control border-dark" required><?php echo htmlspecialchars($formData['content']); ?></textarea>
                <div class="form-text text-muted">Line breaks and paragraph spacing are shown in the typing test exactly as entered here.</div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-dark">Save Paragraph</button>
            </div>
        </form>
    </div>
</div>
