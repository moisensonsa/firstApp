<?php
require_once 'includes/config.php';

// Mettre à jour le statut hors ligne
if (isLoggedIn()) {
    $stmt = $pdo->prepare("UPDATE users SET is_online = 0 WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
}

// Détruire la session
session_destroy();

// Rediriger vers la page de connexion
redirect('login.php');
?>
