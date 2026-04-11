<?php
header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST request required.']);
    exit;
}

$title = trim($_POST['movie_title'] ?? '');
if ($title === '') {
    echo json_encode(['success' => false, 'message' => 'Movie title is required.']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT movieid, title, genres FROM movies WHERE LOWER(title) = LOWER(:title) LIMIT 1');
    $stmt->execute([':title' => $title]);
    $movie = $stmt->fetch();

    if (!$movie) {
        echo json_encode(['success' => false, 'message' => 'Movie not found.']);
        exit;
    }

    echo json_encode(['success' => true, 'movie' => $movie]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
