console.log("FDAP script loaded");
// Supprimer un fichier multimédia
function removeFile(btn, hiddenName) {
    var container = btn.closest('.fdap-file-preview');
    var parent = container.closest('.fdap-media-item');
    var hiddenInput = container.querySelector('input[name="' + hiddenName + '"]');
    
    // Supprimer le preview
    container.remove();
    
    // Supprimer le hidden input
    if (hiddenInput) hiddenInput.remove();
    
    // Ajouter la zone d'upload
    var type = hiddenName.replace('fdap_keep_', '');
    var labels = {audio: {icon: '🎤', text: 'Ajouter un audio', hint: 'MP3, WAV, OGG...'},
                  video: {icon: '📹', text: 'Ajouter une vidéo', hint: 'MP4, WebM, MOV...'},
                  fichier: {icon: '📄', text: 'Ajouter un document', hint: 'PDF, Word, Excel...'}};
    var l = labels[type];
    
    var html = '<label class="fdap-upload-box">' +
        '<input type="file" name="fdap_' + type + '" accept="' + (type === 'audio' ? 'audio/*' : (type === 'video' ? 'video/*' : '.pdf,.doc,.docx,.xls,.xlsx')) + '" class="fdap-hidden-input">' +
        '<span class="fdap-upload-icon">' + l.icon + '</span>' +
        '<span class="fdap-upload-text">' + l.text + '</span>' +
        '<span class="fdap-upload-hint">' + l.hint + '</span>' +
    '</label>';
    parent.insertAdjacentHTML('beforeend', html);
}

// Supprimer une photo
function removePhoto(btn, index) {
    var container = btn.closest('.fdap-photo-preview');
    var parent = container.closest('.fdap-photo-item');
    
    // Supprimer le preview
    container.remove();
    
    // Ajouter la zone d'upload
    var html = '<label class="fdap-upload-box fdap-upload-box-photo">' +
        '<input type="file" name="fdap_photo_' + index + '" accept="image/*" class="fdap-hidden-input" data-preview="photo-preview-' + index + '">' +
        '<span class="fdap-upload-icon">📷</span>' +
        '<span class="fdap-upload-text">Photo ' + index + '</span>' +
    '</label>' +
    '<div class="fdap-photo-preview fdap-photo-empty" id="photo-preview-' + index + '" style="display:none;"></div>';
    parent.insertAdjacentHTML('beforeend', html);
    
    // Réattacher l'event listener
    var input = parent.querySelector('input[data-preview="photo-preview-' + index + '"]');
    if (input) {
        input.addEventListener('change', handlePhotoUpload);
    }
}

// Preview photo upload
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
// Audio Recording for FDAP Comments with Waveform Visualizer
var fdapAudioContext = null, fdapAnalyser = null, fdapAnimationId = null;
var fdapMediaRecorder = null, fdapAudioChunks = [], fdapRecordingInterval = null, fdapRecordingSeconds = 0;

function startFdapAudioRecording(btn) {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        alert("Votre navigateur ne supporte pas l'enregistrement audio.");
        return;
    }
    navigator.mediaDevices.getUserMedia({ audio: true }).then(function(stream) {
        // Setup visualizer
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
        
        // Setup recorder
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
        document.getElementById("fdap-record-btn").disabled = false;
        document.getElementById("fdap-record-btn").style.opacity = "1";
        btn.disabled = true; btn.style.opacity = "0.5";
    }
}

function clearFdapAudioRecording() {
    document.getElementById("fdap-audio-preview").style.display = "none";
    document.getElementById("fdap-comment-audio-data").value = "";
}
// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.fdap-hidden-input').forEach(function(input) {
        if (input.name.startsWith('fdap_photo_')) {
            input.addEventListener('change', handlePhotoUpload);
        }
    });
});

