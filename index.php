<?php
require_once 'includes/config.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    redirect('login.php');
}

$currentUser = getCurrentUser();

// Récupérer les posts
$stmt = $pdo->query("
    SELECT p.*, u.full_name, u.username, u.avatar,
           (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
           (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count,
           (SELECT EXISTS(SELECT 1 FROM likes WHERE post_id = p.id AND user_id = {$_SESSION['user_id']})) as is_liked
    FROM posts p
    JOIN users u ON p.user_id = u.id
    WHERE p.is_published = 1
    ORDER BY p.created_at DESC
    LIMIT 20
");
$posts = $stmt->fetchAll();

// Récupérer les tendances
$stmt = $pdo->query("SELECT * FROM hashtags ORDER BY count DESC LIMIT 5");
$trending = $stmt->fetchAll();

// Récupérer les utilisateurs en ligne
$stmt = $pdo->query("
    SELECT id, full_name, username, avatar, is_online 
    FROM users 
    WHERE id != {$_SESSION['user_id']} 
    ORDER BY is_online DESC, full_name ASC 
    LIMIT 5
");
$onlineUsers = $stmt->fetchAll();

// Récupérer les conversations
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

// Compter les notifications non lues
$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$_SESSION['user_id']]);
$unreadNotifications = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Partagez l'actualité en temps réel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="container header-container">
            <a href="index.php" class="logo-container">
                <div class="logo-icon">N9</div>
                <div class="logo-text"><?php echo SITE_NAME; ?></div>
            </a>
            
            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" class="search-input" placeholder="Rechercher des actualités, sujets ou personnes...">
            </div>
            
            <div class="header-actions">
                <a href="index.php" class="action-btn">
                    <i class="fas fa-home"></i>
                </a>
                
                <button class="action-btn" id="notificationsBtn">
                    <i class="fas fa-bell"></i>
                    <?php if ($unreadNotifications > 0): ?>
                    <span class="notification-badge"><?php echo $unreadNotifications; ?></span>
                    <?php endif; ?>
                </button>
                
                <button class="action-btn" id="messagesBtn">
                    <i class="fas fa-envelope"></i>
                    <?php if (count($conversations) > 0): ?>
                    <span class="notification-badge"><?php echo min(count($conversations), 9); ?></span>
                    <?php endif; ?>
                </button>
                
                <div class="user-profile" onclick="toggleUserMenu()">
                    <div class="user-avatar">
                        <?php if ($currentUser['avatar']): ?>
                        <img src="<?php echo $currentUser['avatar']; ?>" alt="<?php echo $currentUser['full_name']; ?>">
                        <?php else: ?>
                        <?php echo generateAvatar($currentUser['full_name']); ?>
                        <?php endif; ?>
                    </div>
                    <span class="user-name"><?php echo $currentUser['full_name']; ?></span>
                </div>
                
                <button class="mobile-menu-btn">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Contenu principal -->
    <main class="main-content">
        <div class="container">
            <div class="content-grid">
                <!-- Sidebar gauche -->
                <aside class="sidebar-left">
                    <div class="sidebar-card">
                        <h3 class="card-title"><i class="fas fa-compass"></i> Navigation</h3>
                        <nav class="nav-menu">
                            <a href="index.php" class="nav-item active">
                                <i class="fas fa-home"></i>
                                <span>Fil d'actualités</span>
                            </a>
                            <a href="#" class="nav-item">
                                <i class="fas fa-fire"></i>
                                <span>Tendances</span>
                            </a>
                            <a href="#" class="nav-item">
                                <i class="fas fa-video"></i>
                                <span>Vidéos</span>
                            </a>
                            <a href="#" class="nav-item">
                                <i class="fas fa-users"></i>
                                <span>Communautés</span>
                            </a>
                            <a href="#" class="nav-item">
                                <i class="fas fa-bookmark"></i>
                                <span>Signets</span>
                            </a>
                            <a href="#" class="nav-item">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Événements</span>
                            </a>
                            <a href="settings.php" class="nav-item">
                                <i class="fas fa-cog"></i>
                                <span>Paramètres</span>
                            </a>
                        </nav>
                    </div>
                    
                    <div class="sidebar-card">
                        <h3 class="card-title"><i class="fas fa-hashtag"></i> Tendances</h3>
                        <div class="trending-list">
                            <?php foreach ($trending as $index => $tag): ?>
                            <div class="trending-item">
                                <div class="trending-rank <?php echo $index === 0 ? 'top' : ''; ?>"><?php echo $index + 1; ?></div>
                                <div class="trending-info">
                                    <h4><?php echo htmlspecialchars($tag['tag']); ?></h4>
                                    <span><?php echo number_format($tag['count'], 0, ',', ' '); ?> publications</span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </aside>

                <!-- Feed principal -->
                <section class="feed-container">
                    <!-- Créer un post -->
                    <div class="create-post-card">
                        <div class="post-input-container">
                            <div class="user-avatar">
                                <?php if ($currentUser['avatar']): ?>
                                <img src="<?php echo $currentUser['avatar']; ?>" alt="<?php echo $currentUser['full_name']; ?>">
                                <?php else: ?>
                                <?php echo generateAvatar($currentUser['full_name']); ?>
                                <?php endif; ?>
                            </div>
                            <textarea class="post-input" placeholder="Partagez une actualité, une pensée ou une information..."></textarea>
                        </div>
                        
                        <div class="post-actions">
                            <div class="media-actions">
                                <button class="media-btn" onclick="document.getElementById('postImage').click()">
                                    <i class="fas fa-image"></i>
                                    <span>Photo</span>
                                </button>
                                <button class="media-btn">
                                    <i class="fas fa-video"></i>
                                    <span>Vidéo</span>
                                </button>
                                <button class="media-btn">
                                    <i class="fas fa-link"></i>
                                    <span>Lien</span>
                                </button>
                                <button class="media-btn">
                                    <i class="fas fa-poll"></i>
                                    <span>Sondage</span>
                                </button>
                            </div>
                            <input type="file" id="postImage" accept="image/*" style="display: none;">
                            
                            <button class="post-submit-btn" id="publishPost">
                                <i class="fas fa-paper-plane"></i>
                                <span>Publier</span>
                            </button>
                        </div>
                    </div>

                    <!-- Posts -->
                    <?php foreach ($posts as $post): ?>
                    <article class="post-card" data-post-id="<?php echo $post['id']; ?>">
                        <div class="post-header">
                            <div class="post-author">
                                <div class="author-avatar">
                                    <?php if ($post['avatar']): ?>
                                    <img src="<?php echo $post['avatar']; ?>" alt="<?php echo $post['full_name']; ?>">
                                    <?php else: ?>
                                    <?php echo generateAvatar($post['full_name']); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="author-info">
                                    <h4><?php echo htmlspecialchars($post['full_name']); ?></h4>
                                    <span>
                                        <i class="far fa-clock"></i> 
                                        <?php echo formatDate($post['created_at']); ?> • 
                                        <i class="fas fa-globe-americas"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="post-options">
                                <i class="fas fa-ellipsis-h"></i>
                            </div>
                        </div>
                        
                        <div class="post-content">
                            <div class="post-text">
                                <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                            </div>
                            
                            <?php if ($post['image']): ?>
                            <div class="post-media">
                                <img src="<?php echo $post['image']; ?>" alt="Post image" loading="lazy">
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="post-stats">
                            <span><i class="fas fa-thumbs-up"></i> <?php echo $post['likes_count']; ?></span>
                            <span><i class="fas fa-comment"></i> <?php echo $post['comments_count']; ?> commentaire<?php echo $post['comments_count'] > 1 ? 's' : ''; ?></span>
                            <span><i class="fas fa-share"></i> <?php echo $post['shares_count']; ?> partage<?php echo $post['shares_count'] > 1 ? 's' : ''; ?></span>
                        </div>
                        
                        <div class="post-actions-bar">
                            <button class="post-action-btn <?php echo $post['is_liked'] ? 'active' : ''; ?>" data-action="like">
                                <i class="<?php echo $post['is_liked'] ? 'fas' : 'far'; ?> fa-thumbs-up"></i>
                                <span><?php echo $post['is_liked'] ? 'Aimé' : "J'aime"; ?></span>
                            </button>
                            <button class="post-action-btn" data-action="comment">
                                <i class="far fa-comment"></i>
                                <span>Commenter</span>
                            </button>
                            <button class="post-action-btn" data-action="share">
                                <i class="fas fa-share"></i>
                                <span>Partager</span>
                            </button>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </section>

                <!-- Sidebar droite -->
                <aside class="sidebar-right">
                    <div class="sidebar-card">
                        <h3 class="card-title"><i class="fas fa-users"></i> En ligne maintenant</h3>
                        <div class="online-users">
                            <?php foreach ($onlineUsers as $user): ?>
                            <div class="user-item" onclick="startConversation(<?php echo $user['id']; ?>)">
                                <div class="user-status">
                                    <div class="user-avatar-sm">
                                        <?php if ($user['avatar']): ?>
                                        <img src="<?php echo $user['avatar']; ?>" alt="<?php echo $user['full_name']; ?>">
                                        <?php else: ?>
                                        <?php echo generateAvatar($user['full_name']); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="status-indicator <?php echo $user['is_online'] ? '' : 'offline'; ?>"></div>
                                </div>
                                <div class="user-details">
                                    <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
                                    <span><?php echo $user['is_online'] ? 'En ligne' : 'Hors ligne'; ?></span>
                                </div>
                                <button class="message-btn">
                                    <i class="fas fa-comment"></i>
                                </button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="sidebar-card">
                        <h3 class="card-title"><i class="fas fa-newspaper"></i> À ne pas manquer</h3>
                        <div class="trending-list">
                            <div class="trending-item">
                                <div class="trending-info">
                                    <h4>L'avenir de la voiture électrique</h4>
                                    <span>Technologie • 2.4K lectures</span>
                                </div>
                            </div>
                            <div class="trending-item">
                                <div class="trending-info">
                                    <h4>Cybersécurité : les nouveaux défis</h4>
                                    <span>Sécurité • 1.8K lectures</span>
                                </div>
                            </div>
                            <div class="trending-item">
                                <div class="trending-info">
                                    <h4>L'économie circulaire en entreprise</h4>
                                    <span>Économie • 1.5K lectures</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </main>

    <!-- Messagerie -->
    <div class="messaging-container">
        <div class="messaging-toggle" id="messagingToggle">
            <i class="fas fa-comments"></i>
        </div>
        
        <div class="messaging-window" id="messagingWindow">
            <div class="messaging-header">
                <h3><i class="fas fa-comments"></i> Messages</h3>
                <div class="close-messaging" id="closeMessaging">
                    <i class="fas fa-times"></i>
                </div>
            </div>
            
            <div class="conversations-list" id="conversationsList">
                <?php foreach ($conversations as $conv): ?>
                <div class="conversation-item" data-conversation-id="<?php echo $conv['id']; ?>">
                    <div class="user-avatar-sm">
                        <?php if ($conv['other_user_avatar']): ?>
                        <img src="<?php echo $conv['other_user_avatar']; ?>" alt="<?php echo $conv['other_user_name']; ?>">
                        <?php else: ?>
                        <?php echo generateAvatar($conv['other_user_name']); ?>
                        <?php endif; ?>
                    </div>
                    <div class="conversation-info">
                        <h4><?php echo htmlspecialchars($conv['other_user_name']); ?></h4>
                        <p><?php echo $conv['last_message'] ? htmlspecialchars(substr($conv['last_message'], 0, 40)) . '...' : 'Aucun message'; ?></p>
                    </div>
                    <div class="message-time">
                        <?php echo $conv['last_message_time'] ? formatDate($conv['last_message_time']) : ''; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="chat-container" id="chatContainer" style="display: none;">
                <div class="chat-header">
                    <h4>Conversation</h4>
                </div>
                
                <div class="chat-messages" id="chatMessages"></div>
                
                <div class="chat-input-container">
                    <input type="text" class="chat-input" id="chatInput" placeholder="Tapez votre message...">
                    <button class="chat-send-btn" id="sendMessage">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4><?php echo SITE_NAME; ?></h4>
                    <ul class="footer-links">
                        <li><a href="#">À propos de <?php echo SITE_NAME; ?></a></li>
                        <li><a href="#">Notre mission</a></li>
                        <li><a href="#">Carrières</a></li>
                        <li><a href="#">Presse</a></li>
                        <li><a href="#">Contact</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Communauté</h4>
                    <ul class="footer-links">
                        <li><a href="#">Normes de la communauté</a></li>
                        <li><a href="#">Confidentialité</a></li>
                        <li><a href="#">Sécurité</a></li>
                        <li><a href="#">Centre d'aide</a></li>
                        <li><a href="#">Transparence</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Créateurs</h4>
                    <ul class="footer-links">
                        <li><a href="#">Devenir créateur</a></li>
                        <li><a href="#">Monétisation</a></li>
                        <li><a href="#">Statistiques</a></li>
                        <li><a href="#">API pour développeurs</a></li>
                        <li><a href="#">Documentation</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Actualités</h4>
                    <ul class="footer-links">
                        <li><a href="#">Technologie</a></li>
                        <li><a href="#">Science</a></li>
                        <li><a href="#">Politique</a></li>
                        <li><a href="#">Économie</a></li>
                        <li><a href="#">Culture</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div class="copyright">
                    &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Tous droits réservés. Plateforme de partage d'information.
                </div>
                
                <div class="social-links">
                    <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <script src="js/main.js"></script>
</body>
</html>
