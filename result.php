<?php include 'includes/header.php'; ?>

<?php
$wpm = isset($_GET['wpm']) ? intval($_GET['wpm']) : 0;
$accuracy = isset($_GET['accuracy']) ? intval($_GET['accuracy']) : 0;
$words = isset($_GET['words']) ? intval($_GET['words']) : 0;
?>

<div class="container my-5 min-vh-100">
  <div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">

      <h3 class="text-center pb-3">Your Results</h3>

      <div class="card  border-1 border-dark">
        <div class="card-body py-4 px-5">
          <div class="row text-center gy-3">
            <div class="col-12 col-md-4">
              <div class="result-stat">
                <small class="text-uppercase text-muted">WPM</small>
                <h3 class="fw-bold mb-0"><?php echo $wpm; ?></h3>
              </div>
            </div>
            <div class="col-12 col-md-4">
              <div class="result-stat">
                <small class="text-uppercase text-muted">Accuracy</small>
                <h3 class=" fw-bold mb-0"><?php echo $accuracy; ?>%</h3>
              </div>
            </div>
            <div class="col-12 col-md-4">
              <div class="result-stat">
                <small class="text-uppercase text-muted">Total Words</small>
                <h3 class="fw-bold mb-0"><?php echo $words; ?></h3>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="d-flex flex-column flex-sm-row justify-content-around mt-4">
        <a href="typing-preference.php" class="btn btn-dark w-25 w-sm-auto">
          Restart
        </a>
        <a href="index.php" class="btn btn-outline-dark w-25 w-sm-auto">
          Exit
        </a>
      </div>

    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>