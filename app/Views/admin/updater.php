<?php
// Accès superadmin uniquement
if (empty($_SESSION['ecotrace_logged_in']) || ($_SESSION['ecotrace_user_role'] ?? '') !== 'superadmin') {
    header("Location: ?");
    exit;
}
$gitRepo = \App\Config\Database::getEnv('GITHUB_REPO', '');
?>
<div class="container-xl py-3">

    <!-- En-tête -->
    <div class="page-header d-print-none mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <i class="fas fa-sync-alt me-2 text-green"></i>Mises à jour de l'application
                </h2>
                <div class="text-muted mt-1">
                    Vérifiez et appliquez les mises à jour depuis le dépôt GitHub configuré.
                </div>
            </div>
            <div class="col-auto ms-auto">
                <span class="badge bg-blue-lt me-2">
                    <i class="fas fa-code-branch me-1"></i>
                    Version locale : <code id="localCommitDisplay"><?= htmlspecialchars($currentCommit) ?></code>
                </span>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <!-- Carte principale : État & Actions -->
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h3 class="card-title"><i class="fab fa-github me-2"></i>Synchronisation GitHub</h3>
                </div>
                <div class="card-body">

                    <!-- Dépôt configuré -->
                    <div class="mb-4">
                        <label class="form-label fw-bold text-muted small">Dépôt GitHub configuré</label>
                        <?php if (empty($gitRepo)): ?>
                            <div class="alert alert-warning mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Aucun dépôt GitHub configuré. <a href="?edit_config" class="alert-link">Configurer maintenant →</a>
                            </div>
                        <?php else: ?>
                            <div class="d-flex align-items-center gap-2">
                                <i class="fab fa-github text-muted"></i>
                                <code class="text-muted small"><?= htmlspecialchars(preg_replace('/ghp_[^@]+@/', 'ghp_***@', $gitRepo)) ?></code>
                                <span class="badge bg-green-lt">Configuré</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Zone de résultat -->
                    <div id="updateResultBox" class="alert alert-info d-none mb-4" role="alert">
                        <div id="updateResultContent"></div>
                    </div>

                    <!-- Barre de progression -->
                    <div id="updateProgress" class="d-none mb-4">
                        <div class="progress progress-sm mb-2">
                            <div class="progress-bar progress-bar-indeterminate bg-green"></div>
                        </div>
                        <p class="text-muted small" id="updateProgressLabel">Connexion à GitHub en cours…</p>
                    </div>

                    <!-- Actions -->
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-primary" id="btnCheckUpdate" onclick="checkUpdate()"
                                <?= empty($gitRepo) ? 'disabled' : '' ?>>
                            <i class="fas fa-search me-2"></i>Vérifier les mises à jour
                        </button>
                        <button type="button" class="btn btn-success d-none" id="btnApplyUpdate" onclick="applyUpdate()">
                            <i class="fas fa-cloud-download-alt me-2"></i><span id="btnApplyLabel">Initialiser &amp; Mettre à jour</span>
                        </button>
                        <a href="?edit_config" class="btn btn-outline-secondary">
                            <i class="fas fa-cog me-2"></i>Configurer le dépôt
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Carte : Informations système -->
        <div class="col-lg-5">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-info-circle me-2 text-blue"></i>Informations</h3>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td class="text-muted fw-bold small">Commit local</td>
                            <td><code><?= htmlspecialchars($currentCommit) ?></code></td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold small">Répertoire</td>
                            <td><small class="text-muted"><?= htmlspecialchars(realpath(__DIR__ . '/../../..') ?: '—') ?></small></td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold small">Git disponible</td>
                            <td>
                                <?php $gitOk = !empty(shell_exec('which git 2>/dev/null')); ?>
                                <span class="badge <?= $gitOk ? 'bg-success' : 'bg-danger' ?>">
                                    <?= $gitOk ? '✅ Oui' : '❌ Non disponible' ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold small">Dépôt initialisé</td>
                            <td>
                                <?php $gitInit = is_dir(realpath(__DIR__ . '/../../..') . '/.git'); ?>
                                <span class="badge <?= $gitInit ? 'bg-success' : 'bg-warning' ?>">
                                    <?= $gitInit ? '✅ Oui' : '⚠️ Non' ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold small">shell_exec</td>
                            <td>
                                <?php $shellOk = function_exists('shell_exec') && !in_array('shell_exec', array_map('trim', explode(',', ini_get('disable_functions')))); ?>
                                <span class="badge <?= $shellOk ? 'bg-success' : 'bg-danger' ?>">
                                    <?= $shellOk ? '✅ Activé' : '❌ Désactivé' ?>
                                </span>
                            </td>
                        </tr>
                    </table>

                    <?php if (!$gitOk || !$shellOk): ?>
                    <div class="alert alert-warning mt-2 mb-0 py-2">
                        <small><i class="fas fa-exclamation-triangle me-1"></i>
                        <?= !$gitOk ? 'Git n\'est pas installé sur ce serveur.' : '' ?>
                        <?= !$shellOk ? 'La fonction <code>shell_exec</code> est désactivée (php.ini).' : '' ?>
                        La mise à jour automatique n\'est pas possible.</small>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Carte : Comment ça marche -->
        <div class="col-12">
            <div class="card shadow-sm border-0 bg-blue-lt">
                <div class="card-body py-3">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <i class="fas fa-lightbulb fa-2x text-blue"></i>
                        </div>
                        <div class="col">
                            <strong>Comment fonctionne la mise à jour ?</strong>
                            <div class="text-muted small mt-1">
                                1. Cliquez sur <strong>Vérifier les mises à jour</strong> — l'application interroge GitHub pour comparer le commit actuel avec la dernière version disponible.<br>
                                2. Si une nouvelle version est disponible, cliquez sur <strong>Appliquer la mise à jour</strong> — l'application télécharge les fichiers (<code>git reset --hard origin/main</code>).<br>
                                3. Le fichier <code>.env</code> (identifiants base de données) est automatiquement préservé pendant la mise à jour.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * Affiche le résultat d'un check et montre/cache le bouton d'action
 */
