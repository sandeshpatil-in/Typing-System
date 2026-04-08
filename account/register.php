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
$hasStudentContactNumber = dbColumnExists($conn, 'students', 'contact_number');
$captchaScope = 'student_register_captcha';
$formData = [
    'name' => '',
    'email' => '',
    'contact_number' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['name'] = getSafePost('name', '');
    $formData['email'] = getSafePost('email', '');
    $formData['contact_number'] = getSafePost('contact_number', '');

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please refresh and try again.';
    } elseif (!verifyHumanVerification($captchaScope, $_POST['captcha_answer'] ?? '', $_POST['g-recaptcha-response'] ?? '')) {
        $error = isRecaptchaEnabled()
            ? 'Please complete the reCAPTCHA verification.'
            : 'Please solve the captcha correctly.';
        refreshHumanVerification($captchaScope);
    } else {
        $registrationData = [
            'name' => $formData['name'],
            'email' => $formData['email'],
            'contact_number' => $formData['contact_number'],
            'password' => $_POST['password'] ?? '',
            'confirm_password' => $_POST['confirm_password'] ?? ''
        ];

        if ($validator->registerStudent($registrationData)) {
            $student = getStudentByEmail($conn, $registrationData['email']);

            if ($student) {
                clearHumanVerification($captchaScope);
                loginStudent($student['id']);
                linkGuestAttemptsToStudent($conn, (int) $student['id']);
                $freeTestsRemaining = getStudentFreeTestsRemaining($conn, (int) $student['id']);

                if ($freeTestsRemaining > 0) {
                    setFlash(
                        'auth_message',
                        'Signup successful. You have ' . $freeTestsRemaining . ' free tests remaining before plan activation is required.'
                    );
                    redirect('typing-preference.php');
                }

                setFlash('auth_message', 'Signup successful. Your 5 free tests are finished. Activate your plan to continue.');
                redirect('payment.php');
            }

            $message = 'Signup completed. Please log in to continue.';
        } else {
            $error = $validator->getErrorMessage() ?: 'Registration failed.';
            refreshHumanVerification($captchaScope);
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
                    <h3 class="text-center mb-3">Create Your Account</h3>
                    <p class="text-center text-muted mb-4">Start with 5 free typing tests. Activate a plan only after your free attempts are finished.</p>

                    <?php if (!empty($message)) echo successAlert(htmlspecialchars($message)); ?>
                    <?php if (!empty($error)) echo errorAlert(htmlspecialchars($error)); ?>

                    <form method="POST" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">

                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input
                                type="text"
                                name="name"
                                class="form-control"
                                placeholder="Enter your name"
                                value="<?php echo htmlspecialchars($formData['name']); ?>"
                                required
                                autocomplete="name"
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input
                                type="email"
                                name="email"
                                class="form-control"
                                placeholder="Enter your email"
                                value="<?php echo htmlspecialchars($formData['email']); ?>"
                                required
                                autocomplete="email"
                            >
                        </div>

                        <?php if ($hasStudentContactNumber) { ?>
                            <div class="mb-3">
                                <label class="form-label">Contact Number</label>
                                <input
                                    type="tel"
                                    name="contact_number"
                                    class="form-control"
                                    placeholder="Enter your mobile number"
                                    value="<?php echo htmlspecialchars($formData['contact_number']); ?>"
                                    required
                                    autocomplete="tel"
                                    inputmode="numeric"
                                >
                            </div>
                        <?php } ?>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input
                                type="password"
                                name="password"
                                class="form-control"
                                placeholder="Choose a strong password"
                                required
                                autocomplete="new-password"
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Confirm Password</label>
                            <input
                                type="password"
                                name="confirm_password"
                                class="form-control"
                                placeholder="Confirm your password"
                                required
                                autocomplete="new-password"
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

                        <button type="submit" class="btn btn-dark w-100 py-2">Create Account</button>
                    </form>

                    <p class="text-center text-muted mt-3 mb-0">Password must include uppercase, lowercase, and a number.</p>
                    <div class="text-center mt-4">
                        <p class="mb-0">Already have an account? <a href="login.php">Login here</a></p>
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
