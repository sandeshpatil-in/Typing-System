<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/UserValidator.php';

$currentStudent = isStudentLoggedIn() ? syncStudentPlanStatus($conn, getStudentId()) : null;
if ($currentStudent && hasActivePlan($currentStudent)) {
    redirect('account/dashboard.php');
}
if ($currentStudent && !hasActivePlan($currentStudent)) {
    redirect('typing-preference.php');
}

$validator = new UserValidator($conn);
$error = '';
$message = getFlash('auth_message');
$oldEmail = '';
$loginScope = 'student_login';
$captchaScope = 'student_login_captcha';

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
        $oldEmail = getSafePost('email', '');
        $password = $_POST['password'] ?? '';

        $user = $validator->validateLogin($oldEmail, $password, 'student');

        if ($user && $validator->validateStudentStatus($user)) {
            clearLoginRateLimit($loginScope);
            clearHumanVerification($captchaScope);
            loginStudent($user['id']);
            linkGuestAttemptsToStudent($conn, (int) $user['id']);
            $user = syncStudentPlanStatus($conn, $user['id']);

            if (hasActivePlan($user)) {
                setFlash('auth_message', 'Welcome back. Your plan is active.');
                redirect('account/dashboard.php');
            }

            $freeTestsRemaining = getStudentFreeTestsRemaining($conn, (int) $user['id']);

            if ($freeTestsRemaining > 0) {
                setFlash(
                    'auth_message',
                    'Login successful. You have ' . $freeTestsRemaining . ' free tests remaining before plan activation is required.'
                );
                redirect('typing-preference.php');
            }

            setFlash('auth_message', 'Login successful. Your 5 free tests are finished. Activate your plan to continue.');
            redirect('payment.php');
        }

        recordLoginFailure($loginScope);
        refreshHumanVerification($captchaScope);
        if (isLoginRateLimited($loginScope, $secondsRemaining)) {
            $error = getLoginRateLimitMessage($secondsRemaining);
        } else {
            $error = $validator->getErrorMessage() ?: 'Unable to sign in.';
        }
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
                    <h3 class="text-center mb-3">Login</h3>
                    <p class="text-center text-muted mb-4">Sign in to continue your typing practice. New students can use 5 free tests before plan activation is required.</p>

                    <?php if (!empty($message)) echo successAlert(htmlspecialchars($message)); ?>
                    <?php if (!empty($error)) echo errorAlert(htmlspecialchars($error)); ?>

                    <form method="POST" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input
                                type="email"
                                name="email"
                                class="form-control"
                                placeholder="Enter your email"
                                value="<?php echo htmlspecialchars($oldEmail); ?>"
                                required
                                autocomplete="email"
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input
                                type="password"
                                name="password"
                                class="form-control"
                                placeholder="Enter your password"
                                required
                                autocomplete="current-password"
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

                        <button type="submit" class="btn btn-dark w-100 py-2">Login Securely</button>
                    </form>

                    <div class="text-center mt-4">
                        <p class="mb-2"><a href="forgot-password.php">Forgot password?</a></p>
                        <p class="mb-0">New here? <a href="register.php">Create your account</a></p>
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
