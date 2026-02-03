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
$content = sanitize($data['content'] ?? '');

if (empty($content)) {
    echo json_encode(['success' => false, 'message' => 'Le contenu ne peut pas être vide']);
    exit;
}

// Gérer l'upload d'image si présent
$imagePath = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $upload = uploadFile($_FILES['image']);
    if ($upload['success']) {
        $imagePath = $upload['path'];
    }
}

// Insérer le post
try {
    $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, image) VALUES (?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $content, $imagePath]);
    $postId = $pdo->lastInsertId();
    
    // Extraire et enregistrer les hashtags
    preg_match_all('/#(\w+)/', $content, $hashtags);
    foreach ($hashtags[1] as $tag) {
        $stmt = $pdo->prepare("INSERT INTO hashtags (tag) VALUES (?) ON DUPLICATE KEY UPDATE count = count + 1");
        $stmt->execute(['#' . $tag]);
    }
    
    // Récupérer les informations du post créé
    $stmt = $pdo->prepare("
        SELECT p.*, u.full_name, u.avatar
        FROM posts p
        JOIN users u ON p.user_id = u.id
        WHERE p.id = ?
    ");
    $stmt->execute([$postId]);
    $post = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'post' => [
            'id' => $post['id'],
            'content' => $post['content'],
            'image' => $post['image'],
            'author_name' => $post['full_name'],
            'author_initials' => generateAvatar($post['full_name']),
            'avatar' => $post['avatar'],
            'created_at' => $post['created_at']
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la création du post']);
}
?>
