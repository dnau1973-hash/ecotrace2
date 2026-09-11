# EcoTrace 🍃 v2.4.0 (Architecture MVC)

**Plateforme collaborative de bilan carbone & RSE multi-sociétés (Scopes 1, 2 et 3).**

EcoTrace permet aux entreprises et groupes multi-filiales de mesurer, piloter et réduire leurs émissions de gaz à effet de serre en conformité avec les méthodologies officielles de l'ADEME et du GHG Protocol.

---

## 🚀 Fonctionnalités Clés

### 1. 🏢 Multi-Sociétés & Consolidation de Groupe
- Gestion centralisée de la structure holding et des filiales.
- Vue consolidée globale ou filtrage instantané par entité avec recalcul en direct de tous les indicateurs.

### 2. 🏭 Scope 1 & 2 (Émissions Directes & Flotte Automobile)
- **Livre de bord des consommations** : Suivi des émissions stationnaires (gaz, fioul, électricité, réfrigérants).
- **Inventaire du parc roulant** : Gestion de la flotte de véhicules d'entreprise (immatriculations, carburants, consommation).
- **Référentiel Véhicules & Estimation Magique** : Interrogation directe de l'Open Data ADEME (Car Labelling) pour extraire la consommation WLTP exacte selon la marque, le modèle et le carburant.
- **Import kilométrique** : Téléversement CSV des relevés kilométriques annuels.

### 3. 🚚 Scope 3 (Achats & Fret - Émissions Indirectes)
- **Livre de bord des Achats & Fret** : Suivi des dépenses et transports avec filtrage par exercice comptable.
- **Import CSV Rapide par SIREN (3 colonnes)** : Importation avec pré-agrégation automatique par SIREN, support des formats numériques (espaces insécables, séparateurs de milliers) et barre de progression interactive en temps réel.
- **Clôture et Verrouillage d'Exercice** : Protection des données comptables certifiées contre toute modification pour les audits.
- **Inventaire permanent des Fournisseurs** : Qualification automatique via l'API Sirene de l'État (recherche d'entreprise, code NAF, géocodage haute précision BAN/OSM, distance routière, labels RSE ESS/Mission).
- **Gestion intégrée des codes NAF** : Référentiel sectoriel (Alimentaire, Transport, BTP, Numérique, Énergie, Industrie, Services).

### 4. 📑 Reporting & Bilan Carbone Officiel
- Génération automatique du rapport de synthèse Bilan Carbone officiel consolidant les Scopes 1, 2 et 3 par société et par exercice.
- Mise en page responsive et prête à imprimer / exporter en PDF.

---

## 🛠️ Installation & Prérequis

- **PHP 8.0+** avec extensions `pdo_mysql`, `curl`, `mbstring`.
- **MySQL / MariaDB 5.7+**.
- **Serveur Web** : Apache (avec `mod_rewrite`) ou Nginx avec document root pointant vers `public/`.

### Configuration rapide :

1. Cloner le dépôt :
   ```bash
   git clone https://github.com/dnau1973-hash/ecotrace2.git
   cd ecotrace2
   ```

2. Configurer l'environnement :
   ```bash
   cp .env.example .env
   # Renseignez vos identifiants de base de données dans .env
   ```

3. Lancer l'application :
   Accédez à `http://localhost/ecotrace2/public/` pour lancer l'assistant d'installation automatique.

---

## 📄 Licence

Projet open-source développé pour le pilotage de la transition écologique des entreprises.
