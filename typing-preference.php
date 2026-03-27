<?php include 'includes/header.php'; ?>
<link rel="stylesheet" href="assets/css/style.css">

<section class="container my-5 min-vh-100">
  <div class="card border-1 border-dark">
    <div class="card-body p-5">
      <h3 class="text-center pb-3">
        Select Typing Preferences
      </h3>

      <form action="typing-test.php" method="GET">

        <div class="row g-3">

          <!-- Language -->
          <div class="col-md-3">
            <label class="form-label">Language</label>
            <select name="language" class="form-control border-dark border-1">
              <option class="" value="marathi">Marathi</option>
              <option class="" value="english">English</option>
              <option class="" value="hindi">Hindi</option>
            </select>
          </div>

          <!-- Exam Type -->
          <div class="col-md-3">
            <label class="form-label">Exam Type</label>
            <select name="exam_type" class="form-control border-dark border-1">
              <option value="skills">Skills Test</option>
              <option value="gcc-tbc">GCC-TBC</option>
              <option value="high-court">High Court</option>
              <option value="e30">E30 WPM</option>
              <option value="m30">M30 WPM</option>
            </select>
          </div>

          <!-- Passage -->
          <div class="col-md-3">
            <label class="form-label">Select Passage</label>
            <select name="paragraph" class="form-control border-dark border-1">
              <option value="1">Passage 1</option>
              <option value="2">Passage 2</option>
              <option value="3">Passage 3</option>
            </select>
          </div>

          <!-- Time -->
          <div class="col-md-3">
            <label class="form-label">Set Time</label>
            <select name="time" class="form-control border-dark border-1">
              <option value="60">1 Minute</option>
              <option value="120">2 Minutes</option>
              <option value="300">5 Minutes</option>
              <option value="420">7 Minutes</option>
              <option value="600">10 Minutes</option>
            </select>
          </div>

        </div>

        <div class="text-center mt-4">
          <button type="submit" class="btn btn-dark px-5 py-2">
            Start Test
          </button>
        </div>

      </form>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>