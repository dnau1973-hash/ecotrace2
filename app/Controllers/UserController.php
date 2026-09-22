<?php
namespace App\Controllers;

use App\Config\Database;
use App\Models\SocieteModel;
use PDO;

class UserController {

    private $pdo;

    public function __construct() {
        $this->pdo = Database::getConnection();
    }

    /**
     * Affiche la liste des utilisateurs (superadmin uniquement)
     */
    public function manage() {
        if (!AuthController::isSuperAdmin()) {
            $_SESSION['flash_message'] = "Accès réservé au superadministrateur.";
            $_SESSION['flash_type']    = "danger";
            header("Location: ?");
            exit;
        }

        $societeModel = new SocieteModel();
        $societes     = $societeModel->getAll();

        $utilisateurs = $this->pdo
            ->query("SELECT * FROM utilisateurs ORDER BY role ASC, nom ASC")
            ->fetchAll(PDO::FETCH_ASSOC);

        $countEnAttente   = 0;
        $countValides     = 0;
        $countIntrouvables = 0;

        ob_start();
        require __DIR__ . '/../Views/admin/manage_users.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layout.php';
    }

    /**
     * Création ou mise à jour d'un utilisateur (AJAX POST JSON)
     */
    public function saveAjax() {
        header('Content-Type: application/json; charset=utf-8');

        if (!AuthController::isSuperAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Accès refusé.']);
            exit;
        }

        $id      = (int)($_POST['id'] ?? 0);
        $nom     = trim($_POST['nom']    ?? '');
        $prenom  = trim($_POST['prenom'] ?? '');
        $login   = trim($_POST['login']  ?? '');
        $email   = trim($_POST['email']  ?? '');
        $role    = $_POST['role'] ?? 'lecteur';
        $actif   = isset($_POST['actif']) ? (int)$_POST['actif'] : 1;
        $password = $_POST['password'] ?? '';

        // Validation des societes autorisées
        $societeIds = null;
        if ($role !== 'superadmin' && !empty($_POST['societe_ids']) && is_array($_POST['societe_ids'])) {
            $societeIds = json_encode(array_map('intval', $_POST['societe_ids']));
        }

        if (!in_array($role, ['superadmin', 'admin', 'lecteur'])) {
            echo json_encode(['success' => false, 'message' => 'Rôle invalide.']);
            exit;
        }

        if (empty($nom) || empty($login)) {
            echo json_encode(['success' => false, 'message' => 'Le nom et le login sont obligatoires.']);
            exit;
        }

        try {
            if ($id > 0) {
                // Mise à jour
                if (!empty($password)) {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $this->pdo->prepare("UPDATE utilisateurs SET nom=?, prenom=?, login=?, email=?, role=?, actif=?, societe_ids=?, password_hash=? WHERE id=?");
                    $stmt->execute([$nom, $prenom, $login, $email, $role, $actif, $societeIds, $hash, $id]);
                } else {
                    $stmt = $this->pdo->prepare("UPDATE utilisateurs SET nom=?, prenom=?, login=?, email=?, role=?, actif=?, societe_ids=? WHERE id=?");
                    $stmt->execute([$nom, $prenom, $login, $email, $role, $actif, $societeIds, $id]);
                }
                echo json_encode(['success' => true, 'message' => 'Utilisateur mis à jour.', 'id' => $id]);
            } else {
                // Création
                if (empty($password)) {
                    echo json_encode(['success' => false, 'message' => 'Le mot de passe est obligatoire pour un nouvel utilisateur.']);
                    exit;
                }
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $this->pdo->prepare("INSERT INTO utilisateurs (nom, prenom, login, email, password_hash, role, actif, societe_ids) VALUES (?,?,?,?,?,?,?,?)");
                $stmt->execute([$nom, $prenom, $login, $email, $hash, $role, $actif, $societeIds]);
                $newId = (int)$this->pdo->lastInsertId();
                echo json_encode(['success' => true, 'message' => 'Utilisateur créé.', 'id' => $newId]);
            }
        } catch (\PDOException $e) {
            $msg = (strpos($e->getMessage(), 'Duplicate') !== false)
                ? "Ce login est déjà utilisé par un autre compte."
                : "Erreur base de données : " . $e->getMessage();
            echo json_encode(['success' => false, 'message' => $msg]);
        }
        exit;
    }

    /**
     * Suppression d'un utilisateur (AJAX POST)
     */
    public function deleteAjax() {
        header('Content-Type: application/json; charset=utf-8');

        if (!AuthController::isSuperAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Accès refusé.']);
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);

        // Empêcher l'auto-suppression
        if ($id === (int)($_SESSION['ecotrace_user_id'] ?? 0)) {
            echo json_encode(['success' => false, 'message' => 'Vous ne pouvez pas supprimer votre propre compte.']);
            exit;
        }

        if ($id > 0) {
            $this->pdo->prepare("DELETE FROM utilisateurs WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Utilisateur supprimé.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'ID invalide.']);
        }
        exit;
    }

    /**
     * Changement de mot de passe (AJAX POST)
     */
    public function changePasswordAjax() {
        header('Content-Type: application/json; charset=utf-8');

        if (!AuthController::isSuperAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Accès refusé.']);
            exit;
        }

        $id       = (int)($_POST['id'] ?? 0);
        $password = $_POST['password'] ?? '';

        if ($id <= 0 || strlen($password) < 6) {
            echo json_encode(['success' => false, 'message' => 'ID invalide ou mot de passe trop court (min 6 caractères).']);
            exit;
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $this->pdo->prepare("UPDATE utilisateurs SET password_hash = ? WHERE id = ?")->execute([$hash, $id]);
        echo json_encode(['success' => true, 'message' => 'Mot de passe modifié.']);
        exit;
    }
}

