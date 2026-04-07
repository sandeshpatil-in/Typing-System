<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/UserValidator.php';

$error = '';
$token = trim((string) ($_POST['token'] ?? getSafeGet('token', '')));
$captchaScope = null;
$record = $token !== '' ? getStudentPasswordResetRecord($conn, $token) : null;
$isValidToken = isStudentPasswordResetRecordActive($record);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } elseif (!$isValidToken) {
        $error = 'This reset link is invalid or expired.';
    } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
        $error = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.';
    } elseif (!UserValidator::isPasswordStrong($password)) {
        $error = 'Password must include uppercase, lowercase, and a number.';
    } elseif (!hash_equals($password, $confirmPassword)) {
        $error = 'Passwords do not match.';
    } elseif (!resetStudentPasswordWithToken($conn, $token, $password)) {
        $error = 'This reset link is invalid or expired.';
    } else {
        clearLoginRateLimit('student_login');
        setFlash('auth_message', 'Password reset successful. Please log in with your new password.');
        redirect('account/login.php');
    }
}

?>

<?php include("../includes/header.php"); ?>

<div class="container my-5 min-vh-100">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="card border-dark shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <h3 class="text-center mb-3">Reset Password</h3>
                    <p class="text-center text-muted mb-4">Create a new strong password for your student account.</p>

                    <?php if (!$isValidToken) { ?>
                        <?php echo errorAlert('This reset link is invalid or expired. Please request a new password reset link.'); ?>
                        <div class="text-center mt-3">
                            <a href="forgot-password.php" class="btn btn-dark">Request New Link</a>
                        </div>
                    <?php } else { ?>
                        <?php if (!empty($error)) echo errorAlert(htmlspecialchars($error)); ?>

                        <form method="POST" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input
                                    type="password"
                                    name="password"
                                    class="form-control"
                                    placeholder="Enter new password"
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
                                    placeholder="Confirm new password"
                                    required
                                    autocomplete="new-password"
                                >
                            </div>

                            <button type="submit" class="btn btn-dark w-100 py-2">Update Password</button>
                        </form>

                        <p class="text-center text-muted mt-3 mb-0">Password must include uppercase, lowercase, and a number.</p>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("../includes/footer.php"); ?>
