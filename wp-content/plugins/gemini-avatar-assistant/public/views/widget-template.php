<?php
/**
 * Public Avatar and Chat Drawer Template
 * Integrated Gemini AI Interview Assistant for MS Teams & Google Meet
 *
 * @package Gemini_Avatar_Assistant
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$avatar_url   = ! empty( $settings['avatar_image_url'] ) ? $settings['avatar_image_url'] : '';
$avatar_type  = ! empty( $settings['avatar_type'] ) ? $settings['avatar_type'] : 'photo';
$current_lang = Gemini_Avatar_Public::get_current_language();

$state_text = 'Valmiina';
if ( 'sv' === $current_lang ) {
	$state_text = 'Redo';
} elseif ( 'en' === $current_lang ) {
	$state_text = 'Ready';
}
?>

<div id="gemini-avatar-widget" class="gemini-avatar-widget" role="complementary" aria-label="<?php echo esc_attr( $settings['assistant_name'] ); ?>">

	<!-- FLOATING LAUNCHER BUTTON -->
	<button id="gemini-avatar-launcher" class="gemini-launcher-btn" type="button" aria-expanded="false" aria-controls="gemini-avatar-drawer" aria-label="<?php echo esc_attr( $settings['assistant_name'] ); ?>">
		<div class="gemini-launcher-avatar">
			<div class="gemini-avatar-halo"></div>
			<div class="gemini-avatar-img-wrap">
				<?php if ( ! empty( $avatar_url ) ) : ?>
					<img src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php echo esc_attr( $settings['assistant_name'] ); ?>" class="gemini-avatar-thumb" />
				<?php else : ?>
					<span class="gemini-avatar-fallback-icon">🤖</span>
				<?php endif; ?>
			</div>
			<span class="gemini-status-indicator status-ready" id="gemini-launcher-status" aria-hidden="true"></span>
		</div>
		<div class="gemini-launcher-tooltip" id="gemini-launcher-tooltip">
			<span class="gemini-tooltip-badge">AI</span>
			<span class="gemini-tooltip-text" id="gemini-launcher-tooltip-text"><?php
				if ( 'sv' === $current_lang ) {
					echo 'Intervju- & AI-assistent';
				} elseif ( 'en' === $current_lang ) {
					echo 'Interview & AI Assistant';
				} else {
					echo 'Haastattelu- & AI-avustaja';
				}
			?></span>
		</div>
	</button>

	<!-- CHAT DRAWER / INTERVIEW ASSISTANT WINDOW -->
	<div id="gemini-avatar-drawer" class="gemini-drawer" role="dialog" aria-modal="false" aria-labelledby="gemini-drawer-title" hidden>

		<!-- DRAWER HEADER -->
		<header class="gemini-drawer-header">
			<div class="gemini-header-profile">
				<div class="gemini-header-avatar-wrap">
					<?php if ( ! empty( $avatar_url ) ) : ?>
						<img src="<?php echo esc_url( $avatar_url ); ?>" alt="Avatar" class="gemini-header-avatar" />
					<?php else : ?>
						<span class="gemini-avatar-fallback-icon">🤖</span>
					<?php endif; ?>
					<span class="gemini-status-dot status-ready" id="gemini-header-status-dot"></span>
				</div>
				<div class="gemini-header-info">
					<h2 id="gemini-drawer-title" class="gemini-header-name"><?php echo esc_html( $settings['assistant_name'] ); ?></h2>
					<div class="gemini-header-state" id="gemini-header-state-text"><?php echo esc_html( $state_text ); ?></div>
				</div>
			</div>

			<div class="gemini-header-actions">
				<!-- Language Switcher Pill Group -->
				<div class="gemini-lang-selector" id="gemini-lang-selector" role="group" aria-label="Valitse kieli / Select language">
					<button type="button" class="gemini-lang-pill <?php echo ( 'fi' === $current_lang ) ? 'active' : ''; ?>" data-lang="fi" title="Suomi" aria-label="Suomi">FI</button>
					<button type="button" class="gemini-lang-pill <?php echo ( 'en' === $current_lang ) ? 'active' : ''; ?>" data-lang="en" title="English" aria-label="English">EN</button>
					<button type="button" class="gemini-lang-pill <?php echo ( 'sv' === $current_lang ) ? 'active' : ''; ?>" data-lang="sv" title="Svenska" aria-label="Svenska">SV</button>
				</div>

				<!-- Popout Avatar Window Button (For Teams / OBS / Isadora 3 Screen Capture) -->
				<button type="button" id="gemini-btn-popout" class="gemini-header-btn" title="Avaa jaettava Avatar (Teams / Meet / Isadora / OBS)" aria-label="Avaa jaettava Avatar">
					<svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
						<line x1="8" y1="21" x2="16" y2="21"></line>
						<line x1="12" y1="17" x2="12" y2="21"></line>
					</svg>
				</button>

				<!-- Sound Toggle Button -->
				<button type="button" id="gemini-btn-sound" class="gemini-header-btn sound-on" title="Puhe päällä" aria-label="Kytke puhesynteesi">
					<svg class="icon-sound-on" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
						<path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path>
					</svg>
					<svg class="icon-sound-off" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
						<line x1="23" y1="9" x2="17" y2="15"></line>
						<line x1="17" y1="9" x2="23" y2="15"></line>
					</svg>
				</button>

				<!-- Reset Chat Button -->
				<button type="button" id="gemini-btn-reset" class="gemini-header-btn" title="Aloita uusi keskustelu" aria-label="Aloita alusta">
					<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.19"></path>
					</svg>
				</button>

				<!-- Minimize / Close Button -->
				<button type="button" id="gemini-btn-close" class="gemini-header-btn" title="Sulje ja pienennä" aria-label="Sulje avustaja">
					<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<line x1="18" y1="6" x2="6" y2="18"></line>
						<line x1="6" y1="6" x2="18" y2="18"></line>
					</svg>
				</button>
			</div>
		</header>

		<!-- MODE AND INTERVIEW CONTROLS TOOLBAR -->
		<div class="gemini-toolbar">
			<!-- Mode Switcher -->
			<div class="gemini-mode-group" role="group" aria-label="Toimintatila">
				<button type="button" id="gemini-mode-interview" class="gemini-mode-pill active" data-mode="interview">
					<span>🎯 Haastattelu (Teams/Meet)</span>
				</button>
				<button type="button" id="gemini-mode-assistant" class="gemini-mode-pill" data-mode="assistant">
					<span>💬 Sivustoavustaja</span>
				</button>
			</div>

			<!-- Interview Settings (Response style & Speed) -->
			<div class="gemini-subtoolbar" id="gemini-interview-subtoolbar">
				<!-- Style Selector -->
				<div class="gemini-pill-selector" id="gemini-style-selector" role="group" aria-label="Vastauksen tyyli">
					<span class="gemini-selector-label">Tyyli:</span>
					<button type="button" class="gemini-style-pill" data-style="concise" title="Tiivis vastaus (~20-30s)">Tiivis</button>
					<button type="button" class="gemini-style-pill active" data-style="conversational" title="Keskusteleva, ammattimainen (~45s)">Normaali</button>
					<button type="button" class="gemini-style-pill" data-style="technical" title="Syvällinen arkkitehtuuri & teknologia (~75s)">Tekninen</button>
				</div>

				<!-- Speed Selector -->
				<div class="gemini-pill-selector" id="gemini-speed-selector" role="group" aria-label="Puhenopeus">
					<span class="gemini-selector-label">Nopeus:</span>
					<button type="button" class="gemini-speed-pill" data-speed="0.85">0.8x</button>
					<button type="button" class="gemini-speed-pill active" data-speed="1.0">1.0x</button>
					<button type="button" class="gemini-speed-pill" data-speed="1.2">1.2x</button>
					<button type="button" class="gemini-speed-pill" data-speed="1.4">1.4x</button>
				</div>
			</div>
		</div>

		<!-- INTERACTIVE AVATAR STAGE (Audio-reactive visualizer) -->
		<section class="gemini-avatar-stage" aria-label="Avatar-animaatio">
			<!-- Stage Status Strip -->
			<div class="gemini-stage-status-strip">
				<div class="gemini-ai-connection-badge" id="gemini-ai-connection-badge">
					<span class="gemini-pulse-dot dot-online"></span>
					<span id="gemini-ai-status-text">Gemini AI valmiina</span>
				</div>
				<div class="gemini-speech-activity-badge" id="gemini-speech-activity-badge" style="display:none;">
					<span class="gemini-pulse-dot dot-speaking"></span>
					<span>Puhe aktiivinen</span>
				</div>
				<div class="gemini-audio-route-badge" id="gemini-audio-route-badge" title="Äänireititys Teamsiin / Meetiin virtuaalikaapelilla">
					<span>🔊 VB-Cable / Kaiuttimet</span>
				</div>
			</div>

			<div class="gemini-stage-visualizer" id="gemini-stage-visualizer">
				
				<?php if ( 'svg' === $avatar_type ) : ?>
					<!-- 2D STYLIZED VECTOR CHARACTER -->
					<div class="gemini-vector-avatar" id="gemini-vector-avatar">
						<svg viewBox="0 0 120 120" class="gemini-vector-svg">
							<!-- Head Outline -->
							<circle cx="60" cy="62" r="44" fill="#1e293b" stroke="#38bdf8" stroke-width="3" />
							<!-- Hair / Hat / Cap -->
							<path d="M20 54 Q60 14 100 54 Q60 36 20 54 Z" fill="#0f172a" />
							<path d="M12 55 Q60 40 108 55 Q60 48 12 55 Z" fill="#0284c7" />
							<!-- Eyes (Blinking animation) -->
							<g class="gemini-eye eye-left">
								<ellipse cx="44" cy="60" rx="6" ry="7" fill="#ffffff" />
								<circle cx="45" cy="60" r="3.5" fill="#0284c7" />
								<circle cx="43" cy="58" r="1.2" fill="#ffffff" />
							</g>
							<g class="gemini-eye eye-right">
								<ellipse cx="76" cy="60" rx="6" ry="7" fill="#ffffff" />
								<circle cx="75" cy="60" r="3.5" fill="#0284c7" />
								<circle cx="73" cy="58" r="1.2" fill="#ffffff" />
							</g>
							<!-- Glasses Frame (Tech aesthetic) -->
							<rect x="34" y="52" width="20" height="15" rx="3" fill="none" stroke="#38bdf8" stroke-width="2" />
							<rect x="66" y="52" width="20" height="15" rx="3" fill="none" stroke="#38bdf8" stroke-width="2" />
							<line x1="54" y1="58" x2="66" y2="58" stroke="#38bdf8" stroke-width="2" />
							<!-- Audio-Reactive Mouth -->
							<path id="gemini-vector-mouth" class="gemini-vector-mouth" d="M48 84 Q60 88 72 84 Q60 90 48 84 Z" fill="#f43f5e" stroke="#e11d48" stroke-width="1.5" />
						</svg>
					</div>
				<?php else : ?>
					<!-- PHOTO AVATAR WITH HOLOGRAPHIC EQUALIZER HUD -->
					<div class="gemini-photo-avatar-portal" id="gemini-photo-avatar-portal">
						<div class="gemini-portal-ring ring-outer" id="gemini-portal-ring-outer"></div>
						<div class="gemini-portal-ring ring-inner" id="gemini-portal-ring-inner"></div>
						<div class="gemini-portal-photo-wrap">
							<img src="<?php echo esc_url( $avatar_url ); ?>" alt="Matti Kiviharju" class="gemini-portal-img" id="gemini-portal-img" />
							<!-- Dynamic stylized mouth reactive overlay -->
							<div class="gemini-mouth-overlay" id="gemini-mouth-overlay"></div>
						</div>
					</div>
				<?php endif; ?>

				<!-- Frequency Visualizer Equalizer Waves -->
				<div class="gemini-audio-waves" id="gemini-audio-waves" aria-hidden="true">
					<span class="wave-bar bar-1"></span>
					<span class="wave-bar bar-2"></span>
					<span class="wave-bar bar-3"></span>
					<span class="wave-bar bar-4"></span>
					<span class="wave-bar bar-5"></span>
					<span class="wave-bar bar-6"></span>
					<span class="wave-bar bar-7"></span>
					<span class="wave-bar bar-8"></span>
				</div>

				<!-- Live Speech Playback Controls (When speaking or paused) -->
				<div class="gemini-stage-speech-ctrl" id="gemini-stage-speech-ctrl" style="display:none;">
					<button type="button" class="gemini-stage-btn btn-pause" id="gemini-stage-pause-btn" title="Tauko / Jatka">
						<svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor">
							<rect x="6" y="4" width="4" height="16" rx="1"></rect>
							<rect x="14" y="4" width="4" height="16" rx="1"></rect>
						</svg>
						<span id="gemini-stage-pause-text">Tauko</span>
					</button>
					<button type="button" class="gemini-stage-btn btn-stop" id="gemini-stage-stop-btn" title="Pysäytä puhe välittömästi">
						<svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor">
							<rect x="6" y="6" width="12" height="12" rx="2"></rect>
						</svg>
						<span>Pysäytä puhe</span>
					</button>
				</div>
			</div>
		</section>

		<!-- HUMAN-IN-THE-LOOP ANSWER PREVIEW & EDITING CARD -->
		<section class="gemini-preview-card" id="gemini-preview-card" style="display:none;" aria-label="Ehdotettu vastauspuheenvuoro">
			<div class="gemini-preview-header">
				<div class="gemini-preview-title">
					<span class="gemini-preview-icon">✨</span>
					<strong>Ehdotettu puheenvuoro (Muokkaa tarvittaessa)</strong>
				</div>
				<span class="gemini-preview-duration" id="gemini-preview-duration">~40s</span>
			</div>
			
			<div class="gemini-preview-body">
				<textarea id="gemini-preview-textarea" class="gemini-preview-textarea" rows="4" aria-label="Muokkaa puheenvuoroa ennen hyväksymistä"></textarea>
				<div class="gemini-preview-meta">
					<span id="gemini-preview-char-count">0 merkkiä</span>
					<span class="gemini-preview-hint">Tarkista ja muokkaa tekstiä vapaasti ennen puhetta.</span>
				</div>
			</div>

			<div class="gemini-preview-actions">
				<!-- Approve & Speak Button -->
				<button type="button" class="gemini-action-btn btn-approve" id="gemini-btn-approve-speak" title="Hyväksy vastaus ja aloita puhesynteesi kokoukseen">
					<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
						<polyline points="20 6 9 17 4 12"></polyline>
					</svg>
					<span>Hyväksy ja puhu kokoukseen</span>
				</button>

				<!-- Pause/Resume Button -->
				<button type="button" class="gemini-action-btn btn-secondary" id="gemini-btn-pause-toggle" style="display:none;" title="Keskeytä tai jatka puhetta">
					<span id="gemini-pause-btn-text">⏸ Tauko</span>
				</button>

				<!-- Replay Button -->
				<button type="button" class="gemini-action-btn btn-secondary" id="gemini-btn-replay-preview" style="display:none;" title="Toista puhe uudelleen">
					<span>🔁 Toista</span>
				</button>

				<!-- Emergency Stop Button -->
				<button type="button" class="gemini-action-btn btn-danger" id="gemini-btn-emergency-stop" style="display:none;" title="Hätäpysäytys: lopeta puhe heti">
					<span>⏹ Pysäytä</span>
				</button>

				<!-- Dismiss / Cancel Button -->
				<button type="button" class="gemini-action-btn btn-dismiss" id="gemini-btn-dismiss-preview" title="Hylkää tämä ehdotus">
					<span>✕ Hylkää</span>
				</button>
			</div>
		</section>

		<!-- PRIVACY NOTICE BANNER -->
		<div class="gemini-privacy-banner" id="gemini-privacy-banner">
			<span class="dashicons dashicons-shield"></span>
			<span id="gemini-privacy-text"><?php
				if ( 'sv' === $current_lang ) {
					echo 'Matti Kiviharjus AI-intervjuassistent. Svaren granskas och godkänns av Matti innan uppläsning. <a href="' . esc_url( $settings['privacy_policy_url'] ) . '" target="_blank" rel="noopener">Integritetspolicy</a>';
				} elseif ( 'en' === $current_lang ) {
					echo 'Matti Kiviharju’s AI Interview Assistant. Proposed answers are reviewed and approved by Matti before speaking. <a href="' . esc_url( $settings['privacy_policy_url'] ) . '" target="_blank" rel="noopener">Privacy Policy</a>';
				} else {
					echo 'Matti Kiviharjun AI-haastatteluavustaja. Vastaukset tarkistetaan ja hyväksytään manuaalisesti ennen puhetta. <a href="' . esc_url( $settings['privacy_policy_url'] ) . '" target="_blank" rel="noopener">Tietosuojaseloste</a>';
				}
			?></span>
		</div>

		<!-- MESSAGES CONTAINER -->
		<div class="gemini-drawer-body" id="gemini-drawer-body">
			<div class="gemini-messages-list" id="gemini-messages-list" role="log" aria-live="polite">
				<!-- Dynamically populated by avatar-widget.js -->
			</div>

			<!-- STARTER SUGGESTION CHIPS -->
			<div class="gemini-starter-chips" id="gemini-starter-chips" aria-label="Esimerkkikysymykset">
				<!-- Dynamically populated -->
			</div>
		</div>

		<!-- DRAWER FOOTER / INPUT -->
		<footer class="gemini-drawer-footer">
			<form id="gemini-chat-form" class="gemini-chat-form">
				<!-- Microphone Status Indicator -->
				<div class="gemini-mic-status" id="gemini-mic-status" style="display:none;">
					<span class="mic-pulse-dot"></span>
					<span class="mic-status-text">Kuunnellaan haastattelijaa... Puhu mikrofoniin</span>
					<button type="button" class="mic-cancel-btn" id="gemini-mic-cancel">&times;</button>
				</div>

				<div class="gemini-input-row">
					<!-- Voice Input (Mic) Button -->
					<?php if ( ! empty( $settings['enable_voice_input'] ) ) : ?>
						<button type="button" id="gemini-btn-mic" class="gemini-input-btn mic-btn" title="Käytä puheentunnistusta" aria-label="Käynnistä puheentunnistus">
							<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"></path>
								<path d="M19 10v2a7 7 0 0 1-14 0v-2"></path>
								<line x1="12" y1="19" x2="12" y2="23"></line>
								<line x1="8" y1="23" x2="16" y2="23"></line>
							</svg>
						</button>
					<?php endif; ?>

					<!-- Text Input Area -->
					<div class="gemini-textarea-wrap">
						<textarea id="gemini-chat-input" class="gemini-chat-input" rows="1" maxlength="<?php echo absint( $settings['max_input_chars'] ); ?>" placeholder="Syötä haastattelijan kysymys tai puhu mikrofoniin..." aria-label="Kirjoita kysymys Matin AI-avustajalle"></textarea>
						<span class="gemini-char-count" id="gemini-char-count">0/<?php echo absint( $settings['max_input_chars'] ); ?></span>
					</div>

					<!-- Send Button -->
					<button type="submit" id="gemini-btn-send" class="gemini-input-btn send-btn" title="Muotoile vastaus" aria-label="Lähetä">
						<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<line x1="22" y1="2" x2="11" y2="13"></line>
							<polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
						</svg>
					</button>
				</div>
			</form>
		</footer>

	</div>
</div>
