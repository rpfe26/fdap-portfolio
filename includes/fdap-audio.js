/**
 * FDAP Audio Recording - Avec bouton Pause
 * Version: 1.5.0
 */
console.log('FDAP script loaded');

// ============================================
// GESTION DES FICHIERS MULTIMÉDIA
// ============================================

function removeFile(btn, hiddenName) {
    var container = btn.closest('.fdap-file-preview');
    var parent = container.closest('.fdap-media-item');
    var hiddenInput = container.querySelector('input[name="' + hiddenName + '"]');
    
    container.remove();
    if (hiddenInput) hiddenInput.remove();
    
    var type = hiddenName.replace('fdap_keep_', '');
    var labels = {
        audio: {icon: '📁', text: 'Fichier audio', hint: 'MP3, WAV, OGG'},
        video: {icon: '📹', text: 'Vidéo', hint: 'MP4, WebM'},
        fichier: {icon: '📄', text: 'Document', hint: 'PDF, Word'}
    };
    var l = labels[type];
    
    if (type === 'audio') {
        var html = '<div class="fdap-audio-box" id="fdap-audio-box">' +
            '<label class="fdap-upload-box fdap-upload-box-audio">' +
                '<input type="file" name="fdap_audio" accept="audio/*" class="fdap-hidden-input" id="fdap-audio-upload">' +
                '<span class="fdap-upload-icon">📁</span>' +
                '<span class="fdap-upload-text">Fichier audio</span>' +
                '<span class="fdap-upload-hint">MP3, WAV, OGG</span>' +
            '</label>' +
            '<button type="button" class="fdap-record-btn" id="fdap-student-record-btn" onclick="startStudentAudioRecording(this)">🎤 Enregistrer</button>' +
            '<button type="button" class="fdap-pause-btn" id="fdap-student-pause-btn" onclick="togglePauseStudentAudio()" style="display:none; background: #f59e0b; color: #fff; border: none; padding: 10px 20px; border-radius: 10px; cursor: pointer; font-size: 14px; font-weight: 600;">⏸ Pause</button>' +
            '<button type="button" class="fdap-stop-btn" id="fdap-student-stop-btn" onclick="stopStudentAudioRecording(this)" style="display:none;">⏹ Arrêter</button>' +
            '<canvas id="fdap-student-waveform" width="180" height="40" style="display:none;"></canvas>' +
            '<span class="fdap-timer" id="fdap-student-timer" style="display:none;">00:00</span>' +
        '</div>' +
        '<div class="fdap-preview-row" id="fdap-student-preview" style="display: none;">' +
            '<audio id="fdap-student-audio" controls></audio>' +
            '<button type="button" class="fdap-clear-btn" onclick="clearStudentAudio()">🗑 Supprimer</button>' +
        '</div>' +
        '<input type="hidden" name="fdap_student_audio_data" id="fdap-student-audio-data">';
    } else {
        var html = '<label class="fdap-upload-box">' +
            '<input type="file" name="fdap_' + type + '" accept="' + (type === 'video' ? 'video/*' : '.pdf,.doc,.docx,.xls,.xlsx') + '" class="fdap-hidden-input">' +
            '<span class="fdap-upload-icon">' + l.icon + '</span>' +
            '<span class="fdap-upload-text">' + l.text + '</span>' +
            '<span class="fdap-upload-hint">' + l.hint + '</span>' +
        '</label>';
    }
    parent.insertAdjacentHTML('beforeend', html);
}

function removePhoto(btn, index) {
    var container = btn.closest('.fdap-photo-preview');
    var parent = container.closest('.fdap-photo-item');
    
    container.remove();
    
    var html = '<label class="fdap-upload-box fdap-upload-box-photo">' +
        '<input type="file" name="fdap_photo_' + index + '" accept="image/*" class="fdap-hidden-input" data-preview="photo-preview-' + index + '">' +
        '<span class="fdap-upload-icon">📷</span>' +
        '<span class="fdap-upload-text">Photo ' + index + '</span>' +
    '</label>' +
    '<div class="fdap-photo-preview fdap-photo-empty" id="photo-preview-' + index + '" style="display:none;"></div>';
    parent.insertAdjacentHTML('beforeend', html);
    
    var input = parent.querySelector('input[data-preview="photo-preview-' + index + '"]');
    if (input) {
        input.addEventListener('change', handlePhotoUpload);
    }
}

