<?php
require_once __DIR__ . '/../includes/init.php';

if (isAdminLoggedIn()) {
    redirect('admin/dashboard.php');
}

$error = '';
$loginScope = 'admin_login';
$captchaScope = 'admin_login_captcha';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } elseif (isLoginRateLimited($loginScope, $secondsRemaining)) {
        $error = getLoginRateLimitMessage($secondsRemaining);
    } elseif (!verifyHumanVerification($captchaScope, $_POST['captcha_answer'] ?? '', $_POST['g-recaptcha-response'] ?? '')) {
        recordLoginFailure($loginScope);
        refreshHumanVerification($captchaScope);

        if (isLoginRateLimited($loginScope, $secondsRemaining)) {
            $error = getLoginRateLimitMessage($secondsRemaining);
        } else {
            $error = isRecaptchaEnabled()
                ? 'Please complete the reCAPTCHA verification.'
                : 'Please solve the captcha correctly.';
        }
    } else {
        $username = getSafePost('username', '');
        $password = $_POST['password'] ?? '';

        $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ?");

        if ($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $admin = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($admin && password_verify($password, $admin['password'])) {
                clearLoginRateLimit($loginScope);
                clearHumanVerification($captchaScope);
                session_regenerate_id(true);
                $_SESSION[ADMIN_SESSION_KEY] = (int) $admin['id'];
                redirect('admin/dashboard.php');
            }
        }

        recordLoginFailure($loginScope);
        refreshHumanVerification($captchaScope);
        if (isLoginRateLimited($loginScope, $secondsRemaining)) {
            $error = getLoginRateLimitMessage($secondsRemaining);
        } else {
            $error = 'Invalid admin credentials.';
        }
    }
}

$captcha = getCaptchaChallenge($captchaScope);
?>

<?php include("../includes/header.php"); ?>

<div class="container my-5 min-vh-100">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card border-dark shadow-sm">
                <div class="card-body p-4">
                    <h3 class="text-center mb-4">Admin Login</h3>

                    <?php if (!empty($error)) echo errorAlert(htmlspecialchars($error)); ?>
                    <?php if ($flash = getFlash('admin_auth_message')) echo successAlert(htmlspecialchars($flash)); ?>

                    <form method="POST" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">

                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" required autocomplete="username">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required autocomplete="current-password">
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?php echo isRecaptchaEnabled() ? 'Verification' : 'Captcha'; ?></label>
                            <?php if (isRecaptchaEnabled()) { ?>
                                <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars(getRecaptchaSiteKey()); ?>"></div>
                            <?php } else { ?>
                                <div class="input-group">
                                    <span class="input-group-text"><?php echo htmlspecialchars($captcha['question']); ?></span>
                                    <input type="text" name="captcha_answer" class="form-control" placeholder="Enter answer" required inputmode="numeric">
                                </div>
                            <?php } ?>
                        </div>

                        <button type="submit" class="btn btn-dark w-100">Login</button>
                    </form>

                    <div class="text-center mt-3">
                        <a href="forgot-password.php">Forgot password?</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (isRecaptchaEnabled()) { ?>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php } ?>

<?php include("../includes/footer.php"); ?>
