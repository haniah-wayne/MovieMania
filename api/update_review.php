<?php
header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST request required.']);
    exit;
}

$userid = (int) ($_POST['userid'] ?? 0);
$movieid = (int) ($_POST['movieid'] ?? 0);
$rating = trim($_POST['rating'] ?? '');
$verdict = trim($_POST['verdict'] ?? '');

if ($userid <= 0 || $movieid <= 0 || $rating === '' || !is_numeric($rating)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'A valid user, movie, and rating are required.']);
    exit;
}

$rating_value = (float) $rating;
if ($rating_value < 0 || $rating_value > 5) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Rating must be between 0 and 5.']);
    exit;
}

try {
    $pdo->beginTransaction();

    $updateRating = $pdo->prepare(
        'UPDATE ratings SET rating = :rating, timestamp = :timestamp WHERE userid = :userid AND movieid = :movieid'
    );
    $updateRating->execute([
        ':rating' => $rating_value,
        ':timestamp' => time(),
        ':userid' => $userid,
        ':movieid' => $movieid,
    ]);

    if ($updateRating->rowCount() === 0) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'No matching rating was found to update.']);
        exit;
    }

    if ($verdict !== '') {
        $deleteTags = $pdo->prepare('DELETE FROM tags WHERE userid = :userid AND movieid = :movieid');
        $deleteTags->execute([':userid' => $userid, ':movieid' => $movieid]);

        $insertTag = $pdo->prepare(
            'INSERT INTO tags (userid, movieid, tag, timestamp) VALUES (:userid, :movieid, :tag, :timestamp)'
        );
        $insertTag->execute([
            ':userid' => $userid,
            ':movieid' => $movieid,
            ':tag' => $verdict,
            ':timestamp' => time(),
        ]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Review updated successfully.']);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
