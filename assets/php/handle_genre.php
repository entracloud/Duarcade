<?php
require_once '../../assets/database/auth.php';

if (isset($_POST['add_genre'])) {
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $image = uploadGenreImage('genre_image');

    if (addGenre($name, $desc, $image)) {
        echo "<script>alert('Genre added.'); window.location.href='../../admin/game/';</script>";
    } else {
        echo "<script>alert('Failed to add genre.');</script>";
    }
}

if (isset($_POST['edit_genre'])) {
    $id = $_POST['genre_id'];
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $image = uploadGenreImage('genre_image');

    if (editGenre($id, $name, $desc, $image)) {
        echo "<script>alert('Genre updated.'); window.location.href='../../admin/game/';</script>";
    } else {
        echo "<script>alert('Failed to update genre.');</script>";
    }
}

if (isset($_GET['delete_genre'])) {
    $id = $_GET['delete_genre'];
    if (deleteGenre($id)) {
        echo "<script>alert('Genre deleted.'); window.location.href='../../admin/game/';</script>";
    } else {
        echo "<script>alert('Failed to delete genre.');</script>";
    }
}
?>