<?php
require_once '../../assets/database/auth.php';
require_once '../../assets/database/config.php';

checkSessionTimeout();

$isLoggedIn = isLoggedIn();
$user = $isLoggedIn ? getUser() : null;
$userRole = $user['role'] ?? null;

if (!$isLoggedIn) {
    header("Location: ../../auth/login/");
    exit();
}

$currentUser = getUser();
$userId = $currentUser['user_id'];

// Connect to database
$conn = getDatabaseConnection();

// Handle remove item from cart
if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $gameId = $_GET['remove'];
    $stmt = $conn->prepare("DELETE FROM Cart WHERE user_id = ? AND game_id = ?");
    $stmt->bind_param("ii", $userId, $gameId);
    $stmt->execute();
    header("Location: ./");
    exit();
}

// Handle quantity update
if (isset($_POST['update_quantity'])) {
    $gameId = $_POST['game_id'];
    $quantity = $_POST['quantity'];
    
    if ($quantity < 1) $quantity = 1;
    if ($quantity > 10) $quantity = 10;
    
    $stmt = $conn->prepare("UPDATE Cart SET quantity = ? WHERE user_id = ? AND game_id = ?");
    $stmt->bind_param("iii", $quantity, $userId, $gameId);
    $stmt->execute();
    header("Location: ./");
    exit();
}

