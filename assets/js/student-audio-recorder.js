/**
 * Student & Teacher Audio Recorder - FDAP Portfolio
 * Gère l'enregistrement audio, la prévisualisation des fichiers, 
 * le copier-coller d'images et la suppression des photos.
 */
(function () {
    'use strict';

    function log(msg, data) {
        console.log('[FDAP Form] ' + msg, data || '');
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Init form components
        initAudioRecorder('student');
        initAudioRecorder('teacher');
        initGeneralUploads();
        initPhotoUploads();
        log('FDAP Form Handlers loaded');
    });

    /**
     * Initialise un enregistreur audio basé sur son préfixe ('student' ou 'teacher')
     */
    function initAudioRecorder(prefix) {
        var recBtn = document.getElementById('fdap-' + prefix + '-record-btn');
        var stopBtn = document.getElementById('fdap-' + prefix + '-stop-btn');
        var pauseBtn = document.getElementById('fdap-' + prefix + '-pause-btn');
        var clearBtn = document.getElementById('fdap-' + prefix + '-clear-btn');

        if (!recBtn) return; // Si le bouton n'existe pas (ex: prof non connecté), on ignore

        var state = {
            mediaRecorder: null,
            audioChunks: [],
            recInterval: null,
            recSeconds: 0
        };

        recBtn.addEventListener('click', function() { startRecording(prefix, state); });
        
        if (stopBtn) {
            stopBtn.addEventListener('click', function() { stopRecording(prefix, state); });
        }
        if (pauseBtn) {
            pauseBtn.addEventListener('click', function() { togglePause(prefix, state); });
        }
        if (clearBtn) {
            clearBtn.addEventListener('click', function() { clearRecording(prefix, state); });
        }
    }

    function ui(prefix, recording) {
        var r = document.getElementById('fdap-' + prefix + '-record-btn');
        var s = document.getElementById('fdap-' + prefix + '-stop-btn');
        var p = document.getElementById('fdap-' + prefix + '-pause-btn');
        var i = document.getElementById('fdap-' + prefix + '-recording-indicator');
        if (r) r.style.display = recording ? 'none' : 'inline-flex';
        if (s) s.style.display = recording ? 'inline-flex' : 'none';
        if (p) p.style.display = recording ? 'inline-flex' : 'none';
        if (i) i.style.display = recording ? 'flex' : 'none';
    }

    function resetPauseBtn(prefix) {
        var p = document.getElementById('fdap-' + prefix + '-pause-btn');
        if (p) { p.innerHTML = '⏸ Pause'; p.style.background = '#f59e0b'; }
    }

    function startRecording(prefix, state) {
        navigator.mediaDevices.getUserMedia({ audio: true })
            .then(function(stream) {
                var types = ['audio/webm', 'audio/ogg', 'audio/mp4', 'audio/mpeg', 'audio/wav'];
                var mimeType = '';
                for (var i = 0; i < types.length; i++) {
                    if (MediaRecorder.isTypeSupported(types[i])) { mimeType = types[i]; break; }
                }
                var options = mimeType ? { mimeType: mimeType } : {};
                state.mediaRecorder = new MediaRecorder(stream, options);
                state.audioChunks = [];
                
                state.mediaRecorder.addEventListener('dataavailable', function(e) {
                    if (e.data && e.data.size > 0) { state.audioChunks.push(e.data); }
                });
                
                state.mediaRecorder.addEventListener('stop', function() {
                    log('Stopping ' + prefix + '. Chunks length: ' + state.audioChunks.length);
                    if (state.audioChunks.length === 0) {
                        log('No chunks collected for ' + prefix);
                        return;
                    }
                    var blob = new Blob(state.audioChunks, { type: state.mediaRecorder.mimeType });
                    var url = URL.createObjectURL(blob);
                    
                    var preview = document.getElementById('fdap-' + prefix + '-preview');
                    var audioEl = document.getElementById('fdap-' + prefix + '-audio');
                    var dataIn = document.getElementById('fdap-' + prefix + '-audio-data');

                    if (audioEl) audioEl.src = url;
                    if (preview) {
                        preview.style.setProperty('display', 'flex', 'important');
                    }
                    if (dataIn) {
                        var reader = new FileReader();
                        reader.onloadend = function() { dataIn.value = reader.result; };
                        reader.readAsDataURL(blob);
                    }
                });
                
                state.mediaRecorder.start(250); // Important: 250ms chunks for HTTP local
                ui(prefix, true);
                
                state.recSeconds = 0;
                var timer = document.getElementById('fdap-' + prefix + '-timer');
                if (timer) timer.textContent = '00:00';
                
                state.recInterval = setInterval(function() {
                    state.recSeconds++;
                    var m = Math.floor(state.recSeconds / 60);
                    var s = state.recSeconds % 60;
                    if (timer) timer.textContent = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
                }, 1000);
            }).catch(function(err) { log('Mic error', err); });
    }

    function stopRecording(prefix, state) {
        log('stopRecording called for ' + prefix);
        if (!state.mediaRecorder) return;
        clearInterval(state.recInterval);
        
        if (state.mediaRecorder.state !== 'inactive') {
            state.mediaRecorder.requestData();
            setTimeout(function() {
                if (state.mediaRecorder.state !== 'inactive') {
                    state.mediaRecorder.stop();
                }
                if (state.mediaRecorder.stream) {
                    state.mediaRecorder.stream.getTracks().forEach(function(t) { t.stop(); });
                }
            }, 100);
        }
        ui(prefix, false);
        resetPauseBtn(prefix);
    }

    function togglePause(prefix, state) {
        if (!state.mediaRecorder) return;
        var pBtn = document.getElementById('fdap-' + prefix + '-pause-btn');
        if (state.mediaRecorder.state === 'recording') {
            state.mediaRecorder.pause(); 
            clearInterval(state.recInterval);
            if (pBtn) { pBtn.innerHTML = '▶ Reprendre'; pBtn.style.background = '#10b981'; }
        } else if (state.mediaRecorder.state === 'paused') {
            state.mediaRecorder.resume();
            var timer = document.getElementById('fdap-' + prefix + '-timer');
            state.recInterval = setInterval(function() {
                state.recSeconds++;
                var m = Math.floor(state.recSeconds / 60);
                var s = state.recSeconds % 60;
                if (timer) timer.textContent = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
            }, 1000);
            resetPauseBtn(prefix);
        }
    }

    function clearRecording(prefix, state) {
        var p = document.getElementById('fdap-' + prefix + '-preview');
        var a = document.getElementById('fdap-' + prefix + '-audio');
        var d = document.getElementById('fdap-' + prefix + '-audio-data');
        if (p) p.style.display = 'none';
        if (a) {
            a.pause();
            a.removeAttribute('src');
            a.load();
        }
        if (d) d.value = '';
        state.audioChunks = [];
    }

    /**
     * File Uploads (General)
     */
    function initGeneralUploads() {
        document.querySelectorAll('.fdap-hidden-input').forEach(function (input) {
            input.addEventListener('change', function () {
                var box = this.closest('.fdap-upload-box');
                if (box && this.files.length > 0) {
                    var txt  = box.querySelector('.fdap-upload-text');
                    var icon = box.querySelector('.fdap-upload-icon');
                    if (txt)  { 
                        txt.textContent  = this.files[0].name; 
                        txt.style.color = '#10b981'; 
                        txt.style.fontWeight = '700'; 
                    }
                    if (icon) { icon.textContent = '✅'; }
                }
            });
        });
    }

    /**
     * Photo Uploads (avec copier coller)
     */
    function initPhotoUploads() {
        document.querySelectorAll('input[name^="fdap_photo_"]').forEach(function (input) {
            input.addEventListener('change', function () {
                var previewId = input.getAttribute('data-preview');
                var previewEl = document.getElementById(previewId);
                if (previewEl && input.files.length > 0) {
                    displayPhotoPreview(previewEl, input.files[0], input);
                }
            });
        });

        document.querySelectorAll('.fdap-photo-item').forEach(function (item, index) {
            item.addEventListener('paste', function (e) {
                var items = (e.clipboardData || e.originalEvent.clipboardData).items;
                for (var i = 0; i < items.length; i++) {
                    if (items[i].type.indexOf('image') !== -1) {
                        var blob = items[i].getAsFile();
                        var input = this.querySelector('input[type="file"]');
                        var previewEl = document.getElementById('photo-preview-' + (index + 1));
                        
                        if (previewEl) {
                            displayPhotoPreview(previewEl, blob, input);
                            if (input) {
                                var dt = new DataTransfer();
                                dt.items.add(blob);
                                input.files = dt.files;
                            }
                        }
                        e.preventDefault();
                        break;
                    }
                }
            });
        });
    }

    function displayPhotoPreview(previewEl, file, input) {
        var reader = new FileReader();
        reader.onload = function (e) {
            previewEl.innerHTML = ''; 
            
            var img = document.createElement('img');
            img.src = e.target.result;
            img.style.width = '100%';
            img.style.height = '100px';
            img.style.objectFit = 'cover';
            img.style.borderRadius = '8px';
            
            var badge = document.createElement('button');
            badge.type = 'button';
            badge.className = 'fdap-photo-badge';
            badge.innerHTML = '&times;';
            badge.onclick = function() {
                previewEl.style.display = 'none';
                previewEl.innerHTML = '';
                if (input) input.value = '';
            };
            
            previewEl.appendChild(img);
            previewEl.appendChild(badge);
            previewEl.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }

    // Global helpers for PHP onclick
    window.removePhoto = function (btn, index) {
        var preview = btn.closest('.fdap-photo-preview');
        if (preview) {
            var keepInput = preview.querySelector('.fdap-keep-photo');
            if (keepInput) keepInput.value = '';
            preview.style.display = 'none';
        }
    };

    window.removeFile = function (btn, field) {
        var preview = btn.closest('.fdap-file-preview');
        if (preview) {
            var input = preview.querySelector('input[name="' + field + '"]');
            if (input) input.value = '';
            preview.style.display = 'none';
        }
    };
})();