function handlePhotoUpload(e) {
    var previewId = this.getAttribute('data-preview');
    var preview = document.getElementById(previewId);
    var parent = this.closest('.fdap-photo-item');
    var uploadBox = parent.querySelector('.fdap-upload-box-photo');
    
    if (this.files && this.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">' +
                '<div class="fdap-photo-overlay">' +
                    '<button type="button" class="fdap-photo-btn fdap-photo-remove" onclick="this.closest(\'.fdap-photo-preview\').style.display=\'none\'; document.querySelector(\'.fdap-upload-box-photo input\').value=\'\';">🗑</button>' +
                '</div>';
            preview.style.display = 'block';
            uploadBox.style.display = 'none';
        };
        reader.readAsDataURL(this.files[0]);
    }
}

// ============================================
// ENREGISTREMENT AUDIO ÉLÈVE (Avec Pause)
// ============================================

var fdapStudentRecorder = null;
var fdapStudentChunks = [];
var fdapStudentContext = null;
var fdapStudentAnalyser = null;
var fdapStudentAnimId = null;
var fdapStudentTimerInterval = null;
var fdapStudentSeconds = 0;

function startStudentAudioRecording(btn) {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        alert("Votre navigateur ne supporte pas l'enregistrement audio.");
        return;
    }
    
    // Afficher les boutons pause et stop
    var pauseBtn = document.getElementById('fdap-student-pause-btn');
    var stopBtn = document.getElementById('fdap-student-stop-btn');
    var canvas = document.getElementById('fdap-student-waveform');
    var timer = document.getElementById('fdap-student-timer');
    
    if (pauseBtn) { pauseBtn.style.display = 'inline-block'; pauseBtn.textContent = '⏸ Pause'; }
    if (stopBtn) { stopBtn.style.display = 'inline-block'; }
    if (canvas) canvas.style.display = 'inline-block';
    if (timer) timer.style.display = 'inline';
    
    btn.disabled = true;
    btn.style.opacity = '0.5';
    
    // Masquer l'upload
    var uploadBox = document.querySelector('.fdap-upload-box-audio');
    if (uploadBox) uploadBox.style.display = 'none';
    
    navigator.mediaDevices.getUserMedia({ audio: true }).then(function(stream) {
        // Waveform
        fdapStudentContext = new (window.AudioContext || window.webkitAudioContext)();
        fdapStudentAnalyser = fdapStudentContext.createAnalyser();
        fdapStudentAnalyser.fftSize = 256;
        var source = fdapStudentContext.createMediaStreamSource(stream);
        source.connect(fdapStudentAnalyser);
        
        drawWaveform();
        
        function drawWaveform() {
            fdapStudentAnimId = requestAnimationFrame(drawWaveform);
            var buf = fdapStudentAnalyser.frequencyBinCount;
            var arr = new Uint8Array(buf);
            fdapStudentAnalyser.getByteFrequencyData(arr);
            var ctx = canvas.getContext('2d');
            ctx.fillStyle = '#0f172a';
            ctx.fillRect(0, 0, 180, 40);
            var w = Math.max(5, (180 / 32) - 2);
            var x = 2;
            for (var i = 0; i < 32; i++) {
                var idx = Math.floor(i * buf / 32);
                var v = arr[idx] / 255;
                var h = Math.max(3, v * 34);
                ctx.fillStyle = v > 0.5 ? '#22c55e' : (v > 0.25 ? '#10b981' : '#06b6d4');
                ctx.fillRect(x, 40 - h - 3, w, h);
                x += w + 2;
            }
        }
        
        // Recorder
        fdapStudentRecorder = new MediaRecorder(stream);
        fdapStudentChunks = [];
        fdapStudentRecorder.ondataavailable = function(e) { fdapStudentChunks.push(e.data); };
        fdapStudentRecorder.onstop = function(e) {
            var blob = new Blob(fdapStudentChunks, { type: 'audio/webm' });
            var preview = document.getElementById('fdap-student-preview');
            var audio = document.getElementById('fdap-student-audio');
            var data = document.getElementById('fdap-student-audio-data');
            
            if (audio && data) {
                audio.src = URL.createObjectURL(blob);
                if (preview) preview.style.display = 'flex';
                var reader = new FileReader();
                reader.onloadend = function() { data.value = reader.result; };
                reader.readAsDataURL(blob);
            }
            if (fdapStudentAnimId) cancelAnimationFrame(fdapStudentAnimId);
        };
        
        fdapStudentRecorder.start();
        
        var timerEl = document.getElementById('fdap-student-timer');
        if (timerEl) timerEl.style.color = '#dc2626';
        fdapStudentSeconds = 0;
        fdapStudentTimerInterval = setInterval(function() {
            fdapStudentSeconds++;
            if (timerEl) {
                timerEl.textContent = Math.floor(fdapStudentSeconds/60).toString().padStart(2,'0') + ':' + (fdapStudentSeconds%60).toString().padStart(2,'0');
            }
        }, 1000);
        
    }).catch(function(err) {
        console.error('Mic error:', err);
        alert("Impossible d'accéder au microphone. Vérifiez les permissions.");
        // Réinitialiser l'UI en cas d'erreur
        var recordBtn = document.getElementById('fdap-student-record-btn');
        var pauseBtn = document.getElementById('fdap-student-pause-btn');
        var stopBtn = document.getElementById('fdap-student-stop-btn');
        if (recordBtn) { recordBtn.disabled = false; recordBtn.style.opacity = '1'; }
        if (pauseBtn) pauseBtn.style.display = 'none';
        if (stopBtn) stopBtn.style.display = 'none';
    });
}

