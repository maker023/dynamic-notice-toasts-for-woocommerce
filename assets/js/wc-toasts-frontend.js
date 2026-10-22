/**
 * WooCommerce Dynamic Notice Toasts - Frontend Toast Engine
 *
 * Intercepts WooCommerce notices across page loads, jQuery events, AJAX requests,
 * and DOM mutations, transforming them into modern, animated dynamic toasts.
 *
 * @package WooCommerce_Toasts
 */

(function (window, document) {
	'use strict';

	// Retrieve localized parameters.
	var config = window.wc_toasts_frontend_params || {};
	var settings = config.settings || {};
	var icons = config.icons || {};
	var i18n = config.i18n || {};

	// If plugin is disabled, exit.
	if (settings.enabled === 0 || settings.enabled === '0' || settings.enabled === false) {
		return;
	}

	/**
	 * Web Audio API Synthesizer
	 */
	var AudioEngine = (function () {
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

		function playSound(type, customVolume) {
			if (!settings.sound_enabled) {
				return;
			}

			var ctx = getAudioContext();
			if (!ctx) {
				return;
			}

			var volPercent = (customVolume !== undefined ? customVolume : (settings.sound_volume || 40)) / 100;
			var masterGain = ctx.createGain();
			masterGain.gain.setValueAtTime(volPercent * 0.35, ctx.currentTime);
			masterGain.connect(ctx.destination);

			var theme = settings.sound_theme || 'gentle_chime';

			switch (theme) {
				case 'crisp_pop':
					playPop(ctx, masterGain);
					break;
				case 'modern_ping':
					playPing(ctx, masterGain, type === 'error' ? 380 : 880);
					break;
				case 'bell_ding':
					playBell(ctx, masterGain);
					break;
				case 'subtle_click':
					playClick(ctx, masterGain);
					break;
				case 'gentle_chime':
				default:
					if (type === 'error') {
						playErrorChord(ctx, masterGain);
					} else {
						playChime(ctx, masterGain);
					}
					break;
			}
		}

		function playChime(ctx, destination) {
			var notes = [659.25, 880, 1046.5]; // E5, A5, C6 arpeggio.
			notes.forEach(function (freq, index) {
				var osc = ctx.createOscillator();
				var gain = ctx.createGain();
				var startTime = ctx.currentTime + (index * 0.07);

				osc.type = 'sine';
				osc.frequency.setValueAtTime(freq, startTime);

				gain.gain.setValueAtTime(0, startTime);
				gain.gain.linearRampToValueAtTime(0.28, startTime + 0.015);
				gain.gain.exponentialRampToValueAtTime(0.0001, startTime + 0.55);

				osc.connect(gain);
				gain.connect(destination);

				osc.start(startTime);
				osc.stop(startTime + 0.55);
			});
		}

		function playErrorChord(ctx, destination) {
			var freqs = [349.23, 311.13]; // Low dissonance.
			freqs.forEach(function (freq) {
				var osc = ctx.createOscillator();
				var gain = ctx.createGain();

				osc.type = 'triangle';
				osc.frequency.setValueAtTime(freq, ctx.currentTime);

				gain.gain.setValueAtTime(0.3, ctx.currentTime);
				gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.45);

				osc.connect(gain);
				gain.connect(destination);

				osc.start(ctx.currentTime);
				osc.stop(ctx.currentTime + 0.45);
			});
		}

		function playPing(ctx, destination, freq) {
			var osc = ctx.createOscillator();
			var gain = ctx.createGain();

			osc.type = 'sine';
			osc.frequency.setValueAtTime(freq, ctx.currentTime);
			osc.frequency.exponentialRampToValueAtTime(freq * 0.7, ctx.currentTime + 0.28);

			gain.gain.setValueAtTime(0.32, ctx.currentTime);
			gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.3);

			osc.connect(gain);
			gain.connect(destination);

			osc.start(ctx.currentTime);
			osc.stop(ctx.currentTime + 0.3);
		}

		function playPop(ctx, destination) {
			var osc = ctx.createOscillator();
			var gain = ctx.createGain();

			osc.type = 'sine';
			osc.frequency.setValueAtTime(450, ctx.currentTime);
			osc.frequency.exponentialRampToValueAtTime(120, ctx.currentTime + 0.08);

			gain.gain.setValueAtTime(0.35, ctx.currentTime);
			gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.09);

			osc.connect(gain);
			gain.connect(destination);

			osc.start(ctx.currentTime);
			osc.stop(ctx.currentTime + 0.09);
		}

		function playBell(ctx, destination) {
			var harmonics = [880, 1760, 2640];
			harmonics.forEach(function (freq, i) {
				var osc = ctx.createOscillator();
				var gain = ctx.createGain();
				var factor = 1 / (i + 1.5);

				osc.type = 'sine';
				osc.frequency.setValueAtTime(freq, ctx.currentTime);

				gain.gain.setValueAtTime(0.25 * factor, ctx.currentTime);
				gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.8);

				osc.connect(gain);
				gain.connect(destination);

				osc.start(ctx.currentTime);
				osc.stop(ctx.currentTime + 0.8);
			});
		}

		function playClick(ctx, destination) {
			var osc = ctx.createOscillator();
			var gain = ctx.createGain();

			osc.type = 'triangle';
			osc.frequency.setValueAtTime(1400, ctx.currentTime);

			gain.gain.setValueAtTime(0.2, ctx.currentTime);
			gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.035);

			osc.connect(gain);
			gain.connect(destination);

			osc.start(ctx.currentTime);
			osc.stop(ctx.currentTime + 0.035);
		}

		return {
			play: playSound
		};
	})();

	/**
	 * Toast Manager Engine
	 */
	var ToastManager = {
		container: null,
		activeToasts: [],
		recentNoticeHashes: {},

		/**
		 * Initialize Toast Engine
		 */
		init: function () {
			this.ensureContainer();
			this.processInitialNotices();
			this.bindWooCommerceEvents();
			this.bindMutationObserver();
			this.bindAjaxInterception();
		},

		/**
		 * Ensure fixed container exists in the DOM.
		 */
		ensureContainer: function () {
			if (this.container && document.body.contains(this.container)) {
				return this.container;
			}

			var position = settings.position || 'top-right';
			var theme = settings.theme || 'modern-light';
			var shadow = settings.box_shadow || 'medium';

			var container = document.createElement('div');
			container.id = 'wc-toasts-container';
			container.className = 'wc-toasts-container pos-' + position + ' theme-' + theme + ' shadow-' + shadow;
			container.setAttribute('aria-live', 'polite');
			container.setAttribute('aria-atomic', 'true');

			if (settings.mobile_bottom) {
				container.classList.add('mobile-dock-bottom');
			}

			// Apply custom styling overrides if custom theme selected.
			if (theme === 'custom') {
				if (settings.color_success_bg) container.style.setProperty('--wc-toast-bg', settings.color_success_bg);
				if (settings.color_success_text) container.style.setProperty('--wc-toast-text', settings.color_success_text);
				if (settings.color_success_accent) container.style.setProperty('--wc-toast-accent-success', settings.color_success_accent);
				if (settings.color_error_accent) container.style.setProperty('--wc-toast-accent-error', settings.color_error_accent);
				if (settings.color_info_accent) container.style.setProperty('--wc-toast-accent-info', settings.color_info_accent);
			}

			if (settings.border_radius !== undefined) {
				container.style.setProperty('--wc-toast-radius', settings.border_radius + 'px');
			}
			if (settings.toast_width) {
				container.style.setProperty('--wc-toast-width', settings.toast_width + 'px');
			}

			document.body.appendChild(container);
			this.container = container;
			return container;
		},

		/**
		 * Render initial notices supplied by PHP server interception.
		 */
		processInitialNotices: function () {
			var self = this;
			var notices = config.initial_notices || [];

			if (window.__wc_toasts_pending_notices && window.__wc_toasts_pending_notices.length) {
				notices = notices.concat(window.__wc_toasts_pending_notices);
				window.__wc_toasts_pending_notices = [];
			}

			if (Array.isArray(notices) && notices.length > 0) {
				notices.forEach(function (notice, index) {
					// Stagger appearance slightly for multiple initial notices.
					setTimeout(function () {
						self.show(notice);
					}, index * 120);
				});
			}
		},

		/**
		 * Create and display a toast notice.
		 *
		 * @param {Object} notice Notice payload.
		 */
		show: function (notice) {
			if (!notice || !notice.message) {
				return null;
			}

			// Deduplication check: prevent duplicate toasts within 2 seconds.
			if (settings.deduplication) {
				var hash = (notice.type || 'notice') + '_' + (notice.message || '');
				var now = Date.now();
				if (this.recentNoticeHashes[hash] && (now - this.recentNoticeHashes[hash] < 2000)) {
					return null;
				}
				this.recentNoticeHashes[hash] = now;
			}

			var self = this;
			var container = this.ensureContainer();

			// Max toasts enforcement: dismiss oldest if limit reached.
			var maxToasts = parseInt(settings.max_toasts, 10) || 4;
			if (this.activeToasts.length >= maxToasts) {
				var oldest = this.activeToasts[0];
				if (oldest) {
					this.dismiss(oldest.element, oldest.id);
				}
			}

			// Sanitize and determine duration.
			var type = notice.type || 'notice';
			var duration = 5000;
			if (type === 'success') {
				duration = parseInt(settings.duration_success, 10);
			} else if (type === 'error') {
				duration = parseInt(settings.duration_error, 10);
			} else {
				duration = parseInt(settings.duration_info, 10);
			}
			if (isNaN(duration)) {
				duration = 5000;
			}

			var toastId = notice.id || ('wct_' + Math.random().toString(36).substr(2, 9));

			// Construct DOM elements.
			var toast = document.createElement('div');
			toast.className = 'wc-toast type-' + type;
			toast.id = toastId;

			// Entrance animation: Horizontal move + opacity fade only (no scale).
			var pos = settings.position || 'top-right';
			if (pos.indexOf('left') !== -1) {
				toast.classList.add('anim-horizontal-in-left');
			} else {
				toast.classList.add('anim-horizontal-in-right');
			}

			// 1. Icon.
			if (settings.show_icons) {
				var iconWrap = document.createElement('div');
				iconWrap.className = 'wc-toast-icon-wrap';
				var iconKey = notice.icon || (type === 'success' ? 'check' : (type === 'error' ? 'alert_circle' : 'info'));
				iconWrap.innerHTML = icons[iconKey] || icons.info || '';
				toast.appendChild(iconWrap);
			}

			// 2. Content area.
			var content = document.createElement('div');
			content.className = 'wc-toast-content';

			if (notice.title) {
				var title = document.createElement('div');
				title.className = 'wc-toast-title';
				title.textContent = notice.title;
				content.appendChild(title);
			}

			var message = document.createElement('div');
			message.className = 'wc-toast-message';
			// Safe HTML or text.
			if (notice.raw_html && notice.raw_html !== notice.message) {
				message.innerHTML = notice.raw_html;
			} else {
				message.textContent = notice.message;
			}
			content.appendChild(message);

			// Action button (e.g. View Cart, Undo, Checkout).
			if (notice.action && notice.action.url && notice.action.text) {
				var actionWrap = document.createElement('div');
				actionWrap.className = 'wc-toast-action';

				var actionBtn = document.createElement('a');
				actionBtn.className = 'wc-toast-action-btn';
				actionBtn.href = notice.action.url;
				actionBtn.target = notice.action.target || '_self';
				actionBtn.textContent = notice.action.text;

				actionWrap.appendChild(actionBtn);
				content.appendChild(actionWrap);
			}

			toast.appendChild(content);

			// 3. Close button.
			if (settings.show_close_button) {
				var closeBtn = document.createElement('button');
				closeBtn.type = 'button';
				closeBtn.className = 'wc-toast-close-btn';
				closeBtn.setAttribute('aria-label', (i18n.close || 'Close notice'));
				closeBtn.innerHTML = icons.close || '&times;';
				closeBtn.addEventListener('click', function (e) {
					e.stopPropagation();
					self.dismiss(toast, toastId);
				});
				toast.appendChild(closeBtn);
			}

			// 4. Progress bar.
			var progressBar = null;
			var startTime = Date.now();
			var remainingTime = duration;
			var timerId = null;
			var isPaused = false;

			if (settings.show_progress_bar && duration > 0) {
				progressBar = document.createElement('div');
				progressBar.className = 'wc-toast-progress-bar';
				progressBar.style.transition = 'transform ' + duration + 'ms linear';
				toast.appendChild(progressBar);

				// Trigger progress animation on next frame.
				requestAnimationFrame(function () {
					requestAnimationFrame(function () {
						if (progressBar) {
							progressBar.style.transform = 'scaleX(0)';
						}
					});
				});
			}

			// Auto dismiss timer logic with pause on hover.
			if (duration > 0) {
				var startTimer = function () {
					startTime = Date.now();
					timerId = setTimeout(function () {
						self.dismiss(toast, toastId);
					}, remainingTime);
				};

				var pauseTimer = function () {
					if (isPaused) return;
					isPaused = true;
					clearTimeout(timerId);
					remainingTime -= (Date.now() - startTime);
					if (progressBar) {
						var computed = window.getComputedStyle(progressBar);
						progressBar.style.transform = computed.transform;
						progressBar.style.transition = 'none';
					}
				};

				var resumeTimer = function () {
					if (!isPaused || remainingTime <= 0) return;
					isPaused = false;
					if (progressBar) {
						progressBar.style.transition = 'transform ' + remainingTime + 'ms linear';
						progressBar.style.transform = 'scaleX(0)';
					}
					startTimer();
				};

				if (settings.pause_on_hover) {
					toast.addEventListener('mouseenter', pauseTimer);
					toast.addEventListener('mouseleave', resumeTimer);
				}

				startTimer();
			}

			// Insert into DOM stack.
			if (settings.newest_on_top && container.firstChild) {
				container.insertBefore(toast, container.firstChild);
			} else {
				container.appendChild(toast);
			}

			// Track active toast.
			var toastRecord = {
				id: toastId,
				element: toast,
				timerId: timerId
			};
			this.activeToasts.push(toastRecord);

			// Play Audio chime.
			AudioEngine.play(type);

			return toastRecord;
		},

		/**
		 * Dismiss a toast with smooth exit animation.
		 *
		 * @param {HTMLElement} toast Toast element.
		 * @param {string}      id    Toast ID.
		 */
		dismiss: function (toast, id) {
			if (!toast || toast.classList.contains('is-dismissing')) {
				return;
			}
			toast.classList.add('is-dismissing');

			var self = this;
			// Exit animation: Horizontal move + opacity fade only (no scale).
			var pos = settings.position || 'top-right';
			if (pos.indexOf('left') !== -1) {
				toast.classList.add('anim-horizontal-out-left');
			} else {
				toast.classList.add('anim-horizontal-out-right');
			}

			setTimeout(function () {
				if (toast && toast.parentNode) {
					toast.parentNode.removeChild(toast);
				}
				// Remove from active tracking.
				self.activeToasts = self.activeToasts.filter(function (item) {
					return item.id !== id;
				});
			}, 260);
		},

		/**
		 * Clear all active toasts.
		 */
		clearAll: function () {
			var self = this;
			this.activeToasts.slice().forEach(function (item) {
				self.dismiss(item.element, item.id);
			});
		},

		/**
		 * Intercept standard WooCommerce jQuery events.
		 */
		bindWooCommerceEvents: function () {
			var self = this;
			if (typeof window.jQuery === 'undefined') {
				return;
			}

			var $ = window.jQuery;

			// 1. Added to cart (AJAX).
			$(document.body).on('added_to_cart', function (event, fragments, cart_hash, $button) {
				var productName = '';
				if ($button && $button.length) {
					productName = $button.attr('data-product_name') || $button.attr('data-product_title') || '';
					if (!productName) {
						var aria = $button.attr('aria-label') || '';
						var match = aria.match(/“([^”]+)”|"[^"]+"/);
						if (match) {
							productName = match[0].replace(/["“”]/g, '');
						}
					}
				}

				var messageText = productName
					? '“' + productName + '” ' + (i18n.added_notice || 'has been added to your cart.')
					: (i18n.added_notice || 'Product has been added to your cart.');

				var cartUrl = config.cart_url || (window.wc_cart_params && window.wc_cart_params.cart_url) || '';

				self.show({
					case_id: 'cart_item_added',
					category: 'cart',
					type: 'success',
					title: i18n.view_cart ? 'Cart Updated' : 'Added to Cart',
					message: messageText,
					icon: 'cart_check',
					action: cartUrl ? {
						text: i18n.view_cart || 'View cart',
						url: cartUrl
					} : null
				});
			});

			// 2. Checkout Error.
			$(document.body).on('checkout_error', function (event, errorMessage) {
				if (!errorMessage) {
					return;
				}
				self.extractAndShowNoticesFromHtml(errorMessage, 'error');
			});

			// 3. Applied Coupon.
			$(document.body).on('applied_coupon', function (event, couponCode) {
				self.show({
					case_id: 'coupon_applied',
					category: 'coupon',
					type: 'success',
					title: 'Coupon Applied',
					message: 'Coupon code "' + couponCode + '" applied successfully.',
					icon: 'ticket_check'
				});
			});

			// 4. Removed Coupon.
			$(document.body).on('removed_coupon', function (event, couponCode) {
				self.show({
					case_id: 'coupon_removed',
					category: 'coupon',
					type: 'notice',
					title: 'Coupon Removed',
					message: 'Coupon code "' + couponCode + '" has been removed.',
					icon: 'ticket_cross'
				});
			});
		},

		/**
		 * Extract notices from raw HTML string and trigger toasts.
		 *
		 * @param {string} html Raw HTML from checkout or fragments.
		 * @param {string} fallbackType Default type if not determined.
		 */
		extractAndShowNoticesFromHtml: function (html, fallbackType) {
			if (!html || typeof html !== 'string') return;

			var temp = document.createElement('div');
			temp.innerHTML = html;

			var items = temp.querySelectorAll('.woocommerce-error li, .woocommerce-message, .woocommerce-info');

			if (items.length > 0) {
				for (var i = 0; i < items.length; i++) {
					var item = items[i];
					var type = fallbackType || 'notice';

					if (item.closest('.woocommerce-error') || item.classList.contains('woocommerce-error')) {
						type = 'error';
					} else if (item.closest('.woocommerce-message') || item.classList.contains('woocommerce-message')) {
						type = 'success';
					}

					var actionLink = item.querySelector('a');
					var action = null;
					if (actionLink && actionLink.getAttribute('href')) {
						action = {
							text: actionLink.textContent.trim(),
							url: actionLink.getAttribute('href'),
							target: actionLink.getAttribute('target') || '_self'
						};
						actionLink.parentNode.removeChild(actionLink);
					}

					var text = item.textContent.trim();
					if (text) {
						this.show({
							type: type,
							category: 'checkout',
							title: type === 'error' ? 'Checkout Alert' : 'Store Notice',
							message: text,
							action: action,
							icon: type === 'error' ? 'alert_triangle' : 'check'
						});
					}
				}
			} else {
				// Strip tags fallback.
				var cleanText = temp.textContent.trim();
				if (cleanText) {
					this.show({
						type: fallbackType || 'error',
						category: 'checkout',
						title: 'Alert',
						message: cleanText
					});
				}
			}
		},

		/**
		 * Intercept standard AJAX responses (Fetch & XMLHttpRequest).
		 */
		bindAjaxInterception: function () {
			if (settings.ajax_interception === 'standard') {
				return;
			}

			var self = this;

			// Intercept jQuery global ajaxSuccess if jQuery is loaded.
			if (window.jQuery) {
				window.jQuery(document).ajaxSuccess(function (event, xhr, ajaxOptions, data) {
					if (!data) return;

					// WooCommerce AJAX Add to cart / checkout response.
					if (typeof data === 'object') {
						if (data.result === 'failure' && data.messages) {
							self.extractAndShowNoticesFromHtml(data.messages, 'error');
						}
					}
				});
			}
		},

		/**
		 * MutationObserver: Observes .woocommerce-notices-wrapper or body to catch dynamically inserted notices.
		 */
		bindMutationObserver: function () {
			if (!window.MutationObserver) {
				return;
			}

			var self = this;
			var observer = new MutationObserver(function (mutations) {
				mutations.forEach(function (mutation) {
					if (!mutation.addedNodes || !mutation.addedNodes.length) return;

					for (var i = 0; i < mutation.addedNodes.length; i++) {
						var node = mutation.addedNodes[i];
						if (node.nodeType !== 1) continue;

						if (node.classList && (node.classList.contains('woocommerce-message') || node.classList.contains('woocommerce-error') || node.classList.contains('woocommerce-info'))) {
							self.handleObservedNoticeNode(node);
						} else if (node.querySelectorAll) {
							var nested = node.querySelectorAll('.woocommerce-message, .woocommerce-error, .woocommerce-info');
							for (var j = 0; j < nested.length; j++) {
								self.handleObservedNoticeNode(nested[j]);
							}
						}
					}
				});
			});

			observer.observe(document.body, {
				childList: true,
				subtree: true
			});
		},

		/**
		 * Handle and hide dynamically injected notice elements.
		 *
		 * @param {HTMLElement} node Found notice DOM node.
		 */
		handleObservedNoticeNode: function (node) {
			if (node.hasAttribute('data-wct-processed')) {
				return;
			}
			node.setAttribute('data-wct-processed', 'true');

			// Hide immediately to eliminate layout shift.
			node.style.setProperty('display', 'none', 'important');

			var type = 'notice';
			if (node.classList.contains('woocommerce-error') || node.querySelector('.woocommerce-error')) {
				type = 'error';
			} else if (node.classList.contains('woocommerce-message') || node.querySelector('.woocommerce-message')) {
				type = 'success';
			}

			var actionLink = node.querySelector('a');
			var action = null;
			if (actionLink && actionLink.getAttribute('href')) {
				action = {
					text: actionLink.textContent.trim(),
					url: actionLink.getAttribute('href'),
					target: actionLink.getAttribute('target') || '_self'
				};
				actionLink.parentNode.removeChild(actionLink);
			}

			var text = node.textContent.trim();
			if (text) {
				this.show({
					type: type,
					category: 'system',
					title: type === 'success' ? 'Store Notice' : (type === 'error' ? 'Notice Alert' : 'Information'),
					message: text,
					action: action,
					icon: type === 'success' ? 'check' : (type === 'error' ? 'alert_triangle' : 'info')
				});
			}
		}
	};

	// Expose public API on window.WCToasts.
	window.WCToasts = {
		show: function (notice) {
			return ToastManager.show(notice);
		},
		success: function (message, options) {
			var payload = Object.assign({ type: 'success', message: message, title: 'Success' }, options || {});
			return ToastManager.show(payload);
		},
		error: function (message, options) {
			var payload = Object.assign({ type: 'error', message: message, title: 'Error' }, options || {});
			return ToastManager.show(payload);
		},
		info: function (message, options) {
			var payload = Object.assign({ type: 'notice', message: message, title: 'Notice' }, options || {});
			return ToastManager.show(payload);
		},
		dismiss: function (id) {
			var toast = document.getElementById(id);
			if (toast) {
				ToastManager.dismiss(toast, id);
			}
		},
		clearAll: function () {
			ToastManager.clearAll();
		},
		pushNotices: function (noticesArray) {
			if (Array.isArray(noticesArray)) {
				noticesArray.forEach(function (n) {
					ToastManager.show(n);
				});
			}
		},
		playSound: function (type, vol) {
			AudioEngine.play(type, vol);
		}
	};

	// Initialize when DOM is ready.
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () {
			ToastManager.init();
		});
	} else {
		ToastManager.init();
	}

})(window, document);
