import { contextBridge, ipcRenderer } from 'electron';
import { AppConfig, ExtensionMessage, InterviewPreparation } from './types';

contextBridge.exposeInMainWorld('api', {
  getConfig: (): Promise<AppConfig> => ipcRenderer.invoke('get-config'),
  saveConfig: (cfg: Partial<AppConfig>): Promise<AppConfig> => ipcRenderer.invoke('save-config', cfg),

  pairWithWordPress: (serverUrl: string, code: string) =>
    ipcRenderer.invoke('pair-wordpress', serverUrl, code),

  fetchPreparation: (): Promise<any> => ipcRenderer.invoke('fetch-preparation'),
  savePreparation: (prep: Partial<InterviewPreparation>): Promise<any> =>
    ipcRenderer.invoke('save-preparation', prep),

  suggestAnswer: (question: string, mode: string, history: any[]): Promise<any> =>
    ipcRenderer.invoke('suggest-answer', question, mode, history),

  saveTranscript: (transcripts: any[], explicitConsent: boolean): Promise<any> =>
    ipcRenderer.invoke('save-transcript', transcripts, explicitConsent),

  setAvatarSpeaking: (speaking: boolean) => ipcRenderer.send('set-avatar-speaking', speaking),
  sendAvatarVisemeUpdate: (data: any) => ipcRenderer.send('avatar-viseme-update', data),
  toggleAvatarPopout: () => ipcRenderer.send('toggle-avatar-popout'),

  // Listeners
  onExtensionMessage: (callback: (msg: ExtensionMessage) => void) => {
    ipcRenderer.on('extension-message', (_event, msg) => callback(msg));
  },
  onExtensionConnectionChanged: (callback: (connected: boolean) => void) => {
    ipcRenderer.on('extension-connection-changed', (_event, connected) => callback(connected));
  },
  onAvatarPopoutStatus: (callback: (open: boolean) => void) => {
    ipcRenderer.on('avatar-popout-status', (_event, open) => callback(open));
  },
  onAvatarSpeakingState: (callback: (speaking: boolean) => void) => {
    ipcRenderer.on('avatar-speaking-state', (_event, speaking) => callback(speaking));
  },
  onAvatarVisemeUpdate: (callback: (data: any) => void) => {
    ipcRenderer.on('avatar-viseme-update', (_event, data) => callback(data));
  },

  // Window controls
  minimizeWindow: () => ipcRenderer.send('minimize-window'),
  maximizeWindow: () => ipcRenderer.send('maximize-window'),
  closeWindow: () => ipcRenderer.send('close-window'),
});
