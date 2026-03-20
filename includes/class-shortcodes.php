<?php
/**
 * Shortcodes
 */

defined('ABSPATH') || exit;

class FDAP_Shortcodes {
    
    public function __construct() {
        add_shortcode('app_formulaire', [$this, 'form_shortcode']);
        add_shortcode('mes_fiches', [$this, 'dashboard_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
    }
    
    /**
     * Enqueue styles
     */
    public function enqueue_styles() {
        if (is_page('fdap-2') || is_page('mes-fdap') || is_singular('fdap')) {
            wp_enqueue_style('fdap-style', FDAP_PLUGIN_URL . 'assets/css/style.css', [], FDAP_VERSION);
        }
    }
    
    /**
     * Form shortcode [app_formulaire]
     */
    public function form_shortcode($atts) {
        if (!is_user_logged_in()) {
            return $this->login_form();
        }
        
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fdap_nonce'])) {
            if (wp_verify_nonce($_POST['fdap_nonce'], 'fdap_form_submit')) {
                $result = $this->save_fiche();
                if ($result) {
                    return '<div class="fdap-success">Fiche enregistrée avec succès !</div>';
                }
            }
        }
        
        $post_id = isset($_GET['fdap_id']) ? (int) $_GET['fdap_id'] : 0;
        $values = [];
        
        if ($post_id) {
            $post = get_post($post_id);
            // Admins can edit any fiche, students only their own
            if ($post && $post->post_type === 'fdap' && ($post->post_author == get_current_user_id() || current_user_can('edit_others_posts'))) {
                $fields = $this->get_all_fields();
                foreach ($fields as $field) {
                    $values[$field] = get_post_meta($post_id, '_fdap_' . $field, true);
                }
            } else {
                return '<div class="fdap-error">Vous n\'êtes pas autorisé à modifier cette fiche.</div>';
            }
        }
        
        ob_start();
        include FDAP_PLUGIN_DIR . 'includes/form-fields.php';
        return ob_get_clean();
    }
    
    /**
     * Get all field names
     */
    private function get_all_fields() {
        return [
            'nom_prenom', 'date_de_saisie', 'lieu_', 'enseigne_', 'lieu_specifique',
            'domaine', 'competences', 'autonomie', 'materiels',
            'commanditaire', 'contraintes', 'consignes_recues', 'avec_qui_',
            'deroulement', 'resultats_', 'difficulte', 'plaisir_', 'ameliorations',
            'audio', 'video', 'fichier'
        ];
    }
    
    /**
     * Dashboard shortcode [mes_fiches]
     */
    public function dashboard_shortcode($atts) {
        if (!is_user_logged_in()) {
            return $this->login_form();
        }
        
        // Handle deletion
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['fdap_id'])) {
            $del_id = (int) $_GET['fdap_id'];
            if (get_post_field('post_author', $del_id) == get_current_user_id() || current_user_can('delete_others_posts')) {
                wp_trash_post($del_id);
            }
        }
        
        $current_user_id = get_current_user_id();
        $is_admin = current_user_can('edit_others_posts');
        
        // Filter by author (admin only)
        $author_filter = 0;
        if ($is_admin && isset($_GET['author_filter'])) {
            $author_filter = (int) $_GET['author_filter'];
        }
        
        // Build query
        $query_args = [
            'post_type' => 'fdap',
            'posts_per_page' => -1,
            'post_status' => ['publish', 'controlled'],
        ];
        
        // If admin with filter, show filtered results; if admin without filter, show all; if student, show own
        if ($author_filter) {
            $query_args['author'] = $author_filter;
        } elseif (!$is_admin) {
            $query_args['author'] = $current_user_id;
        }
        // If admin and no filter, show all (no author restriction)
        
        $query = new WP_Query($query_args);
        
        $form_url = get_permalink(get_page_by_path('fdap-2'));
        
        // Get all authors with FDAP posts (for admin filter)
        $authors = [];
        if ($is_admin) {
            $authors = get_users([
                'has_published_posts' => ['fdap'],
                'orderby' => 'display_name',
            ]);
        }
        
