<?php
/**
 * Export FDAP to HTML/ZIP
 */

defined('ABSPATH') || exit;

class FDAP_Export {
    
    public static function init() {
        add_action('template_redirect', [__CLASS__, 'handle_export']);
    }
    
    public static function handle_export() {
        if (!isset($_GET['export']) || $_GET['export'] !== 'html') {
            return;
        }
        
        $post_id = get_the_ID();
        if (!$post_id || get_post_type($post_id) !== 'fdap') {
            return;
        }
        
        self::export_fdap($post_id);
    }
    
    public static function export_fdap($post_id) {
        $fields = ['nom_prenom', 'date_de_saisie', 'lieu_', 'enseigne_', 'lieu_specifique',
                   'domaine', 'competences', 'autonomie', 'materiels',
                   'commanditaire', 'contraintes', 'consignes_recues', 'avec_qui_',
                   'deroulement', 'resultats_', 'difficulte', 'plaisir_', 'ameliorations'];
        
        $values = [];
        foreach ($fields as $field) {
            $values[$field] = get_post_meta($post_id, '_fdap_' . $field, true);
        }
        
        $title = get_the_title($post_id);
        $author_name = $values['nom_prenom'] ?: 'Eleve';
        $year = date('Y');
        
        // Get media IDs
        $video_id = get_post_meta($post_id, '_fdap_video', true);
        $audio_id = get_post_meta($post_id, '_fdap_audio', true);
        $fichier_id = get_post_meta($post_id, '_fdap_fichier', true);
        $photos = [];
        for ($i = 1; $i <= 6; $i++) {
            $photo_id = get_post_meta($post_id, '_fdap_photo_' . $i, true);
            if ($photo_id) $photos[] = $photo_id;
        }
        
        // Get comments
        $comments = get_post_meta($post_id, '_fdap_comments', true);
        if (!is_array($comments)) $comments = [];
        
        // Determine export type
        $has_video = $video_id && self::get_file_size($video_id) > 0;
        
        // Generate HTML
        $html = self::generate_html($title, $values, $photos, $audio_id, $video_id, $fichier_id, $comments);
        
        $filename = sanitize_title($author_name . '-' . $title . '-' . $year);
        
        if ($has_video) {
            // Export as ZIP
            self::export_zip($filename, $html, $video_id, $photos, $audio_id);
        } else {
            // Export as HTML
            self::download_html($filename . '.html', $html);
        }
    }
    
    private static function get_file_size($attachment_id) {
        $path = get_attached_file($attachment_id);
        return $path && file_exists($path) ? filesize($path) : 0;
    }
    
    private static function embed_base64($attachment_id, $max_size = 10000000) {
        $path = get_attached_file($attachment_id);
        if (!$path || !file_exists($path)) return '';
        
        if (filesize($path) > $max_size) {
            return wp_get_attachment_url($attachment_id);
        }
        
        $mime = get_post_mime_type($attachment_id);
        $data = base64_encode(file_get_contents($path));
        return 'data:' . $mime . ';base64,' . $data;
    }
    
