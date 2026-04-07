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
$freeTestsRemaining = $hasPlan ? 0 : getStudentFreeTestsRemaining($conn, (int) $student['id']);
$studentExpiryDisplay = !empty($student['expiry_date']) ? formatDate($student['expiry_date']) : 'Not active';

$attempts = getStudentAttemptsResult($conn, (int) $student['id'], 10);
?>

<?php include("../includes/header.php"); ?>

<div class="container-fluid px-2 px-md-3 px-xl-4 my-4 my-xl-5 min-vh-100">
    <?php if (!empty($message)) echo successAlert(htmlspecialchars($message)); ?>

    <div class="bg-body-tertiary rounded-4 p-2 p-md-3">
        <div class="row g-4 align-items-start flex-lg-nowrap">
            <div class="col-12 col-lg-3 flex-shrink-0">
                <div class="sticky-lg-top">
                    <div class="card bg-dark text-white border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-body p-4">
                            <div class="mb-4">
                                <div class="small  text-white-50 fw-semibold">Wellcome</div>
                                <h3 class="mb-1"><?php echo htmlspecialchars($student['name']); ?></h3>
                            </div>


                            <div class="vstack gap-3">
                                <div>
                                    <span class="small text-uppercase text-white-50 fw-semibold d-block mb-1">Email</span>
                                    <div><?php echo htmlspecialchars($student['email']); ?></div>
                                </div>
                                <div>
                                    <span class="small text-uppercase text-white-50 fw-semibold d-block mb-1">Status</span>
                                    <span class="badge <?php echo $hasPlan ? 'text-bg-success' : 'text-bg-secondary'; ?>">
                                        <?php echo $hasPlan ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </div>
                                <div>
                                    <span class="small text-uppercase text-white-50 fw-semibold d-block mb-1">Payment</span>
                                    <div><?php echo htmlspecialchars($paymentLabel); ?></div>
                                </div>
                                <div>
                                    <span class="small text-uppercase text-white-50 fw-semibold d-block mb-1">Expiry</span>
                                    <div><?php echo htmlspecialchars($studentExpiryDisplay); ?></div>
                                </div>
                                <?php if (!$hasPlan) { ?>
                                    <div>
                                        <span class="small text-uppercase text-white-50 fw-semibold d-block mb-1">Free Tests Left</span>
                                        <div><?php echo $freeTestsRemaining; ?> / <?php echo GUEST_TEST_LIMIT; ?></div>
                                    </div>
                                <?php } ?>
                            </div>

                                                        <hr class="border-secondary my-4">


                            <div class="d-grid gap-2 mb-4">
                                
                                <a href="../typing-preference.php" class="btn btn-outline-light text-start d-flex align-items-center gap-2">
                                    <i class="fas fa-keyboard"></i>
                                    <span><?php echo $hasPlan ? 'Start Typing' : 'Free Test'; ?></span>
                                </a>
                                <a href="<?php echo $hasPlan ? '../typing-preference.php' : '../payment.php'; ?>" class="btn btn-outline-light text-start d-flex align-items-center gap-2">
                                    <i class="fas fa-credit-card"></i>
                                    <span><?php echo $hasPlan ? 'Practice Mode' : 'Activate Plan'; ?></span>
                                </a>
                                <a href="logout.php" class="btn btn-outline-danger text-start d-flex align-items-center gap-2">
                                    <i class="fas fa-right-from-bracket"></i>
                                    <span>Logout</span>
                                </a>
                            </div>

                        </div>
                    </div>

                    <?php if (!$hasPlan) { ?>
                        <div class="alert alert-warning shadow-sm mb-0">
                            <?php if ($freeTestsRemaining > 0) { ?>
                                You can still use <?php echo $freeTestsRemaining; ?> free tests before plan activation is required.
                            <?php } else { ?>
                                Your 5 free tests are finished. Activate your plan to continue.
                            <?php } ?>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <div class="col-12 col-lg">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body border-bottom p-4">
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                            <div>
                                <h3 class="mb-1">Recent Attempts</h3>
                                <p class="text-muted mb-0">Your last 10 saved typing attempts.</p>
                            </div>
                            <span class="badge text-bg-light border">Latest Records</span>
                        </div>
                    </div>

                    <div class="table-responsive shadow-sm rounded-3">
                        <table class="table table-bordered table-hover align-middle bg-white mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th class="text-nowrap small ">Date</th>
                                    <th class="text-nowrap small  ">Language</th>
                                    <th class="text-nowrap small  ">Level</th>
                                    <th class="text-nowrap small">WPM</th>
                                    <th class="text-nowrap small ">Accuracy</th>
                                    <th class="text-nowrap small ">Words</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$attempts || $attempts->num_rows === 0) { ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">No attempts recorded yet.</td>
                                    </tr>
                                <?php } ?>

                                <?php while ($attempts && ($row = $attempts->fetch_assoc())) { ?>
                                    <?php $wordCount = max(0, (int) ($row['typed_words'] ?? 0)); ?>
                                    <tr>
                                        <td class="text-nowrap"><?php echo htmlspecialchars(formatDate($row['created_at'], 'd-m-Y')); ?></td>
                                        <td class="text-nowrap text-capitalize"><?php echo htmlspecialchars($row['language']); ?></td>
                                        <td class="text-nowrap text-capitalize"><?php echo htmlspecialchars($row['exam_type']); ?></td>
                                        <td class="text-nowrap"><?php echo number_format((float) $row['wpm'], 0); ?></td>
                                        <td class="text-nowrap"><?php echo number_format((float) $row['accuracy'], 2); ?>%</td>
                                        <td class="text-nowrap"><?php echo $wordCount; ?></td>
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
