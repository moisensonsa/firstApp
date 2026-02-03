<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$postId = intval($data['post_id'] ?? 0);
$content = sanitize($data['content'] ?? '');

if (!$postId || empty($content)) {
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO comments (user_id, post_id, content) VALUES (?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $postId, $content]);
    
    // Mettre à jour le compteur de commentaires
    $stmt = $pdo->prepare("
        UPDATE posts 
        SET comments_count = (SELECT COUNT(*) FROM comments WHERE post_id = ?)
        WHERE id = ?
    ");
    $stmt->execute([$postId, $postId]);
    
    // Créer une notification
    $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
    $stmt->execute([$postId]);
    $postOwner = $stmt->fetchColumn();
    
    if ($postOwner && $postOwner != $_SESSION['user_id']) {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, type, from_user_id, reference_id, content)
            VALUES (?, 'comment', ?, ?, ?)
        ");
        $stmt->execute([$postOwner, $_SESSION['user_id'], $postId, substr($content, 0, 100)]);
    }
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout du commentaire']);
}
?>
