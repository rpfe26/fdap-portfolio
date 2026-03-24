<?php
/**
 * Form fields template - Design Moderne et Responsive (sans accordéon)
 */
defined('ABSPATH') || exit;

$is_edit = $post_id > 0;

// Récupérer les valeurs des fichiers multimédia
$audio_id = $is_edit ? get_post_meta($post_id, '_fdap_audio', true) : 0;
$video_id = $is_edit ? get_post_meta($post_id, '_fdap_video', true) : 0;
$fichier_id = $is_edit ? get_post_meta($post_id, '_fdap_fichier', true) : 0;
?>
<div class="fdap-form-container">
    <form method="post" enctype="multipart/form-data" class="fdap-form">
        <?php wp_nonce_field('fdap_form_submit', 'fdap_nonce'); ?>
        <input type="hidden" name="fdap_action" value="<?php echo $is_edit ? 'update' : 'create'; ?>">
        <?php if ($is_edit): ?>
            <input type="hidden" name="fdap_id" value="<?php echo $post_id; ?>">
        <?php endif; ?>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'saved'): ?>
            <div class="fdap-success">
                Fiche enregistrée avec succès ! ✅
            </div>
        <?php endif; ?>
        
        <!-- Titre de l'activité (React Style) -->
        <div class="fdap-title-card">
            <label>Titre de l'activité <span class="required">*</span></label>
            <input type="text" name="post_title" value="<?php echo esc_attr($is_edit ? get_the_title($post_id) : ''); ?>" required placeholder="Ex: Mise en rayon des surgelés">
        </div>
        
        <!-- Section Identité -->
        <div class="fdap-section">
            <h3><span>👤</span> Identité</h3>

            <div class="fdap-section-body">
                <div class="fdap-grid-2">
                    <div class="fdap-field">
                        <label>Identité de l'élève (Nom Prénom) <span class="required">*</span></label>
                        <input type="text" name="fdap_nom_prenom" value="<?php echo esc_attr($values['nom_prenom'] ?? ''); ?>" required placeholder="Saisir votre nom">
                    </div>
                    <div class="fdap-field">
                        <label>Date de saisie <span class="required">*</span></label>
                        <input type="date" name="fdap_date_de_saisie" value="<?php echo esc_attr($values['date_de_saisie'] ?? ''); ?>" required>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Section Contexte -->
        <div class="fdap-section">
            <h3><span>📍</span> Contexte de réalisation</h3>

            <div class="fdap-section-body">
                <div class="fdap-field">
                    <label>Lieu de réalisation <span class="required">*</span></label>
                    <div class="fdap-radio-row">
                        <label class="fdap-radio-inline">
                            <input type="radio" name="fdap_lieu_" value="lycee" <?php checked($values['lieu_'] ?? '', 'lycee'); ?>>
                            Au lycée
                        </label>
                        <label class="fdap-radio-inline">
                            <input type="radio" name="fdap_lieu_" value="pfmp" <?php checked($values['lieu_'] ?? '', 'pfmp'); ?>>
                            En PFMP
                        </label>
                    </div>
                </div>
                <div class="fdap-grid-2">
                    <div class="fdap-field">
                        <label>Enseigne / Entreprise / Service</label>
                        <input type="text" name="fdap_enseigne_" value="<?php echo esc_attr($values['enseigne_'] ?? ''); ?>" placeholder="Ex: Carrefour, Mairie...">
                    </div>
                    <div class="fdap-field">
                        <label>Lieu spécifique / Rayon</label>
                        <input type="text" name="fdap_lieu_specifique" value="<?php echo esc_attr($values['lieu_specifique'] ?? ''); ?>" placeholder="Ex: Rayon frais, Accueil...">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Domaine / Compétences EPC -->
        <div class="fdap-section fdap-epc-section">
            <h3><span>🎯</span> Référentiel CAP EPC</h3>

            <div class="fdap-section-body">
                <!-- Champs cachés pour compatibilité -->
                <input type="hidden" name="fdap_domaine" id="fdap_domaine" value="<?php echo esc_attr($values['domaine'] ?? ''); ?>">
                <textarea name="fdap_competences" id="fdap_competences" style="display:none;"><?php echo esc_textarea($values['competences'] ?? ''); ?></textarea>
                
                <!-- Conteneur pour le sélecteur dynamique JS -->
                <div id="fdap-competence-selector" class="fdap-dynamic-selector">
                    <p class="fdap-loading">Chargement du référentiel...</p>
                </div>
            </div>
        </div>
        
        <!-- Section Conditions -->
        <div class="fdap-section">
            <h3><span>🛠️</span> Conditions et ressources</h3>

            <div class="fdap-section-body">
                <div class="fdap-grid-2">
                    <div class="fdap-field">
                        <label>Degré d'autonomie</label>
                        <select name="fdap_autonomie" class="fdap-select">
                            <option value="">-- Sélectionner --</option>
                            <option value="1" <?php selected($values['autonomie'] ?? '', 1); ?>>★ Assisté</option>
                            <option value="2" <?php selected($values['autonomie'] ?? '', 2); ?>>★★ Guidé</option>
                            <option value="3" <?php selected($values['autonomie'] ?? '', 3); ?>>★★★ Aide ponctuelle</option>
                            <option value="4" <?php selected($values['autonomie'] ?? '', 4); ?>>★★★★ En autonomie</option>
                            <option value="5" <?php selected($values['autonomie'] ?? '', 5); ?>>★★★★★ Initiative</option>
                        </select>
                    </div>
                    <div class="fdap-field">
                        <label>Commanditaire / Client</label>
                        <input type="text" name="fdap_commanditaire" value="<?php echo esc_attr($values['commanditaire'] ?? ''); ?>" placeholder="Personne demandeuse">
                    </div>
                </div>
                <div class="fdap-field">
                    <label>Matériels / Logiciels utilisés</label>
                    <textarea name="fdap_materiels" rows="2" placeholder="Outils, logiciels utilisés..."><?php echo esc_textarea($values['materiels'] ?? ''); ?></textarea>
                </div>
                <div class="fdap-grid-2">
                    <div class="fdap-field">
                        <label>Contraintes financières / temps</label>
                        <input type="text" name="fdap_contraintes" value="<?php echo esc_attr($values['contraintes'] ?? ''); ?>" placeholder="Délais, ressources...">
                    </div>
                    <div class="fdap-field">
                        <label>Consignes recommandées</label>
                        <textarea name="fdap_consignes_recues" rows="1" placeholder="Instructions données..."><?php echo esc_textarea($values['consignes_recues'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Section Descriptif -->
        <div class="fdap-section">
            <h3><span>📋</span> Descriptif Détaillé</h3>

            <div class="fdap-section-body">
                <div class="fdap-field">
                    <label>Avec qui ? (Équipe, tuteur, seul...)</label>
                    <input type="text" name="fdap_avec_qui_" value="<?php echo esc_attr($values['avec_qui_'] ?? ''); ?>" placeholder="Ex: Seul, avec mon tuteur...">
                </div>
                <div class="fdap-field">
                    <label>Déroulement de l'activité</label>
                    <textarea name="fdap_deroulement" rows="3" placeholder="Décrivez les étapes réalisées..."><?php echo esc_textarea($values['deroulement'] ?? ''); ?></textarea>

                </div>
                <div class="fdap-field">
                    <label>Résultats obtenus</label>
                    <textarea name="fdap_resultats_" rows="2" placeholder="Ce qui a été produit ou réalisé..."><?php echo esc_textarea($values['resultats_'] ?? ''); ?></textarea>

                </div>
            </div>
        </div>
        
        <!-- Section Bilan -->
        <div class="fdap-section">
            <h3><span>📊</span> Bilan Personnel</h3>


            <div class="fdap-section-body">
                <div class="fdap-grid-2">
                    <div class="fdap-field">
                        <label>Difficulté rencontrée</label>
                        <select name="fdap_difficulte" class="fdap-select">
                            <option value="">-- Sélectionner --</option>
                            <option value="1" <?php selected($values['difficulte'] ?? '', 1); ?>>★ Très facile</option>
                            <option value="2" <?php selected($values['difficulte'] ?? '', 2); ?>>★★ Facile</option>
                            <option value="3" <?php selected($values['difficulte'] ?? '', 3); ?>>★★★ Moyen</option>
                            <option value="4" <?php selected($values['difficulte'] ?? '', 4); ?>>★★★★ Difficile</option>
                            <option value="5" <?php selected($values['difficulte'] ?? '', 5); ?>>★★★★★ Très difficile</option>
                        </select>
                    </div>
                    <div class="fdap-field">
                        <label>Plaisir ressenti</label>
                        <select name="fdap_plaisir_" class="fdap-select">
                            <option value="">-- Sélectionner --</option>
                            <option value="1" <?php selected($values['plaisir_'] ?? '', 1); ?>>★ Pas du tout</option>
                            <option value="2" <?php selected($values['plaisir_'] ?? '', 2); ?>>★★ Un peu</option>
                            <option value="3" <?php selected($values['plaisir_'] ?? '', 3); ?>>★★★ Moyen</option>
                            <option value="4" <?php selected($values['plaisir_'] ?? '', 4); ?>>★★★★ Beaucoup</option>
                            <option value="5" <?php selected($values['plaisir_'] ?? '', 5); ?>>★★★★★ Passionnément</option>
                        </select>
                    </div>
                </div>
                <div class="fdap-field">
                    <label>Points forts / Axes d'amélioration</label>
                    <textarea name="fdap_ameliorations" rows="2" placeholder="Ce que vous feriez différemment ou vos réussites..."><?php echo esc_textarea($values['ameliorations'] ?? ''); ?></textarea>

                </div>
            </div>
        </div>
        
        <!-- Section Multimédia -->
        <div class="fdap-section">
            <h3><span>🎤</span> Médias / Explicitation</h3>


            <div class="fdap-section-body">
                <div class="fdap-grid-3">
                    <!-- Audio avec enregistrement intégré -->
                    <div class="fdap-media-item" id="fdap-audio-item">
                        <label>Audio</label>
                        <?php if ($audio_id): 
                            $audio_url = wp_get_attachment_url($audio_id);
                            $audio_name = basename($audio_url);
                        ?>
                            <div class="fdap-file-preview">
                                <div class="fdap-file-icon">🎵</div>
                                <div class="fdap-file-info">
                                    <span class="fdap-file-name" title="<?php echo esc_attr($audio_name); ?>"><?php echo esc_html(mb_strimwidth($audio_name, 0, 25, '...')); ?></span>
                                    <div class="fdap-file-actions">
                                        <a href="<?php echo esc_url($audio_url); ?>" target="_blank" class="fdap-file-btn fdap-file-btn-open">Ouvrir</a>
                                        <button type="button" class="fdap-file-btn fdap-file-btn-remove" onclick="removeFile(this, 'fdap_keep_audio');">Supprimer</button>
                                    </div>
                                </div>
                                <input type="hidden" name="fdap_keep_audio" value="<?php echo $audio_id; ?>">
                            </div>
                        <?php else: ?>
                            <!-- Interface audio simplifiée comme commentaires prof -->
                            <div class="fdap-audio-box" id="fdap-audio-box">
                                <label class="fdap-upload-box fdap-upload-box-audio" style="padding: 12px 8px; min-height: auto;">
                                    <input type="file" name="fdap_audio" accept="audio/*" class="fdap-hidden-input" id="fdap-audio-upload">
                                    <span class="fdap-upload-icon" style="font-size: 1.2rem;">📁</span>
                                    <span class="fdap-upload-text" style="font-size: 12px;">Fichier audio</span>
                                    <span class="fdap-upload-hint" style="font-size: 11px;">MP3, WAV, OGG</span>
                                </label>
                                <div style="display: flex; gap: 6px; margin-top: 8px; flex-wrap: wrap; align-items: center;">
                                    <button type="button" class="fdap-record-btn" id="fdap-student-record-btn" style="padding: 6px 14px; font-size: 13px; display: inline-flex; align-items: center; gap: 6px;">🎤 Enregistrer</button>
                                    <button type="button" id="fdap-student-pause-btn" style="display:none; background: #f59e0b; color: #fff; border: none; padding: 6px 12px; border-radius: 8px; cursor: pointer; font-size: 12px; font-weight: 600; align-items: center;">⏸ Pause</button>
                                    <button type="button" class="fdap-stop-btn" id="fdap-student-stop-btn" style="display:none; padding: 6px 12px; font-size: 12px; align-items: center;">⏹ Stop</button>
                                    <span id="fdap-student-recording-indicator" style="display:none; align-items: center; gap: 6px; font-size: 12px; color: #ef4444; font-weight: 600;">
                                        <span style="display:inline-block; width:10px; height:10px; background:#ef4444; border-radius:50%; animation: fdap-blink 1s infinite;"></span>
                                        <span id="fdap-student-timer">00:00</span>
                                    </span>
                                </div>
                            </div>
                            <div class="fdap-preview-row" id="fdap-student-preview" style="display: none; margin-top: 8px;">
                                <audio id="fdap-student-audio" controls style="width: 100%; height: 32px;"></audio>
                                <button type="button" class="fdap-clear-btn" id="fdap-student-clear-btn" style="font-size: 12px; padding: 4px 8px;">🗑</button>
                            </div>
                            <input type="hidden" name="fdap_student_audio_data" id="fdap-student-audio-data">
                        <?php endif; ?>
                    </div>
                    
                    <!-- Vidéo -->
                    <div class="fdap-media-item">
                        <label>Vidéo</label>
                        <?php if ($video_id): 
                            $video_url = wp_get_attachment_url($video_id);
                            $video_name = basename($video_url);
                        ?>
                            <div class="fdap-file-preview">
                                <div class="fdap-file-icon">🎬</div>
                                <div class="fdap-file-info">
                                    <span class="fdap-file-name" title="<?php echo esc_attr($video_name); ?>"><?php echo esc_html(mb_strimwidth($video_name, 0, 25, '...')); ?></span>
                                    <div class="fdap-file-actions">
                                        <a href="<?php echo esc_url($video_url); ?>" target="_blank" class="fdap-file-btn fdap-file-btn-open">Ouvrir</a>
                                        <button type="button" class="fdap-file-btn fdap-file-btn-remove" onclick="removeFile(this, 'fdap_keep_video');">Supprimer</button>
                                    </div>
                                </div>
                                <input type="hidden" name="fdap_keep_video" value="<?php echo $video_id; ?>">
                            </div>
                        <?php else: ?>
                            <label class="fdap-upload-box">
                                <input type="file" name="fdap_video" accept="video/*" class="fdap-hidden-input">
                                <span class="fdap-upload-icon">📹</span>
                                <span class="fdap-upload-text">Vidéo</span>
                                <span class="fdap-upload-hint">MP4, WebM</span>
                            </label>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Document -->
                    <div class="fdap-media-item">
                        <label>Document</label>
                        <?php if ($fichier_id): 
                            $fichier_url = wp_get_attachment_url($fichier_id);
                            $fichier_name = basename($fichier_url);
                            $ext = strtolower(pathinfo($fichier_name, PATHINFO_EXTENSION));
                            $icon = in_array($ext, ['pdf']) ? '📄' : (in_array($ext, ['doc', 'docx']) ? '📝' : '📁');
                        ?>
                            <div class="fdap-file-preview">
                                <div class="fdap-file-icon"><?php echo $icon; ?></div>
                                <div class="fdap-file-info">
                                    <span class="fdap-file-name" title="<?php echo esc_attr($fichier_name); ?>"><?php echo esc_html(mb_strimwidth($fichier_name, 0, 25, '...')); ?></span>
                                    <div class="fdap-file-actions">
                                        <a href="<?php echo esc_url($fichier_url); ?>" target="_blank" class="fdap-file-btn fdap-file-btn-open">Ouvrir</a>
                                        <button type="button" class="fdap-file-btn fdap-file-btn-remove" onclick="removeFile(this, 'fdap_keep_fichier');">Supprimer</button>
                                    </div>
                                </div>
                                <input type="hidden" name="fdap_keep_fichier" value="<?php echo $fichier_id; ?>">
                            </div>
                        <?php else: ?>
                            <label class="fdap-upload-box">
                                <input type="file" name="fdap_fichier" accept=".pdf,.doc,.docx,.xls,.xlsx" class="fdap-hidden-input">
                                <span class="fdap-upload-icon">📄</span>
                                <span class="fdap-upload-text">Document</span>
                                <span class="fdap-upload-hint">PDF, Word</span>
                            </label>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Photos -->
        <div class="fdap-section">
            <h3><span>📷</span> Photos</h3>

            <div class="fdap-section-body">
                <div class="fdap-photos-grid">
                    <?php for ($i = 1; $i <= 6; $i++): 
                        $photo_id = $is_edit ? get_post_meta($post_id, '_fdap_photo_' . $i, true) : 0;
                        $photo_url = $photo_id ? wp_get_attachment_url($photo_id) : '';
                    ?>
                    <div class="fdap-photo-item" tabindex="0">
                        <label style="font-weight: 700; color: var(--fdap-primary);">Photo <?php echo $i; ?></label>
                        <?php if ($photo_url): ?>
                            <div class="fdap-photo-preview">
                                <img src="<?php echo esc_url($photo_url); ?>" alt="Photo <?php echo $i; ?>">
                                <button type="button" class="fdap-photo-badge" onclick="removePhoto(this, <?php echo $i; ?>);">×</button>
                                <div class="fdap-photo-overlay">
                                    <a href="<?php echo esc_url($photo_url); ?>" target="_blank" class="fdap-photo-btn">🔍</a>
                                </div>
                                <input type="hidden" name="fdap_keep_photo_<?php echo $i; ?>" value="<?php echo esc_attr($photo_id); ?>" class="fdap-keep-photo">
                            </div>
                        <?php else: ?>
                            <label class="fdap-upload-box fdap-upload-box-photo">
                                <input type="file" name="fdap_photo_<?php echo $i; ?>" accept="image/*" class="fdap-hidden-input" data-preview="photo-preview-<?php echo $i; ?>">
                                <span class="fdap-upload-icon">📷</span>
                                <span class="fdap-upload-text">Photo <?php echo $i; ?></span>
                            </label>
                            <div class="fdap-photo-preview fdap-photo-empty" id="photo-preview-<?php echo $i; ?>" style="display:none;">
                                <!-- Le contenu (img + badge X) sera injecté par JS -->
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
        
        <!-- Commentaires du professeur (Admin uniquement) -->
        <?php if (current_user_can('edit_others_posts')): ?>
        <?php
        global $post;
        $post_id = isset($_GET['fdap_id']) ? (int) $_GET['fdap_id'] : 0;
        $existing_comments = $post_id ? get_post_meta($post_id, '_fdap_comments', true) : [];
        if (!is_array($existing_comments)) $existing_comments = [];
        ?>
        <div class="fdap-section fdap-teacher-section" id="fdap-comments-section">
            <h3><span>📝</span> Commentaires Du Professeur</h3>


            <div class="fdap-section-body" style="padding: 20px; background: #fff;">
                <?php if (!empty($existing_comments)): ?>
                <div class="fdap-comments-history" style="margin-bottom: 25px; display: block !important; width: 100% !important;">
                    <h4 style="font-size: 15px; color: #666; margin-bottom: 12px; display: block; border-bottom: 1px solid #eee; padding-bottom: 8px;">Historique (<?php echo count($existing_comments); ?>) :</h4>
                    <?php $c_idx = 0; foreach (array_reverse($existing_comments) as $comment): $orig_idx = count($existing_comments) - 1 - $c_idx; ?>
                    <div class="fdap-comment-entry" style="background: #fff; border-left: 5px solid #f59e0b; padding: 15px; margin-bottom: 15px; border-radius: 8px; position: relative; box-shadow: 0 2px 4px rgba(0,0,0,0.05); display: block !important; width: 100% !important; clear: both !important; box-sizing: border-box !important;">
                        <button type="submit" name="fdap_delete_comment" value="<?php echo $orig_idx; ?>" style="position: absolute; top: 10px; right: 10px; background: #fee2e2; color: #dc2626; border: none; width: 26px; height: 26px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 16px; transition: all 0.2s;" title="Supprimer ce commentaire" onmouseover="this.style.background='#fecaca'" onmouseout="this.style.background='#fee2e2'">×</button>
                        <div style="font-size: 12px; color: #888; margin-bottom: 8px; font-weight: 600; display: block;">📅 <?php echo date('d/m/Y à H:i', strtotime($comment['date'])); ?></div>
                        <?php if (!empty($comment['text'])): ?>
                            <div style="margin-bottom: 10px; line-height: 1.5; color: #334155; font-size: 14px; word-wrap: break-word; display: block;"><?php echo nl2br(esc_html($comment['text'])); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($comment['audio_id'])): 
                            $audio_url = wp_get_attachment_url($comment['audio_id']); 
                        ?>
                            <div style="display: block; margin-top: 5px;"><audio controls style="max-width: 100%; height: 35px;"><source src="<?php echo esc_url($audio_url); ?>"></audio></div>
                        <?php endif; ?>
                    </div>
                    <?php $c_idx++; endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="fdap-new-comment" style="background: #fffbeb; padding: 15px; border-radius: 8px; border: 1px dashed #f59e0b;">
                    <h4 style="font-size: 14px; color: #92400e; margin: 0 0 10px 0;">Ajouter un commentaire :</h4>
                    <div class="fdap-field" style="margin-bottom: 15px;">
                        <label style="font-weight: 600; color: #333; display: block; margin-bottom: 5px;">Commentaire texte</label>
                        <textarea name="fdap_comment_text" rows="3" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;" placeholder="Commentaire..."></textarea>
                    </div>
                    <div class="fdap-field" style="margin-bottom: 15px;">
                        <label style="font-weight: 600; color: #333; display: block; margin-bottom: 5px;">Commentaire audio</label>
                        <div style="display: flex; gap: 6px; margin-top: 8px; flex-wrap: wrap; align-items: center;">
                            <button type="button" class="fdap-record-btn" id="fdap-teacher-record-btn" style="padding: 6px 14px; font-size: 13px; display: inline-flex; align-items: center; gap: 6px;">🎤 Enregistrer</button>
                            <button type="button" id="fdap-teacher-pause-btn" style="display:none; background: #f59e0b; color: #fff; border: none; padding: 6px 12px; border-radius: 8px; cursor: pointer; font-size: 12px; font-weight: 600; align-items: center;">⏸ Pause</button>
                            <button type="button" class="fdap-stop-btn" id="fdap-teacher-stop-btn" style="display:none; padding: 6px 12px; font-size: 12px; align-items: center;">⏹ Stop</button>
                            <span id="fdap-teacher-recording-indicator" style="display:none; align-items: center; gap: 6px; font-size: 12px; color: #ef4444; font-weight: 600;">
                                <span style="display:inline-block; width:10px; height:10px; background:#ef4444; border-radius:50%; animation: fdap-blink 1s infinite;"></span>
                                <span id="fdap-teacher-timer">00:00</span>
                            </span>
                        </div>
                        <div class="fdap-preview-row" id="fdap-teacher-preview" style="display: none; margin-top: 10px; background: transparent; padding: 0;">
                            <audio id="fdap-teacher-audio" controls style="flex: 1; max-width: 200px; height: 36px;"></audio>
                            <button type="button" class="fdap-clear-btn" id="fdap-teacher-clear-btn" style="font-size: 12px; padding: 4px 8px;">🗑</button>
                        </div>
                        <input type="hidden" name="fdap_comment_audio_data" id="fdap-teacher-audio-data">
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php 
            $current_url = $is_edit ? add_query_arg('fdap_id', $post_id, get_permalink()) : get_permalink();
            // Switching to api.qrserver.com as Google Charts is deprecated and often blocked/unstable
            $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=110x110&data=" . urlencode($current_url);
        ?>

        <!-- QR Code Smartphone Bridge (React Style) -->
        <div class="fdap-section fdap-qr-bridge">

            <div class="fdap-section-body" style="display: flex; align-items: center; gap: 24px;">
                <div style="flex: 1;">
                    <h3><span>📱</span> Poursuivre sur smartphone</h3>

                    <p style="margin: 0; color: #64748b; font-size: 0.9rem; line-height: 1.5;">
                        Scannez ce QR Code pour ouvrir cette fiche sur votre téléphone. Très utile pour <b>prendre des photos en direct</b> !
                    </p>
                </div>
                <div style="background: white; padding: 12px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <img src="<?php echo $qr_url; ?>" alt="QR Code" style="display: block; width: 110px; height: 110px;">
                </div>
            </div>
        </div>

        <div class="fdap-submit-wrap">
            <button type="submit" class="fdap-submit-btn">
                <?php echo $is_edit ? '💾 Mettre à jour la fiche' : '💾 Enregistrer la fiche'; ?>
            </button>
        </div>
    </form>
</div>