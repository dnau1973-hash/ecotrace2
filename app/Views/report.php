<div class="container-xl">
    <div class="page-header d-print-none text-white">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">Édition</div>
                <h2 class="page-title"><i class="fas fa-file-pdf me-2"></i> Rapport Automatique (Bilan Carbone)</h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <button onclick="window.print()" class="btn btn-primary btn-pill">
                    <i class="fas fa-print me-2"></i> Imprimer / PDF
                </button>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <!-- FILTERS (hidden on print) -->
        <div class="card mb-4 d-print-none">
            <div class="card-body">
                <form method="GET" action="" class="row align-items-end">
                    <input type="hidden" name="action" value="report">
                    <div class="col-md-4">
                        <label class="form-label">Filiale à consolider</label>
                        <select name="societe_id" class="form-select">
                            <option value="all" <?= $socFiltre == 'all' ? 'selected' : '' ?>>-- Groupe Global (Toutes les filiales) --</option>
                            <?php foreach($societes as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= $socFiltre == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Année d'exercice</label>
                        <input type="number" name="annee" class="form-control" value="<?= htmlspecialchars($anneeFiltre) ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100 btn-pill">Générer</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- PRINTABLE REPORT -->
        <div class="card p-5" id="printableReport">
            <div class="text-center mb-5">
                <h1 class="display-4 fw-bold">Bilan Carbone <?= htmlspecialchars($anneeFiltre) ?></h1>
                <p class="text-muted fs-3">
                    <?php if($socFiltre === 'all'): ?>
                        Consolidation Globale du Groupe
                    <?php else: ?>
                        Filiale : 
                        <?php 
                            $nomFiliale = 'Inconnue';
                            foreach($societes as $s) { if($s['id'] == $socFiltre) $nomFiliale = $s['nom']; }
                            echo htmlspecialchars($nomFiliale);
                        ?>
                    <?php endif; ?>
                </p>
            </div>

            <?php $totalGlobal = $totalScope1 + $totalScope3; ?>

            <div class="row row-cards mb-5">
                <div class="col-sm-6 col-lg-4">
                    <div class="card card-sm bg-blue text-white">
                        <div class="card-body text-center">
                            <div class="fs-4">Émissions Totales (S1, S2, S3)</div>
                            <div class="display-5 fw-bold mt-2"><?= number_format($totalGlobal, 2, ',', ' ') ?> kg</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-4">
                    <div class="card card-sm bg-indigo text-white">
                        <div class="card-body text-center">
                            <div class="fs-4">Scope 1 & 2</div>
                            <div class="display-5 fw-bold mt-2"><?= number_format($totalScope1, 2, ',', ' ') ?> kg</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-4">
                    <div class="card card-sm bg-purple text-white">
                        <div class="card-body text-center">
                            <div class="fs-4">Scope 3 (Achats Validés)</div>
                            <div class="display-5 fw-bold mt-2"><?= number_format($totalScope3, 2, ',', ' ') ?> kg</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-5">
                <div class="col-md-6">
                    <h3 class="mb-3">Répartition Globale (Par Scope)</h3>
                    <canvas id="chartScopes" style="max-height:300px;"></canvas>
                </div>
                <div class="col-md-6">
                    <h3 class="mb-3">Répartition Scope 3 (Top Secteurs)</h3>
                    <canvas id="chartS3Secteurs" style="max-height:300px;"></canvas>
                </div>
            </div>

            <h3 class="mb-3 mt-4 border-bottom pb-2">Détail du Scope 1 & 2</h3>
            <?php if(empty($scope1Data)): ?>
                <p class="text-muted">Aucune donnée pour cette période.</p>
            <?php else: ?>
                <table class="table table-vcenter">
                    <thead><tr><th>Catégorie</th><th class="text-end">Émissions (kg CO2e)</th></tr></thead>
                    <tbody>
                        <?php foreach($scope1Data as $d): ?>
                            <tr>
                                <td><?= htmlspecialchars($d['categorie']) ?></td>
                                <td class="text-end fw-bold text-orange"><?= number_format($d['total_co2'], 2, ',', ' ') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <h3 class="mb-3 mt-5 border-bottom pb-2">Top 10 des Fournisseurs les plus émissifs (Scope 3)</h3>
            <?php if(empty($topFournisseurs)): ?>
                <p class="text-muted">Aucun fournisseur validé pour cette période.</p>
            <?php else: ?>
                <table class="table table-vcenter">
                    <thead><tr><th>Fournisseur</th><th>Secteur NAF</th><th class="text-end">Dépense / Poids</th><th class="text-end">Émissions (kg CO2e)</th></tr></thead>
                    <tbody>
                        <?php foreach($topFournisseurs as $f): ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($f['fournisseur']) ?></td>
                                <td class="text-muted"><?= htmlspecialchars($f['secteur']) ?></td>
                                <td class="text-end">
                                    <?php if($f['montant'] > 0) echo number_format($f['montant'], 2, ',', ' ') . ' €'; ?>
                                    <?php if($f['poids'] > 0) echo number_format($f['poids'], 2, ',', ' ') . ' kg'; ?>
                                </td>
                                <td class="text-end fw-bold text-orange"><?= number_format($f['total_co2'], 2, ',', ' ') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Chart 1: Scopes
    new Chart(document.getElementById('chartScopes'), {
        type: 'doughnut',
        data: {
            labels: ['Scope 1 & 2', 'Scope 3'],
            datasets: [{
                data: [<?= $totalScope1 ?>, <?= $totalScope3 ?>],
                backgroundColor: ['#667382', '#a033b1'] // Indigo/Purple shades
            }]
        }
    });

    // Chart 2: Sectors Scope 3
    <?php
        $lbls = [];
        $vals = [];
        foreach($topSecteurs as $sec => $co2) {
            $lbls[] = $sec;
            $vals[] = $co2;
        }
    ?>
    new Chart(document.getElementById('chartS3Secteurs'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($lbls) ?>,
            datasets: [{
                label: 'kg CO2e',
                data: <?= json_encode($vals) ?>,
                backgroundColor: '#206bc4'
            }]
        },
        options: {
            indexAxis: 'y',
            plugins: { legend: { display: false } }
        }
    });
});
</script>

<style>
@media print {
    body { background: #fff !important; }
    .navbar, .page-header, .btn, form { display: none !important; }
    .card { border: none !important; box-shadow: none !important; }
    #printableReport { padding: 0 !important; }
    canvas { max-width: 100% !important; }
}
</style>
