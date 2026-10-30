// End-to-end check of storefront notice toasts: boots Playground from the directory blueprint with the
// built package mounted, drives the classic cart, coupon, and checkout flows, and asserts on every toast rendered.
import { chromium } from '@playwright/test';
import { spawn, spawnSync } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import assert from 'node:assert/strict';
import { packageName, pluginSlug } from './release-config.mjs';

const root = path.resolve(import.meta.dirname, '..');
const results = path.join(root, 'test-results');
const port = Number(process.env.WC_TOASTS_FLOWS_PORT || 9420);
const base = 'http://127.0.0.1:' + port;
const toastTimeout = 15000;
const settleTime = 1500;

const build = spawnSync(process.execPath, [path.join(import.meta.dirname, 'build-release.mjs')], { cwd: root, stdio: 'inherit' });
assert.equal(build.status, 0, 'Release build failed.');

// Use the published directory blueprint, but activate the mounted build instead of installing from WordPress.org.
const blueprint = JSON.parse(fs.readFileSync(path.join(root, '.wordpress-org/blueprints/blueprint.json'), 'utf8'));
blueprint.steps = blueprint.steps.map(step => step.pluginData && step.pluginData.slug === pluginSlug
  ? { step: 'activatePlugin', pluginPath: pluginSlug + '/woocommerce-toasts.php' }
  : step);
fs.mkdirSync(results, { recursive: true });
const blueprintPath = path.join(results, 'notice-flows-blueprint.json');
fs.writeFileSync(blueprintPath, JSON.stringify(blueprint));

const cli = path.join(root, 'node_modules/@wp-playground/cli/wp-playground.js');
const server = spawn(process.execPath, [cli, 'server', '--blueprint=' + blueprintPath, '--port=' + port, '--mount-dir', path.join(root, 'dist', packageName), '/wordpress/wp-content/plugins/' + pluginSlug], { cwd: root });
await new Promise((resolve, reject) => {
  let output = '';
  const collect = chunk => {
    output += chunk;
    if (output.includes('Ready!')) resolve();
  };
  server.stdout.on('data', collect);
  server.stderr.on('data', collect);
  server.on('exit', code => reject(new Error('Playground exited with code ' + code + ':\n' + output)));
});

const cached = path.join(os.homedir(), 'AppData/Local/ms-playwright/chromium_headless_shell-1228/chrome-headless-shell-win64/chrome-headless-shell.exe');
const browser = await chromium.launch({ headless: true, executablePath: process.env.WC_TOASTS_CHROMIUM_PATH || (fs.existsSync(cached) ? cached : undefined) });
const context = await browser.newContext({ viewport: { width: 1280, height: 900 } });
await context.addInitScript(() => {
  document.addEventListener('wc-toasts:render', event => {
    const notice = event.detail.notice;
    console.log('wc-toast:' + JSON.stringify({ type: notice.type, message: notice.message, action: notice.action ? notice.action.text : null }));
  });
});
const page = await context.newPage();
const errors = [];
let toasts = [];
page.on('pageerror', error => errors.push(error.message));
page.on('console', message => {
  if (message.text().startsWith('wc-toast:')) toasts.push(JSON.parse(message.text().slice(9)));
});

// Navigations can be answered with a cookie redirect by the local server; wait for the resulting page instead.
async function visit(url) {
  await page.goto(base + url).catch(() => {});
  await page.waitForLoadState('load');
}

async function expectToasts(label, action, check, ready = list => list.length > 0) {
  toasts = [];
  await action();
  const deadline = Date.now() + toastTimeout;
  while (!ready(toasts) && Date.now() < deadline) await page.waitForTimeout(100);
  await page.waitForTimeout(settleTime);
  const messages = toasts.map(toast => toast.message);
  assert.equal(new Set(messages).size, messages.length, label + ': duplicate toasts ' + JSON.stringify(messages));
  check(toasts, label + ': ' + JSON.stringify(toasts));
  console.log('ok  ' + label);
}

const addToCart = name => page.locator('li.product', { hasText: name }).locator('a.add_to_cart_button').click();

try {
  await visit('/shop/');

  await expectToasts('AJAX add to cart', () => addToCart('Linen Tote Bag'), (list, detail) => {
    assert.equal(list.length, 1, detail);
    assert.equal(list[0].type, 'success', detail);
    assert.match(list[0].message, /Linen Tote Bag/, detail);
    assert.ok(list[0].action, detail);
  });

  await expectToasts('Sold individually, first add', () => addToCart('Signed Art Print'), (list, detail) => {
    assert.equal(list[0].type, 'success', detail);
  });

  await visit('/shop/');
  await expectToasts('Sold individually, second add', async () => {
    await addToCart('Signed Art Print').catch(() => {});
    await page.waitForURL(/\/product\/signed-art-print\//, { timeout: toastTimeout });
  }, (list, detail) => {
    assert.equal(list.length, 1, detail);
    assert.equal(list[0].type, 'error', detail);
    assert.match(list[0].message, /cannot add another/i, detail);
  });

  await visit('/cart/');
  await expectToasts('Invalid coupon', async () => {
    await page.fill('#coupon_code', 'not-a-coupon');
    await page.click('button[name="apply_coupon"]');
  }, (list, detail) => {
    assert.equal(list.length, 1, detail);
    assert.equal(list[0].type, 'error', detail);
    assert.match(list[0].message, /does not exist/i, detail);
  });

  await visit('/cart/');
  await expectToasts('Valid coupon', async () => {
    await page.fill('#coupon_code', 'toast10');
    await page.click('button[name="apply_coupon"]');
  }, (list, detail) => {
    assert.equal(list.length, 1, detail);
    assert.equal(list[0].type, 'success', detail);
    assert.match(list[0].message, /applied/i, detail);
  });

  await visit('/checkout/');
  await expectToasts('Empty checkout', async () => {
    for (const field of ['#billing_first_name', '#billing_last_name', '#billing_address_1', '#billing_city', '#billing_postcode', '#billing_phone']) {
      if (await page.locator(field).count()) await page.fill(field, '');
    }
    await page.click('#place_order');
  }, (list, detail) => {
    assert.ok(list.length > 1, detail);
    assert.ok(list.every(toast => toast.type === 'error'), detail);
    assert.ok(list.some(toast => /required field/i.test(toast.message)), detail);
    assert.ok(!list.some(toast => /error processing your order/i.test(toast.message)), detail);
  }, list => list.some(toast => toast.type === 'error'));

  assert.deepEqual(errors, [], 'Page errors: ' + JSON.stringify(errors));
  console.log('Notice flows passed.');
} finally {
  await browser.close();
  server.kill();
}
