// Lance une recherche sur une API distante
async function lancerRechercheAjax(id, reload=false, api='gouv') {
    let nom = document.getElementById('input-nom-'+id).value; 
    let box = document.getElementById('feedback-'+id); 
    let apiLabel = 'Gouv.fr';
    
    box.style.color = "#3498db"; 
    box.innerText = "Recherche via " + apiLabel + " en cours... ⏳";
    
    let f = new FormData(); 
    f.append('action', 'ajax_search'); 
    f.append('source_id', id); 
    f.append('nouveau_nom', nom); 
    f.append('api_source', api);
    
    try { 
        let r = await fetch('index.php', {method:'POST', body:f}); 
        let rawText = await r.text();
        let d;
        try {
            d = JSON.parse(rawText);
        } catch(jsonErr) {
            console.error('[EcoTrace] Réponse non-JSON reçue:', rawText);
            box.style.color = "#e74c3c"; 
            box.innerText = "Erreur serveur : réponse inattendue."; 
            return;
        }

        box.style.color = d.success ? "#27ae60" : "#e74c3c"; 
        box.innerText = d.message || (d.success ? "Opération réussie." : "Erreur inconnue."); 
        
        if(d.success) {
            setTimeout(()=> { 
                if(reload) location.reload(); 
                else document.getElementById('card-'+id).style.display='none'; 
            }, 1500); 
        }
    } catch(e) { 
        console.error('[EcoTrace] Erreur fetch:', e);
        box.style.color = "#e74c3c"; 
        box.innerText = "Erreur de communication avec le serveur."; 
    }
}

// Supprime définitivement un enregistrement
async function supprimerEnregistrement(id) { 
    if(confirm("⚠️ Supprimer définitivement cet enregistrement et ses résultats ?")) { 
        let f = new FormData(); 
        f.append('action', 'delete_record'); 
        f.append('id', id); 
        await fetch('index.php', {method:'POST', body:f}); 
        location.reload(); 
    } 
}

function filtrerValidees() { 
    let input = document.getElementById('filterValideesInput').value.toLowerCase(); 
    let table = document.getElementById('table-validees'); 
    if (!table) return; 
    let tr = table.getElementsByTagName('tr'); 
    for (let i = 1; i < tr.length; i++) { 
        tr[i].style.display = ((tr[i].textContent || tr[i].innerText).toLowerCase().indexOf(input) > -1) ? "" : "none"; 
    } 
}

// Affichage des annonces BODACC et liens officiels dans une Modale
async function afficherBodacc(siren, nom) {
    if (!siren) {
        alert("SIREN indisponible pour cette entité.");
        return;
    }

    // Afficher la modale avec un spinner de chargement
    const modalElement = document.getElementById('modalBodacc');
    const modal = window.bootstrap ? new bootstrap.Modal(modalElement) : new bootstrap.Modal(modalElement);
    
    document.getElementById('bodacc-title').innerHTML = `<i class="ti ti-building-bank me-2"></i> ${nom} <span class="text-muted fs-5">(${siren})</span>`;
    const content = document.getElementById('bodacc-content');
    content.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><div class="mt-3 text-muted">Interrogation des journaux officiels en cours...</div></div>';
    
    modal.show();

    try {
        let res = await fetch(`https://bodacc-datadila.opendatasoft.com/api/records/1.0/search/?dataset=annonces-commerciales&q=${siren}&rows=5&sort=dateparution`);
        let data = await res.json();
        
        let html = '';

        if (!data.records || data.records.length === 0) {
            html += '<div class="alert alert-success"><h4 class="alert-title">✅ Aucune procédure collective en cours</h4><div class="text-muted">Aucune annonce de défaillance (redressement, liquidation) n\'a été trouvée récemment au BODACC pour ce SIREN.</div></div>';
        } else {
            html += '<div class="list-group list-group-flush mb-3">';
            data.records.forEach(r => {
                let fields = r.fields;
                html += `
                <div class="list-group-item">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-muted small mb-1"><i class="ti ti-calendar me-1"></i> ${fields.dateparution || 'Date inconnue'} &nbsp;|&nbsp; <i class="ti ti-building-monument me-1"></i> ${fields.tribunal || 'Tribunal non précisé'}</div>
                            <div class="font-weight-bold text-danger mb-1">${fields.typeavis_lib || fields.familleavis_lib || 'Annonce légale'}</div>
                            <div class="small">${fields.libelle_avis || 'Détails non fournis par l\'API.'}</div>
                        </div>
                    </div>
                </div>`;
            });
            html += '</div>';
        }
        
        // Ajout des liens externes
        html += `
            <div class="mt-4 pt-3 border-top text-center">
                <a href="https://www.bodacc.fr/pages/annonces-commerciales/?q.uniteLegale=${siren}" target="_blank" class="btn btn-outline-primary me-2">
                    <i class="ti ti-external-link me-1"></i> Fiche BODACC officielle
                </a>
                <a href="https://www.societe.com/societe/entreprise-${siren}.html" target="_blank" class="btn btn-outline-dark">
                    <i class="ti ti-building me-1"></i> Fiche Societe.com
                </a>
            </div>
        `;
        
        content.innerHTML = html;
        
    } catch (e) {
        content.innerHTML = '<div class="alert alert-danger">❌ Erreur lors de la communication avec les serveurs de l\'État.</div>';
    }
}

// Ouverture de la modale "Enrichir" et pré-remplissage des champs
function ouvrirModalEnrichir(sourceId, montant, poids) {
    document.getElementById('enrichir_source_id').value = sourceId;
    document.getElementById('enrichir_montant').value = montant;
    document.getElementById('enrichir_poids').value = poids;
    
    const modalElement = document.getElementById('modalEnrichir');
    const modal = window.bootstrap ? new bootstrap.Modal(modalElement) : new bootstrap.Modal(modalElement);
    modal.show();
}