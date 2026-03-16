# FDAP Portfolio

Extension WordPress pour Fiches d'Activités Pédagogiques (FDAP) - Portfolio étudiant.

## Caractéristiques

- **Autonome** : Aucune dépendance à ACF ou autre plugin
- **Complet** : 19 champs + 6 photos + multimédia
- **Responsive** : Design mobile-first avec CSS moderne
- **Compression images** : Automatique à 300KB max
- **Connexion intégrée** : Formulaire de login stylisé

---

## Installation

### 1. Uploader le plugin

```bash
# Méthode 1 : SCP
scp -r fdap-portfolio user@serveur:/var/www/html/wordpress/wp-content/plugins/

# Méthode 2 : Via SSH
ssh user@serveur
cd /var/www/html/wordpress/wp-content/plugins/
git clone https://github.com/rpfe26/fdap-portfolio.git fdap-portfolio
```

### 2. Activer le plugin

Dans WordPress Admin : `Extensions > FDAP Portfolio > Activer`

Ou via WP-CLI :
```bash
wp plugin activate fdap-portfolio --allow-root
```

### 3. Vérifier les pages

Le plugin crée automatiquement deux pages à l'activation :
- **Formulaire FDAP** (`/fdap-2/`) → Contient `[app_formulaire]`
- **Mes FDAP** (`/mes-fdap/`) → Contient `[mes_fiches]`

Si les pages n'existent pas, les créer manuellement avec les shortcodes appropriés.

### 4. Configurer les permissions (optionnel)

Pour que les élèves puissent voir leurs propres fiches uniquement :
```php
// Ajouter dans functions.php du thème
add_filter('pre_get_posts', function($query) {
    if (!is_admin() && $query->is_main_query() && $query->get('post_type') === 'fdap') {
        if (!current_user_can('edit_others_posts')) {
            $query->set('author', get_current_user_id());
        }
    }
});
```

---

## Shortcodes

### `[app_formulaire]`

Affiche le formulaire de création/édition de fiche.

**Fonctionnalités :**
- Création d'une nouvelle fiche
- Édition d'une fiche existante (via `?fdap_id=XX`)
- Upload de photos avec compression automatique
- Upload de fichiers audio/vidéo/documents
- Redirection automatique vers "Mes FDAP" après sauvegarde

**Page de formulaire :**
```
http://votre-site.com/fdap-2/
```

### `[mes_fiches]`

Affiche le tableau de bord des fiches de l'utilisateur connecté.

**Fonctionnalités :**
- Liste des fiches en tableau
- Statut visible (Publiée / Brouillon)
- Actions : Voir, Modifier, Supprimer
- Lien vers le formulaire de création

**Page tableau de bord :**
```
http://votre-site.com/mes-fdap/
```

---

## Workflow Utilisateur

### Pour un élève

1. **Connexion** → L'élève arrive sur le formulaire de connexion intégré
2. **Création** → Il remplit le formulaire avec les 19 champs + photos
3. **Sauvegarde** → La fiche est enregistrée et publiée
4. **Consultation** → Il peut voir ses fiches dans "Mes FDAP"
5. **Modification** → Il peut éditer ou supprimer ses fiches

### Pour un enseignant

1. Créer un compte pour chaque élève (ou utiliser une extension d'inscription)
2. Les élèves accèdent à `/fdap-2/` pour créer leurs fiches
3. L'enseignant peut voir toutes les fiches depuis l'admin WordPress

---

## Champs Disponibles

| Section | Champ | Obligatoire |
|---------|-------|-------------|
| **Titre** | Titre de la fiche | ✅ |
| **Identité** | Nom / Prénom | ✅ |
| | Date de saisie | ✅ |
| **Contexte** | Lieu (Lycée / PFMP) | |
| | Enseigne | |
| | Lieu spécifique | |
| **Domaine** | Domaine | |
| | Compétences mobilisées | |
| **Conditions** | Autonomie (★) | |
| | Matériels utilisés | |
| | Commanditaire | |
| | Contraintes | |
| | Consignes reçues | |
| **Descriptif** | Avec qui ? | |
| | Déroulement | |
| | Résultats | |
| **Bilan** | Difficulté (★) | |
| | Plaisir (★) | |
| | Améliorations | |
| **Multimédia** | Audio | |
| | Vidéo | |
| | Fichier | |
| **Photos** | Photo 1-6 | |

---

## Structure des Fichiers

```
fdap-portfolio/
├── fdap-portfolio.php          # Point d'entrée, compression images
├── single-fdap.php             # Template single (fallback)
├── includes/
│   ├── class-post-type.php     # Déclaration du CPT "fdap"
│   ├── class-shortcodes.php    # Shortcodes + sauvegarde
│   └── form-fields.php         # Template du formulaire
├── assets/
│   └── css/
│       └── style.css           # Styles responsive
└── templates/
    └── single-fdap.php          # Template pour affichage single
```

---

## Personnalisation

### Modifier les styles

Les variables CSS sont définies dans `style.css` :

```css
:root {
    --fdap-primary: #2563eb;
    --fdap-primary-dark: #1d4ed8;
    --fdap-success: #10b981;
    --fdap-warning: #f59e0b;
    --fdap-error: #ef4444;
    --fdap-bg: #f8fafc;
    --fdap-text: #1e293b;
    --fdap-radius: 12px;
    --fdap-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
```

Pour personnaliser, ajouter dans le thème enfant :

```css
:root {
    --fdap-primary: #votre-couleur;
}
```

### Template personnalisé

Pour personnaliser l'affichage single, copier :
```
plugins/fdap-portfolio/templates/single-fdap.php
→ themes/votre-theme/single-fdap.php
```

---

## Dépannage

### Les pages ne s'affichent pas

1. Vérifier que le plugin est activé
2. Vérifier les permaliens : `Réglages > Permaliens > Enregistrer`
3. Créer manuellement les pages avec les shortcodes

### Erreur 404 sur les fiches

1. Réenregistrer les permaliens : `Réglages > Permaliens > Enregistrer`
2. Vérifier que le CPT "fdap" est bien déclaré

### Les images ne se compressent pas

1. Vérifier que l'extension Imagick est installée sur le serveur
2. Consulter les logs : `/var/log/apache2/error.log`

### Intégration avec Ultimate Member

Le plugin détecte automatiquement Ultimate Member et affiche une page de connexion stylisée avec redirection vers la page `/login/` du site.

Si Ultimate Member n'est pas installé, un formulaire de connexion WordPress standard est affiché.

---

## Versions

| Version | Notes |
|---------|-------|
| **1.0.1** | Intégration Ultimate Member - redirection vers page de connexion |
| **1.0.0** | Version initiale - tableau + accordéon |

---

## Mise à Jour

```bash
# Récupérer la dernière version
cd /var/www/html/wordpress/wp-content/plugins/fdap-portfolio
git pull origin main

# Ou réinstaller complètement
rm -rf fdap-portfolio
git clone https://github.com/rpfe26/fdap-portfolio.git fdap-portfolio
```

---

## Licence

GPL v2 or later

## Auteur

Patrick L'Hôte