<?php
require_once __DIR__ . '/../includes/init.php';

if (!isAdminLoggedIn()) {
    redirect('admin/login.php');
}

$page = getSafeGet('page', 'home');
$studentCount = 0;
$activeStudentCount = 0;
$paragraphCount = 0;
$resultCount = 0;

if ($studentResult = $conn->query("SELECT COUNT(*) AS total, SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) AS active_total FROM students")) {
    $counts = $studentResult->fetch_assoc();
    $studentCount = (int) ($counts['total'] ?? 0);
    $activeStudentCount = (int) ($counts['active_total'] ?? 0);
}

if (function_exists('dbTableExists') && dbTableExists($conn, 'passages')) {
    if ($countResult = $conn->query("SELECT COUNT(*) AS total FROM passages")) {
        $paragraphCount = (int) (($countResult->fetch_assoc())['total'] ?? 0);
    }
} elseif (function_exists('dbTableExists') && dbTableExists($conn, 'paragraphs')) {
    if ($countResult = $conn->query("SELECT COUNT(*) AS total FROM paragraphs")) {
        $paragraphCount = (int) (($countResult->fetch_assoc())['total'] ?? 0);
    }
}

if (function_exists('dbTableExists') && dbTableExists($conn, 'test_attempts')) {
    if ($countResult = $conn->query("SELECT COUNT(*) AS total FROM test_attempts")) {
        $resultCount = (int) (($countResult->fetch_assoc())['total'] ?? 0);
    }
} elseif (function_exists('dbTableExists') && dbTableExists($conn, 'results')) {
    if ($countResult = $conn->query("SELECT COUNT(*) AS total FROM results")) {
        $resultCount = (int) (($countResult->fetch_assoc())['total'] ?? 0);
    }
}
?>

<?php include("../includes/header.php"); ?>

<div class="container-fluid px-0">
    <div class="row g-0 min-vh-100">
        <div class="col-lg-2 col-md-3 bg-dark text-white p-3">
            <div class="mb-4">
                <h4 class="mb-1">Admin Panel</h4>
                <small class="text-white-50">Typing system control center</small>
            </div>

            <div class="nav flex-column gap-2">
                <a href="dashboard.php?page=home" class="btn <?php echo $page === 'home' ? 'btn-light text-dark' : 'btn-outline-light'; ?> text-start">Dashboard</a>
                <a href="dashboard.php?page=students" class="btn <?php echo $page === 'students' ? 'btn-light text-dark' : 'btn-outline-light'; ?> text-start">Students</a>
                <a href="dashboard.php?page=paragraphs" class="btn <?php echo in_array($page, ['paragraphs', 'add-paragraph', 'edit-paragraph', 'delete-paragraph'], true) ? 'btn-light text-dark' : 'btn-outline-light'; ?> text-start">Paragraphs</a>
                <a href="dashboard.php?page=results" class="btn <?php echo $page === 'results' ? 'btn-light text-dark' : 'btn-outline-light'; ?> text-start">Results</a>
                <a href="logout.php" class="btn btn-outline-danger text-start mt-3">Logout</a>
            </div>
        </div>

        <div class="col-lg-10 col-md-9 bg-light">
            <div class="p-4 p-lg-5">
                <?php if ($page === 'home') { ?>
                    <div class="mb-4">
                        <h2 class="fw-bold mb-1">Welcome to Admin Dashboard</h2>
                        <p class="text-muted mb-0">Activate students, manage exam-wise passages, and review typing activity from one place.</p>
                    </div>

                    <div class="row g-3">
                        <div class="col-xl-3 col-md-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body">
                                    <small class="text-uppercase text-muted">Students</small>
                                    <h3 class="fw-bold mb-0"><?php echo $studentCount; ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body">
                                    <small class="text-uppercase text-muted">Active Students</small>
                                    <h3 class="fw-bold mb-0"><?php echo $activeStudentCount; ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body">
                                    <small class="text-uppercase text-muted">Paragraphs</small>
                                    <h3 class="fw-bold mb-0"><?php echo $paragraphCount; ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body">
                                    <small class="text-uppercase text-muted">Attempts</small>
                                    <h3 class="fw-bold mb-0"><?php echo $resultCount; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } else { ?>
                    <?php
                    switch ($page) {
                        case 'students':
                            include("students.php");
                            break;

                        case 'activate':
                            include("activate-student.php");
                            break;

                        case 'paragraphs':
                            include("paragraphs.php");
                            break;

                        case 'add-paragraph':
                            include("add-paragraph.php");
                            break;

                        case 'edit-paragraph':
                            include("edit-paragraph.php");
                            break;

                        case 'delete-paragraph':
                            include("delete-paragraph.php");
                            break;

                        case 'results':
                            include("results.php");
                            break;

                        default:
                            include("paragraphs.php");
                            break;
                    }
                    ?>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<?php include("../includes/footer.php"); ?>
