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

if ($userid <= 0 || $movieid <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'A valid user and movie are required.']);
    exit;
}

try {
    $pdo->beginTransaction();

    $deleteTags = $pdo->prepare('DELETE FROM tags WHERE userid = :userid AND movieid = :movieid');
    $deleteTags->execute([':userid' => $userid, ':movieid' => $movieid]);

    $deleteRating = $pdo->prepare('DELETE FROM ratings WHERE userid = :userid AND movieid = :movieid');
    $deleteRating->execute([':userid' => $userid, ':movieid' => $movieid]);

    if ($deleteRating->rowCount() === 0) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'No matching rating was found.']);
        exit;
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Review deleted successfully.']);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
