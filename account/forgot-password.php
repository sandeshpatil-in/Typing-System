<?php
require_once __DIR__ . '/../includes/init.php';

$currentStudent = isStudentLoggedIn() ? syncStudentPlanStatus($conn, getStudentId()) : null;
if ($currentStudent && hasActivePlan($currentStudent)) {
    redirect('account/dashboard.php');
}
if ($currentStudent && !hasActivePlan($currentStudent)) {
    redirect('payment.php');
}

$error = '';
$message = '';
$email = '';
$captchaScope = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = getSafePost('email', '');

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } elseif (!isValidEmail($email)) {
        $error = 'Please enter a valid email address.';
    } else {
        $student = getStudentByEmail($conn, $email);

        if ($student) {
            $token = createStudentPasswordResetToken($conn, (int) $student['id']);

            if ($token) {
                sendStudentPasswordResetEmail($student, $token);
            }
        }

        $message = 'If this email is registered, a password reset link has been sent. If you do not receive it, please contact admin.';
        $email = '';
    }
}
?>

<?php include("../includes/header.php"); ?>

<div class="container my-5 min-vh-100">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="card border-dark shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <h3 class="text-center mb-3">Forgot Password</h3>
                    <p class="text-center text-muted mb-4">Enter your student email and we will send you a reset link.</p>

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
                                placeholder="Enter your registered email"
                                value="<?php echo htmlspecialchars($email); ?>"
                                required
                                autocomplete="email"
                            >
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

<?php include("../includes/footer.php"); ?>
