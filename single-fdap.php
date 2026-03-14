<?php
/**
 * Template: Affichage single FDAP
 */

defined('ABSPATH') || exit;

get_header();

while (have_posts()) : the_post();
    $id = get_the_ID();
    $fields = ['nom_prenom', 'date_de_saisie', 'lieu_', 'enseigne_', 'lieu_specifique', 'bloc_de_competences', 'competences', 'autonomie', 'materiels', 'commanditaire', 'contraintes', 'consignes_recues', 'avec_qui_', 'descriptif', 'resultats_', 'difficulte', 'plaisir_', 'ameliorations'];
    $values = [];
    foreach ($fields as $field) {
        $values[$field] = get_post_meta($id, '_fdap_' . $field, true);
    }
    
    $lieu_labels = [
        'lycee' => 'Au Lycée (Plateau technique)',
        'pfmp' => 'En Entreprise (PFMP)'
    ];
    ?>
    <article id="fdap-<?php the_ID(); ?>" class="fdap-single">
        <header class="fdap-header">
            <h1><?php echo esc_html($values['nom_prenom'] ?: get_the_title()); ?></h1>
            <p class="fdap-date"><?php echo get_the_date('d/m/Y'); ?></p>
        </header>
        
        <div class="fdap-content">
            <section class="fdap-section">
                <h2>Informations générales</h2>
                <p><strong>Nom / Prénom :</strong> <?php echo esc_html($values['nom_prenom']); ?></p>
                <p><strong>Date de saisie :</strong> <?php echo esc_html($values['date_de_saisie']); ?></p>
                <p><strong>Lieu :</strong> <?php echo esc_html($lieu_labels[$values['lieu_']] ?? $values['lieu_']); ?></p>
                <?php if ($values['enseigne_']): ?>
                    <p><strong>Enseigne :</strong> <?php echo esc_html($values['enseigne_']); ?></p>
                <?php endif; ?>
                <?php if ($values['lieu_specifique']): ?>
                    <p><strong>Lieu spécifique :</strong> <?php echo esc_html($values['lieu_specifique']); ?></p>
                <?php endif; ?>
            </section>
            
            <section class="fdap-section">
                <h2>Compétences</h2>
                <?php if ($values['bloc_de_competences']): ?>
                    <p><strong>Bloc :</strong> <?php echo esc_html($values['bloc_de_competences']); ?></p>
                <?php endif; ?>
                <?php if ($values['competences']): ?>
                    <p><strong>Compétences :</strong><br><?php echo nl2br(esc_html($values['competences'])); ?></p>
                <?php endif; ?>
                <?php if ($values['autonomie']): ?>
                    <p><strong>Autonomie :</strong> <?php echo str_repeat('★', (int)$values['autonomie']) . str_repeat('☆', 5 - (int)$values['autonomie']); ?></p>
                <?php endif; ?>
            </section>
            
            <section class="fdap-section">
                <h2>Tâche réalisée</h2>
                <?php if ($values['materiels']): ?>
                    <p><strong>Matériels :</strong><br><?php echo nl2br(esc_html($values['materiels'])); ?></p>
                <?php endif; ?>
                <?php if ($values['commanditaire']): ?>
                    <p><strong>Commanditaire :</strong> <?php echo esc_html($values['commanditaire']); ?></p>
                <?php endif; ?>
                <?php if ($values['contraintes']): ?>
                    <p><strong>Contraintes :</strong> <?php echo esc_html($values['contraintes']); ?></p>
                <?php endif; ?>
                <?php if ($values['consignes_recues']): ?>
                    <p><strong>Consignes :</strong><br><?php echo nl2br(esc_html($values['consignes_recues'])); ?></p>
                <?php endif; ?>
                <?php if ($values['avec_qui_']): ?>
                    <p><strong>Avec qui :</strong> <?php echo esc_html($values['avec_qui_']); ?></p>
                <?php endif; ?>
            </section>
            
            <section class="fdap-section">
                <h2>Réalisation</h2>
                <?php if ($values['descriptif']): ?>
                    <p><strong>Descriptif :</strong><br><?php echo nl2br(esc_html($values['descriptif'])); ?></p>
                <?php endif; ?>
                <?php if ($values['resultats_']): ?>
                    <p><strong>Résultats :</strong><br><?php echo nl2br(esc_html($values['resultats_'])); ?></p>
                <?php endif; ?>
            </section>
            
            <section class="fdap-section">
                <h2>Bilan</h2>
                <?php if ($values['difficulte']): ?>
                    <p><strong>Difficulté :</strong> <?php echo str_repeat('★', (int)$values['difficulte']) . str_repeat('☆', 5 - (int)$values['difficulte']); ?></p>
                <?php endif; ?>
                <?php if ($values['plaisir_']): ?>
                    <p><strong>Plaisir :</strong> <?php echo str_repeat('★', (int)$values['plaisir_']) . str_repeat('☆', 5 - (int)$values['plaisir_']); ?></p>
                <?php endif; ?>
                <?php if ($values['ameliorations']): ?>
                    <p><strong>Améliorations :</strong><br><?php echo nl2br(esc_html($values['ameliorations'])); ?></p>
                <?php endif; ?>
            </section>
            
            <?php
            // Photos
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
                <h2>Photos</h2>
                <div class="fdap-photos">
                    <?php foreach ($photos as $photo_id): ?>
                        <?php echo wp_get_attachment_image($photo_id, 'medium'); ?>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
        </div>
        
        <footer class="fdap-footer">
            <p>
                <a href="<?php echo get_permalink(get_page_by_path('mes-fdap')); ?>" class="button">← Retour</a>
                <a href="<?php echo add_query_arg('fdap_id', $id, get_permalink(get_page_by_path('fdap-2'))); ?>" class="button">Modifier</a>
            </p>
        </footer>
    </article>
    <?php
endwhile;

get_footer();