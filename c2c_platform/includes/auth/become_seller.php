<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/User.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "includes/auth/login.php?redirect=become_seller");
    exit();
}

// Redirect if already a seller
if (isSeller($db)) {
    header("Location: " . BASE_URL . "includes/seller/dashboard.php");
    exit();
}

// Get user's delivery address if exists
$userAddress = [];
try {
    $user = new User($db);
    $userAddress = $user->getUserAddress($_SESSION['user_id']);
} catch (Exception $e) {
    // Silently fail - user might not have an address
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid form submission";
    } else {
        try {
            // Process address - use user address if checkbox was checked
            $sellerData = [];
            if (isset($_POST['use_user_address']) && $_POST['use_user_address'] === '1') {
                if (empty($userAddress)) {
                    throw new Exception("No delivery address found for your account");
                }
                $sellerData = $userAddress;
            } else {
                $sellerData = [
                    'street' => sanitizeInput($_POST['street']),
                    'city' => sanitizeInput($_POST['city']),
                    'province' => sanitizeInput($_POST['province']),
                    'zip' => sanitizeInput($_POST['zip'])
                ];
            }

            // Process pickup points
            $pickupPoints = [];
            if (isset($_POST['pickup_street'])) {
                foreach ($_POST['pickup_street'] as $index => $street) {
                    if (!empty(trim($street))) {
                        $pickupPoints[] = [
                            'street' => sanitizeInput($street),
                            'city' => sanitizeInput($_POST['pickup_city'][$index]),
                            'province' => sanitizeInput($_POST['pickup_province'][$index]),
                            'zip' => sanitizeInput($_POST['pickup_zip'][$index]),
                            'hours' => sanitizeInput($_POST['pickup_hours'][$index])
                        ];
                    }
                }
            }

            // Register seller
            if ($user->becomeSeller($_SESSION['user_id'], $sellerData, $pickupPoints)) {
                $_SESSION['is_seller'] = true;
                header("Location: " . BASE_URL . "includes/seller/product_listings.php?new_seller=1");
                exit();
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

$pageTitle = "Become a Seller";
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Complete Your Seller Profile</h3>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form id="sellerForm" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                        <!-- Auto-address checkbox -->
                        <?php if (!empty($userAddress)): ?>
                            <div class="mb-3 form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="useUserAddress" name="use_user_address"
                                    value="1">
                                <label class="form-check-label" for="useUserAddress">
                                    Use my delivery address as business address
                                </label>
                            </div>
                        <?php endif; ?>

                        <!-- Business Address Section -->
                        <div class="mb-4" id="businessAddressSection">
                            <h4 class="border-bottom pb-2">Business Address</h4>
                            <div class="row g-3 mt-3">
                                <div class="col-md-12">
                                    <label class="form-label">Street Address</label>
                                    <input type="text" class="form-control" name="street"
                                        value="<?= !empty($userAddress) ? htmlspecialchars($userAddress['street']) : '' ?>"
                                        required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">City</label>
                                    <input type="text" class="form-control" name="city"
                                        value="<?= !empty($userAddress) ? htmlspecialchars($userAddress['city']) : '' ?>"
                                        required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Province</label>
                                    <input type="text" class="form-control" name="province"
                                        value="<?= !empty($userAddress) ? htmlspecialchars($userAddress['province']) : '' ?>"
                                        required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">ZIP Code</label>
                                    <input type="text" class="form-control" name="zip"
                                        value="<?= !empty($userAddress) ? htmlspecialchars($userAddress['zip']) : '' ?>"
                                        required>
                                </div>
                            </div>
                        </div>

                        <!-- Pickup Points Section -->
                        <div class="mb-4">
                            <h4 class="border-bottom pb-2">Pickup Locations</h4>
                            <p class="text-muted">Add locations where customers can pick up items (optional)</p>

                            <div id="pickupPointsContainer">
                                <div class="pickup-point card mb-3">
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-md-12">
                                                <label class="form-label">Street Address</label>
                                                <input type="text" class="form-control" name="pickup_street[]">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">City</label>
                                                <input type="text" class="form-control" name="pickup_city[]">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Province</label>
                                                <input type="text" class="form-control" name="pickup_province[]">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">ZIP Code</label>
                                                <input type="text" class="form-control" name="pickup_zip[]">
                                            </div>
                                            <div class="col-md-12">
                                                <label class="form-label">Operating Hours</label>
                                                <input type="text" class="form-control" name="pickup_hours[]"
                                                    placeholder="e.g. Mon-Fri 9AM-5PM">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button type="button" class="btn btn-outline-primary btn-sm" id="addPickupPoint">
                                <i class="bi bi-plus-circle"></i> Add Another Location
                            </button>
                        </div>

                        <!-- Terms Agreement -->
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="agreeTerms" name="agreeTerms" required>
                            <label class="form-check-label" for="agreeTerms">
                                I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Seller
                                    Terms and Conditions</a>
                            </label>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                Complete Seller Registration
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Terms Modal -->
<div class="modal fade" id="termsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Seller Terms and Conditions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Here goes your seller terms and conditions content...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Auto-address toggle
        const useUserAddress = document.getElementById('useUserAddress');
        const addressSection = document.getElementById('businessAddressSection');

        if (useUserAddress && addressSection) {
            const addressInputs = addressSection.querySelectorAll('input[type="text"]');

            useUserAddress.addEventListener('change', function () {
                if (this.checked) {
                    addressInputs.forEach(input => {
                        input.disabled = true;
                        input.classList.add('bg-light', 'text-muted');
                    });
                } else {
                    addressInputs.forEach(input => {
                        input.disabled = false;
                        input.classList.remove('bg-light', 'text-muted');
                    });
                }
            });
        }

        // Pickup point management
        const addPickupBtn = document.getElementById('addPickupPoint');
        if (addPickupBtn) {
            addPickupBtn.addEventListener('click', function () {
                const container = document.getElementById('pickupPointsContainer');
                const template = container.querySelector('.pickup-point').cloneNode(true);

                // Clear inputs
                template.querySelectorAll('input').forEach(input => input.value = '');

                // Add remove button
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'btn btn-outline-danger btn-sm mt-2';
                removeBtn.innerHTML = '<i class="bi bi-trash"></i> Remove';
                removeBtn.addEventListener('click', function () {
                    if (container.querySelectorAll('.pickup-point').length > 1) {
                        template.remove();
                    } else {
                        alert('You need at least one pickup location (or remove all to skip)');
                    }
                });

                template.querySelector('.card-body').appendChild(removeBtn);
                container.appendChild(template);
            });
        }

        // Form validation
        const form = document.getElementById('sellerForm');
        if (form) {
            form.addEventListener('submit', function (e) {
                if (!document.getElementById('agreeTerms').checked) {
                    e.preventDefault();
                    alert('You must agree to the terms and conditions');
                }
            });
        }
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>