<?php
require_once __DIR__ . '/includes/init.php';

$wpm = (int) getSafeGet('wpm', 0);
$accuracy = (int) getSafeGet('accuracy', 0);
$words = (int) getSafeGet('words', 0);
$remaining = (int) getSafeGet('remaining', getGuestTestsRemaining($conn));
$accessType = getSafeGet('access', isStudentLoggedIn() ? 'paid' : 'guest');
$isLoggedInStudent = isStudentLoggedIn();
$isFreeAccess = $accessType === 'guest';
$freeLimitReached = $isFreeAccess && $remaining <= 0;
?>

<?php include 'includes/header.php'; ?>

<div class="container my-5 min-vh-100">
  <div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
      <h3 class="text-center pb-3">Your Results</h3>

      <div class="card border-1 border-dark shadow-sm">
        <div class="card-body py-4 px-5">
          <div class="row text-center gy-3">
            <div class="col-12 col-md-4">
              <small class="text-uppercase text-muted">WPM</small>
              <h3 class="fw-bold mb-0"><?php echo $wpm; ?></h3>
            </div>
            <div class="col-12 col-md-4">
              <small class="text-uppercase text-muted">Accuracy</small>
              <h3 class="fw-bold mb-0"><?php echo $accuracy; ?>%</h3>
            </div>
            <div class="col-12 col-md-4">
              <small class="text-uppercase text-muted">Total Words</small>
              <h3 class="fw-bold mb-0"><?php echo $words; ?></h3>
            </div>
          </div>
        </div>
      </div>

      <?php if ($isFreeAccess) { ?>
        <div class="alert alert-warning mt-4 mb-0">
          <?php if ($freeLimitReached) { ?>
            <?php if ($isLoggedInStudent) { ?>
              <strong>Free tests complete:</strong> Your <?php echo GUEST_TEST_LIMIT; ?> free typing tests are finished.
              <div class="mt-2">Activate your plan to continue with full access for the next <?php echo PLAN_DURATION_DAYS; ?> days.</div>
              <div class="mt-3"><a href="payment.php" class="btn btn-dark btn-sm">Activate Plan</a></div>
            <?php } else { ?>
              <strong>Guest mode complete:</strong> Your <?php echo GUEST_TEST_LIMIT; ?> free typing tests are finished.
              <div class="mt-2">Create an account and activate your plan to continue.</div>
              <div class="mt-3"><a href="account/register.php" class="btn btn-dark btn-sm">Create Account</a></div>
            <?php } ?>
          <?php } else { ?>
            <?php if ($isLoggedInStudent) { ?>
              <strong>Free tests remaining:</strong> You have <span id="remainingGuestTests"><?php echo $remaining; ?></span> free tests left before plan activation is required.
            <?php } else { ?>
              <strong>Guest mode:</strong> You have <span id="remainingGuestTests"><?php echo $remaining; ?></span> free tests remaining.
            <?php } ?>
          <?php } ?>
        </div>
      <?php } ?>

      <div class="d-flex flex-column flex-sm-row justify-content-around mt-4 gap-3">
        <a href="<?php echo $freeLimitReached ? ($isLoggedInStudent ? 'payment.php' : 'account/register.php') : 'typing-preference.php'; ?>" class="btn btn-dark flex-fill"><?php echo $freeLimitReached ? ($isLoggedInStudent ? 'Activate Plan' : 'Create Account') : 'Restart'; ?></a>
        <a href="<?php echo $isLoggedInStudent ? 'account/dashboard.php' : 'index.php'; ?>" class="btn btn-outline-dark flex-fill">Exit</a>
      </div>
    </div>
  </div>
</div>

<script>
if ('<?php echo $accessType; ?>' === 'guest' && <?php echo $isLoggedInStudent ? 'false' : 'true'; ?>) {
  localStorage.setItem('guestTestsRemaining', '<?php echo $remaining; ?>');
  localStorage.setItem('guestTestAttempts', String(<?php echo GUEST_TEST_LIMIT; ?> - <?php echo $remaining; ?>));
}
</script>

<?php include 'includes/footer.php'; ?>
