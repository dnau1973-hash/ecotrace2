<?php
// Sécurité : accès superadmin uniquement
if (empty($_SESSION['ecotrace_logged_in']) || ($_SESSION['ecotrace_user_role'] ?? '') !== 'superadmin') {
    header("Location: ?");
    exit;
}
$activeSocieteId = $_SESSION['active_societe_id'] ?? 'all';
$rolesLabels = ['superadmin' => 'Super Admin', 'admin' => 'Admin', 'lecteur' => 'Lecteur'];
$rolesBadge  = ['superadmin' => 'bg-danger', 'admin' => 'bg-primary', 'lecteur' => 'bg-secondary'];
?>
<div class="container-xl py-3">

    <!-- En-tête -->
    <div class="page-header d-print-none mb-3">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <i class="fas fa-users-cog me-2 text-primary"></i>Gestion des Utilisateurs
                </h2>
                <div class="text-muted mt-1">
                    Créez et gérez les comptes utilisateurs, leurs rôles et leurs accès aux sociétés.
                </div>
            </div>
            <div class="col-auto ms-auto">
                <button class="btn btn-primary" onclick="openUserModal()">
                    <i class="fas fa-user-plus me-2"></i>Nouvel Utilisateur
                </button>
            </div>
        </div>
    </div>

    <!-- Flash message -->
    <?php if (!empty($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?= htmlspecialchars($_SESSION['flash_type'] ?? 'info') ?> alert-dismissible fade show mb-3" role="alert">
            <i class="fas fa-info-circle me-2"></i><?= htmlspecialchars($_SESSION['flash_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
    <?php endif; ?>

    <!-- Légende des rôles -->
    <div class="row g-2 mb-4">
        <div class="col-auto">
            <div class="card card-sm border-danger">
                <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                    <span class="badge bg-danger">Super Admin</span>
                    <small class="text-muted">Accès complet, gestion des utilisateurs</small>
                </div>
            </div>
        </div>
        <div class="col-auto">
            <div class="card card-sm border-primary">
                <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                    <span class="badge bg-primary">Admin</span>
                    <small class="text-muted">Accès complet à ses sociétés autorisées</small>
                </div>
            </div>
        </div>
        <div class="col-auto">
            <div class="card card-sm border-secondary">
                <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                    <span class="badge bg-secondary">Lecteur</span>
                    <small class="text-muted">Consultation uniquement</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Tableau des utilisateurs -->
    <div class="card shadow-sm">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-list me-2"></i><?= count($utilisateurs) ?> utilisateur(s)</h3>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter table-hover card-table">
                <thead>
                    <tr>
                        <th>Utilisateur</th>
                        <th>Login</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Sociétés</th>
                        <th>Statut</th>
                        <th>Dernière connexion</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($utilisateurs as $u): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <span class="avatar avatar-sm me-2 <?= $rolesBadge[$u['role']] ?>" style="color:white;font-size:0.8rem;">
                                    <?= strtoupper(substr($u['prenom'] ?: $u['nom'], 0, 1)) . strtoupper(substr($u['nom'], 0, 1)) ?>
                                </span>
                                <div>
                                    <div class="fw-bold"><?= htmlspecialchars(trim($u['prenom'] . ' ' . $u['nom'])) ?></div>
                                    <div class="text-muted small">ID #<?= $u['id'] ?></div>
                                </div>
                            </div>
                        </td>
                        <td><code><?= htmlspecialchars($u['login']) ?></code></td>
                        <td><?= $u['email'] ? htmlspecialchars($u['email']) : '<span class="text-muted">—</span>' ?></td>
                        <td><span class="badge <?= $rolesBadge[$u['role']] ?>"><?= $rolesLabels[$u['role']] ?? $u['role'] ?></span></td>
                        <td>
                            <?php if ($u['societe_ids'] === null || $u['role'] === 'superadmin'): ?>
                                <span class="text-success fw-bold" title="Accès total"><i class="fas fa-globe"></i> Toutes</span>
                            <?php else:
                                $ids = json_decode($u['societe_ids'], true) ?: [];
                                $noms = array_map(function($s) use ($ids) {
                                    return in_array($s['id'], $ids) ? htmlspecialchars($s['nom']) : null;
                                }, $societes);
                                $noms = array_filter($noms);
                                echo count($noms) ? implode(', ', $noms) : '<span class="text-danger">Aucune</span>';
                            endif; ?>
                        </td>
                        <td>
                            <?php if ($u['actif']): ?>
                                <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Actif</span>
                            <?php else: ?>
                                <span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>Inactif</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($u['derniere_connexion']): ?>
                                <span class="text-muted small"><?= date('d/m/Y H:i', strtotime($u['derniere_connexion'])) ?></span>
                            <?php else: ?>
                                <span class="text-muted small">Jamais</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" title="Modifier"
                                    onclick='openUserModal(<?= json_encode($u, JSON_UNESCAPED_UNICODE) ?>)'>
                                    <i class="fas fa-pen"></i>
                                </button>
                                <button class="btn btn-outline-warning" title="Changer mot de passe"
                                    onclick="openPasswordModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['login']) ?>')">
                                    <i class="fas fa-key"></i>
                                </button>
                                <?php if ($u['id'] !== (int)($_SESSION['ecotrace_user_id'] ?? 0)): ?>
                                <button class="btn btn-outline-danger" title="Supprimer"
                                    onclick="deleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['login']) ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($utilisateurs)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Aucun utilisateur trouvé.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ===== MODAL : Créer / Modifier un utilisateur ===== -->
<div class="modal modal-blur fade" id="modalUser" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalUserTitle"><i class="fas fa-user-plus me-2"></i>Nouvel Utilisateur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="userId" value="0">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label required">Nom <sup class="text-danger">*</sup></label>
                        <input type="text" id="userNom" class="form-control" placeholder="Nom de famille">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Prénom</label>
                        <input type="text" id="userPrenom" class="form-control" placeholder="Prénom">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">Login <sup class="text-danger">*</sup></label>
                        <input type="text" id="userLogin" class="form-control" placeholder="login.utilisateur" autocomplete="off">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" id="userEmail" class="form-control" placeholder="email@exemple.fr">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">Rôle <sup class="text-danger">*</sup></label>
                        <select id="userRole" class="form-select" onchange="toggleSocietesSelect()">
                            <option value="lecteur">Lecteur</option>
                            <option value="admin">Admin</option>
                            <option value="superadmin">Super Admin</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Statut</label>
                        <select id="userActif" class="form-select">
                            <option value="1">✅ Actif</option>
                            <option value="0">❌ Inactif</option>
                        </select>
                    </div>
                    <div class="col-12" id="societesGroup">
                        <label class="form-label">Sociétés autorisées <small class="text-muted">(vide = toutes)</small></label>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($societes as $s): ?>
                            <label class="form-check d-flex align-items-center gap-1 border rounded px-2 py-1 cursor-pointer">
                                <input type="checkbox" class="form-check-input societe-check" value="<?= $s['id'] ?>">
                                <span><?= htmlspecialchars($s['nom']) ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label" id="passwordLabel">Mot de passe <sup class="text-danger" id="passwordRequired">*</sup></label>
                        <div class="input-group">
                            <input type="password" id="userPassword" class="form-control" placeholder="Minimum 6 caractères" autocomplete="new-password">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility()">
                                <i class="fas fa-eye" id="eyeIcon"></i>
                            </button>
                        </div>
                        <small class="text-muted" id="passwordHint"></small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="saveUser()">
                    <i class="fas fa-save me-1"></i><span id="saveBtnText">Créer</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===== MODAL : Changer mot de passe ===== -->
<div class="modal modal-blur fade" id="modalPassword" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-key me-2"></i>Changer le mot de passe</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="pwdUserId">
                <p class="text-muted mb-3">Compte : <strong id="pwdUserLogin"></strong></p>
                <div class="mb-2">
                    <label class="form-label">Nouveau mot de passe <sup class="text-danger">*</sup></label>
                    <input type="password" id="newPassword" class="form-control" placeholder="Min 6 caractères" autocomplete="new-password">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-warning" onclick="changePassword()">
                    <i class="fas fa-save me-1"></i>Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// ------- Gestion du modal Créer/Modifier -------
function openUserModal(user) {
    const modal = new bootstrap.Modal(document.getElementById('modalUser'));
    const isEdit = !!user;

    document.getElementById('modalUserTitle').innerHTML = isEdit
        ? '<i class="fas fa-user-edit me-2"></i>Modifier l\'utilisateur'
        : '<i class="fas fa-user-plus me-2"></i>Nouvel Utilisateur';
    document.getElementById('saveBtnText').textContent = isEdit ? 'Enregistrer' : 'Créer';
    document.getElementById('passwordRequired').style.display = isEdit ? 'none' : '';
    document.getElementById('passwordHint').textContent = isEdit ? 'Laisser vide pour conserver le mot de passe actuel.' : '';

    document.getElementById('userId').value     = user?.id    || 0;
    document.getElementById('userNom').value    = user?.nom   || '';
    document.getElementById('userPrenom').value = user?.prenom || '';
    document.getElementById('userLogin').value  = user?.login || '';
    document.getElementById('userEmail').value  = user?.email || '';
    document.getElementById('userRole').value   = user?.role  || 'lecteur';
    document.getElementById('userActif').value  = user?.actif !== undefined ? user.actif : 1;
    document.getElementById('userPassword').value = '';

    // Cocher les sociétés autorisées
    const ids = user?.societe_ids ? JSON.parse(user.societe_ids) : [];
    document.querySelectorAll('.societe-check').forEach(cb => {
        cb.checked = ids.includes(parseInt(cb.value));
    });

    toggleSocietesSelect();
    modal.show();
}

function toggleSocietesSelect() {
    const role = document.getElementById('userRole').value;
    document.getElementById('societesGroup').style.display = (role === 'superadmin') ? 'none' : '';
}

function togglePasswordVisibility() {
    const input = document.getElementById('userPassword');
    const icon = document.getElementById('eyeIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

function saveUser() {
    const fd = new FormData();
    fd.append('id',       document.getElementById('userId').value);
    fd.append('nom',      document.getElementById('userNom').value.trim());
    fd.append('prenom',   document.getElementById('userPrenom').value.trim());
    fd.append('login',    document.getElementById('userLogin').value.trim());
    fd.append('email',    document.getElementById('userEmail').value.trim());
    fd.append('role',     document.getElementById('userRole').value);
    fd.append('actif',    document.getElementById('userActif').value);
    fd.append('password', document.getElementById('userPassword').value);

    document.querySelectorAll('.societe-check:checked').forEach(cb => {
        fd.append('societe_ids[]', cb.value);
    });

    fetch('?action=save_user_ajax', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalUser')).hide();
                showToast(data.message, 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                showToast(data.message || 'Erreur.', 'danger');
            }
        });
}

// ------- Suppression -------
function deleteUser(id, login) {
    if (!confirm(`Supprimer le compte « ${login} » ? Cette action est irréversible.`)) return;
    const fd = new FormData();
    fd.append('id', id);
    fetch('?action=delete_user_ajax', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            showToast(data.message, data.success ? 'success' : 'danger');
            if (data.success) setTimeout(() => location.reload(), 800);
        });
}

// ------- Changement de mot de passe -------
function openPasswordModal(id, login) {
    document.getElementById('pwdUserId').value = id;
    document.getElementById('pwdUserLogin').textContent = login;
    document.getElementById('newPassword').value = '';
    new bootstrap.Modal(document.getElementById('modalPassword')).show();
}

function changePassword() {
    const fd = new FormData();
    fd.append('id',       document.getElementById('pwdUserId').value);
    fd.append('password', document.getElementById('newPassword').value);
    fetch('?action=change_password_ajax', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            bootstrap.Modal.getInstance(document.getElementById('modalPassword')).hide();
            showToast(data.message, data.success ? 'success' : 'danger');
        });
}

// ------- Toast de notification -------
function showToast(msg, type = 'success') {
    const colors = { success: '#2da44e', danger: '#e74c3c', warning: '#f39c12', info: '#3498db' };
    const toast = document.createElement('div');
    toast.style.cssText = `position:fixed;bottom:20px;right:20px;z-index:99999;background:${colors[type]};color:#fff;padding:0.8rem 1.2rem;border-radius:10px;box-shadow:0 4px 20px rgba(0,0,0,0.2);font-family:'Nunito',sans-serif;font-size:0.95rem;max-width:320px;`;
    toast.textContent = msg;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3500);
}
</script>

