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
                    <p class="mb-4"><strong>Expiry:</strong> <?php echo htmlspecialchars((string) ($student['expiry_date'] ?? 'Not active')); ?></p>

                    <?php if ($hasPlan) { ?>
                        <a href="../typing-preference.php" class="btn btn-dark w-100 mb-2">Start Typing</a>
                    <?php } else { ?>
                        <a href="../payment.php" class="btn btn-dark w-100 mb-2">Activate Plan</a>
                    <?php } ?>

                    <a href="../typing-preference.php" class="btn btn-outline-dark w-100">View Test Modes</a>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-dark shadow-sm mb-4">
                <div class="card-body">
                    <h4 class="mb-3">Plan Summary</h4>
                    <?php if ($plan) { ?>
                        <div class="row">
                            <div class="col-md-4"><strong>Plan</strong><div><?php echo htmlspecialchars($plan['plan_name']); ?></div></div>
                            <div class="col-md-4"><strong>Started</strong><div><?php echo htmlspecialchars($plan['start_date']); ?></div></div>
                            <div class="col-md-4"><strong>Expires</strong><div><?php echo htmlspecialchars($plan['expiry_date']); ?></div></div>
                        </div>
                    <?php } else { ?>
                        <p class="mb-0 text-muted">No paid plan found yet. Complete your Razorpay payment to unlock unlimited tests.</p>
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
                                    <th>Language</th>
                                    <th>Exam</th>
                                    <th>WPM</th>
                                    <th>Accuracy</th>
                                    <th>Words</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$attempts || $attempts->num_rows === 0) { ?>
                                    <tr><td colspan="6" class="text-center text-muted">No attempts recorded yet.</td></tr>
                                <?php } ?>

                                <?php while ($attempts && ($row = $attempts->fetch_assoc())) { ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['created_at']); ?></td>
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
