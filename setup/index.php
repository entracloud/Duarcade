<?php
session_start();
ob_start();

// Define constants
define('SETUP_DIR', __DIR__);
define('ROOT_DIR', dirname(SETUP_DIR));
define('CONFIG_FILE', ROOT_DIR.'/assets/database/config.php');
define('DB_FILE', ROOT_DIR.'/assets/database/db.php');
define('AUTH_FILE', ROOT_DIR.'/assets/database/auth.php');
define('HTACCESS_FILE', ROOT_DIR.'/.htaccess');
define('PROFILE_IMG_DIR', ROOT_DIR.'/assets/img/profile/');

// Current step handling
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
if ($step < 1 || $step > 4) $step = 1;

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($step) {
        case 2:
            handleDatabaseSetup();
            break;
        case 3:
            handleSiteSetup();
            break;
        case 4:
            handleAdminSetup();
            break;
    }
}

function handleDatabaseSetup() {
    $_SESSION['db_host'] = $_POST['db_host'];
    $_SESSION['db_name'] = $_POST['db_name'];
    $_SESSION['db_user'] = $_POST['db_user'];
    $_SESSION['db_pass'] = $_POST['db_pass'];
    
    // Test connection
    try {
        $conn = new mysqli($_POST['db_host'], $_POST['db_user'], $_POST['db_pass'], $_POST['db_name']);
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        $conn->close();
        header("Location: ?step=3");
        exit();
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
        header("Location: ?step=2");
        exit();
    }
}

function handleSiteSetup() {
    $site_url = rtrim($_POST['site_url'], '/');
    
    $_SESSION['site_url'] = $site_url;
    $_SESSION['site_name'] = $_POST['site_name'];
    header("Location: ?step=4");
    exit();
}

function handleAdminSetup() {
    // Check if passwords match
    if ($_POST['password'] !== $_POST['confirm_password']) {
        $_SESSION['error_message'] = "Passwords don't match";
        header("Location: ?step=4");
        exit();
    }
    
    // Store admin details in session
    $_SESSION['admin_email'] = $_POST['email'];
    $_SESSION['admin_username'] = $_POST['username'];
    $_SESSION['admin_password'] = password_hash($_POST['password'], PASSWORD_BCRYPT);
    
    // Handle avatar upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $upload = handleProfileImageUpload($_FILES['profile_image']);
        if ($upload['success']) {
            $_SESSION['admin_profile_image'] = $upload['filename'];
            $_SESSION['admin_profile_image_data'] = $upload['image_data'];
        } else {
            $_SESSION['error_message'] = $upload['error'];
            header("Location: ?step=4");
            exit();
        }
    } else {
        // If no image is uploaded, set a default avatar
        $_SESSION['admin_profile_image'] = 'default.png';
        $defaultImagePath = PROFILE_IMG_DIR . 'default.png';
        if (file_exists($defaultImagePath)) {
            $_SESSION['admin_profile_image_data'] = file_get_contents($defaultImagePath);
        }
    }
    
    // Complete the setup process
    completeSetup();
}

function handleProfileImageUpload($file) {
    $target_dir = PROFILE_IMG_DIR;
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 2 * 1024 * 1024; // 2MB
    
    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'error' => 'Only JPG, PNG, and GIF images are allowed'];
    }
    
    if ($file['size'] > $max_size) {
        return ['success' => false, 'error' => 'Image size must be less than 2MB'];
    }

    // Read the file content as binary data
    $imageData = file_get_contents($file['tmp_name']);
    if ($imageData === false) {
        return ['success' => false, 'error' => 'Failed to read image file'];
    }

    // Generate a unique filename
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $username = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '_', $_SESSION['admin_username']));
    $filename = 'admin_' . $username . '.' . $ext;
    $target_file = $target_dir . $filename;

    // Save the file to disk (optional, if you want to keep a file copy)
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return [
            'success' => true, 
            'filename' => $filename,
            'image_data' => $imageData
        ];
    } else {
        return ['success' => false, 'error' => 'Failed to upload image'];
    }
}

