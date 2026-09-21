<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>EcoTrace 🍃 - Connexion</title>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/css/tabler.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Nunito', sans-serif;
            background: linear-gradient(135deg, #1a7f37 0%, #2da44e 50%, #3fb950 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .login-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 24px 64px rgba(0,0,0,0.18);
            width: 100%;
            max-width: 420px;
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #1a7f37, #2da44e);
            padding: 2.5rem 2rem 2rem;
            text-align: center;
            color: white;
        }
        .login-logo {
            font-family: 'Fredoka', sans-serif;
            font-size: 2.4rem;
            font-weight: 600;
            letter-spacing: -0.5px;
            margin-bottom: 0.25rem;
        }
        .login-logo i {
            margin-right: 0.5rem;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
        }
        .login-subtitle {
            font-size: 0.85rem;
            opacity: 0.85;
            background: rgba(255,255,255,0.15);
            display: inline-block;
            padding: 0.2rem 0.75rem;
            border-radius: 20px;
            margin-top: 0.4rem;
        }
        .login-body {
            padding: 2rem;
        }
        .login-body h2 {
            font-family: 'Fredoka', sans-serif;
            font-size: 1.3rem;
            color: #2d3436;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .form-group {
            margin-bottom: 1.1rem;
        }
        .form-label {
            font-weight: 600;
            font-size: 0.85rem;
            color: #636e72;
            margin-bottom: 0.35rem;
            display: block;
        }
        .input-wrapper {
            position: relative;
        }
        .input-wrapper i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #b2bec3;
            font-size: 0.9rem;
        }
        .form-control {
            width: 100%;
            padding: 0.65rem 0.9rem 0.65rem 2.5rem;
            border: 1.5px solid #dee2e6;
            border-radius: 10px;
            font-size: 0.95rem;
            font-family: 'Nunito', sans-serif;
            transition: border-color 0.2s, box-shadow 0.2s;
            background: #f8f9fa;
            outline: none;
        }
        .form-control:focus {
            border-color: #2da44e;
            box-shadow: 0 0 0 3px rgba(45,164,78,0.15);
            background: #fff;
        }
        .btn-login {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(135deg, #1a7f37, #2da44e);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 1rem;
            font-family: 'Fredoka', sans-serif;
            font-weight: 500;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: transform 0.1s, box-shadow 0.2s;
            margin-top: 0.5rem;
        }
        .btn-login:hover {
            box-shadow: 0 6px 20px rgba(45,164,78,0.35);
            transform: translateY(-1px);
        }
        .btn-login:active {
            transform: translateY(0);
        }
        .btn-login i { margin-right: 0.4rem; }
        .alert-error {
            background: #fff0f0;
            border: 1.5px solid #ffcdd2;
            color: #c0392b;
            border-radius: 10px;
            padding: 0.65rem 1rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.2rem;
        }
        .login-footer {
            text-align: center;
            padding: 1rem 2rem 1.5rem;
            font-size: 0.78rem;
            color: #b2bec3;
        }
        .login-footer a { color: #2da44e; text-decoration: none; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <div class="login-logo">
                <i class="fas fa-leaf"></i>EcoTrace
            </div>
            <div class="login-subtitle">Bilan Carbone Multi-Sociétés v2.4.0</div>
        </div>

        <div class="login-body">
            <h2>Bienvenue 👋</h2>

            <?php if (isset($login_error) && $login_error): ?>
                <div class="alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($login_error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" autocomplete="on">
                <input type="hidden" name="login_action" value="1">

                <div class="form-group">
                    <label class="form-label">Identifiant</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" name="username" class="form-control"
                               placeholder="Votre identifiant"
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                               required autofocus autocomplete="username">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Mot de passe</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" class="form-control"
                               placeholder="Votre mot de passe"
                               required autocomplete="current-password">
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i>Se connecter
                </button>
            </form>
        </div>

        <div class="login-footer">
            EcoTrace &copy; <?= date('Y') ?> &mdash; Gestion du Bilan Carbone RSE
        </div>
    </div>
</body>
</html>