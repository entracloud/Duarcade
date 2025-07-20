<?php
require_once '../../assets/database/config.php';
require_once '../../assets/database/db.php';

header('Content-Type: application/json');

if (!isset($_GET['game_id'])) {
    echo json_encode(['error' => 'Game ID not provided']);
    exit;
}

$gameId = (int)$_GET['game_id'];
$conn = getDatabaseConnection();

// Get game data
$stmt = $conn->prepare("SELECT g.*, i.stock_quantity FROM Games g LEFT JOIN Inventory i ON g.game_id = i.game_id WHERE g.game_id = ?");
$stmt->bind_param("i", $gameId);
$stmt->execute();
$result = $stmt->get_result();

if ($game = $result->fetch_assoc()) {
    echo json_encode($game);
} else {
    echo json_encode(['error' => 'Game not found']);
}
?>