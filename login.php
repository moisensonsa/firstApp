<?php
require_once 'includes/config.php';

// Rediriger si déjà connecté
if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            
            // Mettre à jour le statut en ligne
            $stmt = $pdo->prepare("UPDATE users SET is_online = 1, last_activity = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            redirect('index.php');
        } else {
            $error = 'Email ou mot de passe incorrect';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-logo">
                <div class="logo-icon">N9</div>
                <h1><?php echo SITE_NAME; ?></h1>
                <p style="color: var(--text-light); margin-top: 8px;">Partagez l'actualité en temps réel</p>
            </div>
            
            <?php if ($error): ?>
            <div class="notification error" style="position: static; margin-bottom: 20px;">
                <i class="fas fa-times-circle"></i> <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-input" placeholder="votre@email.com" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Mot de passe</label>
                    <input type="password" name="password" class="form-input" placeholder="••••••••" required>
                </div>
                
                <button type="submit" class="form-submit">
                    <i class="fas fa-sign-in-alt"></i> Se connecter
                </button>
            </form>
            
            <div class="auth-footer">
                <p>Pas encore de compte ? <a href="register.php">S'inscrire</a></p>
                <p style="margin-top: 10px;"><a href="#">Mot de passe oublié ?</a></p>
            </div>
            
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--border-color);">
                <p style="text-align: center; font-size: 13px; color: var(--text-light); margin-bottom: 15px;">Comptes de démonstration</p>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <div style="background: var(--gray-bg); padding: 12px; border-radius: 8px; font-size: 13px;">
                        <strong>Jean Dupont</strong><br>
                        jean.dupont@email.com / password
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
