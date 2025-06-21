<?php
$pageTitle = "Test Page";
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-body text-center">
                <h2>Test Page</h2>
                <p class="lead">This page tests all functionality</p>
                
                <div class="alert alert-info mt-4">
                    <h5>Debug Information</h5>
                    <p>Logged In: <?= isLoggedIn() ? 'Yes' : 'No' ?></p>
                    <?php if (isLoggedIn()): ?>
                    <p>Username: <?= htmlspecialchars($_SESSION['username']) ?></p>
                    <p>Seller Status: <?= isSeller($db) ? 'Yes' : 'No' ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>