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

if ($userid <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Enter a numeric User ID.']);
    exit;
}

try {
    $sql = 'SELECT r.userid,
                   r.movieid,
                   m.title AS movie_title,
                   m.genres AS genre,
                   r.rating,
                   r.timestamp,
                   latest_tag.tag AS verdict
            FROM ratings r
            JOIN movies m ON m.movieid = r.movieid
            LEFT JOIN LATERAL (
                SELECT t.tag
                FROM tags t
                WHERE t.userid = r.userid AND t.movieid = r.movieid
                ORDER BY t.timestamp DESC
                LIMIT 1
            ) latest_tag ON true
            WHERE r.userid = :userid';

    $params = [':userid' => $userid];

    if ($movie_title !== '') {
        $sql .= ' AND LOWER(m.title) = LOWER(:movie_title)';
        $params[':movie_title'] = $movie_title;
    }

    $sql .= ' ORDER BY r.timestamp DESC, m.title ASC LIMIT 50';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reviews = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'reviews' => $reviews,
        'message' => count($reviews) . ' review(s) found.'
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
