<?php
require_once __DIR__ . '/../includes/init.php';

if (!isStudentLoggedIn()) {
    redirect('account/login.php');
}

$student = syncStudentPlanStatus($conn, getStudentId());

if (!$student) {
    logoutCurrentUser();
    redirect('account/login.php');
}

$message = getFlash('auth_message');
$plan = getLatestPaidPlan($conn, $student['id']);
$hasPlan = hasActivePlan($student);
$paymentLabel = getPlanPaymentLabel($plan);
$attemptSummary = getStudentAttemptSummary($conn, (int) $student['id']);
$attemptCount = (int) ($attemptSummary['total'] ?? 0);
$planName = $plan['plan_name'] ?? ($paymentLabel === 'Hand Cash' ? 'Hand Cash Activation' : PLAN_NAME);
$planStart = $plan['start_date'] ?? ($student['plan_start_date'] ?? 'Not started');
$planExpiry = $plan['expiry_date'] ?? ($student['expiry_date'] ?? 'Not active');

$attempts = getStudentAttemptsResult($conn, (int) $student['id'], 20);
?>

<?php include("../includes/header.php"); ?>

<div class="container my-5 min-vh-100">
    <?php if (!empty($message)) echo successAlert(htmlspecialchars($message)); ?>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-dark shadow-sm h-100">
                <div class="card-body">
                    <h3 class="mb-3"><?php echo htmlspecialchars($student['name']); ?></h3>
                    <p class="mb-2"><strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></p>
                    <p class="mb-2"><strong>Plan:</strong> <?php echo $hasPlan ? 'Active' : 'Inactive'; ?></p>
                    <p class="mb-2"><strong>Payment:</strong> <?php echo htmlspecialchars($paymentLabel); ?></p>
                    <p class="mb-4"><strong>Expiry:</strong> <?php echo htmlspecialchars((string) ($student['expiry_date'] ?? 'Not active')); ?></p>

                    <?php if ($hasPlan) { ?>
                        <a href="../typing-preference.php" class="btn btn-dark w-100 mb-2">Start Typing</a>
                    <?php } else { ?>
                        <a href="../payment.php" class="btn btn-dark w-100 mb-2">Activate Plan</a>
                    <?php } ?>

                    <a href="../typing-preference.php" class="btn btn-outline-dark w-100">View Test Modes</a>

                    <div class="mt-4 border-top pt-3">
                        <div class="small text-muted">Total attempts saved</div>
                        <div class="fw-semibold"><?php echo $attemptCount; ?></div>
                        <div class="small text-muted mt-1">
                            Guest free tests: <?php echo min(GUEST_TEST_LIMIT, (int) ($attemptSummary['guest_total'] ?? 0)); ?> / <?php echo GUEST_TEST_LIMIT; ?>
                            <?php if (($attemptSummary['paid_total'] ?? 0) > 0) { ?>
                                | Paid attempts: <?php echo (int) ($attemptSummary['paid_total'] ?? 0); ?>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <?php if (!$hasPlan) { ?>
                <div class="alert alert-warning border-dark shadow-sm">
                    Your plan is not active right now. Pay with Razorpay or contact admin if you already paid by hand cash.
                </div>
            <?php } ?>

            <div class="card border-dark shadow-sm mb-4">
                <div class="card-body">
                    <h4 class="mb-3">Plan Summary</h4>
                    <?php if ($plan) { ?>
                        <div class="row">
                            <div class="col-md-3"><strong>Plan</strong><div><?php echo htmlspecialchars($planName); ?></div></div>
                            <div class="col-md-3"><strong>Payment</strong><div><?php echo htmlspecialchars($paymentLabel); ?></div></div>
                            <div class="col-md-3"><strong>Started</strong><div><?php echo htmlspecialchars((string) $planStart); ?></div></div>
                            <div class="col-md-3"><strong>Expires</strong><div><?php echo htmlspecialchars((string) $planExpiry); ?></div></div>
                        </div>
                    <?php } else { ?>
                        <p class="mb-0 text-muted">No paid plan found yet. Complete Razorpay payment or ask admin to confirm your hand cash payment to unlock unlimited tests.</p>
                    <?php } ?>
                </div>
            </div>

            <div class="card border-dark shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="mb-0">Recent Attempts</h4>
                        <span class="badge text-bg-light">Last 20 attempts</span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Access</th>
                                    <th>Language</th>
                                    <th>Exam</th>
                                    <th>WPM</th>
                                    <th>Accuracy</th>
                                    <th>Words</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$attempts || $attempts->num_rows === 0) { ?>
                                    <tr><td colspan="7" class="text-center text-muted">No attempts recorded yet.</td></tr>
                                <?php } ?>

                                <?php while ($attempts && ($row = $attempts->fetch_assoc())) { ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                        <td>
                                            <span class="badge <?php echo ($row['access_type'] ?? 'paid') === 'guest' ? 'text-bg-warning text-dark' : 'text-bg-success'; ?>">
                                                <?php echo htmlspecialchars(ucfirst((string) ($row['access_type'] ?? 'paid'))); ?>
                                            </span>
                                        </td>
                                        <td class="text-capitalize"><?php echo htmlspecialchars($row['language']); ?></td>
                                        <td class="text-capitalize"><?php echo htmlspecialchars($row['exam_type']); ?></td>
                                        <td><?php echo htmlspecialchars((string) $row['wpm']); ?></td>
                                        <td><?php echo htmlspecialchars((string) $row['accuracy']); ?>%</td>
                                        <td><?php echo htmlspecialchars((string) $row['typed_words']); ?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("../includes/footer.php"); ?>
