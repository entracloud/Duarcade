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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle profile update
    if (isset($_POST['update_profile'])) {
        $firstName = $_POST['first_name'];
        $lastName = $_POST['last_name'];
        $email = $_POST['email'];

        $conn = getDatabaseConnection();
        $stmt = $conn->prepare("UPDATE Users SET first_name = ?, last_name = ?, email = ? WHERE user_id = ?");
        $stmt->bind_param("sssi", $firstName, $lastName, $email, $currentUser['user_id']);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Profile updated successfully!";
            // Refresh user data
            $currentUser = getUser();
        } else {
            $_SESSION['error_message'] = "Error updating profile: " . $conn->error;
        }
    }
}

// Get recent activities
$conn = getDatabaseConnection();
$stmt = $conn->prepare("SELECT activity_type, activity_date FROM UserActivities WHERE user_id = ? ORDER BY activity_date DESC LIMIT 3");
$stmt->bind_param("i", $currentUser['user_id']);
$stmt->execute();
$activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
    <link rel="stylesheet" href="../../assets/css/style.css" />
    <style>
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
                        <img src="../../assets/img/profile/<?php echo htmlspecialchars($currentUser['profile_image'] ? 'user_' . $currentUser['user_id'] . '.jpg' : 'default.png'); ?>" alt="Profile Image" class="rounded-circle" width="40" height="40">
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
                    <a href="../cart/" class="btn btn-outline-light align-items-center login-btn">
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
                            <img src="../../assets/img/profile/<?php echo htmlspecialchars($currentUser['profile_image'] ? 'user_' . $currentUser['user_id'] . '.jpg' : 'default.png'); ?>"
                                alt="Profile Image"
                                class="rounded-circle profile-pic shadow">
                        </div>
                        <div class="profile-info-container bg-dark p-4 rounded-3 shadow" style="max-width: 500px; margin: 0 auto;">
                            <h2 class="mb-2"><?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></h2>
                            <p class="text-muted mb-3">
                                <i class="ri-mail-line me-2"></i><?php echo htmlspecialchars($currentUser['email']); ?>
                            </p>
                            <div class="d-flex justify-content-center gap-3">
                                <a href="user-setting/" class="btn btn-primary px-4">
                                    <i class="ri-edit-circle-line me-2"></i>Edit Profile
                                </a>
                                <a href="../../auth/logout/" class="btn btn-outline-light px-4">
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
                                            <a class="nav-link active" href="../profile/">
                                                <i class="fas fa-user me-2"></i>Personal Info
                                            </a>
                                            <a class="nav-link" href="billing/">
                                                <i class="fas fa-credit-card me-2"></i>Billing
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Content Area -->
                                <div class="col-lg-9">
                                    <div class="p-4">
                                        <!-- Personal Information -->
                                        <div class="mb-5">
                                            <h4 class="mb-4 border-bottom pb-3">Profile Details</h4>
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">First Name</label>
                                                        <div class="form-control bg-dark text-white border-secondary">
                                                            <?php echo htmlspecialchars($currentUser['first_name']); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Last Name</label>
                                                        <div class="form-control bg-dark text-white border-secondary">
                                                            <?php echo htmlspecialchars($currentUser['last_name']); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Email</label>
                                                        <div class="form-control bg-dark text-white border-secondary">
                                                            <?php echo htmlspecialchars($currentUser['email']); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Account Created</label>
                                                        <div class="form-control bg-dark text-white border-secondary">
                                                            <?php echo date('F j, Y', strtotime($currentUser['created_at'] ?? 'now')); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Recent Activity -->
                                        <div>
                                            <h4 class="mb-4 border-bottom pb-3">Recent Activity</h4>
                                            <?php if (!empty($activities)): ?>
                                                <div class="activity-list">
                                                    <?php foreach ($activities as $activity): ?>
                                                        <div class="activity-item mb-3">
                                                            <div class="d-flex justify-content-between">
                                                                <h6 class="mb-1">
                                                                    <i class="ri-history-line me-2"></i>
                                                                    <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $activity['activity_type']))); ?>
                                                                </h6>
                                                                <span class="text-muted small">
                                                                    <?php echo date('M j, g:i a', strtotime($activity['activity_date'])); ?>
                                                                </span>
                                                            </div>
                                                            <p class="text-muted small mb-0 ps-4">
                                                                <?php
                                                                // Add descriptive text based on activity type
                                                                switch ($activity['activity_type']) {
                                                                    case 'login':
                                                                        echo "You logged in to your account";
                                                                        break;
                                                                    case 'profile_update':
                                                                        echo "You updated your profile information";
                                                                        break;
                                                                    default:
                                                                        echo "Account activity recorded";
                                                                }
                                                                ?>
                                                            </p>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="text-center py-4">
                                                    <i class="ri-time-line display-4 text-muted mb-3"></i>
                                                    <h5>No recent activity</h5>
                                                    <p class="text-muted">Your activities will appear here</p>
                                                </div>
                                            <?php endif; ?>
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
    <script src="../../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>