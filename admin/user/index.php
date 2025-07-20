<?php
require_once '../../assets/database/auth.php';
require_once '../../assets/database/config.php';

checkSessionTimeout();

// Check if the user is logged in and is an admin
if (!isLoggedIn() || getUser()['role'] !== 'admin') {
    header("Location: ../auth/login/");
    exit();
}

$currentUser = getUser();

// Retrieve users from the database
$conn = getDatabaseConnection();
$stmt = $conn->prepare("SELECT user_id, username, email, first_name, last_name, role FROM Users");
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Users - Duarcade</title>
    <meta name="description" content="Duarcade - Your ultimate gaming destination. Explore a wide range of games, connect with fellow gamers, and enjoy an immersive gaming experience.">
    <meta name="keywords" content="Duarcade, gaming, games, online games, multiplayer, arcade games">
    <meta name="author" content="Duarcade Team">
    <meta name="theme-color" content="#000000">
    <link rel="icon" href="images/favicon.ico" type="image/x-icon">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">

    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

    <!--=============== REMIXICONS ===============-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.2.0/remixicon.css">
    <!--=============== CSS ===============-->
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        .table-responsive {
            overflow-x: auto;
        }
        .table {
            width: 100%;
            margin-bottom: 1rem;
            color: #212529;
        }
        .table th, .table td {
            padding: 0.75rem;
            vertical-align: top;
            border-top: 1px solid #dee2e6;
        }
        .table thead th {
            vertical-align: bottom;
            border-bottom: 2px solid #dee2e6;
            background-color: #f8f9fa;
        }
        .table tbody + tbody {
            border-top: 2px solid #dee2e6;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            line-height: 1.5;
            border-radius: 0.2rem;
        }
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
    </style>
</head>

<body>
    <!--=============== HEADER ===============-->
    <header class="header" id="header">
        <div class="header__container">
            <a href="#" class="header__logo">
                <i class="ri-cloud-fill"></i>
                <span>Cloud</span>
            </a>

            <button class="header__toggle" id="header-toggle">
                <i class="ri-menu-line"></i>
            </button>
        </div>
    </header>

    <!--=============== SIDEBAR ===============-->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar__container">
            <div class="sidebar__user">
                <div class="sidebar__img">
                    <img src="../../assets/img/profile/<?php echo htmlspecialchars($currentUser['profile_image'] ? 'user_' . $currentUser['user_id'] . '.jpg' : 'default.png'); ?>" alt="Profile Image">
                </div>

                <div class="sidebar__info">
                    <h3><?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></h3>
                    <span><?php echo htmlspecialchars($currentUser['email'] ?? 'user@example.com'); ?></span>
                </div>
            </div>

            <div class="sidebar__content">
                <div>
                    <div class="sidebar__list">
                        <a href="../../public/" class="sidebar__link">
                            <i class="ri-home-3-line"></i>
                            <span>Home</span>
                        </a>
                    </div>
                </div>

                <div>
                    <h3 class="sidebar__title">MANAGE</h3>
                    <div class="sidebar__list">
                        <a href="../../admin/user/" class="sidebar__link active-link">
                            <i class="ri-user-line"></i>
                            <span>Users</span>
                        </a>
                    </div>
                </div>

                <div>
                    <h3 class="sidebar__title">Store</h3>

                    <div class="sidebar__list">
                        <a href="../../admin/game/" class="sidebar__link">
                            <i class="ri-gamepad-line"></i>
                            <span>Games</span>
                        </a>

                        <a href="../../admin/status/" class="sidebar__link">
                            <i class="ri-file-list-3-line"></i>
                            <span>Orders</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="sidebar__actions">
                <button>
                    <i class="ri-moon-clear-line sidebar__link sidebar__theme" id="theme-button">
                        <span>Theme</span>
                    </i>
                </button>

                <a href="../../auth/logout/" class="sidebar__link">
                    <i class="ri-logout-circle-r-line"></i>
                    <span>Log Out</span>
                </a>
            </div>
        </div>
    </nav>

    <!--=============== MAIN ===============-->
    <main class="main container" id="main">
        <h1>User Management</h1>
        
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-light">
                    <tr>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['first_name'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($user['last_name'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['role']); ?></td>
                            <td class="action-buttons">
                                <a href="edit-user/?id=<?php echo $user['user_id']; ?>" class="btn btn-primary btn-sm">
                                    <i class="ri-edit-line"></i> Edit
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
    <!--=============== MAIN JS ===============-->
    <script src="../../assets/js/admin.js"></script>
</body>
</html>