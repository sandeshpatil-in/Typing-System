<!DOCTYPE html>
<html lang="en">
<head>

    <?php require_once __DIR__ . '/init.php'; ?>

    <!-- Meta -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo defined('APP_NAME') ? APP_NAME : 'Ahilya Student Desk'; ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>


<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container bg-dark">

        <!-- Logo -->
        <a class="navbar-brand fw-bold" href="<?php echo defined('BASE_URL') ? BASE_URL : 'index.php'; ?>">
            <?php echo defined('APP_NAME') ? APP_NAME : 'Student Desk'; ?>
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
                    <a class="nav-link <?php echo (function_exists('isCurrentPage') && isCurrentPage('index')) ? 'active' : ''; ?>" href="index.php">
                        Home
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?php echo (function_exists('isCurrentPage') && isCurrentPage('about')) ? 'active' : ''; ?>" href="about.php">
                        About
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?php echo (function_exists('isCurrentPage') && isCurrentPage('contact')) ? 'active' : ''; ?>" href="contact.php">
                        Contact
                    </a>
                </li>
                <li class="nav-item dropdown">

                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle"></i> Account
                    </a>

                    <ul class="dropdown-menu dropdown-menu-end">

                        <?php if (function_exists('isStudentLoggedIn') && isStudentLoggedIn()): ?>

                            <li><a class="dropdown-item" href="account/dashboard.php">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="account/logout.php">Logout</a></li>

                        <?php elseif (function_exists('isAdminLoggedIn') && isAdminLoggedIn()): ?>

                            <li><a class="dropdown-item" href="admin/dashboard.php">Admin Dashboard</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="admin/logout.php">Logout</a></li>

                        <?php else: ?>

                            <li><a class="dropdown-item" href="account/login.php">Student Login</a></li>
                            <li><a class="dropdown-item" href="account/register.php">Register</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="admin/login.php">Admin Login</a></li>

                        <?php endif; ?>

                    </ul>

                </li>
            </ul>

        </div>
    </div>
</nav>

<!-- Main Content -->
<main class="container">