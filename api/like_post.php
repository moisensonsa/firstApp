<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Récupérer les données JSON
$data = json_decode(file_get_contents('php://input'), true);
$postId = intval($data['post_id'] ?? 0);
$action = $data['action'] ?? 'like';

if (!$postId) {
    echo json_encode(['success' => false, 'message' => 'ID du post invalide']);
    exit;
}

try {
    if ($action === 'like') {
        // Ajouter le like
        $stmt = $pdo->prepare("INSERT IGNORE INTO likes (user_id, post_id) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $postId]);
    } else {
        // Retirer le like
        $stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
        $stmt->execute([$_SESSION['user_id'], $postId]);
    }
    
    // Mettre à jour le compteur de likes
    $stmt = $pdo->prepare("
        UPDATE posts 
        SET likes_count = (SELECT COUNT(*) FROM likes WHERE post_id = ?)
        WHERE id = ?
    ");
    $stmt->execute([$postId, $postId]);
    
    // Récupérer le nouveau nombre de likes
    $stmt = $pdo->prepare("SELECT likes_count FROM posts WHERE id = ?");
    $stmt->execute([$postId]);
    $likesCount = $stmt->fetchColumn();
    
    // Créer une notification si c'est un nouveau like
    if ($action === 'like') {
        $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
        $stmt->execute([$postId]);
        $postOwner = $stmt->fetchColumn();
        
        if ($postOwner && $postOwner != $_SESSION['user_id']) {
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, type, from_user_id, reference_id)
                VALUES (?, 'like', ?, ?)
            ");
            $stmt->execute([$postOwner, $_SESSION['user_id'], $postId]);
        }
    }
    
    echo json_encode(['success' => true, 'likes_count' => $likesCount]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors du traitement']);
}
?>
