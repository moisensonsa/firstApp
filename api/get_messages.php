<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

$conversationId = intval($_GET['conversation_id'] ?? 0);

if (!$conversationId) {
    echo json_encode(['success' => false, 'message' => 'ID de conversation invalide']);
    exit;
}

// Vérifier que l'utilisateur fait partie de cette conversation
$stmt = $pdo->prepare("
    SELECT id FROM conversations 
    WHERE id = ? AND (user1_id = ? OR user2_id = ?)
");
$stmt->execute([$conversationId, $_SESSION['user_id'], $_SESSION['user_id']]);

if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT m.*, u.full_name
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.conversation_id = ?
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$conversationId]);
    $messages = $stmt->fetchAll();
    
    $formattedMessages = array_map(function($msg) {
        return [
            'id' => $msg['id'],
            'content' => $msg['content'],
            'is_sent' => $msg['sender_id'] == $_SESSION['user_id'],
            'sender_name' => $msg['full_name'],
            'created_at' => $msg['created_at'],
            'is_read' => $msg['is_read']
        ];
    }, $messages);
    
    // Marquer les messages comme lus
    $stmt = $pdo->prepare("
        UPDATE messages 
        SET is_read = 1 
        WHERE conversation_id = ? AND sender_id != ? AND is_read = 0
    ");
    $stmt->execute([$conversationId, $_SESSION['user_id']]);
    
    echo json_encode(['success' => true, 'messages' => $formattedMessages]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des messages']);
}
?>
