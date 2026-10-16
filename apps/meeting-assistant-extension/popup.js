/**
 * Popup Script for Meeting Assistant Extension
 */

document.addEventListener('DOMContentLoaded', () => {
  const appBadge = document.getElementById('popup-app-badge');
  const appDot = document.getElementById('popup-app-dot');
  const appText = document.getElementById('popup-app-text');

  const listenDot = document.getElementById('popup-listen-dot');
  const listenText = document.getElementById('popup-listen-text');

  const btnConnect = document.getElementById('btn-popup-connect');
  const btnListen = document.getElementById('btn-popup-listen');

  let isListening = false;

  function refreshStatus() {
    chrome.runtime.sendMessage({ action: 'CHECK_STATUS' }, (res) => {
      if (chrome.runtime.lastError || !res) {
        appDot.className = 'dot';
        appText.textContent = 'Ei yhteyttä';
        return;
      }

      if (res.connected) {
        appDot.className = 'dot online';
        appText.textContent = 'Yhdistetty';
      } else {
        appDot.className = 'dot';
        appText.textContent = 'Ei yhteyttä';
      }

      isListening = !!res.isListening;
      if (isListening) {
        listenDot.className = 'dot active';
        listenText.textContent = 'Aktiivinen';
        btnListen.className = 'btn btn-danger';
        btnListen.textContent = '⏹ Pysäytä kuuntelu';
      } else {
        listenDot.className = 'dot';
        listenText.textContent = 'Pois käytöstä';
        btnListen.className = 'btn btn-secondary';
        btnListen.textContent = '▶ Aloita kuuntelu';
      }
    });
  }

  btnConnect.addEventListener('click', () => {
    btnConnect.textContent = 'Yhdistetään...';
    chrome.runtime.sendMessage({ action: 'CONNECT_DESKTOP' }, (res) => {
      btnConnect.textContent = '🔄 Yhdistä Windows-sovellukseen';
      refreshStatus();
    });
  });

  btnListen.addEventListener('click', () => {
    if (isListening) {
      chrome.runtime.sendMessage({ action: 'STOP_LISTENING' }, () => {
        refreshStatus();
      });
    } else {
      chrome.runtime.sendMessage({ action: 'START_LISTENING' }, () => {
        refreshStatus();
      });
    }
  });

  refreshStatus();
});
