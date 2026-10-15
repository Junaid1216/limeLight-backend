(function ($) {
    let mediaRecorder = null;
    let recordedChunks = [];
    let recordingStream = null;
    let recordingTimer = null;
    let recordingSeconds = 0;

    function recordingMimeType() {
        const types = ['audio/webm', 'audio/webm;codecs=opus', 'audio/ogg', 'audio/mp4'];
        for (let i = 0; i < types.length; i++) {
            if (window.MediaRecorder && MediaRecorder.isTypeSupported(types[i])) {
                return types[i];
            }
        }
        return '';
    }

    function recordingExtension(mime) {
        if ((mime || '').indexOf('ogg') !== -1) {
            return 'ogg';
        }
        if ((mime || '').indexOf('mp4') !== -1) {
            return 'm4a';
        }
        return 'webm';
    }

    function attachRecordingToInput(blob) {
        const input = document.getElementById('audio_recording');
        if (!input || typeof DataTransfer === 'undefined') {
            return;
        }
        const mime = blob.type || 'audio/webm';
        const file = new File([blob], 'voice-recording.' + recordingExtension(mime), { type: mime });
        const dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
    }

    function stopRecordingTracks() {
        if (recordingStream) {
            recordingStream.getTracks().forEach(function (track) {
                track.stop();
            });
            recordingStream = null;
        }
        if (recordingTimer) {
            clearInterval(recordingTimer);
            recordingTimer = null;
        }
    }

    window.discardVoiceRecording = function () {
        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            try {
                mediaRecorder.stop();
            } catch (e) {}
        }
        mediaRecorder = null;
        recordedChunks = [];
        stopRecordingTracks();
        $('#audio_recording').val('');
        $('#recording_preview').hide().attr('src', '');
        $('#recording_status').text('');
        $('#start_recording').prop('disabled', false);
        $('#stop_recording').prop('disabled', true);
        $('#discard_recording').prop('disabled', true);
    };

    $(function () {
        $('#start_recording').on('click', function () {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                alert('Microphone recording is not supported in this browser.');
                return;
            }

            navigator.mediaDevices.getUserMedia({ audio: true }).then(function (stream) {
                recordingStream = stream;
                recordedChunks = [];
                const mime = recordingMimeType();
                mediaRecorder = mime
                    ? new MediaRecorder(stream, { mimeType: mime })
                    : new MediaRecorder(stream);

                mediaRecorder.ondataavailable = function (event) {
                    if (event.data && event.data.size > 0) {
                        recordedChunks.push(event.data);
                    }
                };

                mediaRecorder.onstop = function () {
                    stopRecordingTracks();
                    if (!recordedChunks.length) {
                        return;
                    }
                    const blob = new Blob(recordedChunks, { type: mediaRecorder.mimeType || 'audio/webm' });
                    attachRecordingToInput(blob);
                    const url = URL.createObjectURL(blob);
                    $('#recording_preview').attr('src', url).show();
                    $('#recording_status').text('Recording ready');
                    $('#start_recording').prop('disabled', false);
                    $('#stop_recording').prop('disabled', true);
                    $('#discard_recording').prop('disabled', false);
                };

                mediaRecorder.start();
                recordingSeconds = 0;
                $('#recording_status').text('Recording... 0s');
                recordingTimer = setInterval(function () {
                    recordingSeconds += 1;
                    $('#recording_status').text('Recording... ' + recordingSeconds + 's');
                }, 1000);

                $('#start_recording').prop('disabled', true);
                $('#stop_recording').prop('disabled', false);
                $('#discard_recording').prop('disabled', false);
                $('#recording_preview').hide().attr('src', '');
            }).catch(function () {
                alert('Microphone permission is required to record audio.');
            });
        });

        $('#stop_recording').on('click', function () {
            if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                mediaRecorder.stop();
            }
        });

        $('#discard_recording').on('click', function () {
            window.discardVoiceRecording();
        });

        $('form').on('submit', function (event) {
            if (mediaRecorder && mediaRecorder.state === 'recording') {
                event.preventDefault();
                alert('Stop the voice recording before saving.');
            }
        });
    });
})(jQuery);
