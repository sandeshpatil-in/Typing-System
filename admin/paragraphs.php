<?php
if (!isAdminLoggedIn()) {
    redirect('admin/login.php');
}

$items = [];
$schemaReady = function_exists('dbTableExists')
    && dbTableExists($conn, 'passages')
    && dbTableExists($conn, 'languages')
    && dbTableExists($conn, 'exam_types');

if ($schemaReady) {
    ensureTypingLevelsForAllLanguages($conn);
    $result = $conn->query(
        "SELECT p.id, l.name AS language_name, e.name AS exam_type_name, e.wpm, p.content
         FROM passages p
         INNER JOIN languages l ON l.id = p.language_id
         INNER JOIN exam_types e ON e.id = p.exam_type_id
         ORDER BY l.name ASC, e.wpm ASC, p.id DESC"
    );

    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
} elseif (function_exists('dbTableExists') && dbTableExists($conn, 'paragraphs')) {
    $result = $conn->query("SELECT id, language, content FROM paragraphs ORDER BY id DESC");

    while ($row = $result->fetch_assoc()) {
        $items[] = [
            'id' => $row['id'],
            'language_name' => ucfirst($row['language']),
            'exam_type_name' => 'General',
            'wpm' => '-',
            'content' => $row['content']
        ];
    }
}
?>

<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
        <div>
            <h3 class="mb-1">Paragraphs</h3>
            <p class="text-muted mb-0">Manage paragraph content by language and level in the same clean table view.</p>
        </div>
        <div>
            <a href="dashboard.php?page=add-paragraph" class="btn btn-dark btn-sm">Add Paragraph</a>
        </div>
    </div>

    <?php if (!$schemaReady) { ?>
        <?php echo warningAlert('Dynamic passage tables are missing. Import config/typing_preference_schema.sql for language and level-wise paragraph control.'); ?>
    <?php } ?>

    <div class="table-responsive shadow-sm rounded-3">
        <table class="table table-bordered table-hover align-middle bg-white mb-0" style="min-width: 900px;">
            <thead class="table-dark">
                <tr>
                    <th class="text-nowrap">ID</th>
                    <th class="text-nowrap">Language</th>
                    <th class="text-nowrap">Level</th>
                    <th>Preview</th>
                    <th class="text-nowrap">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)) { ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">No paragraphs found.</td>
                    </tr>
                <?php } ?>

                <?php foreach ($items as $row) { ?>
                    <?php
                    $levelSlug = detectTypingLevelSlugFromExamType($row['exam_type_name'] ?? '', (int) ($row['wpm'] ?? 0));
                    $levelLabel = $levelSlug !== '' ? getTypingLevelLabel($levelSlug) : 'General';
                    ?>
                    <tr>
                        <td class="text-nowrap fw-semibold"><?php echo htmlspecialchars((string) $row['id']); ?></td>
                        <td class="text-nowrap"><?php echo htmlspecialchars($row['language_name']); ?></td>
                        <td class="text-nowrap">
                            <?php echo htmlspecialchars($levelLabel); ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars(function_exists('mb_substr') ? mb_substr(trim($row['content']), 0, 120) : substr(trim($row['content']), 0, 120)); ?>...
                        </td>
                        <td class="text-nowrap">
                            <div class="d-flex flex-wrap gap-2">
                                <a href="dashboard.php?page=edit-paragraph&id=<?php echo urlencode((string) $row['id']); ?>" class="btn btn-dark btn-sm">Edit</a>
                                <a href="dashboard.php?page=delete-paragraph&id=<?php echo urlencode((string) $row['id']); ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete this paragraph?');">Delete</a>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
