<?php
if (!isAdminLoggedIn()) {
    redirect('admin/login.php');
}

$hasExpiryDate = dbColumnExists($conn, 'students', 'expiry_date');
$hasPlans = dbTableExists($conn, 'plans');
$hasPlanName = $hasPlans && dbColumnExists($conn, 'plans', 'plan_name');
$hasRazorpayOrderId = $hasPlans && dbColumnExists($conn, 'plans', 'razorpay_order_id');
$hasRazorpayPaymentId = $hasPlans && dbColumnExists($conn, 'plans', 'razorpay_payment_id');
$hasPaymentMethod = $hasPlans && dbColumnExists($conn, 'plans', 'payment_method');
$hasAttemptAccessType = dbTableExists($conn, 'test_attempts') && dbColumnExists($conn, 'test_attempts', 'access_type');
$planCondition = $hasPlans ? getPaidPlanCondition($conn, 'p') : '1 = 0';
$expirySelect = $hasExpiryDate
    ? 's.expiry_date'
    : ($hasPlans
        ? "(SELECT p.expiry_date FROM plans p WHERE p.student_id = s.id AND {$planCondition} ORDER BY COALESCE(p.expiry_date, '0000-00-00') DESC, p.id DESC LIMIT 1)"
        : "NULL");

$attemptCountSql = '0';
$guestAttemptCountSql = '0';
$paidAttemptCountSql = '0';
if (dbTableExists($conn, 'test_attempts')) {
    $attemptCountSql = '(SELECT COUNT(*) FROM test_attempts ta WHERE ta.student_id = s.id)';
    if ($hasAttemptAccessType) {
        $guestAttemptCountSql = "(SELECT COUNT(*) FROM test_attempts ta WHERE ta.student_id = s.id AND ta.access_type = 'guest')";
        $paidAttemptCountSql = "(SELECT COUNT(*) FROM test_attempts ta WHERE ta.student_id = s.id AND ta.access_type = 'paid')";
    } else {
        $paidAttemptCountSql = '(SELECT COUNT(*) FROM test_attempts ta WHERE ta.student_id = s.id)';
    }
} elseif (dbTableExists($conn, 'results')) {
    $attemptCountSql = '(SELECT COUNT(*) FROM results r WHERE r.student_id = s.id)';
    $paidAttemptCountSql = '(SELECT COUNT(*) FROM results r WHERE r.student_id = s.id)';
}

$studentQuery = "
    SELECT
        s.id,
        s.name,
        s.email,
        s.status,
        {$expirySelect} AS expiry_date,
        {$attemptCountSql} AS attempts_count,
        {$guestAttemptCountSql} AS guest_attempts_count,
        {$paidAttemptCountSql} AS paid_attempts_count,
        " . ($hasPlans && $hasPlanName ? "(SELECT p.plan_name FROM plans p WHERE p.student_id = s.id AND {$planCondition} ORDER BY COALESCE(p.expiry_date, '0000-00-00') DESC, p.id DESC LIMIT 1)" : "NULL") . " AS plan_name,
        " . ($hasPlans && $hasRazorpayOrderId ? "(SELECT p.razorpay_order_id FROM plans p WHERE p.student_id = s.id AND {$planCondition} ORDER BY COALESCE(p.expiry_date, '0000-00-00') DESC, p.id DESC LIMIT 1)" : "NULL") . " AS razorpay_order_id,
        " . ($hasPlans && $hasRazorpayPaymentId ? "(SELECT p.razorpay_payment_id FROM plans p WHERE p.student_id = s.id AND {$planCondition} ORDER BY COALESCE(p.expiry_date, '0000-00-00') DESC, p.id DESC LIMIT 1)" : "NULL") . " AS razorpay_payment_id,
        " . ($hasPlans && $hasPaymentMethod ? "(SELECT p.payment_method FROM plans p WHERE p.student_id = s.id AND {$planCondition} ORDER BY COALESCE(p.expiry_date, '0000-00-00') DESC, p.id DESC LIMIT 1)" : "NULL") . " AS payment_method
    FROM students s
    ORDER BY s.id DESC
";

$result = $conn->query($studentQuery);
?>

