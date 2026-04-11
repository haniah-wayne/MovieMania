<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    $summary = [];
    $summary['total_reviews'] = (int)$pdo->query('SELECT COUNT(*) FROM ratings')->fetchColumn();
    $summary['unique_movies'] = (int)$pdo->query('SELECT COUNT(DISTINCT movieid) FROM ratings')->fetchColumn();
    $summary['avg_rating'] = round((float)$pdo->query('SELECT COALESCE(AVG(rating), 0) FROM ratings')->fetchColumn(), 2);
    $summary['reviewers'] = (int)$pdo->query('SELECT COUNT(DISTINCT userid) FROM ratings')->fetchColumn();

    $topMovies = $pdo->query(
        'SELECT m.title,
                m.genres,
                ROUND(AVG(r.rating)::numeric, 2) AS avg_rating,
                COUNT(*) AS review_count
         FROM ratings r
         JOIN movies m ON m.movieid = r.movieid
         GROUP BY m.movieid, m.title, m.genres
         ORDER BY AVG(r.rating) DESC, COUNT(*) DESC, m.title ASC
         LIMIT 8'
    )->fetchAll();

    echo json_encode([
        'success' => true,
        'summary' => $summary,
        'top_movies' => $topMovies,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
