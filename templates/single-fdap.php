<?php
/**
 * Template: Affichage single FDAP - Structure identique au formulaire
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
        @media (max-width: 600px) {
            .fdap-view { padding: 15px; margin: 10px; }
            .fdap-view .fdap-photos { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
    
    <article id="fdap-<?php the_ID(); ?>" class="fdap-view">
        <header>
            <h1><?php the_title(); ?></h1>
            <p class="fdap-subtitle">(Fiche d'Activité Professionnelle)</p>
        </header>
        
        <!-- Titre -->
        <section class="fdap-section">
            <h3 class="fdap-section-title">Titre de la fiche</h3>
            <div class="fdap-section-content">
                <div class="fdap-field">
                    <label class="fdap-field-label">Titre</label>
                    <div class="fdap-field-value" style="font-size:18px;font-weight:bold;"><?php the_title(); ?></div>
                </div>
            </div>
        </section>
        
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
            <a href="<?php echo add_query_arg('fdap_id', $id, get_permalink(get_page_by_path('fdap-2'))); ?>">Modifier</a>
            <a href="#" onclick="window.print();" class="fdap-btn-export">📄 Imprimer</a>
        </div>
        
        <footer class="fdap-footer">
            Document généré le <?php echo date('d/m/Y'); ?>
        </footer>
    </article>
    <?php
endwhile;

get_footer();