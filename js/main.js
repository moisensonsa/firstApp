// Variables globales
let currentUser = null;
let currentConversation = null;
let messagingOpen = false;

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    initApp();
});

function initApp() {
    // Éléments du DOM
    const messagingToggle = document.getElementById('messagingToggle');
    const messagingWindow = document.getElementById('messagingWindow');
    const closeMessaging = document.getElementById('closeMessaging');
    const publishPostBtn = document.getElementById('publishPost');
    const postInput = document.querySelector('.post-input');
    const chatInput = document.getElementById('chatInput');
    const sendMessageBtn = document.getElementById('sendMessage');
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const navItems = document.querySelectorAll('.nav-item');
    const postActionBtns = document.querySelectorAll('.post-action-btn');
    const conversationItems = document.querySelectorAll('.conversation-item');
    const mediaBtns = document.querySelectorAll('.media-btn');
    
    // Ouvrir/fermer la messagerie
    if (messagingToggle) {
        messagingToggle.addEventListener('click', function() {
            messagingWindow.classList.toggle('active');
            messagingOpen = !messagingOpen;
            if (messagingOpen) {
                loadConversations();
            }
        });
    }
    
    if (closeMessaging) {
        closeMessaging.addEventListener('click', function() {
            messagingWindow.classList.remove('active');
            messagingOpen = false;
        });
    }
    
    // Publier un nouveau post
    if (publishPostBtn) {
        publishPostBtn.addEventListener('click', publishPost);
    }
    
    // Actions sur les posts
    postActionBtns.forEach(btn => {
        btn.addEventListener('click', handlePostAction);
    });
    
    // Sélection d'une conversation
    conversationItems.forEach(item => {
        item.addEventListener('click', function() {
            const conversationId = this.dataset.conversationId;
            const userName = this.querySelector('h4').textContent;
            selectConversation(conversationId, userName);
        });
    });
    
    // Envoyer un message
    if (sendMessageBtn) {
        sendMessageBtn.addEventListener('click', sendMessage);
    }
    
    if (chatInput) {
        chatInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
    }
    
    // Menu mobile
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', toggleMobileMenu);
    }
    
    // Navigation items
    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            navItems.forEach(i => i.classList.remove('active'));
            this.classList.add('active');
            
            const itemName = this.querySelector('span').textContent;
            showNotification(`Navigation vers: ${itemName}`, 'info');
        });
    });
    
    // Boutons média
    mediaBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const action = this.querySelector('span').textContent;
            showNotification(`${action}: Fonctionnalité en cours de développement`, 'info');
        });
    });
    
    // Charger les posts initiaux
    loadPosts();
    
    // Rafraîchir les données périodiquement
    setInterval(refreshData, 30000);
}

// Publier un post
function publishPost() {
    const postInput = document.querySelector('.post-input');
    const content = postInput.value.trim();
    const publishBtn = document.getElementById('publishPost');
    
    if (!content) {
        showNotification('Veuillez écrire quelque chose avant de publier.', 'warning');
        return;
    }
    
    // Désactiver le bouton pendant l'envoi
    publishBtn.disabled = true;
    publishBtn.innerHTML = '<div class="spinner"></div>';
    
    // Envoyer la requête AJAX
    fetch('api/create_post.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ content: content })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            postInput.value = '';
            showNotification('Votre post a été publié avec succès !', 'success');
            
            // Ajouter le nouveau post au début du feed
            const feedContainer = document.querySelector('.feed-container');
            const createPostCard = document.querySelector('.create-post-card');
            const newPostHTML = createPostHTML(data.post);
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = newPostHTML;
            feedContainer.insertBefore(tempDiv.firstElementChild, createPostCard.nextSibling);
            
            // Ajouter les événements au nouveau post
            const newPost = feedContainer.querySelector('.post-card:nth-child(2)');
            const newPostActions = newPost.querySelectorAll('.post-action-btn');
            newPostActions.forEach(btn => {
                btn.addEventListener('click', handlePostAction);
            });
        } else {
            showNotification(data.message || 'Erreur lors de la publication', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Erreur de connexion', 'error');
    })
    .finally(() => {
        publishBtn.disabled = false;
        publishBtn.innerHTML = '<i class="fas fa-paper-plane"></i><span>Publier</span>';
    });
}

