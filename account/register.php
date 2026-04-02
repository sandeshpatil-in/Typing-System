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
$formData = [
    'name' => '',
    'email' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['name'] = getSafePost('name', '');
    $formData['email'] = getSafePost('email', '');

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please refresh and try again.';
    } else {
        $registrationData = [
            'name' => $formData['name'],
            'email' => $formData['email'],
            'password' => $_POST['password'] ?? ''
        ];

        if ($validator->registerStudent($registrationData)) {
            $student = getStudentByEmail($conn, $registrationData['email']);

            if ($student) {
                loginStudent($student['id']);
                linkGuestAttemptsToStudent($conn, (int) $student['id']);
                setFlash('auth_message', 'Signup successful. Pay with Razorpay or ask admin to activate your hand cash payment for 30-day access.');
                redirect('payment.php');
            }

            $message = 'Signup completed. Please log in to continue.';
        } else {
            $error = $validator->getErrorMessage() ?: 'Registration failed.';
        }
    }
}
?>

<?php include("../includes/header.php"); ?>

<div class="container my-5 min-vh-100">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="card border-dark shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <h3 class="text-center mb-3">Create Your Account</h3>
                    <p class="text-center text-muted mb-4">Get 5 guest tests free, then upgrade with Razorpay or admin hand cash activation for full access.</p>

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

                        <button type="submit" class="btn btn-dark w-100 py-2">Sign Up and Continue</button>
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

<?php include("../includes/footer.php"); ?>
