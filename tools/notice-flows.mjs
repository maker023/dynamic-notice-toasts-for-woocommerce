// End-to-end check of storefront notice toasts. Boots Playground from the directory blueprint with the built
// package mounted, then drives the classic store (Storefront, shortcode cart and checkout) and the block store
// (default block theme, Product Collection, Cart and Checkout blocks), asserting on every toast rendered.
import assert from 'node:assert/strict';
import { buildPackage, writeDemoBlueprint, startPlayground, launchBrowser, visit } from './playground.mjs';

const port = Number(process.env.WC_TOASTS_FLOWS_PORT || 9420);
const toastTimeout = 15000;
const settleTime = 1500;

async function openStore(browser, url) {
  const context = await browser.newContext({ viewport: { width: 1280, height: 900 } });
  await context.addInitScript(() => {
    document.addEventListener('wc-toasts:render', event => {
      const notice = event.detail.notice;
      console.log('wc-toast:' + JSON.stringify({ type: notice.type, message: notice.message, action: notice.action ? notice.action.text : null }));
    });
  });
  const page = await context.newPage();
  const store = { page, errors: [], toasts: [] };
  page.on('pageerror', error => store.errors.push(error.message));
  page.on('console', message => {
    if (message.text().startsWith('wc-toast:')) store.toasts.push(JSON.parse(message.text().slice(9)));
  });
  store.visit = async path => {
    await visit(page, url + path);
    await page.waitForTimeout(1000);
  };
  store.expect = async (label, action, check, ready = list => list.length > 0) => {
    store.toasts = [];
    await action();
    const deadline = Date.now() + toastTimeout;
    while (!ready(store.toasts) && Date.now() < deadline) await page.waitForTimeout(100);
    await page.waitForTimeout(settleTime);
    const messages = store.toasts.map(toast => toast.message);
    assert.equal(new Set(messages).size, messages.length, label + ': duplicate toasts ' + JSON.stringify(messages));
    check(store.toasts, label + ': ' + JSON.stringify(store.toasts));
    console.log('ok  ' + label);
  };
  return store;
}

async function classicStore(store) {
  const { page } = store;
  const addToCart = name => page.locator('li.product', { hasText: name }).locator('a.add_to_cart_button').click();
  await store.visit('/shop/');

  await store.expect('Classic: AJAX add to cart', () => addToCart('Linen Tote Bag'), (list, detail) => {
    assert.equal(list.length, 1, detail);
    assert.equal(list[0].type, 'success', detail);
    assert.match(list[0].message, /Linen Tote Bag/, detail);
    assert.ok(list[0].action, detail);
  });

  await store.expect('Classic: sold individually, first add', () => addToCart('Signed Art Print'), (list, detail) => {
    assert.equal(list[0].type, 'success', detail);
  });

  await store.visit('/shop/');
  await store.expect('Classic: sold individually, second add', async () => {
    await addToCart('Signed Art Print').catch(() => {});
    await page.waitForURL(/\/product\/signed-art-print\//, { timeout: toastTimeout });
  }, (list, detail) => {
    assert.equal(list.length, 1, detail);
    assert.equal(list[0].type, 'error', detail);
    assert.match(list[0].message, /cannot add another/i, detail);
  });

  await store.visit('/cart/');
  await store.expect('Classic: invalid coupon', async () => {
    await page.fill('#coupon_code', 'not-a-coupon');
    await page.click('button[name="apply_coupon"]');
  }, (list, detail) => {
    assert.equal(list.length, 1, detail);
    assert.equal(list[0].type, 'error', detail);
    assert.match(list[0].message, /does not exist/i, detail);
  });

  await store.visit('/cart/');
  await store.expect('Classic: valid coupon', async () => {
    await page.fill('#coupon_code', 'toast10');
    await page.click('button[name="apply_coupon"]');
  }, (list, detail) => {
    assert.equal(list.length, 1, detail);
    assert.equal(list[0].type, 'success', detail);
    assert.match(list[0].message, /applied/i, detail);
  });

  await store.visit('/checkout/');
  await store.expect('Classic: empty checkout', async () => {
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
}

async function blockStore(store) {
  const { page } = store;
  const addToCart = name => page.locator('li', { hasText: name }).locator('[data-wp-interactive="woocommerce/product-button"] button').click();
  const visibleBanners = () => page.locator('.wc-block-components-notice-banner:visible').count();
  await store.visit('/shop/');

  await store.expect('Blocks: add to cart', () => addToCart('Linen Tote Bag'), (list, detail) => {
    assert.equal(list.length, 1, detail);
    assert.equal(list[0].type, 'success', detail);
    assert.match(list[0].message, /Linen Tote Bag/, detail);
    assert.ok(list[0].action, detail);
  });

  await store.expect('Blocks: sold individually, first add', () => addToCart('Signed Art Print'), (list, detail) => {
    assert.equal(list[0].type, 'success', detail);
  });

  await store.expect('Blocks: sold individually, second add', () => addToCart('Signed Art Print'), async (list, detail) => {
    assert.ok(list.some(toast => toast.type === 'error' && /Signed Art Print/.test(toast.message)), detail);
  }, list => list.some(toast => toast.type === 'error'));
  assert.equal(await visibleBanners(), 0, 'Blocks: notice banners must be hidden while toasts replace them.');

  await store.visit('/cart/');
  await page.getByRole('button', { name: /add coupons?/i }).click();
  await store.expect('Blocks: valid coupon', async () => {
    await page.getByLabel(/enter code/i).fill('toast10');
    await page.getByRole('button', { name: /^apply$/i }).click();
  }, (list, detail) => {
    assert.equal(list.length, 1, detail);
    assert.match(list[0].message, /toast10/i, detail);
  });
  assert.equal(await visibleBanners(), 0, 'Blocks: cart snackbars must be hidden while toasts replace them.');

  // Field validation stays inline next to its input in the blocks, so no toast is expected.
  await store.visit('/checkout/');
  store.toasts = [];
  await page.getByRole('button', { name: /place order/i }).click();
  await page.locator('.wc-block-components-validation-error').first().waitFor({ timeout: toastTimeout });
  await page.waitForTimeout(settleTime);
  assert.ok(!store.toasts.some(toast => /error processing your order/i.test(toast.message)), 'Blocks: generic checkout failure ' + JSON.stringify(store.toasts));
  console.log('ok  Blocks: empty checkout keeps inline validation');
}

buildPackage();
const browser = await launchBrowser();
try {
  for (const [blocks, run] of [[false, classicStore], [true, blockStore]]) {
    const site = await startPlayground({ port, blueprint: writeDemoBlueprint({ blocks }) });
    try {
      const store = await openStore(browser, site.url);
      await run(store);
      assert.deepEqual(store.errors, [], 'Page errors: ' + JSON.stringify(store.errors));
      await store.page.context().close();
    } finally {
      site.stop();
    }
  }
  console.log('Notice flows passed.');
} finally {
  await browser.close();
}
