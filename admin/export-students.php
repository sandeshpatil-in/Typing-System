<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/admin_students.php';

if (!isAdminLoggedIn()) {
    redirect('admin/login.php');
}

$rows = getAdminStudentListRows($conn);
$filename = 'student-leads-' . date('Y-m-d-His') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

fputcsv($output, [
    'ID',
    'Name',
    'Email ID',
    'Contact Number'
]);

foreach ($rows as $row) {
    fputcsv($output, [
        (int) ($row['id'] ?? 0),
        (string) ($row['name'] ?? ''),
        (string) ($row['email'] ?? ''),
        getAdminStudentDisplayContactValue($row)
    ]);
}

fclose($output);
exit();
