"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const electron_1 = require("electron");
electron_1.contextBridge.exposeInMainWorld('api', {
    getConfig: () => electron_1.ipcRenderer.invoke('get-config'),
    saveConfig: (cfg) => electron_1.ipcRenderer.invoke('save-config', cfg),
    pairWithWordPress: (serverUrl, code) => electron_1.ipcRenderer.invoke('pair-wordpress', serverUrl, code),
    fetchPreparation: () => electron_1.ipcRenderer.invoke('fetch-preparation'),
    savePreparation: (prep) => electron_1.ipcRenderer.invoke('save-preparation', prep),
    suggestAnswer: (question, mode, history) => electron_1.ipcRenderer.invoke('suggest-answer', question, mode, history),
    saveTranscript: (transcripts, explicitConsent) => electron_1.ipcRenderer.invoke('save-transcript', transcripts, explicitConsent),
    setAvatarSpeaking: (speaking) => electron_1.ipcRenderer.send('set-avatar-speaking', speaking),
    sendAvatarVisemeUpdate: (data) => electron_1.ipcRenderer.send('avatar-viseme-update', data),
    toggleAvatarPopout: () => electron_1.ipcRenderer.send('toggle-avatar-popout'),
    // Listeners
    onExtensionMessage: (callback) => {
        electron_1.ipcRenderer.on('extension-message', (_event, msg) => callback(msg));
    },
    onExtensionConnectionChanged: (callback) => {
        electron_1.ipcRenderer.on('extension-connection-changed', (_event, connected) => callback(connected));
    },
    onAvatarPopoutStatus: (callback) => {
        electron_1.ipcRenderer.on('avatar-popout-status', (_event, open) => callback(open));
    },
    onAvatarSpeakingState: (callback) => {
        electron_1.ipcRenderer.on('avatar-speaking-state', (_event, speaking) => callback(speaking));
    },
    onAvatarVisemeUpdate: (callback) => {
        electron_1.ipcRenderer.on('avatar-viseme-update', (_event, data) => callback(data));
    },
    // Window controls
    minimizeWindow: () => electron_1.ipcRenderer.send('minimize-window'),
    maximizeWindow: () => electron_1.ipcRenderer.send('maximize-window'),
    closeWindow: () => electron_1.ipcRenderer.send('close-window'),
});
