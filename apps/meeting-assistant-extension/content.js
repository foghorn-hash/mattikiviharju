/**
 * Content Script for i4ware Meeting Assistant Extension
 * Injected on Google Meet and Microsoft Teams web meetings.
 * Displays unobtrusive floating control widget, monitors captions/audio,
 * and passes live transcripts to the Windows desktop app.
 */

(function () {
  // Avoid duplicate injection
  if (window.__i4wareAssistantInjected) return;
  window.__i4wareAssistantInjected = true;

  const isMeet = window.location.hostname.includes('meet.google.com');
  const isTeams = window.location.hostname.includes('teams.microsoft.com') || window.location.hostname.includes('teams.live.com');
  const platform = isMeet ? 'meet_web' : 'teams_web';

  let isConnectedToDesktop = false;
  let isListening = false;
  let captionObserver = null;
  let localRecognizer = null;

  // Notify background of meeting detection
  chrome.runtime.sendMessage({
    action: 'MEETING_DETECTED',
    platform: platform,
    title: document.title,
  });

  // Create and inject the floating widget
  function injectWidget() {
    const existing = document.getElementById('i4ware-meeting-widget');
    if (existing) return;

    const widget = document.createElement('div');
    widget.id = 'i4ware-meeting-widget';
    widget.innerHTML = `
      <div class="widget-header">
        <div class="widget-brand">
          <div class="widget-icon">AI</div>
          <span>Haastatteluavustaja</span>
        </div>
        <div class="widget-controls">
          <button type="button" class="widget-btn-icon" id="i4ware-btn-minimize" title="Pienennä">−</button>
        </div>
      </div>
      <div class="widget-body">
        <div class="widget-status-row">
          <span style="color:#94a3b8; font-size:11px;">ALUSTA: <strong>${isMeet ? 'Google Meet' : 'MS Teams'}</strong></span>
          <div class="widget-badge" id="i4ware-widget-status">
            <span class="widget-dot" id="i4ware-widget-dot"></span>
            <span id="i4ware-widget-status-text">Odottaa</span>
          </div>
        </div>

        <button type="button" class="widget-btn" id="i4ware-btn-toggle-listen">
          <span>▶</span> Aloita kokousäänen kuuntelu
        </button>

        <div class="widget-preview-box" id="i4ware-preview-box">
          Kuuntelu on pysäytetty. Paina nappia aloittaaksesi haastattelukysymysten automaattisen litteroinnin.
        </div>
      </div>
    `;

    document.body.appendChild(widget);

    // Minimize toggle
    const btnMinimize = document.getElementById('i4ware-btn-minimize');
    btnMinimize.addEventListener('click', () => {
      widget.classList.toggle('minimized');
      btnMinimize.textContent = widget.classList.contains('minimized') ? '+' : '−';
    });

    // Listen button toggle
    const btnToggle = document.getElementById('i4ware-btn-toggle-listen');
    btnToggle.addEventListener('click', () => {
      if (isListening) {
        stopListening();
      } else {
        startListening();
      }
    });

    // Initial status check
    chrome.runtime.sendMessage({ action: 'CHECK_STATUS' }, (res) => {
      if (res) {
        updateConnectionStatus(res.connected);
        if (res.isListening) {
          setListeningUI(true);
        }
      }
    });
  }

  function updateConnectionStatus(connected) {
    isConnectedToDesktop = connected;
    const dot = document.getElementById('i4ware-widget-dot');
    const text = document.getElementById('i4ware-widget-status-text');
    if (!dot || !text) return;

    if (connected) {
      dot.className = 'widget-dot connected';
      text.textContent = 'Yhdistetty appiin';
    } else {
      dot.className = 'widget-dot';
      text.textContent = 'Ei yhteyttä appiin';
    }
  }

  function setListeningUI(listening) {
    isListening = listening;
    const btn = document.getElementById('i4ware-btn-toggle-listen');
    const dot = document.getElementById('i4ware-widget-dot');
    const preview = document.getElementById('i4ware-preview-box');

    if (!btn) return;

    if (listening) {
      btn.className = 'widget-btn active';
      btn.innerHTML = '<span>⏹</span> Pysäytä kuuntelu';
      if (dot) dot.className = 'widget-dot listening';
      if (preview) preview.textContent = 'Kuunnellaan kokousta aktiivisesti...';
    } else {
      btn.className = 'widget-btn';
      btn.innerHTML = '<span>▶</span> Aloita kokousäänen kuuntelu';
      if (dot) dot.className = isConnectedToDesktop ? 'widget-dot connected' : 'widget-dot';
      if (preview) preview.textContent = 'Kuuntelu pysäytetty.';
    }
  }

  // Start Listening
  function startListening() {
    chrome.runtime.sendMessage(
      {
        action: 'START_LISTENING',
        platform: platform,
        captureTabAudio: true,
      },
      (res) => {
        if (res && res.success) {
          setListeningUI(true);
          setupCaptionsObserver();
          setupFallbackSpeechRecognition();
        }
      }
    );
  }

  // Stop Listening
  function stopListening() {
    chrome.runtime.sendMessage({ action: 'STOP_LISTENING' }, (res) => {
      setListeningUI(false);
      if (captionObserver) {
        captionObserver.disconnect();
        captionObserver = null;
      }
      if (localRecognizer) {
        localRecognizer.stop();
        localRecognizer = null;
      }
    });
  }

  // Setup DOM Captions Observer for Google Meet and Teams
  // (Both platforms render ultra-accurate live captions into DOM when toggled)
  function setupCaptionsObserver() {
    if (captionObserver) return;

    let lastSentText = '';

    captionObserver = new MutationObserver(() => {
      let extractedText = '';

      if (isMeet) {
        // Meet caption selectors
        const captionNodes = document.querySelectorAll('div[jsname="YSvy7"], .iTTPOb, .nMxTrf');
        if (captionNodes.length > 0) {
          const lastNode = captionNodes[captionNodes.length - 1];
          extractedText = lastNode.textContent || '';
        }
      } else if (isTeams) {
        // Teams caption selectors
        const teamsCaptions = document.querySelectorAll('div[data-tid="closed-captions-renderer"], .ui-chat__item');
        if (teamsCaptions.length > 0) {
          const lastNode = teamsCaptions[teamsCaptions.length - 1];
          extractedText = lastNode.textContent || '';
        }
      }

      extractedText = extractedText.trim();
      if (extractedText && extractedText !== lastSentText && extractedText.length > 5) {
        lastSentText = extractedText;
        const preview = document.getElementById('i4ware-preview-box');
        if (preview) preview.textContent = extractedText;

        // Send to background -> Desktop Windows app
        chrome.runtime.sendMessage({
          action: 'SEND_TRANSCRIPT',
          text: extractedText,
          speaker: 'interviewer',
          isFinal: true,
        });
      }
    });

    captionObserver.observe(document.body, {
      childList: true,
      subtree: true,
      characterData: true,
    });
  }

  // Fallback direct Web Speech Recognition
  function setupFallbackSpeechRecognition() {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRecognition) return;

    try {
      localRecognizer = new SpeechRecognition();
      localRecognizer.continuous = true;
      localRecognizer.interimResults = true;
      localRecognizer.lang = 'fi-FI';

      localRecognizer.onresult = (event) => {
        let finalStr = '';
        for (let i = event.resultIndex; i < event.results.length; ++i) {
          if (event.results[i].isFinal) {
            finalStr += event.results[i][0].transcript;
          }
        }
        if (finalStr.trim()) {
          const preview = document.getElementById('i4ware-preview-box');
          if (preview) preview.textContent = finalStr.trim();

          chrome.runtime.sendMessage({
            action: 'SEND_TRANSCRIPT',
            text: finalStr.trim(),
            speaker: 'interviewer',
            isFinal: true,
          });
        }
      };

      localRecognizer.onerror = () => {};
      localRecognizer.onend = () => {
        if (isListening) {
          try { localRecognizer.start(); } catch (e) {}
        }
      };

      localRecognizer.start();
    } catch (e) {
      console.warn('[i4ware] Direct speech recognition fallback not started:', e);
    }
  }

  // Listen to messages from background
  chrome.runtime.onMessage.addListener((msg) => {
    if (msg.type === 'DESKTOP_CONNECTION_STATUS') {
      updateConnectionStatus(msg.connected);
    }
  });

  // Inject widget after page loads
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', injectWidget);
  } else {
    injectWidget();
  }
})();
