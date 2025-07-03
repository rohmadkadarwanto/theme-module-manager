<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Theme & Module Manager</title>
    <link href="./assets/css/adminlte.min.css" rel="stylesheet">
    <link href="./assets/css/bootstrap-icons.min.css" rel="stylesheet">
    <link href="./assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="./index.php">
                <i class="bi bi-git"></i> Repo Manager
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="./index.php?page=dashboard">
                            <i class="bi bi-grid"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="./index.php?page=sync">
                            <i class="bi bi-arrow-repeat"></i> Sync All
                        </a>
                    </li>
                </ul>
                <div class="d-flex">
                    <span class="navbar-text">
                        v1.0.0
                    </span>
                </div>
            </div>
        </div>
    </nav>
    <div class="container">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?= $_SESSION['message']['type'] ?> alert-dismissible fade show">
                <?= $_SESSION['message']['text'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>