function togglePauseStudentAudio() {
    if (!fdapStudentRecorder) return;
    
    var pauseBtn = document.getElementById('fdap-student-pause-btn');
    var timerEl = document.getElementById('fdap-student-timer');
    
    if (fdapStudentRecorder.state === 'recording') {
        // Pause
        fdapStudentRecorder.pause();
        clearInterval(fdapStudentTimerInterval);
        if (fdapStudentAnimId) cancelAnimationFrame(fdapStudentAnimId);
        if (pauseBtn) { pauseBtn.textContent = '▶ Reprendre'; pauseBtn.style.background = '#22c55e'; }
        if (timerEl) timerEl.style.color = '#f59e0b';
    } else if (fdapStudentRecorder.state === 'paused') {
        // Reprendre
        fdapStudentRecorder.resume();
        fdapStudentTimerInterval = setInterval(function() {
            fdapStudentSeconds++;
            if (timerEl) {
                timerEl.textContent = Math.floor(fdapStudentSeconds/60).toString().padStart(2,'0') + ':' + (fdapStudentSeconds%60).toString().padStart(2,'0');
            }
        }, 1000);
        
        // Redémarrer le waveform
        var canvas = document.getElementById('fdap-student-waveform');
        drawWaveform();
        
        function drawWaveform() {
            fdapStudentAnimId = requestAnimationFrame(drawWaveform);
            var buf = fdapStudentAnalyser.frequencyBinCount;
            var arr = new Uint8Array(buf);
            fdapStudentAnalyser.getByteFrequencyData(arr);
            var ctx = canvas.getContext('2d');
            ctx.fillStyle = '#0f172a';
            ctx.fillRect(0, 0, 180, 40);
            var w = Math.max(5, (180 / 32) - 2);
            var x = 2;
            for (var i = 0; i < 32; i++) {
                var idx = Math.floor(i * buf / 32);
                var v = arr[idx] / 255;
                var h = Math.max(3, v * 34);
                ctx.fillStyle = v > 0.5 ? '#22c55e' : (v > 0.25 ? '#10b981' : '#06b6d4');
                ctx.fillRect(x, 40 - h - 3, w, h);
                x += w + 2;
            }
        }
        
        if (pauseBtn) { pauseBtn.textContent = '⏸ Pause'; pauseBtn.style.background = '#f59e0b'; }
        if (timerEl) timerEl.style.color = '#dc2626';
    }
}

function stopStudentAudioRecording(btn) {
    if (fdapStudentRecorder && (fdapStudentRecorder.state === 'recording' || fdapStudentRecorder.state === 'paused')) {
        fdapStudentRecorder.stop();
        fdapStudentRecorder.stream.getTracks().forEach(function(t) { t.stop(); });
        clearInterval(fdapStudentTimerInterval);
        if (fdapStudentAnimId) cancelAnimationFrame(fdapStudentAnimId);
        
        var recordBtn = document.getElementById('fdap-student-record-btn');
        var pauseBtn = document.getElementById('fdap-student-pause-btn');
        var stopBtn = document.getElementById('fdap-student-stop-btn');
        var canvas = document.getElementById('fdap-student-waveform');
        var timerEl = document.getElementById('fdap-student-timer');
        
        if (recordBtn) { recordBtn.disabled = false; recordBtn.style.opacity = '1'; }
        if (pauseBtn) pauseBtn.style.display = 'none';
        if (stopBtn) stopBtn.style.display = 'none';
        if (canvas) canvas.style.display = 'none';
        if (timerEl) { timerEl.style.display = 'none'; timerEl.style.color = '#16a34a'; }
    }
}

