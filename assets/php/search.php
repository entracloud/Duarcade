<?php
// search_handler.php

require_once '../../assets/database/auth.php';

$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

$conn = getDatabaseConnection();
$sql = "SELECT g.game_id, g.title, g.description, g.actual_price, g.discounted_price, g.cover_image, ge.name as genre 
        FROM Games g
        LEFT JOIN Genres ge ON g.genre_id = ge.genre_id
        WHERE g.title LIKE ? OR g.description LIKE ? OR ge.name LIKE ?";

$searchTerm = "%" . $searchQuery . "%";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();

$games = $result->fetch_all(MYSQLI_ASSOC);
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

    <?php
// Generate HTML for results
if ($games) {
    foreach ($games as $game) {
        echo '<div class="col-md-4">
                <div class="card mb-4">
                    <img src="' . htmlspecialchars($game['cover_image']) . '" class="card-img-top" alt="' . htmlspecialchars($game['title']) . '">
                    <div class="card-body">
                        <h5 class="card-title">' . htmlspecialchars($game['title']) . '</h5>
                        <p class="card-text">' . htmlspecialchars(substr($game['description'], 0, 100)) . '...</p>
                        <p class="card-text"><strong>Genre:</strong> ' . htmlspecialchars($game['genre']) . '</p>
                        <p class="card-text">
                            <strong>Price:</strong> $' . number_format($game['actual_price'], 2) . '
                            ' . ($game['discounted_price'] && $game['discounted_price'] < $game['actual_price'] ? 
                                    '<span class="text-danger"><del>$' . number_format($game['actual_price'], 2) . '</del></span> 
                                     $' . number_format($game['discounted_price'], 2) : '') . '
                        </p>
                        <a href="../../public/games/product-detail/?id=' . $game['game_id'] . '" class="btn btn-primary">View Details</a>
                    </div>
                </div>
            </div>';
    }
} else {
    echo 'No games found matching your search criteria.';
}
$conn->close();
?>
   

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
</body>
</html>