<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    $rows = $pdo->query(
        'SELECT r.userid,
                m.title,
                m.genres,
                r.rating,
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
         ORDER BY r.timestamp DESC, m.title ASC
         LIMIT 6'
    )->fetchAll();

    echo json_encode(['success' => true, 'reviews' => $rows]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
