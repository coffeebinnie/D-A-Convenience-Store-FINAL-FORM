<?php
$pageTitle = $pageTitle ?? "Admin";
$activePage = $activePage ?? "";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= e($pageTitle) ?> | D & A Admin</title>
    <link rel="stylesheet" href="../assets/styles/admin.css?v=3" />
</head>
<body class="admin-body">

<header class="admin-header">
    <div class="admin-header-inner">
        <a href="dashboard.php" class="admin-brand">
            <img src="../assets/images/logo1.jpg" alt="D & A Logo">
            <span>
                <strong>D &amp; A</strong>
                <small>Admin Panel</small>
            </span>
        </a>

        <nav class="admin-nav">
            <a href="dashboard.php"
               class="<?= $activePage === "dashboard" ? "active" : "" ?>">
                Dashboard
            </a>
            <a href="orders.php"
               class="<?= $activePage === "orders" ? "active" : "" ?>">
                Orders
            </a>
            <a href="products.php"
               class="<?= $activePage === "products" ? "active" : "" ?>">
                Products
            </a>
            <span class="admin-user">
                <?= e(ucfirst($_SESSION["admin_username"] ?? "Admin")) ?>
            </span>
            <a href="logout.php" class="admin-logout">Logout</a>
        </nav>
    </div>
</header>

<main class="admin-main">