function createDirectory($path) {
    if (!is_dir($path)) {
        if (!mkdir($path, 0755, true)) {
            throw new Exception("Failed to create directory: $path");
        }
    }
}

function completeSetup() {
    // Create necessary directories if they don't exist
    createDirectory(ROOT_DIR . '/assets/database');
    createDirectory(PROFILE_IMG_DIR);

    // Create config files
    createConfigFile();
    createDbFile();
    createAuthFile();
    
    // Update .htaccess
    updateHtaccess();
    
    // Create database tables
    initializeDatabase();
    
    // Create admin user
    createAdminUser();
    
    // Mark setup as complete
    file_put_contents(ROOT_DIR . '/setup_complete.flag', '1');
    
    // Redirect to home page
    header("Location: " . $_SESSION['site_url']);
    exit();
}

function createConfigFile() {
    $config = <<<EOT
<?php
// Auto-generated during setup
define('DB_HOST', '{$_SESSION['db_host']}');
define('DB_NAME', '{$_SESSION['db_name']}');
define('DB_USER', '{$_SESSION['db_user']}');
define('DB_PASS', '{$_SESSION['db_pass']}');
define('SITE_URL', '{$_SESSION['site_url']}');
define('SITE_NAME', '{$_SESSION['site_name']}');
EOT;
    file_put_contents(CONFIG_FILE, $config);
}

function createDbFile() {
    $dbFileContent = <<<EOT
<?php
// Auto-generated DB file
function getDatabaseConnection() {
    \$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (\$mysqli->connect_error) {
        die("Database connection failed: " . \$mysqli->connect_error);
    }
    return \$mysqli;
}
EOT;

    file_put_contents(DB_FILE, $dbFileContent);
}

function createAuthFile() {
    $authContent = <<<EOT
<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

session_start();

// Use SITE_URL defined in config.php as the base URL
\$baseUrl = rtrim(SITE_URL, '/') . '/';

// Define session timeout for admin users (30 minutes)
define('SESSION_TIMEOUT_ADMIN', 30 * 60);

function isLoggedIn() {
    return isset(\$_SESSION['user']);
}

function getUser() {
    return \$_SESSION['user'] ?? null;
}

function logout() {
    global \$baseUrl;
    session_destroy();
    header("Location: " . \$baseUrl . "auth/login/");
    exit();
}

function authenticateUser(\$email, \$password) {
    \$conn = getDatabaseConnection();
    \$stmt = \$conn->prepare("SELECT user_id, username, email, password, role, first_name, last_name, profile_image FROM Users WHERE email = ?");
    \$stmt->bind_param("s", \$email);
    \$stmt->execute();
    \$result = \$stmt->get_result();

    if (\$user = \$result->fetch_assoc()) {
        if (password_verify(\$password, \$user['password'])) {
            unset(\$user['password']);
            \$_SESSION['user'] = \$user;
            return true;
        }
    }
    return false;
}

function redirectAfterLogin() {
    global \$baseUrl;
    header("Location: " . \$baseUrl . "public/");
    exit();
}

function redirectBasedOnRole() {
    global \$baseUrl;

    if (!isLoggedIn()) {
        header("Location: " . \$baseUrl . "auth/login/");
        exit();
    }

    \$role = \$_SESSION['user']['role'] ?? 'customer';

    if (\$role === 'admin') {
        header("Location: " . \$baseUrl . "admin/dashboard.php");
    } else {
        header("Location: " . \$baseUrl . "public/");
    }
    exit();
}

function updateUserRole(\$userId, \$newRole) {
    \$conn = getDatabaseConnection();
    
    if (!in_array(\$newRole, ['admin', 'customer'])) {
        return false;
    }

    \$stmt = \$conn->prepare("UPDATE Users SET role = ? WHERE user_id = ?");
    \$stmt->bind_param("si", \$newRole, \$userId);
    \$stmt->execute();
    
    if (\$stmt->affected_rows > 0) {
        return true;
    }
    return false;
}

function checkSessionTimeout() {
    if (!isLoggedIn()) {
        return;
    }

    \$user = getUser();
    
    if (\$user['role'] === 'admin' && isset(\$_SESSION['last_activity'])) {
        \$inactiveTime = time() - \$_SESSION['last_activity'];

        if (\$inactiveTime > SESSION_TIMEOUT_ADMIN) {
            logout();
        }
    }
    
    \$_SESSION['last_activity'] = time();
}

function usernameExists(\$username) {
    \$conn = getDatabaseConnection();
    \$stmt = \$conn->prepare("SELECT user_id FROM Users WHERE username = ?");
    \$stmt->bind_param("s", \$username);
    \$stmt->execute();
    \$stmt->store_result();
    return \$stmt->num_rows > 0;
}
EOT;

    file_put_contents(AUTH_FILE, $authContent);
}

