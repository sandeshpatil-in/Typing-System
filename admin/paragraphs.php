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

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h3 class="mb-1">Paragraph Management</h3>
                <p class="text-muted mb-0">Add passages for a particular language and exam type so students only see the correct content.</p>
            </div>
            <a href="dashboard.php?page=add-paragraph" class="btn btn-dark">Add Paragraph</a>
        </div>

        <?php if (!$schemaReady) { ?>
            <?php echo warningAlert('Dynamic passage tables are missing. Import config/typing_preference_schema.sql for language and exam-wise paragraph control.'); ?>
        <?php } ?>

        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Language</th>
                        <th>Exam Type</th>
                        <th>Preview</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)) { ?>
                        <tr><td colspan="5" class="text-center text-muted">No paragraphs found.</td></tr>
                    <?php } ?>

                    <?php foreach ($items as $row) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string) $row['id']); ?></td>
                            <td><?php echo htmlspecialchars($row['language_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['exam_type_name']) . ($row['wpm'] !== '-' ? ' (' . htmlspecialchars((string) $row['wpm']) . ' WPM)' : ''); ?></td>
                            <td><?php echo htmlspecialchars(function_exists('mb_substr') ? mb_substr(trim($row['content']), 0, 120) : substr(trim($row['content']), 0, 120)); ?>...</td>
                            <td class="d-flex gap-2 flex-wrap">
                                <a href="dashboard.php?page=edit-paragraph&id=<?php echo urlencode((string) $row['id']); ?>" class="btn btn-dark btn-sm">Edit</a>
                                <a href="dashboard.php?page=delete-paragraph&id=<?php echo urlencode((string) $row['id']); ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete this paragraph?');">Delete</a>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
