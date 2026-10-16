import { app, BrowserWindow, ipcMain } from 'electron';
import * as path from 'path';
import * as fs from 'fs';
import * as http from 'http';
import { WebSocketServer, WebSocket } from 'ws';
import { AppConfig, ExtensionMessage, InterviewPreparation, AISuggestion, DevicePairingResponse } from './types';

let mainWindow: BrowserWindow | null = null;
let avatarPopoutWindow: BrowserWindow | null = null;

// Persistent config path in Electron userData directory
const configPath = path.join(app.getPath('userData'), 'config.json');

function loadConfig(): AppConfig {
  try {
    if (fs.existsSync(configPath)) {
      const data = fs.readFileSync(configPath, 'utf-8');
      return JSON.parse(data);
    }
  } catch (err) {
    console.error('Failed to load config:', err);
  }
  return {
    serverUrl: 'http://localhost/mattikiviharju',
    deviceToken: '',
    audioRecordingEnabled: false,
    loopbackPort: 41793,
  };
}

function saveConfig(cfg: AppConfig): void {
  try {
    fs.writeFileSync(configPath, JSON.stringify(cfg, null, 2), 'utf-8');
  } catch (err) {
    console.error('Failed to save config:', err);
  }
}

let appConfig = loadConfig();

// Generate a random session token for the local loopback server to verify extension
const localSessionToken = Math.random().toString(36).substring(2) + Date.now().toString(36);
let connectedExtensionSocket: WebSocket | null = null;
let isAvatarSpeaking = false;

// Initialize Windows
function createMainWindow() {
  mainWindow = new BrowserWindow({
    width: 1360,
    height: 900,
    minWidth: 1080,
    minHeight: 700,
    title: 'i4ware Job Seekers Autopilot AI - Haastatteluavustaja',
    backgroundColor: '#0f172a',
    webPreferences: {
      preload: path.join(__dirname, 'preload.js'),
      nodeIntegration: false,
      contextIsolation: true,
      sandbox: false,
    },
    autoHideMenuBar: true,
  });

  mainWindow.loadFile(path.join(__dirname, '../src/index.html'));

  mainWindow.on('closed', () => {
    mainWindow = null;
    if (avatarPopoutWindow) {
      avatarPopoutWindow.close();
    }
  });
}

function createAvatarPopoutWindow() {
  if (avatarPopoutWindow) {
    avatarPopoutWindow.focus();
    return;
  }

  avatarPopoutWindow = new BrowserWindow({
    width: 640,
    height: 480,
    title: 'LiveAvatar - Jaettava esikatselu (Ei sisällä yksityisiä ohjeita)',
    backgroundColor: '#0a0f1d',
    webPreferences: {
      preload: path.join(__dirname, 'preload.js'),
      nodeIntegration: false,
      contextIsolation: true,
    },
    autoHideMenuBar: true,
  });

  avatarPopoutWindow.loadFile(path.join(__dirname, '../src/avatar-popout.html'));

  avatarPopoutWindow.on('closed', () => {
    avatarPopoutWindow = null;
    if (mainWindow) {
      mainWindow.webContents.send('avatar-popout-status', false);
    }
  });

  if (mainWindow) {
    mainWindow.webContents.send('avatar-popout-status', true);
  }
}