function updateHtaccess() {
    if (!file_exists(HTACCESS_FILE)) {
        return;
    }
    
    $htaccess = file_get_contents(HTACCESS_FILE);
    $replacements = [
        'RewriteBase /' => 'RewriteBase '.parse_url($_SESSION['site_url'], PHP_URL_PATH),
    ];
    $htaccess = str_replace(array_keys($replacements), array_values($replacements), $htaccess);
    file_put_contents(HTACCESS_FILE, $htaccess);
}

function initializeDatabase() {
    $conn = new mysqli($_SESSION['db_host'], $_SESSION['db_user'], $_SESSION['db_pass'], $_SESSION['db_name']);
    
    $schema_file = ROOT_DIR.'/assets/database/schema.sql';
    if (file_exists($schema_file)) {
        $migration = file_get_contents($schema_file);
        $conn->multi_query($migration);
    } else {
        $default_schema = getDefaultSchema();
        $conn->multi_query($default_schema);
    }
    
    $conn->close();
}

function getDefaultSchema() {
    return <<<SQL
CREATE TABLE IF NOT EXISTS Users (
    user_id INT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    profile_image LONGBLOB,
    social_id VARCHAR(255),
    social_provider VARCHAR(50),
    is_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS UserActivities (
    activity_id INT PRIMARY KEY,
    user_id INT NOT NULL,
    activity_type VARCHAR(50) NOT NULL,
    activity_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id)
);

CREATE TABLE IF NOT EXISTS Genres (
    genre_id INT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    image_url VARCHAR(255),
    image_data LONGBLOB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS Games (
    game_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    actual_price DECIMAL(10,2) NOT NULL,
    discounted_price DECIMAL(10,2),
    genre_id INT,
    platform VARCHAR(50),
    cover_image VARCHAR(255),
    release_date DATE,
    developer VARCHAR(100),
    publisher VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (genre_id) REFERENCES Genres(genre_id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS GameImages (
    image_id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT,
    image_url VARCHAR(255),
    image_data LONGBLOB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (game_id) REFERENCES Games(game_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS Inventory (
    inventory_id INT PRIMARY KEY,
    game_id INT,
    stock_quantity INT DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (game_id) REFERENCES Games(game_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS Orders (
    order_id INT PRIMARY KEY,
    user_id INT,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(10,2),
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES Users(user_id)
);

CREATE TABLE IF NOT EXISTS OrderItems (
    order_item_id INT PRIMARY KEY,
    order_id INT,
    game_id INT,
    quantity INT,
    price_at_purchase DECIMAL(10,2),
    FOREIGN KEY (order_id) REFERENCES Orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES Games(game_id)
);

CREATE TABLE IF NOT EXISTS Payments (
    payment_id INT PRIMARY KEY,
    order_id INT,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    payment_method VARCHAR(50),
    FOREIGN KEY (order_id) REFERENCES Orders(order_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS PaymentMethods (
    id INT PRIMARY KEY,
    user_id INT NOT NULL,
    card_number VARCHAR(4) NOT NULL,
    expiry_date VARCHAR(5) NOT NULL,
    cvv VARCHAR(4) NOT NULL,
    card_name VARCHAR(100) NOT NULL,
    is_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS BillingHistory (
    id INT PRIMARY KEY,
    user_id INT NOT NULL,
    invoice_number VARCHAR(20) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS Cart (
    cart_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    game_id INT NOT NULL,
    quantity INT DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES Games(game_id) ON DELETE CASCADE
);
SQL;
}

function createAdminUser() {
    $conn = new mysqli($_SESSION['db_host'], $_SESSION['db_user'], $_SESSION['db_pass'], $_SESSION['db_name']);
    
    try {
        // Check if username already exists
        if (usernameExists($_SESSION['admin_username'])) {
            throw new Exception("Username already exists");
        }

        $stmt = $conn->prepare("INSERT INTO Users (username, email, password, role, profile_image, first_name, last_name, is_verified) VALUES (?, ?, ?, 'admin', ?, '', '', 1)");
        
        $null = null;
        $stmt->bind_param("sssb", 
            $_SESSION['admin_username'], 
            $_SESSION['admin_email'], 
            $_SESSION['admin_password'], 
            $null
        );
        
        if (isset($_SESSION['admin_profile_image_data'])) {
            $stmt->send_long_data(3, $_SESSION['admin_profile_image_data']);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Error creating admin user: " . $stmt->error);
        }
        
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        $conn->close();
        throw $e;
    }
    
    $conn->close();
}

function usernameExists($username) {
    $conn = new mysqli($_SESSION['db_host'], $_SESSION['db_user'], $_SESSION['db_pass'], $_SESSION['db_name']);
    $stmt = $conn->prepare("SELECT user_id FROM Users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    $conn->close();
    return $exists;
}

function checkRequirements() {
    $requirements = [
        'PHP 7.4+' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'MySQLi Extension' => extension_loaded('mysqli'),
        'GD Library' => extension_loaded('gd'),
        'JSON Support' => extension_loaded('json'),
        'File Uploads' => ini_get('file_uploads'),
        'PDO Extension' => extension_loaded('pdo'),
        'cURL Extension' => extension_loaded('curl'),
        'assets/directory writable' => is_writable(ROOT_DIR.'/assets'),
        'profile images directory writable' => is_writable(ROOT_DIR.'/assets/img/profile') || is_writable(ROOT_DIR.'/assets/img'),
    ];
    
    return $requirements;
}

$requirements = checkRequirements();
$allRequirementsMet = !in_array(false, $requirements, true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Wizard - Gaming Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .step {
            text-align: center;
            flex: 1;
            position: relative;
        }
        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-weight: bold;
        }
        .step.active .step-number {
            background: #0d6efd;
            color: white;
        }
        .step.completed .step-number {
            background: #198754;
            color: white;
        }
        .step-line {
            position: absolute;
            top: 20px;
            left: 50%;
            right: -50%;
            height: 2px;
            background: #e9ecef;
            z-index: -1;
        }
        .step:last-child .step-line {
            display: none;
        }
        .requirement-list {
            list-style: none;
            padding: 0;
        }
        .requirement-list li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .requirement-list li:before {
            content: "✓";
            color: #198754;
            margin-right: 10px;
        }
        .requirement-list li.fail:before {
            content: "✗";
            color: #dc3545;
        }
        .preview-image {
            max-width: 150px;
            max-height: 150px;
            margin-top: 10px;
            display: none;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">Gaming Store Setup Wizard</h3>
                    </div>
                    <div class="card-body">
                        <!-- Step Indicator -->
                        <div class="step-indicator">
                            <div class="step <?= $step >= 1 ? 'completed' : ($step == 1 ? 'active' : '') ?>">
                                <div class="step-number">1</div>
                                <div class="step-title">Requirements</div>
                                <div class="step-line"></div>
                            </div>
                            <div class="step <?= $step >= 2 ? 'completed' : ($step == 2 ? 'active' : '') ?>">
                                <div class="step-number">2</div>
                                <div class="step-title">Database</div>
                                <div class="step-line"></div>
                            </div>
                            <div class="step <?= $step >= 3 ? 'completed' : ($step == 3 ? 'active' : '') ?>">
                                <div class="step-number">3</div>
                                <div class="step-title">Website</div>
                                <div class="step-line"></div>
                            </div>
                            <div class="step <?= $step >= 4 ? 'completed' : ($step == 4 ? 'active' : '') ?>">
                                <div class="step-number">4</div>
                                <div class="step-title">Admin</div>
                            </div>
                        </div>

                        <!-- Step 1: Requirements Check -->
                        <?php if ($step == 1): ?>
                            <h4 class="mb-4">System Requirements Check</h4>
                            <ul class="requirement-list">
                                <?php foreach ($requirements as $name => $met): ?>
                                    <li class="<?= $met ? '' : 'fail' ?>"><?= $name ?></li>
                                <?php endforeach; ?>
                            </ul>
                            
                            <?php if ($allRequirementsMet): ?>
                                <div class="alert alert-success mt-4">
                                    All system requirements are met. You can proceed with the installation.
                                </div>
                                <div class="text-end mt-4">
                                    <a href="?step=2" class="btn btn-primary">Continue</a>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-danger mt-4">
                                    Some system requirements are not met. Please contact your hosting provider to resolve these issues before proceeding.
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <!-- Step 2: Database Setup -->
                        <?php if ($step == 2): ?>
                            <h4 class="mb-4">Database Configuration</h4>
                            <?php if (isset($_SESSION['error_message'])): ?>
                                <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error_message']) ?></div>
                                <?php unset($_SESSION['error_message']); ?>
                            <?php endif; ?>
                            <form method="post" action="?step=2">
                                <div class="mb-3">
                                    <label for="db_host" class="form-label">Database Host</label>
                                    <input type="text" class="form-control" id="db_host" name="db_host" value="localhost" required>
                                </div>
                                <div class="mb-3">
                                    <label for="db_name" class="form-label">Database Name</label>
                                    <input type="text" class="form-control" id="db_name" name="db_name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="db_user" class="form-label">Database Username</label>
                                    <input type="text" class="form-control" id="db_user" name="db_user" required>
                                </div>
                                <div class="mb-3">
                                    <label for="db_pass" class="form-label">Database Password</label>
                                    <input type="password" class="form-control" id="db_pass" name="db_pass">
                                </div>
                                <div class="d-flex justify-content-between mt-4">
                                    <a href="?step=1" class="btn btn-secondary">Back</a>
                                    <button type="submit" class="btn btn-primary">Test Connection</button>
                                </div>
                            </form>
                        <?php endif; ?>

                        <!-- Step 3: Website Setup -->
                        <?php if ($step == 3): ?>
                            <h4 class="mb-4">Website Information</h4>
                            <form method="post" action="?step=3">
                                <div class="mb-3">
                                    <label for="site_url" class="form-label">Website URL</label>
                                    <input type="url" class="form-control" id="site_url" name="site_url" value="<?= isset($_SERVER['HTTPS']) ? 'https://' : 'http://' ?><?= $_SERVER['HTTP_HOST'] ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="site_name" class="form-label">Website Name</label>
                                    <input type="text" class="form-control" id="site_name" name="site_name" value="Gaming Store" required>
                                </div>
                                <div class="d-flex justify-content-between mt-4">
                                    <a href="?step=2" class="btn btn-secondary">Back</a>
                                    <button type="submit" class="btn btn-primary">Continue</button>
                                </div>
                            </form>
                        <?php endif; ?>

                        <!-- Step 4: Admin Setup -->
                        <?php if ($step == 4): ?>
                            <h4 class="mb-4">Create Admin Account</h4>
                            <?php if (isset($_SESSION['error_message'])): ?>
                                <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error_message']) ?></div>
                                <?php unset($_SESSION['error_message']); ?>
                            <?php endif; ?>
                            <form method="post" action="?step=4" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
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
                                <div class="mb-3">
                                    <label for="profile_image" class="form-label">Profile Image (Optional)</label>
                                    <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*">
                                    <img id="imagePreview" class="preview-image" src="#" alt="Preview">
                                </div>
                                <div class="d-flex justify-content-between mt-4">
                                    <a href="?step=3" class="btn btn-secondary">Back</a>
                                    <button type="submit" class="btn btn-success">Complete Setup</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Image preview functionality
        document.getElementById('profile_image').addEventListener('change', function(e) {
            const preview = document.getElementById('imagePreview');
            const file = e.target.files[0];
            
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        });
    </script>
</body>
</html>