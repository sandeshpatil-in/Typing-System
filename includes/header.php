<!DOCTYPE html>
<html lang="en">
<head>

    <?php require_once __DIR__ . '/init.php'; ?>

    <?php
        $appName = defined('APP_NAME') ? APP_NAME : 'Ahilya Typing';
        $metaDescriptionContent = trim((string) ($metaDescription ?? 'Practice typing with guided levels, reports, and a clean dashboard for students and admins.'));
        $metaRobots = trim((string) ($metaRobots ?? 'index,follow'));
        $requestPath = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $canonicalUrl = trim((string) ($canonicalUrl ?? (BASE_URL . ltrim($requestPath, '/'))));
        $pageTitle = trim((string) ($pageTitle ?? $appName));
        $structuredData = $structuredData ?? null;
    ?>

    <!-- Meta -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($metaDescriptionContent); ?>">
    <meta name="robots" content="<?php echo htmlspecialchars($metaRobots); ?>">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonicalUrl); ?>">
    <meta name="theme-color" content="#0d6efd">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon.png">

    <?php if (!empty($structuredData)) { ?>
        <script type="application/ld+json">
            <?php echo json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT); ?>
        </script>
    <?php } ?>
</head>

<body>


<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container bg-dark">

        <!-- Logo -->
        <a class="navbar-brand fw-bold" href="<?php echo defined('BASE_URL') ? BASE_URL : 'index.php'; ?>">
            <?php echo defined('APP_NAME') ? APP_NAME : 'Ahilya Typing'; ?>
        </a>

        <!-- Mobile Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Menu -->
        <div class="collapse navbar-collapse" id="navbarMain">


            <!-- Right (Account) -->
            <ul class="navbar-nav ms-auto">
                  <li class="nav-item">
                    <a class="nav-link <?php echo (function_exists('isCurrentPage') && isCurrentPage('index')) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>index.php">
                        Home
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?php echo (function_exists('isCurrentPage') && isCurrentPage('about')) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>about.php">
                        About
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?php echo (function_exists('isCurrentPage') && isCurrentPage('contact')) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>contact.php">
                        Contact
                    </a>
                </li>
                <li class="nav-item dropdown">

                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle"></i> Account
                    </a>

                    <ul class="dropdown-menu dropdown-menu-end">

                        <?php if (function_exists('isStudentLoggedIn') && isStudentLoggedIn()): ?>

                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>account/dashboard.php">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>account/logout.php">Logout</a></li>

                        <?php elseif (function_exists('isAdminLoggedIn') && isAdminLoggedIn()): ?>

                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>admin/dashboard.php">Admin Dashboard</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>admin/logout.php">Logout</a></li>

                        <?php else: ?>

                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>account/login.php">Student Login</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>account/register.php">Register</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>admin/login.php">Admin Login</a></li>

                        <?php endif; ?>

                    </ul>

                </li>
            </ul>

        </div>
    </div>
</nav>

<!-- Main Content -->
<?php
$currentScript = str_replace('\\', '/', (string) ($_SERVER['PHP_SELF'] ?? ''));
$isAdminArea = str_contains($currentScript, '/admin/');
$isStudentDashboard = str_contains($currentScript, '/account/dashboard.php');
$mainClass = ($isAdminArea || $isStudentDashboard) ? 'container-fluid px-0' : 'container';
?>
<main class="<?php echo $mainClass; ?>">
