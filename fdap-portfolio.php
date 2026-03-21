<?php
/**
 * Plugin Name: FDAP Portfolio
 * Description: Fiches d'activités pédagogiques pour portfolios étudiants.
 * Version: 1.0.3
 * Author: Patrick L'Hôte
 * Text Domain: fdap-portfolio
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

defined('ABSPATH') || exit;

// Constants
define('FDAP_VERSION', '1.0.6');
define('FDAP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FDAP_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load classes - late priority to avoid conflicts
add_action('plugins_loaded', function() {
    require_once FDAP_PLUGIN_DIR . 'includes/class-post-type.php';
    require_once FDAP_PLUGIN_DIR . 'includes/class-shortcodes.php';
    require_once FDAP_PLUGIN_DIR . 'includes/class-admin.php';
    
    // Register CPT
    new FDAP_Post_Type();
    
    // Register shortcodes
    new FDAP_Shortcodes();
    
    // Admin dashboard (only in admin)
    if (is_admin()) {
        new FDAP_Admin();
    }
}, 20);

// Template loader for single-fdap
add_filter('single_template', function($template) {
    if (is_admin()) {
        return $template;
    }
    
    global $post;
    if (!$post || !is_object($post)) {
        return $template;
    }
    
    if (get_post_type($post) === 'fdap') {
        $plugin_template = FDAP_PLUGIN_DIR . 'templates/single-fdap.php';
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
    }
    
    return $template;
}, 20);

// Activation
register_activation_hook(__FILE__, function() {
    require_once FDAP_PLUGIN_DIR . 'includes/class-post-type.php';
    FDAP_Post_Type::register();
    flush_rewrite_rules();
});

// Deactivation
register_deactivation_hook(__FILE__, function() {
    flush_rewrite_rules();
});

/**
 * Compress uploaded images - ONLY for FDAP uploads
 * Checks for fdap_nonce to identify FDAP form submissions
 */
add_filter('wp_handle_upload', function($upload, $context = 'upload') {
    // Only process image files from FDAP forms
    if (!in_array($upload['type'] ?? '', ['image/jpeg', 'image/png', 'image/webp'])) {
        return $upload;
    }
    
    // Check if this is an FDAP upload
    if (!isset($_POST['fdap_nonce']) || !wp_verify_nonce($_POST['fdap_nonce'], 'fdap_form_submit')) {
        return $upload;
    }
    
    // Skip if file doesn't exist
    if (!file_exists($upload['file'])) {
        return $upload;
    }
    
    // Check for Imagick
    if (!class_exists('Imagick')) {
        return $upload;
    }
    
    try {
        $image = new Imagick($upload['file']);
        $image->autoOrient();
        
        // Convert to JPEG
        $image->setImageFormat('jpeg');
        
        // Resize if needed (max 1920px)
        $geometry = $image->getImageGeometry();
        if ($geometry['width'] > 1920 || $geometry['height'] > 1920) {
            $image->resizeImage(1920, 1920, Imagick::FILTER_LANCZOS, 1, true);
        }
        
        // Compress to 300KB max
        $quality = 75;
        $maxSize = 300000;
        $blob = $image->getImageBlob();
        
        while ($quality >= 40 && strlen($blob) > $maxSize) {
            $image->setImageCompressionQuality($quality);
            $blob = $image->getImageBlob();
            $quality -= 5;
        }
        
        $image->stripImage();
        $image->writeImage($upload['file']);
        $image->destroy();
        
        // Update file size
        $upload['size'] = filesize($upload['file']);
        
    } catch (Exception $e) {
        // Log error but don't break upload
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[FDAP] Image compression error: ' . $e->getMessage());
        }
    }
    
    return $upload;
}, 10, 2);

// Enqueue styles - ONLY on FDAP pages
add_action('wp_enqueue_scripts', function() {
    global $post;
    
    // Don't load on admin pages
    if (is_admin()) {
        return;
    }
    
    // Only load on FDAP pages
    if (is_page('fdap-2') || is_page('mes-fdap') || (is_singular() && $post && get_post_type($post) === 'fdap')) {
        wp_enqueue_style('fdap-style', FDAP_PLUGIN_URL . 'assets/css/style.css', [], FDAP_VERSION);
    }
});

// Enqueue FDAP audio script on FDAP pages
add_action('wp_enqueue_scripts', function() {
    if (is_page('fdap-2') || (get_query_var('post_type') === 'fdap') || strpos($_SERVER['REQUEST_URI'], 'fdap') !== false) {
        wp_enqueue_script('fdap-audio', FDAP_PLUGIN_URL . 'includes/fdap-audio.js', array(), '1.0.6', true);
    }
});
require_once FDAP_PLUGIN_DIR . 'includes/class-export.php';
