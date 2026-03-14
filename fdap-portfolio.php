<?php
/**
 * Plugin Name: FDAP Portfolio
 * Description: Fiches d'activités pédagogiques pour portfolios étudiants.
 * Version: 1.0.0
 * Author: Patrick L'Hôte
 * Text Domain: fdap-portfolio
 */

defined('ABSPATH') || exit;

// Constants
define('FDAP_VERSION', '1.0.1');
define('FDAP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FDAP_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load classes
require_once FDAP_PLUGIN_DIR . 'includes/class-post-type.php';
require_once FDAP_PLUGIN_DIR . 'includes/class-shortcodes.php';

// Init
add_action('plugins_loaded', function() {
    // Register CPT
    new FDAP_Post_Type();
    
    // Register shortcodes
    new FDAP_Shortcodes();
    
    // Add upload compression
    add_filter('wp_handle_upload_prefilter', 'fdap_compress_image');
    
    // Template loader for single-fdap
    add_filter('single_template', function($template) {
        global $post;
        if ($post->post_type === 'fdap') {
            $plugin_template = FDAP_PLUGIN_DIR . 'templates/single-fdap.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        return $template;
    });
});

// Activation
register_activation_hook(__FILE__, function() {
    FDAP_Post_Type::register();
    flush_rewrite_rules();
    
    // Create portfolio category
    if (!get_term_by('slug', 'portfolio', 'category')) {
        wp_insert_term('Portfolio', 'category', ['slug' => 'portfolio']);
    }
    
    // Create pages
    $form_page = get_page_by_path('fdap-2');
    if (!$form_page) {
        wp_insert_post([
            'post_title' => 'Formulaire FDAP',
            'post_name' => 'fdap-2',
            'post_content' => '[app_formulaire]',
            'post_status' => 'publish',
            'post_type' => 'page',
        ]);
    }
    
    $dash_page = get_page_by_path('mes-fdap');
    if (!$dash_page) {
        wp_insert_post([
            'post_title' => 'Mes FDAP',
            'post_name' => 'mes-fdap',
            'post_content' => '[mes_fiches]',
            'post_status' => 'publish',
            'post_type' => 'page',
        ]);
    }
});

/**
 * Compress uploaded images to 300KB max
 */
function fdap_compress_image($file) {
    if (!in_array($file['type'] ?? '', ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'])) {
        return $file;
    }
    
    if (!file_exists($file['tmp_name'])) {
        return $file;
    }
    
    if (!class_exists('Imagick')) {
        return $file;
    }
    
    try {
        $image = new Imagick($file['tmp_name']);
        $image->autoOrient();
        $image->setImageFormat('jpeg');
        
        // Resize if needed
        $geometry = $image->getImageGeometry();
        if ($geometry['width'] > 1920 || $geometry['height'] > 1920) {
            $image->resizeImage(1920, 1920, Imagick::FILTER_LANCZOS, 1, true);
        }
        
        // Compress iteratively
        $quality = 75;
        while ($quality >= 40 && $image->getImageBlob() && strlen($image->getImageBlob()) > 300000) {
            $image->setImageCompressionQuality($quality);
            $quality -= 5;
        }
        
        $image->stripImage();
        $image->writeImage($file['tmp_name']);
        $image->destroy();
        
        clearstatcache(true, $file['tmp_name']);
        $file['size'] = filesize($file['tmp_name']);
        
    } catch (Exception $e) {
        error_log('[FDAP] Compression error: ' . $e->getMessage());
    }
    
    return $file;
}

// Enqueue styles
add_action('wp_enqueue_scripts', function() {
    if (is_page('fdap-2') || is_page('mes-fdap') || is_singular('fdap')) {
        wp_enqueue_style('fdap-style', FDAP_PLUGIN_URL . 'assets/css/style.css', [], FDAP_VERSION);
    }
});