// Créer le HTML d'un post
function createPostHTML(post) {
    const avatar = post.avatar ? 
        `<img src="${post.avatar}" alt="${post.author_name}">` : 
        post.author_initials;
    
    const imageHTML = post.image ? 
        `<div class="post-media"><img src="${post.image}" alt="Post image"></div>` : '';
    
    return `
        <article class="post-card" data-post-id="${post.id}">
            <div class="post-header">
                <div class="post-author">
                    <div class="author-avatar">${avatar}</div>
                    <div class="author-info">
                        <h4>${post.author_name}</h4>
                        <span><i class="far fa-clock"></i> À l'instant • <i class="fas fa-globe-americas"></i></span>
                    </div>
                </div>
                <div class="post-options">
                    <i class="fas fa-ellipsis-h"></i>
                </div>
            </div>
            
            <div class="post-content">
                <div class="post-text">
                    <p>${post.content}</p>
                </div>
                ${imageHTML}
            </div>
            
            <div class="post-stats">
                <span><i class="fas fa-thumbs-up"></i> 0</span>
                <span><i class="fas fa-comment"></i> 0 commentaire</span>
                <span><i class="fas fa-share"></i> 0 partage</span>
            </div>
            
            <div class="post-actions-bar">
                <button class="post-action-btn" data-action="like">
                    <i class="far fa-thumbs-up"></i>
                    <span>J'aime</span>
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
    `;
}

// Gérer les actions sur les posts
function handlePostAction(e) {
    const btn = e.currentTarget;
    const action = btn.dataset.action;
    const postCard = btn.closest('.post-card');
    const postId = postCard.dataset.postId;
    
    if (action === 'like') {
        toggleLike(btn, postId);
    } else if (action === 'comment') {
        toggleComments(postCard);
    } else if (action === 'share') {
        sharePost(postId);
    }
}

// Toggle like
function toggleLike(btn, postId) {
    const icon = btn.querySelector('i');
    const isLiked = icon.classList.contains('fas');
    
    fetch('api/like_post.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ post_id: postId, action: isLiked ? 'unlike' : 'like' })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (isLiked) {
                icon.classList.remove('fas');
                icon.classList.add('far');
                btn.querySelector('span').textContent = "J'aime";
            } else {
                icon.classList.remove('far');
                icon.classList.add('fas');
                btn.querySelector('span').textContent = 'Aimé';
            }
            
            // Mettre à jour le compteur
            const likeCount = btn.closest('.post-card').querySelector('.post-stats span:first-child');
            likeCount.innerHTML = `<i class="fas fa-thumbs-up"></i> ${data.likes_count}`;
        }
    })
    .catch(error => console.error('Error:', error));
}

// Toggle comments section
function toggleComments(postCard) {
    let commentsSection = postCard.querySelector('.comments-section');
    
    if (!commentsSection) {
        commentsSection = document.createElement('div');
        commentsSection.className = 'comments-section';
        commentsSection.innerHTML = `
            <div class="comment-form">
                <div class="user-avatar" style="width: 36px; height: 36px; font-size: 14px;">JD</div>
                <input type="text" class="comment-input" placeholder="Écrire un commentaire...">
                <button class="comment-submit">Envoyer</button>
            </div>
            <div class="comments-list"></div>
        `;
        postCard.appendChild(commentsSection);
        
        // Charger les commentaires
        loadComments(postCard.dataset.postId, commentsSection);
    }
    
    commentsSection.classList.toggle('active');
}

// Charger les commentaires
function loadComments(postId, container) {
    fetch(`api/get_comments.php?post_id=${postId}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const commentsList = container.querySelector('.comments-list');
            commentsList.innerHTML = data.comments.map(comment => createCommentHTML(comment)).join('');
        }
    })
    .catch(error => console.error('Error:', error));
}

// Créer le HTML d'un commentaire
function createCommentHTML(comment) {
    const avatar = comment.avatar ? 
        `<img src="${comment.avatar}" alt="${comment.author_name}">` : 
        comment.author_initials;
    
    return `
        <div class="comment-item">
            <div class="author-avatar" style="width: 36px; height: 36px; font-size: 14px;">${avatar}</div>
            <div class="comment-content">
                <div class="comment-author">${comment.author_name}</div>
                <div class="comment-text">${comment.content}</div>
                <div class="comment-meta">${comment.created_at}</div>
            </div>
        </div>
    `;
}

// Partager un post
function sharePost(postId) {
    // Copier le lien dans le presse-papiers
    const url = `${window.location.origin}/post/${postId}`;
    navigator.clipboard.writeText(url).then(() => {
        showNotification('Lien copié dans le presse-papiers !', 'success');
    });
}

// Sélectionner une conversation
function selectConversation(conversationId, userName) {
    currentConversation = conversationId;
    
    // Mettre à jour l'interface
    document.querySelectorAll('.conversation-item').forEach(item => {
        item.classList.remove('active');
    });
    document.querySelector(`[data-conversation-id="${conversationId}"]`).classList.add('active');
    
    document.querySelector('.chat-header h4').textContent = `Conversation avec ${userName}`;
    
    // Charger les messages
    loadMessages(conversationId);
}

// Charger les messages
function loadMessages(conversationId) {
    fetch(`api/get_messages.php?conversation_id=${conversationId}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const chatMessages = document.getElementById('chatMessages');
            chatMessages.innerHTML = data.messages.map(msg => createMessageHTML(msg)).join('');
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    })
    .catch(error => console.error('Error:', error));
}

