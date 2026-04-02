<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/UserValidator.php';

$currentStudent = isStudentLoggedIn() ? syncStudentPlanStatus($conn, getStudentId()) : null;
if ($currentStudent && hasActivePlan($currentStudent)) {
    redirect('account/dashboard.php');
}
if ($currentStudent && !hasActivePlan($currentStudent)) {
    redirect('payment.php');
}

$validator = new UserValidator($conn);
$error = '';
$message = getFlash('auth_message');
$oldEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } else {
        $oldEmail = getSafePost('email', '');
        $password = $_POST['password'] ?? '';

        $user = $validator->validateLogin($oldEmail, $password, 'student');

        if ($user && $validator->validateStudentStatus($user)) {
            loginStudent($user['id']);
            linkGuestAttemptsToStudent($conn, (int) $user['id']);
            $user = syncStudentPlanStatus($conn, $user['id']);

            if (hasActivePlan($user)) {
                setFlash('auth_message', 'Welcome back. Your plan is active.');
                redirect('account/dashboard.php');
            }

            setFlash('auth_message', 'Login successful. Complete Razorpay payment or ask admin to confirm your hand cash payment.');
            redirect('payment.php');
        }

        $error = $validator->getErrorMessage() ?: 'Unable to sign in.';
    }
}
?>

<?php include("../includes/header.php"); ?>

<div class="container my-5 min-vh-100">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="card border-dark shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <h3 class="text-center mb-3">Login</h3>
                    <p class="text-center text-muted mb-4">Sign in to continue your typing journey and manage your plan.</p>

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

                        <button type="submit" class="btn btn-dark w-100 py-2">Login Securely</button>
                    </form>

                    <div class="text-center mt-4">
                        <p class="mb-0">New here? <a href="register.php">Create your account</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("../includes/footer.php"); ?>
