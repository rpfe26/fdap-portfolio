<?php
/**
 * FDAP Migration Script — ACF → Custom Fields
 * 
 * Convertit les posts WordPress avec champs ACF en Custom Post Type 'fdap'
 * avec champs natifs (_fdap_*)
 * 
 * Usage: wp eval-file /path/to/fdap-migrate.php [--dry-run]
 */

if (!defined('ABSPATH')) {
    exit;
}

// Configuration
define('FDAP_SOURCE_POST_TYPE', 'post');
define('FDAP_TARGET_POST_TYPE', 'fdap');
define('FDAP_DRY_RUN', isset($args[0]) && $args[0] === '--dry-run');

// Mapping des champs ACF → FDAP
$FIELD_MAP = [
    // Correspondance directe
    'nom_prenom' => '_fdap_nom_prenom',
    'date_de_saisie' => '_fdap_date_de_saisie',
    'lieu_' => '_fdap_lieu_',
    'enseigne_' => '_fdap_enseigne_',
    'lieu_specifique' => '_fdap_lieu_specifique',
    'domaine' => '_fdap_domaine',
    'competences' => '_fdap_competences',
    'autonomie' => '_fdap_autonomie',
    'materiels' => '_fdap_materiels',
    'commanditaire' => '_fdap_commanditaire',
    'contraintes' => '_fdap_contraintes',
    'consignes_recues' => '_fdap_consignes_recues',
    'avec_qui_' => '_fdap_avec_qui_',
    'resultats_' => '_fdap_resultats_',
    'difficulte' => '_fdap_difficulte',
    'plaisir_' => '_fdap_plaisir_',
    'ameliorations' => '_fdap_ameliorations',
    'audio' => '_fdap_audio',
    'video' => '_fdap_video',
    
    // Renommage
    'descriptif' => '_fdap_deroulement',
    'telecharger_vos_fichiers' => '_fdap_fichier',
];

// Mapping photos (1-6)
for ($i = 1; $i <= 6; $i++) {
    $FIELD_MAP["photo_$i"] = "_fdap_photo_$i";
}

/**
 * Fonction principale de migration
 */
function fdap_migrate($dry_run = false) {
    global $FIELD_MAP;
    
    // Récupérer tous les posts avec le champ nom_prenom (signature ACF)
    $args = [
        'post_type' => FDAP_SOURCE_POST_TYPE,
        'post_status' => ['publish', 'draft', 'pending'],
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => 'nom_prenom',
                'compare' => 'EXISTS'
            ]
        ]
    ];
    
    $posts = get_posts($args);
    $migrated = 0;
    $errors = [];
    
    echo "Trouvé " . count($posts) . " fiches à migrer.\n";
    echo $dry_run ? "=== MODE DRY RUN (aucune modification) ===\n\n" : "=== MIGRATION EN COURS ===\n\n";
    
    foreach ($posts as $post) {
        // Vérifier si déjà migré
        $already_migrated = get_post_meta($post->ID, '_fdap_migrated', true);
        if ($already_migrated) {
            echo "[SKIP] Post {$post->ID} déjà migré vers FDAP #{$already_migrated}\n";
            continue;
        }
        
        echo "[POST {$post->ID}] {$post->post_title}\n";
        
        if ($dry_run) {
            // En mode dry-run, afficher les champs qui seraient migrés
            foreach ($FIELD_MAP as $acf_key => $fdap_key) {
                $value = get_post_meta($post->ID, $acf_key, true);
                if ($value) {
                    $preview = is_array($value) ? json_encode($value) : substr($value, 0, 50);
                    echo "  $acf_key -> $fdap_key: $preview\n";
                }
            }
            echo "\n";
            continue;
        }
        
        // Créer le nouveau post FDAP
        $new_post = [
            'post_title' => $post->post_title,
            'post_author' => $post->post_author,
            'post_status' => $post->post_status,
            'post_type' => FDAP_TARGET_POST_TYPE,
            'post_date' => $post->post_date,
            'post_modified' => $post->post_modified,
        ];
        
        $new_id = wp_insert_post($new_post);
        
        if (is_wp_error($new_id)) {
            $errors[] = "Erreur création FDAP pour post {$post->ID}: " . $new_id->get_error_message();
            echo "[ERROR] " . $new_id->get_error_message() . "\n";
            continue;
        }
        
        // Copier les métadonnées
        foreach ($FIELD_MAP as $acf_key => $fdap_key) {
            $value = get_post_meta($post->ID, $acf_key, true);
            if ($value) {
                update_post_meta($new_id, $fdap_key, $value);
            }
        }
        
        // Copier l'image à la une (thumbnail)
        $thumbnail_id = get_post_thumbnail_id($post->ID);
        if ($thumbnail_id) {
            set_post_thumbnail($new_id, $thumbnail_id);
        }
        
        // Marquer l'ancien post comme migré
        update_post_meta($post->ID, '_fdap_migrated', $new_id);
        update_post_meta($new_id, '_fdap_source_id', $post->ID);
        
        // Ajouter à la catégorie portfolio si elle existe
        $portfolio_cat = get_term_by('slug', 'portfolio', 'category');
        if ($portfolio_cat) {
            wp_set_object_terms($new_id, $portfolio_cat->term_id, 'category');
        }
        
        echo "  -> Créé FDAP #$new_id\n";
        $migrated++;
    }
    
    echo "\n=== RÉSUMÉ ===\n";
    echo "Fiches migrées: $migrated\n";
    if (!empty($errors)) {
        echo "Erreurs: " . count($errors) . "\n";
        foreach ($errors as $error) {
            echo "  - $error\n";
        }
    }
    
    return [
        'migrated' => $migrated,
        'errors' => $errors
    ];
}

/**
 * Annuler la migration (restaurer)
 */
function fdap_rollback() {
    // Récupérer tous les posts fdap migrés
    $args = [
        'post_type' => FDAP_TARGET_POST_TYPE,
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => '_fdap_source_id',
                'compare' => 'EXISTS'
            ]
        ]
    ];
    
    $posts = get_posts($args);
    
    echo "Suppression de " . count($posts) . " fiches FDAP...\n";
    
    foreach ($posts as $post) {
        $source_id = get_post_meta($post->ID, '_fdap_source_id', true);
        
        // Supprimer le marqueur sur l'ancien post
        delete_post_meta($source_id, '_fdap_migrated');
        
        // Supprimer le post fdap
        wp_delete_post($post->ID, true);
        
        echo "  Supprimé FDAP #{$post->ID} (source: #$source_id)\n";
    }
    
    echo "Rollback terminé.\n";
}

// Exécuter si appelé directement
if (php_sapi_name() === 'cli' && basename($argv[0]) === basename(__FILE__)) {
    
    if (in_array('--rollback', $argv)) {
        fdap_rollback();
    } else {
        $dry_run = in_array('--dry-run', $argv);
        fdap_migrate($dry_run);
    }
    
} elseif (function_exists('WP_CLI') && defined('WP_CLI')) {
    // Pour WP-CLI
    WP_CLI::add_command('fdap migrate', function($args, $assoc_args) {
        $dry_run = isset($assoc_args['dry-run']);
        fdap_migrate($dry_run);
    });
    
    WP_CLI::add_command('fdap rollback', function() {
        fdap_rollback();
    });
}