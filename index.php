<?php
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Online Typing Practice | ' . APP_NAME;
$metaDescription = 'Practice structured typing tests, track speed and accuracy, and manage student/admin dashboards in one clean interface.';
$structuredData = [
    [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => APP_NAME,
        'url' => BASE_URL,
        'logo' => BASE_URL . 'assets/images/favicon.png',
        'sameAs' => []
    ],
    [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => APP_NAME,
        'url' => BASE_URL,
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => BASE_URL . '?q={search_term_string}',
            'query-input' => 'required name=search_term_string'
        ]
    ]
];

include 'includes/header.php';

$showGuestSignupCta = !isStudentLoggedIn() && !isAdminLoggedIn();
?>

<section class="py-5 py-lg-6">
  <div class="container-fluid px-3 px-md-4 px-xl-5">
    <div class="row align-items-center g-4 g-lg-5">
      <div class="col-lg-6 order-2 order-lg-1">
        <div class="mx-auto" style="max-width: 620px;">
          <span class="badge text-bg-light border border-dark text-dark mb-3">Typing Practice Platform</span>

          <h1 class="fw-bold mb-3">
            Master Your Typing Skills for Government Exams
          </h1>

          <p class="mb-4 text-muted fs-5">
            Practice with structured typing tests, improve speed and accuracy, and get instant performance results in a clean exam-style flow.
          </p>

          <div class="d-flex flex-column flex-sm-row gap-3">
            <a href="typing-preference.php" class="btn btn-dark px-4 py-2">
              Start Typing Test
            </a>

            <?php if ($showGuestSignupCta) { ?>
              <a href="account/register.php" class="btn btn-outline-dark px-4 py-2">
                Create Account
              </a>
            <?php } ?>
          </div>
        </div>
      </div>

      <div class="col-lg-6 order-1 order-lg-2 text-center">
        <img src="assets/images/hero.png" class="img-fluid" alt="Typing Practice" style="max-height: 460px;">
      </div>
    </div>
  </div>
</section>

<section class="py-5">
  <div class="container-fluid px-3 px-md-4 px-xl-5">
    <h2 class="text-center fw-bold mb-4">Why Choose Our Platform?</h2>

    <div class="row g-3">
      <div class="col-md-4">
        <div class="border rounded-3 p-4 h-100 bg-white shadow-sm">
          <h5 class="fw-bold">Fast Typing Tests</h5>
          <p class="mb-0 text-muted">Real-time typing tests with instant results.</p>
        </div>
      </div>

      <div class="col-md-4">
        <div class="border rounded-3 p-4 h-100 bg-white shadow-sm">
          <h5 class="fw-bold">Performance Reports</h5>
          <p class="mb-0 text-muted">Track speed and accuracy after every test.</p>
        </div>
      </div>

      <div class="col-md-4">
        <div class="border rounded-3 p-4 h-100 bg-white shadow-sm">
          <h5 class="fw-bold">Multi Language</h5>
          <p class="mb-0 text-muted">Practice English, Marathi, and Hindi typing easily.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="bg-dark text-white my-5 py-5 rounded-3">
  <div class="container-fluid px-3 px-md-4 px-xl-5">
    <div class="row align-items-center g-3">
      <div class="col-md-8">
        <h4 class="fw-bold mb-2">Ready to Improve Your Typing?</h4>
        <p class="mb-0 text-white-50">Start your practice now and boost your exam preparation.</p>
      </div>

      <div class="col-md-4 text-md-end">
        <div class="d-flex flex-column flex-sm-row justify-content-md-end gap-2">
          <a href="typing-preference.php" class="btn btn-light">
            Start Test
          </a>

          <?php if ($showGuestSignupCta) { ?>
            <a href="account/register.php" class="btn btn-outline-light">
              Register
            </a>
          <?php } ?>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
