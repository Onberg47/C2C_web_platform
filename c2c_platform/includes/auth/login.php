<?php
require_once __DIR__ . '/../../config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: " . BASE_URL);
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid form submission";
    } else {
        try {
            $user = new User($db);
            $userData = $user->login($_POST['email'], $_POST['password']);

            // Set session
            $_SESSION['user_id'] = $userData['id'];
            $_SESSION['username'] = $userData['username'];

            // Redirect to home
            header("Location: " . BASE_URL);
            exit();
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

$pageTitle = "Login";
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow">
                <div class="card-body p-4">
                    <h2 class="card-title text-center mb-4">Login</h2>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <?php if (isset($_GET['registered'])): ?>
                        <div class="alert alert-success">Registration successful! Please login.</div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Login</button>
                            <a href="<?= BASE_URL ?>includes/auth/register.php" class="btn btn-outline-secondary">
                                Don't have an account? Register
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>