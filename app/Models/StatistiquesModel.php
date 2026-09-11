<?php
namespace App\Models;

use App\Helpers\EcoHelper;
use App\Helpers\ApiHelper;

class StatistiquesModel {

    public static function calculerStatsRse(array $sourcesValides): array {
        $stats = [
            'totalCO2' => 0,
            'totalAchats' => 0,
            'totalPoids' => 0,
            'alimCount' => 0,
            'nonAlimCount' => 0,
            'countValides' => count($sourcesValides),
            'countEss' => 0,
            'countRisque' => 0,
            'geoCounts' => ['Alliance Locale' => 0, 'Régionale' => 0, 'Nationale' => 0, 'Internationale' => 0],
            'mapMarkers' => [],
            'top10Labels' => [],
            'top10Values' => []
        ];

        $secteursData = [];

        foreach ($sourcesValides as $v) {
            $co2 = EcoHelper::estimerCO2($v['activite_principale'], $v['montant'], $v['poids'], $v['distance']);
            $stats['totalCO2'] += $co2;
            $stats['totalAchats'] += $v['montant'];
            $stats['totalPoids'] += $v['poids'];

            if ($v['est_alimentaire']) {
                $stats['alimCount']++;
            } else {
                $stats['nonAlimCount']++;
            }

            if (!empty($v['est_ess'])) {
                $stats['countEss']++;
            }

            $sJ = $v['statut_juridique'] ?? 'Actif';
            if ($sJ === 'Fermée' || strpos($sJ, 'Liquidation') !== false || strpos($sJ, 'Redressement') !== false || $sJ === 'Radiée') {
                $stats['countRisque']++;
            }

            // --- CORRECTION : Décompte par NOMBRE DE FOURNISSEURS ---
            $secteurNom = !empty($v['activite_principale_libelle']) ? $v['activite_principale_libelle'] : ($v['activite_principale'] ?? 'Non renseigné');
            if (!isset($secteursData[$secteurNom])) {
                $secteursData[$secteurNom] = 0;
            }
            $secteursData[$secteurNom] += 1; // On incrémente de 1 par entreprise au lieu de la somme des montants
            // --------------------------------------------------------

            $orig = $v['origine_geo'] ?? 'Inconnue';
            if ($orig === 'Inconnue' && $v['distance'] !== null) {
                $orig = ApiHelper::determinerOrigineGeo($v['distance'], $v['siege_adresse']);
            }
            if (isset($stats['geoCounts'][$orig])) {
                $stats['geoCounts'][$orig]++;
            }

            if (!empty($v['latitude']) && !empty($v['longitude'])) {
                $stats['mapMarkers'][] = [
                    'nom'  => $v['nom_complet'],
                    'lat'  => $v['latitude'],
                    'lon'  => $v['longitude'],
                    'co2'  => $co2,
                    'alim' => (bool)$v['est_alimentaire']
                ];
            }
        }

        arsort($secteursData);
        $top10 = array_slice($secteursData, 0, 10, true);
        $stats['top10Labels'] = array_keys($top10);
        $stats['top10Values'] = array_values($top10);

        return $stats;
    }
}