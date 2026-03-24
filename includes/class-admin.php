<?php
/**
 * Admin Dashboard for FDAP Portfolio
 */

defined("ABSPATH") || exit;

class FDAP_Admin {
    
    public function __construct() {
        add_action("admin_menu", [$this, "add_menu"], 99);
        add_action("admin_post_fdap_switch_user", [$this, "handle_switch_user"]);
        add_action("init", [$this, "handle_switch_back"]);
        add_action("wp_footer", [$this, "render_switch_back_notice"]);
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
    
    public function render_by_student() {
        global $wpdb;
        
        // Fetch all potential students by role
        $users = get_users([
            'role__in' => ['author', 'subscriber', 'customer', 'contributor'],
            'orderby'  => 'display_name',
            'order'    => 'ASC',
            'number'   => -1 // Ensure we get absolutely everyone
        ]);

        
        $authors_data = [];
        if ($users) {
            foreach ($users as $user) {
                // Count fiches for this specific user
                $fiche_count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_author = %d AND post_type = 'fdap' AND post_status IN ('publish', 'controlled')", 
                    $user->ID
                ));
                $authors_data[] = ["user" => $user, "count" => (int)$fiche_count];
            }
        }

        $count_authors = count($authors_data);

        ?>
        <div class="wrap">
            <h1>👥 Fiches par élève (<?php echo $count_authors; ?> élèves)</h1>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin-top: 20px;">
                <?php if ($count_authors > 0): ?>
                    <?php foreach ($authors_data as $data): 
                        $user = $data["user"];
                        $edit_url = admin_url("admin.php?page=fdap-dashboard&author_filter=" . $user->ID);
                    ?>
                    <div style="background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                            <?php echo get_avatar($user->ID, 60); ?>
                            <div style="flex: 1;">
                                <?php 
                                $full_name = trim($user->first_name . ' ' . $user->last_name);
                                $display_name = $full_name ?: $user->display_name;
                                ?>
                                <strong style="font-size: 1.1em;"><?php echo esc_html($display_name); ?></strong>
                                <br><span style="font-size: 0.85em; color: #666;">@<?php echo esc_html($user->user_login); ?></span>

                                
                                <?php 
                                // Calcul des stats de compétences pour cet élève
                                $student_fdaps = $wpdb->get_col($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE p.post_author = %d AND pm.meta_key = '_fdap_competences' AND p.post_status IN ('publish', 'controlled')", $user->ID));
                                $all_comp_text = "";
                                if ($student_fdaps) {
                                    foreach ($student_fdaps as $fid) {
                                        $all_comp_text .= get_post_meta($fid, '_fdap_competences', true) . "\n";
                                    }
                                }
                                $stats = fdap_get_validation_stats($all_comp_text);
                                ?>
                                
                                <div class="fdap-admin-tracker">
                                    <div class="fdap-progress-bar">
                                        <div class="fdap-progress-fill" style="width: <?php echo $stats['percent']; ?>%;"></div>
                                    </div>
                                    <div class="fdap-tracker-meta">
                                        <span>🎯 CAP EPC</span>
                                        <span><?php echo $stats['treated']; ?> / <?php echo $stats['total']; ?></span>
                                    </div>
                                </div>
                                
                                <div class="fdap-dots-grid">
                                    <?php if (!empty($stats['poles'])): ?>
                                        <?php foreach ($stats['poles'] as $pole): ?>
                                            <?php foreach ($pole['metas'] as $meta): 
                                                $dot_class = ($meta['treated_count'] === $meta['total_count']) ? 'full' : (($meta['treated_count'] > 0) ? 'partial' : '');
                                                $title = esc_attr($meta['label'] . ' (' . $meta['treated_count'] . '/' . $meta['total_count'] . ')');
                                            ?>
                                                <div class="fdap-dot <?php echo $dot_class; ?>" title="<?php echo $title; ?>"></div>
                                            <?php endforeach; ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 15px; border-top: 1px solid #eee; gap: 10px;">
                            <span style="color: #2271b1; font-weight:700; font-size: 0.9em;"><?php echo $data["count"]; ?> fiche<?php echo $data["count"] > 1 ? "s" : ""; ?></span>
                            <div style="display: flex; gap: 8px;">
                                <?php 
                                $switch_url = wp_nonce_url(
                                    admin_url('admin-post.php?action=fdap_switch_user&user_id=' . $user->ID),
                                    'fdap_switch_user_' . $user->ID
                                );
                                ?>
                                <a href="<?php echo esc_url($switch_url); ?>" class="button" title="Prendre le rôle de cet élève">👤 Jouer le rôle</a>
                                <a href="<?php echo esc_url($edit_url); ?>" class="button button-primary">Détails →</a>
                            </div>
                        </div>
                    </div>

                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #666; background: #fff; border-radius: 8px; margin-top: 20px;">Aucune fiche enregistrée.</div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**

     * Gère le changement d'utilisateur (Impersonation)
     */
    public function handle_switch_user() {
        if (!current_user_can('edit_others_posts')) {
            wp_die('Accès refusé.');
        }

        $user_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
        if (!$user_id) {
            wp_die('ID utilisateur manquant.');
        }

        check_admin_referer('fdap_switch_user_' . $user_id);

        $current_admin_id = get_current_user_id();
        
        // Stocker l'ID de l'admin original dans un cookie sécurisé
        setcookie('fdap_original_admin_id', $current_admin_id, time() + 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);

        // Changer d'utilisateur
        wp_clear_auth_cookie();
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);

        wp_redirect(home_url('/mes-fdap/'));
        exit;
    }

