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
            <div class="fdap-success">✓ Fiche enregistrée avec succès !</div>
        <?php endif; ?>
        
        <!-- Titre simple en haut -->
        <div class="fdap-title-field">
            <label>Titre de la fiche <span class="required">*</span></label>
            <input type="text" name="post_title" value="<?php echo esc_attr($is_edit ? get_the_title($post_id) : ''); ?>" required placeholder="Ex: Montage d'une installation électrique">
        </div>
        
        <!-- Identité de l'élève -->
        <div class="fdap-section">
            <h3>👤 Identité de l'élève</h3>
            <div class="fdap-section-body">
                <div class="fdap-field">
                    <label>Nom / Prénom <span class="required">*</span></label>
                    <input type="text" name="fdap_nom_prenom" value="<?php echo esc_attr($values['nom_prenom'] ?? ''); ?>" required>
                </div>
                <div class="fdap-field">
                    <label>Date de saisie <span class="required">*</span></label>
                    <input type="date" name="fdap_date_de_saisie" value="<?php echo esc_attr($values['date_de_saisie'] ?? ''); ?>" required>
                </div>
            </div>
        </div>
        
        <!-- Contexte de réalisation -->
        <div class="fdap-section">
            <h3>📍 Contexte de réalisation</h3>
            <div class="fdap-section-body">
                <div class="fdap-field">
                    <label>Lieu <span class="required">*</span></label>
                    <div class="fdap-radio-group">
                        <label class="fdap-radio-label">
                            <input type="radio" name="fdap_lieu_" value="lycee" <?php checked($values['lieu_'] ?? '', 'lycee'); ?>>
                            <span class="fdap-radio-text">Au Lycée (Plateau technique)</span>
                        </label>
                        <label class="fdap-radio-label">
                            <input type="radio" name="fdap_lieu_" value="pfmp" <?php checked($values['lieu_'] ?? '', 'pfmp'); ?>>
                            <span class="fdap-radio-text">En Entreprise (PFMP)</span>
                        </label>
                    </div>
                </div>
                <div class="fdap-field">
                    <label>Enseigne / Entreprise</label>
                    <input type="text" name="fdap_enseigne_" value="<?php echo esc_attr($values['enseigne_'] ?? ''); ?>" placeholder="Nom de l'entreprise">
                </div>
                <div class="fdap-field">
                    <label>Lieu spécifique</label>
                    <input type="text" name="fdap_lieu_specifique" value="<?php echo esc_attr($values['lieu_specifique'] ?? ''); ?>" placeholder="Atelier, salle, etc.">
                </div>
            </div>
        </div>
        
        <!-- Domaine / Compétences -->
        <div class="fdap-section">
            <h3>🎯 Domaine / Compétences</h3>
            <div class="fdap-section-body">
                <div class="fdap-field">
                    <label>Domaine</label>
                    <input type="text" name="fdap_domaine" value="<?php echo esc_attr($values['domaine'] ?? ''); ?>" placeholder="Ex: Électrotechnique">
                </div>
                <div class="fdap-field">
                    <label>Compétences mobilisées</label>
                    <textarea name="fdap_competences" rows="3" placeholder="Listez les compétences..."><?php echo esc_textarea($values['competences'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>
        
        <!-- Conditions et ressources -->
        <div class="fdap-section">
            <h3>⚙️ Conditions et ressources</h3>
            <div class="fdap-section-body">
                <div class="fdap-field">
                    <label>Autonomie</label>
                    <select name="fdap_autonomie" class="fdap-select">
                        <option value="">-- Sélectionner --</option>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php selected($values['autonomie'] ?? '', $i); ?>><?php echo str_repeat('★', $i) . str_repeat('☆', 5-$i); ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="fdap-field">
                    <label>Matériels / Logiciels</label>
                    <textarea name="fdap_materiels" rows="2" placeholder="Outils, logiciels utilisés..."><?php echo esc_textarea($values['materiels'] ?? ''); ?></textarea>
                </div>
                <div class="fdap-field">
                    <label>Commanditaire</label>
                    <input type="text" name="fdap_commanditaire" value="<?php echo esc_attr($values['commanditaire'] ?? ''); ?>" placeholder="Personne demandeuse">
                </div>
                <div class="fdap-field">
                    <label>Contraintes</label>
                    <input type="text" name="fdap_contraintes" value="<?php echo esc_attr($values['contraintes'] ?? ''); ?>" placeholder="Délais, ressources...">
                </div>
                <div class="fdap-field">
                    <label>Consignes reçues</label>
                    <textarea name="fdap_consignes_recues" rows="2" placeholder="Instructions données..."><?php echo esc_textarea($values['consignes_recues'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>
        
        <!-- Descriptif Détaillé -->
        <div class="fdap-section">
            <h3>📋 Descriptif Détaillé</h3>
            <div class="fdap-section-body">
                <div class="fdap-field">
                    <label>Avec qui ?</label>
                    <input type="text" name="fdap_avec_qui_" value="<?php echo esc_attr($values['avec_qui_'] ?? ''); ?>" placeholder="Équipe, tuteur...">
                </div>
                <div class="fdap-field">
                    <label>Déroulement</label>
                    <textarea name="fdap_deroulement" rows="4" placeholder="Décrivez les étapes..."><?php echo esc_textarea($values['deroulement'] ?? ''); ?></textarea>
                </div>
                <div class="fdap-field">
                    <label>Résultats obtenus</label>
                    <textarea name="fdap_resultats_" rows="3" placeholder="Ce qui a été réalisé..."><?php echo esc_textarea($values['resultats_'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>
        
        <!-- Bilan Personnel -->
        <div class="fdap-section">
            <h3>💡 Bilan Personnel</h3>
            <div class="fdap-section-body">
                <div class="fdap-field">
                    <label>Difficulté rencontrée</label>
                    <select name="fdap_difficulte" class="fdap-select">
                        <option value="">-- Sélectionner --</option>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php selected($values['difficulte'] ?? '', $i); ?>><?php echo str_repeat('★', $i) . str_repeat('☆', 5-$i); ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="fdap-field">
                    <label>Plaisir ressenti</label>
                    <select name="fdap_plaisir_" class="fdap-select">
                        <option value="">-- Sélectionner --</option>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php selected($values['plaisir_'] ?? '', $i); ?>><?php echo str_repeat('★', $i) . str_repeat('☆', 5-$i); ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="fdap-field">
                    <label>Améliorations possibles</label>
                    <textarea name="fdap_ameliorations" rows="3" placeholder="Ce que vous feriez différemment..."><?php echo esc_textarea($values['ameliorations'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>
        
        <!-- Multimédia -->
        <div class="fdap-section">
            <h3>🎤 Multimédia / Entretien</h3>
            <div class="fdap-section-body">
                <div class="fdap-media-grid">
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
                                <label class="fdap-upload-box fdap-upload-box-audio">
                                    <input type="file" name="fdap_audio" accept="audio/*" class="fdap-hidden-input" id="fdap-audio-upload">
                                    <span class="fdap-upload-icon">📁</span>
                                    <span class="fdap-upload-text">Fichier audio</span>
                                    <span class="fdap-upload-hint">MP3, WAV, OGG</span>
                                </label>
                                <button type="button" class="fdap-record-btn" id="fdap-student-record-btn" onclick="startStudentAudioRecording(this)">🎤 Enregistrer</button>
                                <button type="button" class="fdap-pause-btn" id="fdap-student-pause-btn" onclick="togglePauseStudentAudio()" style="display:none; background: #f59e0b; color: #fff; border: none; padding: 10px 20px; border-radius: 10px; cursor: pointer; font-size: 14px; font-weight: 600;">⏸ Pause</button>
                                <button type="button" class="fdap-stop-btn" id="fdap-student-stop-btn" onclick="stopStudentAudioRecording(this)" style="display:none;">⏹ Arrêter</button>
                                <canvas id="fdap-student-waveform" width="180" height="40" style="display:none;"></canvas>
                                <span class="fdap-timer" id="fdap-student-timer" style="display:none;">00:00</span>
                            </div>
                            <div class="fdap-preview-row" id="fdap-student-preview" style="display: none;">
                                <audio id="fdap-student-audio" controls></audio>
                                <button type="button" class="fdap-clear-btn" onclick="clearStudentAudio()">🗑 Supprimer</button>
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
            <h3>📷 Photos</h3>
            <div class="fdap-section-body">
                <div class="fdap-photos-grid">
                    <?php for ($i = 1; $i <= 6; $i++): 
                        $photo_id = $is_edit ? get_post_meta($post_id, '_fdap_photo_' . $i, true) : 0;
                        $photo_url = $photo_id ? wp_get_attachment_url($photo_id) : '';
                    ?>
                    <div class="fdap-photo-item">
                        <label>Photo <?php echo $i; ?></label>
                        <?php if ($photo_url): ?>
                            <div class="fdap-photo-preview">
                                <img src="<?php echo esc_url($photo_url); ?>" alt="Photo <?php echo $i; ?>">
                                <div class="fdap-photo-overlay">
                                    <a href="<?php echo esc_url($photo_url); ?>" target="_blank" class="fdap-photo-btn">🔍</a>
                                    <button type="button" class="fdap-photo-btn fdap-photo-remove" onclick="removePhoto(this, <?php echo $i; ?>);">🗑</button>
                                </div>
                                <input type="hidden" name="fdap_keep_photo_<?php echo $i; ?>" value="<?php echo esc_attr($photo_id); ?>">
                            </div>
                        <?php else: ?>
                            <label class="fdap-upload-box fdap-upload-box-photo">
                                <input type="file" name="fdap_photo_<?php echo $i; ?>" accept="image/*" class="fdap-hidden-input" data-preview="photo-preview-<?php echo $i; ?>">
                                <span class="fdap-upload-icon">📷</span>
                                <span class="fdap-upload-text">Photo <?php echo $i; ?></span>
                            </label>
                            <div class="fdap-photo-preview fdap-photo-empty" id="photo-preview-<?php echo $i; ?>" style="display:none;"></div>
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
        <div class="fdap-section fdap-comments-section" style="background: linear-gradient(135deg, #fef3c7 0%, #fcd34d 100%); border: 2px solid #f59e0b; margin-bottom: 20px;">
            <h3 style="background: #f59e0b; color: #fff; padding: 12px 15px; margin: 0;">📝 Commentaires du professeur</h3>
            <div class="fdap-section-body" style="padding: 20px; background: #fff;">
                <?php if (!empty($existing_comments)): ?>
                <div class="fdap-comments-history" style="margin-bottom: 20px;">
                    <h4 style="font-size: 14px; color: #666; margin-bottom: 10px;">Historique (<?php echo count($existing_comments); ?>) :</h4>
                    <?php $c_idx = 0; foreach (array_reverse($existing_comments) as $comment): $orig_idx = count($existing_comments) - 1 - $c_idx; ?>
                    <div class="fdap-comment-entry" style="background: #fff; border-left: 4px solid #f59e0b; padding: 12px; margin-bottom: 10px; border-radius: 4px; position: relative;"><button type="submit" name="fdap_delete_comment" value="<?php echo $orig_idx; ?>" style="position: absolute; top: 8px; right: 8px; background: #fee2e2; color: #dc2626; border: none; width: 24px; height: 24px; border-radius: 50%; cursor: pointer;">×
                        <div style="font-size: 12px; color: #888; margin-bottom: 5px;"><?php echo date('d/m/Y à H:i', strtotime($comment['date'])); ?></div>
                        <?php if (!empty($comment['text'])): ?><div style="margin-bottom: 8px;"><?php echo nl2br(esc_html($comment['text'])); ?></div><?php endif; ?>
                        <?php if (!empty($comment['audio_id'])): $audio_url = wp_get_attachment_url($comment['audio_id']); ?>
                        <div><audio controls style="max-width: 100%;"><source src="<?php echo esc_url($audio_url); ?>"></audio></div><?php endif; ?>
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
                        <button type="button" class="fdap-record-btn" id="fdap-record-btn" onclick="startFdapAudioRecording(this)" style="background: #10b981; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600;">🎤 Enregistrer</button>
                        <button type="button" class="fdap-pause-btn" id="fdap-pause-btn" onclick="togglePauseFdapAudio()" style="display: none; background: #f59e0b; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600;">⏸ Pause</button>
                        <button type="button" class="fdap-stop-btn" id="fdap-stop-btn" onclick="stopFdapAudioRecording(this)" style="display: none; background: #ef4444; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600;">⏹ Arrêter</button>
                        <canvas id="fdap-waveform" width="200" height="40" style="display: none; background: #1a1a2e; border-radius: 8px; vertical-align: middle;"></canvas>
                        <span class="fdap-recording-time" style="color: #666; font-size: 14px; display: none;">00:00</span>
                        <div class="fdap-audio-preview" id="fdap-audio-preview" style="margin-top: 10px; display: none;"><audio id="fdap-recorded-audio" controls style="max-width: 100%;"></audio> <button type="button" onclick="clearFdapAudioRecording()" style="background: #fee2e2; color: #dc2626; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; margin-left: 10px;">🗑 Supprimer</button></div>
                        <input type="hidden" name="fdap_comment_audio_data" id="fdap-comment-audio-data">
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="fdap-submit-wrap">
            <button type="submit" class="fdap-submit-btn">
                <?php echo $is_edit ? 'Mettre à jour' : 'Enregistrer la fiche'; ?>
            </button>
        </div>
    </form>
</div>