// Créer le HTML d'un message
function createMessageHTML(message) {
    const isSent = message.is_sent;
    const time = new Date(message.created_at).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
    
    return `
        <div class="message ${isSent ? 'sent' : 'received'}">
            ${message.content}
            <div class="message-time-small">${time}</div>
        </div>
    `;
}

// Envoyer un message
function sendMessage() {
    const chatInput = document.getElementById('chatInput');
    const content = chatInput.value.trim();
    
    if (!content || !currentConversation) return;
    
    fetch('api/send_message.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            conversation_id: currentConversation,
            content: content
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            chatInput.value = '';
            
            // Ajouter le message à l'interface
            const chatMessages = document.getElementById('chatMessages');
            const messageHTML = createMessageHTML({
                content: content,
                is_sent: true,
                created_at: new Date().toISOString()
            });
            chatMessages.insertAdjacentHTML('beforeend', messageHTML);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    })
    .catch(error => console.error('Error:', error));
}

// Charger les posts
function loadPosts() {
    fetch('api/get_posts.php')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Les posts sont déjà affichés côté serveur
            // Cette fonction peut être utilisée pour le rafraîchissement
        }
    })
    .catch(error => console.error('Error:', error));
}

// Charger les conversations
function loadConversations() {
    fetch('api/get_conversations.php')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mettre à jour la liste des conversations
            const conversationsList = document.getElementById('conversationsList');
            // Implémentation du rendu des conversations
        }
    })
    .catch(error => console.error('Error:', error));
}

// Rafraîchir les données
function refreshData() {
    if (messagingOpen && currentConversation) {
        loadMessages(currentConversation);
    }
}

// Toggle menu mobile
function toggleMobileMenu() {
    const headerActions = document.querySelector('.header-actions');
    headerActions.classList.toggle('mobile-open');
    
    const icon = this.querySelector('i');
    if (icon.classList.contains('fa-bars')) {
        icon.classList.remove('fa-bars');
        icon.classList.add('fa-times');
    } else {
        icon.classList.remove('fa-times');
        icon.classList.add('fa-bars');
    }
}

// Afficher une notification
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    
    const icons = {
        success: 'fa-check-circle',
        warning: 'fa-exclamation-triangle',
        error: 'fa-times-circle',
        info: 'fa-info-circle'
    };
    
    notification.innerHTML = `<i class="fas ${icons[type] || icons.info}"></i> ${message}`;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Gestion du scroll infini
let isLoading = false;
let currentPage = 1;

window.addEventListener('scroll', function() {
    if (isLoading) return;
    
    const scrollPosition = window.innerHeight + window.scrollY;
    const documentHeight = document.documentElement.scrollHeight;
    
    if (scrollPosition >= documentHeight - 500) {
        loadMorePosts();
    }
});

function loadMorePosts() {
    isLoading = true;
    currentPage++;
    
    fetch(`api/get_posts.php?page=${currentPage}`)
    .then(response => response.json())
    .then(data => {
        if (data.success && data.posts.length > 0) {
            const feedContainer = document.querySelector('.feed-container');
            data.posts.forEach(post => {
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = createPostHTML(post);
                feedContainer.appendChild(tempDiv.firstElementChild);
            });
        }
        isLoading = false;
    })
    .catch(error => {
        console.error('Error:', error);
        isLoading = false;
    });
}

// Gestion des hashtags
function highlightHashtags(text) {
    return text.replace(/#(\w+)/g, '<a href="/tag/$1" class="hashtag">#$1</a>');
}

// Gestion des mentions
function highlightMentions(text) {
    return text.replace(/@(\w+)/g, '<a href="/user/$1" class="mention">@$1</a>');
}

// Formatage du texte des posts
function formatPostContent(content) {
    let formatted = highlightHashtags(content);
    formatted = highlightMentions(formatted);
    return formatted;
}

// Recherche en temps réel
const searchInput = document.querySelector('.search-input');
if (searchInput) {
    let searchTimeout;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        
        if (query.length < 2) return;
        
        searchTimeout = setTimeout(() => {
            performSearch(query);
        }, 300);
    });
}

function performSearch(query) {
    fetch(`api/search.php?q=${encodeURIComponent(query)}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Afficher les résultats de recherche
            showNotification(`${data.results.length} résultats trouvés`, 'info');
        }
    })
    .catch(error => console.error('Error:', error));
}
