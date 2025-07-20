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

$conn = getDatabaseConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add Genre
    if (isset($_POST['add_genre'])) {
        $name = $_POST['name'];
        $description = $_POST['description'];
        
        // Handle image upload
        $image_url = '';
        if (isset($_FILES['image_url']) && $_FILES['image_url']['error'] === UPLOAD_ERR_OK) {
            $target_dir = "../../assets/img/genres/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }
            
            $file_ext = pathinfo($_FILES['image_url']['name'], PATHINFO_EXTENSION);
            $image_url = 'genre_' . time() . '.' . $file_ext;
            $target_file = $target_dir . $image_url;
            
            if (move_uploaded_file($_FILES['image_url']['tmp_name'], $target_file)) {
                // Image uploaded successfully
            } else {
                $_SESSION['error_message'] = "Error uploading image.";
            }
        }
        
        // Insert into database
        $stmt = $conn->prepare("INSERT INTO Genres (name, description, image_url) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $description, $image_url);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Genre added successfully!";
            // Log activity
            $activity_stmt = $conn->prepare("INSERT INTO UserActivities (user_id, activity_type) VALUES (?, 'add_genre')");
            $activity_stmt->bind_param("i", $currentUser['user_id']);
            $activity_stmt->execute();
        } else {
            $_SESSION['error_message'] = "Error adding genre: " . $conn->error;
        }
    }
    
    // Edit Genre
    if (isset($_POST['edit_genre'])) {
        $genre_id = $_POST['genre_id'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        
        // Get current image
        $current_image = '';
        $stmt = $conn->prepare("SELECT image_url FROM Genres WHERE genre_id = ?");
        $stmt->bind_param("i", $genre_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $current_image = $row['image_url'];
        }
        
        // Handle new image upload
        $image_url = $current_image;
        if (isset($_FILES['image_url']) && $_FILES['image_url']['error'] === UPLOAD_ERR_OK) {
            $target_dir = "../../assets/img/genres/";
            $file_ext = pathinfo($_FILES['image_url']['name'], PATHINFO_EXTENSION);
            $image_url = 'genre_' . time() . '.' . $file_ext;
            $target_file = $target_dir . $image_url;
            
            if (move_uploaded_file($_FILES['image_url']['tmp_name'], $target_file)) {
                // Delete old image if it exists
                if ($current_image && file_exists($target_dir . $current_image)) {
                    unlink($target_dir . $current_image);
                }
            } else {
                $_SESSION['error_message'] = "Error uploading new image.";
                $image_url = $current_image;
            }
        }
        
        // Update database
        $stmt = $conn->prepare("UPDATE Genres SET name = ?, description = ?, image_url = ? WHERE genre_id = ?");
        $stmt->bind_param("sssi", $name, $description, $image_url, $genre_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Genre updated successfully!";
            // Log activity
            $activity_stmt = $conn->prepare("INSERT INTO UserActivities (user_id, activity_type) VALUES (?, 'edit_genre')");
            $activity_stmt->bind_param("i", $currentUser['user_id']);
            $activity_stmt->execute();
        } else {
            $_SESSION['error_message'] = "Error updating genre: " . $conn->error;
        }
    }
    
    // Add Game
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $title = $_POST['title'];
        $description = $_POST['description'];
        $actual_price = $_POST['actual_price'];
        $discounted_price = $_POST['discounted_price'] ?? null;
        $genre_id = $_POST['genre_id'];
        $platform = $_POST['platform'];
        $stock_quantity = $_POST['stock_quantity'];
        $release_date = $_POST['release_date'];
        $developer = $_POST['developer'];
        $publisher = $_POST['publisher'];
        
        // Handle cover image upload
        $cover_image = '';
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            $target_dir = "../../assets/img/games/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }
            
            $file_ext = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
            $cover_image = 'cover_' . time() . '.' . $file_ext;
            $target_file = $target_dir . $cover_image;
            
            if (!move_uploaded_file($_FILES['cover_image']['tmp_name'], $target_file)) {
                $_SESSION['error_message'] = "Error uploading cover image.";
                $cover_image = '';
            }
        }
        
        // Insert game into database
        $stmt = $conn->prepare("INSERT INTO Games (title, description, actual_price, discounted_price, genre_id, platform, cover_image,release_date,developer,publisher) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssddiss", $title, $description, $actual_price, $discounted_price, $genre_id, $platform, $cover_image, $release_date,$developer,$publisher);
        

        if ($stmt->execute()) {
            $game_id = $conn->insert_id;
            
            // Handle game images upload
            if (isset($_FILES['game_images']) && !empty($_FILES['game_images']['name'][0])) {
    $target_dir = "../../assets/img/games/";
    $upload_errors = [];
    
    foreach ($_FILES['game_images']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['game_images']['error'][$key] === UPLOAD_ERR_OK) {
            // Validate file type
            $file_ext = strtolower(pathinfo($_FILES['game_images']['name'][$key], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (!in_array($file_ext, $allowed_extensions)) {
                $upload_errors[] = "Invalid file type for: " . $_FILES['game_images']['name'][$key];
                continue;
            }
            
            $image_name = 'game_' . time() . '_' . $key . '.' . $file_ext;
            $target_file = $target_dir . $image_name;
            
            if (move_uploaded_file($tmp_name, $target_file)) {
                // Insert image into GameImages table
                $img_stmt = $conn->prepare("INSERT INTO GameImages (game_id, image_url) VALUES (?, ?)");
                $img_stmt->bind_param("is", $game_id, $image_name);
                
                if (!$img_stmt->execute()) {
                    $upload_errors[] = "Failed to save image record for: " . $_FILES['game_images']['name'][$key] . " - " . $conn->error;
                    // Delete the uploaded file if DB insert failed
                    if (file_exists($target_file)) {
                        unlink($target_file);
                    }
                }
            } else {
                $upload_errors[] = "Failed to upload: " . $_FILES['game_images']['name'][$key];
            }
        } elseif ($_FILES['game_images']['error'][$key] !== UPLOAD_ERR_NO_FILE) {
            $upload_errors[] = "Error with file: " . $_FILES['game_images']['name'][$key] . " - Error code: " . $_FILES['game_images']['error'][$key];
        }
    }
    
    if (!empty($upload_errors)) {
        $_SESSION['error_message'] = implode("<br>", $upload_errors);
    }
}
            
            // Add to inventory
            $inv_stmt = $conn->prepare("INSERT INTO Inventory (game_id, stock_quantity) VALUES (?, ?)");
            $inv_stmt->bind_param("ii", $game_id, $stock_quantity);
            $inv_stmt->execute();
            
            $_SESSION['success_message'] = "Game added successfully!";
            // Log activity
            $activity_stmt = $conn->prepare("INSERT INTO UserActivities (user_id, activity_type) VALUES (?, 'add_game')");
            $activity_stmt->bind_param("i", $currentUser['user_id']);
            $activity_stmt->execute();
        } else {
            $_SESSION['error_message'] = "Error adding game: " . $conn->error;
        }
    }
    
    // Edit Game
    if (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $game_id = $_POST['game_id'];
        $title = $_POST['title'];
        $description = $_POST['description'];
        $actual_price = $_POST['actual_price'];
        $discounted_price = $_POST['discounted_price'] ?? null;
        $genre_id = $_POST['genre_id'];
        $platform = $_POST['platform'];
        $stock_quantity = $_POST['stock_quantity'];
        $release_date = $_POST['release_date'];
        $developer = $_POST['developer'];
        $publisher = $_POST['publisher'];
        
        // Get current cover image
        $current_cover = '';
        $stmt = $conn->prepare("SELECT cover_image FROM Games WHERE game_id = ?");
        $stmt->bind_param("i", $game_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $current_cover = $row['cover_image'];
        }
        
        // Handle new cover image upload
        $cover_image = $current_cover;
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            $target_dir = "../../assets/img/games/";
            $file_ext = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
            $cover_image = 'cover_' . time() . '.' . $file_ext;
            $target_file = $target_dir . $cover_image;
            
            if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $target_file)) {
                // Delete old cover image if it exists
                if ($current_cover && file_exists($target_dir . $current_cover)) {
                    unlink($target_dir . $current_cover);
                }
            } else {
                $_SESSION['error_message'] = "Error uploading new cover image.";
                $cover_image = $current_cover;
            }
        }
        
        // Update game in database
        $stmt = $conn->prepare("UPDATE Games SET title = ?, description = ?, actual_price = ?, discounted_price = ?, genre_id = ?, platform = ?, cover_image = ?, release_date = ?,developer = ?,publisher = ?, WHERE game_id = ?");
        $stmt->bind_param("ssddissi", $title, $description, $actual_price, $discounted_price, $genre_id, $platform, $cover_image, $game_id, $release_date,$developer,$publisher);
        
        if ($stmt->execute()) {
            // Handle additional game images upload
            if (isset($_FILES['game_images']) && !empty($_FILES['game_images']['name'][0])) {
    $target_dir = "../../assets/img/games/";
    $upload_errors = [];
    
    foreach ($_FILES['game_images']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['game_images']['error'][$key] === UPLOAD_ERR_OK) {
            // Validate file type
            $file_ext = strtolower(pathinfo($_FILES['game_images']['name'][$key], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (!in_array($file_ext, $allowed_extensions)) {
                $upload_errors[] = "Invalid file type for: " . $_FILES['game_images']['name'][$key];
                continue;
            }
            
            $image_name = 'game_' . time() . '_' . $key . '.' . $file_ext;
            $target_file = $target_dir . $image_name;
            
            if (move_uploaded_file($tmp_name, $target_file)) {
                // Insert image into GameImages table
                $img_stmt = $conn->prepare("INSERT INTO GameImages (game_id, image_url) VALUES (?, ?)");
                $img_stmt->bind_param("is", $game_id, $image_name);
                
                if (!$img_stmt->execute()) {
                    $upload_errors[] = "Failed to save image record for: " . $_FILES['game_images']['name'][$key] . " - " . $conn->error;
                    // Delete the uploaded file if DB insert failed
                    if (file_exists($target_file)) {
                        unlink($target_file);
                    }
                }
            } else {
                $upload_errors[] = "Failed to upload: " . $_FILES['game_images']['name'][$key];
            }
        } elseif ($_FILES['game_images']['error'][$key] !== UPLOAD_ERR_NO_FILE) {
            $upload_errors[] = "Error with file: " . $_FILES['game_images']['name'][$key] . " - Error code: " . $_FILES['game_images']['error'][$key];
        }
    }
    
    if (!empty($upload_errors)) {
        $_SESSION['error_message'] = implode("<br>", $upload_errors);
    }
}
            
            // Update inventory
            $inv_stmt = $conn->prepare("UPDATE Inventory SET stock_quantity = ? WHERE game_id = ?");
            $inv_stmt->bind_param("ii", $stock_quantity, $game_id);
            $inv_stmt->execute();
            
            $_SESSION['success_message'] = "Game updated successfully!";
            // Log activity
            $activity_stmt = $conn->prepare("INSERT INTO UserActivities (user_id, activity_type) VALUES (?, 'edit_game')");
            $activity_stmt->bind_param("i", $currentUser['user_id']);
            $activity_stmt->execute();
        } else {
            $_SESSION['error_message'] = "Error updating game: " . $conn->error;
        }
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle GET requests (delete operations)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Delete Genre
    if (isset($_GET['delete_genre'])) {
        $genre_id = $_GET['delete_genre'];
        
        // Check if any games are using this genre
        $check_stmt = $conn->prepare("SELECT COUNT(*) FROM Games WHERE genre_id = ?");
        $check_stmt->bind_param("i", $genre_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $count = $check_result->fetch_row()[0];
        
        if ($count > 0) {
            $_SESSION['error_message'] = "Cannot delete genre because it's being used by $count game(s).";
        } else {
            // Get image path before deleting
            $img_stmt = $conn->prepare("SELECT image_url FROM Genres WHERE genre_id = ?");
            $img_stmt->bind_param("i", $genre_id);
            $img_stmt->execute();
            $img_result = $img_stmt->get_result();
            $image_url = $img_result->fetch_row()[0];
            
            // Delete genre
            $stmt = $conn->prepare("DELETE FROM Genres WHERE genre_id = ?");
            $stmt->bind_param("i", $genre_id);
            
            if ($stmt->execute()) {
                // Delete image file if it exists
                if ($image_url && file_exists("../../assets/img/genres/" . $image_url)) {
                    unlink("../../assets/img/genres/" . $image_url);
                }
                
                $_SESSION['success_message'] = "Genre deleted successfully!";
                // Log activity
                $activity_stmt = $conn->prepare("INSERT INTO UserActivities (user_id, activity_type) VALUES (?, 'delete_genre')");
                $activity_stmt->bind_param("i", $currentUser['user_id']);
                $activity_stmt->execute();
            } else {
                $_SESSION['error_message'] = "Error deleting genre: " . $conn->error;
            }
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    
    // Delete Game
    if (isset($_GET['delete_game_id'])) {
        $game_id = $_GET['delete_game_id'];
        
        // Get cover image and game images before deleting
        $cover_stmt = $conn->prepare("SELECT cover_image FROM Games WHERE game_id = ?");
        $cover_stmt->bind_param("i", $game_id);
        $cover_stmt->execute();
        $cover_result = $cover_stmt->get_result();
        $cover_image = $cover_result->fetch_row()[0];
        
        $images_stmt = $conn->prepare("SELECT image_url FROM GameImages WHERE game_id = ?");
        $images_stmt->bind_param("i", $game_id);
        $images_stmt->execute();
        $images_result = $images_stmt->get_result();
        $game_images = $images_result->fetch_all(MYSQLI_ASSOC);
        
        // Delete game
        $stmt = $conn->prepare("DELETE FROM Games WHERE game_id = ?");
        $stmt->bind_param("i", $game_id);
        
        if ($stmt->execute()) {
            // Delete cover image if it exists
            if ($cover_image && file_exists("../../assets/img/games/" . $cover_image)) {
                unlink("../../assets/img/games/" . $cover_image);
            }
            
            // Delete game images if they exist
            foreach ($game_images as $image) {
                if ($image['image_url'] && file_exists("../../assets/img/games/" . $image['image_url'])) {
                    unlink("../../assets/img/games/" . $image['image_url']);
                }
            }
            
            $_SESSION['success_message'] = "Game deleted successfully!";
            // Log activity
            $activity_stmt = $conn->prepare("INSERT INTO UserActivities (user_id, activity_type) VALUES (?, 'delete_game')");
            $activity_stmt->bind_param("i", $currentUser['user_id']);
            $activity_stmt->execute();
        } else {
            $_SESSION['error_message'] = "Error deleting game: " . $conn->error;
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Retrieve genres for dropdowns
$genres = $conn->query("SELECT * FROM Genres ORDER BY name");

// Retrieve games for dropdowns
$games = $conn->query("SELECT * FROM Games ORDER BY title");
?>

<!DOCTYPE html>
<html lang="en">

<head>
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Admin Games - Duarcade</title>
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
      .alert {
         margin-top: 20px;
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
                  <a href="../../admin/user/" class="sidebar__link">
                     <i class="ri-user-line"></i>
                     <span>Users</span>
                  </a>
               </div>
            </div>

            <div>
               <h3 class="sidebar__title">Store</h3>

               <div class="sidebar__list">
                  <a href="../../admin/game/" class="sidebar__link active-link">
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
      <div class="d-flex justify-content-between align-items-center my-4">
         <h1>Game Management</h1>
         <div class="dropdown">
            <button class="btn btn-primary dropdown-toggle" type="button" id="formDropdown" data-bs-toggle="dropdown" aria-expanded="false">
               Select Action
            </button>
            <ul class="dropdown-menu" aria-labelledby="formDropdown">
               <li><a class="dropdown-item" href="#" onclick="showForm('add-genre')">Add Genre</a></li>
               <li><a class="dropdown-item" href="#" onclick="showForm('edit-genre')">Edit Genre</a></li>
               <li><a class="dropdown-item" href="#" onclick="showForm('delete-genre')">Delete Genre</a></li>
               <li><a class="dropdown-item" href="#" onclick="showForm('add-game')">Add Game</a></li>
               <li><a class="dropdown-item" href="#" onclick="showForm('edit-game')">Edit Game</a></li>
               <li><a class="dropdown-item" href="#" onclick="showForm('delete-game')">Delete Game</a></li>
            </ul>
         </div>
      </div>

      <!-- Display success/error messages -->
      <?php if (isset($_SESSION['success_message'])): ?>
         <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['success_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
         </div>
         <?php unset($_SESSION['success_message']); ?>
      <?php endif; ?>

      <?php if (isset($_SESSION['error_message'])): ?>
         <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['error_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
         </div>
         <?php unset($_SESSION['error_message']); ?>
      <?php endif; ?>
      <!-- FORMS -->
      <div id="formContainer">
         <!-- Add Genre Form -->
         <form id="add-genre" class="d-none" method="POST" enctype="multipart/form-data" action="">
            <input type="hidden" name="add_genre" value="1">
            <h4>Add Genre</h4>
            <div class="mb-3">
               <label class="form-label">Name</label>
               <input type="text" class="form-control" name="name" required>
            </div>
            <div class="mb-3">
               <label class="form-label">Description</label>
               <textarea class="form-control" name="description" required></textarea>
            </div>
            <div class="mb-3">
               <label class="form-label">Image</label>
               <input type="file" class="form-control" name="image_url" accept="image/*" required onchange="previewImage(this)">
               <img id="imagePreview" src="#" alt="Image Preview" style="display: none; width: 100px; height: 100px; margin-top: 10px;">
            </div>
            <button type="submit" class="btn btn-success">Submit</button>
         </form>

         <!-- Edit Genre Form -->
         <form id="edit-genre" class="d-none" method="POST" enctype="multipart/form-data" action="">
            <input type="hidden" name="edit_genre" value="1">
            <h4>Edit Genre</h4>
            <div class="mb-3">
               <label class="form-label">Select Genre</label>
               <select class="form-select" name="genre_id" id="genre-select" required>
                  <option value="">Select a genre</option>
                  <?php
                  $genres = $conn->query("SELECT * FROM Genres");
                  while ($genre = $genres->fetch_assoc()) {
                     echo "<option value='{$genre['genre_id']}' data-image='{$genre['image_url']}'>{$genre['name']}</option>";
                  }
                  ?>
               </select>
            </div>
            <div class="mb-3">
               <label class="form-label">New Name</label>
               <input type="text" class="form-control" name="name" required>
            </div>
            <div class="mb-3">
               <label class="form-label">New Description</label>
               <textarea class="form-control" name="description" required></textarea>
            </div>
            <div class="mb-3">
               <label class="form-label">Current Image</label>
               <div id="currentGenreImage" class="current-image-container">
                  <p>Select a genre to view its current image</p>
               </div>
               <label class="form-label">New Image (Leave empty to keep current)</label>
               <input type="file" class="form-control" name="image_url" accept="image/*" onchange="previewEditImage(this)">
               <img id="editImagePreview" src="#" alt="Image Preview" style="display: none; width: 100px; height: 100px; margin-top: 10px;">
            </div>
            <button type="submit" class="btn btn-warning">Update</button>
         </form>

         <!-- Delete Genre Form -->
         <form id="delete-genre" class="d-none" method="GET" action="">
            <h4>Delete Genre</h4>
            <div class="mb-3">
               <label class="form-label">Select Genre</label>
               <select class="form-select" name="delete_genre" required>
                  <option value="">Select a genre</option>
                  <?php
                  $genres = $conn->query("SELECT * FROM Genres");
                  while ($genre = $genres->fetch_assoc()) {
                     echo "<option value='{$genre['genre_id']}'>{$genre['name']}</option>";
                  }
                  ?>
               </select>
            </div>
            <button type="submit" class="btn btn-danger">Delete</button>
         </form>

         <!-- Add Game Form -->
         <form id="add-game" class="d-none" method="POST" enctype="multipart/form-data" action="">
            <input type="hidden" name="action" value="add">
            <h4>Add Game</h4>
            <div class="mb-3">
               <label class="form-label">Title</label>
               <input type="text" class="form-control" name="title" required>
            </div>
            <div class="mb-3">
               <label class="form-label">Description</label>
               <textarea class="form-control" name="description" required></textarea>
            </div>
            <div class="row">
               <div class="col-md-6">
                  <div class="mb-3">
                     <label class="form-label">Actual Price</label>
                     <input type="number" step="0.01" class="form-control" name="actual_price" required>
                  </div>
               </div>
               <div class="col-md-6">
                  <div class="mb-3">
                     <label class="form-label">Discounted Price</label>
                     <input type="number" step="0.01" class="form-control" name="discounted_price">
                  </div>
               </div>
            </div>
            <div class="mb-3">
               <label class="form-label">Genre</label>
               <select class="form-select" name="genre_id" required>
                  <option value="">Select a genre</option>
                  <?php
                  $genres = $conn->query("SELECT * FROM Genres");
                  while ($genre = $genres->fetch_assoc()) {
                     echo "<option value='{$genre['genre_id']}'>{$genre['name']}</option>";
                  }
                  ?>
               </select>
            </div>
            <div class="mb-3">
               <label class="form-label">Platform</label>
               <input type="text" class="form-control" name="platform" required>
            </div>
            <div class="mb-3">
               <label class="form-label">Cover Image</label>
               <input type="file" class="form-control" name="cover_image" accept="image/*" required onchange="previewCoverImage(this)">
               <img id="coverImagePreview" src="#" alt="Cover Image Preview" style="display: none; width: 100px; height: 100px; margin-top: 10px;">
            </div>
            <div class="mb-3">
               <label class="form-label">Game Images (Multiple)</label>
               <input type="file" class="form-control" name="game_images[]" accept="image/*" multiple required onchange="previewGameImages(this)">
               <div id="gameImagesPreview" style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px;"></div>
            </div>
            <div class="mb-3">
               <label class="form-label">Initial Stock Quantity</label>
               <input type="number" class="form-control" name="stock_quantity" min="0" value="0" required>
            </div>
            <div class="col-md-4">
            <div class="mb-3">
                <label class="form-label">Release Date</label>
                <input type="date" class="form-control" name="release_date" required>
            </div>
        </div>
        <div class="col-md-4">
            <div class="mb-3">
                <label class="form-label">Developer</label>
                <input type="text" class="form-control" name="developer" required>
            </div>
        </div>
        <div class="col-md-4">
            <div class="mb-3">
                <label class="form-label">Publisher</label>
                <input type="text" class="form-control" name="publisher" required>
            </div>
        </div>
            <button type="submit" class="btn btn-success">Add Game</button>
         </form>

         <!-- Edit Game Form -->
         <form id="edit-game" class="d-none" method="POST" enctype="multipart/form-data" action="">
            <input type="hidden" name="action" value="edit">
            <h4>Edit Game</h4>
            <div class="mb-3">
               <label class="form-label">Select Game</label>
               <select class="form-select" name="game_id" id="game-select" required>
                  <option value="">Select a game</option>
                  <?php
                  $games = $conn->query("SELECT * FROM Games");
                  while ($game = $games->fetch_assoc()) {
                     echo "<option value='{$game['game_id']}' data-cover='{$game['cover_image']}'>{$game['title']}</option>";
                  }
                  ?>
               </select>
            </div>
            <div class="mb-3">
               <label class="form-label">Current Cover Image</label>
               <div id="currentCoverImage" class="current-image-container">
                  <p>Select a game to view its current cover image</p>
               </div>
               <label class="form-label">New Cover Image (Leave empty to keep current)</label>
               <input type="file" class="form-control" name="cover_image" accept="image/*" onchange="previewEditCoverImage(this)">
               <img id="editCoverImagePreview" src="#" alt="Cover Image Preview" style="display: none; width: 100px; height: 100px; margin-top: 10px;">
            </div>
            <div class="mb-3">
               <label class="form-label">Current Game Images</label>
               <div id="currentGameImages" class="current-image-container">
                  <p>Select a game to view its current images</p>
               </div>
               <label class="form-label">Additional Game Images</label>
               <input type="file" class="form-control" name="game_images[]" accept="image/*" multiple onchange="previewEditGameImages(this)">
               <div id="editGameImagesPreview" style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px;"></div>
            </div>
            <div class="mb-3">
               <label class="form-label">New Title</label>
               <input type="text" class="form-control" name="title" required>
            </div>
            <div class="mb-3">
               <label class="form-label">New Description</label>
               <textarea class="form-control" name="description" required></textarea>
            </div>
            <div class="row">
               <div class="col-md-6">
                  <div class="mb-3">
                     <label class="form-label">New Actual Price</label>
                     <input type="number" step="0.01" class="form-control" name="actual_price" required>
                  </div>
               </div>
               <div class="col-md-6">
                  <div class="mb-3">
                     <label class="form-label">New Discounted Price</label>
                     <input type="number" step="0.01" class="form-control" name="discounted_price">
                  </div>
               </div>
            </div>
            <div class="mb-3">
               <label class="form-label">New Genre</label>
               <select class="form-select" name="genre_id" required>
                  <option value="">Select a genre</option>
                  <?php
                  $genres = $conn->query("SELECT * FROM Genres");
                  while ($genre = $genres->fetch_assoc()) {
                     echo "<option value='{$genre['genre_id']}'>{$genre['name']}</option>";
                  }
                  ?>
               </select>
            </div>
            <div class="mb-3">
               <label class="form-label">New Platform</label>
               <input type="text" class="form-control" name="platform" required>
            </div>
            <div class="mb-3">
               <label class="form-label">New Stock Quantity</label>
               <input type="number" class="form-control" name="stock_quantity" min="0" required>
            </div>
             <div class="col-md-4">
            <div class="mb-3">
                <label class="form-label">Release Date</label>
                <input type="date" class="form-control" name="release_date" required>
            </div>
        </div>
        <div class="col-md-4">
            <div class="mb-3">
                <label class="form-label">Developer</label>
                <input type="text" class="form-control" name="developer" required>
            </div>
        </div>
        <div class="col-md-4">
            <div class="mb-3">
                <label class="form-label">Publisher</label>
                <input type="text" class="form-control" name="publisher" required>
            </div>
        </div>
            <button type="submit" class="btn btn-warning">Update Game</button>
         </form>

         <!-- Delete Game Form -->
         <form id="delete-game" class="d-none" method="GET" action="">
            <h4>Delete Game</h4>
            <div class="mb-3">
               <label class="form-label">Select Game</label>
               <select class="form-select" name="delete_game_id" required>
                  <option value="">Select a game</option>
                  <?php
                  $games = $conn->query("SELECT * FROM Games");
                  while ($game = $games->fetch_assoc()) {
                     echo "<option value='{$game['game_id']}'>{$game['title']}</option>";
                  }
                  ?>
               </select>
            </div>
            <button type="submit" class="btn btn-danger">Delete</button>
         </form>
      </div>
   </main>

   <!--=============== MAIN JS ===============-->
   <script src="../../assets/js/admin.js"></script>
   <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-..." crossorigin="anonymous"></script>
   <script>
      // Show selected form and hide others
      function showForm(formId) {
         // Hide all forms
         const forms = document.querySelectorAll('#formContainer form');
         forms.forEach(form => form.classList.add('d-none'));

         // Show the selected form
         const selectedForm = document.getElementById(formId);
         if (selectedForm) {
            selectedForm.classList.remove('d-none');
            window.scrollTo({
               top: selectedForm.offsetTop - 80,
               behavior: 'smooth'
            });
         }
      }

      // Preview image for "Add Genre"
      function previewImage(input) {
         const preview = document.getElementById('imagePreview');
         preview.style.display = 'none';
         if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = e => {
               preview.src = e.target.result;
               preview.style.display = 'block';
            };
            reader.readAsDataURL(input.files[0]);
         }
      }

      // Preview image for "Edit Genre"
      function previewEditImage(input) {
         const preview = document.getElementById('editImagePreview');
         preview.style.display = 'none';
         if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = e => {
               preview.src = e.target.result;
               preview.style.display = 'block';
            };
            reader.readAsDataURL(input.files[0]);
         }
      }

      // Preview image for "Add Game"
      function previewCoverImage(input) {
         const preview = document.getElementById('coverImagePreview');
         preview.style.display = 'none';
         if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = e => {
               preview.src = e.target.result;
               preview.style.display = 'block';
            };
            reader.readAsDataURL(input.files[0]);
         }
      }

      function previewGameImages(input) {
         const container = document.getElementById('gameImagesPreview');
         container.innerHTML = '';
         if (input.files) {
            Array.from(input.files).forEach(file => {
               const reader = new FileReader();
               reader.onload = e => {
                  const img = document.createElement('img');
                  img.src = e.target.result;
                  img.style.width = '100px';
                  img.style.height = '100px';
                  container.appendChild(img);
               };
               reader.readAsDataURL(file);
            });
         }
      }

      // Preview image for "Edit Game"
      function previewEditCoverImage(input) {
         const preview = document.getElementById('editCoverImagePreview');
         preview.style.display = 'none';
         if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = e => {
               preview.src = e.target.result;
               preview.style.display = 'block';
            };
            reader.readAsDataURL(input.files[0]);
         }
      }

      function previewEditGameImages(input) {
         const container = document.getElementById('editGameImagesPreview');
         container.innerHTML = '';
         if (input.files) {
            Array.from(input.files).forEach(file => {
               const reader = new FileReader();
               reader.onload = e => {
                  const img = document.createElement('img');
                  img.src = e.target.result;
                  img.style.width = '100px';
                  img.style.height = '100px';
                  container.appendChild(img);
               };
               reader.readAsDataURL(file);
            });
         }
      }

      // Auto-display current genre image on selection
      document.addEventListener('DOMContentLoaded', () => {
         // For genre edit form
         const genreSelect = document.querySelector('#edit-genre select[name="genre_id"]');
         const currentGenreImage = document.getElementById('currentGenreImage');

         if (genreSelect) {
            genreSelect.addEventListener('change', function() {
               const selectedOption = this.options[this.selectedIndex];
               const imageUrl = selectedOption.getAttribute('data-image');
               currentGenreImage.innerHTML = imageUrl ?
                  `<img src="../../assets/img/genres/${imageUrl}" style="max-width: 200px; max-height: 200px;">` :
                  '<p>No image available for this genre</p>';
            });
         }

         // For game edit form
         const gameSelect = document.querySelector('#edit-game select[name="game_id"]');
         const currentCoverImage = document.getElementById('currentCoverImage');
         const currentGameImages = document.getElementById('currentGameImages');

         if (gameSelect) {
            gameSelect.addEventListener('change', function() {
               const gameId = this.value;
               
               // Get cover image
               const selectedOption = this.options[this.selectedIndex];
               const coverImageUrl = selectedOption.getAttribute('data-cover');
               currentCoverImage.innerHTML = coverImageUrl ?
                  `<img src="../../assets/img/games/${coverImageUrl}" style="max-width: 200px; max-height: 200px;">` :
                  '<p>No cover image available for this game</p>';
               
               // Get additional game images via AJAX
               if (gameId) {
                  fetch(`get_game_images.php?game_id=${gameId}`)
                     .then(response => response.json())
                     .then(data => {
                        if (data.length > 0) {
                           let imagesHTML = '<div style="display: flex; flex-wrap: wrap; gap: 10px;">';
                           data.forEach(image => {
                              imagesHTML += `<img src="../../assets/img/games/${image.image_url}" style="max-width: 100px; max-height: 100px;">`;
                           });
                           imagesHTML += '</div>';
                           currentGameImages.innerHTML = imagesHTML;
                        } else {
                           currentGameImages.innerHTML = '<p>No additional images available for this game</p>';
                        }
                     })
                     .catch(error => {
                        console.error('Error fetching game images:', error);
                        currentGameImages.innerHTML = '<p>Error loading additional images</p>';
                     });
               } else {
                  currentGameImages.innerHTML = '<p>Select a game to view its images</p>';
               }
            });
         }
      });
   </script>

</body>

</html>