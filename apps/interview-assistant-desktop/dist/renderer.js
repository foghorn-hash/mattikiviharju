"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
// State
let appConfig = null;
let activePrep = null;
let currentMode = 'job_seeker';
let isListening = false;
let isSpeaking = false;
let isApproved = false;
let micRecognition = null;
let currentSpeechUtterance = null;
let conversationHistory = [];
// DOM Elements
const badgeWp = document.getElementById('badge-wp');
const badgeWpText = document.getElementById('badge-wp-text');
const badgeExtension = document.getElementById('badge-extension');
const badgeExtensionText = document.getElementById('badge-extension-text');
const barCompanyRole = document.getElementById('bar-company-role');
const barLanguage = document.getElementById('bar-language');
const barProjectsCount = document.getElementById('bar-projects-count');
const btnModeJobSeeker = document.getElementById('btn-mode-jobseeker');
const btnModeHrAdvisor = document.getElementById('btn-mode-hradvisor');
const jobSeekerView = document.getElementById('jobseeker-view');
const hrAdvisorView = document.getElementById('hradvisor-view');
const interviewerQuestionDisplay = document.getElementById('interviewer-question-display');
const listeningIndicatorBadge = document.getElementById('listening-indicator-badge');
const listeningText = document.getElementById('listening-text');
const btnToggleListen = document.getElementById('btn-toggle-listen');
const btnMicTest = document.getElementById('btn-mic-test');
const manualQuestionInput = document.getElementById('manual-question-input');
const btnSubmitManualQ = document.getElementById('btn-submit-manual-q');
const aiLoadingBar = document.getElementById('ai-loading-bar');
// Advice outputs
const adviceIntent = document.getElementById('advice-intent');
const adviceMainPoint = document.getElementById('advice-main-point');
const adviceSupportingPoints = document.getElementById('advice-supporting-points');
const adviceRealExample = document.getElementById('advice-real-example');
const adviceClarifyCard = document.getElementById('advice-clarify-card');
const adviceClarifyingQuestion = document.getElementById('advice-clarifying-question');
// HR outputs
const hrScoreVal = document.getElementById('hr-score-val');
const hrEvaluationContent = document.getElementById('hr-evaluation-content');
const hrNextQuestion = document.getElementById('hr-next-question');
// Speech Draft & Approval
const speechDraftInput = document.getElementById('speech-draft-input');
const speechCharCount = document.getElementById('speech-char-count');
const approvalStatusBadge = document.getElementById('approval-status-badge');
const btnApproveSpeak = document.getElementById('btn-approve-speak');
const btnEmergencyStop = document.getElementById('btn-emergency-stop');
// Avatar Status
const avatarStatusBadge = document.getElementById('avatar-status-badge');
const avatarStatusText = document.getElementById('avatar-status-text');
const avatarCanvas = document.getElementById('avatar-canvas');
const waveformCanvas = document.getElementById('waveform-canvas');
// Pairing Modal
const pairingModal = document.getElementById('pairing-modal');
const btnOpenPairing = document.getElementById('btn-open-pairing');
const btnCloseModal = document.getElementById('btn-close-modal');
const btnDoPair = document.getElementById('btn-do-pair');
const inputWpUrl = document.getElementById('input-wp-url');
const inputPairCode = document.getElementById('input-pair-code');
const pairErrorMsg = document.getElementById('pair-error-msg');
const btnOpenPopout = document.getElementById('btn-open-popout');
const btnRefreshPrep = document.getElementById('btn-refresh-prep');
// Initialize
async function initApp() {
    appConfig = await window.api.getConfig();
    inputWpUrl.value = appConfig.serverUrl || 'http://localhost/mattikiviharju';
    initAvatarCanvas();
    setupEventListeners();
    if (appConfig.deviceToken) {
        updateWpStatus(true, appConfig.pairedUser?.name || 'Matti Kiviharju');
        loadPreparation();
    }
    else {
        updateWpStatus(false, 'Ei paritettu');
        openPairingModal();
    }
    // Setup Extension connection listener
    window.api.onExtensionConnectionChanged((connected) => {
        if (connected) {
            badgeExtension.className = 'badge badge-online';
            badgeExtensionText.textContent = 'Selainlaajennus: Yhdistetty';
        }
        else {
            badgeExtension.className = 'badge';
            badgeExtensionText.textContent = 'Selainlaajennus: Odottaa';
        }
    });
    // Setup Extension incoming messages
    window.api.onExtensionMessage((msg) => {
        handleExtensionMessage(msg);
    });
}
function updateWpStatus(paired, userName) {
    if (paired) {
        badgeWp.className = 'badge badge-online';
        badgeWpText.textContent = `WordPress: ${userName}`;
    }
    else {
        badgeWp.className = 'badge';
        badgeWpText.textContent = 'WordPress: Ei paritettu';
    }
}
// Load Interview Preparation
async function loadPreparation() {
    try {
        barCompanyRole.textContent = 'Ladataan taustapakettia...';
        const res = await window.api.fetchPreparation();
        if (res.success && res.preparation) {
            activePrep = res.preparation;
            currentMode = activePrep?.mode || 'job_seeker';
            updateModeUI();
            const company = activePrep?.company_name || 'Kohdeyritys';
            const role = activePrep?.role_title || activePrep?.job_title || 'Toimeksianto';
            barCompanyRole.textContent = `${company} — ${role}`;
            const langMap = {
                fi: 'Suomi (FI)',
                en: 'English (EN)',
                sv: 'Svenska (SV)',
                de: 'Deutsch (DE)',
            };
            barLanguage.textContent = langMap[activePrep?.language || 'fi'] || 'Suomi (FI)';
            const count = activePrep?.verified_projects?.length || 0;
            barProjectsCount.textContent = `${count} kpl`;
        }
        else {
            barCompanyRole.textContent = 'Valitse hakemus WordPress-hallinnasta';
        }
    }
    catch (err) {
        console.error('Failed to load preparation:', err);
        barCompanyRole.textContent = 'Virhe ladattaessa taustoja';
    }
}
// Mode UI toggle
function updateModeUI() {
    if (currentMode === 'hr_advisor') {
        btnModeJobSeeker.classList.remove('active');
        btnModeHrAdvisor.classList.add('active');
        jobSeekerView.style.display = 'none';
        hrAdvisorView.style.display = 'flex';
    }
    else {
        btnModeHrAdvisor.classList.remove('active');
        btnModeJobSeeker.classList.add('active');
        hrAdvisorView.style.display = 'none';
        jobSeekerView.style.display = 'flex';
    }
}
// Handle Incoming Extension Message
function handleExtensionMessage(msg) {
    if (msg.type === 'MEETING_DETECTED') {
        badgeExtension.className = 'badge badge-online';
        badgeExtensionText.textContent = `${msg.platform === 'teams_web' ? 'Teams' : 'Meet'}: ${msg.meetingTitle || 'Kokous'}`;
    }
    else if (msg.type === 'TRANSCRIPT' && msg.text) {
        interviewerQuestionDisplay.textContent = msg.text;
        if (msg.isFinal) {
            triggerAnalysis(msg.text);
        }
    }
}
// Trigger AI Suggestion
async function triggerAnalysis(questionText) {
    if (!questionText.trim())
        return;
    aiLoadingBar.style.display = 'flex';
    setAvatarStatus('ANALOI', '#3b82f6');
    // Push question to history
    conversationHistory.push({ role: 'interviewer', text: questionText });
    try {
        const res = await window.api.suggestAnswer(questionText, currentMode, conversationHistory);
        aiLoadingBar.style.display = 'none';
        if (res.success && res.data) {
            renderAiSuggestion(res.data);
        }
        else {
            alert('Virhe vastauksen ehdotuksessa: ' + (res.message || 'Tuntematon virhe'));
            setAvatarStatus('VALMIUSTILASSA', '#10b981');
        }
    }
    catch (err) {
        aiLoadingBar.style.display = 'none';
        console.error('AI Suggestion error:', err);
        setAvatarStatus('VALMIUSTILASSA', '#10b981');
    }
}
// Render Suggestion
function renderAiSuggestion(s) {
    adviceIntent.textContent = s.intent || 'Ei tunnistettu';
    adviceMainPoint.textContent = s.main_point || 'Ei pääpointtia';
    adviceSupportingPoints.innerHTML = '';
    if (s.supporting_points && s.supporting_points.length) {
        s.supporting_points.forEach((pt) => {
            const li = document.createElement('li');
            li.textContent = pt;
            adviceSupportingPoints.appendChild(li);
        });
    }
    adviceRealExample.textContent = s.real_example || 'Ei erillistä projektia';
    if (s.clarifying_question) {
        adviceClarifyingQuestion.textContent = s.clarifying_question;
        adviceClarifyCard.style.display = 'block';
    }
    else {
        adviceClarifyCard.style.display = 'none';
    }
    // HR mode outputs
    if (s.hr_score) {
        hrScoreVal.textContent = `${s.hr_score} / 5`;
    }
    hrEvaluationContent.textContent = s.main_point;
    hrNextQuestion.textContent = s.clarifying_question || s.real_example || 'Kysy konkreettisesta teknisestä arkkitehtuurista.';
    // Speech Draft
    speechDraftInput.value = s.speech_draft || '';
    updateSpeechCharCount();
    // Reset Approval State: Approval is VOID whenever new suggestion appears
    setApprovalState('pending', 'Odottaa hyväksyntää');
    setAvatarStatus('VALMIUSTILASSA', '#10b981');
}
// Approval State Machine
function setApprovalState(state, label) {
    approvalStatusBadge.className = `approval-state approval-${state}`;
    approvalStatusBadge.textContent = label;
    if (state === 'approved') {
        isApproved = true;
        btnApproveSpeak.disabled = false;
    }
    else {
        isApproved = false;
    }
}
function updateSpeechCharCount() {
    const len = speechDraftInput.value.length;
    speechCharCount.textContent = `${len} merkkiä`;
}
// Invalidate approval immediately if user edits text
speechDraftInput.addEventListener('input', () => {
    updateSpeechCharCount();
    if (isSpeaking) {
        emergencyStopSpeech();
    }
    setApprovalState('voided', 'Tekstiä muokattu — vaatii uuden hyväksynnän');
});
// Approve & Speak Action
function approveAndSpeak() {
    const text = speechDraftInput.value.trim();
    if (!text) {
        alert('Puheenvuoroluonnos on tyhjä.');
        return;
    }
    setApprovalState('approved', 'Hyväksytty — puhutaan...');
    isSpeaking = true;
    window.api.setAvatarSpeaking(true);
    setAvatarStatus('PUHUU', '#10b981');
    // Push candidate response to history
    conversationHistory.push({ role: 'candidate', text });
    // Web Speech Synthesis
    if ('speechSynthesis' in window) {
        window.speechSynthesis.cancel();
        const u = new SpeechSynthesisUtterance(text);
        currentSpeechUtterance = u;
        const lang = activePrep?.language || 'fi';
        u.lang = lang === 'en' ? 'en-US' : lang === 'sv' ? 'sv-SE' : lang === 'de' ? 'de-DE' : 'fi-FI';
        u.onend = () => {
            onSpeechComplete();
        };
        u.onerror = (e) => {
            console.warn('Speech synthesis error or cancelled:', e);
            onSpeechComplete();
        };
        window.speechSynthesis.speak(u);
    }
    else {
        alert('Web Speech Synthesis ei ole tuettu tässä ympäristössä.');
        onSpeechComplete();
    }
}
// Speech Completed: Permission automatically revoked
function onSpeechComplete() {
    isSpeaking = false;
    window.api.setAvatarSpeaking(false);
    setAvatarStatus('VALMIUSTILASSA', '#10b981');
    // Automatically void approval: speaking permission expires immediately
    setApprovalState('voided', 'Puhe päättynyt — puhelupa rauennut');
    currentSpeechUtterance = null;
}
// Emergency Stop
function emergencyStopSpeech() {
    if ('speechSynthesis' in window) {
        window.speechSynthesis.cancel();
    }
    isSpeaking = false;
    window.api.setAvatarSpeaking(false);
    setAvatarStatus('VALMIUSTILASSA', '#10b981');
    setApprovalState('voided', 'Puhe keskeytetty välittömästi! Puhelupa mitätöity.');
    currentSpeechUtterance = null;
}
// Microphone Testing (Local SpeechRecognition)
function toggleMicrophoneTest() {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRecognition) {
        alert('Puheentunnistusrajapinta (SpeechRecognition) ei ole suoraan käytettävissä. Voit käyttää selainlaajennusta kokouksissa tai syöttää kysymyksen tekstikenttään.');
        return;
    }
    if (micRecognition) {
        micRecognition.stop();
        micRecognition = null;
        btnMicTest.textContent = '🎤 Testaa puhetta mikillä';
        setListeningState(false);
        return;
    }
    try {
        micRecognition = new SpeechRecognition();
        const lang = activePrep?.language || 'fi';
        micRecognition.lang = lang === 'en' ? 'en-US' : lang === 'sv' ? 'sv-SE' : 'fi-FI';
        micRecognition.continuous = true;
        micRecognition.interimResults = true;
        micRecognition.onstart = () => {
            btnMicTest.textContent = '⏹ Pysäytä mikkitesti';
            setListeningState(true);
            interviewerQuestionDisplay.textContent = 'Kuunnellaan puhetta mikrofonista...';
        };
        micRecognition.onresult = (event) => {
            let interim = '';
            let final = '';
            for (let i = event.resultIndex; i < event.results.length; ++i) {
                if (event.results[i].isFinal) {
                    final += event.results[i][0].transcript;
                }
                else {
                    interim += event.results[i][0].transcript;
                }
            }
            const display = final || interim;
            if (display) {
                interviewerQuestionDisplay.textContent = display;
            }
            if (final && !isSpeaking) {
                triggerAnalysis(final);
            }
        };
        micRecognition.onerror = (err) => {
            console.warn('Mic recognition error:', err);
            btnMicTest.textContent = '🎤 Testaa puhetta mikillä';
            setListeningState(false);
            micRecognition = null;
        };
        micRecognition.onend = () => {
            btnMicTest.textContent = '🎤 Testaa puhetta mikillä';
            setListeningState(false);
            micRecognition = null;
        };
        micRecognition.start();
    }
    catch (err) {
        console.error('Failed to start SpeechRecognition:', err);
    }
}
function setListeningState(listening) {
    isListening = listening;
    if (listening) {
        listeningIndicatorBadge.className = 'badge badge-listening';
        listeningText.textContent = 'Kuuntelee aktiivisesti';
        btnToggleListen.textContent = '⏹ Pysäytä kuuntelu';
        setAvatarStatus('KUUNTELEE', '#f59e0b');
    }
    else {
        listeningIndicatorBadge.className = 'badge';
        listeningText.textContent = 'Kuuntelu pois';
        btnToggleListen.textContent = '▶ Aloita kuuntelu';
        setAvatarStatus('VALMIUSTILASSA', '#10b981');
    }
}
// Avatar Status Display
function setAvatarStatus(label, color) {
    avatarStatusText.textContent = label;
    const dot = avatarStatusBadge.querySelector('.badge-dot');
    if (dot)
        dot.style.background = color;
    if (label === 'PUHUU') {
        avatarStatusBadge.classList.add('avatar-badge-speaking');
    }
    else {
        avatarStatusBadge.classList.remove('avatar-badge-speaking');
    }
}
// Setup Event Listeners
function setupEventListeners() {
    btnModeJobSeeker.addEventListener('click', () => {
        currentMode = 'job_seeker';
        updateModeUI();
    });
    btnModeHrAdvisor.addEventListener('click', () => {
        currentMode = 'hr_advisor';
        updateModeUI();
    });
    btnToggleListen.addEventListener('click', () => {
        setListeningState(!isListening);
    });
    btnMicTest.addEventListener('click', toggleMicrophoneTest);
    btnSubmitManualQ.addEventListener('click', () => {
        const q = manualQuestionInput.value.trim();
        if (q) {
            interviewerQuestionDisplay.textContent = q;
            manualQuestionInput.value = '';
            triggerAnalysis(q);
        }
    });
    manualQuestionInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            btnSubmitManualQ.click();
        }
    });
    // Preset question buttons
    document.querySelectorAll('.btn-preset').forEach((b) => {
        b.addEventListener('click', () => {
            const q = b.dataset.q || '';
            interviewerQuestionDisplay.textContent = q;
            triggerAnalysis(q);
        });
    });
    btnApproveSpeak.addEventListener('click', approveAndSpeak);
    btnEmergencyStop.addEventListener('click', emergencyStopSpeech);
    btnOpenPopout.addEventListener('click', () => {
        window.api.toggleAvatarPopout();
    });
    btnRefreshPrep.addEventListener('click', loadPreparation);
    // Pairing Modal Events
    btnOpenPairing.addEventListener('click', openPairingModal);
    btnCloseModal.addEventListener('click', closePairingModal);
    btnDoPair.addEventListener('click', performPairing);
}
function openPairingModal() {
    pairingModal.style.display = 'flex';
    pairErrorMsg.style.display = 'none';
    inputPairCode.focus();
}
function closePairingModal() {
    pairingModal.style.display = 'none';
}
async function performPairing() {
    const url = inputWpUrl.value.trim();
    const code = inputPairCode.value.trim().toUpperCase();
    if (!url || !code) {
        pairErrorMsg.textContent = 'Syötä sekä osoite että parituskoodi.';
        pairErrorMsg.style.display = 'block';
        return;
    }
    btnDoPair.disabled = true;
    btnDoPair.textContent = 'Yhdistetään...';
    pairErrorMsg.style.display = 'none';
    try {
        const res = await window.api.pairWithWordPress(url, code);
        btnDoPair.disabled = false;
        btnDoPair.textContent = 'Yhdistä ja parita';
        if (res.success) {
            closePairingModal();
            appConfig = await window.api.getConfig();
            updateWpStatus(true, res.user?.display_name || 'Matti Kiviharju');
            loadPreparation();
            alert('Laiteparitus onnistui! Yhteys WordPressiin on aktiivinen.');
        }
        else {
            pairErrorMsg.textContent = res.message || 'Laiteparitus epäonnistui.';
            pairErrorMsg.style.display = 'block';
        }
    }
    catch (err) {
        btnDoPair.disabled = false;
        btnDoPair.textContent = 'Yhdistä ja parita';
        pairErrorMsg.textContent = err.message || String(err);
        pairErrorMsg.style.display = 'block';
    }
}
// Canvas LiveAvatar Animation Loop
function initAvatarCanvas() {
    const ctx = avatarCanvas.getContext('2d');
    if (!ctx)
        return;
    function resizeCanvas() {
        const rect = avatarCanvas.parentElement?.getBoundingClientRect();
        if (rect) {
            avatarCanvas.width = rect.width;
            avatarCanvas.height = rect.height;
        }
    }
    resizeCanvas();
    window.addEventListener('resize', resizeCanvas);
    function renderFrame() {
        if (!ctx)
            return;
        const w = avatarCanvas.width;
        const h = avatarCanvas.height;
        ctx.clearRect(0, 0, w, h);
        // Vignette background
        const bgGrad = ctx.createRadialGradient(w / 2, h / 2, 30, w / 2, h / 2, Math.max(w, h) / 1.3);
        bgGrad.addColorStop(0, '#1e293b');
        bgGrad.addColorStop(1, '#090d16');
        ctx.fillStyle = bgGrad;
        ctx.fillRect(0, 0, w, h);
        const cx = w / 2;
        const cy = h / 2 - 10;
        const scale = Math.min(w, h) / 320;
        const time = Date.now() * 0.002;
        const headAngle = Math.sin(time) * 0.02;
        const breath = Math.sin(time * 0.8) * 2;
        ctx.save();
        ctx.translate(cx, cy + breath);
        ctx.rotate(headAngle);
        ctx.scale(scale, scale);
        // Suit shoulders
        ctx.fillStyle = '#0f172a';
        ctx.beginPath();
        ctx.ellipse(0, 150, 130, 65, 0, 0, Math.PI * 2);
        ctx.fill();
        // Shirt collar
        ctx.fillStyle = '#f8fafc';
        ctx.beginPath();
        ctx.moveTo(-30, 90);
        ctx.lineTo(0, 120);
        ctx.lineTo(30, 90);
        ctx.lineTo(20, 105);
        ctx.lineTo(0, 130);
        ctx.lineTo(-20, 105);
        ctx.closePath();
        ctx.fill();
        // Neck
        ctx.fillStyle = '#f6d8b8';
        ctx.fillRect(-20, 45, 40, 55);
        // Head
        ctx.fillStyle = '#fed7aa';
        ctx.beginPath();
        ctx.ellipse(0, 10, 60, 75, 0, 0, Math.PI * 2);
        ctx.fill();
        // Hair
        ctx.fillStyle = '#262626';
        ctx.beginPath();
        ctx.arc(0, -18, 62, Math.PI, 0);
        ctx.fill();
        // Eyebrows
        ctx.strokeStyle = '#1c1917';
        ctx.lineWidth = 3;
        ctx.beginPath();
        ctx.moveTo(-40, -22);
        ctx.quadraticCurveTo(-22, -32, -8, -24);
        ctx.moveTo(8, -24);
        ctx.quadraticCurveTo(22, -32, 40, -22);
        ctx.stroke();
        // Eyes
        const blink = Math.sin(time * 0.5) > 0.96;
        ctx.fillStyle = '#ffffff';
        ctx.strokeStyle = '#334155';
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.ellipse(-24, -8, 12, blink ? 2 : 8, 0, 0, Math.PI * 2);
        ctx.ellipse(24, -8, 12, blink ? 2 : 8, 0, 0, Math.PI * 2);
        ctx.fill();
        ctx.stroke();
        if (!blink) {
            // Pupils
            ctx.fillStyle = '#1e3a8a';
            ctx.beginPath();
            ctx.arc(-24, -8, 4.5, 0, Math.PI * 2);
            ctx.arc(24, -8, 4.5, 0, Math.PI * 2);
            ctx.fill();
            // Catchlight
            ctx.fillStyle = '#ffffff';
            ctx.beginPath();
            ctx.arc(-22, -10, 1.8, 0, Math.PI * 2);
            ctx.arc(26, -10, 1.8, 0, Math.PI * 2);
            ctx.fill();
        }
        // Nose
        ctx.strokeStyle = '#d97706';
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.moveTo(0, -8);
        ctx.lineTo(2, 18);
        ctx.lineTo(-4, 21);
        ctx.stroke();
        // Mouth with Lip-Sync Visemes
        let mouthViseme = 0;
        if (isSpeaking) {
            mouthViseme = Math.abs(Math.sin(time * 16));
            const mouthHeight = 3 + mouthViseme * 9;
            ctx.fillStyle = '#881337';
            ctx.beginPath();
            ctx.ellipse(0, 44, 15, Math.max(2, mouthHeight), 0, 0, Math.PI * 2);
            ctx.fill();
        }
        else {
            ctx.strokeStyle = '#b91c1c';
            ctx.lineWidth = 2.5;
            ctx.beginPath();
            ctx.arc(0, 40, 13, 0.2, Math.PI - 0.2);
            ctx.stroke();
        }
        // Forward viseme to popout window
        window.api.sendAvatarVisemeUpdate({ mouthOpen: mouthViseme });
        ctx.restore();
        // Draw bottom audio waveform
        drawWaveform();
        requestAnimationFrame(renderFrame);
    }
    function drawWaveform() {
        const wCtx = waveformCanvas.getContext('2d');
        if (!wCtx)
            return;
        const ww = (waveformCanvas.width = waveformCanvas.clientWidth || 300);
        const wh = (waveformCanvas.height = 40);
        wCtx.clearRect(0, 0, ww, wh);
        wCtx.fillStyle = 'rgba(59, 130, 246, 0.2)';
        const bars = 30;
        const barWidth = ww / bars;
        const time = Date.now() * 0.005;
        for (let i = 0; i < bars; i++) {
            let h = 3;
            if (isSpeaking) {
                h = 6 + Math.abs(Math.sin(time + i * 0.4)) * 26;
            }
            else if (isListening) {
                h = 4 + Math.abs(Math.sin(time * 0.6 + i * 0.3)) * 14;
            }
            wCtx.fillRect(i * barWidth + 2, wh - h, barWidth - 4, h);
        }
    }
    requestAnimationFrame(renderFrame);
}
// Run
window.addEventListener('DOMContentLoaded', initApp);
