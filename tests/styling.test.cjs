const { test } = require('node:test');
const assert = require('node:assert/strict');
require('../assets/js/wc-toasts-styling.js');
const data = require('../assets/config/styling.json');
const engine = globalThis.WCToastStyling;

test('blank editor fields preserve layout defaults and explicit colors take precedence', () => {
  const palette = data.palettes['lime-charcoal'];
  const automatic = engine.resolve(palette, {'action-bg':'','action-ink':''}, data.tokens, 'utility');
  assert.equal(automatic['action-bg'], '#2B2B2B');
  assert.equal(automatic['action-ink'], '#FFFFFF');
  const custom = engine.resolve(palette, {'action-bg':'#123456'}, data.tokens, 'utility');
  assert.equal(custom['action-bg'], '#123456');
  assert.equal(custom['action-ink'], '#FFFFFF');
  const otherLayout = engine.resolve(palette, {}, data.tokens, 'retro-outline');
  assert.equal(otherLayout['action-bg'], undefined);
});

test('unregistered tokens and CSS expressions are rejected while short hex is supported', () => {
  const values = engine.resolve(data.palettes['default-light'], { surface:'#abc',ink:'red;position:fixed',unregistered:'#123456' }, data.tokens, 'editorial');
  assert.equal(values.surface, '#abc');
  assert.equal(values.ink, '#1F2328');
  assert.equal(values.unregistered, undefined);
});

test('reapplying a palette removes previous layout and user overrides', () => {
  const stored = new Map();
  const element = { style: { setProperty:(key,value)=>stored.set(key,value),removeProperty:key=>stored.delete(key) } };
  engine.apply(element, data.palettes['lime-charcoal'], {'border':'#112233'}, data.tokens, 'utility');
  assert.equal(stored.get('--wc-toast-action-bg'), '#2B2B2B');
  engine.apply(element, data.palettes['sky-aubergine'], {}, data.tokens, 'utility');
  assert.equal(stored.has('--wc-toast-action-bg'), false);
  assert.equal(stored.has('--wc-toast-border'), false);
});
