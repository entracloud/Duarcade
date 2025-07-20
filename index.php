<?php
// index.php

// Function to check if setup is complete
function isSetupComplete() {
    // Check for the existence of a config file or a setup flag in a config or database
    return file_exists(__DIR__ . '/assets/database/config.php'); // Adjust the path as necessary
}

// Redirect based on setup completion status
if (!isSetupComplete()) {
    // If setup is not complete, redirect to setup/index.php
    header('Location: setup/');
    exit;
} else {
    // If setup is complete, redirect to public/index.php
    header('Location: public/');
    exit;
}
?>