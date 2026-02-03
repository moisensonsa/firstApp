<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

$postId = intval($_GET['post_id'] ?? 0);

if (!$postId) {
    echo json_encode(['success' => false, 'message' => 'ID du post invalide']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT c.*, u.full_name, u.avatar
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.post_id = ?
        ORDER BY c.created_at ASC
    ");
    $stmt->execute([$postId]);
    $comments = $stmt->fetchAll();
    
    $formattedComments = array_map(function($comment) {
        return [
            'id' => $comment['id'],
            'content' => $comment['content'],
            'author_name' => $comment['full_name'],
            'author_initials' => generateAvatar($comment['full_name']),
            'avatar' => $comment['avatar'],
            'created_at' => formatDate($comment['created_at'])
        ];
    }, $comments);
    
    echo json_encode(['success' => true, 'comments' => $formattedComments]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des commentaires']);
}
?>