function clearStudentAudio() {
    // Arrêter l'enregistrement si en cours
    if (fdapStudentRecorder) {
        if (fdapStudentRecorder.state === 'recording' || fdapStudentRecorder.state === 'paused') {
            fdapStudentRecorder.stop();
            fdapStudentRecorder.stream.getTracks().forEach(function(t) { t.stop(); });
        }
    }
    
    clearInterval(fdapStudentTimerInterval);
    if (fdapStudentAnimId) cancelAnimationFrame(fdapStudentAnimId);
    
    // Masquer l'aperçu
    var preview = document.getElementById('fdap-student-preview');
    var data = document.getElementById('fdap-student-audio-data');
    var canvas = document.getElementById('fdap-student-waveform');
    var uploadBox = document.querySelector('.fdap-upload-box-audio');
    
    if (preview) preview.style.display = 'none';
    if (data) data.value = '';
    if (canvas) canvas.style.display = 'none';
    if (uploadBox) uploadBox.style.display = 'flex';
    
    // Réinitialiser les boutons
    var recordBtn = document.getElementById('fdap-student-record-btn');
    var pauseBtn = document.getElementById('fdap-student-pause-btn');
    var stopBtn = document.getElementById('fdap-student-stop-btn');
    var timerEl = document.getElementById('fdap-student-timer');
    
    if (recordBtn) { recordBtn.disabled = false; recordBtn.style.opacity = '1'; }
    if (pauseBtn) { pauseBtn.style.display = 'none'; pauseBtn.textContent = '⏸ Pause'; pauseBtn.style.background = '#f59e0b'; }
    if (stopBtn) stopBtn.style.display = 'none';
    if (timerEl) { timerEl.textContent = '00:00'; timerEl.style.display = 'none'; }
    
    // Réinitialiser les variables
    fdapStudentRecorder = null;
    fdapStudentChunks = [];
    fdapStudentSeconds = 0;
    fdapStudentContext = null;
    fdapStudentAnalyser = null;
}

// ============================================
// ENREGISTREMENT AUDIO PROFESSEUR (Commentaires)
// ============================================

var fdapAudioContext = null, fdapAnalyser = null, fdapAnimationId = null;
var fdapMediaRecorder = null, fdapAudioChunks = [], fdapRecordingInterval = null, fdapRecordingSeconds = 0;

function startFdapAudioRecording(btn) {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        alert("Votre navigateur ne supporte pas l'enregistrement audio.");
        return;
    }
    navigator.mediaDevices.getUserMedia({ audio: true }).then(function(stream) {
        fdapAudioContext = new (window.AudioContext || window.webkitAudioContext)();
        fdapAnalyser = fdapAudioContext.createAnalyser();
        fdapAnalyser.fftSize = 256;
        var source = fdapAudioContext.createMediaStreamSource(stream);
        source.connect(fdapAnalyser);
        
        var canvas = document.getElementById("fdap-waveform");
        canvas.style.display = "inline-block";
        var ctx = canvas.getContext("2d");
        
        function drawWaveform() {
            fdapAnimationId = requestAnimationFrame(drawWaveform);
            var bufferLength = fdapAnalyser.frequencyBinCount;
            var dataArray = new Uint8Array(bufferLength);
            fdapAnalyser.getByteFrequencyData(dataArray);
            ctx.fillStyle = "#1a1a2e";
            ctx.fillRect(0, 0, 200, 40);
            var barWidth = (200 / bufferLength) * 1.5;
            var x = 0;
            for (var i = 0; i < bufferLength; i++) {
                var barHeight = (dataArray[i] / 255) * 35;
                var hue = (i / bufferLength) * 60 + 180;
                ctx.fillStyle = "hsl(" + hue + ", 70%, 60%)";
                ctx.fillRect(x, 40 - barHeight, barWidth - 1, barHeight);
                x += barWidth;
            }
        }
        drawWaveform();
        
        fdapMediaRecorder = new MediaRecorder(stream);
        fdapAudioChunks = [];
        fdapMediaRecorder.ondataavailable = function(e) { fdapAudioChunks.push(e.data); };
        fdapMediaRecorder.onstop = function(e) {
            var blob = new Blob(fdapAudioChunks, { type: "audio/webm" });
            document.getElementById("fdap-recorded-audio").src = URL.createObjectURL(blob);
            document.getElementById("fdap-audio-preview").style.display = "block";
            var reader = new FileReader();
            reader.onloadend = function() { document.getElementById("fdap-comment-audio-data").value = reader.result; };
            reader.readAsDataURL(blob);
            cancelAnimationFrame(fdapAnimationId);
            canvas.style.display = "none";
        };
        fdapMediaRecorder.start();
        btn.disabled = true; btn.style.opacity = "0.5";
        document.getElementById("fdap-pause-btn").style.display = "inline-block";
        document.getElementById("fdap-pause-btn").textContent = "⏸ Pause";
        document.getElementById("fdap-pause-btn").style.background = "#f59e0b";
        document.getElementById("fdap-stop-btn").style.display = "inline-block";
        document.getElementById("fdap-stop-btn").disabled = false;
        document.getElementById("fdap-stop-btn").style.opacity = "1";
        var timerSpan = document.querySelector(".fdap-recording-time");
        timerSpan.style.display = "inline";
        fdapRecordingSeconds = 0;
        fdapRecordingInterval = setInterval(function() {
            fdapRecordingSeconds++;
            timerSpan.textContent = Math.floor(fdapRecordingSeconds/60).toString().padStart(2,"0") + ":" + (fdapRecordingSeconds%60).toString().padStart(2,"0");
        }, 1000);
    }).catch(function(err) { alert("Impossible d'accéder au microphone."); console.error(err); });
}

