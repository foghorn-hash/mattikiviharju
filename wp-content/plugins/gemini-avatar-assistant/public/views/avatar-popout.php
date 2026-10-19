<?php
/**
 * Standalone Popout Avatar View
 * Optimized for Microsoft Teams, Google Meet, OBS Studio, and Isadora 3 Screen Capture.
 *
 * @package Gemini_Avatar_Assistant
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings   = Gemini_Avatar_Settings::get_all();
$avatar_url = ! empty( $settings['avatar_image_url'] ) ? $settings['avatar_image_url'] : '';
?>
<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>LiveAvatar — Matti Kiviharju (Teams / Meet / Isadora 3)</title>
	<style>
		* { margin: 0; padding: 0; box-sizing: border-box; }
		body {
			background: #070b14;
			color: #f1f5f9;
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			overflow: hidden;
			height: 100vh;
			display: flex;
			align-items: center;
			justify-content: center;
		}

		.popout-stage {
			position: relative;
			width: 100vw;
			height: 100vh;
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			background: radial-gradient(circle at center, #172554 0%, #0b1220 50%, #030712 100%);
		}

		/* BROADCAST HUD OVERLAY */
		.broadcast-hud {
			position: absolute;
			top: 20px;
			left: 24px;
			display: flex;
			align-items: center;
			gap: 10px;
			z-index: 10;
		}

		.live-badge {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			background: rgba(15, 23, 42, 0.85);
			border: 1px solid rgba(56, 189, 248, 0.4);
			padding: 6px 14px;
			border-radius: 9999px;
			font-size: 13px;
			font-weight: 600;
			letter-spacing: 0.5px;
			color: #38bdf8;
			backdrop-filter: blur(8px);
		}

		.live-dot {
			width: 9px;
			height: 9px;
			border-radius: 50%;
			background: #22c55e;
			box-shadow: 0 0 10px #22c55e;
			transition: background 0.3s, box-shadow 0.3s;
		}

		.live-dot.speaking {
			background: #ef4444;
			box-shadow: 0 0 14px #ef4444;
			animation: livePulse 1s infinite alternate;
		}

		@keyframes livePulse {
			0% { transform: scale(0.9); opacity: 0.8; }
			100% { transform: scale(1.3); opacity: 1; }
		}

		.popout-title-text {
			color: #94a3b8;
			font-size: 12px;
			letter-spacing: 0.5px;
		}

		/* AVATAR PORTAL */
		.avatar-portal {
			position: relative;
			width: 320px;
			height: 320px;
			display: flex;
			align-items: center;
			justify-content: center;
		}

		.portal-ring {
			position: absolute;
			border-radius: 50%;
			pointer-events: none;
			transition: transform 0.08s ease-out, border-color 0.2s ease;
		}

		.portal-ring.ring-outer {
			width: 350px;
			height: 350px;
			border: 2px dashed rgba(56, 189, 248, 0.35);
			animation: ringRotate 20s linear infinite;
		}

		.portal-ring.ring-inner {
			width: 328px;
			height: 328px;
			border: 2px solid rgba(56, 189, 248, 0.6);
			box-shadow: 0 0 35px rgba(56, 189, 248, 0.25);
		}

		@keyframes ringRotate {
			from { transform: rotate(0deg); }
			to { transform: rotate(360deg); }
		}

		.portal-img-wrap {
			position: relative;
			width: 290px;
			height: 290px;
			border-radius: 50%;
			overflow: hidden;
			background: #0f172a;
			box-shadow: 0 10px 40px rgba(0, 0, 0, 0.8);
		}

		.portal-img {
			width: 100%;
			height: 100%;
			object-fit: cover;
			display: block;
		}

		/* LIP-SYNC MOUTH OVERLAY */
		.mouth-overlay {
			position: absolute;
			bottom: 27%;
			left: 49%;
			transform: translateX(-50%) scale(1, 0.2);
			width: 34px;
			height: 14px;
			background: #111827;
			border-radius: 50%;
			opacity: 0;
			box-shadow: inset 0 2px 4px rgba(0,0,0,0.9), 0 0 8px rgba(244, 63, 94, 0.4);
			transition: transform 0.04s ease-out, opacity 0.05s ease-out;
			pointer-events: none;
		}

		/* FREQUENCY WAVES EQUALIZER */
		.audio-waves {
			position: absolute;
			bottom: 30px;
			display: flex;
			align-items: flex-end;
			gap: 6px;
			height: 40px;
		}

		.wave-bar {
			width: 6px;
			height: 4px;
			background: linear-gradient(to top, #38bdf8, #818cf8);
			border-radius: 4px;
			transition: height 0.06s ease;
		}

		/* BROADCAST WATERMARK */
		.broadcast-watermark {
			position: absolute;
			bottom: 20px;
			right: 24px;
			font-size: 11px;
			color: rgba(148, 163, 184, 0.5);
			letter-spacing: 1px;
			text-transform: uppercase;
		}
	</style>
</head>
<body>
	<div class="popout-stage">
		<!-- BROADCAST STATUS HUD -->
		<div class="broadcast-hud">
			<div class="live-badge">
				<span class="live-dot" id="live-dot"></span>
				<span id="live-label">LIVEAVATAR • VALMIINA</span>
			</div>
			<span class="popout-title-text">Matti Kiviharju | i4ware</span>
		</div>

		<!-- AVATAR PORTAL WITH AUDIO-REACTIVE LIP-SYNC -->
		<div class="avatar-portal">
			<div class="portal-ring ring-outer" id="popout-ring-outer"></div>
			<div class="portal-ring ring-inner" id="popout-ring-inner"></div>
			<div class="portal-img-wrap">
				<img src="<?php echo esc_url( $avatar_url ); ?>" alt="Matti Kiviharju" class="portal-img" />
				<div class="mouth-overlay" id="popout-mouth"></div>
			</div>
		</div>

		<!-- FREQUENCY EQUALIZER BARS -->
		<div class="audio-waves" id="popout-waves">
			<span class="wave-bar"></span>
			<span class="wave-bar"></span>
			<span class="wave-bar"></span>
			<span class="wave-bar"></span>
			<span class="wave-bar"></span>
			<span class="wave-bar"></span>
			<span class="wave-bar"></span>
			<span class="wave-bar"></span>
			<span class="wave-bar"></span>
			<span class="wave-bar"></span>
		</div>

		<div class="broadcast-watermark">
			Google Gemini AI Avatar • TroikaTronix Isadora 3 / OBS Studio
		</div>
	</div>

	<script>
		(function () {
			const liveDot = document.getElementById('live-dot');
			const liveLabel = document.getElementById('live-label');
			const mouth = document.getElementById('popout-mouth');
			const ringOuter = document.getElementById('popout-ring-outer');
			const ringInner = document.getElementById('popout-ring-inner');
			const waveBars = document.querySelectorAll('#popout-waves .wave-bar');

			function updateAvatarState(data) {
				if (!data) return;

				const isSpeaking = !!data.isSpeaking;
				const amplitude = typeof data.amplitude === 'number' ? data.amplitude : 0;

				if (isSpeaking) {
					liveDot.className = 'live-dot speaking';
					liveLabel.textContent = 'LIVEAVATAR • PUHE AKTIIVINEN';
					liveLabel.style.color = '#f43f5e';
				} else {
					liveDot.className = 'live-dot';
					liveLabel.textContent = 'LIVEAVATAR • VALMIUSTILASSA';
					liveLabel.style.color = '#38bdf8';
				}

				// Animate mouth
				if (mouth) {
					if (isSpeaking && amplitude > 0.04) {
						const scaleY = Math.max(0.3, amplitude * 3.2);
						const scaleX = 1 + amplitude * 0.5;
						mouth.style.transform = 'translateX(-50%) scale(' + scaleX + ', ' + scaleY + ')';
						mouth.style.opacity = String(0.4 + amplitude * 0.6);
					} else {
						mouth.style.transform = 'translateX(-50%) scale(1, 0.2)';
						mouth.style.opacity = '0';
					}
				}

				// Animate rings
				if (ringOuter) {
					const rScale = 1 + amplitude * 0.15;
					ringOuter.style.transform = 'scale(' + rScale + ')';
				}
				if (ringInner) {
					ringInner.style.boxShadow = isSpeaking
						? '0 0 ' + (30 + amplitude * 40) + 'px rgba(56, 189, 248, 0.7)'
						: '0 0 35px rgba(56, 189, 248, 0.25)';
				}

				// Animate wave bars
				if (waveBars.length) {
					waveBars.forEach((bar, idx) => {
						const offset = Math.sin(idx * 0.7 + Date.now() * 0.01);
						const h = isSpeaking ? Math.max(4, Math.round(amplitude * 36 + offset * 8)) : 4;
						bar.style.height = h + 'px';
					});
				}
			}

			// BroadcastChannel synchronization across tabs/windows
			if (typeof BroadcastChannel !== 'undefined') {
				const bc = new BroadcastChannel('gemini_avatar_sync');
				bc.onmessage = function (evt) {
					updateAvatarState(evt.data);
				};
			}

			// Window postMessage fallback
			window.addEventListener('message', function (evt) {
				if (evt.data && evt.data.type === 'gemini_avatar_sync') {
					updateAvatarState(evt.data);
				}
			});

			// Fullscreen on 'F' keypress
			window.addEventListener('keydown', function (e) {
				if (e.key === 'f' || e.key === 'F') {
					if (!document.fullscreenElement) {
						document.documentElement.requestFullscreen().catch(() => {});
					} else {
						document.exitFullscreen().catch(() => {});
					}
				}
			});
		})();
	</script>
</body>
</html>
