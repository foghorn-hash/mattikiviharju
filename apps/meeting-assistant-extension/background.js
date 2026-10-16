/**
 * Background Service Worker for i4ware Meeting Assistant Extension (Manifest V3)
 * Manages WebSocket connection to local Windows 11 app on 127.0.0.1:41793
 * Manages offscreen document for tab audio capture
 */

const LOOPBACK_WS_URL = 'ws://127.0.0.1:41793';
const LOOPBACK_HTTP_URL = 'http://127.0.0.1:41793';

let ws = null;
let sessionToken = null;
let isConnectedToDesktop = false;
let activeMeetingTabId = null;
let isListening = false;
let avatarSpeaking = false;

// Connect to Local Windows Desktop App
async function connectToDesktopApp() {
  try {
    // 1. HTTP Handshake to verify desktop app is running and get session token
    const res = await fetch(`${LOOPBACK_HTTP_URL}/api/handshake`);
    if (!res.ok) throw new Error('Desktop app handshake failed');
    const data = await res.json();
    sessionToken = data.sessionToken;

    // 2. Open WebSocket
    if (ws && ws.readyState === WebSocket.OPEN) {
      return true;
    }

    ws = new WebSocket(LOOPBACK_WS_URL);

    ws.onopen = () => {
      isConnectedToDesktop = true;
      console.log('[i4ware Extension] Connected to Windows app via loopback.');
      broadcastToTabs({
        type: 'DESKTOP_CONNECTION_STATUS',
        connected: true,
      });

      // Send initial handshake message
      sendToDesktop({
        type: 'HANDSHAKE',
        token: sessionToken,
      });
    };

    ws.onmessage = (event) => {
      try {
        const msg = JSON.parse(event.data);
        if (msg.type === 'AVATAR_SPEAKING_STATE') {
          avatarSpeaking = !!msg.speaking;
          broadcastToTabs({
            type: 'AVATAR_SPEAKING_STATE',
            speaking: avatarSpeaking,
          });
        }
      } catch (err) {
        console.error('[i4ware Extension] Error parsing desktop message:', err);
      }
    };

    ws.onclose = () => {
      isConnectedToDesktop = false;
      ws = null;
      console.log('[i4ware Extension] Disconnected from Windows app.');
      broadcastToTabs({
        type: 'DESKTOP_CONNECTION_STATUS',
        connected: false,
      });
    };

    ws.onerror = (err) => {
      console.warn('[i4ware Extension] WebSocket error:', err);
      isConnectedToDesktop = false;
    };

    return true;
  } catch (err) {
    console.warn('[i4ware Extension] Could not reach Windows desktop app at 127.0.0.1:41793. Is it running?');
    isConnectedToDesktop = false;
    return false;
  }
}

// Send message to Windows Desktop App via WebSocket
function sendToDesktop(msg) {
  if (ws && ws.readyState === WebSocket.OPEN) {
    ws.send(JSON.stringify(msg));
  }
}

// Broadcast message to content scripts
function broadcastToTabs(msg) {
  chrome.tabs.query({ url: ['https://teams.microsoft.com/*', 'https://teams.live.com/*', 'https://meet.google.com/*'] }, (tabs) => {
    tabs.forEach((tab) => {
      if (tab.id) {
        chrome.tabs.sendMessage(tab.id, msg).catch(() => {});
      }
    });
  });
}

// Ensure offscreen document exists for tab audio capture
async function ensureOffscreenDocument() {
  if (await chrome.offscreen.hasDocument()) {
    return;
  }
  await chrome.offscreen.createDocument({
    url: 'offscreen.html',
    reasons: ['USER_MEDIA'],
    justification: 'Kokousäänen turvallinen käsittely ja transkriptio',
  });
}

// Stop offscreen document
async function closeOffscreenDocument() {
  if (await chrome.offscreen.hasDocument()) {
    await chrome.offscreen.closeDocument();
  }
}

// Listen to messages from content scripts and popup
chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
  if (request.action === 'CHECK_STATUS') {
    sendResponse({
      connected: isConnectedToDesktop,
      isListening: isListening,
      avatarSpeaking: avatarSpeaking,
    });
    return true;
  }

  if (request.action === 'CONNECT_DESKTOP') {
    connectToDesktopApp().then((ok) => {
      sendResponse({ success: ok, connected: isConnectedToDesktop });
    });
    return true;
  }

  if (request.action === 'START_LISTENING') {
    isListening = true;
    activeMeetingTabId = sender?.tab?.id || activeMeetingTabId;

    // Send response right away to update UI
    sendResponse({ success: true, isListening: true });

    connectToDesktopApp().then(async () => {
      sendToDesktop({
        type: 'LISTENING_STATE',
        isListening: true,
        platform: request.platform || 'meet_web',
      });

      // Start tab capture via offscreen if supported
      if (request.captureTabAudio && sender?.tab?.id && chrome.tabCapture?.getMediaStreamId) {
        try {
          await ensureOffscreenDocument();
          chrome.tabCapture.getMediaStreamId({ targetTabId: sender.tab.id }, (streamId) => {
            if (streamId && !chrome.runtime.lastError) {
              chrome.runtime.sendMessage({
                type: 'START_TAB_AUDIO_STREAM',
                target: 'offscreen',
                streamId: streamId,
              }).catch(() => {});
            }
          });
        } catch (e) {
          console.warn('[i4ware Extension] Tab audio capture error:', e);
        }
      }
    }).catch((err) => {
      console.warn('[i4ware Extension] Connect desktop error:', err);
    });
    return true;
  }

  if (request.action === 'STOP_LISTENING') {
    isListening = false;
    sendToDesktop({
      type: 'LISTENING_STATE',
      isListening: false,
    });
    closeOffscreenDocument().catch(() => {});
    sendResponse({ success: true, isListening: false });
    return true;
  }

  if (request.action === 'SEND_TRANSCRIPT') {
    // If avatar is speaking, loopback echo shield prevents echoing avatar's speech
    if (avatarSpeaking) {
      console.log('[i4ware Extension Shield] Ignored transcript while avatar speaking.');
      sendResponse({ dropped: true });
      return true;
    }

    sendToDesktop({
      type: 'TRANSCRIPT',
      text: request.text,
      speaker: request.speaker || 'interviewer',
      isFinal: request.isFinal !== false,
      timestamp: Date.now(),
    });
    sendResponse({ sent: true });
    return true;
  }

  if (request.action === 'MEETING_DETECTED') {
    activeMeetingTabId = sender?.tab?.id;
    sendToDesktop({
      type: 'MEETING_DETECTED',
      platform: request.platform,
      meetingTitle: request.title,
    });
    sendResponse({ acknowledged: true });
    return true;
  }
});

// Auto-clean on tab close
chrome.tabs.onRemoved.addListener((tabId) => {
  if (tabId === activeMeetingTabId) {
    activeMeetingTabId = null;
    isListening = false;
    sendToDesktop({
      type: 'DISCONNECT',
      reason: 'Meeting tab closed',
    });
    closeOffscreenDocument().catch(() => {});
  }
});

// Initial attempt to connect to desktop app
connectToDesktopApp();