function stopFdapAudioRecording(btn) {
    if (fdapMediaRecorder && fdapMediaRecorder.state === "recording") {
        fdapMediaRecorder.stop();
        fdapMediaRecorder.stream.getTracks().forEach(function(t) { t.stop(); });
        clearInterval(fdapRecordingInterval);
        if (fdapAnimationId) cancelAnimationFrame(fdapAnimationId);
        document.getElementById("fdap-waveform").style.display = "none";
        document.getElementById("fdap-pause-btn").style.display = "none";
        document.getElementById("fdap-record-btn").disabled = false;
        document.getElementById("fdap-record-btn").style.opacity = "1";
        btn.style.display = "none";
    }
}

function clearFdapAudioRecording() {
    document.getElementById("fdap-audio-preview").style.display = "none";
    document.getElementById("fdap-comment-audio-data").value = "";
}

// ============================================
// INITIALISATION
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.fdap-hidden-input').forEach(function(input) {
        if (input.name.startsWith('fdap_photo_')) {
            input.addEventListener('change', handlePhotoUpload);
        }
    });
});
// ============================================
// PAUSE POUR COMMENTAIRES PROF
// ============================================

function togglePauseFdapAudio() {
    if (!fdapMediaRecorder) return;
    
    var pauseBtn = document.getElementById('fdap-pause-btn');
    var timerSpan = document.querySelector('.fdap-recording-time');
    
    if (fdapMediaRecorder.state === 'recording') {
        // Pause
        fdapMediaRecorder.pause();
        clearInterval(fdapRecordingInterval);
        if (fdapAnimationId) cancelAnimationFrame(fdapAnimationId);
        if (pauseBtn) { pauseBtn.textContent = '▶ Reprendre'; pauseBtn.style.background = '#22c55e'; }
        if (timerSpan) timerSpan.style.color = '#f59e0b';
    } else if (fdapMediaRecorder.state === 'paused') {
        // Reprendre
        fdapMediaRecorder.resume();
        fdapRecordingInterval = setInterval(function() {
            fdapRecordingSeconds++;
            if (timerSpan) {
                timerSpan.textContent = Math.floor(fdapRecordingSeconds/60).toString().padStart(2,'0') + ':' + (fdapRecordingSeconds%60).toString().padStart(2,'0');
            }
        }, 1000);
        if (pauseBtn) { pauseBtn.textContent = '⏸ Pause'; pauseBtn.style.background = '#f59e0b'; }
        if (timerSpan) timerSpan.style.color = '#dc2626';
        
        // Redémarrer le waveform
        var canvas = document.getElementById('fdap-waveform');
        var ctx = canvas.getContext('2d');
        canvas.style.display = 'inline-block';
        function drawWaveform() {
            fdapAnimationId = requestAnimationFrame(drawWaveform);
            var buf = fdapAnalyser.frequencyBinCount;
            var arr = new Uint8Array(buf);
            fdapAnalyser.getByteFrequencyData(arr);
            ctx.fillStyle = '#1a1a2e';
            ctx.fillRect(0, 0, 200, 40);
            var barWidth = (200 / buf) * 1.5;
            var x = 0;
            for (var i = 0; i < buf; i++) {
                var barHeight = (arr[i] / 255) * 35;
                var hue = (i / buf) * 60 + 180;
                ctx.fillStyle = 'hsl(' + hue + ', 70%, 60%)';
                ctx.fillRect(x, 40 - barHeight, barWidth - 1, barHeight);
                x += barWidth;
            }
        }
        drawWaveform();
    }
}
