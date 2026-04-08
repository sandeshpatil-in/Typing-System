<?php
if (!isAdminLoggedIn()) {
    redirect('admin/login.php');
}

require_once __DIR__ . '/../includes/admin_students.php';

$rows = getAdminStudentListRows($conn);
$studentMessage = getFlash('admin_student_message');
$studentMessageType = getFlash('admin_student_message_type', 'success');
$students = [];

foreach ($rows as $row) {
    $isActive = hasActivePlan($row);
    $paymentMode = getAdminStudentPaymentModeLabel($row);
    $contactValue = getAdminStudentDisplayContactValue($row);
    $joinedDisplay = !empty($row['created_at']) ? formatDate($row['created_at'], 'd-m-Y') : 'Not available';

    $students[] = [
        'id' => (int) ($row['id'] ?? 0),
        'name' => (string) ($row['name'] ?? ''),
        'email' => (string) ($row['email'] ?? ''),
        'contact_value' => $contactValue,
        'contact_href' => preg_replace('/\D+/', '', $contactValue),
        'joined_display' => $joinedDisplay,
        'is_active' => $isActive,
        'status_badge_class' => $isActive ? 'text-bg-success' : 'text-bg-secondary',
        'payment_mode' => $paymentMode,
        'payment_badge_class' => $paymentMode === 'Online Pay'
            ? 'text-bg-primary'
            : ($paymentMode === 'Manual Pay' ? 'text-bg-warning text-dark' : 'text-bg-secondary')
    ];
}
?>

<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
        <div>
            <h3 class="mb-1">Manage Students</h3>
            <p class="text-muted mb-0">Simple student list with export and quick actions.</p>
        </div>
        <div>
            <a href="export-students.php" class="btn btn-dark btn-sm">Export CSV</a>
        </div>
    </div>

    <?php if (!empty($studentMessage)) { ?>
        <?php
        switch ($studentMessageType) {
            case 'error':
                echo errorAlert(htmlspecialchars($studentMessage));
                break;
            case 'warning':
                echo warningAlert(htmlspecialchars($studentMessage));
                break;
            default:
                echo successAlert(htmlspecialchars($studentMessage));
                break;
        }
        ?>
    <?php } ?>

    <div class="table-responsive shadow-sm rounded-3">
        <table class="table table-bordered table-hover align-middle bg-white mb-0" style="min-width: 980px;">
            <thead class="table-dark">
                <tr>
                    <th class="text-nowrap">ID</th>
                    <th class="text-nowrap">Name</th>
                    <th class="text-nowrap">Email ID</th>
                    <th class="text-nowrap">Contact No</th>
                    <th class="text-nowrap">Joined Date</th>
                    <th class="text-nowrap">Status</th>
                    <th class="text-nowrap">Pay Mode</th>
                    <th class="text-nowrap">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($students)) { ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No students found.</td>
                    </tr>
                <?php } ?>

                <?php foreach ($students as $student) { ?>
                    <tr>
                        <td class="text-nowrap fw-semibold"><?php echo htmlspecialchars((string) $student['id']); ?></td>
                        <td class="text-nowrap"><?php echo htmlspecialchars($student['name']); ?></td>
                        <td class="text-nowrap"><?php echo htmlspecialchars($student['email']); ?></td>
                        <td class="text-nowrap">
                            <?php if ($student['contact_value'] !== '' && $student['contact_href'] !== '') { ?>
                                <a href="tel:<?php echo htmlspecialchars($student['contact_href']); ?>">
                                    <?php echo htmlspecialchars($student['contact_value']); ?>
                                </a>
                            <?php } else { ?>
                                <span class="text-muted">Not provided</span>
                            <?php } ?>
                        </td>
                        <td class="text-nowrap"><?php echo htmlspecialchars($student['joined_display']); ?></td>
                        <td class="text-nowrap">
                            <span class="badge <?php echo $student['status_badge_class']; ?>">
                                <?php echo $student['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td class="text-nowrap">
                            <span class="badge <?php echo $student['payment_badge_class']; ?>">
                                <?php echo htmlspecialchars($student['payment_mode']); ?>
                            </span>
                        </td>
                        <td class="text-nowrap">
                            <div class="d-flex flex-wrap gap-2">
                                <?php if ($student['is_active']) { ?>
                                    <form method="POST" action="deactivate-student.php" onsubmit="return confirm('Deactivate this student now?');" class="m-0">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
                                        <input type="hidden" name="id" value="<?php echo htmlspecialchars((string) $student['id']); ?>">
                                        <button type="submit" class="btn btn-outline-warning btn-sm">Disable</button>
                                    </form>
                                <?php } else { ?>
                                    <form method="POST" action="activate-student.php" class="m-0">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
                                        <input type="hidden" name="id" value="<?php echo htmlspecialchars((string) $student['id']); ?>">
                                        <button type="submit" class="btn btn-dark btn-sm">Activate</button>
                                    </form>
                                <?php } ?>

                                <form method="POST" action="delete-student.php" onsubmit="return confirm('Delete this student and related records? This cannot be undone.');" class="m-0">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars((string) $student['id']); ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