    /**
     * Gère le retour à l'administrateur
     */
    public function handle_switch_back() {
        if (isset($_GET['fdap_switch_back']) && $_GET['fdap_switch_back'] == '1') {
            $original_admin_id = isset($_COOKIE['fdap_original_admin_id']) ? (int) $_COOKIE['fdap_original_admin_id'] : 0;
            
            if ($original_admin_id) {
                // On vérifie que cet ID a bien les droits admin avant de switcher
                $user = get_user_by('id', $original_admin_id);
                if ($user && user_can($user, 'edit_others_posts')) {
                    wp_clear_auth_cookie();
                    wp_set_current_user($original_admin_id);
                    wp_set_auth_cookie($original_admin_id);
                    
                    // Supprimer le cookie
                    setcookie('fdap_original_admin_id', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
                    
                    wp_redirect(admin_url('admin.php?page=fdap-by-student'));
                    exit;
                }
            }
        }
    }

    /**
     * Affiche un bandeau d'avertissement quand on est en mode impersonation
     */
    public function render_switch_back_notice() {
        $original_admin_id = isset($_COOKIE['fdap_original_admin_id']) ? (int) $_COOKIE['fdap_original_admin_id'] : 0;
        if ($original_admin_id && !is_admin()) {
            $current_user = wp_get_current_user();
            $back_url = add_query_arg('fdap_switch_back', '1', home_url());
            ?>
            <div style="position: fixed; top: 0; left: 0; right: 0; background: #6366f1; color: white; padding: 12px; text-align: center; z-index: 999999; font-family: sans-serif; box-shadow: 0 4px 15px rgba(0,0,0,0.3); border-bottom: 2px solid rgba(255,255,255,0.2);">
                <span style="font-size: 1.1em;">👤 Vous jouez le rôle de : <strong><?php echo esc_html($current_user->display_name); ?></strong></span>
                <a href="<?php echo esc_url($back_url); ?>" style="color: #fff; margin-left: 20px; background: #ef4444; padding: 8px 18px; border-radius: 6px; text-decoration: none; font-weight: 900; border: 1px solid rgba(255,255,255,0.3); box-shadow: 0 2px 10px rgba(0,0,0,0.3); text-transform: uppercase; font-size: 13px;">
                    🚪 Quitter le rôle
                </a>

            </div>
            <style>body { padding-top: 50px !important; }</style>

            <?php
        }
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
        // Fetch all students for the filter dropdown
        $authors = get_users([
            'role__in' => ['author', 'subscriber', 'customer', 'contributor'],
            'orderby'  => 'display_name',
            'order'    => 'ASC',
            'number'   => -1
        ]);


        
        $count_publish = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type=\"fdap\" AND post_status=\"publish\"" );
        $count_controlled = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type=\"fdap\" AND post_status=\"controlled\"" );
        
