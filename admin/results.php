<?php
if (!isAdminLoggedIn()) {
    redirect('admin/login.php');
}

$items = [];
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

    while ($result && ($row = $result->fetch_assoc())) {
        $items[] = $row;
    }
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

    while ($result && ($row = $result->fetch_assoc())) {
        $items[] = $row;
    }
}
?>

<div class="container-fluid px-0">
    <div class="mb-4">
        <h3 class="mb-1">Results</h3>
        <p class="text-muted mb-0">Results table now follows the same admin design as students.</p>
    </div>

    <div class="table-responsive shadow-sm rounded-3">
        <table class="table table-bordered table-hover align-middle bg-white mb-0" style="min-width: 950px;">
            <thead class="table-dark">
                <tr>
                    <th class="text-nowrap">ID</th>
                    <th class="text-nowrap">Student</th>
                    <th class="text-nowrap">Access</th>
                    <th class="text-nowrap">Language</th>
                    <th class="text-nowrap">Exam</th>
                    <th class="text-nowrap">WPM</th>
                    <th class="text-nowrap">Accuracy</th>
                    <th class="text-nowrap">Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)) { ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No results found.</td>
                    </tr>
                <?php } ?>

                <?php foreach ($items as $row) { ?>
                    <tr>
                        <td class="text-nowrap fw-semibold"><?php echo htmlspecialchars((string) $row['id']); ?></td>
                        <td class="text-nowrap"><?php echo htmlspecialchars($row['student_name']); ?></td>
                        <td class="text-nowrap">
                            <span class="badge <?php echo ($row['access_type'] ?? 'guest') === 'guest' ? 'text-bg-warning text-dark' : 'text-bg-success'; ?>">
                                <?php echo htmlspecialchars(ucfirst((string) ($row['access_type'] ?? 'guest'))); ?>
                            </span>
                        </td>
                        <td class="text-nowrap text-capitalize"><?php echo htmlspecialchars($row['language']); ?></td>
                        <td class="text-nowrap text-capitalize"><?php echo htmlspecialchars($row['exam_type']); ?></td>
                        <td class="text-nowrap"><?php echo htmlspecialchars((string) $row['wpm']); ?></td>
                        <td class="text-nowrap"><?php echo htmlspecialchars((string) $row['accuracy']); ?>%</td>
                        <td class="text-nowrap"><?php echo htmlspecialchars(formatDate($row['created_at'], 'd-m-Y')); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