// Start Secure Loopback Server (Bound ONLY to 127.0.0.1)
function startLoopbackServer(port: number) {
  const server = http.createServer((req, res) => {
    // CORS restricted strictly to loopback and extensions
    const origin = req.headers.origin || '';
    if (
      origin.startsWith('chrome-extension://') ||
      origin.startsWith('edge-extension://') ||
      origin.startsWith('moz-extension://') ||
      origin === 'http://localhost' ||
      origin === 'http://127.0.0.1'
    ) {
      res.setHeader('Access-Control-Allow-Origin', origin);
      res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
      res.setHeader('Access-Control-Allow-Headers', 'Content-Type, X-Session-Token');
    }

    if (req.method === 'OPTIONS') {
      res.writeHead(204);
      res.end();
      return;
    }

    // Health and pairing handshake for browser extension
    if (req.url === '/api/handshake') {
      res.writeHead(200, { 'Content-Type': 'application/json' });
      res.end(
        JSON.stringify({
          status: 'ready',
          sessionToken: localSessionToken,
          serverUrl: appConfig.serverUrl,
          paired: !!appConfig.deviceToken,
          app: 'i4ware-interview-assistant',
        })
      );
      return;
    }

    res.writeHead(404);
    res.end();
  });

  const wss = new WebSocketServer({ server });

  wss.on('connection', (ws: WebSocket, req) => {
    // Origin check
    const origin = req.headers.origin || '';
    const isExtension =
      origin.startsWith('chrome-extension://') ||
      origin.startsWith('edge-extension://') ||
      origin.startsWith('moz-extension://') ||
      origin === 'http://localhost' ||
      origin === 'http://127.0.0.1';

    if (!isExtension) {
      console.warn('Rejected connection from untrusted origin:', origin);
      ws.close(1008, 'Untrusted origin');
      return;
    }

    connectedExtensionSocket = ws;
    console.log('Browser extension connected via local loopback WebSocket.');

    if (mainWindow) {
      mainWindow.webContents.send('extension-connection-changed', true);
    }

    ws.on('message', (data: Buffer | string) => {
      try {
        const msg: ExtensionMessage = JSON.parse(data.toString());

        // Shield: When avatar is speaking, drop or ignore loopback audio from microphone/speaker
        // so the avatar's own speech does NOT get transcribed as a new interviewer question!
        if (isAvatarSpeaking && msg.type === 'TRANSCRIPT') {
          console.log('[Loopback Shield] Dropped transcript during avatar speech to prevent echo loop.');
          return;
        }

        if (mainWindow) {
          mainWindow.webContents.send('extension-message', msg);
        }
      } catch (err) {
        console.error('Error parsing extension message:', err);
      }
    });

    ws.on('close', () => {
      if (connectedExtensionSocket === ws) {
        connectedExtensionSocket = null;
      }
      if (mainWindow) {
        mainWindow.webContents.send('extension-connection-changed', false);
      }
    });
  });

  server.listen(port, '127.0.0.1', () => {
    console.log(`Interview Assistant loopback server listening on 127.0.0.1:${port}`);
  });

  server.on('error', (err) => {
    console.error('Loopback server error:', err);
  });
}

