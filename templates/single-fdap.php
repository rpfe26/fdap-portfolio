<?php
/**
 * Template: Affichage single FDAP
 */

defined('ABSPATH') || exit;

get_header();

while (have_posts()) : the_post();
    $id = get_the_ID();
    $fields = ['nom_prenom', 'date_de_saisie', 'lieu_', 'enseigne_', 'lieu_specifique',
               'domaine', 'competences', 'autonomie', 'materiels',
               'commanditaire', 'contraintes', 'consignes_recues', 'avec_qui_',
               'deroulement', 'resultats_', 'difficulte', 'plaisir_', 'ameliorations'];
    $values = [];
    foreach ($fields as $field) {
        $values[$field] = get_post_meta($id, '_fdap_' . $field, true);
    }
    
    $lieu_labels = [
        'lycee' => 'Au Lycée (Plateau technique)',
        'pfmp' => 'En Entreprise (PFMP)'
    ];
    
    $audio_id = get_post_meta($id, '_fdap_audio', true);
    $video_id = get_post_meta($id, '_fdap_video', true);
    $fichier_id = get_post_meta($id, '_fdap_fichier', true);
    $status = get_post_status($id);
    $fdap_comments = get_post_meta($id, '_fdap_comments', true);
    ?>    <article id="fdap-<?php the_ID(); ?>" class="fdap-main-wrapper fdap-single-container">
    <?php fdap_render_impersonation_banner(); ?>
    
    <div class="fdap-fiche-card">
        <div class="fdap-view">

        
        <?php if ($status === 'controlled'): ?>
        <!-- Bannière Contrôlée -->
        <div class="fdap-controlled-banner">
            <span class="fdap-banner-icon">✅</span>
            <div class="fdap-banner-text">
                <h3>Cette fiche a été contrôlée</h3>
                <p>Votre professeur a validé cette fiche. Consultez les commentaires ci-dessus.</p>
            </div>
        </div>
        <?php endif; ?>
        
        <?php
        // Affichage des commentaires en haut
        if (!empty($fdap_comments) && is_array($fdap_comments)):
            $fdap_comments = array_reverse($fdap_comments);
        ?>
        <div class="fdap-comments-teacher" style="margin-bottom: 30px; display: block !important; width: 100% !important;">
            <h3>📝 Commentaires Du Professeur</h3>

            <div class="fdap-comments-list" style="display: block !important; width: 100% !important;">
                <?php foreach ($fdap_comments as $comment): 
                    $date_fmt = isset($comment['date']) ? date('d/m/Y à H:i', strtotime($comment['date'])) : '';
                ?>
                <div class="fdap-comment-entry" style="background: #fff; border-left: 5px solid #f59e0b; padding: 15px; margin-bottom: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); display: block !important; width: 100% !important; clear: both !important; box-sizing: border-box !important;">
                    <div class="fdap-comment-date" style="font-size: 12px; color: #888; margin-bottom: 8px; font-weight: 600; display: block;">📅 <?php echo esc_html($date_fmt); ?></div>


                    <?php if (!empty($comment['text'])): ?>
                    <div class="fdap-comment-text" style="line-height: 1.5; color: #334155; font-size: 14px; word-wrap: break-word; display: block;"><?php echo nl2br(esc_html($comment['text'])); ?></div>
                    <?php endif; ?>

                    <?php if (!empty($comment['audio_id'])): 
                        $audio_url = wp_get_attachment_url($comment['audio_id']);
                        if ($audio_url):
                    ?>
                    <div class="fdap-comment-audio">
                        <audio controls style="max-width: 100%;"><source src="<?php echo esc_url($audio_url); ?>"></audio>
                    </div>
                    <?php endif; endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <header>
            <h1><?php the_title(); ?></h1>
            <p class="fdap-subtitle">(Fiche d'Activité Professionnelle)</p>
        </header>
        
        <!-- Identité de l'élève -->
        <section class="fdap-section">
            <h3 class="fdap-section-title"><span>👤</span> Identité de l'élève</h3>

            <div class="fdap-section-content">
                <div class="fdap-field">
                    <label class="fdap-field-label">Nom / Prénom <span class="required">*</span></label>
                    <div class="fdap-field-value"><?php echo esc_html($values['nom_prenom'] ?: '—'); ?></div>
                </div>
                <div class="fdap-field">
                    <label class="fdap-field-label">Date de saisie <span class="required">*</span></label>
                    <div class="fdap-field-value"><?php echo esc_html($values['date_de_saisie'] ?: '—'); ?></div>
                </div>
            </div>
        </section>
        
        <!-- Contexte de réalisation -->
        <section class="fdap-section">
            <h3 class="fdap-section-title"><span>📍</span> Contexte de réalisation</h3>

            <div class="fdap-section-content">
                <div class="fdap-field">
                    <label class="fdap-field-label">Lieu <span class="required">*</span></label>
                    <div class="fdap-field-value"><?php echo esc_html($lieu_labels[$values['lieu_']] ?? $values['lieu_'] ?: '—'); ?></div>
                </div>
                <?php if (!empty($values['enseigne_'])): ?>
                <div class="fdap-field">
                    <label class="fdap-field-label">Enseigne / Entreprise</label>
                    <div class="fdap-field-value"><?php echo esc_html($values['enseigne_']); ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($values['lieu_specifique'])): ?>
                <div class="fdap-field">
                    <label class="fdap-field-label">Lieu spécifique</label>
                    <div class="fdap-field-value"><?php echo esc_html($values['lieu_specifique']); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </section>
        
        <!-- Domaine / Compétences -->
        <section class="fdap-section">
            <h3 class="fdap-section-title"><span>🎯</span> Domaine / Compétences</h3>

            <div class="fdap-section-content">
                <?php if (!empty($values['domaine'])): ?>
                <div class="fdap-field">
                    <label class="fdap-field-label">Domaine</label>
                    <div class="fdap-field-value"><?php echo esc_html($values['domaine']); ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($values['competences'])): ?>
                <div class="fdap-field">
                    <label class="fdap-field-label">Compétences mobilisées</label>
                    <div class="fdap-field-value"><?php echo nl2br(esc_html($values['competences'])); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </section>
        
        <!-- Conditions et ressources -->
        <section class="fdap-section">
            <h3 class="fdap-section-title"><span>🛠️</span> Conditions et ressources</h3>

            <div class="fdap-section-content">
                <div class="fdap-field">
                    <label class="fdap-field-label">Autonomie (1-5)</label>
                    <div class="fdap-field-value fdap-stars">
                        <?php 
                        $aut = (int)($values['autonomie'] ?? 0);
                        echo str_repeat('★', $aut) . str_repeat('☆', max(0, 5 - $aut));
                        ?>
                    </div>
                </div>
                <?php if (!empty($values['materiels'])): ?>
                <div class="fdap-field">
                    <label class="fdap-field-label">Matériels / Logiciels</label>
                    <div class="fdap-field-value"><?php echo nl2br(esc_html($values['materiels'])); ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($values['commanditaire'])): ?>
                <div class="fdap-field">
                    <label class="fdap-field-label">Commanditaire</label>
                    <div class="fdap-field-value"><?php echo esc_html($values['commanditaire']); ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($values['contraintes'])): ?>
                <div class="fdap-field">
                    <label class="fdap-field-label">Contraintes</label>
                    <div class="fdap-field-value"><?php echo esc_html($values['contraintes']); ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($values['consignes_recues'])): ?>
                <div class="fdap-field">
                    <label class="fdap-field-label">Consignes reçues</label>
                    <div class="fdap-field-value"><?php echo nl2br(esc_html($values['consignes_recues'])); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </section>
        
        <!-- Descriptif Détaillé -->
        <section class="fdap-section">
            <h3 class="fdap-section-title"><span>📋</span> Descriptif Détaillé</h3>

            <div class="fdap-section-content">
                <?php if (!empty($values['avec_qui_'])): ?>
                <div class="fdap-field">
                    <label class="fdap-field-label">Avec qui ?</label>
                    <div class="fdap-field-value"><?php echo esc_html($values['avec_qui_']); ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($values['deroulement'])): ?>
                <div class="fdap-field">
                    <label class="fdap-field-label">Déroulement</label>
                    <div class="fdap-field-value"><?php echo nl2br(esc_html($values['deroulement'])); ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($values['resultats_'])): ?>
                <div class="fdap-field">
                    <label class="fdap-field-label">Résultats obtenus</label>
                    <div class="fdap-field-value"><?php echo nl2br(esc_html($values['resultats_'])); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </section>
        
        <!-- Bilan Personnel -->
        <section class="fdap-section">
            <h3 class="fdap-section-title"><span>📊</span> Bilan Personnel</h3>

            <div class="fdap-section-content">
                <div class="fdap-field">
                    <label class="fdap-field-label">Difficulté rencontrée (1-5)</label>
                    <div class="fdap-field-value fdap-stars">
                        <?php 
                        $diff = (int)($values['difficulte'] ?? 0);
                        echo str_repeat('★', $diff) . str_repeat('☆', max(0, 5 - $diff));
                        ?>
                    </div>
                </div>
                <div class="fdap-field">
                    <label class="fdap-field-label">Plaisir ressenti (1-5)</label>
                    <div class="fdap-field-value fdap-stars">
                        <?php 
                        $plaisir = (int)($values['plaisir_'] ?? 0);
                        echo str_repeat('★', $plaisir) . str_repeat('☆', max(0, 5 - $plaisir));
                        ?>
                    </div>
                </div>
                <?php if (!empty($values['ameliorations'])): ?>
                <div class="fdap-field">
                    <label class="fdap-field-label">Améliorations possibles</label>
                    <div class="fdap-field-value"><?php echo nl2br(esc_html($values['ameliorations'])); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </section>
        
        <!-- Multimédia -->
        <?php if ($audio_id || $video_id || $fichier_id): ?>
        <section class="fdap-section">
            <h3 class="fdap-section-title"><span>🎤</span> Multimédia / Explicitation</h3>

            <div class="fdap-section-content">
                <?php if ($audio_id): ?>
                <div class="fdap-field">
                    <label class="fdap-field-label">Audio</label>
                    <div class="fdap-media">
                        <audio controls style="max-width:100%;"><source src="<?php echo esc_url(wp_get_attachment_url($audio_id)); ?>"></audio>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($video_id): ?>
                <div class="fdap-field">
                    <label class="fdap-field-label">Vidéo</label>
                    <div class="fdap-media">
                        <video controls style="max-width:100%;"><source src="<?php echo esc_url(wp_get_attachment_url($video_id)); ?>"></video>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($fichier_id): ?>
                <div class="fdap-field">
                    <label class="fdap-field-label">Fichier</label>
                    <div class="fdap-media">
                        <a href="<?php echo esc_url(wp_get_attachment_url($fichier_id)); ?>" target="_blank" class="button">📄 Télécharger le fichier</a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- Vos Photos -->
        <?php
        $photos = [];
        for ($i = 1; $i <= 6; $i++) {
            $photo_id = get_post_meta($id, '_fdap_photo_' . $i, true);
            if ($photo_id) {
                $photos[] = $photo_id;
            }
        }
        if (!empty($photos)):
        ?>
        <section class="fdap-section">
            <h3 class="fdap-section-title"><span>📷</span> Vos Photos</h3>

            <div class="fdap-section-content">
                <div class="fdap-photos">
                    <?php foreach ($photos as $photo_id): ?>
                        <div><?php echo wp_get_attachment_image($photo_id, 'medium', false, ['style' => 'border-radius:8px;']); ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- Actions -->
        <div class="fdap-actions">
            <a href="<?php echo get_permalink(get_page_by_path('mes-fdap')); ?>" class="fdap-btn-back">← Retour</a>
            <?php if (current_user_can('edit_others_posts') || get_post_field('post_author', $id) == get_current_user_id()): ?>
            <a href="<?php echo add_query_arg('fdap_id', $id, get_permalink(get_page_by_path('fdap-2'))); ?>" class="fdap-btn-edit">✏️ Modifier</a>
            <?php endif; ?>
            <a href="?export=html" class="fdap-btn-export">📄 Exporter</a>
        </div>
        
        <footer class="fdap-footer">
            Document généré le <?php echo date('d/m/Y'); ?>
        </footer>
        </div> <!-- .fdap-view -->
    </article> <!-- .fdap-main-wrapper -->
<?php
endwhile;


get_footer();