<div class="container mt-5">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 pb-3">
        <div>
            <h3 class="mb-1">Manage Students</h3>
            <p class="text-muted mb-0">Activate hand cash payments, review test progress, and confirm which students currently have 30-day access.</p>
        </div>
        <div class="border rounded-3 bg-light px-3 py-2">
            <div class="small text-muted">Free test limit</div>
            <div class="fw-semibold"><?php echo GUEST_TEST_LIMIT; ?> attempts</div>
        </div>
    </div>

    <?php
    $studentMessage = getFlash('admin_student_message');
    if (!empty($studentMessage)) {
        echo successAlert(htmlspecialchars($studentMessage));
    }
    ?>

    <div class="table-responsive">
        <table class="table table-bordered border-1 border-dark align-middle">
            <thead class="table-light border-1 border-dark">
                <tr>
                    <th>ID</th>
                    <th>Student</th>
                    <th>Attempts</th>
                    <th>Plan Status</th>
                    <th>Payment</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$result || $result->num_rows === 0) { ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">No students found.</td>
                    </tr>
                <?php } ?>

                <?php while ($result && ($row = $result->fetch_assoc())) { ?>
                    <?php
                    $isActive = hasActivePlan($row);
                    $attemptCount = (int) ($row['attempts_count'] ?? 0);
                    $guestAttemptCount = (int) ($row['guest_attempts_count'] ?? 0);
                    $paidAttemptCount = (int) ($row['paid_attempts_count'] ?? 0);
                    $paymentLabel = getPlanPaymentLabel($row);
                    $planTitle = !empty($row['plan_name'])
                        ? (string) $row['plan_name']
                        : ($paymentLabel === 'Razorpay'
                            ? PLAN_NAME
                            : ($paymentLabel === 'Hand Cash' ? 'Hand Cash Activation' : 'Waiting for payment'));
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string) $row['id']); ?></td>
                        <td>
                            <div class="fw-semibold"><?php echo htmlspecialchars($row['name']); ?></div>
                            <div class="text-muted small"><?php echo htmlspecialchars($row['email']); ?></div>
                        </td>
                        <td>
                            <div class="fw-semibold"><?php echo $attemptCount; ?> completed</div>
                            <small class="text-muted">
                                Guest free tests used: <?php echo min(GUEST_TEST_LIMIT, $guestAttemptCount); ?> / <?php echo GUEST_TEST_LIMIT; ?>
                                <?php if ($paidAttemptCount > 0) { ?>
                                    | Paid attempts: <?php echo $paidAttemptCount; ?>
                                <?php } ?>
                            </small>
                            <div class="small text-muted mt-1">
                                <?php if ($guestAttemptCount >= GUEST_TEST_LIMIT) { ?>
                                    Free guest limit completed
                                <?php } else { ?>
                                    <?php echo max(0, GUEST_TEST_LIMIT - $guestAttemptCount); ?> free tests remaining before signup/payment
                                <?php } ?>
                            </div>
                        </td>
                        <td>
                            <?php if ($isActive) { ?>
                                <span class="badge text-bg-success">Active</span>
                                <div class="small text-muted mt-1">
                                    Access until <?php echo htmlspecialchars((string) $row['expiry_date']); ?>
                                </div>
                            <?php } else { ?>
                                <span class="badge text-bg-danger">Inactive</span>
                                <div class="small text-muted mt-1">
                                    <?php if (!empty($row['expiry_date'])) { ?>
                                        Expired on <?php echo htmlspecialchars((string) $row['expiry_date']); ?>
                                    <?php } else { ?>
                                        No paid plan yet
                                    <?php } ?>
                                </div>
                            <?php } ?>
                        </td>
                        <td>
                            <div class="fw-semibold"><?php echo htmlspecialchars($paymentLabel); ?></div>
                            <small class="text-muted">
                                <?php echo htmlspecialchars($planTitle); ?>
                            </small>
                        </td>
                        <td>
                            <?php if (!$isActive) { ?>
                                <form method="POST" action="dashboard.php?page=activate" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars((string) $row['id']); ?>">
                                    <button type="submit" class="btn btn-dark btn-sm">Activate 30 Days</button>
                                </form>
                            <?php } else { ?>
                                <button type="button" class="btn btn-secondary btn-sm" disabled>Already Active</button>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
