/**
 * WooCommerce Dynamic Notice Toasts - Admin Settings & Live Preview Controller
 *
 * @package WooCommerce_Toasts
 */

(function ($) {
	'use strict';

	var adminData = window.wc_toasts_admin_params || {};
	var i18n = adminData.i18n || {};
	var icons = adminData.icons || {};

	/**
	 * Web Audio Synthesizer for Admin Sound Preview
	 */
	var AdminAudio = (function () {
		var audioCtx = null;

		function getAudioContext() {
			if (!audioCtx && (window.AudioContext || window.webkitAudioContext)) {
				var AudioContextClass = window.AudioContext || window.webkitAudioContext;
				audioCtx = new AudioContextClass();
			}
			if (audioCtx && audioCtx.state === 'suspended') {
				audioCtx.resume();
			}
			return audioCtx;
		}

		function play(type) {
			var ctx = getAudioContext();
			if (!ctx) return;

			var volInput = document.getElementById('wc-toasts-vol-range');
			var volume = volInput ? parseInt(volInput.value, 10) : 40;
			var themeSelect = document.getElementById('wc-toasts-sound-theme');
			var theme = themeSelect ? themeSelect.value : 'gentle_chime';

			var masterGain = ctx.createGain();
			masterGain.gain.setValueAtTime((volume / 100) * 0.35, ctx.currentTime);
			masterGain.connect(ctx.destination);

			if (theme === 'crisp_pop') {
				var osc = ctx.createOscillator();
				var gain = ctx.createGain();
				osc.type = 'sine';
				osc.frequency.setValueAtTime(450, ctx.currentTime);
				osc.frequency.exponentialRampToValueAtTime(120, ctx.currentTime + 0.08);
				gain.gain.setValueAtTime(0.35, ctx.currentTime);
				gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.09);
				osc.connect(gain);
				gain.connect(masterGain);
				osc.start(ctx.currentTime);
				osc.stop(ctx.currentTime + 0.09);
			} else if (theme === 'modern_ping') {
				var pOsc = ctx.createOscillator();
				var pGain = ctx.createGain();
				pOsc.type = 'sine';
				var freq = (type === 'error') ? 380 : 880;
				pOsc.frequency.setValueAtTime(freq, ctx.currentTime);
				pOsc.frequency.exponentialRampToValueAtTime(freq * 0.7, ctx.currentTime + 0.28);
				pGain.gain.setValueAtTime(0.32, ctx.currentTime);
				pGain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.3);
				pOsc.connect(pGain);
				pGain.connect(masterGain);
				pOsc.start(ctx.currentTime);
				pOsc.stop(ctx.currentTime + 0.3);
			} else if (theme === 'bell_ding') {
				[880, 1760, 2640].forEach(function (f, i) {
					var bOsc = ctx.createOscillator();
					var bGain = ctx.createGain();
					bOsc.type = 'sine';
					bOsc.frequency.setValueAtTime(f, ctx.currentTime);
					bGain.gain.setValueAtTime(0.25 / (i + 1.5), ctx.currentTime);
					bGain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.8);
					bOsc.connect(bGain);
					bGain.connect(masterGain);
					bOsc.start(ctx.currentTime);
					bOsc.stop(ctx.currentTime + 0.8);
				});
			} else if (theme === 'subtle_click') {
				var cOsc = ctx.createOscillator();
				var cGain = ctx.createGain();
				cOsc.type = 'triangle';
				cOsc.frequency.setValueAtTime(1400, ctx.currentTime);
				cGain.gain.setValueAtTime(0.2, ctx.currentTime);
				cGain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.035);
				cOsc.connect(cGain);
				cGain.connect(masterGain);
				cOsc.start(ctx.currentTime);
				cOsc.stop(ctx.currentTime + 0.035);
			} else {
				// Gentle Chime.
				var notes = (type === 'error') ? [349.23, 311.13] : [659.25, 880, 1046.5];
				notes.forEach(function (freq, index) {
					var osc = ctx.createOscillator();
					var gain = ctx.createGain();
					var startTime = ctx.currentTime + (index * 0.07);
					osc.type = (type === 'error') ? 'triangle' : 'sine';
					osc.frequency.setValueAtTime(freq, startTime);
					gain.gain.setValueAtTime(0, startTime);
					gain.gain.linearRampToValueAtTime(0.28, startTime + 0.015);
					gain.gain.exponentialRampToValueAtTime(0.0001, startTime + 0.55);
					osc.connect(gain);
					gain.connect(masterGain);
					osc.start(startTime);
					osc.stop(startTime + 0.55);
				});
			}
		}

		return {
			play: play
		};
	})();

	$(document).ready(function () {
		// 1. Initialize Color Pickers.
		if ($.fn.wpColorPicker) {
			$('.wc-toasts-color-picker').wpColorPicker({
				change: function () {
					updateLivePreviewStyles();
				}
			});
		}

		// 2. Tab Navigation.
		$('.wc-toasts-tab-link').on('click', function (e) {
			e.preventDefault();
			var tabId = $(this).data('tab');

			$('.wc-toasts-tab-link').removeClass('nav-tab-active');
			$(this).addClass('nav-tab-active');

			$('.wc-toasts-tab-content').removeClass('active');
			$('#tab-' + tabId).addClass('active');

			$('#wc-toasts-current-tab').val(tabId);
			if (history.pushState) {
				history.pushState(null, null, '#tab=' + tabId);
			}
		});

		// Check URL hash on page load for active tab.
		if (window.location.hash) {
			var hashMatch = window.location.hash.match(/#tab=([a-z_-]+)/);
			if (hashMatch && hashMatch[1]) {
				var targetTab = hashMatch[1];
				var $tabLink = $('.wc-toasts-tab-link[data-tab="' + targetTab + '"]');
				if ($tabLink.length) {
					$tabLink.trigger('click');
				}
			}
		}

		// 3. Position Cards selection.
		$('.wc-toasts-pos-card').on('click', function () {
			$('.wc-toasts-pos-card').removeClass('selected');
			$(this).addClass('selected');
			var posVal = $(this).find('input').val();
			$('#stage-toast-slot').removeClass('pos-top-right pos-top-left pos-top-center pos-bottom-right pos-bottom-left pos-bottom-center')
				.addClass('pos-' + posVal);
		});

		// 4. Sliders Live Feedback.
		$('#wc-toasts-radius-range').on('input', function () {
			var val = $(this).val();
			$('#wc-toasts-radius-val').text(val + 'px');
			updateLivePreviewStyles();
		});

		$('#wc-toasts-vol-range').on('input', function () {
			var val = $(this).val();
			$('#wc-toasts-vol-val').text(val + '%');
		});

		// 5. Sound Chime Test Buttons.
		$('#wc-toasts-btn-test-chime').on('click', function () {
			AdminAudio.play('success');
		});

		$('#wc-toasts-sound-theme').on('change', function () {
			AdminAudio.play('success');
		});

		// 6. Preview Buttons Click Handler.
		$('.preview-btn').on('click', function () {
			var cat = $(this).data('cat');
			var type = $(this).data('type');
			triggerTestNotice(cat, type);
		});

		$('#wc-toasts-btn-quick-preview').on('click', function () {
			triggerTestNotice('cart', 'success');
		});

		// 7. Reset Defaults AJAX handler.
		$('#wc-toasts-btn-reset-defaults').on('click', function () {
			if (!confirm(i18n.reset_confirm || 'Are you sure you want to reset all toast settings to factory defaults?')) {
				return;
			}

			var $btn = $(this);
			$btn.prop('disabled', true);

			$.ajax({
				url: adminData.ajax_url,
				type: 'POST',
				data: {
					action: 'wc_toasts_reset_settings',
					security: adminData.nonce
				},
				success: function (res) {
					if (res.success) {
						alert(res.data.message || i18n.reset_done);
						window.location.reload();
					} else {
						alert(res.data.message || 'Error resetting settings.');
						$btn.prop('disabled', false);
					}
				},
				error: function () {
					alert('Connection error occurred.');
					$btn.prop('disabled', false);
				}
			});
		});

		// Trigger initial sample preview on load.
		setTimeout(function () {
			triggerTestNotice('cart', 'success');
		}, 300);
	});

	/**
	 * Trigger a test notice via AJAX and display in the simulator stage.
	 *
	 * @param {string} cat  Category.
	 * @param {string} type Notice type.
	 */
	function triggerTestNotice(cat, type) {
		$.ajax({
			url: adminData.ajax_url,
			type: 'POST',
			data: {
				action: 'wc_toasts_test_notice',
				security: adminData.nonce,
				category: cat,
				type: type
			},
			success: function (response) {
				if (response.success && response.data && response.data.notice) {
					renderStageToast(response.data.notice);
					AdminAudio.play(type);
				}
			}
		});
	}

	/**
	 * Render a toast inside the admin mockup stage.
	 *
	 * @param {Object} notice Notice payload.
	 */
	function renderStageToast(notice) {
		var $slot = $('#stage-toast-slot');
		$slot.empty();

		var theme = $('#wc-toasts-theme-select').val() || 'modern-light';
		var radius = $('#wc-toasts-radius-range').val() || '12';
		var animIn = $('#wc-toasts-anim-in').val() || 'slide-in';
		var iconKey = notice.icon || 'info';

		var $toast = $('<div>', {
			class: 'wc-toast type-' + notice.type + ' anim-' + animIn,
			css: {
				borderRadius: radius + 'px'
			}
		});

		// Icon.
		var $iconWrap = $('<div>', { class: 'wc-toast-icon-wrap' });
		$iconWrap.html(icons[iconKey] || icons.info || '');
		$toast.append($iconWrap);

		// Content.
		var $content = $('<div>', { class: 'wc-toast-content' });
		$content.append($('<div>', { class: 'wc-toast-title', text: notice.title }));
		$content.append($('<div>', { class: 'wc-toast-message', text: notice.message }));

		if (notice.action && notice.action.text) {
			var $actWrap = $('<div>', { class: 'wc-toast-action' });
			$actWrap.append($('<a>', {
				class: 'wc-toast-action-btn',
				href: '#preview',
				text: notice.action.text,
				click: function (e) { e.preventDefault(); }
			}));
			$content.append($actWrap);
		}
		$toast.append($content);

		// Close button.
		var $close = $('<button>', {
			type: 'button',
			class: 'wc-toast-close-btn',
			html: icons.close || '&times;',
			click: function () {
				$toast.fadeOut(200, function () { $toast.remove(); });
			}
		});
		$toast.append($close);

		// Progress bar.
		var $progress = $('<div>', { class: 'wc-toast-progress-bar' });
		$toast.append($progress);

		$slot.append($toast);
	}

	/**
	 * Update live preview styles dynamically.
	 */
	function updateLivePreviewStyles() {
		var radius = $('#wc-toasts-radius-range').val();
		$('#stage-toast-slot .wc-toast').css('border-radius', radius + 'px');
	}

})(jQuery);
