<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$conversationId = intval($data['conversation_id'] ?? 0);
$content = sanitize($data['content'] ?? '');

if (!$conversationId || empty($content)) {
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
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
        INSERT INTO messages (conversation_id, sender_id, content) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$conversationId, $_SESSION['user_id'], $content]);
    
    // Mettre à jour la date du dernier message
    $stmt = $pdo->prepare("
        UPDATE conversations 
        SET last_message_at = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$conversationId]);
    
    // Créer une notification pour le destinataire
    $stmt = $pdo->prepare("
        SELECT CASE WHEN user1_id = ? THEN user2_id ELSE user1_id END as recipient_id
        FROM conversations WHERE id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $conversationId]);
    $recipientId = $stmt->fetchColumn();
    
    if ($recipientId) {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, type, from_user_id, reference_id, content)
            VALUES (?, 'message', ?, ?, ?)
        ");
        $stmt->execute([$recipientId, $_SESSION['user_id'], $conversationId, substr($content, 0, 100)]);
    }
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'envoi du message']);
}
?>
