<?php
require_once __DIR__ . '/../includes/init.php';

if (isAdminLoggedIn()) {
    redirect('admin/dashboard.php');
}

$error = '';
$message = '';
$identity = '';
$captchaScope = 'admin_forgot_password_captcha';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identity = getSafePost('identity', '');

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } elseif (!verifyHumanVerification($captchaScope, $_POST['captcha_answer'] ?? '', $_POST['g-recaptcha-response'] ?? '')) {
        $error = isRecaptchaEnabled()
            ? 'Please complete the reCAPTCHA verification.'
            : 'Please solve the captcha correctly.';
        refreshHumanVerification($captchaScope);
    } else {
        $admin = getAdminByIdentity($conn, $identity);

        if ($admin) {
            $token = createAdminPasswordResetToken($conn, (int) $admin['id']);

            if ($token) {
                sendAdminPasswordResetEmail($admin, $token);
            }
        }

        $message = 'If the account exists, a password reset link has been sent. If you do not receive it, contact the super admin.';
        $identity = '';
        refreshHumanVerification($captchaScope);
    }
}

$captcha = getCaptchaChallenge($captchaScope);
?>

<?php include("../includes/header.php"); ?>

<div class="container my-5 min-vh-100">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="card border-dark shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <h3 class="text-center mb-3">Admin Forgot Password</h3>
                    <p class="text-center text-muted mb-4">Enter your admin username or email to receive a reset link.</p>

                    <?php if (!empty($message)) echo successAlert(htmlspecialchars($message)); ?>
                    <?php if (!empty($error)) echo errorAlert(htmlspecialchars($error)); ?>

                    <form method="POST" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">

                        <div class="mb-3">
                            <label class="form-label">Username or Email</label>
                            <input
                                type="text"
                                name="identity"
                                class="form-control"
                                placeholder="Enter admin username or email"
                                value="<?php echo htmlspecialchars($identity); ?>"
                                required
                                autocomplete="username"
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?php echo isRecaptchaEnabled() ? 'Verification' : 'Captcha'; ?></label>
                            <?php if (isRecaptchaEnabled()) { ?>
                                <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars(getRecaptchaSiteKey()); ?>"></div>
                            <?php } else { ?>
                                <div class="input-group">
                                    <span class="input-group-text"><?php echo htmlspecialchars($captcha['question']); ?></span>
                                    <input
                                        type="text"
                                        name="captcha_answer"
                                        class="form-control"
                                        placeholder="Enter answer"
                                        required
                                        inputmode="numeric"
                                    >
                                </div>
                            <?php } ?>
                        </div>

                        <button type="submit" class="btn btn-dark w-100 py-2">Send Reset Link</button>
                    </form>

                    <div class="text-center mt-4">
                        <p class="mb-0"><a href="login.php">Back to login</a></p>
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
