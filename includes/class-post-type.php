<?php
/**
 * Custom Post Type
 */

defined("ABSPATH") || exit;

class FDAP_Post_Type {
    
    public function __construct() {
        add_action("init", [$this, "register"], 0);
        add_action("init", [$this, "register_status"], 1);
    }
    
    public function register() {
        register_post_type("fdap", [
            "labels" => [
                "name" => "Fiches Activité",
                "singular_name" => "Fiche Activité",
                "menu_name" => "Portfolio FDAP",
                "add_new" => "Nouvelle fiche",
                "add_new_item" => "Créer une fiche",
                "edit_item" => "Modifier la fiche",
                "new_item" => "Nouvelle fiche",
                "view_item" => "Voir la fiche",
                "search_items" => "Rechercher",
                "not_found" => "Aucune fiche trouvée",
            ],
            "public" => true,
            "has_archive" => true,
            "rewrite" => ["slug" => "portfolio"],
            "supports" => ["title", "author", "thumbnail"],
            "show_in_rest" => true,
            "show_in_menu" => false,
            "show_ui" => true,
        ]);
    }
    
    public function register_status() {
        register_post_status("controlled", [
            "label" => "Contrôlée",
            "public" => true,
            "exclude_from_search" => false,
            "show_in_admin_all_list" => true,
            "show_in_admin_status_list" => true,
            "label_count" => _n_noop("Contrôlée <span class=\"count\">(%s)</span>", "Contrôlées <span class=\"count\">(%s)</span>"),
        ]);
    }
}

/**
 * Notification email quand le statut passe à 'controlled'
 */
add_action('transition_post_status', 'fdap_send_controlled_email', 10, 3);

function fdap_send_controlled_email($new_status, $old_status, $post) {
    if ($post->post_type !== 'fdap' || $new_status !== 'controlled' || $old_status === 'controlled') {
        return;
    }
    
    $author_email = get_the_author_meta('user_email', $post->post_author);
    $author_name = get_the_author_meta('display_name', $post->post_author);
    $post_title = get_the_title($post->ID);
    $post_url = get_permalink($post->ID);
    
    $subject = '✅ Votre fiche FDAP a été contrôlée';
    
    $message = "Bonjour " . $author_name . ",\n\n";
    $message .= "Votre fiche '" . $post_title . "' a été contrôlée par votre professeur.\n\n";
    $message .= "Consultez les commentaires : " . $post_url . "\n\n";
    $message .= "Cordialement,\nL'équipe pédagogique";
    
    wp_mail($author_email, $subject, $message);
}
