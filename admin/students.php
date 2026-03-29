<?php
if (!isAdminLoggedIn()) {
    redirect('admin/login.php');
}

$hasExpiryDate = function_exists('dbColumnExists') && dbColumnExists($conn, 'students', 'expiry_date');
$studentQuery = $hasExpiryDate
    ? "SELECT id, name, email, status, expiry_date FROM students ORDER BY id DESC"
    : "SELECT id, name, email, status FROM students ORDER BY id DESC";
$result = $conn->query($studentQuery);
?>

<div class="container mt-5">
    <h3 class="text-center pb-3">Manage Students</h3>

    <?php
    $studentMessage = getFlash('admin_student_message');
    if (!empty($studentMessage)) {
        echo successAlert(htmlspecialchars($studentMessage));
    }
    ?>

    <table class="table table-bordered border-1 border-dark">
        <tr class="table-light border-1 border-dark">
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Status</th>
            <th>Action</th>
        </tr>

        <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
                <td><?php echo htmlspecialchars((string) $row['id']); ?></td>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td>
                    <?php if ((int) $row['status'] === 1) { ?>
                        <span class="badge text-bg-success">Active</span><br>
                        <small class="text-muted">
                            <?php if ($hasExpiryDate && !empty($row['expiry_date'])) { ?>
                                Expire: <?php echo htmlspecialchars((string) $row['expiry_date']); ?>
                            <?php } else { ?>
                                Plan active
                            <?php } ?>
                        </small>
                    <?php } else { ?>
                        <span class="badge text-bg-danger">Inactive</span>
                    <?php } ?>
                </td>
                <td>
                    <?php if ((int) $row['status'] === 0) { ?>
                        <form method="POST" action="dashboard.php?page=activate" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars((string) $row['id']); ?>">
                            <button type="submit" class="btn btn-dark btn-sm">Activate</button>
                        </form>
                    <?php } else { ?>
                        <button type="button" class="btn btn-secondary btn-sm" disabled>Activated</button>
                    <?php } ?>
                </td>
            </tr>
        <?php } ?>
    </table>
</div>
