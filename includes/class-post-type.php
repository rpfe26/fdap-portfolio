<?php
/**
 * Custom Post Type
 */

defined('ABSPATH') || exit;

class FDAP_Post_Type {
    
    public function __construct() {
        add_action('init', [$this, 'register']);
    }
    
    public static function register() {
        register_post_type('fdap', [
            'labels' => [
                'name' => 'Fiches Activité',
                'singular_name' => 'Fiche Activité',
                'menu_name' => 'Portfolio FDAP',
                'add_new' => 'Nouvelle fiche',
                'add_new_item' => 'Créer une fiche',
                'edit_item' => 'Modifier la fiche',
                'new_item' => 'Nouvelle fiche',
                'view_item' => 'Voir la fiche',
                'search_items' => 'Rechercher',
                'not_found' => 'Aucune fiche trouvée',
            ],
            'public' => true,
            'has_archive' => true,
            'rewrite' => ['slug' => 'portfolio'],
            'supports' => ['title', 'author', 'thumbnail'],
            'menu_icon' => 'dashicons-portfolio',
            'show_in_rest' => true,
        ]);
    }
}