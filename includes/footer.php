</main>

<?php
$footerQuickLinks = [
    ['label' => 'Home', 'href' => BASE_URL . 'index.php'],
    ['label' => 'About', 'href' => BASE_URL . 'about.php'],
    ['label' => 'Practice', 'href' => BASE_URL . 'typing-preference.php'],
    ['label' => 'Contact', 'href' => BASE_URL . 'contact.php'],
];

$footerSupportLinks = [
    ['label' => 'Privacy Policy', 'href' => BASE_URL . 'privacy.php'],
    ['label' => 'Terms & Conditions', 'href' => BASE_URL . 'terms.php'],
    ['label' => 'Support', 'href' => BASE_URL . 'support.php'],
];

$footerAccountLinks = [];

if (function_exists('isStudentLoggedIn') && isStudentLoggedIn()) {
    $footerAccountLinks = [
        ['label' => 'Student Dashboard', 'href' => BASE_URL . 'account/dashboard.php'],
        ['label' => 'Logout', 'href' => BASE_URL . 'account/logout.php'],
    ];
} elseif (function_exists('isAdminLoggedIn') && isAdminLoggedIn()) {
    $footerAccountLinks = [
        ['label' => 'Admin Dashboard', 'href' => BASE_URL . 'admin/dashboard.php'],
        ['label' => 'Logout', 'href' => BASE_URL . 'admin/logout.php'],
    ];
} else {
    $footerAccountLinks = [
        ['label' => 'Student Login', 'href' => BASE_URL . 'account/login.php'],
        ['label' => 'Register', 'href' => BASE_URL . 'account/register.php'],
        ['label' => 'Admin Login', 'href' => BASE_URL . 'admin/login.php'],
    ];
}

$footerSocialLinks = [
    ['label' => 'Facebook', 'icon' => 'fab fa-facebook-f', 'href' => '#'],
    ['label' => 'Instagram', 'icon' => 'fab fa-instagram', 'href' => '#'],
    ['label' => 'YouTube', 'icon' => 'fab fa-youtube', 'href' => '#'],
];
?>

<footer class="bg-dark text-light mt-auto border-top border-secondary">
    <div class="container py-5">
        <div class="row g-4">
            <div class="col-12 col-lg-4">
                <a href="<?php echo BASE_URL; ?>index.php" class="text-decoration-none text-light fw-bold fs-4">
                    <?php echo APP_NAME ?? 'Ahilya Typing'; ?>
                </a>
                <p class="text-white-50 mt-3 mb-4">
                    Practice typing with guided levels, clear results, and a simple dashboard experience for students and admins.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($footerSocialLinks as $social) { ?>
                        <a
                            href="<?php echo htmlspecialchars($social['href']); ?>"
                            class="btn btn-outline-light btn-sm rounded-circle p-2 d-inline-flex align-items-center justify-content-center"
                            aria-label="<?php echo htmlspecialchars($social['label']); ?>"
                            <?php echo $social['href'] === '#' ? 'onclick="return false;"' : 'target="_blank" rel="noopener noreferrer"'; ?>
                        >
                            <i class="<?php echo htmlspecialchars($social['icon']); ?>"></i>
                        </a>
                    <?php } ?>
                </div>
            </div>

            <div class="col-6 col-md-4 col-lg-2">
                <h6 class="text-uppercase text-white-50 mb-3">Quick Links</h6>
                <div class="d-flex flex-column gap-2">
                    <?php foreach ($footerQuickLinks as $link) { ?>
                        <a href="<?php echo htmlspecialchars($link['href']); ?>" class="text-decoration-none text-light">
                            <?php echo htmlspecialchars($link['label']); ?>
                        </a>
                    <?php } ?>
                </div>
            </div>

            <div class="col-6 col-md-4 col-lg-3">
                <h6 class="text-uppercase text-white-50 mb-3">Support</h6>
                <div class="d-flex flex-column gap-2">
                    <?php foreach ($footerSupportLinks as $link) { ?>
                        <a href="<?php echo htmlspecialchars($link['href']); ?>" class="text-decoration-none text-light">
                            <?php echo htmlspecialchars($link['label']); ?>
                        </a>
                    <?php } ?>
                </div>
            </div>

            <div class="col-12 col-md-4 col-lg-3">
                <h6 class="text-uppercase text-white-50 mb-3">Account</h6>
                <div class="d-flex flex-column gap-2">
                    <?php foreach ($footerAccountLinks as $link) { ?>
                        <a href="<?php echo htmlspecialchars($link['href']); ?>" class="text-decoration-none text-light">
                            <?php echo htmlspecialchars($link['label']); ?>
                        </a>
                    <?php } ?>
                </div>
            </div>
        </div>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-4 pt-4 border-top border-secondary">
            <p class="mb-0 text-white-50 small">
                &copy; <span id="year"></span> <?php echo APP_NAME ?? 'Ahilya Typing'; ?>. All rights reserved.
            </p>
            <small class="text-white-50">Developed by Perfect Software</small>
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Year Script -->
<script>
document.getElementById("year").textContent = new Date().getFullYear();
</script>

</body>
</html>
