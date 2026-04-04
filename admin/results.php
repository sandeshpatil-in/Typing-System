<?php
if (!isAdminLoggedIn()) {
    redirect('admin/login.php');
}

$result = false;
$hasAttemptAccessType = function_exists('dbColumnExists') && dbTableExists($conn, 'test_attempts') && dbColumnExists($conn, 'test_attempts', 'access_type');

if (function_exists('dbTableExists') && dbTableExists($conn, 'test_attempts')) {
    $result = $conn->query(
        "SELECT ta.id,
                COALESCE(s.name, 'Guest User') AS student_name,
                " . ($hasAttemptAccessType ? "COALESCE(ta.access_type, 'guest')" : "'paid'") . " AS access_type,
                ta.language,
                COALESCE(ta.exam_type, 'typing') AS exam_type,
                ta.wpm,
                ta.accuracy,
                ta.created_at
         FROM test_attempts ta
         LEFT JOIN students s ON ta.student_id = s.id
         ORDER BY ta.id DESC"
    );
} elseif (function_exists('dbTableExists') && dbTableExists($conn, 'results')) {
    $result = $conn->query(
        "SELECT r.id,
                s.name AS student_name,
                'paid' AS access_type,
                r.language,
                'typing' AS exam_type,
                r.wpm,
                r.accuracy,
                r.created_at
         FROM results r
         JOIN students s ON r.student_id = s.id
         ORDER BY r.id DESC"
    );
}
?>

<div class="container mt-5">
    <h3 class="text-center pb-3">Results</h3>

    <table class="table table-bordered border-1 border-dark">
        <tr class="table-light border-1 border-dark">
            <th>ID</th>
            <th>Student</th>
            <th>Access</th>
            <th>Language</th>
            <th>Exam</th>
            <th>WPM</th>
            <th>Accuracy</th>
            <th>Date</th>
        </tr>

        <?php if (!$result || $result->num_rows === 0) { ?>
            <tr><td colspan="8" class="text-center text-muted">No results found.</td></tr>
        <?php } ?>

        <?php while ($result && ($row = $result->fetch_assoc())) { ?>
            <tr>
                <td><?php echo htmlspecialchars((string) $row['id']); ?></td>
                <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                <td>
                    <span class="badge <?php echo ($row['access_type'] ?? 'guest') === 'guest' ? 'text-bg-warning text-dark' : 'text-bg-success'; ?>">
                        <?php echo htmlspecialchars(ucfirst((string) ($row['access_type'] ?? 'guest'))); ?>
                    </span>
                </td>
                <td class="text-capitalize"><?php echo htmlspecialchars($row['language']); ?></td>
                <td class="text-capitalize"><?php echo htmlspecialchars($row['exam_type']); ?></td>
                <td><?php echo htmlspecialchars((string) $row['wpm']); ?></td>
                <td><?php echo htmlspecialchars((string) $row['accuracy']); ?>%</td>
                <td><?php echo htmlspecialchars(formatDate($row['created_at'])); ?></td>
            </tr>
        <?php } ?>
    </table>
</div>
