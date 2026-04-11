<?php
header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST request required.']);
    exit;
}

$userid = (int) trim($_POST['reviewer_name'] ?? '');
$movie_title = trim($_POST['movie_title'] ?? '');
$rating = trim($_POST['rating'] ?? '');
$verdict = trim($_POST['verdict'] ?? '');

if ($userid <= 0 || $movie_title === '' || $rating === '' || !is_numeric($rating)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Use a numeric User ID, a valid movie title, and a rating.']);
    exit;
}

$rating_value = (float) $rating;
if ($rating_value < 0 || $rating_value > 5) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Rating must be between 0 and 5.']);
    exit;
}

try {
    $movieStmt = $pdo->prepare('SELECT movieid, title, genres FROM movies WHERE LOWER(title) = LOWER(:title) LIMIT 1');
    $movieStmt->execute([':title' => $movie_title]);
    $movie = $movieStmt->fetch();

    if (!$movie) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Movie title not found in the database. You must use exact title from database.']);
        exit;
    }

    $timestamp = time();
    $upsert = $pdo->prepare(
        'INSERT INTO ratings (userid, movieid, rating, timestamp)
         VALUES (:userid, :movieid, :rating, :timestamp)
         ON CONFLICT (userid, movieid)
         DO UPDATE SET rating = EXCLUDED.rating, timestamp = EXCLUDED.timestamp'
    );
    $upsert->execute([
        ':userid' => $userid,
        ':movieid' => $movie['movieid'],
        ':rating' => $rating_value,
        ':timestamp' => $timestamp,
    ]);

    if ($verdict !== '') {
        $tagStmt = $pdo->prepare(
            'INSERT INTO tags (userid, movieid, tag, timestamp)
             VALUES (:userid, :movieid, :tag, :timestamp)'
        );
        $tagStmt->execute([
            ':userid' => $userid,
            ':movieid' => $movie['movieid'],
            ':tag' => $verdict,
            ':timestamp' => $timestamp,
        ]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Review saved successfully.',
        'movie' => [
            'movieid' => (int)$movie['movieid'],
            'title' => $movie['title'],
            'genres' => $movie['genres'],
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
