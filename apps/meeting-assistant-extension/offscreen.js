/**
 * Offscreen Document Audio Processor (Manifest V3)
 * Captures tab audio stream from Google Meet / Teams using chromeMediaSourceId
 * Keeps tab audio playing to user's speakers, processes speech for transcription
 */

let audioContext = null;
let tabMediaStream = null;
let speechRecognizer = null;

chrome.runtime.onMessage.addListener(async (message) => {
  if (message.target !== 'offscreen') return;

  if (message.type === 'START_TAB_AUDIO_STREAM' && message.streamId) {
    try {
      // 1. Capture Tab Audio (Remote participants speech)
      tabMediaStream = await navigator.mediaDevices.getUserMedia({
        audio: {
          mandatory: {
            chromeMediaSource: 'tab',
            chromeMediaSourceId: message.streamId,
          },
        },
        video: false,
      });

      // 2. Play audio to speakers so user still hears the meeting!
      audioContext = new AudioContext();
      const source = audioContext.createMediaStreamSource(tabMediaStream);
      source.connect(audioContext.destination);

      console.log('[i4ware Offscreen] Tab audio successfully captured and playing.');

      // 3. Initialize Speech Recognition on captured tab audio stream if available
      startRecognitionOnStream(tabMediaStream);
    } catch (err) {
      console.error('[i4ware Offscreen] Error capturing tab audio:', err);
    }
  }

  if (message.type === 'STOP_TAB_AUDIO_STREAM') {
    if (tabMediaStream) {
      tabMediaStream.getTracks().forEach((track) => track.stop());
      tabMediaStream = null;
    }
    if (audioContext) {
      audioContext.close();
      audioContext = null;
    }
    if (speechRecognizer) {
      speechRecognizer.stop();
      speechRecognizer = null;
    }
  }
});

function startRecognitionOnStream(stream) {
  const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
  if (!SpeechRecognition) {
    console.warn('[i4ware Offscreen] Web SpeechRecognition not available in offscreen document.');
    return;
  }

  try {
    speechRecognizer = new SpeechRecognition();
    speechRecognizer.continuous = true;
    speechRecognizer.interimResults = true;
    speechRecognizer.lang = 'fi-FI';

    speechRecognizer.onresult = (event) => {
      let finalTranscript = '';
      for (let i = event.resultIndex; i < event.results.length; ++i) {
        if (event.results[i].isFinal) {
          finalTranscript += event.results[i][0].transcript;
        }
      }

      if (finalTranscript.trim()) {
        chrome.runtime.sendMessage({
          action: 'SEND_TRANSCRIPT',
          text: finalTranscript.trim(),
          speaker: 'remote_participant',
          isFinal: true,
        });
      }
    };

    speechRecognizer.onerror = (e) => {
      console.warn('[i4ware Offscreen] Speech recognizer error:', e);
    };

    speechRecognizer.onend = () => {
      // Restart if stream is still active
      if (tabMediaStream && tabMediaStream.active) {
        try { speechRecognizer.start(); } catch (e) {}
      }
    };

    speechRecognizer.start();
  } catch (err) {
    console.warn('[i4ware Offscreen] Could not start speech recognition on stream:', err);
  }
}
