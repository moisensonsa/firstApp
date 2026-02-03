-- Base de données News9
CREATE DATABASE IF NOT EXISTS news9_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE news9_db;

-- Table des utilisateurs
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    role ENUM('user', 'moderator', 'admin') DEFAULT 'user',
    is_online BOOLEAN DEFAULT FALSE,
    last_activity TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des posts
CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    likes_count INT DEFAULT 0,
    comments_count INT DEFAULT 0,
    shares_count INT DEFAULT 0,
    is_published BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des likes
CREATE TABLE likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    post_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    UNIQUE KEY unique_like (user_id, post_id)
);

-- Table des commentaires
CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    post_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
);

-- Table des messages (conversations)
CREATE TABLE conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user1_id INT NOT NULL,
    user2_id INT NOT NULL,
    last_message_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user1_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (user2_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_conversation (user1_id, user2_id)
);

-- Table des messages
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    sender_id INT NOT NULL,
    content TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des tendances (hashtags)
CREATE TABLE hashtags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tag VARCHAR(100) NOT NULL UNIQUE,
    count INT DEFAULT 1,
    last_used TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table de relation posts-hashtags
CREATE TABLE post_hashtags (
    post_id INT NOT NULL,
    hashtag_id INT NOT NULL,
    PRIMARY KEY (post_id, hashtag_id),
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (hashtag_id) REFERENCES hashtags(id) ON DELETE CASCADE
);

-- Table des notifications
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('like', 'comment', 'follow', 'message', 'mention') NOT NULL,
    from_user_id INT NOT NULL,
    reference_id INT DEFAULT NULL,
    content TEXT DEFAULT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (from_user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des followers
CREATE TABLE followers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    follower_id INT NOT NULL,
    following_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_follow (follower_id, following_id)
);

-- Insertion de données de test
INSERT INTO users (username, email, password, full_name, role, is_online) VALUES
('jeandupont', 'jean.dupont@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jean Dupont', 'user', TRUE),
('mariesimon', 'marie.simon@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Marie Simon', 'user', TRUE),
('pierrelambert', 'pierre.lambert@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Pierre Lambert', 'user', FALSE),
('alexandresimon', 'alexandre.simon@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Alexandre Simon', 'user', TRUE),
('emmagarcia', 'emma.garcia@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Emma Garcia', 'user', TRUE),
('mohammedrami', 'mohammed.rami@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mohammed Rami', 'user', TRUE),
('laurachen', 'laura.chen@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Laura Chen', 'user', TRUE);

-- Insertion de posts de test
INSERT INTO posts (user_id, content, image, likes_count, comments_count, shares_count) VALUES
(2, 'Les nouvelles technologies de l\'énergie renouvelable transforment notre façon de produire et de consommer l\'électricité. Les panneaux solaires à pérovskite pourraient bientôt révolutionner le marché avec un rendement de conversion de plus de 30%. #InnovationTech #ÉnergieVerte', 'https://images.unsplash.com/photo-1466611653911-95081537e5b7?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80', 245, 42, 18),
(3, 'L\'intelligence artificielle au service de la médecine : un nouveau modèle prédictif permet de détecter les risques de maladies cardiovasculaires avec une précision de 94%. Cette avancée pourrait sauver des milliers de vies chaque année. #SantéDigitale #IA', 'https://images.unsplash.com/photo-1559757148-5c350d0d3c56?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80', 189, 31, 9),
(4, 'Nouvelle avancée dans le domaine du stockage d\'énergie : les batteries au sodium pourraient remplacer le lithium d\'ici 2025. Une solution plus écologique et économique pour la transition énergétique.', NULL, 156, 23, 12),
(5, 'Le télétravail transforme nos villes : étude sur l\'impact de la digitalisation sur l\'immobilier de bureau. Les espaces de coworking connaissent une croissance de 40% par an. #Télétravail #Immobilier', 'https://images.unsplash.com/photo-1497366216548-37526070297c?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80', 312, 67, 45);

-- Insertion de hashtags
INSERT INTO hashtags (tag, count) VALUES
('#InnovationTech', 15200),
('#DéveloppementDurable', 12700),
('#SantéDigitale', 9800),
('#Education2024', 7500),
('#IA', 25300),
('#ÉnergieVerte', 18700),
('#Télétravail', 14200),
('#Startup', 11900),
('#Mobilité', 9400);

-- Insertion de conversations
INSERT INTO conversations (user1_id, user2_id, last_message_at) VALUES
(1, 2, NOW()),
(1, 3, DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(1, 4, DATE_SUB(NOW(), INTERVAL 1 DAY));

-- Insertion de messages
INSERT INTO messages (conversation_id, sender_id, content, created_at) VALUES
(1, 2, 'Salut Jean, as-tu vu mon dernier article sur les énergies renouvelables ?', DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
(1, 1, 'Oui, c\'est très intéressant ! Les chiffres sur l\'efficacité des nouvelles cellules solaires sont impressionnants.', DATE_SUB(NOW(), INTERVAL 28 MINUTE)),
(1, 2, 'Merci ! Je prépare un autre article sur le stockage d\'énergie. On pourrait collaborer si ça t\'intéresse ?', DATE_SUB(NOW(), INTERVAL 25 MINUTE)),
(2, 3, 'La réunion est prévue pour demain à 14h', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(3, 4, 'Je t\'ai envoyé les photos pour l\'article', DATE_SUB(NOW(), INTERVAL 1 DAY));

-- Insertion de likes
INSERT INTO likes (user_id, post_id) VALUES
(1, 1), (3, 1), (4, 1), (5, 1), (6, 1),
(1, 2), (2, 2), (4, 2), (7, 2),
(2, 3), (3, 3), (5, 3),
(1, 4), (2, 4), (3, 4), (4, 4), (6, 4), (7, 4);

-- Insertion de commentaires
INSERT INTO comments (user_id, post_id, content) VALUES
(1, 1, 'Excellent article ! J\'ai hâte de voir ces technologies déployées à grande échelle.'),
(3, 1, 'Les perspectives sont vraiment prometteuses. Merci pour ce partage !'),
(2, 2, 'C\'est une avancée majeure pour la médecine préventive.'),
(1, 3, 'Le sodium est effectivement beaucoup plus abondant que le lithium.'),
(2, 4, 'Le télétravail a vraiment changé notre façon de travailler.');

