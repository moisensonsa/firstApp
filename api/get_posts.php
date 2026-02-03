<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

$page = intval($_GET['page'] ?? 1);
$perPage = 10;
$offset = ($page - 1) * $perPage;

try {
    $stmt = $pdo->prepare("
        SELECT p.*, u.full_name, u.username, u.avatar,
               (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
               (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count,
               (SELECT EXISTS(SELECT 1 FROM likes WHERE post_id = p.id AND user_id = ?)) as is_liked
        FROM posts p
        JOIN users u ON p.user_id = u.id
        WHERE p.is_published = 1
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$_SESSION['user_id'], $perPage, $offset]);
    $posts = $stmt->fetchAll();
    
    $formattedPosts = array_map(function($post) {
        return [
            'id' => $post['id'],
            'content' => $post['content'],
            'image' => $post['image'],
            'author_name' => $post['full_name'],
            'author_initials' => generateAvatar($post['full_name']),
            'avatar' => $post['avatar'],
            'likes_count' => $post['likes_count'],
            'comments_count' => $post['comments_count'],
            'shares_count' => $post['shares_count'],
            'is_liked' => (bool)$post['is_liked'],
            'created_at' => $post['created_at']
        ];
    }, $posts);
    
    echo json_encode(['success' => true, 'posts' => $formattedPosts]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des posts']);
}
?>
