/**
 * Gemini AI Avatar Assistant Widget
 * Handles interactive avatar animations, Web Audio API amplitude analysis,
 * speech synthesis playback, REST API communication, human-in-the-loop interview approval,
 * audio routing controls, and Popout window synchronization for Teams, Meet, and Isadora 3.
 *
 * @package Gemini_Avatar_Assistant
 */

/* global geminiAvatarConfig */
(function () {
	'use strict';

	if (typeof geminiAvatarConfig === 'undefined') {
		return;
	}

	const config = geminiAvatarConfig;
	let currentLang = config.currentLang || 'fi';
	let i18n = (config.languages && config.languages[currentLang]) ? config.languages[currentLang] : config.i18n;

	// Mode and Style State
	let currentMode = 'interview'; // 'interview' or 'assistant'
	let currentStyle = 'conversational'; // 'concise', 'conversational', 'technical'
	let speechSpeed = 1.0; // 0.85, 1.0, 1.2, 1.4

	// Elements
	let widgetEl, launcherEl, drawerEl, messagesEl, inputEl, sendBtnEl;
	let soundBtnEl, resetBtnEl, closeBtnEl, popoutBtnEl, micBtnEl, micStatusEl, micCancelEl;
	let starterChipsEl, charCountEl, headerStatusDot, headerStateText, launcherStatus;
	let portalOuterRing, mouthOverlay, vectorMouth, audioWavesEl;
	let stopSpeechStageBtn, pauseSpeechStageBtn, pauseStageText;

	// Interview Toolbar Elements
	let modeInterviewBtn, modeAssistantBtn, interviewSubtoolbar;
	let stylePills, speedPills;

	// Preview Card Elements
	let previewCardEl, previewTextareaEl, previewDurationEl, previewCharCountEl;
	let btnApproveSpeak, btnPauseToggle, btnReplayPreview, btnEmergencyStop, btnDismissPreview;
	let pauseBtnText, speechActivityBadge, aiStatusText;

	// Audio & Web Audio API state
	let audioCtx = null;
	let currentAudioSource = null;
	let currentAudioBuffer = null;
	let analyserNode = null;
	let animFrameId = null;
	let isPlayingAudio = false;
	let isAudioPaused = false;
	let lastApprovedSpeechText = '';

	// BroadcastChannel for Popout window synchronization
	let syncChannel = null;
	try {
		if (typeof BroadcastChannel !== 'undefined') {
			syncChannel = new BroadcastChannel('gemini_avatar_sync');
		}
	} catch (e) {
		console.warn('[Gemini Avatar] BroadcastChannel not supported:', e);
	}

	function broadcastAvatarState(data) {
		if (syncChannel) {
			try {
				syncChannel.postMessage(data);
			} catch (e) {}
		}
	}

	// Speech recognition state
	let speechRecognizer = null;
	let isRecordingVoice = false;

	// Widget state
	let isOpen = false;
	let isGenerating = false;
	let soundEnabled = config.enableSpeech;
	let conversationHistory = [];

	// Load sound & mode preferences from session
	try {
		const storedSound = sessionStorage.getItem('gemini_avatar_sound');
		if (storedSound !== null) {
			soundEnabled = storedSound === '1';
		}
		const storedMode = sessionStorage.getItem('gemini_avatar_mode');
		if (storedMode && (storedMode === 'interview' || storedMode === 'assistant')) {
			currentMode = storedMode;
		}
	} catch (e) {
		// Ignore storage errors
	}

	/**
	 * Initialize DOM elements and event listeners
	 */
	function init() {
		widgetEl       = document.getElementById('gemini-avatar-widget');
		launcherEl     = document.getElementById('gemini-avatar-launcher');
		drawerEl       = document.getElementById('gemini-avatar-drawer');
		messagesEl     = document.getElementById('gemini-messages-list');
		inputEl        = document.getElementById('gemini-chat-input');
		sendBtnEl      = document.getElementById('gemini-btn-send');
		soundBtnEl     = document.getElementById('gemini-btn-sound');
		resetBtnEl     = document.getElementById('gemini-btn-reset');
		closeBtnEl     = document.getElementById('gemini-btn-close');
		popoutBtnEl    = document.getElementById('gemini-btn-popout');
		micBtnEl       = document.getElementById('gemini-btn-mic');
		micStatusEl    = document.getElementById('gemini-mic-status');
		micCancelEl    = document.getElementById('gemini-mic-cancel');
		starterChipsEl = document.getElementById('gemini-starter-chips');
		charCountEl    = document.getElementById('gemini-char-count');

		headerStatusDot   = document.getElementById('gemini-header-status-dot');
		headerStateText   = document.getElementById('gemini-header-state-text');
		launcherStatus    = document.getElementById('gemini-launcher-status');
		portalOuterRing   = document.getElementById('gemini-portal-ring-outer');
		mouthOverlay      = document.getElementById('gemini-mouth-overlay');
		vectorMouth       = document.getElementById('gemini-vector-mouth');
		audioWavesEl      = document.getElementById('gemini-audio-waves');
		stopSpeechStageBtn= document.getElementById('gemini-stage-stop-btn');
		pauseSpeechStageBtn = document.getElementById('gemini-stage-pause-btn');
		pauseStageText    = document.getElementById('gemini-stage-pause-text');

		// Mode and toolbar elements
		modeInterviewBtn   = document.getElementById('gemini-mode-interview');
		modeAssistantBtn   = document.getElementById('gemini-mode-assistant');
		interviewSubtoolbar= document.getElementById('gemini-interview-subtoolbar');
		stylePills         = document.querySelectorAll('.gemini-style-pill');
		speedPills         = document.querySelectorAll('.gemini-speed-pill');

		// Preview card elements
		previewCardEl      = document.getElementById('gemini-preview-card');
		previewTextareaEl  = document.getElementById('gemini-preview-textarea');
		previewDurationEl  = document.getElementById('gemini-preview-duration');
		previewCharCountEl = document.getElementById('gemini-preview-char-count');
		btnApproveSpeak    = document.getElementById('gemini-btn-approve-speak');
		btnPauseToggle     = document.getElementById('gemini-btn-pause-toggle');
		btnReplayPreview   = document.getElementById('gemini-btn-replay-preview');
		btnEmergencyStop   = document.getElementById('gemini-btn-emergency-stop');
		btnDismissPreview  = document.getElementById('gemini-btn-dismiss-preview');
		pauseBtnText       = document.getElementById('gemini-pause-btn-text');
		speechActivityBadge= document.getElementById('gemini-speech-activity-badge');
		aiStatusText       = document.getElementById('gemini-ai-status-text');

		if (!widgetEl || !launcherEl || !drawerEl) {
			return;
		}

		// Update sound toggle UI
		updateSoundUI();
		updateModeUI();

		// Bind events
		launcherEl.addEventListener('click', openDrawer);
		closeBtnEl.addEventListener('click', closeDrawer);
		soundBtnEl.addEventListener('click', toggleSound);
		resetBtnEl.addEventListener('click', resetConversation);
		if (popoutBtnEl) {
			popoutBtnEl.addEventListener('click', openPopoutAvatar);
		}
		if (stopSpeechStageBtn) {
			stopSpeechStageBtn.addEventListener('click', stopAudio);
		}
		if (pauseSpeechStageBtn) {
			pauseSpeechStageBtn.addEventListener('click', togglePauseAudio);
		}

		// Mode buttons
		if (modeInterviewBtn) {
			modeInterviewBtn.addEventListener('click', () => setMode('interview'));
		}
		if (modeAssistantBtn) {
			modeAssistantBtn.addEventListener('click', () => setMode('assistant'));
		}

		// Style pills
		stylePills.forEach(pill => {
			pill.addEventListener('click', function () {
				stylePills.forEach(p => p.classList.remove('active'));
				pill.classList.add('active');
				currentStyle = pill.getAttribute('data-style') || 'conversational';
			});
		});

		// Speed pills
		speedPills.forEach(pill => {
			pill.addEventListener('click', function () {
				speedPills.forEach(p => p.classList.remove('active'));
				pill.classList.add('active');
				speechSpeed = parseFloat(pill.getAttribute('data-speed') || '1.0');
				if (currentAudioSource && audioCtx && audioCtx.state === 'running') {
					try {
						currentAudioSource.playbackRate.value = speechSpeed;
					} catch (e) {}
				}
			});
		});

		// Preview card events
		if (btnApproveSpeak) {
			btnApproveSpeak.addEventListener('click', onApproveAndSpeak);
		}
		if (btnPauseToggle) {
			btnPauseToggle.addEventListener('click', togglePauseAudio);
		}
		if (btnReplayPreview) {
			btnReplayPreview.addEventListener('click', onReplayCurrent);
		}
		if (btnEmergencyStop) {
			btnEmergencyStop.addEventListener('click', stopAudio);
		}
		if (btnDismissPreview) {
			btnDismissPreview.addEventListener('click', dismissPreview);
		}
		if (previewTextareaEl) {
			previewTextareaEl.addEventListener('input', onPreviewTextareaChange);
		}

		// Chat form submit
		const form = document.getElementById('gemini-chat-form');
		if (form) {
			form.addEventListener('submit', onFormSubmit);
		}

		// Input keypress (Enter to send, Shift+Enter for newline)
		if (inputEl) {
			inputEl.addEventListener('keydown', function (e) {
				if (e.key === 'Enter' && !e.shiftKey) {
					e.preventDefault();
					onFormSubmit(e);
				}
			});
			inputEl.addEventListener('input', onInputChange);
		}

		// Voice input
		if (micBtnEl) {
			micBtnEl.addEventListener('click', toggleVoiceInput);
		}
		if (micCancelEl) {
			micCancelEl.addEventListener('click', stopVoiceInput);
		}

		// Keyboard navigation (ESC to close)
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && isOpen) {
				closeDrawer();
			}
		});

		// Language switcher buttons
		const langPills = document.querySelectorAll('.gemini-lang-pill');
		langPills.forEach(function (pill) {
			pill.addEventListener('click', function (e) {
				e.preventDefault();
				const selected = pill.getAttribute('data-lang');
				if (selected && selected !== currentLang) {
					setLanguage(selected);
				}
			});
		});

		// Render initial greeting and starter questions
		renderInitialState();

		// Vector avatar blink timer if SVG mode
		if (config.avatarType === 'svg') {
			initVectorAvatarBlink();
		}
	}

	/**
	 * Open Popout avatar window (Broadcast-ready for Teams/Meet/OBS/Isadora 3)
	 */
	function openPopoutAvatar() {
		const popoutUrl = config.popoutUrl || (config.homeUrl + '?gemini_popout=1');
		window.open(
			popoutUrl,
			'GeminiAvatarPopout',
			'width=780,height=560,menubar=no,toolbar=no,location=no,status=no,resizable=yes'
		);
	}

	/**
	 * Mode switching: 'interview' (1st person job interview) or 'assistant' (3rd person)
	 */
	function setMode(mode) {
		currentMode = mode;
		try {
			sessionStorage.setItem('gemini_avatar_mode', mode);
		} catch (e) {}

		updateModeUI();

		// Update starter chips according to mode
		renderStarterChips();
	}

	function updateModeUI() {
		if (modeInterviewBtn && modeAssistantBtn) {
			modeInterviewBtn.classList.toggle('active', currentMode === 'interview');
			modeAssistantBtn.classList.toggle('active', currentMode === 'assistant');
		}
		if (interviewSubtoolbar) {
			interviewSubtoolbar.style.display = (currentMode === 'interview') ? 'flex' : 'none';
		}
		if (inputEl) {
			inputEl.placeholder = (currentMode === 'interview')
				? (currentLang === 'sv' ? 'Ange intervjuarens fråga eller tala i mikrofonen...' : (currentLang === 'en' ? 'Enter interviewer question or speak into mic...' : 'Syötä haastattelijan kysymys tai puhu mikrofoniin...'))
				: i18n.inputPlaceholder;
		}
	}

	/**
	 * Open the chat drawer
	 */
	function openDrawer() {
		isOpen = true;
		widgetEl.classList.add('is-open');
		drawerEl.removeAttribute('hidden');
		launcherEl.setAttribute('aria-expanded', 'true');

		// Warm up Web Audio context on user interaction
		ensureAudioContext();

		// Focus input
		if (inputEl) {
			setTimeout(() => inputEl.focus(), 150);
		}
		scrollToBottom();
	}

	/**
	 * Close the chat drawer
	 */
	function closeDrawer() {
		isOpen = false;
		stopAudio();
		stopVoiceInput();
		widgetEl.classList.remove('is-open');
		drawerEl.setAttribute('hidden', '');
		launcherEl.setAttribute('aria-expanded', 'false');
		launcherEl.focus();
	}

	/**
	 * Toggle speech synthesis on / off
	 */
	function toggleSound() {
		soundEnabled = !soundEnabled;
		try {
			sessionStorage.setItem('gemini_avatar_sound', soundEnabled ? '1' : '0');
		} catch (e) {}

		updateSoundUI();
		if (!soundEnabled) {
			stopAudio();
		}
	}

	function updateSoundUI() {
		if (!soundBtnEl) return;
		if (soundEnabled) {
			soundBtnEl.classList.add('sound-on');
			soundBtnEl.classList.remove('sound-off');
			soundBtnEl.title = i18n.soundOn + ' (klikkaa mykistääksesi)';
			soundBtnEl.setAttribute('aria-label', i18n.soundOn);
		} else {
			soundBtnEl.classList.add('sound-off');
			soundBtnEl.classList.remove('sound-on');
			soundBtnEl.title = i18n.soundOff + ' (klikkaa kytkeäksesi puhe päälle)';
			soundBtnEl.setAttribute('aria-label', i18n.soundOff);
		}
	}

	/**
	 * Reset conversation to initial state
	 */
	function resetConversation() {
		stopAudio();
		stopVoiceInput();
		dismissPreview();
		conversationHistory = [];
		messagesEl.innerHTML = '';
		renderInitialState();
		setStatus('ready');
		if (inputEl) {
			inputEl.value = '';
			onInputChange();
			inputEl.focus();
		}
	}

	/**
	 * Set avatar and widget status: ready | thinking | speaking | error
	 */
	function setStatus(state) {
		const portal = document.getElementById('gemini-photo-avatar-portal');
		const stage = document.getElementById('gemini-stage-visualizer');
		const speechCtrl = document.getElementById('gemini-stage-speech-ctrl');

		if (launcherStatus) {
			launcherStatus.className = 'gemini-status-indicator status-' + state;
		}
		if (headerStatusDot) {
			headerStatusDot.className = 'gemini-status-dot status-' + state;
		}
		if (portal) {
			portal.classList.toggle('is-speaking', state === 'speaking');
			portal.classList.toggle('is-thinking', state === 'thinking');
		}
		if (stage) {
			stage.classList.toggle('is-speaking', state === 'speaking');
			stage.classList.toggle('is-thinking', state === 'thinking');
		}

		if (speechCtrl) {
			speechCtrl.style.display = (state === 'speaking' || isAudioPaused) ? 'flex' : 'none';
		}
		if (speechActivityBadge) {
			speechActivityBadge.style.display = (state === 'speaking') ? 'inline-flex' : 'none';
		}

		if (headerStateText) {
			switch (state) {
				case 'thinking':
					headerStateText.textContent = i18n.thinking;
					headerStateText.style.color = 'var(--gemini-accent)';
					break;
				case 'speaking':
					headerStateText.textContent = i18n.speaking;
					headerStateText.style.color = 'var(--gemini-pink)';
					break;
				case 'error':
					headerStateText.textContent = i18n.error;
					headerStateText.style.color = 'var(--gemini-red)';
					break;
				default:
					headerStateText.textContent = i18n.ready;
					headerStateText.style.color = 'var(--gemini-accent)';
					break;
			}
		}

		if (aiStatusText) {
			switch (state) {
				case 'thinking':
					aiStatusText.textContent = i18n.thinking;
					break;
				case 'speaking':
					aiStatusText.textContent = (currentLang === 'sv') ? 'Puhuu kokoukseen' : ((currentLang === 'en') ? 'Speaking to meeting' : 'Puhuu kokoukseen');
					break;
				case 'error':
					aiStatusText.textContent = 'API-virhe';
					break;
				default:
					aiStatusText.textContent = 'Gemini AI valmiina';
					break;
			}
		}
	}

	/**
	 * Switch active language (fi, en, sv)
	 */
	function setLanguage(newLang) {
		if (!config.languages || !config.languages[newLang]) return;
		currentLang = newLang;
		i18n = config.languages[currentLang];

		// Update pills
		document.querySelectorAll('.gemini-lang-pill').forEach(function (pill) {
			pill.classList.toggle('active', pill.getAttribute('data-lang') === currentLang);
		});

		// Update launcher tooltip
		const launcherTooltip = document.getElementById('gemini-launcher-tooltip-text');
		if (launcherTooltip) {
			if (currentLang === 'sv') launcherTooltip.textContent = 'Intervju- & AI-assistent';
			else if (currentLang === 'en') launcherTooltip.textContent = 'Interview & AI Assistant';
			else launcherTooltip.textContent = 'Haastattelu- & AI-avustaja';
		}

		// Update privacy notice
		const privacyText = document.getElementById('gemini-privacy-text');
		if (privacyText) {
			if (currentLang === 'sv') {
				privacyText.innerHTML = 'Matti Kiviharjus AI-intervjuassistent. Svaren granskas och godkänns av Matti innan uppläsning. <a href="' + escapeHtml(config.privacyUrl) + '" target="_blank" rel="noopener">Integritetspolicy</a>';
			} else if (currentLang === 'en') {
				privacyText.innerHTML = 'Matti Kiviharju’s AI Interview Assistant. Proposed answers are reviewed and approved by Matti before speaking. <a href="' + escapeHtml(config.privacyUrl) + '" target="_blank" rel="noopener">Privacy Policy</a>';
			} else {
				privacyText.innerHTML = 'Matti Kiviharjun AI-haastatteluavustaja. Vastaukset tarkistetaan ja hyväksytään manuaalisesti ennen puhetta. <a href="' + escapeHtml(config.privacyUrl) + '" target="_blank" rel="noopener">Tietosuojaseloste</a>';
			}
		}

		updateModeUI();
		updateSoundUI();
		setStatus('ready');

		if (conversationHistory.length === 0) {
			messagesEl.innerHTML = '';
			renderInitialState();
		} else {
			renderStarterChips();
		}

		try {
			sessionStorage.setItem('gemini_avatar_lang', currentLang);
		} catch (e) {}
	}

	/**
	 * Render initial greeting and suggestion chips
	 */
	function renderInitialState() {
		const greeting = (currentMode === 'interview')
			? (i18n.interviewGreeting || i18n.greeting)
			: i18n.greeting;
		appendMessage('assistant', greeting, []);
		renderStarterChips();
	}

	/**
	 * Render starter suggestion chips
	 */
	function renderStarterChips() {
		if (starterChipsEl && i18n.starters && i18n.starters.length) {
			starterChipsEl.innerHTML = '';
			i18n.starters.forEach(function (promptText) {
				const chip = document.createElement('button');
				chip.type = 'button';
				chip.className = 'gemini-starter-chip';
				chip.innerHTML = '<span>' + escapeHtml(promptText) + '</span>'
					+ '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>';

				chip.addEventListener('click', function () {
					if (isGenerating) return;
					sendQuestion(promptText);
				});
				starterChipsEl.appendChild(chip);
			});
			starterChipsEl.style.display = 'flex';
		}
	}

	/**
	 * Append message to chat transcript
	 */
	function appendMessage(role, text, sources, audioData, isProposed) {
		const msgEl = document.createElement('div');
		msgEl.className = 'gemini-msg msg-' + role;

		if (isProposed) {
			msgEl.classList.add('msg-proposed');
		}

		const bubbleEl = document.createElement('div');
		bubbleEl.className = 'gemini-bubble';

		if (isProposed) {
			const badge = document.createElement('div');
			badge.className = 'gemini-bubble-proposed-badge';
			badge.innerHTML = '🎯 ' + (i18n.proposedAnswer || 'Ehdotettu puheenvuoro');
			bubbleEl.appendChild(badge);
		}

		const textEl = document.createElement('div');
		textEl.className = 'gemini-bubble-text';
		textEl.innerHTML = formatMarkdown(text);
		bubbleEl.appendChild(textEl);
		msgEl.appendChild(bubbleEl);

		// Sources if available
		if (role === 'assistant' && sources && sources.length) {
			const sourcesEl = document.createElement('div');
			sourcesEl.className = 'gemini-msg-sources';
			sourcesEl.innerHTML = '<span class="gemini-sources-label">' + i18n.sources + '</span>';

			sources.forEach(function (s) {
				const tag = document.createElement('a');
				tag.className = 'gemini-source-tag';
				tag.href = s.url;
				tag.target = '_blank';
				tag.rel = 'noopener';
				tag.innerHTML = '<svg viewBox="0 0 24 24" width="10" height="10" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>'
					+ ' ' + escapeHtml(s.title);
				sourcesEl.appendChild(tag);
			});
			bubbleEl.appendChild(sourcesEl);
		}

		// Replay audio button for assistant messages if audio is attached
		if (role === 'assistant' && audioData) {
			const actionsEl = document.createElement('div');
			actionsEl.className = 'gemini-msg-audio-actions';

			const playBtn = document.createElement('button');
			playBtn.type = 'button';
			playBtn.className = 'gemini-msg-audio-btn';
			playBtn.innerHTML = '<svg viewBox="0 0 24 24" width="12" height="12" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>'
				+ ' <span>' + i18n.replay + '</span>';

			playBtn.addEventListener('click', function () {
				if (isPlayingAudio) {
					stopAudio();
				} else {
					playBase64Wav(audioData);
				}
			});
			actionsEl.appendChild(playBtn);
			msgEl.appendChild(actionsEl);
		}

		messagesEl.appendChild(msgEl);
		scrollToBottom();
		return msgEl;
	}

	function showThinkingIndicator() {
		const indicator = document.createElement('div');
		indicator.id = 'gemini-thinking-indicator';
		indicator.className = 'gemini-thinking-indicator';
		indicator.innerHTML = '<span class="gemini-thinking-dot"></span>'
			+ '<span class="gemini-thinking-dot"></span>'
			+ '<span class="gemini-thinking-dot"></span>';
		messagesEl.appendChild(indicator);
		scrollToBottom();
	}

	function removeThinkingIndicator() {
		const el = document.getElementById('gemini-thinking-indicator');
		if (el) el.remove();
	}

	/**
	 * Form submit handler
	 */
	function onFormSubmit(e) {
		if (e) e.preventDefault();
		if (!inputEl) return;
		const question = inputEl.value.trim();
		if (!question || isGenerating) return;

		inputEl.value = '';
		onInputChange();
		sendQuestion(question);
	}

	/**
	 * Send interview question to backend REST endpoint
	 */
	function sendQuestion(question) {
		if (isGenerating) return;

		stopAudio();
		stopVoiceInput();

		isGenerating = true;
		if (sendBtnEl) sendBtnEl.disabled = true;
		setStatus('thinking');

		// Hide existing starter chips
		if (starterChipsEl) starterChipsEl.style.display = 'none';

		// Append user question
		appendMessage('user', question);
		showThinkingIndicator();

		if (soundEnabled) {
			ensureAudioContext();
		}

		// In interview mode, do not auto-generate audio in /chat; user reviews text first!
		const shouldAutoGenerateAudio = (currentMode === 'assistant' && soundEnabled);

		fetch(config.apiUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': config.nonce,
			},
			body: JSON.stringify({
				question: question,
				generate_audio: shouldAutoGenerateAudio,
				history: conversationHistory,
				lang: currentLang,
				mode: currentMode,
				style: currentStyle,
			}),
		})
			.then(res => res.json().then(data => ({ status: res.status, data: data })))
			.then(result => {
				removeThinkingIndicator();
				isGenerating = false;
				if (sendBtnEl) sendBtnEl.disabled = false;

				if (result.status === 200 && result.data && result.data.success) {
					const answerText = result.data.text;
					const sources    = result.data.sources || [];
					const audioData  = result.data.audio_base64;

					// Record history
					conversationHistory.push({ role: 'user', text: question });
					conversationHistory.push({ role: 'model', text: answerText });

					setStatus('ready');

					if (currentMode === 'interview') {
						// HUMAN IN THE LOOP: Open preview & editing card
						showAnswerPreview(answerText, sources);
						appendMessage('assistant', answerText, sources, null, true);
					} else {
						// Normal assistant mode
						appendMessage('assistant', answerText, sources, audioData);
						if (soundEnabled && audioData) {
							playBase64Wav(audioData);
						}
					}
				} else {
					setStatus('error');
					const errorMsg = (result.data && result.data.error)
						? result.data.error
						: 'Pahoittelut, vastausta ei voitu noutaa. Yritä hetken kuluttua uudelleen.';
					appendMessage('assistant', '⚠️ ' + errorMsg);
				}
			})
			.catch(err => {
				removeThinkingIndicator();
				isGenerating = false;
				if (sendBtnEl) sendBtnEl.disabled = false;
				setStatus('error');
				appendMessage('assistant', '⚠️ Verkkovirhe: ' + err.message);
			});
	}

	/**
	 * ==========================================================================
	 * HUMAN-IN-THE-LOOP PREVIEW & APPROVAL WORKFLOW
	 * ==========================================================================
	 */
	function showAnswerPreview(text, sources) {
		if (!previewCardEl || !previewTextareaEl) return;

		previewTextareaEl.value = text;
		onPreviewTextareaChange();

		// Reset action buttons state
		if (btnApproveSpeak) {
			btnApproveSpeak.style.display = 'inline-flex';
			btnApproveSpeak.disabled = false;
			btnApproveSpeak.innerHTML = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg> <span>' + (i18n.approveSpeak || 'Hyväksy ja puhu kokoukseen') + '</span>';
		}
		if (btnPauseToggle) btnPauseToggle.style.display = 'none';
		if (btnReplayPreview) btnReplayPreview.style.display = 'none';
		if (btnEmergencyStop) btnEmergencyStop.style.display = 'none';

		previewCardEl.style.display = 'block';
		previewCardEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
	}

	function onPreviewTextareaChange() {
		if (!previewTextareaEl) return;
		const val = previewTextareaEl.value;

		if (previewCharCountEl) {
			previewCharCountEl.textContent = val.length + ' merkkiä';
		}

		// Estimate speaking duration: ~2.2 words per second (130 words per minute)
		const words = val.trim().split(/\s+/).filter(Boolean).length;
		const estSec = Math.max(5, Math.round(words / 2.2));
		if (previewDurationEl) {
			previewDurationEl.textContent = '~' + estSec + 's';
		}
	}

	function dismissPreview() {
		if (previewCardEl) {
			previewCardEl.style.display = 'none';
		}
		stopAudio();
	}

	/**
	 * User approves the (edited) answer text: Generate speech via Gemini TTS
	 */
	function onApproveAndSpeak() {
		if (!previewTextareaEl) return;
		const textToSpeak = previewTextareaEl.value.trim();
		if (!textToSpeak) return;

		lastApprovedSpeechText = textToSpeak;
		ensureAudioContext();

		if (btnApproveSpeak) {
			btnApproveSpeak.disabled = true;
			btnApproveSpeak.innerHTML = '<span class="gemini-thinking-dot"></span> <span>Syntetisoidaan puhetta...</span>';
		}
		setStatus('thinking');

		fetch(config.ttsUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': config.nonce,
			},
			body: JSON.stringify({
				text: textToSpeak,
			}),
		})
			.then(res => res.json())
			.then(data => {
				if (data && data.success && data.audio_base64) {
					// Play generated speech
					playBase64Wav(data.audio_base64);

					if (btnApproveSpeak) btnApproveSpeak.style.display = 'none';
					if (btnPauseToggle) btnPauseToggle.style.display = 'inline-flex';
					if (btnEmergencyStop) btnEmergencyStop.style.display = 'inline-flex';
					if (btnReplayPreview) btnReplayPreview.style.display = 'inline-flex';
				} else {
					setStatus('error');
					alert('TTS-virhe: ' + (data.error || 'Puhesynteesi epäonnistui.'));
					if (btnApproveSpeak) {
						btnApproveSpeak.disabled = false;
						btnApproveSpeak.innerHTML = '<span>Yritä uudelleen</span>';
					}
				}
			})
			.catch(err => {
				setStatus('error');
				alert('TTS-yhteysvirhe: ' + err.message);
				if (btnApproveSpeak) btnApproveSpeak.disabled = false;
			});
	}

	function onReplayCurrent() {
		if (currentAudioBuffer) {
			startAudioPlayback(currentAudioBuffer);
		} else if (lastApprovedSpeechText) {
			onApproveAndSpeak();
		}
	}

	function togglePauseAudio() {
		if (!isPlayingAudio && !isAudioPaused) return;

		if (!isAudioPaused && audioCtx && audioCtx.state === 'running') {
			audioCtx.suspend().then(() => {
				isAudioPaused = true;
				if (pauseBtnText) pauseBtnText.textContent = '▶ ' + (i18n.resume || 'Jatka');
				if (pauseStageText) pauseStageText.textContent = (i18n.resume || 'Jatka');
				setStatus('ready');
				broadcastAvatarState({ isSpeaking: false, amplitude: 0 });
			});
		} else if (isAudioPaused && audioCtx && audioCtx.state === 'suspended') {
			audioCtx.resume().then(() => {
				isAudioPaused = false;
				if (pauseBtnText) pauseBtnText.textContent = '⏸ ' + (i18n.pause || 'Tauko');
				if (pauseStageText) pauseStageText.textContent = (i18n.pause || 'Tauko');
				setStatus('speaking');
				broadcastAvatarState({ isSpeaking: true, amplitude: 0.5 });
			});
		}
	}

	/**
	 * ==========================================================================
	 * WEB AUDIO API & SPEECH SYNTHESIS PLAYBACK
	 * ==========================================================================
	 */
	function ensureAudioContext() {
		try {
			if (!audioCtx) {
				const AudioCtxClass = window.AudioContext || window.webkitAudioContext;
				if (AudioCtxClass) {
					audioCtx = new AudioCtxClass();
				}
			}
			if (audioCtx && audioCtx.state === 'suspended' && !isAudioPaused) {
				audioCtx.resume();
			}
		} catch (e) {
			console.warn('[Gemini Avatar] AudioContext error:', e);
		}
	}

	/**
	 * Play base64 encoded WAV audio using Web Audio API and drive visualizer
	 */
	function playBase64Wav(base64Data) {
		stopAudio();
		ensureAudioContext();

		if (!audioCtx) {
			console.warn('[Gemini Avatar] No AudioContext available.');
			return;
		}

		try {
			const binaryString = window.atob(base64Data);
			const len = binaryString.length;
			const bytes = new Uint8Array(len);
			for (let i = 0; i < len; i++) {
				bytes[i] = binaryString.charCodeAt(i);
			}

			audioCtx.decodeAudioData(
				bytes.buffer,
				function (audioBuffer) {
					currentAudioBuffer = audioBuffer;
					startAudioPlayback(audioBuffer);
				},
				function (err) {
					console.error('[Gemini Avatar] decodeAudioData error:', err);
					setStatus('ready');
				}
			);
		} catch (e) {
			console.error('[Gemini Avatar] Audio processing error:', e);
			setStatus('ready');
		}
	}

	/**
	 * Start audio source and hook into AnalyserNode
	 */
	function startAudioPlayback(buffer) {
		if (!audioCtx) return;

		analyserNode = audioCtx.createAnalyser();
		analyserNode.fftSize = 64;
		analyserNode.smoothingTimeConstant = 0.75;

		currentAudioSource = audioCtx.createBufferSource();
		currentAudioSource.buffer = buffer;

		// Set playback rate (speed)
		try {
			currentAudioSource.playbackRate.value = speechSpeed;
		} catch (e) {}

		currentAudioSource.connect(analyserNode);
		analyserNode.connect(audioCtx.destination);

		isPlayingAudio = true;
		isAudioPaused = false;
		if (pauseBtnText) pauseBtnText.textContent = '⏸ ' + (i18n.pause || 'Tauko');
		if (pauseStageText) pauseStageText.textContent = (i18n.pause || 'Tauko');
		setStatus('speaking');

		// Broadcast speaking start to Popout window
		broadcastAvatarState({ isSpeaking: true, amplitude: 0.5 });

		currentAudioSource.onended = function () {
			stopAudio();
		};

		currentAudioSource.start(0);

		// Start real-time audio amplitude animation
		startAmplitudeAnimation();
	}

	/**
	 * Real-time loop reading audio amplitude and animating avatar
	 */
	function startAmplitudeAnimation() {
		if (!analyserNode) return;

		const freqData = new Uint8Array(analyserNode.frequencyBinCount);
		const waveBars = audioWavesEl ? audioWavesEl.querySelectorAll('.wave-bar') : [];

		function renderFrame() {
			if (!isPlayingAudio || !analyserNode || isAudioPaused) {
				return;
			}

			analyserNode.getByteFrequencyData(freqData);

			let sum = 0;
			for (let i = 0; i < freqData.length; i++) {
				sum += freqData[i];
			}
			const average = sum / freqData.length;
			const amplitude = Math.min(1.0, average / 128.0); // 0.0 - 1.0

			// 1. Animate Photo Avatar mouth overlay
			if (mouthOverlay) {
				const scaleY = Math.max(0.2, amplitude * 2.8);
				const scaleX = 1 + amplitude * 0.4;
				mouthOverlay.style.transform = 'translateX(-50%) scale(' + scaleX + ', ' + scaleY + ')';
				mouthOverlay.style.opacity = (amplitude > 0.05) ? String(0.3 + amplitude * 0.7) : '0';
			}

			// 2. Animate Vector Avatar mouth (if SVG mode)
			if (vectorMouth) {
				const mouthOpen = 84 + amplitude * 14;
				const curve = 88 + amplitude * 18;
				vectorMouth.setAttribute('d', 'M48 84 Q60 ' + curve + ' 72 84 Q60 ' + mouthOpen + ' 48 84 Z');
			}

			// 3. Animate Outer Portal Ring scale
			if (portalOuterRing) {
				const ringScale = 1 + amplitude * 0.12;
				portalOuterRing.style.transform = 'scale(' + ringScale + ')';
			}

			// 4. Animate Equalizer Wave Bars
			if (waveBars && waveBars.length) {
				const step = Math.floor(freqData.length / waveBars.length);
				waveBars.forEach(function (bar, idx) {
					const val = (freqData[idx * step] || 0) / 255;
					const heightPx = Math.max(3, Math.round(val * 16));
					bar.style.height = heightPx + 'px';
				});
			}

			// 5. Broadcast to Popout window for Teams / OBS / Isadora
			broadcastAvatarState({
				isSpeaking: true,
				amplitude: amplitude,
			});

			animFrameId = requestAnimationFrame(renderFrame);
		}

		renderFrame();
	}

	/**
	 * Stop audio playback and reset avatar animation to idle
	 */
	function stopAudio() {
		isPlayingAudio = false;
		isAudioPaused = false;

		if (animFrameId) {
			cancelAnimationFrame(animFrameId);
			animFrameId = null;
		}

		if (currentAudioSource) {
			try {
				currentAudioSource.stop();
				currentAudioSource.disconnect();
			} catch (e) {}
			currentAudioSource = null;
		}

		// Reset visual elements
		if (mouthOverlay) {
			mouthOverlay.style.transform = 'translateX(-50%) scaleY(0)';
			mouthOverlay.style.opacity = '0';
		}
		if (vectorMouth) {
			vectorMouth.setAttribute('d', 'M48 84 Q60 88 72 84 Q60 90 48 84 Z');
		}
		if (portalOuterRing) {
			portalOuterRing.style.transform = 'scale(1)';
		}
		if (audioWavesEl) {
			const bars = audioWavesEl.querySelectorAll('.wave-bar');
			bars.forEach(function (bar) {
				bar.style.height = '3px';
			});
		}

		// Broadcast speech stop to Popout window
		broadcastAvatarState({ isSpeaking: false, amplitude: 0 });

		if (!isGenerating) {
			setStatus('ready');
		}
	}

	/**
	 * ==========================================================================
	 * VOICE INPUT (SPEECH RECOGNITION)
	 * ==========================================================================
	 */
	function toggleVoiceInput() {
		if (isRecordingVoice) {
			stopVoiceInput();
		} else {
			startVoiceInput();
		}
	}

	function startVoiceInput() {
		const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
		if (!SpeechRecognition) {
			alert(currentLang === 'sv' ? 'Din webbläsare stöder inte taligenkänning.' : (currentLang === 'en' ? 'Your browser does not support speech recognition.' : 'Selaimesi ei tue Web Speech API -puheentunnistusta.'));
			return;
		}

		stopAudio();

		try {
			speechRecognizer = new SpeechRecognition();
			speechRecognizer.lang = (i18n && i18n.speechLang) ? i18n.speechLang : ( (currentLang === 'sv') ? 'sv-SE' : ((currentLang === 'en') ? 'en-US' : 'fi-FI') );
			speechRecognizer.interimResults = true;
			speechRecognizer.continuous = false;

			speechRecognizer.onstart = function () {
				isRecordingVoice = true;
				if (micBtnEl) micBtnEl.classList.add('is-listening');
				if (micStatusEl) micStatusEl.style.display = 'flex';
			};

			speechRecognizer.onresult = function (event) {
				let transcript = '';
				for (let i = event.resultIndex; i < event.results.length; i++) {
					transcript += event.results[i][0].transcript;
				}
				if (inputEl) {
					inputEl.value = transcript;
					onInputChange();
				}
			};

			speechRecognizer.onerror = function (event) {
				console.warn('[Gemini Avatar] Speech recognition error:', event.error);
				stopVoiceInput();
				if (event.error === 'not-allowed') {
					alert(i18n.micPermission);
				}
			};

			speechRecognizer.onend = function () {
				stopVoiceInput();
			};

			speechRecognizer.start();
		} catch (e) {
			console.error('[Gemini Avatar] Voice input error:', e);
			stopVoiceInput();
		}
	}

	function stopVoiceInput() {
		isRecordingVoice = false;
		if (speechRecognizer) {
			try {
				speechRecognizer.stop();
			} catch (e) {}
			speechRecognizer = null;
		}
		if (micBtnEl) micBtnEl.classList.remove('is-listening');
		if (micStatusEl) micStatusEl.style.display = 'none';
	}

	/**
	 * Vector avatar subtle blinking loop
	 */
	function initVectorAvatarBlink() {
		const eyes = drawerEl ? drawerEl.querySelectorAll('.gemini-eye') : [];
		if (!eyes || !eyes.length) return;

		function triggerBlink() {
			eyes.forEach(eye => eye.style.transform = 'scaleY(0.1)');
			setTimeout(function () {
				eyes.forEach(eye => eye.style.transform = 'scaleY(1)');
			}, 150);

			const nextBlink = 3000 + Math.random() * 3000;
			setTimeout(triggerBlink, nextBlink);
		}

		setTimeout(triggerBlink, 3500);
	}

	function onInputChange() {
		if (!inputEl) return;
		inputEl.style.height = 'auto';
		inputEl.style.height = Math.min(100, Math.max(24, inputEl.scrollHeight)) + 'px';

		if (charCountEl) {
			charCountEl.textContent = inputEl.value.length + '/' + config.maxChars;
		}
	}

	function scrollToBottom() {
		const body = document.getElementById('gemini-drawer-body');
		if (body) {
			body.scrollTop = body.scrollHeight;
		}
	}

	function escapeHtml(str) {
		const div = document.createElement('div');
		div.textContent = str || '';
		return div.innerHTML;
	}

	function formatMarkdown(text) {
		let safe = escapeHtml(text);
		safe = safe.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
		safe = safe.replace(/\*(.*?)\*/g, '<em>$1</em>');
		safe = safe.replace(/`([^`]+)`/g, '<code style="background:rgba(255,255,255,0.1);padding:1px 4px;border-radius:4px;">$1</code>');
		const paragraphs = safe.split(/\n\n+/);
		return paragraphs.map(p => '<p>' + p.replace(/\n/g, '<br/>') + '</p>').join('');
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