        // Enqueue admin styles (inline for simplicity in this file, or we could link a CSS)
        ?>
        <style>
            :root {
                --admin-primary: #4f46e5;
                --admin-slate-900: #0f172a;
                --admin-slate-500: #64748b;
                --admin-bg: #f8fafc;
            }
            .fdap-admin-wrap {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                padding: 20px;
                background: var(--admin-bg);
            }
            .fdap-admin-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 30px;
            }
            .fdap-admin-header h1 {
                font-weight: 900;
                font-size: 24px;
                color: var(--admin-slate-900);
                margin: 0;
                text-transform: uppercase;
                letter-spacing: -0.02em;
            }
            .fdap-stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
                gap: 20px;
                margin-bottom: 30px;
            }
            .fdap-stat-card {
                background: #fff;
                padding: 24px;
                border-radius: 20px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.05);
                display: flex;
                align-items: center;
                gap: 20px;
                transition: transform 0.3s ease;
                cursor: pointer;
                text-decoration: none;
                border: 1px solid transparent;
            }
            .fdap-stat-card:hover {
                transform: translateY(-5px);
                border-color: var(--admin-primary);
            }
            .fdap-stat-icon {
                width: 56px;
                height: 56px;
                border-radius: 16px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 24px;
            }
            .fdap-stat-info h3 {
                margin: 0;
                font-size: 28px;
                font-weight: 900;
                line-height: 1;
                color: var(--admin-slate-900);
            }
            .fdap-stat-info p {
                margin: 4px 0 0 0;
                font-size: 11px;
                font-weight: 700;
                color: var(--admin-slate-500);
                text-transform: uppercase;
                letter-spacing: 0.1em;
            }
            .fdap-table-card {
                background: #fff;
                border-radius: 20px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.08);
                overflow: hidden;
            }
            .fdap-table-filters {
                padding: 24px;
                border-bottom: 1px solid #f1f5f9;
                display: flex;
                gap: 12px;
                flex-wrap: wrap;
            }
            .fdap-filter-item {
                padding: 10px 16px;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                font-weight: 600;
                background: #fff;
            }
            .fdap-admin-table {
                width: 100%;
                border-collapse: collapse;
            }
            .fdap-admin-table th {
                background: #f8fafc;
                padding: 16px 24px;
                text-align: left;
                font-size: 11px;
                font-weight: 800;
                color: var(--admin-slate-500);
                text-transform: uppercase;
                letter-spacing: 0.05em;
            }
            .fdap-admin-table td {
                padding: 20px 24px;
                border-bottom: 1px solid #f1f5f9;
            }
            .fdap-status-pill {
                padding: 6px 12px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 700;
            }
            .status-publish { background: #eef2ff; color: #4f46e5; }
            .status-controlled { background: #ecfdf5; color: #10b981; }

            /* Competency Tracker Styles */
            .fdap-admin-tracker { margin-top: 15px; }
            .fdap-progress-bar {
                height: 8px;
                background: #e2e8f0;
                border-radius: 4px;
                overflow: hidden;
                margin-bottom: 6px;
            }
            .fdap-progress-fill {
                height: 100%;
                background: linear-gradient(90deg, #4f46e5, #818cf8);
                transition: width 0.5s ease-out;
            }
            .fdap-tracker-meta {
                display: flex;
                justify-content: space-between;
                font-size: 11px;
                font-weight: 700;
                color: var(--admin-slate-500);
            }
            .fdap-dots-grid {
                display: flex;
                flex-wrap: wrap;
                gap: 4px;
                margin-top: 10px;
            }
            .fdap-dot {
                width: 10px;
                height: 10px;
                border-radius: 2px;
                background: #e2e8f0;
            }
            .fdap-dot.full { background: #22c55e; }
            .fdap-dot.partial { background: #f59e0b; }
        </style>

        <div class="fdap-admin-wrap">
            <div class="fdap-admin-header">
                <h1>Plateforme FDAP <span style="color:var(--admin-primary)">• Admin</span></h1>
            </div>

            <div class="fdap-stats-grid">
                <a href="?page=fdap-dashboard&status_filter=publish" class="fdap-stat-card">
                    <div class="fdap-stat-icon" style="background: #eef2ff; color: #4f46e5;">📤</div>
                    <div class="fdap-stat-info">
                        <h3><?php echo $count_publish; ?></h3>
                        <p>À contrôler</p>
                    </div>
                </a>
                <a href="?page=fdap-dashboard&status_filter=controlled" class="fdap-stat-card">
                    <div class="fdap-stat-icon" style="background: #ecfdf5; color: #10b981;">✅</div>
                    <div class="fdap-stat-info">
                        <h3><?php echo $count_controlled; ?></h3>
                        <p>Contrôlées</p>
                    </div>
                </a>
                <div class="fdap-stat-card">
                    <div class="fdap-stat-icon" style="background: #fff7ed; color: #f97316;">👥</div>
                    <div class="fdap-stat-info">
                        <h3><?php echo count($authors); ?></h3>
                        <p>Élèves inscrits</p>
                    </div>
                </div>
            </div>

            <div class="fdap-table-card">
                <div class="fdap-table-filters">
                    <form method="get" style="display: flex; gap: 10px; width: 100%;">
                        <input type="hidden" name="page" value="fdap-dashboard">
                        <select name="author_filter" class="fdap-filter-item">
                            <option value="">Tous les élèves</option>
                            <?php foreach ($authors as $author): 
                                $afull = trim($author->first_name . ' ' . $author->last_name);
                                $aname = $afull ?: $author->display_name;
                            ?>
                                <option value="<?php echo $author->ID; ?>" <?php selected($author_filter, $author->ID); ?>><?php echo esc_html($aname); ?> (@<?php echo esc_html($author->user_login); ?>)</option>
                            <?php endforeach; ?>

                        </select>
                        <select name="status_filter" class="fdap-filter-item">
                            <option value="">Tous les statuts</option>
                            <option value="publish" <?php selected($status_filter, "publish"); ?>>À contrôler</option>
                            <option value="controlled" <?php selected($status_filter, "controlled"); ?>>Contrôlées</option>
                        </select>
                        <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Rechercher..." class="fdap-filter-item" style="flex: 1;">
                        <button type="submit" class="button button-primary">Filtrer</button>
                    </form>
                </div>

                <table class="fdap-admin-table">
                    <thead>
                        <tr>
                            <th>Titre de l'activité</th>
                            <th>Élève</th>
                            <th>Dernière modification</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($query->have_posts()): while ($query->have_posts()): $query->the_post(); 
                            $post_id = get_the_ID();
                            $nom = get_post_meta($post_id, "_fdap_nom_prenom", true);
                            $status = get_post_status();
                            $status_label = ($status === 'controlled') ? 'Contrôlée' : 'À contrôler';
                            $status_class = 'status-' . $status;
                        ?>
                        <tr>
                            <td><strong><?php the_title(); ?></strong></td>
                            <td><?php echo esc_html($nom ?: get_the_author()); ?></td>
                            <td><?php echo get_the_modified_date("d/m/Y à H:i"); ?></td>
                            <td><span class="fdap-status-pill <?php echo $status_class; ?>"><?php echo $status_label; ?></span></td>
                            <td style="display: flex; gap: 6px; align-items: center;">
                                <a href="<?php echo esc_url(add_query_arg(['fdap_id' => $post_id], get_permalink(get_page_by_path('fdap-2')))); ?>" class="button" style="background: #6366f1; color: white; border: none; width: 34px; height: 34px; padding: 0; display: inline-flex; align-items: center; justify-content: center; font-size: 16px;" title="Modifier">✏️</a>
                                <a href="<?php the_permalink(); ?>" target="_blank" class="button" style="background: #3b82f6; color: white; border: none; width: 34px; height: 34px; padding: 0; display: inline-flex; align-items: center; justify-content: center; font-size: 16px;" title="Voir">👁</a>
                                <a href="<?php echo wp_nonce_url(add_query_arg(['action' => 'delete', 'fdap_id' => $post_id], get_permalink(get_page_by_path('mes-fdap'))), 'fdap_delete_' . $post_id); ?>" class="button" style="background: #ef4444; color: white; border: none; width: 34px; height: 34px; padding: 0; display: inline-flex; align-items: center; justify-content: center; font-size: 16px;" onclick="return confirm('Supprimer cette fiche ?')" title="Supprimer">🗑</a>
                            </td>


                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="5" style="text-align:center; padding: 40px;">Aucune fiche trouvée.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <?php if ($query->max_num_pages > 1): ?>
                    <div class="fdap-pagination" style="padding: 20px; display: flex; justify-content: center; gap: 5px; background: #f8fafc; border-top: 1px solid #f1f5f9;">
                        <?php
                        echo paginate_links([
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => __('&laquo; Précédent'),
                            'next_text' => __('Suivant &raquo;'),
                            'total' => $query->max_num_pages,
                            'current' => $paged,
                            'type' => 'plain'
                        ]);
                        ?>
                    </div>
                    <style>
                        .fdap-pagination a, .fdap-pagination span {
                            padding: 8px 14px;
                            background: white;
                            border: 1px solid #e2e8f0;
                            border-radius: 8px;
                            text-decoration: none;
                            color: #4f46e5;
                            font-weight: 700;
                            font-size: 13px;
                            transition: all 0.2s ease;
                        }
                        .fdap-pagination a:hover {
                            background: #f1f5f9;
                            border-color: #cbd5e1;
                        }
                        .fdap-pagination .current {
                            background: #4f46e5;
                            color: white;
                            border-color: #4f46e5;
                        }
                    </style>
                <?php endif; ?>
            </div>
        </div>

        <?php
    }
}

