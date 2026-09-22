<?php
namespace App\Controllers;

use App\Config\Database;

class UpdateController {

    private string $appDir;
    private string $envFile;

    public function __construct() {
        $this->appDir = realpath(__DIR__ . '/../../');
        $this->envFile = $this->appDir . '/.env';
    }

    /**
     * Affiche la page de mise à jour (vue admin)
     */
    public function index() {
        if (!AuthController::isSuperAdmin()) {
            header("Location: ?");
            exit;
        }

        $githubRepo = Database::getEnv('GITHUB_REPO', '');
        $currentCommit = $this->getCurrentCommit();
        $countEnAttente   = 0;
        $countValides     = 0;
        $countIntrouvables = 0;

        ob_start();
        require __DIR__ . '/../Views/admin/updater.php';
        $content = ob_get_clean();
        require __DIR__ . '/../Views/layout.php';
    }

    /**
     * Vérifie si une mise à jour est disponible sur GitHub (AJAX)
     */
    public function checkAjax() {
        header('Content-Type: application/json; charset=utf-8');

        if (!AuthController::isSuperAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Accès refusé.']);
            exit;
        }

        $githubRepo = Database::getEnv('GITHUB_REPO', '');
        if (empty($githubRepo)) {
            echo json_encode(['success' => false, 'message' => 'Le dépôt GitHub n\'est pas configuré.<br>Allez dans <strong>Configuration</strong> pour renseigner le champ <code>GITHUB_REPO</code>.']);
            exit;
        }

        // Marquer le répertoire comme safe pour git
        shell_exec('git config --global --add safe.directory ' . escapeshellarg($this->appDir) . ' 2>&1');

        if (!is_dir($this->appDir . '/.git')) {
            echo json_encode([
                'success'          => true,
                'update_available' => true,
                'message'          => '⚠️ L\'application n\'est pas encore liée à GitHub.<br>Cliquez sur <strong>Initialiser &amp; Mettre à jour</strong> pour effectuer la connexion initiale.',
                'local'            => '',
                'remote'           => '',
            ]);
            exit;
        }

        // Configurer le remote avec le token inclus dans l'URL
        shell_exec('git -C ' . escapeshellarg($this->appDir) . ' remote set-url origin ' . escapeshellarg($githubRepo) . ' 2>&1');
        shell_exec('git -C ' . escapeshellarg($this->appDir) . ' fetch origin main 2>&1');

        $local  = trim(shell_exec('git -C ' . escapeshellarg($this->appDir) . ' rev-parse HEAD 2>/dev/null'));
        $remote = trim(shell_exec('git -C ' . escapeshellarg($this->appDir) . ' rev-parse origin/main 2>/dev/null'));

        if (!empty($remote) && $local !== $remote) {
            // Récupérer les commits en avance
            $log = trim(shell_exec('git -C ' . escapeshellarg($this->appDir) . ' log --oneline HEAD..origin/main 2>/dev/null'));
            $logHtml = '';
            if ($log) {
                $lines = array_filter(explode("\n", $log));
                $logHtml = '<ul class="mt-2 mb-0" style="font-size:0.8rem;color:#636e72;">';
                foreach (array_slice($lines, 0, 10) as $l) {
                    $logHtml .= '<li>' . htmlspecialchars($l) . '</li>';
                }
                if (count($lines) > 10) {
                    $logHtml .= '<li>... et ' . (count($lines) - 10) . ' autre(s) commit(s)</li>';
                }
                $logHtml .= '</ul>';
            }
            echo json_encode([
                'success'          => true,
                'update_available' => true,
                'message'          => '✨ <strong>Une nouvelle version est disponible sur GitHub !</strong><br><small class="text-muted">Version locale : <code>' . substr($local, 0, 7) . '</code> → Nouvelle : <code>' . substr($remote, 0, 7) . '</code></small>' . $logHtml,
                'local'            => substr($local, 0, 7),
                'remote'           => substr($remote, 0, 7),
            ]);
        } else {
            echo json_encode([
                'success'          => true,
                'update_available' => false,
                'message'          => '✅ <strong>Votre application est à jour !</strong><br><small class="text-muted">Version : <code>' . substr($local, 0, 7) . '</code></small>',
                'local'            => substr($local, 0, 7),
                'remote'           => $remote ? substr($remote, 0, 7) : '—',
            ]);
        }
        exit;
    }

    /**
     * Applique la mise à jour depuis GitHub (AJAX)
     */
    public function applyAjax() {
        header('Content-Type: application/json; charset=utf-8');

        if (!AuthController::isSuperAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Accès refusé.']);
            exit;
        }

        $githubRepo = Database::getEnv('GITHUB_REPO', '');
        if (empty($githubRepo)) {
            echo json_encode(['success' => false, 'message' => 'Dépôt GitHub non configuré.']);
            exit;
        }

        // Sauvegarde du .env avant mise à jour
        $envBackup = file_exists($this->envFile) ? file_get_contents($this->envFile) : false;

        shell_exec('git config --global --add safe.directory ' . escapeshellarg($this->appDir) . ' 2>&1');

        // Initialiser git si nécessaire
        if (!is_dir($this->appDir . '/.git')) {
            shell_exec('git -C ' . escapeshellarg($this->appDir) . ' init 2>&1');
            shell_exec('git -C ' . escapeshellarg($this->appDir) . ' remote add origin ' . escapeshellarg($githubRepo) . ' 2>&1');
        } else {
            shell_exec('git -C ' . escapeshellarg($this->appDir) . ' remote set-url origin ' . escapeshellarg($githubRepo) . ' 2>&1');
        }

        shell_exec('git -C ' . escapeshellarg($this->appDir) . ' fetch origin main 2>&1');
        $output = shell_exec('git -C ' . escapeshellarg($this->appDir) . ' reset --hard origin/main 2>&1');

        // Restaurer le .env (git reset --hard pourrait l'écraser)
        if ($envBackup !== false) {
            file_put_contents($this->envFile, $envBackup);
        }

        $newCommit = trim(shell_exec('git -C ' . escapeshellarg($this->appDir) . ' rev-parse HEAD 2>/dev/null'));

        echo json_encode([
            'success' => true,
            'message' => '🎉 <strong>Mise à jour appliquée avec succès !</strong><br><small class="text-muted">Version : <code>' . substr($newCommit, 0, 7) . '</code></small><br>L\'application va se recharger dans 3 secondes…',
            'commit'  => substr($newCommit, 0, 7),
        ]);
        exit;
    }

    /**
     * Retourne le hash du commit actuel (court)
     */
    private function getCurrentCommit(): string {
        shell_exec('git config --global --add safe.directory ' . escapeshellarg($this->appDir) . ' 2>&1');
        $commit = trim(shell_exec('git -C ' . escapeshellarg($this->appDir) . ' rev-parse HEAD 2>/dev/null'));
        return $commit ? substr($commit, 0, 7) : 'non-versionné';
    }
}
