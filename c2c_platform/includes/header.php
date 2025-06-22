<?php
require_once __DIR__ . '/../config.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="C2C Marketplace Platform">
    <meta name="author" content="Your Name">

    <title><?= isset($pageTitle) ? sanitizeInput($pageTitle) . ' | C2C Platform' : 'C2C Platform' ?></title>

    <!-- Favicon -->
    <link rel="icon" href="<?= BASE_URL ?>assets/images/favicon.ico">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

    <?php if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/c2c_platform/assets/css/main.css')): ?>
        <link
            href="<?= BASE_URL ?>assets/css/main.css?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'] . '/c2c_platform/assets/css/main.css') ?>"
            rel="stylesheet">
    <?php endif; ?>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="<?= BASE_URL ?>">
                <i class="bi bi-shop me-2"></i>
                <span class="d-none d-sm-inline">C2C Platform</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav me-auto">
                    <!-- No redundant links - just logo acts as home -->
                </ul>

                <div class="d-flex align-items-center">

                    <ul class="navbar-nav ms-auto">
                        <?php if (isLoggedIn()): ?>
                            <li class="nav-item">
                                <a class="nav-link position-relative" href="<?= BASE_URL ?>cart/cart.php">
                                    <i class="bi bi-cart3"></i>
                                    <?php
                                    $cartCount = 0;
                                    if (isset($_SESSION['user_id'])) {
                                        require_once __DIR__ . '/../src/Cart.php';
                                        $cart = new Cart($db);
                                        $cartCount = $cart->getCount($_SESSION['user_id']);
                                    }
                                    if ($cartCount > 0): ?>
                                        <span
                                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                            <?= $cartCount ?>
                                        </span>
                                    <?php endif; ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>

                    <?php if (isLoggedIn()): ?>
                        <div class="dropdown">
                            <button class="btn btn-light dropdown-toggle" type="button" id="userDropdown"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle me-1"></i>
                                <?= sanitizeInput($_SESSION['username']) ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>includes/user/profile.php">
                                        <i class="bi bi-person me-2"></i>Profile
                                    </a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>includes/messaging/my_messages.php">
                                        <i class="bi bi-envelope"></i>My Messages
                                    </a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>includes/orders/order_list.php">
                                        <i class="bi bi-receipt"></i>Orders
                                    </a></li>
                                <?php if (!isSeller($db)): ?>
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>includes/auth/become_seller.php">
                                            <i class="bi bi-shop me-2"></i>Become Seller
                                        </a></li>
                                <?php endif; ?>
                                <?php if (isSeller($db)): ?>
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>includes/seller/product_listings.php">
                                            <i class="bi bi-shop me-2"></i>Product Listings
                                        </a></li>
                                <?php endif; ?>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <a class="dropdown-item text-danger" href="<?= BASE_URL ?>includes/auth/logout.php">
                                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                                    </a>
                                </li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>includes/auth/login.php" class="btn btn-outline-light me-2">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Login
                        </a>
                        <a href="<?= BASE_URL ?>includes/auth/register.php" class="btn btn-light">
                            <i class="bi bi-person-plus me-1"></i>Register
                        </a>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </nav>

    <!-- Main Content Container -->
    <main class="container my-4">