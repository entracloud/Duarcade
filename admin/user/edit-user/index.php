<?php
require_once '../../../assets/database/auth.php';
require_once '../../../assets/database/config.php';

checkSessionTimeout();

// Check if the user is logged in and is an admin
if (!isLoggedIn() || getUser()['role'] !== 'admin') {
    header("Location: ../../auth/login/");
    exit();
}

$currentUser = getUser();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle profile update
    if (isset($_POST['update_profile'])) {
        $userId = $_POST['user_id'];
        $firstName = $_POST['first_name'];
        $lastName = $_POST['last_name'];
        $email = $_POST['email'];
        
        // Update user in database
        $conn = getDatabaseConnection();
        $stmt = $conn->prepare("UPDATE Users SET first_name = ?, last_name = ?, email = ? WHERE user_id = ?");
        $stmt->bind_param("sssi", $firstName, $lastName, $email, $userId);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Profile updated successfully!";
        } else {
            $_SESSION['error_message'] = "Error updating profile: " . $conn->error;
        }
    }
    
    // Handle password change
    if (isset($_POST['change_password'])) {
        $userId = $_POST['user_id'];
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        // Verify current password
        $conn = getDatabaseConnection();
        $stmt = $conn->prepare("SELECT password FROM Users WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if (password_verify($currentPassword, $user['password'])) {
            if ($newPassword === $confirmPassword) {
                $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("UPDATE Users SET password = ? WHERE user_id = ?");
                $stmt->bind_param("si", $hashedPassword, $userId);
                
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Password changed successfully!";
                } else {
                    $_SESSION['error_message'] = "Error changing password: " . $conn->error;
                }
            } else {
                $_SESSION['error_message'] = "New passwords don't match!";
            }
        } else {
            $_SESSION['error_message'] = "Current password is incorrect!";
        }
    }
    
    // Handle profile picture upload
    if (isset($_POST['upload_avatar']) && isset($_FILES['profile_picture'])) {
        $userId = $_POST['user_id'];
        $file = $_FILES['profile_picture'];
        
        // Validate file
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        if (in_array($file['type'], $allowedTypes) && $file['size'] <= $maxSize) {
            // Read file as binary data
            $imageData = file_get_contents($file['tmp_name']);
            
            // Update database
            $conn = getDatabaseConnection();
            $stmt = $conn->prepare("UPDATE Users SET profile_image = ? WHERE user_id = ?");
            $null = null;
            $stmt->bind_param("bi", $null, $userId);
            $stmt->send_long_data(0, $imageData);
            
            if ($stmt->execute()) {
                // Also save to file system if needed
                $targetDir = '../../../assets/img/profile/';
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                $filename = 'user_' . $userId . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
                $targetFile = $targetDir . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                    $_SESSION['success_message'] = "Profile picture updated successfully!";
                } else {
                    $_SESSION['error_message'] = "Profile picture saved to database but not to file system.";
                }
            } else {
                $_SESSION['error_message'] = "Error updating profile picture: " . $conn->error;
            }
        } else {
            $_SESSION['error_message'] = "Invalid file type or size (max 2MB allowed)!";
        }
    }
    
    // Redirect to avoid form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Get user data to edit (default to current user)
$editUserId = $_GET['id'] ?? $currentUser['user_id'];
$conn = getDatabaseConnection();
$stmt = $conn->prepare("SELECT user_id, username, email, first_name, last_name, role FROM Users WHERE user_id = ?");
$stmt->bind_param("i", $editUserId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Check if user exists
if (!$user) {
    header("Location: ../");
    exit();
}
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
    <link rel="stylesheet" href="../../../assets/css/admin.css">
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
        
        .profile-img-container {
            position: relative;
            display: inline-block;
        }
        
        .profile-img-container img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .profile-img-container .change-photo {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: #4158D0;
            color: white;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
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
                    <img src="../../../assets/img/profile/<?php echo htmlspecialchars($currentUser['profile_image'] ? 'user_' . $currentUser['user_id'] . '.jpg' : 'default.png'); ?>" alt="Profile Image">
                </div>

                <div class="sidebar__info">
                    <h3><?php echo htmlspecialchars($currentUser['username'] ?? 'User'); ?></h3>
                    <span><?php echo htmlspecialchars($currentUser['email'] ?? 'user@example.com'); ?></span>
                </div>
            </div>

            <div class="sidebar__content">
                <div>
                    <div class="sidebar__list">
                        <a href="../../" class="sidebar__link">
                            <i class="ri-home-3-line"></i>
                            <span>Home</span>
                        </a>
                    </div>
                </div>

                <div>
                    <h3 class="sidebar__title">MANAGE</h3>
                    <div class="sidebar__list">
                        <a href="../../user/" class="sidebar__link active-link">
                            <i class="ri-user-line"></i>
                            <span>Users</span>
                        </a>
                    </div>
                </div>

                <div>
                    <h3 class="sidebar__title">Store</h3>

                    <div class="sidebar__list">
                        <a href="../../game/" class="sidebar__link">
                            <i class="ri-gamepad-line"></i>
                            <span>Games</span>
                        </a>

                        <a href="../../status/" class="sidebar__link">
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

                <a href="../../../auth/logout/" class="sidebar__link">
                    <i class="ri-logout-circle-r-line"></i>
                    <span>Log Out</span>
                </a>
            </div>
        </div>
    </nav>

    <!--=============== MAIN ===============-->
    <main class="main container" id="main">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                <?php echo htmlspecialchars($_SESSION['success_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                <?php echo htmlspecialchars($_SESSION['error_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
        
        <h1 class="mb-4">User Settings</h1>
        
        <form method="post" action="" enctype="multipart/form-data">
            <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <h4>Personal Information</h4>
                    <div class="mb-3">
                        <label for="firstName" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="firstName" name="first_name" 
                               value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="lastName" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="lastName" name="last_name" 
                               value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($user['email']); ?>">
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                </div>
                
                <div class="col-md-6">
                    <h4>Profile Picture</h4>
                    <div class="profile-img-container mb-3">
                        <img id="profilePicPreview" src="../../../assets/img/profile/<?php 
                            echo htmlspecialchars(file_exists('../../../assets/img/profile/user_' . $user['user_id'] . '.jpg') ? 
                                'user_' . $user['user_id'] . '.jpg' : 'default.png'); 
                        ?>" alt="Profile Picture">
                        <label for="profilePicture" class="change-photo" title="Change Photo">
                            <i class="ri-camera-line"></i>
                        </label>
                        <input type="file" id="profilePicture" name="profile_picture" accept="image/*" 
                               style="display: none;" onchange="previewImage(event);">
                    </div>
                    <button type="submit" name="upload_avatar" class="btn btn-primary">Update Profile Picture</button>
                </div>
            </div>
            
            <hr>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <h4>Change Password</h4>
                    <div class="mb-3">
                        <label for="currentPassword" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="currentPassword" name="current_password">
                    </div>
                    <div class="mb-3">
                        <label for="newPassword" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="newPassword" name="new_password">
                    </div>
                    <div class="mb-3">
                        <label for="confirmPassword" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirmPassword" name="confirm_password">
                    </div>
                    <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                </div>
            </div>
        </form>
    </main>

    <!--=============== MAIN JS ===============-->
    <script src="../../../assets/js/admin.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function previewImage(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profilePicPreview').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        }
        
        // Show file dialog when clicking the camera icon
        document.querySelector('.change-photo').addEventListener('click', function() {
            document.getElementById('profilePicture').click();
        });
    </script>
</body>
</html>