// Setup IPC Handlers
function setupIpcHandlers() {
  ipcMain.handle('get-config', () => appConfig);

  ipcMain.handle('save-config', (_event, cfg: Partial<AppConfig>) => {
    appConfig = { ...appConfig, ...cfg };
    saveConfig(appConfig);
    return appConfig;
  });

  // Pairing with WordPress REST API
  ipcMain.handle('pair-wordpress', async (_event, serverUrl: string, pairingCode: string) => {
    try {
      const cleanUrl = serverUrl.replace(/\/+$/, '');
      const endpoint = `${cleanUrl}/wp-json/ai-cv-tailor/v1/interview/pair`;

      const res = await fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          pairing_code: pairingCode,
          device_name: 'Windows 11 Desktop (' + process.env.USERNAME + ')',
        }),
      });

      const data: DevicePairingResponse = await res.json();
      if (data.success && data.token) {
        appConfig.serverUrl = cleanUrl;
        appConfig.deviceToken = data.token;
        if (data.user) {
          appConfig.pairedUser = {
            id: data.user.id,
            name: data.user.display_name,
            email: data.user.email,
          };
        }
        saveConfig(appConfig);
      }
      return data;
    } catch (err: any) {
      return { success: false, message: 'Yhteysvirhe WordPressiin: ' + (err.message || String(err)) };
    }
  });

  // Fetch Interview Preparation from WordPress
  ipcMain.handle('fetch-preparation', async () => {
    if (!appConfig.deviceToken) {
      return { success: false, message: 'Laite ei ole paritettu WordPressiin.' };
    }

    try {
      const endpoint = `${appConfig.serverUrl}/wp-json/ai-cv-tailor/v1/interview/preparation`;
      const res = await fetch(endpoint, {
        headers: {
          Authorization: `Bearer ${appConfig.deviceToken}`,
        },
      });
      return await res.json();
    } catch (err: any) {
      return { success: false, message: err.message || String(err) };
    }
  });

  // Save Interview Preparation to WordPress
  ipcMain.handle('save-preparation', async (_event, prep: Partial<InterviewPreparation>) => {
    if (!appConfig.deviceToken) {
      return { success: false, message: 'Laite ei ole paritettu.' };
    }

    try {
      const endpoint = `${appConfig.serverUrl}/wp-json/ai-cv-tailor/v1/interview/preparation`;
      const res = await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${appConfig.deviceToken}`,
        },
        body: JSON.stringify(prep),
      });
      return await res.json();
    } catch (err: any) {
      return { success: false, message: err.message || String(err) };
    }
  });

  // Request AI Answer Suggestion
  ipcMain.handle('suggest-answer', async (_event, question: string, mode: string, history: any[]) => {
    if (!appConfig.deviceToken) {
      return { success: false, message: 'Laite ei ole paritettu.' };
    }

    try {
      const endpoint = `${appConfig.serverUrl}/wp-json/ai-cv-tailor/v1/interview/suggest-answer`;
      const res = await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${appConfig.deviceToken}`,
        },
        body: JSON.stringify({ question, mode, history }),
      });
      return await res.json();
    } catch (err: any) {
      return { success: false, message: err.message || String(err) };
    }
  });

  // Save Transcript (Explicit opt-in only)
  ipcMain.handle('save-transcript', async (_event, transcripts: any[], explicitConsent: boolean) => {
    if (!appConfig.deviceToken) {
      return { success: false, message: 'Laite ei ole paritettu.' };
    }

    try {
      const endpoint = `${appConfig.serverUrl}/wp-json/ai-cv-tailor/v1/interview/save-transcript`;
      const res = await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${appConfig.deviceToken}`,
        },
        body: JSON.stringify({
          transcripts,
          explicit_consent: explicitConsent,
        }),
      });
      return await res.json();
    } catch (err: any) {
      return { success: false, message: err.message || String(err) };
    }
  });

  // Relay speech state to prevent loopback echo
  ipcMain.on('set-avatar-speaking', (_event, speaking: boolean) => {
    isAvatarSpeaking = speaking;
    // Also notify extension if connected
    if (connectedExtensionSocket && connectedExtensionSocket.readyState === WebSocket.OPEN) {
      connectedExtensionSocket.send(
        JSON.stringify({
          type: 'AVATAR_SPEAKING_STATE',
          speaking: isAvatarSpeaking,
        })
      );
    }
    // Also forward to popout window if open
    if (avatarPopoutWindow) {
      avatarPopoutWindow.webContents.send('avatar-speaking-state', speaking);
    }
  });

  // Sync avatar animation data to popout window
  ipcMain.on('avatar-viseme-update', (_event, visemeData: any) => {
    if (avatarPopoutWindow) {
      avatarPopoutWindow.webContents.send('avatar-viseme-update', visemeData);
    }
  });

  // Toggle popout avatar window
  ipcMain.on('toggle-avatar-popout', () => {
    if (avatarPopoutWindow) {
      avatarPopoutWindow.close();
    } else {
      createAvatarPopoutWindow();
    }
  });

  // Window controls
  ipcMain.on('minimize-window', () => {
    mainWindow?.minimize();
  });
  ipcMain.on('maximize-window', () => {
    if (mainWindow?.isMaximized()) {
      mainWindow.unmaximize();
    } else {
      mainWindow?.maximize();
    }
  });
  ipcMain.on('close-window', () => {
    mainWindow?.close();
  });
}

// App lifecycle
app.whenReady().then(() => {
  setupIpcHandlers();
  startLoopbackServer(appConfig.loopbackPort || 41793);
  createMainWindow();

  app.on('activate', () => {
    if (BrowserWindow.getAllWindows().length === 0) {
      createMainWindow();
    }
  });

  if (process.argv.includes('--test-mode')) {
    console.log('[TEST SUCCESS] Electron app initialized cleanly and loopback server started!');
    setTimeout(() => {
      app.quit();
    }, 2500);
  }
});

app.on('window-all-closed', () => {
  if (process.platform !== 'darwin') {
    app.quit();
  }
});
