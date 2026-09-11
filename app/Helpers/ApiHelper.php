<?php
namespace App\Helpers;

use App\Config\Database;
use PDO;

class ApiHelper {
    
    /**
     * Géocodage via l'API officielle Base Adresse Nationale (BAN - data.gouv.fr)
     * Rapide, gratuit, ultra précis pour la France, et gère les centres-villes (type=municipality)
     */
    public static function geocoderAdresseBAN($query, $postcode = null, $type = null) {
        if (empty(trim($query))) return null;
        $url = "https://api-adresse.data.gouv.fr/search/?q=" . urlencode(trim($query)) . "&limit=1";
        if (!empty($postcode) && preg_match('/^[0-9]{5}$/', trim($postcode))) {
            $url .= "&postcode=" . urlencode(trim($postcode));
        }
        if (!empty($type)) {
            $url .= "&type=" . urlencode($type);
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'EcoTrace/2.0 (contact@ecotrace.app)');
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        $allowSelfSigned = (bool) Database::getEnv('APP_ALLOW_SELF_SIGNED', 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $allowSelfSigned ? false : true);
        $reponse = curl_exec($ch);
        $errNo = curl_errno($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if (!$errNo && $httpCode == 200 && $reponse) {
            $data = json_decode($reponse, true);
            if (!empty($data['features'][0]['geometry']['coordinates'])) {
                $lon = (float)$data['features'][0]['geometry']['coordinates'][0];
                $lat = (float)$data['features'][0]['geometry']['coordinates'][1];
                if ($lat != 0 || $lon != 0) {
                    return ['lat' => $lat, 'lon' => $lon];
                }
            }
        }
        return null;
    }

    /**
     * Géocodage via OpenStreetMap Nominatim
     */
    public static function geocoderAdresseOSM($adresse) {
        if (empty(trim($adresse))) return null;
        $url = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode(trim($adresse)) . "&limit=1";
        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL, $url); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'EcoTrace/2.0 (contact@ecotrace.app)'); 
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        $allowSelfSigned = (bool) Database::getEnv('APP_ALLOW_SELF_SIGNED', 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $allowSelfSigned ? false : true);
        $reponse = curl_exec($ch); 
        $errNo = curl_errno($ch); 
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($errNo || $httpCode != 200) { return null; }
        if ($reponse) {
            $data = json_decode($reponse, true);
            if (!empty($data[0]['lat']) && !empty($data[0]['lon'])) {
                $lat = (float)$data[0]['lat'];
                $lon = (float)$data[0]['lon'];
                if ($lat != 0 || $lon != 0) {
                    return ['lat' => $lat, 'lon' => $lon];
                }
            }
        }
        return null;
    }

    /**
     * Géocode spécifiquement le centre d'une ville (commune) avec ou sans code postal
     */
    public static function geocoderCentreVille($ville, $codePostal = null) {
        $ville = trim((string)$ville);
        if (empty($ville) && empty($codePostal)) return null;

        // Nettoyer les mentions parasites de la ville (CEDEX, BP, CS, etc.)
        $villeNettoyee = trim(preg_replace('/\b(CEDEX\b.*|BP\b.*|CS\b.*|TSA\b.*|[0-9]+)/i', '', $ville));
        if (empty($villeNettoyee) && !empty($ville)) $villeNettoyee = $ville;

        // 1. Essai BAN avec type municipality (centre exact de la commune)
        if (!empty($villeNettoyee)) {
            $coords = self::geocoderAdresseBAN($villeNettoyee, $codePostal, 'municipality');
            if ($coords) return $coords;
        }

        // 2. Essai BAN recherche simple ville + CP
        $query = trim(($codePostal ? $codePostal . ' ' : '') . $villeNettoyee);
        if (!empty($query)) {
            $coords = self::geocoderAdresseBAN($query, $codePostal);
            if ($coords) return $coords;
        }

        // 3. Essai OSM Nominatim ciblé ville
        $queryOsm = trim(($codePostal ? $codePostal . ' ' : '') . $villeNettoyee . ', France');
        $coords = self::geocoderAdresseOSM($queryOsm);
        if ($coords) return $coords;

        return null;
    }

    /**
     * Extraction intelligente de la ville et du code postal depuis une adresse textuelle
     */
    public static function extraireVilleEtCodePostal($adresse) {
        if (empty(trim((string)$adresse))) return ['ville' => null, 'cp' => null];

        $adresseStr = trim((string)$adresse);

        // 1. Recherche code postal français à 5 chiffres (ex: 75008, 33000, 24000)
        if (preg_match('/\b(0[1-9]|[1-8][0-9]|9[0-8]|2[AB])[0-9]{3}\b/i', $adresseStr, $matches, PREG_OFFSET_CAPTURE)) {
            $cp = $matches[0][0];
            $pos = $matches[0][1] + strlen($cp);
            $apresCp = trim(substr($adresseStr, $pos));
            
            // La commune se trouve généralement juste après le code postal
            $ville = preg_replace('/\b(CEDEX\b.*|BP\b.*|CS\b.*|TSA\b.*)/i', '', $apresCp);
            $ville = trim(preg_replace('/[^A-Za-zÀ-ÖØ-öø-ÿ\s\-\']/u', '', $ville));
            
            if (!empty($ville)) {
                return ['ville' => $ville, 'cp' => $cp];
            }
            return ['ville' => null, 'cp' => $cp];
        }

        // 2. Si virgule dans l'adresse, la ville est souvent le dernier segment
        if (strpos($adresseStr, ',') !== false) {
            $parts = explode(',', $adresseStr);
            $dernierePartie = trim(end($parts));
            if (!empty($dernierePartie)) {
                $ville = trim(preg_replace('/\b(CEDEX\b.*|BP\b.*|CS\b.*|TSA\b.*|[0-9]+)/i', '', $dernierePartie));
                if (!empty($ville)) {
                    return ['ville' => $ville, 'cp' => null];
                }
            }
        }

        // 3. Derniers mots de l'adresse
        $words = preg_split('/\s+/', $adresseStr);
        if (count($words) >= 1) {
            $last = end($words);
            if (strlen($last) >= 3 && !is_numeric($last)) {
                return ['ville' => $last, 'cp' => null];
            }
        }

        return ['ville' => null, 'cp' => null];
    }

    /**
     * Recherche d'adresse via le SIREN auprès de l'API Recherche Entreprises
     */
    public static function recupererAdresseParSiren($siren) {
        if (empty($siren)) return null;
        $url = "https://recherche-entreprises.api.gouv.fr/search?q=" . urlencode(trim($siren)) . "&page=1&per_page=1";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        $allowSelfSigned = (bool) Database::getEnv('APP_ALLOW_SELF_SIGNED', 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $allowSelfSigned ? false : true);
        $res = curl_exec($ch);
        curl_close($ch);
        if ($res) {
            $data = json_decode($res, true);
            if (!empty($data['results'][0]['siege']['adresse'])) {
                return $data['results'][0]['siege']['adresse'];
            }
            if (!empty($data['results'][0]['siege']['libelle_commune'])) {
                $cp = $data['results'][0]['siege']['code_postal'] ?? '';
                return trim($cp . ' ' . $data['results'][0]['siege']['libelle_commune']);
            }
        }
        return null;
    }

    /**
     * Géocodage intelligent complet :
     * 1. Adresse complète exacte (BAN puis OSM)
     * 2. Si échec : Détection et repli sur le CENTRE DE LA VILLE (Commune)
     */
    public static function geocodeSmarter($adresse) {
        if (empty(trim((string)$adresse))) return null;

        $adresseStr = trim((string)$adresse);

        // Nettoyage de l'adresse (enlever les BP, CEDEX qui perturbent les géocodeurs d'adresses précises)
        $adresseNettoyee = preg_replace('/\b(BP\s*[0-9]+|CS\s*[0-9]+|TSA\s*[0-9]+)\b/i', '', $adresseStr);
        $adresseNettoyee = trim(preg_replace('/\s+/', ' ', $adresseNettoyee));

        // 1. Essai de l'adresse complète avec la Base Adresse Nationale (BAN)
        $coords = self::geocoderAdresseBAN($adresseNettoyee);
        if ($coords) return $coords;

        // 2. Essai de l'adresse complète avec OpenStreetMap Nominatim
        $coords = self::geocoderAdresseOSM($adresseNettoyee);
        if ($coords) return $coords;

        // 3. REPLI CENTRE-VILLE : Si l'adresse exacte échoue, positionner au centre de la ville
        $infosVille = self::extraireVilleEtCodePostal($adresseStr);
        if (!empty($infosVille['ville']) || !empty($infosVille['cp'])) {
            $coordsCentre = self::geocoderCentreVille($infosVille['ville'], $infosVille['cp']);
            if ($coordsCentre) return $coordsCentre;
        }

        // 4. Dernier recours : Code postal seul vers BAN
        if (!empty($infosVille['cp'])) {
            $coordsCp = self::geocoderAdresseBAN($infosVille['cp'], $infosVille['cp'], 'municipality');
            if ($coordsCp) return $coordsCp;
        }

        return null;
    }

    public static function calculerDistanceVolDOiseau($lat1, $lon1, $lat2, $lon2) {
        $earth_radius = 6371; $dLat = deg2rad($lat2 - $lat1); $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        return round($earth_radius * (2 * asin(sqrt($a))), 2);
    }

    public static function getOrigineCoordinates($sourceId = null, $societeId = null) {
        $pdo = Database::getConnection();
        
        // 1. Si une societe_id directe est passée
        if ($societeId && $societeId !== 'all') {
            $stmt = $pdo->prepare("SELECT latitude, longitude FROM societes WHERE id = :id");
            $stmt->execute([':id' => (int)$societeId]);
            $soc = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($soc && !empty($soc['latitude']) && !empty($soc['longitude']) && ((float)$soc['latitude'] != 0 || (float)$soc['longitude'] != 0)) {
                return ['lat' => (float)$soc['latitude'], 'lon' => (float)$soc['longitude']];
            }
        }

        // 2. Si un sourceId est passé, chercher la société associée dans sources_csv
        if ($sourceId) {
            $stmt = $pdo->prepare("SELECT s.latitude, s.longitude FROM societes s JOIN sources_csv sc ON s.id = sc.societe_id WHERE sc.id = :id");
            $stmt->execute([':id' => (int)$sourceId]);
            $soc = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($soc && !empty($soc['latitude']) && !empty($soc['longitude']) && ((float)$soc['latitude'] != 0 || (float)$soc['longitude'] != 0)) {
                return ['lat' => (float)$soc['latitude'], 'lon' => (float)$soc['longitude']];
            }
        }

        // 3. Chercher la société active en session
        $activeSocId = $_SESSION['active_societe_id'] ?? null;
        if ($activeSocId && $activeSocId !== 'all') {
            $stmt = $pdo->prepare("SELECT latitude, longitude FROM societes WHERE id = :id");
            $stmt->execute([':id' => (int)$activeSocId]);
            $soc = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($soc && !empty($soc['latitude']) && !empty($soc['longitude']) && ((float)$soc['latitude'] != 0 || (float)$soc['longitude'] != 0)) {
                return ['lat' => (float)$soc['latitude'], 'lon' => (float)$soc['longitude']];
            }
        }

        // 4. Chercher la société par défaut en base de données
        $stmt = $pdo->query("SELECT latitude, longitude FROM societes WHERE est_defaut = 1 AND latitude IS NOT NULL AND latitude != 0 LIMIT 1");
        $soc = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
        if ($soc && !empty($soc['latitude']) && !empty($soc['longitude']) && ((float)$soc['latitude'] != 0 || (float)$soc['longitude'] != 0)) {
            return ['lat' => (float)$soc['latitude'], 'lon' => (float)$soc['longitude']];
        }

        // 5. Chercher n'importe quelle société ayant des coordonnées en base de données
        $stmt = $pdo->query("SELECT latitude, longitude FROM societes WHERE latitude IS NOT NULL AND latitude != 0 AND longitude IS NOT NULL AND longitude != 0 ORDER BY id ASC LIMIT 1");
        $soc = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
        if ($soc && !empty($soc['latitude']) && !empty($soc['longitude']) && ((float)$soc['latitude'] != 0 || (float)$soc['longitude'] != 0)) {
            return ['lat' => (float)$soc['latitude'], 'lon' => (float)$soc['longitude']];
        }

        // 6. Coordonnées de secours en France
        return ['lat' => 45.19165526, 'lon' => 0.76262712];
    }

    public static function calculerDistance($lat1, $lon1, $lat2, $lon2) {
        $lat1 = (float)$lat1; $lon1 = (float)$lon1;
        $lat2 = (float)$lat2; $lon2 = (float)$lon2;
        
        // Si l'origine est 0,0, récupérer depuis la base de données
        if ($lat1 == 0 && $lon1 == 0) {
            $orig = self::getOrigineCoordinates();
            $lat1 = $orig['lat'];
            $lon1 = $orig['lon'];
        }
        if ($lat2 == 0 && $lon2 == 0) {
            return null;
        }

        $url = "https://router.project-osrm.org/route/v1/driving/{$lon1},{$lat1};{$lon2},{$lat2}?overview=false";
        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL, $url); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'EcoTrace/2.0 (contact@ecotrace.app)'); 
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        $allowSelfSigned = (bool) Database::getEnv('APP_ALLOW_SELF_SIGNED', 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $allowSelfSigned ? false : true);
        $reponse = curl_exec($ch); 
        $errNo = curl_errno($ch); 
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); 
        curl_close($ch);
        
        if (!$errNo && $httpCode == 200 && $reponse) {
            $donnees = json_decode($reponse, true);
            if (isset($donnees['routes'][0]['distance'])) {
                return round($donnees['routes'][0]['distance'] / 1000, 2);
            }
        }
        $volOiseau = self::calculerDistanceVolDOiseau($lat1, $lon1, $lat2, $lon2);
        return round($volOiseau * 1.25, 2);
    }

    public static function calculerDistanceDepuisAdresse($adresse, $apiResultatId = null) {
        $pdo = Database::getConnection();
        $destLat = null;
        $destLon = null;
        $sourceId = null;

        if ($apiResultatId) {
            $stmtAr = $pdo->prepare("SELECT source_id, latitude, longitude FROM api_resultats WHERE id = :id");
            $stmtAr->execute([':id' => $apiResultatId]);
            $ar = $stmtAr->fetch(PDO::FETCH_ASSOC);
            if ($ar) {
                $sourceId = $ar['source_id'];
                if (!empty($ar['latitude']) && !empty($ar['longitude']) && ((float)$ar['latitude'] != 0 || (float)$ar['longitude'] != 0)) {
                    $destLat = (float)$ar['latitude'];
                    $destLon = (float)$ar['longitude'];
                }
            }
        }

        if (!$destLat || !$destLon) {
            $coords = self::geocodeSmarter($adresse);
            if (!$coords) return null;
            $destLat = $coords['lat'];
            $destLon = $coords['lon'];
        }
        
        $origine = self::getOrigineCoordinates($sourceId);
        return self::calculerDistance($origine['lat'], $origine['lon'], $destLat, $destLon);
    }

    public static function determinerOrigineGeo($distance, $adresse) {
        if ($distance === null || $distance === '') return 'Inconnue';
        if ($distance <= 100) return 'Alliance Locale';
        if ($distance <= 150) return 'Régionale';
        $adresseUpper = strtoupper(trim($adresse));
        $paysEtrangers = ['BELGIQUE', 'ESPAGNE', 'ALLEMAGNE', 'SUISSE', 'ITALIE', 'ROYAUME-UNI', 'IRLANDE', 'PAYS-BAS', 'PORTUGAL', 'LUXEMBOURG', 'ETATS-UNIS', 'USA', 'POLSKA', 'POLOGNE', 'CHINE', 'JAPON'];
        $isEtranger = false;
        foreach ($paysEtrangers as $pays) { if (strpos($adresseUpper, $pays) !== false) { $isEtranger = true; break; } }
        if (!$isEtranger && !preg_match('/\b[0-9]{5}\b/', $adresseUpper)) { $isEtranger = true; }
        if (preg_match('/FRANCE$/', $adresseUpper)) { $isEtranger = false; }
        return $isEtranger ? 'Internationale' : 'Nationale';
    }

    public static function getLibelleNafLocal($codeNaf) {
        if (empty($codeNaf)) return "Non renseigné";
        $cleanCode = strtoupper(trim(str_replace('.', '', $codeNaf)));
        if (strlen($cleanCode) === 5) $cleanCode = substr($cleanCode, 0, 2) . '.' . substr($cleanCode, 2);
        
        $pdo = Database::getConnection();
        try {
            $stmt = $pdo->prepare("SELECT libelle FROM codes_naf WHERE UPPER(REPLACE(code, '.', '')) = :code LIMIT 1");
            $stmt->execute([':code' => str_replace('.', '', $cleanCode)]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($res && !empty($res['libelle'])) return $res['libelle'];
        } catch (\Exception $e) {}
        
        $divs = ['01' => 'Agriculture', '10' => 'Industrie alimentaire', '41' => 'Construction', '45' => 'Commerce auto', '46' => 'Commerce gros', '47' => 'Commerce détail', '49' => 'Transports', '55' => 'Hébergement', '56' => 'Restauration', '62' => 'Informatique'];
        return $divs[substr($cleanCode, 0, 2)] ?? "Secteur d'activité ($cleanCode)";
    }

    public static function estAlimentaire($codeNaf) {
        if (empty($codeNaf) || strlen($codeNaf) < 2) return 0;
        $division = substr($codeNaf, 0, 2); $groupe = substr($codeNaf, 0, 4);
        if (in_array($division, ['10', '11', '56']) || in_array($groupe, ['46.3', '47.1', '47.2'])) return 1;
        return 0;
    }

    public static function determinerSanteJuridique($entreprise) {
        $etat = $entreprise['etat_administratif'] ?? 'A'; 
        if ($etat === 'C' || $etat === 'F') return 'Fermée';
        $siren = $entreprise['siren'] ?? null;
        if (!$siren) return 'Actif';
        $url = 'https://bodacc-datadila.opendatasoft.com/api/records/1.0/search/?dataset=annonces-commerciales&q=' . urlencode($siren) . '&rows=1&sort=dateparution';
        $ch = curl_init(); curl_setopt($ch, CURLOPT_URL, $url); curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); curl_setopt($ch, CURLOPT_USERAGENT, 'EcoTrace/2.0'); curl_setopt($ch, CURLOPT_TIMEOUT, 4);
        $allowSelfSigned = (bool) Database::getEnv('APP_ALLOW_SELF_SIGNED', 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $allowSelfSigned ? false : true);
        $reponse = curl_exec($ch); $errNo = curl_errno($ch); $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); $errMsg = curl_error($ch); curl_close($ch);
        if ($errNo || $httpCode !== 200 || !$reponse) { return 'Actif'; }
        if ($httpCode == 200 && $reponse) {
            $donnees = json_decode($reponse, true);
            if (!empty($donnees['records'][0]['fields'])) {
                $famille = strtolower($donnees['records'][0]['fields']['familleavis_lib'] ?? '');
                if (strpos($famille, 'collective') !== false) {
                    $txt = strtolower(($donnees['records'][0]['fields']['typeavis_lib'] ?? '') . ' ' . ($donnees['records'][0]['fields']['libelle_avis'] ?? ''));
                    if (strpos($txt, 'liquidation') !== false) return 'Liquidation';
                    if (strpos($txt, 'redressement') !== false) return 'Redressement';
                    if (strpos($txt, 'sauvegarde') !== false) return 'Sauvegarde';
                    return 'En difficulté';
                }
            }
        }
        return 'Actif';
    }
}