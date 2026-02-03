# News9 - Plateforme de Partage d'Actualités

Une application web sociale moderne permettant aux utilisateurs de partager et discuter de l'actualité en temps réel.

## 🚀 Fonctionnalités

### Utilisateurs
- ✅ Inscription et connexion sécurisées
- ✅ Profils utilisateurs avec avatars
- ✅ Statut en ligne/hors ligne
- ✅ Système de followers

### Posts
- ✅ Création de posts avec texte et images
- ✅ Système de likes
- ✅ Commentaires
- ✅ Partage de posts
- ✅ Hashtags automatiques

### Messagerie
- ✅ Conversations en temps réel
- ✅ Liste des conversations
- ✅ Indicateurs de lecture
- ✅ Notifications de nouveaux messages

### Interface
- ✅ Design responsive (mobile, tablette, desktop)
- ✅ Mode sombre/clair
- ✅ Navigation intuitive
- ✅ Tendances en temps réel

## 📋 Prérequis

- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur
- Serveur web Apache avec mod_rewrite
- Extension PDO PHP

## 🛠️ Installation

### 1. Cloner le projet
```bash
git clone https://github.com/votre-compte/news9.git
cd news9
```

### 2. Créer la base de données
```bash
mysql -u root -p < database.sql
```

### 3. Configurer la base de données
Modifier le fichier `includes/config.php` :
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'news9_db');
define('DB_USER', 'votre_utilisateur');
define('DB_PASS', 'votre_mot_de_passe');
```

### 4. Configurer les permissions
```bash
chmod 755 uploads/
chmod 644 includes/config.php
```

### 5. Accéder à l'application
Ouvrir `http://localhost/news9` dans votre navigateur.

### Comptes de démonstration
- **Jean Dupont** : jean.dupont@email.com / password
- **Marie Simon** : marie.simon@email.com / password

## 📁 Structure du projet

```
news9/
├── api/                    # API endpoints (AJAX)
│   ├── create_post.php
│   ├── like_post.php
│   ├── get_posts.php
│   ├── get_comments.php
│   ├── add_comment.php
│   ├── get_messages.php
│   ├── send_message.php
│   ├── get_conversations.php
│   └── start_conversation.php
├── css/
│   └── style.css          # Styles principaux
├── includes/
│   └── config.php         # Configuration et fonctions
├── js/
│   └── main.js            # JavaScript principal
├── uploads/               # Dossier des uploads
├── .htaccess             # Configuration Apache
├── database.sql          # Structure de la base de données
├── index.php             # Page principale
├── login.php             # Page de connexion
├── register.php          # Page d'inscription
├── logout.php            # Déconnexion
└── README.md             # Ce fichier
```

## 🗄️ Structure de la base de données

### Tables principales
- **users** : Utilisateurs
- **posts** : Publications
- **likes** : J'aime
- **comments** : Commentaires
- **conversations** : Conversations privées
- **messages** : Messages privés
- **hashtags** : Hashtags
- **notifications** : Notifications
- **followers** : Relations de suivi

## 🔒 Sécurité

- Protection contre les injections SQL (PDO prepared statements)
- Hashage des mots de passe (bcrypt)
- Protection XSS (htmlspecialchars)
- CSRF protection
- Validation des uploads
- Headers de sécurité

## 🌐 API Endpoints

### Posts
- `POST /api/create_post.php` - Créer un post
- `POST /api/like_post.php` - Liker/unliker un post
- `GET /api/get_posts.php?page=1` - Récupérer les posts

### Commentaires
- `GET /api/get_comments.php?post_id=1` - Récupérer les commentaires
- `POST /api/add_comment.php` - Ajouter un commentaire

### Messagerie
- `GET /api/get_conversations.php` - Récupérer les conversations
- `GET /api/get_messages.php?conversation_id=1` - Récupérer les messages
- `POST /api/send_message.php` - Envoyer un message
- `POST /api/start_conversation.php` - Démarrer une conversation

## 📱 Responsive Design

L'application est entièrement responsive :
- **Desktop** : 1200px+ (3 colonnes)
- **Tablette** : 768px - 1199px (2 colonnes)
- **Mobile** : < 768px (1 colonne)

## 🎨 Personnalisation

### Couleurs
Modifier les variables CSS dans `css/style.css` :
```css
:root {
    --primary-green: #25D366;
    --dark-green: #128C7E;
    --light-green: #5CE1A2;
    /* ... */
}
```

## 📝 License

Ce projet est sous licence MIT.

## 👥 Auteurs

- Développé par [Votre Nom]

## 🙏 Remerciements

- Font Awesome pour les icônes
- Google Fonts pour la typographie
- Unsplash pour les images de démonstration
