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
	var styling = config.styling || { palettes: {}, tokens: {}, layouts: {} };

	function safeURL(value) {
		if (typeof value !== 'string' || !value.trim()) return '';
		try { var url = new URL(value, document.baseURI); return /^(https?:)$/.test(url.protocol) ? url.href : ''; } catch (error) { return ''; }
	}

	function safeNoticeHTML(value) {
		var template = document.createElement('template');
		template.innerHTML = String(value);
		var allowed = ['A', 'STRONG', 'EM', 'B', 'I', 'BR', 'P', 'SPAN', 'UL', 'OL', 'LI'];
		Array.from(template.content.querySelectorAll('*')).forEach(function (node) {
			if (allowed.indexOf(node.tagName) === -1) { node.replaceWith(document.createTextNode(node.textContent)); return; }
			var href = node.tagName === 'A' ? safeURL(node.getAttribute('href') || '') : '';
			Array.from(node.attributes).forEach(function (attribute) { node.removeAttribute(attribute.name); });
			if (href) node.setAttribute('href', href);
		});
		return template.content;
	}

	// Classic notice wrappers plus the banner markup used by WooCommerce Blocks and block themes.
	var bannerClass = 'wc-block-components-notice-banner';
	var noticeSelectors = ['.woocommerce-error', '.woocommerce-message', '.woocommerce-info', '.' + bannerClass];
	var noticeSelector = noticeSelectors.join(', ');

	// If plugin is disabled, exit.
	if (settings.enabled === 0 || settings.enabled === '0' || settings.enabled === false) {
		return;
	}

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
		},

		/**
		 * Ensure fixed container exists in the DOM.
		 */
		ensureContainer: function () {
			if (this.container && document.body.contains(this.container)) {
				return this.container;
			}

			var position = settings.position || 'bottom-left';
			var theme = settings.theme || 'modern-light';
			var shadow = settings.box_shadow || 'medium';

			var container = document.createElement('div');
			container.id = 'wc-toasts-container';
			container.className = 'wc-toasts-container pos-' + position + ' theme-' + theme + ' shadow-' + shadow;
			container.classList.add('layout-' + (Object.prototype.hasOwnProperty.call(styling.layouts, settings.layout) ? settings.layout : 'editorial'));
			if (window.WCToastStyling) window.WCToastStyling.apply(container, styling.palettes[settings.palette], settings.custom_tokens, styling.tokens, settings.layout);
			container.setAttribute('aria-live', 'polite');
			container.setAttribute('aria-atomic', 'true');

			if (settings.mobile_bottom) {
				container.classList.add('mobile-dock-bottom');
			}

			// Apply custom styling overrides if custom theme selected.
			if (theme === 'custom') {
				if (settings.color_success_bg) container.style.setProperty('--wc-toast-success-bg', settings.color_success_bg);
				if (settings.color_success_text) container.style.setProperty('--wc-toast-success-text', settings.color_success_text);
				if (settings.color_success_accent) container.style.setProperty('--wc-toast-accent-success', settings.color_success_accent);
				if (settings.color_error_bg) container.style.setProperty('--wc-toast-error-bg', settings.color_error_bg);
				if (settings.color_error_text) container.style.setProperty('--wc-toast-error-text', settings.color_error_text);
				if (settings.color_error_accent) container.style.setProperty('--wc-toast-accent-error', settings.color_error_accent);
				if (settings.color_info_bg) container.style.setProperty('--wc-toast-info-bg', settings.color_info_bg);
				if (settings.color_info_text) container.style.setProperty('--wc-toast-info-text', settings.color_info_text);
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
			if (!document.dispatchEvent(new CustomEvent('wc-toasts:before-show', { cancelable: true, detail: { notice: notice } }))) return null;
			if (notice.category && (settings['enable_cat_' + notice.category] === 0 || settings['enable_cat_' + notice.category] === '0' || settings['enable_cat_' + notice.category] === false)) return null;

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
			var type = ['success', 'error', 'notice', 'info', 'warning'].indexOf(notice.type) !== -1 ? notice.type : 'notice';
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
			toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
			if (window.WCToastStyling) window.WCToastStyling.apply(toast, styling.palettes[settings.palette], settings.custom_tokens, styling.tokens, settings.layout);

			// Entrance animation: Horizontal move + opacity fade only (no scale).
			var pos = settings.position || 'bottom-left';
			if (pos.indexOf('left') !== -1) {
				toast.classList.add('anim-horizontal-in-left');
			} else {
				toast.classList.add('anim-horizontal-in-right');
			}

			// 1. Icon.
			if (settings.show_icons) {
				var iconWrap = document.createElement('div');
				iconWrap.className = 'wc-toast-icon-wrap';
				iconWrap.setAttribute('aria-hidden', 'true');
				var iconKey = notice.icon || (type === 'success' ? 'check' : (type === 'error' ? 'alert_circle' : 'info'));
				iconWrap.innerHTML = icons[iconKey] || icons.info || '';
				toast.appendChild(iconWrap);
			}

			// 2. Content area.
			var content = document.createElement('div');
			content.className = 'wc-toast-content';

			// Quiet category label; keep the notice itself as the focal point.
			var eyebrowText = '';
			if (notice.category) {
				eyebrowText = notice.cat_name || (config.categories && config.categories[notice.category] && config.categories[notice.category].name) || notice.category.replace(/[_-]/g, ' ');
			} else if (notice.type) {
				eyebrowText = notice.type === 'notice' ? 'Notice' : notice.type;
			}
			if (eyebrowText) {
				var eyebrow = document.createElement('div');
				eyebrow.className = 'wc-toast-eyebrow';
				eyebrow.textContent = eyebrowText;
				content.appendChild(eyebrow);
			}

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
				message.appendChild(safeNoticeHTML(notice.raw_html));
			} else {
				message.textContent = notice.message;
			}
			content.appendChild(message);

			// Action link (e.g. View Cart, Undo, Checkout).
			if (notice.action && safeURL(notice.action.url) && notice.action.text) {
				var actionWrap = document.createElement('div');
				actionWrap.className = 'wc-toast-action';

				var actionBtn = document.createElement('a');
				actionBtn.className = 'wc-toast-action-btn';
				actionBtn.href = safeURL(notice.action.url);
				actionBtn.target = notice.action.target === '_blank' ? '_blank' : '_self';
				if (actionBtn.target === '_blank') actionBtn.rel = 'noopener noreferrer';
				actionBtn.textContent = notice.action.text;
				var actionArrow = document.createElement('span');
				actionArrow.className = 'arrow-icon';
				actionArrow.setAttribute('aria-hidden', 'true');
				actionArrow.textContent = '→';
				actionBtn.appendChild(actionArrow);

				actionWrap.appendChild(actionBtn);
				if (settings.layout !== 'utility' && settings.layout !== 'retro-outline') content.appendChild(actionWrap);
			}

			toast.appendChild(content);
			if (actionWrap && (settings.layout === 'utility' || settings.layout === 'retro-outline')) toast.appendChild(actionWrap);

			// 3. Close button.
			if (settings.show_close_button) {
				var closeBtn = document.createElement('button');
				closeBtn.type = 'button';
				closeBtn.className = 'wc-toast-close-btn';
				closeBtn.setAttribute('aria-label', (i18n.close || 'Close notice'));
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
					if (!isPaused || remainingTime <= 0 || toast.contains(document.activeElement) || (settings.pause_on_hover && toast.matches(':hover'))) return;
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
				toast.addEventListener('focusin', pauseTimer);
				toast.addEventListener('focusout', function (event) { if (!toast.contains(event.relatedTarget)) resumeTimer(); });
				toast.__wcToastCancelTimer = function () { clearTimeout(timerId); };

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
			document.dispatchEvent(new CustomEvent('wc-toasts:render', { detail: { notice: notice, element: toast } }));

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
			if (toast.__wcToastCancelTimer) toast.__wcToastCancelTimer();
			document.dispatchEvent(new CustomEvent('wc-toasts:dismiss', { detail: { id: id, element: toast } }));

			var self = this;
			// Exit animation: Horizontal move + opacity fade only (no scale).
			var pos = settings.position || 'bottom-left';
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
			}, 240);
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

			// 1. Added to cart (classic AJAX).
			var lastAddButton = null;
			$(document).on('click', '.add_to_cart_button', function () {
				lastAddButton = $(this);
			});
			$(document.body).on('added_to_cart', function (event, fragments, cart_hash, $button) {
				lastAddButton = null;
				self.showAddedToCart($button);
			});

			// 2. Added to cart (Blocks). Mini-Cart re-dispatches the classic event without detail; that case is handled above.
			$(document.body).on('wc-blocks_added_to_cart', function (event) {
				var detail = event.originalEvent ? event.originalEvent.detail : null;
				if (!detail || !Object.prototype.hasOwnProperty.call(detail, 'preserveCartData')) {
					return;
				}
				self.showAddedToCart(lastAddButton);
				lastAddButton = null;
			});

			// 3. Checkout Error. WooCommerce inserts the notice markup before triggering this event.
			$(document.body).on('checkout_error', function (event, errorMessage) {
				if (!self.flushDomNotices() && errorMessage) {
					self.extractAndShowNoticesFromHtml(errorMessage, 'error');
				}
			});

			// 4. Coupon errors. WooCommerce shows these inline and still triggers applied_coupon afterwards.
			var erroredCoupon = null;
			$(document.body).on('errored_coupon', function (event, couponCode, response) {
				erroredCoupon = couponCode;
				self.parseNoticeHtml(response).forEach(function (item) {
					self.show({
						case_id: 'coupon_error',
						category: 'coupon',
						type: 'error',
						title: i18n.error || 'Error',
						message: item.message,
						icon: 'ticket_alert'
					});
				});
			});

			// 5. Applied Coupon.
			$(document.body).on('applied_coupon', function (event, couponCode) {
				if (erroredCoupon === couponCode) {
					erroredCoupon = null;
					return;
				}
				if (self.flushDomNotices()) {
					return;
				}
				self.show({
					case_id: 'coupon_applied',
					category: 'coupon',
					type: 'success',
					title: i18n.coupon_applied || 'Coupon Applied',
					message: (i18n.coupon_applied_message || 'Coupon code "%s" applied successfully.').replace('%s', function () { return couponCode; }),
					icon: 'ticket_check'
				});
			});

			// 6. Removed Coupon.
			$(document.body).on('removed_coupon', function (event, couponCode) {
				if (self.flushDomNotices()) {
					return;
				}
				self.show({
					case_id: 'coupon_removed',
					category: 'coupon',
					type: 'notice',
					title: i18n.coupon_removed || 'Coupon Removed',
					message: (i18n.coupon_removed_message || 'Coupon code "%s" has been removed.').replace('%s', function () { return couponCode; }),
					icon: 'ticket_cross'
				});
			});
		},

		/**
		 * Show the added-to-cart toast, naming the product when the clicked button identifies it.
		 *
		 * @param {jQuery|null} $button Add to cart button.
		 */
		showAddedToCart: function ($button) {
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

			this.show({
				case_id: 'cart_item_added',
				category: 'cart',
				type: 'success',
				title: i18n.added_to_cart || 'Added to Cart',
				message: messageText,
				icon: 'cart_check',
				action: cartUrl ? {
					text: i18n.view_cart || 'View cart',
					url: cartUrl
				} : null
			});
		},

		/**
		 * Extract notices from raw HTML string and trigger toasts.
		 *
		 * @param {string} html Raw HTML from checkout or fragments.
		 * @param {string} fallbackType Default type if not determined.
		 */
		extractAndShowNoticesFromHtml: function (html, fallbackType) {
			var self = this;
			this.parseNoticeHtml(html).forEach(function (item) {
				var type = item.type || fallbackType || 'error';
				self.show({
					type: type,
					category: 'checkout',
					title: item.type ? (type === 'error' ? (i18n.checkout_alert || 'Checkout Alert') : (i18n.store_notice || 'Store Notice')) : (i18n.alert || 'Alert'),
					message: item.message,
					action: item.action,
					icon: item.type ? (type === 'error' ? 'alert_triangle' : 'check') : undefined
				});
			});
		},

		/**
		 * Parse notices from an HTML string. Markup without notice wrappers becomes one untyped item.
		 *
		 * @param {string} html Raw notice HTML.
		 * @return {Array} Parsed items with type, message, and action.
		 */
		parseNoticeHtml: function (html) {
			if (!html || typeof html !== 'string') return [];

			var template = document.createElement('template');
			template.innerHTML = html;

			var nodes = template.content.querySelectorAll(noticeSelector);
			if (!nodes.length) {
				var text = template.content.textContent.replace(/\s+/g, ' ').trim();
				return text ? [{ type: '', message: text, action: null }] : [];
			}

			var self = this;
			var parsed = [];
			Array.prototype.forEach.call(nodes, function (node) {
				parsed = parsed.concat(self.parseNoticeElement(node));
			});
			return parsed;
		},

		/**
		 * Parse a WooCommerce notice element into one item per list entry.
		 *
		 * @param {HTMLElement} node Notice element.
		 * @return {Array} Parsed items with type, message, and action.
		 */
		parseNoticeElement: function (node) {
			var type = 'notice';
			if (node.classList.contains(bannerClass)) {
				type = node.classList.contains('is-error') ? 'error' : (node.classList.contains('is-success') ? 'success' : (node.classList.contains('is-warning') ? 'warning' : 'notice'));
				// Read only the message: banners also hold an icon, a dismiss button, and a summary line above lists.
				node = node.querySelector('.' + bannerClass + '__content') || node;
			} else if (node.classList.contains('woocommerce-error') || node.querySelector('.woocommerce-error')) {
				type = 'error';
			} else if (node.classList.contains('woocommerce-message') || node.querySelector('.woocommerce-message')) {
				type = 'success';
			}

			var entries = node.querySelectorAll('li');
			var sources = entries.length ? Array.prototype.slice.call(entries) : [node];

			return sources.map(function (source) {
				// Work on a copy so WooCommerce's own notice markup is never modified.
				var copy = source.cloneNode(true);
				// Only WooCommerce's button links are actions; links inside a sentence stay in the message.
				var actionLink = copy.querySelector('a.button, a.wc-forward');
				var action = null;
				if (actionLink && actionLink.getAttribute('href')) {
					action = {
						text: actionLink.textContent.trim(),
						url: actionLink.getAttribute('href'),
						target: actionLink.getAttribute('target') || '_self'
					};
					actionLink.parentNode.removeChild(actionLink);
				}
				return { type: type, message: copy.textContent.replace(/\s+/g, ' ').trim(), action: action };
			}).filter(function (item) {
				return item.message;
			});
		},

		/**
		 * Toast notices WooCommerce has just inserted into the page, before the observer sees them.
		 *
		 * @return {boolean} Whether any notice was found.
		 */
		flushDomNotices: function () {
			var nodes = document.querySelectorAll(noticeSelectors.map(function (selector) {
				return selector + ':not([data-wct-processed])';
			}).join(', '));
			for (var i = 0; i < nodes.length; i++) {
				this.handleObservedNoticeNode(nodes[i]);
			}
			return nodes.length > 0;
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

						if (node.matches(noticeSelector)) {
							self.handleObservedNoticeNode(node);
						} else {
							var nested = node.querySelectorAll(noticeSelector);
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
		 * @param {HTMLElement} node    Found notice DOM node.
		 * @param {number}      attempt Frames already spent waiting for the notice text.
		 */
		handleObservedNoticeNode: function (node, attempt) {
			if (node.hasAttribute('data-wct-processed')) {
				return;
			}

			var self = this;
			var items = this.parseNoticeElement(node);
			attempt = attempt || 0;

			// Block notices can be inserted before their text renders; wait up to half a second for it.
			if (!items.length && attempt < 30 && document.body.contains(node)) {
				requestAnimationFrame(function () {
					self.handleObservedNoticeNode(node, attempt + 1);
				});
				return;
			}
			node.setAttribute('data-wct-processed', 'true');

			// Hide immediately to eliminate layout shift.
			if (settings.hide_default_notices) node.style.setProperty('display', 'none', 'important');

			items.forEach(function (item) {
				self.show({
					type: item.type,
					category: 'system',
					title: item.type === 'success' ? (i18n.store_notice || 'Store Notice') : (item.type === 'error' ? (i18n.notice_alert || 'Notice Alert') : (i18n.information || 'Information')),
					message: item.message,
					action: item.action,
					icon: item.type === 'success' ? 'check' : (item.type === 'error' ? 'alert_triangle' : 'info')
				});
			});
		}
	};

	// Expose public API on window.WCToasts.
	window.WCToasts = {
		show: function (notice) {
			return ToastManager.show(notice);
		},
		success: function (message, options) {
			var payload = Object.assign({ type: 'success', message: message, title: i18n.success || 'Success' }, options || {});
			return ToastManager.show(payload);
		},
		error: function (message, options) {
			var payload = Object.assign({ type: 'error', message: message, title: i18n.error || 'Error' }, options || {});
			return ToastManager.show(payload);
		},
		info: function (message, options) {
			var payload = Object.assign({ type: 'notice', message: message, title: i18n.notice || 'Notice' }, options || {});
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
