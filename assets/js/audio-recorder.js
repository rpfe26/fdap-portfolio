// Audio Recording for FDAP Comments
var fdapMediaRecorder = null;
var fdapAudioChunks = [];
var fdapRecordingInterval = null;
var fdapRecordingSeconds = 0;

function startFdapAudioRecording(btn) {
    navigator.mediaDevices.getUserMedia({ audio: true })
        .then(function(stream) {
            fdapMediaRecorder = new MediaRecorder(stream);
            fdapAudioChunks = [];
            fdapMediaRecorder.ondataavailable = function(e) { fdapAudioChunks.push(e.data); };
            fdapMediaRecorder.onstop = function(e) {
                var blob = new Blob(fdapAudioChunks, { type: 'audio/webm' });
                document.getElementById('fdap-recorded-audio').src = URL.createObjectURL(blob);
                document.getElementById('fdap-audio-preview').style.display = 'block';
                var reader = new FileReader();
                reader.onloadend = function() { document.getElementById('fdap-comment-audio-data').value = reader.result; };
                reader.readAsDataURL(blob);
            };
            fdapMediaRecorder.start();
            btn.disabled = true; btn.style.opacity = '0.5';
            var stopBtn = btn.nextElementSibling;
            stopBtn.disabled = false; stopBtn.style.opacity = '1';
            var timerSpan = document.querySelector('.fdap-recording-time');
            timerSpan.style.display = 'inline';
            fdapRecordingSeconds = 0;
            fdapRecordingInterval = setInterval(function() {
                fdapRecordingSeconds++;
                timerSpan.textContent = Math.floor(fdapRecordingSeconds/60).toString().padStart(2,'0') + ':' + (fdapRecordingSeconds%60).toString().padStart(2,'0');
            }, 1000);
        })
        .catch(function(err) { alert('Impossible d acceder au microphone.'); console.error(err); });
}

function stopFdapAudioRecording(btn) {
    if (fdapMediaRecorder && fdapMediaRecorder.state === 'recording') {
        fdapMediaRecorder.stop();
        fdapMediaRecorder.stream.getTracks().forEach(function(t) { t.stop(); });
        clearInterval(fdapRecordingInterval);
        btn.previousElementSibling.disabled = false;
        btn.previousElementSibling.style.opacity = '1';
        btn.disabled = true;
        btn.style.opacity = '0.5';
    }
}

function clearFdapAudioRecording() {
    document.getElementById('fdap-audio-preview').style.display = 'none';
    document.getElementById('fdap-comment-audio-data').value = '';
}
