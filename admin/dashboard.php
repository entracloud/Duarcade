<?php
require_once '../assets/database/auth.php';
require_once '../assets/database/config.php';

checkSessionTimeout();

// Check if the user is logged in and is an admin
if (!isLoggedIn() || getUser()['role'] !== 'admin') {
   header("Location: ../auth/login/");
   exit();
}

// Redirect to ../admin/user
header("Location: ../admin/user/");
exit();

$user = getUser();
?>