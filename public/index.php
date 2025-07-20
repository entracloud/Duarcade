<?php
require_once '../assets/database/auth.php';
require_once '../assets/database/config.php';

checkSessionTimeout();

$isLoggedIn = isLoggedIn();
$user = $isLoggedIn ? getUser() : null;
$userRole = $user['role'] ?? null;

if (!$isLoggedIn) {
    header("Location: ../auth/login/");
    exit();
}
$currentUser = getUser();

// Connect to database
$conn = getDatabaseConnection();

// Retrieve featured games (you can modify this query to get actual featured games)
$stmt = $conn->prepare("
    SELECT g.game_id, g.title, g.description, g.actual_price, g.discounted_price, 
           g.platform, g.cover_image, gen.name as genre_name
    FROM Games g
    LEFT JOIN Genres gen ON g.genre_id = gen.genre_id
    ORDER BY g.game_id DESC
    LIMIT 6
");
$stmt->execute();
$featuredGames = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Retrieve genres for the hero section
$stmt = $conn->prepare("
    SELECT g.genre_id, g.name, g.description, g.image_url, 
           COUNT(ga.game_id) as game_count
    FROM Genres g
    LEFT JOIN Games ga ON g.genre_id = ga.genre_id
    GROUP BY g.genre_id
    ORDER BY game_count DESC
    LIMIT 6
");
$stmt->execute();
$genres = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Retrieve all games for the products section
$stmt = $conn->prepare("
    SELECT g.game_id, g.title, g.description, g.actual_price, g.discounted_price, 
           g.platform, g.cover_image, gen.name as genre_name, inv.stock_quantity
    FROM Games g
    LEFT JOIN Genres gen ON g.genre_id = gen.genre_id
    LEFT JOIN Inventory inv ON g.game_id = inv.game_id
    ORDER BY g.game_id DESC
");
$stmt->execute();
$games = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Retrieve all genres for filters
$stmt = $conn->prepare("SELECT genre_id, name FROM Genres");
$stmt->execute();
$allGenres = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Duarcade</title>
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
    <link rel="stylesheet" href="../assets/css/style.css" />
    <style>
        /* ============= featured ================ */
        .header {
            color: white;
            text-align: center;
            margin-bottom: 40px;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            background: linear-gradient(45deg, #ff3366, #0d6efd, #ff9933);
            -webkit-text-fill-color: transparent;
            -webkit-background-clip: text;
            background-clip: text;
        }

        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            max-width: 1200px;
            width: 100%;
        }

        .card {
            position: relative;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 20px;
            color: white;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .card::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg,
                    transparent,
                    rgba(255, 255, 255, 0.2),
                    transparent);
            transition: 0.5s;
        }

        .card:hover::after {
            left: 100%;
        }

        .card-image {
            width: 100%;
            height: 200px;
            border-radius: 15px;
            overflow: hidden;
            position: relative;
            margin-bottom: 20px;
        }

        .card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }

        .card:hover .card-image img {
            transform: scale(1.1);
        }

        .tag {
            position: absolute;
            top: 10px;
            right: 10px;
            background: linear-gradient(45deg, #ff3366, #ff9933);
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .content {
            position: relative;
        }

        .title {
            font-size: 1.4rem;
            margin-bottom: 10px;
            color: #fff;
        }

        .description {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .price {
            font-size: 1.8rem;
            font-weight: bold;
            margin: 15px 0;
            color: #ff3366;
            text-shadow: 0 0 10px rgba(255, 51, 102, 0.3);
        }

        .button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 25px;
            background: linear-gradient(45deg, #ff3366, #ff9933);
            color: white;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 51, 102, 0.4);
        }

        .button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg,
                    transparent,
                    rgba(255, 255, 255, 0.2),
                    transparent);
            transition: 0.5s;
        }

        .button:hover::before {
            left: 100%;
        }

        .rating {
            display: flex;
            gap: 5px;
            margin: 10px 0;
        }

        .star {
            color: #ffd700;
            font-size: 1.2rem;
        }

        @media (max-width: 768px) {
            .card-grid {
                grid-template-columns: 1fr;
                padding: 20px;
            }
        }

        .stock-status {
            position: absolute;
            top: 10px;
            left: 10px;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            background: rgba(0, 0, 0, 0.5);
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }

            100% {
                transform: scale(1);
            }
        }

        .new-badge {
            animation: pulse 2s infinite;
        }

        /* Hero section styles */
        .bento-item {
            position: relative;
            height: 100%;
            border-radius: 15px;
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .bento-item:hover {
            transform: scale(1.02);
        }

        .bento-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }

        .bento-item:hover img {
            transform: scale(1.1);
        }

        .bento-content {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 20px;
            background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
            color: white;
        }

        .bento-tall {
            height: 500px;
        }

        .bento-content h3, .bento-content h4 {
            margin: 0;
            font-weight: bold;
        }

        .bento-content p {
            margin: 5px 0 0;
            font-size: 0.9rem;
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
                <li class="nav-item"><a class="nav-link active" aria-current="page" href="../public/">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="../public/games/">Games</a></li>
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
                    <a href="../auth/login/" class="btn btn-outline-light align-items-center login-btn ">
                        <i class="ri-login-circle-line"></i>
                    </a>
                </div>
            <?php endif; ?>

            <!-- Profile Dropdown (Visible when logged in) -->
            <?php if ($isLoggedIn): ?>
                <div id="profileDropdown" class="dropdown profile-dropdown">
                    <a class="d-flex align-items-center text-white text-decoration-none me-3 ms-3" href="#" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="../assets/img/profile/<?php echo htmlspecialchars($currentUser['profile_image'] ? 'user_' . $currentUser['user_id'] . '.jpg' : 'default.png'); ?>" alt="Profile Image" width="40" height="40" class="rounded-circle">
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark" aria-labelledby="dropdownUser">
                        <?php if ($userRole === 'admin'): ?>
                            <li><a class="dropdown-item" href="../admin/dashboard.php">Dashboard</a></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item" href="profile/">Profile</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="../auth/logout/">Logout</a></li>
                    </ul>
                </div>

                <!-- Cart Icon (Visible when logged in) -->
                <div id="cartIcon">
                    <a href="../public/cart/" class="btn btn-outline-light align-items-center login-btn">
                        <i class="ri-shopping-cart-2-line"></i>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </nav>


    <!-- ============== featured ============== -->
    <!-- featured header -->
    <div class="header mt-5">
        <h1>Featured Products</h1>
        <p style="color: rgba(255,255,255,0.7)">Discover our premium collection</p>
    </div>
    <div class="container">
        <div id="featuredCarousel" class="carousel slide" data-bs-ride="carousel">

            <!-- Carousel Indicators (Dots) -->
            <div class="carousel-indicators" style="bottom:-60px">
                <button type="button" data-bs-target="#featuredCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                <button type="button" data-bs-target="#featuredCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
                <button type="button" data-bs-target="#featuredCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
            </div>

            <div class="carousel-inner">
                <?php 
                // Split featured games into slides of 3 items each
                $slides = array_chunk($featuredGames, 3);
                foreach ($slides as $slideIndex => $slideGames): 
                ?>
                    <div class="carousel-item <?php echo $slideIndex === 0 ? 'active' : ''; ?>">
                        <div class="row">
                            <?php foreach ($slideGames as $game): 
                                $price = $game['discounted_price'] ?: $game['actual_price'];
                            ?>
                                <div class="col-md-4">
                                    <div class="col">
                                        <div class="card h-100 shadow-sm" style="border-radius: 15px;">
                                            <img src="../assets/img/games/<?php echo htmlspecialchars($game['cover_image'] ?: 'default.jpg'); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($game['title']); ?>" style="border-radius: 15px 15px 0 0;">
                                            <div class="card-body">
                                                <h5 class="card-title"><?php echo htmlspecialchars($game['title']); ?></h5>
                                                <p class="card-text"><?php echo htmlspecialchars($game['description']); ?></p>
                                            </div>
                                            <div class="card-footer bg-transparent border-top-0">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <p class="text-primary fw-bold">$<?php echo number_format($price, 2); ?></p>
                                                    <a href="games/product-detail/?id=<?php echo $game['game_id']; ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div> <!-- carousel-inner end -->
        </div>
    </div>
    <!-- ============== Hero ============== -->
    <!-- Hero header -->
    <div class="header mt-5">
        <h1>Categories</h1>
        <p style="color: rgba(255,255,255,0.7)">Browse games by genre</p>
    </div>
    <!-- Hero grid -->
    <div class="container my-5">
        <div class="row g-4">
            <!-- Large image carousel (left side) -->
            <div class="col-md-8">
                <div id="bentoCarousel" class="carousel slide" data-bs-ride="carousel">
                    <!-- Indicators (dots) -->
                    <div class="carousel-indicators">
                        <button type="button" data-bs-target="#bentoCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                        <button type="button" data-bs-target="#bentoCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
                        <button type="button" data-bs-target="#bentoCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
                    </div>

                    <div class="carousel-inner rounded">
                        <?php 
                        // Split genres into slides for carousel
                        $genreSlides = array_chunk($genres, 1);
                        foreach ($genreSlides as $slideIndex => $slideGenres): 
                        ?>
                            <div class="carousel-item <?php echo $slideIndex === 0 ? 'active' : ''; ?>">
                                <div class="bento-item bento-tall">
                                    <img src="../assets/img/genres/<?php echo htmlspecialchars($slideGenres[0]['image_url'] ?: 'default.jpg'); ?>" class="d-block w-100" alt="<?php echo htmlspecialchars($slideGenres[0]['name']); ?>">
                                    <div class="bento-content">
                                        <h3><?php echo htmlspecialchars($slideGenres[0]['name']); ?></h3>
                                        <p><?php echo htmlspecialchars($slideGenres[0]['description']); ?></p>
                                        <a href="../public/games/?genre=<?php echo $slideGenres[0]['genre_id']; ?>" class="btn btn-primary mt-2">Browse Games</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Two small items (right top) -->
            <div class="col-md-4">
                <div class="row g-4">
                    <?php for ($i = 1; $i < min(3, count($genres)); $i++): ?>
                        <div class="col-12">
                            <div class="bento-item">
                                <img src="../assets/img/genres/<?php echo htmlspecialchars($genres[$i]['image_url'] ?: 'default.jpg'); ?>" alt="<?php echo htmlspecialchars($genres[$i]['name']); ?>">
                                <div class="bento-content">
                                    <h4><?php echo htmlspecialchars($genres[$i]['name']); ?></h4>
                                    <a href="../public/games/?genre=<?php echo $genres[$i]['genre_id']; ?>" class="btn btn-sm btn-primary mt-1">View</a>
                                </div>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Bottom row: 3 medium items -->
            <?php for ($i = 3; $i < min(6, count($genres)); $i++): ?>
                <div class="col-md-4">
                    <div class="bento-item">
                        <img src="../assets/img/genres/<?php echo htmlspecialchars($genres[$i]['image_url'] ?: 'default.jpg'); ?>" alt="<?php echo htmlspecialchars($genres[$i]['name']); ?>">
                        <div class="bento-content">
                            <h4><?php echo htmlspecialchars($genres[$i]['name']); ?></h4>
                            <a href="../public/games/?genre=<?php echo $genres[$i]['genre_id']; ?>" class="btn btn-sm btn-primary mt-1">View</a>
                        </div>
                    </div>
                </div>
            <?php endfor; ?>
        </div>
    </div>

    <!-- ============== Products ============== -->
    <!-- Products header -->
    <div class="header mt-5">
        <h1>Games</h1>
        <p style="color: rgba(255,255,255,0.7)">Discover our premium collection</p>
    </div>
    <!-- ============== Products ============== -->
    <!-- Products grid -->
    <div class="container py-5 text-white">
        <!-- Top Bar -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">Game Collection</h4>
            <div class="d-flex gap-2 align-items-center">
                <span class="text-secondary">Sort by:</span>
                <div class="dropdown">
                    <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        Newest
                    </button>
                    <ul class="dropdown-menu bg-dark" aria-labelledby="sortDropdown">
                        <li><button class="dropdown-item btn btn-outline-light btn-sm" onclick="updateSort('Newest')">Newest</button></li>
                        <li><button class="dropdown-item btn btn-outline-light btn-sm" onclick="updateSort('Oldest')">Oldest</button></li>
                        <li><button class="dropdown-item btn btn-outline-light btn-sm" onclick="updateSort('Most Popular')">Most Popular</button></li>
                        <li><button class="dropdown-item btn btn-outline-light btn-sm" onclick="updateSort('Highest Rated')">Highest Rated</button></li>
                    </ul>
                </div>
            </div>
        </div>

        <script>
            function updateSort(sortOption) {
                document.getElementById('sortDropdown').textContent = sortOption;
            }
        </script>

        <!-- Products Grid -->
        <div class="row g-4">
            <!-- Filters Sidebar -->
            <div class="col-lg-3">
                <div class="p-4 shadow-sm rounded bg-secondary bg-opacity-10">
                    <div class="mb-4">
                        <h6 class="mb-3">Genres</h6>
                        <?php foreach ($allGenres as $genre): ?>
                            <div class="form-check mb-2">
                                <input class="form-check-input bg-dark border-secondary genre-filter"
                                    type="checkbox"
                                    id="genre-<?php echo $genre['genre_id']; ?>"
                                    value="<?php echo $genre['genre_id']; ?>">
                                <label class="form-check-label" for="genre-<?php echo $genre['genre_id']; ?>">
                                    <?php echo htmlspecialchars($genre['name']); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="mb-4">
                        <h6 class="mb-3">Price Range</h6>
                        <input type="range" class="form-range" min="0" max="100" value="50" id="priceRange">
                        <div class="d-flex justify-content-between small text-secondary">
                            <span>$0</span>
                            <span>$100</span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h6 class="mb-3">Platform</h6>
                        <div class="form-check mb-2">
                            <input class="form-check-input bg-dark border-secondary" type="checkbox" id="platform-pc">
                            <label class="form-check-label" for="platform-pc">PC</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input bg-dark border-secondary" type="checkbox" id="platform-console">
                            <label class="form-check-label" for="platform-console">Console</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input bg-dark border-secondary" type="checkbox" id="platform-mobile">
                            <label class="form-check-label" for="platform-mobile">Mobile</label>
                        </div>
                    </div>

                    <button class="btn btn-outline-light w-100" id="applyFilters">Apply Filters</button>
                </div>
            </div>
            <!-- Product Grid -->
            <div class="col-lg-9">
                <div class="row g-4" id="gameContainer">
                    <?php foreach ($games as $game): 
                        $price = $game['discounted_price'] ?: $game['actual_price'];
                    ?>
                        <div class="col-md-4 game-card"
                            data-genre="<?php echo htmlspecialchars($game['genre_name']); ?>"
                            data-price="<?php echo $price; ?>">
                            <div class="bg-dark border border-secondary rounded shadow-sm h-100">
                                <div class="position-relative">
                                    <img src="../assets/img/games/<?php echo htmlspecialchars($game['cover_image'] ?: 'default.jpg'); ?>"
                                        class="w-100 rounded-top"
                                        alt="<?php echo htmlspecialchars($game['title']); ?>"
                                        style="height: 200px; object-fit: cover;">
                                    <?php if ($game['discounted_price']): ?>
                                        <span class="badge bg-danger position-absolute top-0 end-0 m-2">
                                            -<?php echo round(100 - ($game['discounted_price'] / $game['actual_price'] * 100)); ?>%
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="p-3">
                                    <span class="badge bg-secondary mb-2"><?php echo htmlspecialchars($game['genre_name']); ?></span>
                                    <h6 class="mb-1 text-white"><?php echo htmlspecialchars($game['title']); ?></h6>
                                    <p class="small text-muted mb-2"><?php echo htmlspecialchars($game['platform']); ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <?php if ($game['discounted_price']): ?>
                                                <span class="text-decoration-line-through text-muted small me-2">$<?php echo $game['actual_price']; ?></span>
                                                <span class="text-primary fw-bold">$<?php echo $game['discounted_price']; ?></span>
                                            <?php else: ?>
                                                <span class="text-primary fw-bold">$<?php echo $game['actual_price']; ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="game_id" value="<?php echo $game['game_id']; ?>">
                                                <button type="submit" name="add_to_cart" class="btn btn-sm btn-primary"
                                                    <?php echo ($game['stock_quantity'] <= 0) ? 'disabled' : ''; ?>>
                                                    <i class="ri-shopping-cart-2-line"></i>
                                                </button>
                                            </form>
                                            <a href="games/product-detail/?id=<?php echo $game['game_id']; ?>" class="btn btn-sm btn-outline-light ms-1">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <?php if ($game['stock_quantity'] <= 0): ?>
                                        <div class="text-danger small mt-2">Out of Stock</div>
                                    <?php elseif ($game['stock_quantity'] < 5): ?>
                                        <div class="text-warning small mt-2">Only <?php echo $game['stock_quantity']; ?> left!</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <!-- minimal pagination -->
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center pagination-dark">
            <li class="page-item">
                <a class="page-link" href="#">Previous</a>
            </li>
            <li class="page-item active"><a class="page-link" href="#">1</a></li>
            <li class="page-item"><a class="page-link" href="#">2</a></li>
            <li class="page-item"><a class="page-link" href="#">3</a></li>
            <li class="page-item">
                <a class="page-link" href="#">Next</a>
            </li>
        </ul>
    </nav>


    <!-- ============== Footer ============== -->
    <footer class=" text-white pt-5 pb-4">
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
    <script src="../assets/js/script.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Filter games based on selected filters
        document.getElementById('applyFilters').addEventListener('click', function() {
            const selectedGenres = [];
            document.querySelectorAll('.genre-filter:checked').forEach(checkbox => {
                selectedGenres.push(checkbox.value);
            });
            
            const priceRange = document.getElementById('priceRange').value;
            
            document.querySelectorAll('.game-card').forEach(card => {
                const genre = card.dataset.genre;
                const price = parseFloat(card.dataset.price);
                
                const genreMatch = selectedGenres.length === 0 || selectedGenres.includes(genre);
                const priceMatch = price <= priceRange;
                
                if (genreMatch && priceMatch) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    </script>
</body>

</html>