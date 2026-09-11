<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>EcoTrace 🍃 - Connexion</title>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- NOUVEAU LIEN VERS LE FICHIER CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-box">
        <h1>EcoTrace 🍃</h1>
        <div class="app-version">Version 2.1.0 (MVC)</div>
        <p>Veuillez vous identifier</p>
        
        <?php if (isset($login_error) && $login_error): ?>
            <div class='error'><?= htmlspecialchars($login_error) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="login_action" value="1">
            <input type="text" name="username" placeholder="Identifiant" required>
            <input type="password" name="password" placeholder="Mot de passe" required>
            <button type="submit">Se connecter</button>
        </form>
    </div>
</body>
</html>