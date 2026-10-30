/** Shared palette and token engine. @package WooCommerce_Toasts */
(function (root) {
	'use strict';
	var prefix = '--wc-toast-';
	function validColor(color) { return typeof color === 'string' && /^#(?:[a-f\d]{3}|[a-f\d]{6})$/i.test(color); }
	function expand(color) { return color.length === 4 ? '#' + color.slice(1).split('').map(function (c) { return c + c; }).join('') : color; }
	function luminance(hex) {
		return expand(hex).slice(1).match(/../g).map(function (c) { var v = parseInt(c, 16) / 255; return v <= .04045 ? v / 12.92 : Math.pow((v + .055) / 1.055, 2.4); }).reduce(function (sum, v, i) { return sum + v * [.2126, .7152, .0722][i]; }, 0);
	}
	function onAccent(palette) {
		if (!validColor(palette.accent)) return palette.ink;
		var l = luminance(palette.accent);
		function contrast(color) { var c = luminance(color); return (Math.max(c, l) + .05) / (Math.min(c, l) + .05); }
		return contrast(palette.ink) >= contrast(palette.surface) ? palette.ink : palette.surface;
	}
	function resolve(palette, overrides, tokenNames, layout) {
		var result = {};
		var combined = Object.assign({}, palette && palette.layoutTokens && palette.layoutTokens[layout] || {});
		Object.keys(overrides || {}).forEach(function (key) { if (validColor(overrides[key])) combined[key] = overrides[key]; });
		overrides = combined;
		if (palette && validColor(palette.surface) && validColor(palette.ink)) {
			result.surface = palette.surface;
			result.ink = palette.ink;
			result.accent = validColor(palette.accent) ? palette.accent : 'color-mix(in srgb, var(--wc-toast-ink) 12%, var(--wc-toast-surface))';
		}
		Object.keys(overrides || {}).forEach(function (key) {
			if (/^[a-z][a-z0-9-]*$/.test(key) && Object.prototype.hasOwnProperty.call(tokenNames || {}, key) && validColor(overrides[key])) result[key] = overrides[key];
		});
		if (result.surface && result.ink && !result['on-accent']) result['on-accent'] = onAccent(result);
		if (result.surface && !result.muted) result.muted = 'color-mix(in srgb, var(--wc-toast-ink) 76%, var(--wc-toast-surface))';
		return result;
	}
	function apply(element, palette, overrides, tokenNames, layout) {
		var values = resolve(palette, overrides, tokenNames, layout);
		(element.__wcToastTokens || []).forEach(function (key) { element.style.removeProperty(prefix + key); });
		Object.keys(values).forEach(function (key) { element.style.setProperty(prefix + key, values[key]); });
		element.__wcToastTokens = Object.keys(values);
		return values;
	}
	root.WCToastStyling = { resolve: resolve, apply: apply, validColor: validColor, onAccent: onAccent };
})(typeof window !== 'undefined' ? window : globalThis);
