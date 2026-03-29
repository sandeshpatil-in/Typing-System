<?php
http_response_code(500);
require_once __DIR__ . '/../includes/init.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Error</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container min-vh-100 d-flex align-items-center justify-content-center">
        <div class="text-center">
            <h1 class="display-4 fw-bold">500</h1>
            <p class="lead">Something went wrong on the server.</p>
            <a href="<?php echo BASE_URL; ?>index.php" class="btn btn-dark">Back to Home</a>
        </div>
    </div>
</body>
</html>