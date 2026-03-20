<?php
/**
 * Admin Dashboard for FDAP Portfolio
 */

defined("ABSPATH") || exit;

class FDAP_Admin {
    
    public function __construct() {
        add_action("admin_menu", [$this, "add_menu"], 99);
    }
    
    public function add_menu() {
        add_menu_page(
            "Portfolio FDAP",
            "Portfolio FDAP",
            "edit_others_posts",
            "fdap-dashboard",
            [$this, "render_dashboard"],
            "dashicons-portfolio",
            25
        );
        
        add_submenu_page(
            "fdap-dashboard",
            "Toutes les fiches",
            "Toutes les fiches",
            "edit_others_posts",
            "fdap-dashboard",
            [$this, "render_dashboard"]
        );
        
        add_submenu_page(
            "fdap-dashboard",
            "Par élève",
            "Par élève",
            "edit_others_posts",
            "fdap-by-student",
            [$this, "render_by_student"]
        );
    }
    
    public function render_dashboard() {
        $author_filter = isset($_GET["author_filter"]) ? (int) $_GET["author_filter"] : 0;
        $status_filter = isset($_GET["status_filter"]) ? sanitize_text_field($_GET["status_filter"]) : "";
        $search = isset($_GET["s"]) ? sanitize_text_field($_GET["s"]) : "";
        $paged = isset($_GET["paged"]) ? (int) $_GET["paged"] : 1;
        
        $args = [
            "post_type" => "fdap",
            "posts_per_page" => 50,
            "paged" => $paged,
            "orderby" => "modified",
            "order" => "DESC",
            "post_status" => ["publish", "controlled"],
        ];
        
        if ($author_filter) $args["author"] = $author_filter;
        if ($status_filter) $args["post_status"] = $status_filter;
        if ($search) $args["s"] = $search;
        
        $query = new WP_Query($args);
        
        global $wpdb;
        $author_ids = $wpdb->get_col("SELECT DISTINCT post_author FROM {$wpdb->posts} WHERE post_type=\"" . "fdap" . "\" AND post_status IN (\"" . "publish" . "\", \"" . "controlled" . "\")" );
        $authors = [];
        foreach ($author_ids as $aid) {
            $user = get_user_by("id", $aid);
            if ($user) $authors[] = $user;
        }
        usort($authors, function($a, $b) { return strcmp($a->display_name, $b->display_name); });
        
        // Counts
        $count_publish = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type=\"" . "fdap" . "\" AND post_status=\"" . "publish" . "\"" );
        $count_controlled = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type=\"" . "fdap" . "\" AND post_status=\"" . "controlled" . "\"" );
        
        ?>
        <div class="wrap">
            <h1>📚 Toutes les fiches FDAP (<?php echo $query->found_posts; ?>)</h1>
            
            <div style="display: flex; gap: 20px; margin: 20px 0; flex-wrap: wrap;">
                <div style="background: #fff; padding: 15px 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); cursor: pointer;" onclick="window.location.href="?page=fdap-dashboard&status_filter=publish";">
                    <span style="font-size: 2em; font-weight: 700; color: #2271b1;"><?php echo $count_publish; ?></span>
                    <span style="color: #666; margin-left: 8px;">publiées (à contrôler)</span>
                </div>
                <div style="background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); padding: 15px 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #10b981; cursor: pointer;" onclick="window.location.href="?page=fdap-dashboard&status_filter=controlled";">
                    <span style="font-size: 2em; font-weight: 700; color: #065f46;"><?php echo $count_controlled; ?></span>
                    <span style="color: #047857; margin-left: 8px; font-weight: 600;">✅ contrôlées</span>
                </div>
                <div style="background: #fff; padding: 15px 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <span style="font-size: 2em; font-weight: 700; color: #065f46;"><?php echo count($authors); ?></span>
                    <span style="color: #666; margin-left: 8px;">élèves</span>
                </div>
            </div>
            
            <div style="background: #fff; padding: 15px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <form method="get" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                    <input type="hidden" name="page" value="fdap-dashboard">
                    <select name="author_filter" style="min-width: 200px; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="">Tous les élèves</option>
                        <?php foreach ($authors as $author): ?>
                            <option value="<?php echo $author->ID; ?>" <?php selected($author_filter, $author->ID); ?>>
                                <?php echo esc_html($author->display_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="status_filter" style="min-width: 180px; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="">Tous les statuts</option>
                        <option value="publish" <?php selected($status_filter, "publish"); ?>>📤 Publiées (à contrôler)</option>
                        <option value="controlled" <?php selected($status_filter, "controlled"); ?>>✅ Contrôlées</option>
                    </select>
                    <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Rechercher..." style="min-width: 200px; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px;">
                    <button type="submit" class="button button-primary">Filtrer</button>
                    <?php if ($author_filter || $status_filter || $search): ?>
                        <a href="?page=fdap-dashboard" class="button">Réinitialiser</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <?php if ($query->have_posts()): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 40%;">Titre</th>
                            <th style="width: 20%;">Élève</th>
                            <th style="width: 12%;">Modifiée</th>
                            <th style="width: 8%;">Lieu</th>
                            <th style="width: 8%;">Statut</th>
                            <th style="width: 12%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($query->have_posts()): $query->the_post();
                            global $post;
                            $post_id = $post->ID;
                            $title = $post->post_title;
                            $nom = get_post_meta($post_id, "_fdap_nom_prenom", true);
                            $lieu = get_post_meta($post_id, "_fdap_lieu_", true);
                            $lieu_label = $lieu === "lycee" ? "Lycée" : ($lieu === "pfmp" ? "PFMP" : "-");
                            $status = $post->post_status;
                            
                            $status_labels = [
                                "publish" => ["label" => "📤 Publiée", "bg" => "#dbeafe", "color" => "#1d4ed8"],
                                "controlled" => ["label" => "✅ Contrôlée", "bg" => "#d1fae5", "color" => "#065f46"],
                            ];
                            $status_info = $status_labels[$status] ?? ["label" => $status, "bg" => "#f3f4f6", "color" => "#374151"];
                            
                            $author = get_the_author();
                            $edit_url = get_permalink(get_page_by_path("fdap-2")) . "?fdap_id=" . $post_id;
                            $view_url = get_permalink($post_id);
                        ?>
                        <tr>
                            <td><strong style="font-size: 14px;"><?php echo esc_html($title); ?></strong></td>
                            <td>
                                <?php if ($nom): ?>
                                    <strong><?php echo esc_html($nom); ?></strong><br>
                                <?php endif; ?>
                                <span style="color: #888; font-size: 12px;">par <?php echo esc_html($author); ?></span>
                            </td>
                            <td><?php echo get_the_modified_date("d/m/Y"); ?></td>
                            <td><?php echo esc_html($lieu_label); ?></td>
                            <td><span style="background: <?php echo $status_info["bg"]; ?>; color: <?php echo $status_info["color"]; ?>; padding: 4px 10px; border-radius: 12px; font-size: 0.85em; font-weight: 600;"><?php echo $status_info["label"]; ?></span></td>
                            <td>
                                <a href="<?php echo esc_url($view_url); ?>" target="_blank" class="button button-small">👁</a>
                                <a href="<?php echo esc_url($edit_url); ?>" class="button button-small">✏️</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #666; background: #fff; border-radius: 8px;">Aucune fiche trouvée.</div>
            <?php endif; ?>
            
            <?php wp_reset_postdata(); ?>
        </div>
        <?php
    }
    
    public function render_by_student() {
        global $wpdb;
        $results = $wpdb->get_results("SELECT post_author, COUNT(*) as count FROM {$wpdb->posts} WHERE post_type=\"" . "fdap" . "\" AND post_status IN (\"" . "publish" . "\", \"" . "controlled" . "\") GROUP BY post_author ORDER BY count DESC" );
        
        $authors_data = [];
        foreach ($results as $row) {
            $user = get_user_by("id", $row->post_author);
            if ($user) {
                $authors_data[] = ["user" => $user, "count" => $row->count];
            }
        }
        ?>
        <div class="wrap">
            <h1>👥 Fiches par élève (<?php echo count($authors_data); ?> élèves)</h1>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin-top: 20px;">
                <?php foreach ($authors_data as $data): 
                    $user = $data["user"];
                    $edit_url = admin_url("admin.php?page=fdap-dashboard&author_filter=" . $user->ID);
                ?>
                <div style="background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                        <?php echo get_avatar($user->ID, 60); ?>
                        <div>
                            <strong style="font-size: 1.1em;"><?php echo esc_html($user->display_name); ?></strong>
                            <br><span style="font-size: 0.85em; color: #666;">@<?php echo esc_html($user->user_login); ?></span>
                        </div>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 15px; border-top: 1px solid #eee;">
                        <span style="color: #2271b1;"><?php echo $data["count"]; ?> fiche<?php echo $data["count"] > 1 ? "s" : ""; ?></span>
                        <a href="<?php echo esc_url($edit_url); ?>" class="button button-primary">Voir →</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if (empty($authors_data)): ?>
                <div style="text-align: center; padding: 40px; color: #666; background: #fff; border-radius: 8px; margin-top: 20px;">Aucune fiche enregistrée.</div>
            <?php endif; ?>
        </div>
        <?php
    }
}