        ob_start();
        ?>
        <div class="fdap-dashboard">
            <div class="fdap-header">
                <h2><?php echo $is_admin && !$author_filter ? 'Toutes les fiches FDAP' : 'Mes fiches'; ?></h2>
                <a href="<?php echo esc_url($form_url); ?>" class="fdap-btn-new">+ Nouvelle activité</a>
            </div>
            
            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'saved'): ?>
                <div class="fdap-success">Fiche enregistrée !</div>
            <?php endif; ?>
            
            <?php if ($is_admin): ?>
                <!-- Admin filter by student -->
                <div class="fdap-admin-filter">
                    <form method="get" class="fdap-filter-form">
                        <?php foreach ($_GET as $key => $value): ?>
                            <?php if ($key !== 'author_filter'): ?>
                                <input type="hidden" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>">
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <label>Filtrer par élève :</label>
                        <select name="author_filter" class="fdap-filter-select" onchange="this.form.submit()">
                            <option value="">Tous les élèves</option>
                            <?php foreach ($authors as $author): ?>
                                <option value="<?php echo $author->ID; ?>" <?php selected($author_filter, $author->ID); ?>>
                                    <?php echo esc_html($author->display_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
            <?php endif; ?>
            
            <?php if ($query->have_posts()): ?>
                <div class="fdap-table-wrapper">
                    <table class="fdap-table">
                        <thead>
                            <tr>
                                <th>Élève / Titre</th>
                                <th>Date</th>
                                <th>Lieu</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($query->have_posts()): $query->the_post(); ?>
                                <?php 
                                $post_id = get_the_ID();
                                $post_author_id = get_the_author_ID();
                                $nom = get_post_meta($post_id, '_fdap_nom_prenom', true);
                                $lieu = get_post_meta($post_id, '_fdap_lieu_', true);
                                $lieu_label = $lieu === 'lycee' ? 'Lycée' : ($lieu === 'pfmp' ? 'PFMP' : '-');
                                $status = get_post_status();
                                
                                $status_labels = [
                                    'publish' => ['label' => '📤 Publiée', 'class' => 'fdap-status--published'],
                                    'controlled' => ['label' => '✅ Contrôlée', 'class' => 'fdap-status--controlled'],
                                ];
                                $status_info = $status_labels[$status] ?? ['label' => $status, 'class' => ''];
                                
                                $can_edit = ($post_author_id == $current_user_id) || $is_admin;
                                ?>
                                <tr>
                                    <td class="fdap-title-cell">
                                        <a href="<?php the_permalink(); ?>" class="fdap-link"><?php echo esc_html($nom ?: get_the_title()); ?></a>
                                        <?php if ($is_admin && $post_author_id != $current_user_id): ?>
                                            <span class="fdap-author-tag">par <?php the_author(); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo get_the_date('d/m/Y'); ?></td>
                                    <td><?php echo esc_html($lieu_label); ?></td>
                                    <td><span class="fdap-status <?php echo $status_info['class']; ?>"><?php echo $status_info['label']; ?></span></td>
                                    <td class="fdap-actions">
                                        <a href="<?php the_permalink(); ?>" class="fdap-btn fdap-btn--view" title="Voir">👁</a>
                                        <?php if ($can_edit): ?>
                                            <a href="<?php echo add_query_arg('fdap_id', $post_id, $form_url); ?>" class="fdap-btn fdap-btn--edit" title="Modifier">✏️</a>
                                        <?php endif; ?>
                                        <?php if ($post_author_id == $current_user_id || current_user_can('delete_others_posts')): ?>
                                            <a href="<?php echo add_query_arg(['action' => 'delete', 'fdap_id' => $post_id]); ?>" class="fdap-btn fdap-btn--delete" title="Supprimer" onclick="return confirm('Supprimer cette fiche ?')">🗑</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="fdap-empty">
                    <p>Aucune fiche pour le moment.</p>
                    <a href="<?php echo esc_url($form_url); ?>" class="fdap-btn-new">Créer ma première fiche</a>
                </div>
            <?php endif; wp_reset_postdata(); ?>
        </div>
        
        <style>
            .fdap-admin-filter {
                background: #f8fafc;
                padding: 15px;
                border-radius: 8px;
                margin-bottom: 20px;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .fdap-admin-filter label {
                font-weight: 600;
                color: #1e293b;
            }
            .fdap-filter-select {
                padding: 8px 12px;
                border: 1px solid #e2e8f0;
                border-radius: 6px;
                background: #fff;
                min-width: 200px;
            }
            .fdap-author-tag {
                display: block;
                font-size: 0.85em;
                color: #666;
                margin-top: 4px;
            }
            /* Status badges */
            .fdap-status {
                display: inline-block;
                padding: 6px 12px;
                border-radius: 20px;
                font-size: 13px;
                font-weight: 600;
            }
            .fdap-status--published {
                background: #dbeafe;
                color: #1d4ed8;
            }
            .fdap-status--controlled {
                background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                color: #fff;
                animation: pulse-controlled 2s infinite;
            }
            @keyframes pulse-controlled {
                0%, 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
                50% { box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
            }
                background: #fef3c7;
                color: #d97706;
            }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Save fiche
     */
    private function save_fiche() {
        $user_id = get_current_user_id();
        $is_admin = current_user_can('edit_others_posts');
        
        $post_data = [
            'post_title' => sanitize_text_field($_POST['post_title'] ?? 'Nouvelle fiche'),
            'post_type' => 'fdap',
            'post_status' => 'publish',
        ];
        
        $action = sanitize_text_field($_POST['fdap_action'] ?? 'create');
        
        if ($action === 'update' && isset($_POST['fdap_id'])) {
            $post_id = (int) $_POST['fdap_id'];
            
            // Verify ownership or admin
            $existing_post = get_post($post_id);
            if (!$existing_post || $existing_post->post_type !== 'fdap') {
                return false;
            }
            
            // Only allow owner or admin to edit
            if ($existing_post->post_author != $user_id && !$is_admin) {
                return false;
            }
            
            $post_data['ID'] = $post_id;
            wp_update_post($post_data);

            // Si un élève modifie une fiche contrôlée, repasser en publié
            if (!$is_admin && get_post_status($post_id) === "controlled") {
                wp_update_post(["ID" => $post_id, "post_status" => "publish"]);
            }
        } else {
            $post_data['post_author'] = $user_id;
            $post_id = wp_insert_post($post_data);
        }
        
        if (is_wp_error($post_id) || !$post_id) {
            return false;
        }
        
        // Save meta fields
        $fields = $this->get_all_fields();
        foreach ($fields as $field) {
            if (isset($_POST['fdap_' . $field])) {
                $value = $_POST['fdap_' . $field];
                if (is_array($value)) {
                    $value = array_map('sanitize_text_field', $value);
                } else {
                    $value = sanitize_textarea_field($value);
                }
                update_post_meta($post_id, '_fdap_' . $field, $value);
            }
        }
        
        // Add to portfolio category
        $cat = get_term_by('slug', 'portfolio', 'category');
        if ($cat) {
            wp_set_object_terms($post_id, $cat->term_id, 'category');
        }
        
        // Delete comment if requested
        if ($is_admin && isset($_POST["fdap_delete_comment"])) {
            $delete_idx = (int) $_POST["fdap_delete_comment"];
            $comments = get_post_meta($post_id, "_fdap_comments", true);
            if (is_array($comments) && isset($comments[$delete_idx])) {
                // Delete associated audio if exists
                if (!empty($comments[$delete_idx]["audio_id"])) {
                    wp_delete_attachment($comments[$delete_idx]["audio_id"], true);
                }
                array_splice($comments, $delete_idx, 1);
                update_post_meta($post_id, "_fdap_comments", $comments);
                // Redirect back to the same page
                wp_redirect(add_query_arg(["msg" => "deleted", "fdap_id" => $post_id], get_permalink(get_page_by_path("fdap-2"))));
                exit;
            }
        }

        // Save comments (admin only)
        if ($is_admin && (isset($_POST["fdap_comment_text"]) || isset($_POST["fdap_comment_audio_data"]))) {
            $comment_text = isset($_POST["fdap_comment_text"]) ? sanitize_textarea_field($_POST["fdap_comment_text"]) : "";
            $comment_audio_data = isset($_POST["fdap_comment_audio_data"]) ? $_POST["fdap_comment_audio_data"] : "";
            
            if (!empty($comment_text) || !empty($comment_audio_data)) {
                $comments = get_post_meta($post_id, "_fdap_comments", true);
                if (!is_array($comments)) $comments = [];
                
                $new_comment = [
                    "date" => current_time("mysql"),
                ];
                
                if (!empty($comment_text)) {
                    $new_comment["text"] = $comment_text;
                }
                
                if (!empty($comment_audio_data)) {
                    $audio_data = str_replace("data:audio/webm;base64,", "", $comment_audio_data);
                    $audio_data = str_replace("data:audio/ogg;base64,", "", $audio_data);
                    $audio_data = base64_decode($audio_data);
                    
                    $upload_dir = wp_upload_dir();
                    $filename = "comment-" . $post_id . "-" . time() . ".webm";
                    $filepath = $upload_dir["path"] . "/" . $filename;
                    
                    if (file_put_contents($filepath, $audio_data)) {
                        $attachment = [
                            "post_mime_type" => "audio/webm",
                            "post_title" => "Commentaire audio FDAP #" . $post_id,
                            "post_content" => "",
                            "post_status" => "inherit",
                        ];
                        $attach_id = wp_insert_attachment($attachment, $filepath, $post_id);
                        if (!is_wp_error($attach_id)) {
                            $new_comment["audio_id"] = $attach_id;
                        }
                    }
                }
                
                $comments[] = $new_comment;
                update_post_meta($post_id, "_fdap_comments", $comments);
                // Redirect back to the same page
                wp_redirect(add_query_arg(["msg" => "deleted", "fdap_id" => $post_id], get_permalink(get_page_by_path("fdap-2"))));
                exit;
                
                // Change status to controlled
                wp_update_post(["ID" => $post_id, "post_status" => "controlled"]);
            }
        }
        
        // Handle file uploads
        if (!function_exists('wp_generate_attachment_metadata')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }
        
        // Photo fields (1-6)
        for ($i = 1; $i <= 6; $i++) {
            $keep_photo_id = isset($_POST['fdap_keep_photo_' . $i]) ? (int) $_POST['fdap_keep_photo_' . $i] : 0;
            $current_photo_id = get_post_meta($post_id, '_fdap_photo_' . $i, true);
            
            if ($current_photo_id && !$keep_photo_id) {
                wp_delete_attachment($current_photo_id, true);
                delete_post_meta($post_id, '_fdap_photo_' . $i);
            }
            
            $field_name = 'fdap_photo_' . $i;
            if (!empty($_FILES[$field_name]['name']) && $_FILES[$field_name]['error'] === UPLOAD_ERR_OK) {
                $upload = wp_handle_upload($_FILES[$field_name], ['test_form' => false]);
                if (!isset($upload['error']) && isset($upload['file'])) {
                    $attachment = [
                        'post_mime_type' => $upload['type'],
                        'post_title' => sanitize_file_name($_FILES[$field_name]['name']),
                        'post_content' => '',
                        'post_status' => 'inherit',
                    ];
                    $attach_id = wp_insert_attachment($attachment, $upload['file'], $post_id);
                    if (!is_wp_error($attach_id)) {
                        wp_update_attachment_metadata($attach_id, wp_generate_attachment_metadata($attach_id, $upload['file']));
                        update_post_meta($post_id, '_fdap_photo_' . $i, $attach_id);
                    }
                }
            }
        }
        
        // Audio, Video, Fichier
        foreach (['audio', 'video', 'fichier'] as $field) {
            $keep_id = isset($_POST['fdap_keep_' . $field]) ? (int) $_POST['fdap_keep_' . $field] : 0;
            $current_id = get_post_meta($post_id, '_fdap_' . $field, true);
            
            if ($current_id && !$keep_id) {
                wp_delete_attachment($current_id, true);
                delete_post_meta($post_id, '_fdap_' . $field);
            }
            
            if (!empty($_FILES['fdap_' . $field]['name']) && $_FILES['fdap_' . $field]['error'] === UPLOAD_ERR_OK) {
                $upload = wp_handle_upload($_FILES['fdap_' . $field], ['test_form' => false]);
                if (!isset($upload['error']) && isset($upload['file'])) {
                    $attach_id = wp_insert_attachment([
                        'post_mime_type' => $upload['type'],
                        'post_title' => sanitize_file_name($_FILES['fdap_' . $field]['name']),
                        'post_content' => '',
                        'post_status' => 'inherit'
                    ], $upload['file'], $post_id);
                    if (!is_wp_error($attach_id)) {
                        update_post_meta($post_id, '_fdap_' . $field, $attach_id);
                    }
                }
            }
        }
        
        wp_redirect(add_query_arg('msg', 'saved', get_permalink(get_page_by_path('mes-fdap'))));
        exit;
    }
    
    /**
     * Login form - Redirection vers Ultimate Member ou formulaire intégré
     */
    private function login_form() {
        // Si Ultimate Member est actif, afficher un lien vers la page de connexion
        if (class_exists('UM')) {
            $login_page = get_page_by_path('login');
            if ($login_page) {
                $login_url = add_query_arg('redirect_to', urlencode(get_permalink()), get_permalink($login_page));
                ob_start();
                ?>
                <div class="fdap-login-redirect">
                    <div class="fdap-redirect-card">
                        <div class="fdap-redirect-icon">🔒</div>
                        <h3>Connexion requise</h3>
                        <p>Vous devez être connecté pour accéder à cette page.</p>
                        <a href="<?php echo esc_url($login_url); ?>" class="fdap-btn fdap-btn-primary">Se connecter</a>
                    </div>
                </div>
                <style>
                    .fdap-login-redirect {
                        display: flex;
                        justify-content: center;
                        padding: 40px 20px;
                    }
                    .fdap-redirect-card {
                        text-align: center;
                        background: #fff;
                        padding: 40px;
                        border-radius: 16px;
                        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
                        max-width: 400px;
                    }
                    .fdap-redirect-icon {
                        font-size: 48px;
                        margin-bottom: 20px;
                    }
                    .fdap-redirect-card h3 {
                        margin: 0 0 10px 0;
                        color: #1e293b;
                        font-size: 24px;
                    }
                    .fdap-redirect-card p {
                        color: #64748b;
                        margin-bottom: 25px;
                    }
                    .fdap-btn {
                        display: inline-block;
                        padding: 14px 28px;
                        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
                        color: #fff;
                        text-decoration: none;
                        border-radius: 12px;
                        font-weight: 600;
                        transition: all 0.3s ease;
                    }
                    .fdap-btn:hover {
                        transform: translateY(-2px);
                        box-shadow: 0 8px 20px rgba(37, 99, 235, 0.4);
                    }
                </style>
                <?php
                return ob_get_clean();
            }
        }
        
        // Formulaire de fallback si UM n'est pas disponible
        ob_start();
        ?>
        <style>
            .fdap-login-wrap {
                all: initial;
                display: flex;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                min-height: 100vh;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                align-items: center;
                justify-content: center;
                padding: 20px;
                box-sizing: border-box;
            }
            
            .fdap-login-card {
                all: initial;
                display: block;
                width: 100%;
                max-width: 420px;
                background: #ffffff;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                padding: 50px 40px;
                box-sizing: border-box;
                font-family: inherit;
            }
            
            .fdap-login-card * { box-sizing: border-box; }
            
            .fdap-login-title {
                display: block;
                margin: 0 0 8px 0;
                color: #2563eb;
                font-size: 32px;
                font-weight: 700;
                text-align: center;
            }
            
            .fdap-login-subtitle {
                display: block;
                margin: 0 0 35px 0;
                color: #64748b;
                font-size: 16px;
                text-align: center;
            }
            
            .fdap-login-form { display: block; }
            
            .fdap-field-wrap {
                display: block;
                margin-bottom: 25px;
            }
            
            .fdap-field-wrap label {
                display: block;
                font-size: 14px;
                font-weight: 600;
                color: #1e293b;
                margin-bottom: 10px;
            }
            
            .fdap-field-wrap input[type="text"],
            .fdap-field-wrap input[type="password"] {
                display: block;
                width: 100%;
                padding: 18px 20px;
                border: 2px solid #e2e8f0;
                border-radius: 14px;
                font-size: 16px;
                background: #f8fafc;
                transition: all 0.3s ease;
                font-family: inherit;
            }
            
            .fdap-field-wrap input:focus {
                outline: none;
                border-color: #2563eb;
                background: #ffffff;
                box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
            }
            
            .fdap-remember-wrap {
                display: flex;
                align-items: center;
                gap: 12px;
                margin-bottom: 25px;
            }
            
            .fdap-remember-wrap input[type="checkbox"] {
                width: 20px;
                height: 20px;
                accent-color: #2563eb;
            }
            
            .fdap-remember-wrap label {
                font-size: 14px;
                color: #64748b;
                margin: 0;
            }
            
            .fdap-login-btn {
                display: block;
                width: 100%;
                padding: 18px;
                background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
                color: #ffffff;
                border: none;
                border-radius: 14px;
                font-size: 17px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                box-shadow: 0 8px 20px rgba(37, 99, 235, 0.4);
                font-family: inherit;
            }
            
            .fdap-login-btn:hover {
                transform: translateY(-3px);
                box-shadow: 0 12px 28px rgba(37, 99, 235, 0.5);
            }
            
            .fdap-login-links {
                display: flex;
                justify-content: center;
                gap: 30px;
                margin-top: 30px;
                padding-top: 25px;
                border-top: 1px solid #e2e8f0;
            }
            
            .fdap-login-links a {
                color: #2563eb;
                text-decoration: none;
                font-size: 15px;
                font-weight: 500;
                transition: color 0.3s ease;
            }
            
            .fdap-login-links a:hover {
                color: #1d4ed8;
                text-decoration: underline;
            }
            
            .fdap-error-box {
                display: block;
                background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
                color: #ffffff;
                padding: 16px 20px;
                border-radius: 14px;
                margin-bottom: 25px;
                font-size: 15px;
                font-weight: 500;
            }
            
            @media (max-width: 480px) {
                .fdap-login-card { padding: 35px 25px; border-radius: 16px; }
                .fdap-login-title { font-size: 26px; }
                .fdap-login-links { flex-direction: column; text-align: center; gap: 15px; }
            }
        </style>
        
        <div class="fdap-login-wrap">
            <div class="fdap-login-card">
                <div class="fdap-login-title">🔒 Connexion</div>
                <div class="fdap-login-subtitle">Accédez à votre espace FDAP</div>
                
                <?php if (isset($_GET['login']) && $_GET['login'] === 'failed'): ?>
                    <div class="fdap-error-box">Identifiants incorrects. Veuillez réessayer.</div>
                <?php endif; ?>
                
                <form method="post" action="<?php echo esc_url(site_url('/wp-login.php')); ?>" class="fdap-login-form">
                    <input type="hidden" name="redirect_to" value="<?php echo esc_url(get_permalink()); ?>">
                    
                    <div class="fdap-field-wrap">
                        <label for="fdap-user">Identifiant</label>
                        <input type="text" name="log" id="fdap-user" placeholder="Votre nom d'utilisateur" required autocomplete="username">
                    </div>
                    
                    <div class="fdap-field-wrap">
                        <label for="fdap-pass">Mot de passe</label>
                        <input type="password" name="pwd" id="fdap-pass" placeholder="Votre mot de passe" required autocomplete="current-password">
                    </div>
                    
                    <div class="fdap-remember-wrap">
                        <input type="checkbox" name="rememberme" id="fdap-remember" value="forever">
                        <label for="fdap-remember">Se souvenir de moi</label>
                    </div>
                    
                    <button type="submit" class="fdap-login-btn">Se connecter</button>
                </form>
                
                <div class="fdap-login-links">
                    <a href="<?php echo esc_url(wp_registration_url()); ?>">Créer un compte</a>
                    <a href="<?php echo esc_url(wp_lostpassword_url()); ?>">Mot de passe oublié ?</a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
