<?php
require_once '../../../assets/database/auth.php';
require_once '../../../assets/database/config.php';

checkSessionTimeout();

$isLoggedIn = isLoggedIn();
$user = $isLoggedIn ? getUser() : null;
$userRole = $user['role'] ?? null;

if (!$isLoggedIn) {
    header("Location: ../../auth/login/");
    exit();
}

$currentUser = getUser();
$conn = getDatabaseConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new payment method
    if (isset($_POST['add_payment_method'])) {
        $cardNumber = str_replace(' ', '', $_POST['card_number']);
        $expiry = $_POST['expiry_date'];
        $cvv = $_POST['cvv'];
        $cardName = $_POST['card_name'];
        $isDefault = isset($_POST['is_default']) ? 1 : 0;
        
        // Basic validation
        if (!preg_match('/^\d{16}$/', $cardNumber)) {
            $_SESSION['error_message'] = "Invalid card number (must be 16 digits)";
        } elseif (!preg_match('/^(0[1-9]|1[0-2])\/?([0-9]{2})$/', $expiry)) {
            $_SESSION['error_message'] = "Invalid expiry date (MM/YY)";
        } elseif (!preg_match('/^\d{3,4}$/', $cvv)) {
            $_SESSION['error_message'] = "Invalid CVV (must be 3-4 digits)";
        } else {
            // If setting as default, first unset any existing default
            if ($isDefault) {
                $conn->query("UPDATE PaymentMethods SET is_default = 0 WHERE user_id = {$currentUser['user_id']}");
            }
            
            // Insert new payment method (store only last 4 digits)
            $lastFour = substr($cardNumber, -4);
            $stmt = $conn->prepare("INSERT INTO PaymentMethods (user_id, card_number, expiry_date, cvv, card_name, is_default) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssi", $currentUser['user_id'], $lastFour, $expiry, $cvv, $cardName, $isDefault);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Payment method added successfully!";
            } else {
                $_SESSION['error_message'] = "Error adding payment method: " . $conn->error;
            }
        }
    }
    
    // Remove payment method
    if (isset($_POST['remove_payment_method'])) {
        $methodId = $_POST['method_id'];
        
        $stmt = $conn->prepare("DELETE FROM PaymentMethods WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $methodId, $currentUser['user_id']);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Payment method removed successfully!";
        } else {
            $_SESSION['error_message'] = "Error removing payment method: " . $conn->error;
        }
    }
    
    // Set default payment method
    if (isset($_POST['set_default_payment'])) {
        $methodId = $_POST['method_id'];
        
        // First unset any existing default
        $conn->query("UPDATE PaymentMethods SET is_default = 0 WHERE user_id = {$currentUser['user_id']}");
        
        // Set new default
        $stmt = $conn->prepare("UPDATE PaymentMethods SET is_default = 1 WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $methodId, $currentUser['user_id']);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Default payment method updated!";
        } else {
            $_SESSION['error_message'] = "Error updating default payment method: " . $conn->error;
        }
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Get payment methods
$paymentMethods = [];
$stmt = $conn->prepare("SELECT id, card_number, expiry_date, card_name, is_default FROM PaymentMethods WHERE user_id = ? ORDER BY is_default DESC");
$stmt->bind_param("i", $currentUser['user_id']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $paymentMethods[] = $row;
}

// Get billing history
$billingHistory = [];
$stmt = $conn->prepare("SELECT id, invoice_number, amount, status, payment_date FROM BillingHistory WHERE user_id = ? ORDER BY payment_date DESC LIMIT 10");
$stmt->bind_param("i", $currentUser['user_id']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $billingHistory[] = $row;
}

// Get recent activities (without details column)
$activities = [];
$stmt = $conn->prepare("SELECT activity_type, activity_date FROM UserActivities WHERE user_id = ? ORDER BY activity_date DESC LIMIT 3");
$stmt->bind_param("i", $currentUser['user_id']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $activities[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Games - Duarcade</title>
    <meta name="description" content="Duarcade - Your ultimate gaming destination. Explore a wide range of games, connect with fellow gamers, and enjoy an immersive gaming experience.">
    <meta name="keywords" content="Duarcade, gaming, games, online games, multiplayer, arcade games">
    <meta name="author" content="Duarcade Team">
    <meta name="theme-color" content="#000000">
    <link rel="icon" href="images/favicon.ico" type="image/x-icon">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgY9F8c7Jpaj6x5I/Cbm+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet" />
    <!-- main css -->
    <link rel="stylesheet" href="../../../assets/css/style.css" />
    <style>
        .profile-sidebar {
            background: linear-gradient(135deg, #4158D0 0%, #C850C0 100%);
        }

        .nav-pills .nav-link {
            color: #6c757d;
            border-radius: 10px;
            padding: 12px 20px;
            margin: 4px 0;
            transition: all 0.3s ease;
        }

        .nav-pills .nav-link:hover {
            background-color: #f8f9fa;
        }

        .nav-pills .nav-link.active {
            background-color: #fff;
            color: #4158D0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .profile-header {
            background: linear-gradient(135deg, #4158D0 0%, #C850C0 100%);
            height: 150px;
            border-radius: 15px;
        }

        .profile-pic {
            width: 120px;
            height: 120px;
            border: 4px solid #fff;
            margin-top: -60px;
            background-color: #fff;
        }

        .settings-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .settings-card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .form-switch .form-check-input {
            width: 3em;
            height: 1.5em;
            margin-left: -3.5em;
        }

        .activity-item {
            border-left: 2px solid #e9ecef;
            padding-left: 20px;
            position: relative;
        }

        .activity-item::before {
            content: '';
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #4158D0;
            position: absolute;
            left: -7px;
            top: 5px;
        }

        .form-control {
            padding: 10px;
            transition: all 0.3s ease;
        }

        #profilePicture {
            padding: 10px;
            transition: all 0.3s ease;
        }

        .bg-dark-2 {
            background-color: #1e1e1e;
        }

        .settings-card {
            border-radius: 10px;
        }

        .payment-method {
            transition: all 0.3s ease;
        }

        .payment-method:hover {
            background-color: #252525 !important;
        }

        .table-dark {
            --bs-table-bg: #1e1e1e;
            --bs-table-striped-bg: #252525;
            --bs-table-hover-bg: #2a2a2a;
        }

        .form-control:focus,
        .form-select:focus {
            background-color: #1e1e1e;
            border-color: #495057;
            color: #fff;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
    </style>
</head>

<body>
    <!-- ============== Header ============== -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-2" style="border-radius: 30px;">
        <a class="navbar-brand me-3" href="#" style="margin-left: 15px;">
            <img src="https://www.svgrepo.com/show/303109/adobe-xd-logo.svg" alt="Logo" height="40">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse p-2" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="../../">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="../../games/">Games</a></li>
            </ul>

            <form class="d-flex me-3">
                <input class="form-control" type="search" placeholder="Search" aria-label="Search">
                <button class="btn btn-outline-light" type="submit"
                    style="border:1px solid #444; border-radius: 0px 20px 20px 0px; background-color: #333;">
                    Search
                </button>
            </form>

            <!-- Login Icon (Visible when not logged in) -->
            <?php if (!$isLoggedIn): ?>
                <div id="loginIcon" class="login-icon">
                    <a href="../../../auth/login/" class="btn btn-outline-light align-items-center login-btn ">
                        <i class="ri-login-circle-line"></i>
                    </a>
                </div>
            <?php endif; ?>

            <!-- Profile Dropdown (Visible when logged in) -->
            <?php if ($isLoggedIn): ?>
                <div id="profileDropdown" class="dropdown profile-dropdown">
                    <a class="d-flex align-items-center text-white text-decoration-none me-3 ms-3" href="#" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="../../../assets/img/profile/<?php echo htmlspecialchars($currentUser['profile_image'] ? 'user_' . $currentUser['user_id'] . '.jpg' : 'default.png'); ?>" alt="Profile Image" class="rounded-circle" width="40" height="40">
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark" aria-labelledby="dropdownUser">
                        <!-- Show Dashboard link only if user is admin -->
                        <?php if ($userRole === 'admin'): ?>
                            <li><a class="dropdown-item" href="../../../admin/dashboard.php">Dashboard</a></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item" href="../../profile/">Profile</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="../../../auth/logout/">Logout</a></li>
                    </ul>
                </div>

                <!-- Cart Icon (Visible when logged in) -->
                <div id="cartIcon">
                    <a href="../../cart/" class="btn btn-outline-light align-items-center login-btn">
                        <i class="ri-shopping-cart-2-line"></i>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </nav>

    <!-- ============== Main ============== -->
    <div class="text-white">
        <div class="container py-5">
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_SESSION['success_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_SESSION['error_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>
            
            <div class="row">
                <!-- Profile Header -->
                <div class="col-12 mb-4">
                    <div class="position-relative mb-4"></div>
                    <div class="text-center">
                        <div class="position-relative d-inline-block mb-3">
                            <img src="../../../assets/img/profile/<?php echo htmlspecialchars($currentUser['profile_image'] ? 'user_' . $currentUser['user_id'] . '.jpg' : 'default.png'); ?>" 
                                 alt="Profile Image" 
                                 class="rounded-circle profile-pic shadow">
                        </div>
                        <div class="profile-info-container bg-dark p-4 rounded-3 shadow" style="max-width: 500px; margin: 0 auto;">
                            <h2 class="mb-2"><?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></h2>
                            <p class="text-muted mb-3">
                                <i class="ri-mail-line me-2"></i><?php echo htmlspecialchars($currentUser['email']); ?>
                            </p>
                            <div class="d-flex justify-content-center gap-3">
                                <a href="../profile/" class="btn btn-primary px-4">
                                    <i class="ri-edit-circle-line me-2"></i>Edit Profile
                                </a>
                                <a href="../../../auth/logout/" class="btn btn-outline-light px-4">
                                    <i class="ri-logout-circle-line me-2"></i>Logout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="col-12">
                    <div class="border-0">
                        <div class="card-body p-0">
                            <div class="row g-0">
                                <!-- Sidebar -->
                                <div class="col-lg-3 border-end">
                                    <div class="p-4">
                                        <div class="nav flex-column nav-pills">
                                            <a class="nav-link" href="../../profile/"><i class="fas fa-user me-2"></i>Personal Info</a>
                                            <a class="nav-link active" href="../billing/"><i class="fas fa-credit-card me-2"></i>Billing</a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Content Area -->
                                <div class="col-lg-9">
                                    <div class="p-4">
                                        <h4 class="mb-4 text-light">Billing Information</h4>
                                        
                                        <!-- Payment Methods Card -->
                                        <div class="card settings-card mb-4 bg-dark border-secondary">
                                            <div class="card-body">
                                                <h5 class="mb-3 text-light">Payment Methods</h5>
                                                <p class="text-muted">Manage your payment methods and billing information.</p>

                                                <!-- Credit Card Section -->
                                                <div class="payment-method bg-dark-2 p-3 rounded mb-3">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <h6 class="mb-0 text-light">
                                                            <i class="fas fa-credit-card me-2"></i>Saved Cards
                                                        </h6>
                                                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCardModal">
                                                            <i class="fas fa-plus me-1"></i>Add New
                                                        </button>
                                                    </div>
                                                    
                                                    <div class="saved-cards">
                                                        <?php if (empty($paymentMethods)): ?>
                                                            <div class="text-center py-3">
                                                                <i class="fas fa-credit-card fa-2x text-muted mb-2"></i>
                                                                <p class="text-muted mb-0">No payment methods saved</p>
                                                            </div>
                                                        <?php else: ?>
                                                            <?php foreach ($paymentMethods as $method): ?>
                                                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-secondary">
                                                                    <div class="d-flex align-items-center">
                                                                        <?php 
                                                                        // Determine card type based on first digit (simplified)
                                                                        $firstDigit = substr($method['card_number'], 0, 1);
                                                                        $cardClass = "fa-cc-visa";
                                                                        $cardBrand = "Visa";
                                                                        if ($firstDigit == '5') {
                                                                            $cardClass = "fa-cc-mastercard";
                                                                            $cardBrand = "Mastercard";
                                                                        } elseif ($firstDigit == '3') {
                                                                            $cardClass = "fa-cc-amex";
                                                                            $cardBrand = "American Express";
                                                                        } elseif ($firstDigit == '4') {
                                                                            $cardClass = "fa-cc-visa";
                                                                            $cardBrand = "Visa";
                                                                        }
                                                                        ?>
                                                                        <i class="fab <?php echo $cardClass; ?> fs-4 me-3 text-primary"></i>
                                                                        <div>
                                                                            <p class="mb-0 text-light">
                                                                                <?php echo $cardBrand; ?> ending in <?php echo htmlspecialchars($method['card_number']); ?>
                                                                                <?php if ($method['is_default']): ?>
                                                                                    <span class="badge bg-success ms-2">Default</span>
                                                                                <?php endif; ?>
                                                                            </p>
                                                                            <small class="text-muted">Expires <?php echo htmlspecialchars($method['expiry_date']); ?></small>
                                                                        </div>
                                                                    </div>
                                                                    <div class="d-flex">
                                                                        <?php if (!$method['is_default']): ?>
                                                                            <form method="post" class="me-2">
                                                                                <input type="hidden" name="method_id" value="<?php echo $method['id']; ?>">
                                                                                <button type="submit" name="set_default_payment" class="btn btn-outline-success btn-sm">
                                                                                    <i class="fas fa-check-circle"></i> Set Default
                                                                                </button>
                                                                            </form>
                                                                        <?php endif; ?>
                                                                        <form method="post" onsubmit="return confirm('Are you sure you want to remove this payment method?');">
                                                                            <input type="hidden" name="method_id" value="<?php echo $method['id']; ?>">
                                                                            <button type="submit" name="remove_payment_method" class="btn btn-outline-danger btn-sm">
                                                                                <i class="fas fa-trash-alt"></i> Remove
                                                                            </button>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Billing History -->
                                        <div class="card settings-card mb-4 bg-dark border-secondary">
                                            <div class="card-body">
                                                <h5 class="mb-3 text-light">Billing History</h5>
                                                <p class="text-muted">View your billing history and download invoices.</p>

                                                <div class="table-responsive">
                                                    <table class="table table-dark table-hover">
                                                        <thead>
                                                            <tr>
                                                                <th>Invoice #</th>
                                                                <th>Date</th>
                                                                <th>Amount</th>
                                                                <th>Status</th>
                                                                <th>Action</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php if (empty($billingHistory)): ?>
                                                                <tr>
                                                                    <td colspan="5" class="text-center py-4">
                                                                        <i class="fas fa-receipt fa-2x text-muted mb-2"></i>
                                                                        <p class="text-muted mb-0">No billing history found</p>
                                                                    </td>
                                                                </tr>
                                                            <?php else: ?>
                                                                <?php foreach ($billingHistory as $invoice): ?>
                                                                    <tr>
                                                                        <td>#<?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                                                                        <td><?php echo date('M j, Y', strtotime($invoice['payment_date'])); ?></td>
                                                                        <td>$<?php echo number_format($invoice['amount'], 2); ?></td>
                                                                        <td>
                                                                            <?php 
                                                                            $statusClass = 'bg-secondary';
                                                                            if ($invoice['status'] == 'paid') $statusClass = 'bg-success';
                                                                            elseif ($invoice['status'] == 'pending') $statusClass = 'bg-warning text-dark';
                                                                            elseif ($invoice['status'] == 'failed') $statusClass = 'bg-danger';
                                                                            ?>
                                                                            <span class="badge <?php echo $statusClass; ?>">
                                                                                <?php echo ucfirst(htmlspecialchars($invoice['status'])); ?>
                                                                            </span>
                                                                        </td>
                                                                        <td>
                                                                            <a href="download_invoice.php?id=<?php echo $invoice['id']; ?>" class="btn btn-sm btn-outline-light">
                                                                                <i class="fas fa-download me-1"></i>Download
                                                                            </a>
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            <?php endif; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Add Card Modal -->
                                <div class="modal fade" id="addCardModal" tabindex="-1" aria-labelledby="addCardModalLabel" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content bg-dark">
                                            <div class="modal-header border-secondary">
                                                <h5 class="modal-title text-light" id="addCardModalLabel">Add New Card</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form method="post">
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label for="cardNumber" class="form-label text-light">Card Number</label>
                                                        <input type="text" class="form-control bg-dark-2 border-secondary text-light" 
                                                               id="cardNumber" name="card_number" placeholder="1234 5678 9012 3456" required
                                                               pattern="\d{16}" title="16-digit card number">
                                                    </div>
                                                    <div class="row mb-3">
                                                        <div class="col-md-6">
                                                            <label for="expiryDate" class="form-label text-light">Expiry Date</label>
                                                            <input type="text" class="form-control bg-dark-2 border-secondary text-light" 
                                                                   id="expiryDate" name="expiry_date" placeholder="MM/YY" required
                                                                   pattern="(0[1-9]|1[0-2])\/[0-9]{2}" title="MM/YY format">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label for="cvv" class="form-label text-light">CVV</label>
                                                            <input type="text" class="form-control bg-dark-2 border-secondary text-light" 
                                                                   id="cvv" name="cvv" placeholder="123" required
                                                                   pattern="\d{3,4}" title="3 or 4-digit CVV">
                                                        </div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="cardName" class="form-label text-light">Name on Card</label>
                                                        <input type="text" class="form-control bg-dark-2 border-secondary text-light" 
                                                               id="cardName" name="card_name" placeholder="John Doe" required>
                                                    </div>
                                                    <div class="form-check mb-3">
                                                        <input class="form-check-input" type="checkbox" id="isDefault" name="is_default" value="1">
                                                        <label class="form-check-label text-light" for="isDefault">
                                                            Set as default payment method
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="modal-footer border-secondary">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="add_payment_method" class="btn btn-primary">Save Card</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============== Footer ============== -->
    <footer class="text-white pt-5 pb-4">
        <div class="container text-md-left">
            <div class="row text-md-left">
                <!-- Company Info -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <h5 class="text-uppercase mb-3">Duarcade</h5>
                    <p>
                        We are dedicated to providing the best service to our customers. Our mission is to create innovative solutions that make a difference in people's lives.
                    </p>
                    <div class="d-flex gap-3">
                        <a href="#" class="text-white"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5 class="text-uppercase mb-3">Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white text-decoration-none">About Us</a></li>
                        <li><a href="#" class="text-white text-decoration-none">Services</a></li>
                        <li><a href="#" class="text-white text-decoration-none">Products</a></li>
                        <li><a href="#" class="text-white text-decoration-none">Contact</a></li>
                        <li><a href="#" class="text-white text-decoration-none">Careers</a></li>
                    </ul>
                </div>

                <!-- Support -->
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5 class="text-uppercase mb-3">Support</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white text-decoration-none">FAQ</a></li>
                        <li><a href="#" class="text-white text-decoration-none">Help Center</a></li>
                        <li><a href="#" class="text-white text-decoration-none">Privacy Policy</a></li>
                        <li><a href="#" class="text-white text-decoration-none">Terms of Service</a></li>
                        <li><a href="#" class="text-white text-decoration-none">Contact Support</a></li>
                    </ul>
                </div>

                <!-- Newsletter -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <h5 class="text-uppercase mb-3">Newsletter</h5>
                    <p>Subscribe to our newsletter for updates, news, and exclusive offers.</p>
                    <form class="d-flex">
                        <input type="email" class="form-control" placeholder="Enter your email">
                        <button class="btn btn-outline-light" type="submit" style="border:1px solid #444;border-radius: 0px 20px 20px 0px; background-color: #333;">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Bottom Links -->
            <div class="row border-top pt-3 mt-3">
                <div class="col-md-6">
                    <ul class="list-inline mb-0">
                        <li class="list-inline-item"><a href="#" class="text-white text-decoration-none">Privacy Policy</a></li>
                        <li class="list-inline-item"><a href="#" class="text-white text-decoration-none">Terms of Service</a></li>
                        <li class="list-inline-item"><a href="#" class="text-white text-decoration-none">Cookie Policy</a></li>
                    </ul>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">© 2024 Duarcade. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- ============== Script ============== -->
    <script src="../../../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Format card number input
        document.getElementById('cardNumber')?.addEventListener('input', function(e) {
            this.value = this.value.replace(/\s+/g, '').replace(/(\d{4})/g, '$1 ').trim();
        });

        // Format expiry date input
        document.getElementById('expiryDate')?.addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '')
                .replace(/^(\d{2})/, '$1/')
                .substr(0, 5);
        });

        // Restrict CVV to numbers only
        document.getElementById('cvv')?.addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '');
        });
    </script>
</body>
</html>