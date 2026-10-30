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
	var styleData = adminData.styling || { palettes: {}, tokens: {} };
	var lastNotice;

	$(document).ready(function () {
		// 1. Initialize Color Pickers.
		if ($.fn.wpColorPicker) {
			$('.wc-toasts-color-picker').wpColorPicker({
				change: function () {
					setTimeout(updateLivePreviewStyles, 0);
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

		// 3. Position & Theme Live Feedback.
		$('.wc-toasts-pos-card').on('click', function () {
			$('.wc-toasts-pos-card').removeClass('selected');
			$(this).addClass('selected');
			var posVal = $(this).find('input').val();
			$('#stage-toast-slot').removeClass('pos-top-right pos-top-left pos-top-center pos-bottom-right pos-bottom-left pos-bottom-center')
				.addClass('pos-' + posVal);
		});

		$('#wc-toasts-theme-select').on('change', function () {
			var themeVal = $(this).val();
			var $slot = $('#stage-toast-slot');
			$slot.attr('class', function (i, c) {
				return (c || '').replace(/(^|\s)theme-\S+/g, '');
			}).addClass('theme-' + themeVal);
			updateLivePreviewStyles();
		});
		$('#wc-toasts-layout-select').on('change', function () { if (lastNotice) renderStageToast(lastNotice); });
		$('#wc-toasts-palette-select').on('change', updateLivePreviewStyles);
		$('.wc-toasts-token-input').on('input change', updateLivePreviewStyles);
		$('#wc-toasts-reset-colors').on('click', function () {
			$('.wc-toasts-token-input').each(function () { $(this).val(''); if ($.fn.wpColorPicker) $(this).wpColorPicker('color', ''); });
			updateLivePreviewStyles();
		});

		// 4. Sliders Live Feedback.
		$('#wc-toasts-radius-range').on('input', function () {
			var val = $(this).val();
			$('#wc-toasts-radius-val').text(val + 'px');
			updateLivePreviewStyles();
		});

		// 5. Preview Buttons Click Handler.
		$('.preview-btn').on('click', function () {
			var cat = $(this).data('cat');
			var type = $(this).data('type');
			triggerTestNotice(cat, type);
		});

		$('#wc-toasts-btn-quick-preview').on('click', function () {
			triggerTestNotice('cart', 'success');
		});

		// 6. Reset Defaults AJAX handler.
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
						alert(res.data.message || i18n.reset_error || 'Error resetting settings.');
						$btn.prop('disabled', false);
					}
				},
				error: function () {
					alert(i18n.connection_error || 'Connection error occurred.');
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
		lastNotice = notice;
		var layout = $('#wc-toasts-layout-select').val() || 'retro-outline';
		var $slot = $('#stage-toast-slot');
		$slot.empty();

		var radius = $('#wc-toasts-radius-range').val() || 12;
		var pos = $('.wc-toasts-pos-card.selected input').val() || 'bottom-left';
		var animClass = (pos.indexOf('left') !== -1) ? 'anim-horizontal-in-left' : 'anim-horizontal-in-right';
		var exitClass = (pos.indexOf('left') !== -1) ? 'anim-horizontal-out-left' : 'anim-horizontal-out-right';
		var iconKey = notice.icon || 'info';

		var $toast = $('<div>', {
			class: 'wc-toast type-' + notice.type + ' ' + animClass,
			css: {
				borderRadius: radius + 'px'
			}
		});

		// 1. Status icon.
		var $iconWrap = $('<div>', { class: 'wc-toast-icon-wrap' });
		$iconWrap.attr('aria-hidden', 'true');
		$iconWrap.html(icons[iconKey] || icons.info || '');
		$toast.append($iconWrap);

		// 2. Content area.
		var $content = $('<div>', { class: 'wc-toast-content' });

		// Category label.
		var eyebrowText = '';
		if (notice.category) {
			eyebrowText = notice.cat_name || (adminData.categories[notice.category] && adminData.categories[notice.category].name) || notice.category.replace(/[_-]/g, ' ');
		} else if (notice.type) {
			eyebrowText = notice.type === 'notice' ? 'Notice' : notice.type;
		}
		if (eyebrowText) {
			$content.append($('<div>', { class: 'wc-toast-eyebrow', text: eyebrowText }));
		}

		$content.append($('<div>', { class: 'wc-toast-title', text: notice.title }));
		$content.append($('<div>', { class: 'wc-toast-message', text: notice.message }));

		if (notice.action && notice.action.text) {
			var $actWrap = $('<div>', { class: 'wc-toast-action' });
			var $action = $('<a>', {
				class: 'wc-toast-action-btn',
				href: '#preview',
				text: notice.action.text,
				click: function (e) { e.preventDefault(); }
			});
			$action.append($('<span>', { class: 'arrow-icon', 'aria-hidden': 'true', text: '→' }));
			$actWrap.append($action);
			if (layout !== 'utility' && layout !== 'retro-outline') $content.append($actWrap);
		}
		$toast.append($content);
		if ($actWrap && (layout === 'utility' || layout === 'retro-outline')) $toast.append($actWrap);

		// 3. Close button.
		var $close = $('<button>', {
			type: 'button',
			class: 'wc-toast-close-btn',
			'aria-label': i18n.close || 'Dismiss preview notice',
			click: function () {
				$toast.removeClass('anim-horizontal-in-right anim-horizontal-in-left').addClass(exitClass);
				setTimeout(function () { $toast.remove(); }, 240);
			}
		});
		$toast.append($close);

		// 4. Progress bar.
		var $progress = $('<div>', { class: 'wc-toast-progress-bar' });
		$toast.append($progress);

		$slot.append($toast);
		var $editorToast = $toast.clone(false).removeClass('anim-horizontal-in-left anim-horizontal-in-right');
		$editorToast.find('.wc-toast-close-btn').on('click', function () { $(this).closest('.wc-toast').hide(); });
		$('#wc-toasts-editor-preview').empty().append($editorToast);
		updateLivePreviewStyles();
	}

	/**
	 * Update live preview styles dynamically.
	 */
	function updateLivePreviewStyles() {
		var radius = $('#wc-toasts-radius-range').val();
		$('#stage-toast-slot .wc-toast').css('border-radius', radius + 'px');
		var slot = document.getElementById('stage-toast-slot');
		if (!slot) return;
		var colors = {
			'color_success_bg': '--wc-toast-success-bg',
			'color_success_text': '--wc-toast-success-text',
			'color_success_accent': '--wc-toast-accent-success',
			'color_error_bg': '--wc-toast-error-bg',
			'color_error_text': '--wc-toast-error-text',
			'color_error_accent': '--wc-toast-accent-error',
			'color_info_bg': '--wc-toast-info-bg',
			'color_info_text': '--wc-toast-info-text',
			'color_info_accent': '--wc-toast-accent-info'
		};
		Object.keys(colors).forEach(function (key) {
			var value = $('[name="wc_toasts[' + key + ']"]').val();
			if (value) slot.style.setProperty(colors[key], value);
		});
		var overrides = {};
		$('.wc-toasts-token-input').each(function () { overrides[this.dataset.token] = this.value; });
		var layout = $('#wc-toasts-layout-select').val() || 'retro-outline';
		[slot, document.getElementById('wc-toasts-editor-preview')].forEach(function (element) {
			if (!element) return;
			element.className = element.className.replace(/\blayout-\S+/g, '') + ' layout-' + layout;
			window.WCToastStyling.apply(element, styleData.palettes[$('#wc-toasts-palette-select').val()], overrides, styleData.tokens, layout);
			element.querySelectorAll('.wc-toast').forEach(function (toast) { window.WCToastStyling.apply(toast, styleData.palettes[$('#wc-toasts-palette-select').val()], overrides, styleData.tokens, layout); });
		});
	}

})(jQuery);
