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
            echo json_encode(['success' => false, 'message' => 'Dépôt GitHub non configuré. Allez dans Configuration.']);
            exit;
        }

        // ── Pré-vérifications ──────────────────────────────────────────────
        if (!function_exists('shell_exec') || in_array('shell_exec', array_map('trim', explode(',', ini_get('disable_functions'))))) {
            echo json_encode(['success' => false, 'message' => '❌ <strong>shell_exec est désactivé</strong> sur ce serveur (php.ini).<br>La mise à jour automatique est impossible. Contactez votre hébergeur ou effectuez la mise à jour manuellement via FTP/SSH.']);
            exit;
        }

        $gitBin = trim(shell_exec('which git 2>/dev/null') ?: shell_exec('command -v git 2>/dev/null'));
        if (empty($gitBin)) {
            echo json_encode(['success' => false, 'message' => '❌ <strong>Git n\'est pas installé</strong> sur ce serveur.<br>Installez git (<code>apt install git</code>) ou effectuez la mise à jour manuellement.']);
            exit;
        }

        $steps   = [];
        $success = true;
        $appDir  = escapeshellarg($this->appDir);

        // ── Sauvegarde .env ────────────────────────────────────────────────
        $envBackup = file_exists($this->envFile) ? file_get_contents($this->envFile) : false;

        // ── Configurer git safe directory ──────────────────────────────────
        shell_exec("git config --global --add safe.directory {$appDir} 2>&1");

        // ── Étape 1 : Initialiser git si nécessaire ────────────────────────
        if (!is_dir($this->appDir . '/.git')) {
            $out = trim(shell_exec("git -C {$appDir} init 2>&1"));
            $steps[] = ['init', $out];
            if (strpos($out, 'Initialized') === false && strpos($out, 'Réinitialisé') === false && strpos($out, 'nitialized') === false) {
                // init peut afficher des messages différents selon la langue, on continue quand même
                // si le répertoire .git n'existe toujours pas, c'est une vraie erreur
                if (!is_dir($this->appDir . '/.git')) {
                    echo json_encode(['success' => false, 'message' => '❌ Échec de <code>git init</code>.<br><pre>' . htmlspecialchars($out) . '</pre>', 'steps' => $steps]);
                    exit;
                }
            }

            $out2 = trim(shell_exec("git -C {$appDir} remote add origin " . escapeshellarg($githubRepo) . " 2>&1"));
            $steps[] = ['remote add', $out2];
        } else {
            $out2 = trim(shell_exec("git -C {$appDir} remote set-url origin " . escapeshellarg($githubRepo) . " 2>&1"));
            $steps[] = ['remote set-url', $out2];
        }

        // ── Étape 2 : Fetch ────────────────────────────────────────────────
        $outFetch = trim(shell_exec("git -C {$appDir} fetch origin main 2>&1"));
        $steps[] = ['fetch', $outFetch];

        if (empty($outFetch) && !is_dir($this->appDir . '/.git/refs')) {
            echo json_encode(['success' => false, 'message' => '❌ <code>git fetch</code> n\'a retourné aucune sortie. Vérifiez l\'URL du dépôt et le token GitHub.', 'steps' => $steps]);
            exit;
        }

        // ── Étape 3 : Reset --hard ─────────────────────────────────────────
        $outReset = trim(shell_exec("git -C {$appDir} reset --hard origin/main 2>&1"));
        $steps[] = ['reset', $outReset];

        // ── Restaurer .env ─────────────────────────────────────────────────
        if ($envBackup !== false) {
            file_put_contents($this->envFile, $envBackup);
            $steps[] = ['.env', 'Fichier .env restauré avec succès.'];
        }

        // ── Vérifier le résultat ───────────────────────────────────────────
        if (strpos($outReset, 'HEAD is now at') !== false || strpos($outReset, 'HEAD est maintenant') !== false || strpos($outReset, 'HEAD pointe maintenant') !== false) {
            $newCommit = trim(shell_exec("git -C {$appDir} rev-parse HEAD 2>/dev/null"));
            echo json_encode([
                'success' => true,
                'message' => '🎉 <strong>Mise à jour appliquée avec succès !</strong><br><small class="text-muted">Version : <code>' . substr($newCommit, 0, 7) . '</code></small><br>L\'application va se recharger dans 3 secondes…',
                'commit'  => substr($newCommit, 0, 7),
                'steps'   => $steps,
            ]);
        } else {
            // Essai avec FETCH_HEAD si origin/main échoue
            $outReset2 = trim(shell_exec("git -C {$appDir} reset --hard FETCH_HEAD 2>&1"));
            $steps[] = ['reset FETCH_HEAD', $outReset2];

            if (strpos($outReset2, 'HEAD is now at') !== false || strpos($outReset2, 'HEAD est maintenant') !== false || strpos($outReset2, 'HEAD pointe maintenant') !== false) {
                $newCommit = trim(shell_exec("git -C {$appDir} rev-parse HEAD 2>/dev/null"));
                echo json_encode([
                    'success' => true,
                    'message' => '🎉 <strong>Mise à jour appliquée avec succès !</strong><br><small class="text-muted">Version : <code>' . substr($newCommit, 0, 7) . '</code></small><br>L\'application va se recharger dans 3 secondes…',
                    'commit'  => substr($newCommit, 0, 7),
                    'steps'   => $steps,
                ]);
            } else {
                // Retourner les détails pour diagnostic
                $stepsHtml = '<details class="mt-2"><summary class="text-muted small" style="cursor:pointer;">Détails des commandes</summary><pre class="mt-1" style="font-size:0.75rem;max-height:150px;overflow:auto;">';
                foreach ($steps as [$label, $out]) {
                    $stepsHtml .= htmlspecialchars("[$label]: $out") . "\n";
                }
                $stepsHtml .= '</pre></details>';
                echo json_encode([
                    'success' => false,
                    'message' => '❌ <strong>Échec de la mise à jour.</strong><br>La commande <code>git reset</code> n\'a pas retourné de résultat attendu.' . $stepsHtml,
                    'steps'   => $steps,
                ]);
            }
        }
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

