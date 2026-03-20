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
    ?>
    <style>
        .fdap-view { max-width: 800px; margin: 20px auto; padding: 30px; background: #fff; border-radius: 8px; box-shadow: 0 2px 15px rgba(0,0,0,0.1); }
        .fdap-view h1 { text-align: center; color: #2271b1; margin-bottom: 5px; }
        .fdap-view .fdap-subtitle { text-align: center; color: #888; font-size: 12px; margin-bottom: 25px; font-style: italic; }
        .fdap-view .fdap-section { margin-bottom: 25px; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; }
        .fdap-view .fdap-section-title { background: #2271b1; color: #fff; padding: 12px 15px; margin: 0; font-size: 16px; }
        .fdap-view .fdap-section-content { padding: 15px; }
        .fdap-view .fdap-field { margin-bottom: 15px; }
        .fdap-view .fdap-field:last-child { margin-bottom: 0; }
        .fdap-view .fdap-field-label { font-weight: 600; color: #555; font-size: 13px; text-transform: uppercase; margin-bottom: 5px; display: block; }
        .fdap-view .fdap-field-value { background: #f8f9fa; padding: 10px; border-radius: 4px; border-left: 3px solid #2271b1; min-height: 20px; }
        .fdap-view .fdap-stars { font-size: 20px; letter-spacing: 2px; }
        .fdap-view .fdap-photos { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 10px; }
        .fdap-view .fdap-photos img { max-width: 100%; border-radius: 8px; }
        .fdap-view .fdap-media { margin-top: 10px; }
        .fdap-view .fdap-media audio, .fdap-view .fdap-media video { max-width: 100%; border-radius: 8px; }
        .fdap-view .fdap-footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #888; font-size: 12px; }
        .fdap-view .fdap-actions { text-align: center; margin-top: 20px; }
        .fdap-view .fdap-actions a { display: inline-block; padding: 10px 20px; background: #2271b1; color: #fff; text-decoration: none; border-radius: 4px; margin: 0 5px; }
        .fdap-view .fdap-actions a:hover { background: #135e96; }
        .fdap-view .fdap-actions a.fdap-btn-export { background: #00a32a; }
        .fdap-view .fdap-actions a.fdap-btn-export:hover { background: #008a20; }
        .fdap-view .required { color: #dc3232; }
        
        /* Bannière Contrôlée */
        .fdap-controlled-banner {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border: 2px solid #10b981;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            animation: pulse-banner 2s infinite;
        }
        @keyframes pulse-banner {
            0%, 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.3); }
            50% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
        }
        .fdap-banner-icon {
            font-size: 48px;
            flex-shrink: 0;
        }
        .fdap-banner-text h3 {
            margin: 0 0 8px 0;
            color: #065f46;
            font-size: 18px;
        }
        .fdap-banner-text p {
            margin: 0;
            color: #047857;
            font-size: 14px;
        }
        
        /* Commentaires professeur */
        .fdap-comments-teacher {
            background: linear-gradient(135deg, #fef3c7 0%, #fcd34d 100%);
            border: 2px solid #f59e0b;
            border-radius: 12px;
            margin-bottom: 25px;
            overflow: hidden;
        }
        .fdap-comments-teacher h3 {
            background: #f59e0b;
            color: #fff;
            padding: 12px 15px;
            margin: 0;
            font-size: 16px;
        }
        .fdap-comments-teacher .fdap-comments-list {
            padding: 15px;
        }
        .fdap-comment-entry {
            background: #fff;
            border-left: 4px solid #f59e0b;
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .fdap-comment-entry:last-child {
            margin-bottom: 0;
        }
        .fdap-comment-date {
            font-size: 12px;
            color: #666;
            margin-bottom: 8px;
        }
        .fdap-comment-text {
            margin-bottom: 10px;
            white-space: pre-wrap;
            line-height: 1.6;
        }
        
        @media (max-width: 600px) {
            .fdap-view { padding: 15px; margin: 10px; }
            .fdap-view .fdap-photos { grid-template-columns: repeat(2, 1fr); }
            .fdap-controlled-banner { flex-direction: column; text-align: center; }
        }
    </style>
    
    <article id="fdap-<?php the_ID(); ?>" class="fdap-view">
        
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
        <div class="fdap-comments-teacher">
            <h3>📝 Commentaires du professeur</h3>
            <div class="fdap-comments-list">
                <?php foreach ($fdap_comments as $comment): 
                    $date_fmt = isset($comment['date']) ? date('d/m/Y à H:i', strtotime($comment['date'])) : '';
                ?>
                <div class="fdap-comment-entry">
                    <div class="fdap-comment-date">📅 <?php echo esc_html($date_fmt); ?></div>
                    <?php if (!empty($comment['text'])): ?>
                    <div class="fdap-comment-text"><?php echo esc_html($comment['text']); ?></div>
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
            <h3 class="fdap-section-title">Identité de l'élève</h3>
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
            <h3 class="fdap-section-title">Contexte de réalisation</h3>
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
            <h3 class="fdap-section-title">Domaine / Compétences</h3>
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
            <h3 class="fdap-section-title">Conditions et ressources</h3>
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
            <h3 class="fdap-section-title">Descriptif Détaillé</h3>
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
            <h3 class="fdap-section-title">Bilan Personnel</h3>
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
            <h3 class="fdap-section-title">Multimédia / Entretien d'explicitation ou un Document</h3>
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
            <h3 class="fdap-section-title">Vos Photos</h3>
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
            <a href="<?php echo get_permalink(get_page_by_path('mes-fdap')); ?>">← Retour</a>
            <?php if (current_user_can('edit_others_posts') || get_post_field('post_author', $id) == get_current_user_id()): ?>
            <a href="<?php echo add_query_arg('fdap_id', $id, get_permalink(get_page_by_path('fdap-2'))); ?>">Modifier</a>
            <?php endif; ?>
            <a href="?export=html" class="fdap-btn-export">📄 Exporter</a>
        </div>
        
        <footer class="fdap-footer">
            Document généré le <?php echo date('d/m/Y'); ?>
        </footer>
    </article>
    <?php
endwhile;

get_footer();