function afficherResultatCheck(data) {
    const box     = document.getElementById('updateResultBox');
    const content = document.getElementById('updateResultContent');
    const btnApply = document.getElementById('btnApplyUpdate');
    const btnLabel = document.getElementById('btnApplyLabel');

    box.classList.remove('d-none');
    box.className = 'alert mb-4 ' + (data.update_available ? 'alert-success' : 'alert-info');
    content.innerHTML = data.message || '—';

    if (data.update_available) {
        // Adapter le libellé selon si .git existe ou non
        if (data.local === '') {
            btnLabel.textContent = 'Initialiser & Mettre à jour';
        } else {
            btnLabel.textContent = 'Appliquer la mise à jour';
        }
        btnApply.classList.remove('d-none');
    } else {
        btnApply.classList.add('d-none');
    }
}

async function checkUpdate() {
    const btn      = document.getElementById('btnCheckUpdate');
    const btnApply = document.getElementById('btnApplyUpdate');
    const box      = document.getElementById('updateResultBox');
    const progress = document.getElementById('updateProgress');
    const progressLabel = document.getElementById('updateProgressLabel');

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Vérification…';
    btnApply.classList.add('d-none');
    box.classList.add('d-none');
    progress.classList.remove('d-none');
    progressLabel.textContent = 'Connexion à GitHub en cours…';

    try {
        const fd = new FormData();
        fd.append('action', 'check_update_ajax');
        const r    = await fetch('?', { method: 'POST', body: fd });
        const data = await r.json();
        progress.classList.add('d-none');
        afficherResultatCheck(data);
    } catch (e) {
        progress.classList.add('d-none');
        box.classList.remove('d-none');
        box.className = 'alert alert-danger mb-4';
        document.getElementById('updateResultContent').innerHTML =
            '<i class="fas fa-times-circle me-2"></i>Erreur de communication. Vérifiez votre connexion et le dépôt configuré.';
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-search me-2"></i>Vérifier les mises à jour';
    }
}

async function applyUpdate() {
    const btnLabel = document.getElementById('btnApplyLabel');
    if (!confirm('Appliquer la mise à jour depuis GitHub ?\n\nL\'application sera rechargée après la mise à jour.\nVotre fichier .env sera automatiquement préservé.')) return;

    const btn      = document.getElementById('btnApplyUpdate');
    const box      = document.getElementById('updateResultBox');
    const content  = document.getElementById('updateResultContent');
    const progress = document.getElementById('updateProgress');
    const progressLabel = document.getElementById('updateProgressLabel');

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Téléchargement…';
    progress.classList.remove('d-none');
    progressLabel.textContent = 'Téléchargement et application de la mise à jour…';

    try {
        const fd = new FormData();
        fd.append('action', 'apply_update_ajax');
        const r    = await fetch('?', { method: 'POST', body: fd });
        const data = await r.json();

        progress.classList.add('d-none');
        box.className = 'alert mb-4 ' + (data.success ? 'alert-success' : 'alert-danger');
        content.innerHTML = data.message || '—';
        btn.classList.add('d-none');

        if (data.success) {
            setTimeout(() => location.reload(), 3000);
        }
    } catch (e) {
        progress.classList.add('d-none');
        box.className = 'alert alert-danger mb-4';
        content.innerHTML = '<i class="fas fa-times-circle me-2"></i>Échec critique lors de la mise à jour.';
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-cloud-download-alt me-2"></i><span id="btnApplyLabel">Réessayer</span>';
    }
}

// Vérification silencieuse au chargement — exécutée après que le DOM est prêt
document.addEventListener('DOMContentLoaded', async function() {
    <?php if (!empty($gitRepo)): ?>
    try {
        const fd = new FormData();
        fd.append('action', 'check_update_ajax');
        const r    = await fetch('?', { method: 'POST', body: fd });
        const data = await r.json();
        if (data.success) {
            afficherResultatCheck(data);
        }
    } catch(e) {
        // Silencieux au chargement
    }
    <?php endif; ?>
});
</script>


