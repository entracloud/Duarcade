<?php
require_once '../../assets/database/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ADD GAME
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $title = $_POST['title'];
        $description = $_POST['description'];
        $actual_price = $_POST['actual_price'];
        $discounted_price = $_POST['discounted_price'];
        $genre_id = $_POST['genre_id'];
        $platform = $_POST['platform'];
        $stock_quantity = $_POST['stock_quantity'];

        if (addGame($title, $description, $actual_price, $discounted_price, $genre_id, $platform, $coverImage, $gameImages, $stock_quantity)) {
            echo "<script>alert('Game added successfully!'); window.location.href='../../admin/game/';</script>";
        } else {
            echo "<script>alert('Failed to add game.'); window.history.back();</script>";
        }

    // EDIT GAME
    } elseif (isset($_POST['action']) && $_POST['action'] === 'edit' && isset($_POST['game_id'])) {
        $game_id = $_POST['game_id'];
        $title = $_POST['title'];
        $description = $_POST['description'];
        $actual_price = $_POST['actual_price'];
        $discounted_price = $_POST['discounted_price'];
        $genre_id = $_POST['genre_id'];
        $platform = $_POST['platform'];
        $stock_quantity = $_POST['stock_quantity'];

        if (editGame($game_id, $title, $description, $actual_price, $discounted_price, $genre_id, $platform, $newCoverImage, $newGameImages, $stock_quantity)) {
            echo "<script>alert('Game updated successfully!'); window.location.href='../../admin/game/';</script>";
        } else {
            echo "<script>alert('Failed to update game.'); window.history.back();</script>";
        }

    }
}

// DELETE GAME (via GET for admin panel link-based deletion)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['delete_game_id'])) {
    $gameId = $_GET['delete_game_id'];
    if (deleteGame($gameId)) {
        echo "<script>alert('Game deleted successfully!'); window.location.href='../../admin/game/';</script>";
    } else {
        echo "<script>alert('Failed to delete game.'); window.history.back();</script>";
    }
}
?>