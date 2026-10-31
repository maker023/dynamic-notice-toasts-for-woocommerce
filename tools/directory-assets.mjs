// Generates WordPress.org directory banners and icons. Banners show a real toast rendered by the built plugin
// in the demo store, so the artwork always matches the shipped design.
import fs from 'node:fs';
import path from 'node:path';
import { buildPackage, writeDemoBlueprint, startPlayground, launchBrowser, visit, root } from './playground.mjs';

const port = Number(process.env.WC_TOASTS_ASSETS_PORT || 9421);
const directory = path.join(root, '.wordpress-org');
const sample = {
  type: 'success',
  category: 'cart',
  title: 'Added to Cart',
  message: '“Wool Beanie” has been added to your cart.',
  icon: 'cart_check',
  action: { text: 'View cart', url: '/cart/' }
};

async function captureToast(browser, url) {
  const page = await browser.newPage({ viewport: { width: 900, height: 500 }, deviceScaleFactor: 2 });
  await visit(page, url + '/shop/');
  await page.evaluate(notice => {
    document.documentElement.style.background = 'transparent';
    document.body.style.background = 'transparent';
    Array.from(document.body.children).forEach(child => { child.style.visibility = 'hidden'; });
    window.WCToasts.show(notice);
    document.getElementById('wc-toasts-container').style.visibility = 'visible';
  }, sample);
  const toast = page.locator('.wc-toast').first();
  await toast.waitFor();
  // Let the entrance animation finish, then hover the message to pause the progress bar.
  await page.waitForTimeout(450);
  await toast.locator('.wc-toast-message').hover();
  await page.waitForTimeout(300);
  const box = await toast.boundingBox();
  const margin = 24;
  const image = await page.screenshot({
    omitBackground: true,
    clip: { x: box.x - margin, y: box.y - margin, width: box.width + margin * 2, height: box.height + margin * 2 }
  });
  await page.close();
  return { image, width: box.width + margin * 2, height: box.height + margin * 2 };
}

function bannerHtml(toast) {
  const font = fs.readFileSync(path.join(root, 'assets/fonts/TikTokSans-Variable.woff2')).toString('base64');
  const scale = 1.45;
  return `<!doctype html><html><head><style>
@font-face { font-family: "TikTok Sans"; src: url(data:font/woff2;base64,${font}) format("woff2"); font-weight: 300 900; }
html, body { margin: 0; width: 1544px; height: 500px; overflow: hidden; }
body { position: relative; background: #F8F7F2; font-family: "TikTok Sans", sans-serif; color: #1F2328; }
.panel { position: absolute; inset: 0; background: #D7EBFF; clip-path: polygon(1090px 0, 1544px 0, 1544px 500px, 924px 500px); }
.copy { position: absolute; left: 72px; top: 96px; }
h1 { margin: 0; font-size: 62px; font-weight: 700; letter-spacing: -0.01em; line-height: 1.05; }
h2 { margin: 14px 0 0; font-size: 38px; font-weight: 400; }
p { margin: 42px 0 0; font-size: 28px; }
.swatches { display: flex; gap: 10px; margin-top: 28px; }
.swatches span { width: 42px; height: 22px; border-radius: 4px; }
.toast { position: absolute; right: 64px; top: 50%; width: ${toast.width * scale}px; transform: translateY(-50%); }
</style></head><body>
<div class="panel"></div>
<div class="copy">
<h1>Dynamic Notice Toasts</h1>
<h2>for WooCommerce</h2>
<p>Four layouts. Your colors.</p>
<div class="swatches"><span style="background:#D7EBFF"></span><span style="background:#D9F752"></span><span style="background:#D8DDE6"></span><span style="background:#1E222A"></span></div>
</div>
<img class="toast" alt="" src="data:image/png;base64,${toast.image.toString('base64')}">
</body></html>`;
}

const icon = fs.readFileSync(path.join(directory, 'icon.svg'), 'utf8');
buildPackage();
const site = await startPlayground({ port, blueprint: writeDemoBlueprint() });
const browser = await launchBrowser();
try {
  const html = bannerHtml(await captureToast(browser, site.url));
  for (const [width, height, scale] of [[1544, 500, 1], [772, 250, 0.5]]) {
    const page = await browser.newPage({ viewport: { width: 1544, height: 500 }, deviceScaleFactor: scale });
    await page.setContent(html);
    await page.evaluate(() => document.fonts.ready);
    await page.screenshot({ path: path.join(directory, 'banner-' + width + 'x' + height + '.png') });
    await page.close();
  }
  const page = await browser.newPage();
  for (const width of [128, 256]) {
    await page.setViewportSize({ width, height: width });
    await page.setContent('<style>body{margin:0}svg{display:block;width:100vw;height:100vh}</style>' + icon);
    await page.screenshot({ path: path.join(directory, 'icon-' + width + 'x' + width + '.png'), omitBackground: true });
  }
  console.log('Directory banners and icons generated.');
} finally {
  await browser.close();
  site.stop();
}
