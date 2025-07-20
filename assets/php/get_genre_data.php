<?php
require_once '../../assets/database/config.php';
require_once '../../assets/database/db.php';

header('Content-Type: application/json');

if (!isset($_GET['genre_id'])) {
    echo json_encode(['error' => 'Genre ID not provided']);
    exit;
}

$genreId = (int)$_GET['genre_id'];
$conn = getDatabaseConnection();

$stmt = $conn->prepare("SELECT * FROM Genres WHERE genre_id = ?");
$stmt->bind_param("i", $genreId);
$stmt->execute();
$result = $stmt->get_result();

if ($genre = $result->fetch_assoc()) {
    echo json_encode($genre);
} else {
    echo json_encode(['error' => 'Genre not found']);
}
?>