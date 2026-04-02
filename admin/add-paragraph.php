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
    'exam_type_id' => '',
    'content' => ''
];
$languages = [];
$examTypeMap = [];

if ($schemaReady) {
    if ($result = $conn->query("SELECT id, name FROM languages ORDER BY name ASC")) {
        while ($row = $result->fetch_assoc()) {
            $languages[] = $row;
        }
    }

    if ($result = $conn->query("SELECT id, language_id, name, wpm FROM exam_types ORDER BY language_id ASC, wpm ASC, name ASC")) {
        while ($row = $result->fetch_assoc()) {
            $languageId = (int) $row['language_id'];
            if (!isset($examTypeMap[$languageId])) {
                $examTypeMap[$languageId] = [];
            }
            $examTypeMap[$languageId][] = [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'wpm' => (int) $row['wpm']
            ];
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['language_id'] = getSafePost('language_id', '');
    $formData['exam_type_id'] = getSafePost('exam_type_id', '');
    $formData['content'] = trim($_POST['content'] ?? '');

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = 'Session expired. Please try again.';
    } elseif ($schemaReady) {
        $languageId = (int) $formData['language_id'];
        $examTypeId = (int) $formData['exam_type_id'];

        if ($languageId <= 0 || $examTypeId <= 0 || $formData['content'] === '') {
            $error = 'Language, exam type, and content are required.';
        } else {
            $stmt = $conn->prepare("INSERT INTO passages (language_id, exam_type_id, content) VALUES (?, ?, ?)");

            if ($stmt) {
                $stmt->bind_param("iis", $languageId, $examTypeId, $formData['content']);
                $stmt->execute();
                $stmt->close();
                $success = 'Paragraph saved successfully.';
                $formData = ['language_id' => '', 'exam_type_id' => '', 'content' => ''];
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
                $formData = ['language_id' => '', 'exam_type_id' => '', 'content' => ''];
            } else {
                $error = 'Unable to save paragraph.';
            }
        }
    } else {
        $error = 'No compatible paragraph table exists in the database.';
    }
}
?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-1">Add Paragraph</h3>
                <p class="text-muted mb-0">Save a paragraph for the exact language and exam type students will practice.</p>
            </div>
            <a href="dashboard.php?page=paragraphs" class="btn btn-outline-dark">Back</a>
        </div>

        <?php if (!empty($success)) echo successAlert(htmlspecialchars($success)); ?>
        <?php if (!empty($error)) echo errorAlert(htmlspecialchars($error)); ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">

            <?php if ($schemaReady) { ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Language</label>
                        <select name="language_id" id="languageId" class="form-select border-dark">
                            <option value="">Select language</option>
                            <?php foreach ($languages as $language) { ?>
                                <option value="<?php echo (int) $language['id']; ?>" <?php echo (string) $formData['language_id'] === (string) $language['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($language['name']); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Exam Type</label>
                        <select name="exam_type_id" id="examTypeId" class="form-select border-dark">
                            <option value="">Select exam type</option>
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
                <div class="form-text text-muted">Line breaks and paragraph spacing are shown in the student typing test exactly as entered here.</div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-dark">Save Paragraph</button>
            </div>
        </form>
    </div>
</div>

<?php if ($schemaReady) { ?>
<script>
const adminExamTypeMap = <?php echo json_encode($examTypeMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
const adminLanguageSelect = document.getElementById('languageId');
const adminExamTypeSelect = document.getElementById('examTypeId');
const selectedAdminExamTypeId = '<?php echo htmlspecialchars((string) $formData['exam_type_id']); ?>';

function renderAdminExamTypes(languageId) {
    adminExamTypeSelect.innerHTML = '<option value="">Select exam type</option>';
    const items = adminExamTypeMap[languageId] || [];

    items.forEach((item) => {
        const option = document.createElement('option');
        option.value = item.id;
        option.textContent = `${item.name} (${item.wpm} WPM)`;
        if (String(item.id) === selectedAdminExamTypeId) {
            option.selected = true;
        }
        adminExamTypeSelect.appendChild(option);
    });
}

if (adminLanguageSelect && adminExamTypeSelect) {
    renderAdminExamTypes(adminLanguageSelect.value);
    adminLanguageSelect.addEventListener('change', () => {
        adminExamTypeSelect.innerHTML = '<option value="">Select exam type</option>';
        const items = adminExamTypeMap[adminLanguageSelect.value] || [];

        items.forEach((item) => {
            const option = document.createElement('option');
            option.value = item.id;
            option.textContent = `${item.name} (${item.wpm} WPM)`;
            adminExamTypeSelect.appendChild(option);
        });
    });
}
</script>
<?php } ?>
