<?php
require_once '../../../assets/database/auth.php';
require_once '../../../assets/database/config.php';

checkSessionTimeout();

$isLoggedIn = isLoggedIn();
$user = $isLoggedIn ? getUser() : null;
$userRole = $user['role'] ?? null;

if (!$isLoggedIn) {
    header("Location: ../../../auth/login/");
    exit();
}
$currentUser = getUser();

// Retrieve users from the database
$conn = getDatabaseConnection();
$stmt = $conn->prepare("SELECT user_id, username, email, first_name, last_name, role FROM Users");
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Retrieve game data from the database
$gameId = isset($_GET['id']) ? (int)$_GET['id'] : 1; // Get game ID from URL or default to 1
$stmt = $conn->prepare("SELECT * FROM Games WHERE game_id = ?");
$stmt->bind_param("i", $gameId);
$stmt->execute();
$game = $stmt->get_result()->fetch_assoc();

if (!$game) {
    header("Location: ../../../games/");
    exit();
}

// Retrieve game images
$stmtImages = $conn->prepare("SELECT * FROM GameImages WHERE game_id = ?");
$stmtImages->bind_param("i", $gameId);
$stmtImages->execute();
$gameImages = $stmtImages->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($game['title']); ?> - Duarcade</title>
    <meta name="description" content="<?php echo htmlspecialchars($game['description']); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($game['title']); ?>, game, Duarcade, <?php echo htmlspecialchars($game['genre'] ?? ''); ?>">
    <meta name="author" content="Duarcade Team">
    <meta name="theme-color" content="#000000">
    <link rel="icon" href="../../../assets/images/favicon.ico" type="image/x-icon">
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
        .card {
            position: relative;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            color: white;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
        }
        .product-container {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
        }
        .product-images {
            flex: 1;
            min-width: 300px;
        }
        .product-details {
            flex: 1;
            min-width: 300px;
        }
        .img-thumbnail {
            cursor: pointer;
            transition: transform 0.2s;
        }
        .img-thumbnail:hover {
            transform: scale(1.05);
        }
        .btn-add-to-cart {
            background-color: #4e44ce;
            border: none;
            padding: 10px 20px;
            border-radius: 20px;
            font-weight: bold;
        }
        .btn-add-to-cart:hover {
            background-color: #3a32a8;
        }
    </style>
</head>

<body>
    <!-- ============== Header ============== -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-2" style="border-radius: 30px;">
        <a class="navbar-brand me-3" href="../../../" style="margin-left: 15px;">
            <img src="https://www.svgrepo.com/show/303109/adobe-xd-logo.svg" alt="Logo" height="40">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse p-2" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="../../../">Home</a></li>
                <li class="nav-item"><a class="nav-link active" aria-current="page" href="../../../games/">Games</a></li>
            </ul>

            <form class="d-flex me-3"  method="GET" action="../../../assets/php/search.php">
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
                <div id="cartIcon" class="me-2">
                    <a href="../../cart/" class="btn btn-outline-light align-items-center login-btn">
                        <i class="ri-shopping-cart-2-line"></i>
                    </a>
                </div>
                
                <div id="profileDropdown" class="dropdown profile-dropdown">
                    <a class="d-flex align-items-center text-white text-decoration-none" href="#" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="../../../assets/img/profile/<?php echo htmlspecialchars($currentUser['profile_image'] ? 'user_' . $currentUser['user_id'] . '.jpg' : 'default.png'); ?>" alt="Profile Image" width="40" height="40" class="rounded-circle">
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark" aria-labelledby="dropdownUser">
                        <?php if ($userRole === 'admin'): ?>
                            <li><a class="dropdown-item" href="../../../admin/dashboard.php">Dashboard</a></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item" href="../../Profile/">Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../../../auth/logout/">Logout</a></li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </nav>

    <!-- ============== Product Details ============== -->
    <div class="container py-5 text-white">
        <div class="product-container">
            <!-- Product Images -->
            <div class="product-images mb-4">
                <div class="card">
                    <img src="../../../assets/img/games/<?php echo htmlspecialchars($game['cover_image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($game['title']); ?>" id="mainImage">
                    <div class="card-body">
                        <div class="row g-2">
                            <?php foreach ($gameImages as $image): ?>
                                <div class="col-3">
                                    <img src="../../../assets/img/games/<?php echo htmlspecialchars($image['image_url']); ?>" class="img-thumbnail" alt="Thumbnail" onclick="document.getElementById('mainImage').src = this.src">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <<!-- Product Details -->
<div class="product-details">
    <h1 class="h2 mb-3"><?php echo htmlspecialchars($game['title']); ?></h1>
    <div class="mb-3">
        <?php if ($game['discounted_price'] && $game['discounted_price'] < $game['actual_price']): ?>
            <span class="h4 me-2 text-danger">$<?php echo number_format($game['discounted_price'], 2); ?></span>
            <span class="text-muted text-decoration-line-through">$<?php echo number_format($game['actual_price'], 2); ?></span>
            <span class="badge bg-danger ms-2"><?php echo round((($game['actual_price'] - $game['discounted_price']) / $game['actual_price']) * 100); ?>% OFF</span>
        <?php else: ?>
            <span class="h4 me-2">$<?php echo number_format($game['actual_price'], 2); ?></span>
        <?php endif; ?>
    </div>

    <div class="mb-3">
        <span class="badge bg-primary me-2"><?php echo htmlspecialchars($game['genre'] ?? 'Action'); ?></span>
        <span class="badge bg-secondary"><?php echo htmlspecialchars($game['platform'] ?? 'Multi-platform'); ?></span>
    </div>

    <!-- Quantity -->
    <div class="mb-4">
        <div class="d-flex align-items-center">
            <label class="me-2">Quantity:</label>
            <select class="form-select w-auto" id="quantity">
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
                <option value="5">5</option>
            </select>
        </div>
    </div>

    <div class="d-grid gap-2 mb-4">
        <button class="btn btn-add-to-cart" onclick="addToCart(<?php echo $gameId; ?>)">
            <i class="ri-shopping-cart-2-line me-2"></i>Add to Cart
        </button>
    </div>

    <div class="mb-3">
        <h5 class="mb-3">About this game</h5>
        <p><?php echo htmlspecialchars($game['description']); ?></p>
    </div>

    <div class="mb-3">
        <h5 class="mb-3">Details</h5>
        <ul>
            <li><strong>Release Date:</strong> <?php echo date('F j, Y', strtotime($game['release_date'] ?? '2023-01-01')); ?></li>
            <li><strong>Developer:</strong> <?php echo htmlspecialchars($game['developer'] ?? 'Unknown'); ?></li>
            <li><strong>Publisher:</strong> <?php echo htmlspecialchars($game['publisher'] ?? 'Unknown'); ?></li>
        </ul>
    </div>
</div>

        </div>
    </div>

    <!-- ============== Footer ============== -->
    <footer class="text-white pt-5 pb-4 bg-dark">
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
    <script>
    function addToCart(gameId) {
        const quantity = document.getElementById('quantity').value;

        // Validate quantity
        if (quantity <= 0) {
            alert('Please select a valid quantity.');
            return;
        }

        // Send POST request to add game to cart
        fetch('path_to_addToCart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                game_id: gameId,
                quantity: quantity
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Game added to cart successfully!');
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while adding to cart.');
        });
    }
</script>
</body>
</html>