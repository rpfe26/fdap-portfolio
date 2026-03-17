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
        add_action('template_redirect', [$this, 'handle_form_submission']);
    }
    
    public function enqueue_styles() {
        if (is_page('fdap-2') || is_page('mes-fdap') || is_singular('fdap')) {
            wp_enqueue_style('fdap-style', FDAP_PLUGIN_URL . 'assets/css/style.css', [], FDAP_VERSION);
        }
    }
    
    public function handle_form_submission() {
        if (!is_page('fdap-2')) return;
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['fdap_nonce'])) return;
        if (!wp_verify_nonce($_POST['fdap_nonce'], 'fdap_form_submit')) return;
        
        $this->save_fiche();
        wp_redirect(add_query_arg('msg', 'saved', get_permalink(get_page_by_path('mes-fdap'))));
        exit;
    }
    
    public function form_shortcode($atts) {
        if (!is_user_logged_in()) {
            return $this->login_form();
        }
        
        // Afficher message de succès si redirect
        if (isset($_GET['msg']) && $_GET['msg'] === 'saved') {
            return '<div class="fdap-success">✓ Fiche enregistrée avec succès !</div>';
        }
        
        $post_id = isset($_GET['fdap_id']) ? (int) $_GET['fdap_id'] : 0;
        $values = [];
        
        if ($post_id) {
            $post = get_post($post_id);
            if ($post && $post->post_type === 'fdap' && ($post->post_author == get_current_user_id() || current_user_can('edit_others_posts'))) {
                $fields = $this->get_all_fields();
                foreach ($fields as $field) {
                    $values[$field] = get_post_meta($post_id, '_fdap_' . $field, true);
                }
            }
        }
        
        ob_start();
        include FDAP_PLUGIN_DIR . 'includes/form-fields.php';
        return ob_get_clean();
    }
    
    private function get_all_fields() {
        return [
            'nom_prenom', 'date_de_saisie', 'lieu_', 'enseigne_', 'lieu_specifique',
            'domaine', 'competences', 'autonomie', 'materiels',
            'commanditaire', 'contraintes', 'consignes_recues', 'avec_qui_',
            'deroulement', 'resultats_', 'difficulte', 'plaisir_', 'ameliorations',
            'audio', 'video', 'fichier'
        ];
    }
    
    public function dashboard_shortcode($atts) {
        if (!is_user_logged_in()) {
            return $this->login_form();
        }
        
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['fdap_id'])) {
            $del_id = (int) $_GET['fdap_id'];
            if (get_post_field('post_author', $del_id) == get_current_user_id()) {
                wp_trash_post($del_id);
            }
        }
        
        $query = new WP_Query([
            'post_type' => 'fdap',
            'author' => current_user_can('edit_others_posts') ? '' : get_current_user_id(),
            'posts_per_page' => -1,
            'post_status' => ['publish', 'draft'],
        ]);
        
        $form_url = get_permalink(get_page_by_path('fdap-2'));
        
        ob_start();
        ?>
        <div class="fdap-dashboard">
            <div class="fdap-header">
                <h2>Mes fiches</h2>
                <a href="<?php echo esc_url($form_url); ?>" class="fdap-btn-new">+ Nouvelle activité</a>
            </div>
            
            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'saved'): ?>
                <div class="fdap-success">Fiche enregistrée !</div>
            <?php endif; ?>
            
            <?php if ($query->have_posts()): ?>
                <div class="fdap-table-wrapper">
                    <table class="fdap-table">
                        <thead>
                            <tr>
                                <th>Titre</th>
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
                                $nom = get_post_meta($post_id, '_fdap_nom_prenom', true);
                                $lieu = get_the_title();
                                $lieu_label = $lieu === 'lycee' ? 'Lycée' : ($lieu === 'pfmp' ? 'PFMP' : '-');
                                $status = get_post_status();
                                $status_label = $status === 'publish' ? 'Publiée' : 'Brouillon';
                                $status_class = $status === 'publish' ? 'published' : 'draft';
                                ?>
                                <tr>
                                    <td class="fdap-title-cell">
                                        <a href="<?php the_permalink(); ?>" class="fdap-link"><?php echo esc_html($nom ?: get_the_title()); ?></a>
                                    </td>
                                    <td><?php echo get_the_date('d/m/Y'); ?></td>
                                    <td><?php echo esc_html($lieu_label); ?></td>
                                    <td><span class="fdap-status fdap-status--<?php echo $status_class; ?>"><?php echo $status_label; ?></span></td>
                                    <td class="fdap-actions">
                                        <a href="<?php the_permalink(); ?>" class="fdap-btn fdap-btn--view" title="Voir">👁</a>
                                        <a href="<?php echo add_query_arg('fdap_id', $post_id, $form_url); ?>" class="fdap-btn fdap-btn--edit" title="Modifier">✏️</a>
                                        <a href="<?php echo add_query_arg(['action' => 'delete', 'fdap_id' => $post_id]); ?>" class="fdap-btn fdap-btn--delete" title="Supprimer" onclick="return confirm('Supprimer cette fiche ?')">🗑</a>
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
        <?php
        return ob_get_clean();
    }
    
    private function save_fiche() {
        $user_id = get_current_user_id();
        
        $post_data = [
            'post_title' => sanitize_text_field($_POST['post_title'] ?? 'Nouvelle fiche'),
            'post_type' => 'fdap',
            'post_status' => 'publish',
            'post_author' => $user_id,
        ];
        
        $action = sanitize_text_field($_POST['fdap_action'] ?? 'create');
        
        if ($action === 'update' && isset($_POST['fdap_id'])) {
            $post_id = (int) $_POST['fdap_id'];
            $post_data['ID'] = $post_id;
            wp_update_post($post_data);
        } else {
            $post_id = wp_insert_post($post_data);
        }
        
        if (is_wp_error($post_id) || !$post_id) {
            return false;
        }
        
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
        
        $cat = get_term_by('slug', 'portfolio', 'category');
        if ($cat) {
            wp_set_object_terms($post_id, $cat->term_id, 'category');
        }
        
        if (!function_exists("wp_generate_attachment_metadata")) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }
        
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
        
        return true;
    }
    
    private function login_form() {
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
