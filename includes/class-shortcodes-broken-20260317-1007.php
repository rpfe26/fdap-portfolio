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
        if (!is_user_logged_in()) return $this->login_form();
        
        if (isset($_GET['msg']) && $_GET['msg'] === 'saved') {
            return '<div class="fdap-success">✓ Fiche enregistrée avec succès !</div>';
        }
        
        $post_id = isset($_GET['fdap_id']) ? (int) $_GET['fdap_id'] : 0;
        $values = [];
        
        if ($post_id) {
            $post = get_post($post_id);
            if ($post && $post->post_type === 'fdap' && ($post->post_author == get_current_user_id() || current_user_can('edit_others_posts'))) {
                foreach ($this->get_all_fields() as $field) {
                    $values[$field] = get_post_meta($post_id, '_fdap_' . $field, true);
                }
            }
        }
        
        ob_start();
        include FDAP_PLUGIN_DIR . 'includes/form-fields.php';
        return ob_get_clean();
    }
    
    private function get_all_fields() {
        return ['nom_prenom', 'date_de_saisie', 'lieu_', 'enseigne_', 'lieu_specifique',
            'domaine', 'competences', 'autonomie', 'materiels', 'commanditaire', 
            'contraintes', 'consignes_recues', 'avec_qui_', 'deroulement', 
            'resultats_', 'difficulte', 'plaisir_', 'ameliorations', 'audio', 'video', 'fichier'];
    }
    
    public function dashboard_shortcode($atts) {
        if (!is_user_logged_in()) return $this->login_form();
        
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
                        <thead><tr><th>Titre</th><th>Date</th><th>Lieu</th><th>Statut</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php while ($query->have_posts()): $query->the_post();
                            $post_id = get_the_ID();
                            $nom = get_post_meta($post_id, '_fdap_nom_prenom', true);
                            $lieu = get_the_title();
                            $lieu_label = $lieu === 'lycee' ? 'Lycée' : ($lieu === 'pfmp' ? 'PFMP' : '-');
                            $status = get_post_status();
                            $status_label = $status === 'publish' ? 'Publiée' : 'Brouillon';
                            $status_class = $status === 'publish' ? 'published' : 'draft';
                        ?>
                            <tr>
                                <td class="fdap-title-cell"><a href="<?php the_permalink(); ?>" class="fdap-link"><?php echo esc_html($nom ?: get_the_title()); ?></a></td>
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
                <div class="fdap-empty"><p>Aucune fiche pour le moment.</p><a href="<?php echo esc_url($form_url); ?>" class="fdap-btn-new">Créer ma première fiche</a></div>
            <?php endif; wp_reset_postdata(); ?>
        </div>
        <?php return ob_get_clean();
    }
    
    private function save_fiche() {
        // IMPORTANT: Charger les fichiers admin AVANT tout traitement
        if (!function_exists('wp_handle_upload')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }
        
        $user_id = get_current_user_id();
        $post_data = [
            'post_title' => sanitize_text_field($_POST['post_title'] ?? 'Nouvelle fiche'),
            'post_type' => 'fdap',
            'post_status' => 'publish',
            'post_author' => $user_id,
        ];
        
        $action = sanitize_text_field($_POST['fdap_action'] ?? 'create');
        $post_id = 0;
        
        if ($action === 'update' && isset($_POST['fdap_id'])) {
            $post_id = (int) $_POST['fdap_id'];
            $post_data['ID'] = $post_id;
            wp_update_post($post_data);
        } else {
            $post_id = wp_insert_post($post_data);
        }
        
        if (is_wp_error($post_id) || !$post_id) return false;
        
        foreach ($this->get_all_fields() as $field) {
            if (isset($_POST['fdap_' . $field])) {
                $value = is_array($_POST['fdap_' . $field]) 
                    ? array_map('sanitize_text_field', $_POST['fdap_' . $field])
                    : sanitize_textarea_field($_POST['fdap_' . $field]);
                update_post_meta($post_id, '_fdap_' . $field, $value);
            }
        }
        
        $cat = get_term_by('slug', 'portfolio', 'category');
        if ($cat) wp_set_object_terms($post_id, $cat->term_id, 'category');
        
        // Photos (1-6)
        for ($i = 1; $i <= 6; $i++) {
            $keep = isset($_POST['fdap_keep_photo_' . $i]) ? (int) $_POST['fdap_keep_photo_' . $i] : 0;
            $current = get_post_meta($post_id, '_fdap_photo_' . $i, true);
            if ($current && !$keep) { wp_delete_attachment($current, true); delete_post_meta($post_id, '_fdap_photo_' . $i); }
            
            $field = 'fdap_photo_' . $i;
            if (!empty($_FILES[$field]['name']) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                $upload = wp_handle_upload($_FILES[$field], ['test_form' => false]);
                if (!isset($upload['error']) && isset($upload['file'])) {
                    $attach_id = wp_insert_attachment(['post_mime_type' => $upload['type'], 'post_title' => sanitize_file_name($_FILES[$field]['name']), 'post_content' => '', 'post_status' => 'inherit'], $upload['file'], $post_id);
                    if (!is_wp_error($attach_id)) {
                        wp_update_attachment_metadata($attach_id, wp_generate_attachment_metadata($attach_id, $upload['file']));
                        update_post_meta($post_id, '_fdap_photo_' . $i, $attach_id);
                    }
                }
            }
        }
        
        // Audio, Video, Fichier
        foreach (['audio', 'video', 'fichier'] as $type) {
            $keep = isset($_POST['fdap_keep_' . $type]) ? (int) $_POST['fdap_keep_' . $type] : 0;
            $current = get_post_meta($post_id, '_fdap_' . $type, true);
            if ($current && !$keep) { wp_delete_attachment($current, true); delete_post_meta($post_id, '_fdap_' . $type); }
            
            if (!empty($_FILES['fdap_' . $type]['name']) && $_FILES['fdap_' . $type]['error'] === UPLOAD_ERR_OK) {
                $upload = wp_handle_upload($_FILES['fdap_' . $type], ['test_form' => false]);
                if (!isset($upload['error']) && isset($upload['file'])) {
                    $attach_id = wp_insert_attachment(['post_mime_type' => $upload['type'], 'post_title' => sanitize_file_name($_FILES['fdap_' . $type]['name']), 'post_content' => '', 'post_status' => 'inherit'], $upload['file'], $post_id);
                    if (!is_wp_error($attach_id)) update_post_meta($post_id, '_fdap_' . $type, $attach_id);
                }
            }
        }
        return true;
    }
    
    private function login_form() {
        ob_start();
        ?>
        <style>.fdap-login-wrap{all:initial;display:flex;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;min-height:100vh;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);align-items:center;justify-content:center;padding:20px;box-sizing:border-box}.fdap-login-card{all:initial;display:block;width:100%;max-width:420px;background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.3);padding:50px 40px;box-sizing:border-box;font-family:inherit}.fdap-login-card *{box-sizing:border-box}.fdap-login-title{margin:0 0 8px;color:#2563eb;font-size:32px;font-weight:700;text-align:center}.fdap-login-subtitle{margin:0 0 35px;color:#64748b;font-size:16px;text-align:center}.fdap-field-wrap{margin-bottom:25px}.fdap-field-wrap label{display:block;font-size:14px;font-weight:600;color:#1e293b;margin-bottom:10px}.fdap-field-wrap input{width:100%;padding:18px 20px;border:2px solid #e2e8f0;border-radius:14px;font-size:16px;background:#f8fafc}.fdap-field-wrap input:focus{outline:none;border-color:#2563eb;background:#fff;box-shadow:0 0 0 4px rgba(37,99,235,.15)}.fdap-remember-wrap{display:flex;align-items:center;gap:12px;margin-bottom:25px}.fdap-remember-wrap input{width:20px;height:20px;accent-color:#2563eb}.fdap-remember-wrap label{font-size:14px;color:#64748b;margin:0}.fdap-login-btn{width:100%;padding:18px;background:linear-gradient(135deg,#2563eb 0%,#1d4ed8 100%);color:#fff;border:none;border-radius:14px;font-size:17px;font-weight:600;cursor:pointer;box-shadow:0 8px 20px rgba(37,99,235,.4)}.fdap-login-btn:hover{transform:translateY(-3px);box-shadow:0 12px 28px rgba(37,99,235,.5)}.fdap-login-links{display:flex;justify-content:center;gap:30px;margin-top:30px;padding-top:25px;border-top:1px solid #e2e8f0}.fdap-login-links a{color:#2563eb;text-decoration:none;font-size:15px;font-weight:500}.fdap-error-box{background:linear-gradient(135deg,#ef4444 0%,#dc2626 100%);color:#fff;padding:16px 20px;border-radius:14px;margin-bottom:25px;font-size:15px;font-weight:500}</style>
        <div class="fdap-login-wrap"><div class="fdap-login-card"><div class="fdap-login-title">🔒 Connexion</div><div class="fdap-login-subtitle">Accédez à votre espace FDAP</div>
        <?php if (isset($_GET['login']) && $_GET['login'] === 'failed'): ?><div class="fdap-error-box">Identifiants incorrects</div><?php endif; ?>
        <form method="post" action="<?php echo esc_url(site_url('/wp-login.php')); ?>"><input type="hidden" name="redirect_to" value="<?php echo esc_url(get_permalink()); ?>">
        <div class="fdap-field-wrap"><label for="fdap-user">Identifiant</label><input type="text" name="log" id="fdap-user" placeholder="Votre nom d'utilisateur" required autocomplete="username"></div>
        <div class="fdap-field-wrap"><label for="fdap-pass">Mot de passe</label><input type="password" name="pwd" id="fdap-pass" placeholder="Votre mot de passe" required autocomplete="current-password"></div>
        <div class="fdap-remember-wrap"><input type="checkbox" name="rememberme" id="fdap-remember" value="forever"><label for="fdap-remember">Se souvenir de moi</label></div>
        <button type="submit" class="fdap-login-btn">Se connecter</button></form>
        <div class="fdap-login-links"><a href="<?php echo esc_url(wp_registration_url()); ?>">Créer un compte</a><a href="<?php echo esc_url(wp_lostpassword_url()); ?>">Mot de passe oublié ?</a></div></div></div>
        <?php return ob_get_clean();
    }
}