    private static function generate_html($title, $values, $photos, $audio_id, $video_id, $fichier_id, $comments) {
        $lieu_labels = [
            'lycee' => 'Au Lycée (Plateau technique)',
            'pfmp' => 'En Entreprise (PFMP)'
        ];
        
        $html = '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . esc_html($title) . '</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; max-width: 800px; margin: 20px auto; padding: 30px; background: #f5f5f5; }
        .fiche { background: #fff; border-radius: 8px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #2271b1; margin-bottom: 10px; }
        .subtitle { text-align: center; color: #888; font-size: 14px; margin-bottom: 25px; }
        .section { margin-bottom: 25px; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
        .section-title { background: #2271b1; color: #fff; padding: 12px 15px; margin: 0; font-size: 16px; }
        .section-content { padding: 15px; }
        .field { margin-bottom: 15px; }
        .field:last-child { margin-bottom: 0; }
        .field-label { font-weight: 600; color: #555; font-size: 13px; text-transform: uppercase; margin-bottom: 5px; display: block; }
        .field-value { background: #f8f9fa; padding: 10px; border-radius: 4px; border-left: 3px solid #2271b1; }
        .stars { font-size: 20px; letter-spacing: 2px; }
        .photos { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 10px; }
        .photos img { max-width: 100%; border-radius: 8px; }
        .media { margin-top: 10px; }
        .comments { background: linear-gradient(135deg, #fef3c7 0%, #fcd34d 100%); border: 2px solid #f59e0b; border-radius: 12px; margin-bottom: 25px; overflow: hidden; }
        .comments h3 { background: #f59e0b; color: #fff; padding: 12px 15px; margin: 0; }
        .comments-list { padding: 15px; }
        .comment-entry { background: #fff; border-left: 4px solid #f59e0b; padding: 12px; margin-bottom: 15px; border-radius: 4px; }
        .comment-date { font-size: 12px; color: #888; margin-bottom: 8px; }
        .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #888; font-size: 12px; }
    </style>
</head>
<body>
    <div class="fiche">
        <h1>' . esc_html($title) . '</h1>
        <p class="subtitle">(Fiche d\'Activité Professionnelle)</p>';
        
        // Comments (if any)
        if (!empty($comments)) {
            $html .= '<div class="comments"><h3>📝 Commentaires du professeur</h3><div class="comments-list">';
            foreach (array_reverse($comments) as $comment) {
                $date_fmt = isset($comment['date']) ? date('d/m/Y à H:i', strtotime($comment['date'])) : '';
                $html .= '<div class="comment-entry"><div class="comment-date">📅 ' . esc_html($date_fmt) . '</div>';
                if (!empty($comment['text'])) {
                    $html .= '<div>' . nl2br(esc_html($comment['text'])) . '</div>';
                }
                if (!empty($comment['audio_id'])) {
                    $audio_url = self::embed_base64($comment['audio_id']);
                    if ($audio_url) {
                        $html .= '<div class="media"><audio controls style="max-width:100%;"><source src="' . esc_url($audio_url) . '"></audio></div>';
                    }
                }
                $html .= '</div>';
            }
            $html .= '</div></div>';
        }
        
        // Identity section
        $html .= '<div class="section"><h3 class="section-title">Identité de l\'élève</h3><div class="section-content">
            <div class="field"><label class="field-label">Nom / Prénom</label><div class="field-value">' . esc_html($values['nom_prenom'] ?: '—') . '</div></div>
            <div class="field"><label class="field-label">Date de saisie</label><div class="field-value">' . esc_html($values['date_de_saisie'] ?: '—') . '</div></div>
        </div></div>';
        
        // Context section
        $html .= '<div class="section"><h3 class="section-title">Contexte de réalisation</h3><div class="section-content">
            <div class="field"><label class="field-label">Lieu</label><div class="field-value">' . esc_html($lieu_labels[$values['lieu_']] ?? $values['lieu_'] ?: '—') . '</div></div>';
        if (!empty($values['enseigne_'])) {
            $html .= '<div class="field"><label class="field-label">Enseigne / Entreprise</label><div class="field-value">' . esc_html($values['enseigne_']) . '</div></div>';
        }
        if (!empty($values['lieu_specifique'])) {
            $html .= '<div class="field"><label class="field-label">Lieu spécifique</label><div class="field-value">' . esc_html($values['lieu_specifique']) . '</div></div>';
        }
        $html .= '</div></div>';
        
        // Domain section
        $html .= '<div class="section"><h3 class="section-title">Domaine / Compétences</h3><div class="section-content">';
        if (!empty($values['domaine'])) {
            $html .= '<div class="field"><label class="field-label">Domaine</label><div class="field-value">' . esc_html($values['domaine']) . '</div></div>';
        }
        if (!empty($values['competences'])) {
            $html .= '<div class="field"><label class="field-label">Compétences mobilisées</label><div class="field-value">' . nl2br(esc_html($values['competences'])) . '</div></div>';
        }
        $html .= '</div></div>';
        
        // Conditions section
        $aut = (int)($values['autonomie'] ?? 0);
        $html .= '<div class="section"><h3 class="section-title">Conditions et ressources</h3><div class="section-content">
            <div class="field"><label class="field-label">Autonomie (1-5)</label><div class="field-value stars">' . str_repeat('★', $aut) . str_repeat('☆', max(0, 5 - $aut)) . '</div></div>';
        if (!empty($values['materiels'])) {
            $html .= '<div class="field"><label class="field-label">Matériels / Logiciels</label><div class="field-value">' . nl2br(esc_html($values['materiels'])) . '</div></div>';
        }
        if (!empty($values['commanditaire'])) {
            $html .= '<div class="field"><label class="field-label">Commanditaire</label><div class="field-value">' . esc_html($values['commanditaire']) . '</div></div>';
        }
        if (!empty($values['contraintes'])) {
            $html .= '<div class="field"><label class="field-label">Contraintes</label><div class="field-value">' . esc_html($values['contraintes']) . '</div></div>';
        }
        if (!empty($values['consignes_recues'])) {
            $html .= '<div class="field"><label class="field-label">Consignes reçues</label><div class="field-value">' . nl2br(esc_html($values['consignes_recues'])) . '</div></div>';
        }
        $html .= '</div></div>';
        
        // Description section
        $html .= '<div class="section"><h3 class="section-title">Descriptif Détaillé</h3><div class="section-content">';
        if (!empty($values['avec_qui_'])) {
            $html .= '<div class="field"><label class="field-label">Avec qui ?</label><div class="field-value">' . esc_html($values['avec_qui_']) . '</div></div>';
        }
        if (!empty($values['deroulement'])) {
            $html .= '<div class="field"><label class="field-label">Déroulement</label><div class="field-value">' . nl2br(esc_html($values['deroulement'])) . '</div></div>';
        }
        if (!empty($values['resultats_'])) {
            $html .= '<div class="field"><label class="field-label">Résultats obtenus</label><div class="field-value">' . nl2br(esc_html($values['resultats_'])) . '</div></div>';
        }
        $html .= '</div></div>';
        
        // Feedback section
        $diff = (int)($values['difficulte'] ?? 0);
        $plaisir = (int)($values['plaisir_'] ?? 0);
        $html .= '<div class="section"><h3 class="section-title">Bilan Personnel</h3><div class="section-content">
            <div class="field"><label class="field-label">Difficulté rencontrée (1-5)</label><div class="field-value stars">' . str_repeat('★', $diff) . str_repeat('☆', max(0, 5 - $diff)) . '</div></div>
            <div class="field"><label class="field-label">Plaisir ressenti (1-5)</label><div class="field-value stars">' . str_repeat('★', $plaisir) . str_repeat('☆', max(0, 5 - $plaisir)) . '</div></div>';
        if (!empty($values['ameliorations'])) {
            $html .= '<div class="field"><label class="field-label">Améliorations possibles</label><div class="field-value">' . nl2br(esc_html($values['ameliorations'])) . '</div></div>';
        }
        $html .= '</div></div>';
        
        // Media section
        $has_media = $audio_id || $video_id || $fichier_id || !empty($photos);
        if ($has_media) {
            $html .= '<div class="section"><h3 class="section-title">Multimédia</h3><div class="section-content">';
            
            if ($audio_id) {
                $audio_url = self::embed_base64($audio_id);
                $html .= '<div class="field"><label class="field-label">Audio</label><div class="media"><audio controls><source src="' . esc_url($audio_url) . '"></audio></div></div>';
            }
            if ($video_id) {
                $video_url = 'VIDEO_FILE'; // Placeholder for ZIP export
                $html .= '<div class="field"><label class="field-label">Vidéo</label><div class="media"><video controls><source src="' . esc_url($video_url) . '"></video></div></div>';
            }
            if ($fichier_id) {
                $fichier_url = self::embed_base64($fichier_id);
                $html .= '<div class="field"><label class="field-label">Fichier</label><div class="media"><a href="' . esc_url($fichier_url) . '">📄 Télécharger le fichier</a></div></div>';
            }
            if (!empty($photos)) {
                $html .= '<div class="field"><label class="field-label">Photos</label><div class="photos">';
                foreach ($photos as $photo_id) {
                    $photo_url = wp_get_attachment_url($photo_id);
                    
                    if ($photo_url) {
                        $html .= '<img src="' . esc_url($photo_url) . '" alt="Photo">';
                    }
                }
                $html .= '</div></div>';
            }
            $html .= '</div></div>';
        }
        
        $html .= '<div class="footer">Document généré le ' . date('d/m/Y') . '</div>
    </div>
</body>
</html>';
        
        return $html;
    }
    
    private static function download_html($filename, $content) {
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));
        echo $content;
        exit;
    }
    
    private static function export_zip($filename, $html, $video_id, $photos, $audio_id) {
        $zip = new ZipArchive();
        $temp_dir = sys_get_temp_dir() . '/fdap-export-' . time();
        mkdir($temp_dir);
        
        $html_file = $temp_dir . '/' . $filename . '.html';
        file_put_contents($html_file, $html);
        
        $zip_file = $temp_dir . '.zip';
        
        if ($zip->open($zip_file, ZipArchive::CREATE) === TRUE) {
            $zip->addFile($html_file, $filename . '.html');
            
            // Add video
            if ($video_id) {
                $video_path = get_attached_file($video_id);
                if ($video_path && file_exists($video_path)) {
                    $zip->addFile($video_path, 'video.' . pathinfo($video_path, PATHINFO_EXTENSION));
                }
            }
            
            // Add audio
            if ($audio_id) {
                $audio_path = get_attached_file($audio_id);
                if ($audio_path && file_exists($audio_path)) {
                    $zip->addFile($audio_path, 'audio.' . pathinfo($audio_path, PATHINFO_EXTENSION));
                }
            }
            
            // Add photos
            foreach ($photos as $i => $photo_id) {
                $photo_path = get_attached_file($photo_id);
                if ($photo_path && file_exists($photo_path)) {
                    $zip->addFile($photo_path, 'photo_' . ($i + 1) . '.' . pathinfo($photo_path, PATHINFO_EXTENSION));
                }
            }
            
            $zip->close();
            
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $filename . '.zip"');
            header('Content-Length: ' . filesize($zip_file));
            readfile($zip_file);
            
            // Cleanup
            unlink($html_file);
            unlink($zip_file);
            rmdir($temp_dir);
            exit;
        }
        
        // Fallback to HTML if ZIP fails
        self::download_html($filename . '.html', $html);
    }
}

FDAP_Export::init();