// Get cart items with game details
$stmt = $conn->prepare("
    SELECT c.game_id, c.quantity, g.title, g.description, 
           g.actual_price, g.discounted_price, g.platform, g.cover_image
    FROM Cart c
    JOIN Games g ON c.game_id = g.game_id
    WHERE c.user_id = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$cartItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate totals
$subtotal = 0;
$itemCount = count($cartItems);

foreach ($cartItems as $item) {
    $price = $item['discounted_price'] ?: $item['actual_price'];
    $subtotal += $price * $item['quantity'];
}

$shipping = $itemCount > 0 ? 5.99 : 0;
$tax = $subtotal * 0.08; // 8% tax
$total = $subtotal + $shipping + $tax;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart - Duarcade</title>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>

    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet" />
    <!-- main css -->
    <link rel="stylesheet" href="../../assets/css/style.css" />
    <style>
        .shopping-cart-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .shopping-cart-container h1 {
            margin-bottom: 20px;
            color: #1565c0;
            font-weight: 500;
        }

        .cart-summary {
            margin-bottom: 20px;
            color: #7f8c8d;
        }

        .cart-items {
            border-top: 1px solid #e1e1e1;
        }

        .cart-item {
            display: grid;
            grid-template-columns: 80px 3fr 1fr 1fr 30px;
            grid-gap: 15px;
            align-items: center;
            padding: 20px 0;
            border-bottom: 1px solid #e1e1e1;
            position: relative;
        }

        .item-image img {
            width: 100%;
            height: auto;
            border-radius: 4px;
            object-fit: cover;
        }

        .item-details h3 {
            margin: 0 0 8px;
            font-size: 16px;
            font-weight: 500;
        }

        .item-description {
            margin: 0 0 8px;
            font-size: 14px;
            color: #7f8c8d;
        }

        .item-price {
            font-weight: 500;
            color: #1565c0;
            margin: 0;
        }

        .item-quantity {
            display: flex;
            align-items: center;
        }

        .quantity-btn {
            width: 30px;
            height: 30px;
            background: #1565c0;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s;
        }

        .quantity-btn:hover {
            background-color: #3498db;
        }

        .quantity-input {
            width: 40px;
            height: 30px;
            text-align: center;
            margin: 0 8px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }

        .item-total {
            font-weight: 600;
            color: #1565c0;
        }

        .remove-item {
            background: none;
            border: none;
            color: #e74c3c;
            font-size: 20px;
            cursor: pointer;
            padding: 0;
            line-height: 1;
        }

        .cart-footer {
            margin-top: 30px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-gap: 30px;
        }

        .subtotal {
            line-height: 1.8;
        }

        .total {
            text-align: right;
        }

        .total h2 {
            color: #1565c0;
            font-weight: 600;
        }

        .cart-actions {
            grid-column: span 2;
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        #continue-shopping {
            background: white;
            border: none;
            color: #1565c0;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
        }

        #continue-shopping:hover {
            background: #f8f9fa;
        }

        #checkout-btn {
            background: #3498db;
            border: none;
            color: white;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
        }

        #checkout-btn:hover {
            background: #2980b9;
        }

        @media (max-width: 768px) {
            .cart-item {
                grid-template-columns: 60px 3fr 1fr;
                grid-template-rows: auto auto;
                grid-gap: 10px;
            }

            .item-total {
                grid-column: 3;
                grid-row: 2;
            }

            .item-quantity {
                grid-column: 2;
                grid-row: 2;
            }

            .remove-item {
                position: absolute;
                top: 10px;
                right: 0;
            }

            .cart-footer {
                grid-template-columns: 1fr;
            }

            .total {
                text-align: left;
            }

            .cart-actions {
                grid-column: 1;
                flex-direction: column;
                gap: 10px;
            }

            #continue-shopping,
            #checkout-btn {
                width: 100%;
            }
        }

        /* payment done modal */
        .success-modal .modal-content {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            text-align: center;
            max-width: 400px;
            width: 90%;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: #4CAF50;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            padding: 8px;
        }

        .title {
            color: #2d3748;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .message {
            color: #718096;
            font-size: 16px;
            line-height: 1.5;
            margin-bottom: 32px;
        }

        .details {
            background: #f8f9fa;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 32px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            color: #4a5568;
            font-size: 14px;
        }

        .detail-row:last-child {
            margin-bottom: 0;
        }

        .label {
            color: #718096;
        }

        .value {
            font-weight: 500;
        }

        .button {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
            width: 100%;
        }

        .button:hover {
            background: #43a047;
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
                <li class="nav-item"><a class="nav-link" href="../">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="../games/">Games</a></li>
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
                    <a href="../../auth/login/" class="btn btn-outline-light align-items-center login-btn ">
                        <i class="ri-login-circle-line"></i>
                    </a>
                </div>
            <?php endif; ?>

            <!-- Profile Dropdown (Visible when logged in) -->
            <?php if ($isLoggedIn): ?>
                <div id="profileDropdown" class="dropdown profile-dropdown">
                    <a class="d-flex align-items-center text-white text-decoration-none me-3 ms-3" href="#" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="../../assets/img/profile/<?php echo htmlspecialchars($user['avatar'] ?? 'default.png'); ?>" alt="Profile Image" width="40" height="40" class="rounded-circle">
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark" aria-labelledby="dropdownUser">
                        <!-- Show Dashboard link only if user is admin -->
                        <?php if ($userRole === 'admin'): ?>
                            <li><a class="dropdown-item" href="../../admin/dashboard.php">Dashboard</a></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item" href="../profile/">Profile</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="../../auth/logout/">Logout</a></li>
                    </ul>
                </div>

                <!-- Cart Icon (Visible when logged in) -->
                <div id="cartIcon">
                    <a href="../cart/" class="btn btn-outline-light align-items-center login-btn active" aria-current="page">
                        <i class="ri-shopping-cart-2-line"></i>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </nav>
    <!-- ============== Cart ============== -->
    <div class="shopping-cart-container text-white">
        <h1>Your Shopping Cart</h1>
        <div class="cart-summary">
            <p id="item-count"><?php echo $itemCount; ?> item<?php echo $itemCount !== 1 ? 's' : ''; ?> in your cart</p>
        </div>

        <div class="cart-items">
            <?php foreach ($cartItems as $item): 
                $price = $item['discounted_price'] ?: $item['actual_price'];
                $itemTotal = $price * $item['quantity'];
            ?>
                <div class="cart-item" data-id="<?php echo $item['game_id']; ?>" data-price="<?php echo $price; ?>">
                    <div class="item-image">
                        <img src="../../assets/img/games/<?php echo htmlspecialchars($item['cover_image'] ?: 'https://via.placeholder.com/80'); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                    </div>
                    <div class="item-details">
                        <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                        <p class="item-description"><?php echo htmlspecialchars($item['description']); ?></p>
                        <p class="item-price">$<?php echo number_format($price, 2); ?></p>
                    </div>
                    <div class="item-quantity">
                        <form method="post" class="d-flex align-items-center">
                            <input type="hidden" name="game_id" value="<?php echo $item['game_id']; ?>">
                            <button type="button" class="quantity-btn minus">-</button>
                            <input type="number" class="quantity-input" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="10">
                            <button type="button" class="quantity-btn plus">+</button>
                            <button type="submit" name="update_quantity" class="btn btn-sm btn-primary ms-2">Update</button>
                        </form>
                    </div>
                    <div class="item-total">
                        <p>$<span class="item-total-price"><?php echo number_format($itemTotal, 2); ?></span></p>
                    </div>
                    <a href="?remove=<?php echo $item['game_id']; ?>" class="remove-item">×</a>
                </div>
            <?php endforeach; ?>

            <?php if ($itemCount === 0): ?>
                <div class="text-center py-5">
                    <h4>Your cart is empty</h4>
                    <p>Start shopping to add items to your cart</p>
                    <a href="../games/" class="btn btn-primary">Browse Games</a>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($itemCount > 0): ?>
        <div class="cart-footer">
            <div class="subtotal">
                <p>Subtotal: $<span id="subtotal-amount"><?php echo number_format($subtotal, 2); ?></span></p>
                <p>Shipping: $<span id="shipping-amount"><?php echo number_format($shipping, 2); ?></span></p>
                <p>Tax: $<span id="tax-amount"><?php echo number_format($tax, 2); ?></span></p>
            </div>
            <div class="total">
                <h2>Total: $<span id="total-amount"><?php echo number_format($total, 2); ?></span></h2>
            </div>
            <div class="cart-actions">
                <a href="../games/" id="continue-shopping">Continue Shopping</a>
                <a href="checkout/" id="checkout-btn">Proceed to Checkout</a>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- alert -->
    <div class="container  p-5">
        <div class="row">
            <div class="modal fade" id="statusErrorsModal" tabindex="-1" role="dialog" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
                    <div class="modal-content">
                        <div class="modal-body text-center p-lg-4">
                            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 130.2 130.2">
                                <circle class="path circle" fill="none" stroke="#db3646" stroke-width="6" stroke-miterlimit="10" cx="65.1" cy="65.1" r="62.1" />
                                <line class="path line" fill="none" stroke="#db3646" stroke-width="6" stroke-linecap="round" stroke-miterlimit="10" x1="34.4" y1="37.9" x2="95.8" y2="92.3" />
                                <line class="path line" fill="none" stroke="#db3646" stroke-width="6" stroke-linecap="round" stroke-miterlimit="10" x1="95.8" y1="38" X2="34.4" y2="92.2" />
                            </svg>
                            <h4 class="text-danger mt-3">Empty Cart!</h4>
                            <p class="mt-3">Your cart is empty, please Try again.</p>
                            <button type="button" class="btn btn-sm mt-3 btn-danger" data-bs-dismiss="modal">Ok</button>
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
    <script src="../../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const cartItems = document.querySelectorAll('.cart-item');
            const itemCountEl = document.getElementById('item-count');
            const subtotalEl = document.getElementById('subtotal-amount');
            const shippingEl = document.getElementById('shipping-amount');
            const taxEl = document.getElementById('tax-amount');
            const totalEl = document.getElementById('total-amount');
            const continueShoppingBtn = document.getElementById('continue-shopping');
            const checkoutBtn = document.getElementById('checkout-btn');

            // Add event listeners to quantity buttons
            cartItems.forEach(item => {
                const minusBtn = item.querySelector('.minus');
                const plusBtn = item.querySelector('.plus');
                const quantityInput = item.querySelector('.quantity-input');
                const form = item.querySelector('form');

                minusBtn.addEventListener('click', () => {
                    if (quantityInput.value > 1) {
                        quantityInput.value = parseInt(quantityInput.value) - 1;
                        updateItemTotal(item);
                    }
                });

                plusBtn.addEventListener('click', () => {
                    if (quantityInput.value < 10) {
                        quantityInput.value = parseInt(quantityInput.value) + 1;
                        updateItemTotal(item);
                    }
                });

                quantityInput.addEventListener('change', () => {
                    if (quantityInput.value < 1) quantityInput.value = 1;
                    if (quantityInput.value > 10) quantityInput.value = 10;
                    updateItemTotal(item);
                });
            });

            // Update the total for a specific item
            function updateItemTotal(item) {
                const price = parseFloat(item.dataset.price);
                const quantity = parseInt(item.querySelector('.quantity-input').value);
                const itemTotalEl = item.querySelector('.item-total-price');
                const itemTotal = price * quantity;
                itemTotalEl.textContent = itemTotal.toFixed(2);
            }

            // Checkout button functionality
            checkoutBtn?.addEventListener('click', function(e) {
                const itemCount = document.querySelectorAll('.cart-item').length;
                const emptyCartModal = new bootstrap.Modal(document.getElementById('statusErrorsModal'));

                if (itemCount === 0) {
                    e.preventDefault();
                    emptyCartModal.show();
                }
            });
        });
    </script>
</body>
</html>