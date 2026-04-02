<?php
require_once __DIR__ . '/includes/init.php';

$wpm = (int) getSafeGet('wpm', 0);
$accuracy = (int) getSafeGet('accuracy', 0);
$words = (int) getSafeGet('words', 0);
$remaining = (int) getSafeGet('remaining', getGuestTestsRemaining($conn));
$accessType = getSafeGet('access', isStudentLoggedIn() ? 'paid' : 'guest');
$guestLimitReached = $accessType === 'guest' && $remaining <= 0;
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

      <?php if ($accessType === 'guest') { ?>
        <div class="alert alert-warning mt-4 mb-0">
          <?php if ($guestLimitReached) { ?>
            <strong>Guest mode complete:</strong> Your <?php echo GUEST_TEST_LIMIT; ?> free typing tests are finished.
            <div class="mt-2">Create an account, complete payment, and unlock full access for the next <?php echo PLAN_DURATION_DAYS; ?> days.</div>
            <div class="mt-3"><a href="account/register.php" class="btn btn-dark btn-sm">Create Account to Continue</a></div>
          <?php } else { ?>
            <strong>Guest mode:</strong> You have <span id="remainingGuestTests"><?php echo $remaining; ?></span> free tests remaining.
          <?php } ?>
        </div>
      <?php } ?>

      <div class="d-flex flex-column flex-sm-row justify-content-around mt-4 gap-3">
        <a href="<?php echo $guestLimitReached ? 'account/register.php' : 'typing-preference.php'; ?>" class="btn btn-dark flex-fill"><?php echo $guestLimitReached ? 'Create Account' : 'Restart'; ?></a>
        <a href="<?php echo $accessType === 'paid' ? 'account/dashboard.php' : 'index.php'; ?>" class="btn btn-outline-dark flex-fill">Exit</a>
      </div>
    </div>
  </div>
</div>

<script>
if ('<?php echo $accessType; ?>' === 'guest') {
  localStorage.setItem('guestTestsRemaining', '<?php echo $remaining; ?>');
  localStorage.setItem('guestTestAttempts', String(<?php echo GUEST_TEST_LIMIT; ?> - <?php echo $remaining; ?>));
}
</script>

<?php include 'includes/footer.php'; ?>
