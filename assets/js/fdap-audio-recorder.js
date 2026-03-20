// Audio Recording for FDAP Comments
let fdapMediaRecorder = null;
let fdapAudioChunks = [];
let fdapRecordingInterval = null;
let fdapRecordingSeconds = 0;

function startFdapAudioRecording(btn) {
    navigator.mediaDevices.getUserMedia({ audio: true })
        .then(function(stream) {
            fdapMediaRecorder = new MediaRecorder(stream);
            fdapAudioChunks = [];
            
            fdapMediaRecorder.ondataavailable = function(e) {
                fdapAudioChunks.push(e.data);
            };
            
            fdapMediaRecorder.onstop = function(e) {
                var blob = new Blob(fdapAudioChunks, { type: 'audio/webm' });
                var audioURL = URL.createObjectURL(blob);
                
                var preview = document.getElementById('fdap-audio-preview');
                var audio = document.getElementById('fdap-recorded-audio');
                var dataInput = document.getElementById('fdap-comment-audio-data');
                
                audio.src = audioURL;
                preview.style.display = 'block';
                
                var reader = new FileReader();
                reader.onloadend = function() {
                    dataInput.value = reader.result;
                };
                reader.readAsDataURL(blob);
            };
            
            fdapMediaRecorder.start();
            
            btn.disabled = true;
            btn.style.opacity = '0.5';
            var stopBtn = btn.nextElementSibling;
            stopBtn.disabled = false;
            stopBtn.style.opacity = '1';
            
            var timerSpan = document.querySelector('.fdap-recording-time');
            timerSpan.style.display = 'inline';
            
            fdapRecordingSeconds = 0;
            fdapRecordingInterval = setInterval(function() {
                fdapRecordingSeconds++;
                var mins = Math.floor(fdapRecordingSeconds / 60);
                var secs = fdapRecordingSeconds % 60;
                timerSpan.textContent = (mins < 10 ? '0' : '') + mins + ':' + (secs < 10 ? '0' : '') + secs;
            }, 1000);
        })
        .catch(function(err) {
            alert('Impossible d\'accéder au microphone. Vérifiez les permissions du navigateur.');
            console.error('Erreur microphone:', err);
        });
}

function stopFdapAudioRecording(btn) {
    if (fdapMediaRecorder && fdapMediaRecorder.state === 'recording') {
        fdapMediaRecorder.stop();
        fdapMediaRecorder.stream.getTracks().forEach(function(track) { track.stop(); });
        
        clearInterval(fdapRecordingInterval);
        
        var recordBtn = btn.previousElementSibling;
        recordBtn.disabled = false;
        recordBtn.style.opacity = '1';
        recordBtn.textContent = '🎤 Enregistrer';
        btn.disabled = true;
        btn.style.opacity = '0.5';
    }
}

function clearFdapAudioRecording() {
    document.getElementById('fdap-audio-preview').style.display = 'none';
    document.getElementById('fdap-comment-audio-data').value = '';
}