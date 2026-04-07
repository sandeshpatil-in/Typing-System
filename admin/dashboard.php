<?php
require_once __DIR__ . '/../includes/init.php';

if (!isAdminLoggedIn()) {
    redirect('admin/login.php');
}

$page = getSafeGet('page', 'home');
$studentCount = 0;
$activeStudentCount = 0;
$paragraphCount = 0;

$studentCountQuery = "SELECT COUNT(*) AS total, 0 AS active_total FROM students";

if (dbColumnExists($conn, 'students', 'expiry_date')) {
    $studentCountQuery = "SELECT COUNT(*) AS total, SUM(CASE WHEN expiry_date IS NOT NULL AND expiry_date >= NOW() THEN 1 ELSE 0 END) AS active_total FROM students";
} elseif (dbTableExists($conn, 'plans')) {
    $planCondition = getPaidPlanCondition($conn, 'p');
    $studentCountQuery = "
        SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN latest_expiry IS NOT NULL AND latest_expiry >= NOW() THEN 1 ELSE 0 END) AS active_total
        FROM (
            SELECT
                s.id,
                (SELECT p.expiry_date
                 FROM plans p
                 WHERE p.student_id = s.id AND {$planCondition}
                 ORDER BY COALESCE(p.expiry_date, '0000-00-00') DESC, p.id DESC
                 LIMIT 1) AS latest_expiry
            FROM students s
        ) AS student_plan_summary
    ";
}

if ($studentResult = $conn->query($studentCountQuery)) {
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

?>

<?php include("../includes/header.php"); ?>

<div class="container-fluid px-2 px-md-3 px-xl-4 my-4 my-xl-5 min-vh-100">
    <div class="bg-body-tertiary rounded-4 p-2 p-md-3">
        <div class="row g-4 align-items-start flex-lg-nowrap">
            <div class="col-12 col-lg-auto flex-shrink-0">
                <div class="sticky-lg-top">
                    <div class="card bg-dark text-white border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <div class="mb-4">
                                <div class="small text-uppercase text-white-50 fw-semibold">Admin Panel</div>
                                <h3 class="mb-1">Dashboard</h3>
                            </div>

                            <div class="d-grid gap-2 mb-4">
                                <a href="dashboard.php?page=home" class="btn <?php echo $page === 'home' ? 'btn-primary' : 'btn-outline-light'; ?> text-start d-flex align-items-center gap-2">
                                    <i class="fas fa-table-columns"></i>
                                    <span>Dashboard</span>
                                </a>
                                <a href="dashboard.php?page=students" class="btn <?php echo $page === 'students' ? 'btn-primary' : 'btn-outline-light'; ?> text-start d-flex align-items-center gap-2">
                                    <i class="fas fa-users"></i>
                                    <span>Students</span>
                                </a>
                                <a href="dashboard.php?page=paragraphs" class="btn <?php echo in_array($page, ['paragraphs', 'add-paragraph', 'edit-paragraph', 'delete-paragraph'], true) ? 'btn-primary' : 'btn-outline-light'; ?> text-start d-flex align-items-center gap-2">
                                    <i class="fas fa-file-lines"></i>
                                    <span>Paragraphs</span>
                                </a>
                                <a href="logout.php" class="btn btn-outline-danger text-start d-flex align-items-center gap-2">
                                    <i class="fas fa-right-from-bracket"></i>
                                    <span>Logout</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-9 flex-grow-1">
                <?php if ($page === 'home') { ?>
                    <div class="row row-cols-1 row-cols-md-2 row-cols-xxl-3 g-3 mb-4">
                        <div class="col">
                            <div class="card h-100 border-0 shadow-sm rounded-4">
                                <div class="card-body">
                                    <div class="small text-uppercase text-muted fw-semibold">Students</div>
                                    <div class="display-6 fw-bold lh-1"><?php echo $studentCount; ?></div>
                                    <div class="text-muted small mt-2">Registered students in the system.</div>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="card h-100 border-0 shadow-sm rounded-4">
                                <div class="card-body">
                                    <div class="small text-uppercase text-muted fw-semibold">Active Students</div>
                                    <div class="display-6 fw-bold lh-1"><?php echo $activeStudentCount; ?></div>
                                    <div class="text-muted small mt-2">Students with active access right now.</div>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="card h-100 border-0 shadow-sm rounded-4">
                                <div class="card-body">
                                    <div class="small text-uppercase text-muted fw-semibold">Paragraphs</div>
                                    <div class="display-6 fw-bold lh-1"><?php echo $paragraphCount; ?></div>
                                    <div class="text-muted small mt-2">Available passages across typing levels.</div>
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
