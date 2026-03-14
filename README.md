# FDAP Portfolio

Extension WordPress pour Fiches d'Activités Pédagogiques (FDAP) - Portfolio étudiant.

## Installation

1. Copier le dossier `fdap-portfolio` dans `/wp-content/plugins/`
2. Activer le plugin dans WordPress
3. Les shortcodes disponibles :
   - `[fdap_form]` - Formulaire de création/édition
   - `[fdap_list]` - Liste des fiches de l'utilisateur

## Champs supportés

- **Titre** de la fiche
- **Identité de l'élève** : Nom/Prénom, Date de saisie
- **Contexte** : Lieu, Enseigne, Lieu spécifique
- **Domaine / Compétences**
- **Conditions et ressources** : Autonomie, Matériels, Commanditaire, Contraintes, Consignes
- **Descriptif Détaillé** : Avec qui ?, Déroulement, Résultats
- **Bilan Personnel** : Difficulté, Plaisir, Améliorations
- **Multimédia** : Audio, Vidéo, Fichier
- **Photos** : 6 photos avec compression automatique (300KB max)

## Développement

```bash
# Cloner le repo
git clone https://github.com/rpfe26/fdap-portfolio.git

# Déployer sur LXC 102
scp -r fdap-portfolio/* root@192.168.10.102:/var/lib/lxc/102/rootfs/var/www/html/wordpress/wp-content/plugins/fdap-portfolio/
```

## Versions

- **1.0.0** - Version stable avec tableau et accordéon
- Design responsive
- Compression automatique des images

## Auteur

Patrick L'Hôte