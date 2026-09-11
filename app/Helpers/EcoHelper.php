<?php
namespace App\Helpers;

use App\Config\Database;
use PDO;

class EcoHelper {
    
    private static $ademeCache = null;
    
    // Initialise le cache des facteurs ADEME depuis la BDD
    private static function initAdemeCache() {
        if (self::$ademeCache === null) {
            self::$ademeCache = [];
            try {
                $db = Database::getConnection();
                $stmt = $db->query("SELECT code_naf, facteur FROM ademe_facteurs");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    self::$ademeCache[$row['code_naf']] = (float)$row['facteur'];
                }
            } catch (\Exception $e) {
                // Ignore silent DB errors here, fallback will be used
            }
        }
    }

    // Calcule l'empreinte carbone estimée
    public static function estimerCO2($codeNaf, $montant, $poids, $distance) {
        $co2 = 0;
        
        // Calcul Fret (Tonnes-Kilomètres)
        if ($poids > 0 && $distance > 0) {
            $tkm = ($poids / 1000) * $distance;
            $co2 += ($tkm * 0.1);
        }
        
        // Calcul Monétaire (Base Empreinte ADEME)
        if ($montant > 0) {
            $codeNaf = (string)$codeNaf;
            self::initAdemeCache();
            
            $facteur = 0.2; // Valeur par défaut si introuvable
            
            if (isset(self::$ademeCache[$codeNaf])) {
                // Utilisation du facteur précis de la base
                $facteur = self::$ademeCache[$codeNaf];
            } else {
                // Fallback de sécurité "historique" (division sur 2 chiffres)
                $div = substr($codeNaf, 0, 2);
                if (in_array($div, ['10','11','56'])) $facteur = 0.45; 
                elseif (in_array($div, ['49','50','51','52'])) $facteur = 0.8; 
                elseif (in_array($div, ['61','62','63','69','70','71'])) $facteur = 0.05;
            }
            
            $co2 += ($montant * $facteur);
        }
        
        return round($co2, 2);
    }

    /**
     * Nettoie et convertit une chaîne représentant un nombre (montant, poids, etc.) en float.
     * Gère les formats français, internationaux, espaces insécables (\u00A0, \u202F), symboles monétaires (€, $, £, EUR), etc.
     */
    public static function cleanDecimal($val) {
        if (is_numeric($val)) {
            return (float)$val;
        }
        $val = trim((string)$val);
        if ($val === '') {
            return 0.0;
        }

        // Supprimer tout caractère qui n'est pas un chiffre, un point, une virgule ou un signe moins/plus
        // (élimine espaces ordinaires, espaces insécables UTF-8 \xC2\xA0, \xE2\x80\xAF, \xE2\x80\x87, symboles monétaires, etc.)
        $val = preg_replace('/[^\d.,+-]/u', '', $val);
        if ($val === '' || $val === '-' || $val === '+') {
            return 0.0;
        }

        // Format français avec séparateur de milliers sous forme de point : 1.163.022,52
        if (preg_match('/^[+-]?\d{1,3}(\.\d{3})*,\d+$/', $val)) {
            $val = str_replace('.', '', $val);
            $val = str_replace(',', '.', $val);
        }
        // Format anglo-saxon avec virgule pour milliers : 1,163,022.52
        elseif (preg_match('/^[+-]?\d{1,3}(,\d{3})*\.\d+$/', $val)) {
            $val = str_replace(',', '', $val);
        }
        // Format standard avec virgule décimale : 1163022,52
        else {
            $val = str_replace(',', '.', $val);
        }

        return floatval($val);
    }

    // Détermine le grand secteur d'activité à partir du code NAF
    public static function getSectorFromNaf($code) {
        $clean = preg_replace('/[^0-9]/', '', (string)$code);
        $div = substr($clean, 0, 2);
        $grp3 = substr($clean, 0, 3);
        $grp4 = substr($clean, 0, 4);

        if (in_array($div, ['01', '02', '03', '10', '11', '56']) || in_array($grp3, ['462', '463', '472', '478']) || in_array($grp4, ['4711'])) {
            return ['id' => 'alimentaire', 'label' => 'Alimentaire', 'icon' => 'fas fa-apple-alt', 'badge' => 'bg-yellow text-white'];
        }
        if (in_array($div, ['49', '50', '51', '52', '53'])) {
            return ['id' => 'transport', 'label' => 'Transport & Fret', 'icon' => 'fas fa-truck', 'badge' => 'bg-blue text-white'];
        }
        if (in_array($div, ['35', '36', '37', '38', '39'])) {
            return ['id' => 'energie', 'label' => 'Énergie & Déchets', 'icon' => 'fas fa-bolt', 'badge' => 'bg-teal text-white'];
        }
        if (in_array($div, ['41', '42', '43'])) {
            return ['id' => 'btp', 'label' => 'BTP & Construction', 'icon' => 'fas fa-hard-hat', 'badge' => 'bg-orange text-white'];
        }
        if (in_array($div, ['58', '59', '60', '61', '62', '63'])) {
            return ['id' => 'numerique', 'label' => 'Numérique & Tech', 'icon' => 'fas fa-laptop-code', 'badge' => 'bg-purple text-white'];
        }
        if (in_array($div, ['13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23', '24', '25', '26', '27', '28', '29', '30', '31', '32', '33'])) {
            return ['id' => 'industrie', 'label' => 'Industrie & Fab.', 'icon' => 'fas fa-industry', 'badge' => 'bg-secondary text-white'];
        }
        if (in_array($div, ['69', '70', '71', '72', '73', '74', '77', '78', '80', '81', '82'])) {
            return ['id' => 'conseil', 'label' => 'Services & Conseil', 'icon' => 'fas fa-briefcase', 'badge' => 'bg-azure text-white'];
        }
        return ['id' => 'autre', 'label' => 'Autre', 'icon' => 'fas fa-tag', 'badge' => 'bg-light text-muted'];
    }
}
