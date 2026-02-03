<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$userId = intval($data['user_id'] ?? 0);

if (!$userId || $userId == $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'ID utilisateur invalide']);
    exit;
}

try {
    // Vérifier si une conversation existe déjà
    $stmt = $pdo->prepare("
        SELECT id FROM conversations 
        WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)
    ");
    $stmt->execute([$_SESSION['user_id'], $userId, $userId, $_SESSION['user_id']]);
    $existingConv = $stmt->fetch();
    
    if ($existingConv) {
        echo json_encode(['success' => true, 'conversation_id' => $existingConv['id']]);
    } else {
        // Créer une nouvelle conversation
        $stmt = $pdo->prepare("INSERT INTO conversations (user1_id, user2_id) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $userId]);
        $conversationId = $pdo->lastInsertId();
        
        echo json_encode(['success' => true, 'conversation_id' => $conversationId]);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la création de la conversation']);
}
?>
