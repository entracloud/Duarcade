<?php
require_once '../../assets/database/auth.php';

if (isLoggedIn()) {
    redirectBasedOnRole();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if ($password !== $confirm_password) {
        $error = "Passwords don't match";
    } else {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $conn = getDatabaseConnection(); // Ensure you have a database connection
            $stmt = $conn->prepare("INSERT INTO Users (username, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $hashed_password]);
            $success = "Registration successful! Please login.";
        } catch(PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "Email or username already exists";
            } else {
                $error = "Registration failed: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Duarcade</title>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    
    <style>
        .social-login-btn {
            display: flex;
            justify-content: space-between;
            margin: 15px 0;
        }
        .social-btn {
            flex: 1;
            margin: 0 5px;
            color: white !important;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            border-radius: 4px;
            height: 40px;
            cursor: pointer;
            text-decoration: none;
        }
        .social-btn svg {
            margin-right: 8px;
        }
        .google-btn {
            background-color: #db4437;
        }
        .facebook-btn {
            background-color: #1877f2;
        }
        .github-btn {
            background-color: #333;
        }
        .social-btn:hover {
            opacity: 0.9;
            text-decoration: none;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow">
                    <div class="card-body p-4">
                        <h2 class="text-center mb-4">Register</h2>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                        <?php endif; ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <button type="submit" name="register" class="btn btn-primary w-100">Register</button>
                        </form>

                        <div class="social-login-btn">
                            <a href="oauth/google-login.php" class="social-btn google-btn" title="Login with Google">
                                <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false" width="20" height="20" viewBox=" 0 0 24 24" fill="white"><path d="M21.806 10.23h-9.61v3.54h5.43c-.247 1.36-1.516 4-5.43 4-3.28 0-5.975-2.7-5.975-6 0-3.305 2.696-6 5.975-6 1.86 0 3.11.805 3.83 1.5l2.6-2.48C17.523 5 15.48 4.05 13 4.05c-5.27 0-9.55 4.37-9.55 9.8 0 5.44 4.28 9.8 9.55 9.8 5.5 0 9.15-3.84 9.15-9.27 0-.63-.07-1.08-.34-1.14z"/></svg>
                                Google
                            </a>
                            <a href="oauth/facebook-login.php" class="social-btn facebook-btn" title="Login with Facebook">
                                <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false" width="20" height="20" viewBox="0 0 24 24" fill="white"><path d="M22 12c0-5.55-4.45-10-10-10S2 6.45 2 12c0 4.99 3.66 9.12 8.44 9.88v-7H8v-3h2.44v-2.2c0-2.41 1.43-3.75 3.63-3.75 1.05 0 2.14.19 2.14.19v2.36H15.5c-1.18 0-1.54.73-1.54 1.48v1.72h2.63l-.42 3h-2.21v7C18.34 21.12 22 16.99 22 12z"/></svg>
                                Facebook
                            </a>
                            <a href="oauth/github-login.php" class="social-btn github-btn" title="Login with GitHub">
                                <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false" width="20" height="20" viewBox="0 0 24 24" fill="white"><path d="M12 2C6.48 2 2 6.58 2 12.238c0 4.52 2.865 8.35 6.84 9.71.5.09.68-.22.68-.48 0-.24-.01-1.04-.015-1.88-2.782.62-3.37-1.357-3.37-1.357-.455-1.18-1.11-1.49-1.11-1.49-.908-.65.07-.64.07-.64 1.004.07 1.53 1.04 1.53 1.04.892 1.55 2.34 1.1 2.91.85.09-.65.35-1.1.635-1.35-2.22-.26-4.555-1.13-4.555-5.03 0-1.11.39-2.01 1.03-2.72-.1-.26-.45-1.31.1-2.72 0 0 .84-.27 2.75 1.03a9.31 9.31 0 012.5-.35c.85.004 1.71.12 2.5.35 1.9-1.31 2.75-1.03 2.75-1.03.55 1.41.2 2.46.1 2.72.64.7 1.03 1.61 1.03 2.72 0 3.91-2.34 4.77-4.56 5.02.36.32.67.93.67 1.88 0 1.36-.012 2.46-.012 2.8 0 .27.18.59.69.48A10.27 10.27 0 0022 12.238C22 6.58 17.52 2  12 2z"/></svg>
                                GitHub
                            </a>
                        </div>

                        <div class="mt-3 text-center">
                            Already have an account? <a href="../login/">Login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>