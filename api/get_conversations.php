<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT c.*, 
               CASE WHEN c.user1_id = ? THEN c.user2_id ELSE c.user1_id END as other_user_id,
               u.full_name as other_user_name,
               u.avatar as other_user_avatar,
               u.is_online as other_user_online,
               (SELECT content FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message,
               (SELECT created_at FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message_time
        FROM conversations c
        JOIN users u ON u.id = CASE WHEN c.user1_id = ? THEN c.user2_id ELSE c.user1_id END
        WHERE c.user1_id = ? OR c.user2_id = ?
        ORDER BY c.last_message_at DESC
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
    $conversations = $stmt->fetchAll();
    
    $formattedConversations = array_map(function($conv) {
        return [
            'id' => $conv['id'],
            'other_user_id' => $conv['other_user_id'],
            'other_user_name' => $conv['other_user_name'],
            'other_user_initials' => generateAvatar($conv['other_user_name']),
            'other_user_avatar' => $conv['other_user_avatar'],
            'other_user_online' => (bool)$conv['other_user_online'],
            'last_message' => $conv['last_message'],
            'last_message_time' => $conv['last_message_time'] ? formatDate($conv['last_message_time']) : ''
        ];
    }, $conversations);
    
    echo json_encode(['success' => true, 'conversations' => $formattedConversations]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des conversations']);
}
?>
