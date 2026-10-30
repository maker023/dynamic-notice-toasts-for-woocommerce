import { chromium } from '@playwright/test';
import fs from 'node:fs';
import path from 'node:path';
import os from 'node:os';
import assert from 'node:assert/strict';
const executablePath = path.join(os.homedir(), 'AppData/Local/ms-playwright/chromium_headless_shell-1228/chrome-headless-shell-win64/chrome-headless-shell.exe');
const browser = await chromium.launch({ executablePath: process.env.WC_TOASTS_CHROMIUM_PATH || (fs.existsSync(executablePath) ? executablePath : undefined), headless: true });
const page = await browser.newPage({ viewport: { width: 1600, height: 1100 } });
const errors = [];
page.on('pageerror', error => errors.push(error.message));
const base = process.env.WC_TOASTS_TEST_URL || 'http://127.0.0.1:9410';
fs.mkdirSync('test-results',{recursive:true});
try {
  await page.goto(base + '/wp-admin/admin.php?page=wc-toasts-settings&tab=appearance', { waitUntil: 'domcontentloaded', timeout: 120000 });
  if (await page.locator('#user_login').count()) {
    await page.locator('#user_login').fill('admin');
    await page.locator('#user_pass').fill('password');
    await page.locator('#wp-submit').click();
    await page.waitForLoadState('domcontentloaded');
    await page.goto(base + '/wp-admin/admin.php?page=wc-toasts-settings&tab=appearance');
  }
  await page.locator('#wc-toasts-editor-preview .wc-toast').waitFor({timeout:30000});
  assert.equal(await page.locator('.wc-toasts-token-input').count(),24);
  assert.equal(await page.locator('.wc-toasts-token-field > label:visible').count(),24);
  fs.mkdirSync('.wordpress-org',{recursive:true});
  await page.screenshot({path:'.wordpress-org/screenshot-1.png'});
  const data=JSON.parse(fs.readFileSync('assets/config/styling.json','utf8'));
  const rgb=h=>'rgb('+h.slice(1).match(/../g).map(v=>parseInt(v,16)).join(', ')+')';
  let combinations=0;
  for(const layout of ['editorial','utility','retro-outline','expressive']) {
    await page.locator('#wc-toasts-layout-select').selectOption(layout);
    for(const [key,palette] of Object.entries(data.palettes)) {
      await page.locator('#wc-toasts-palette-select').selectOption(key);
      const color=await page.locator('#wc-toasts-editor-preview .wc-toast').evaluate(element=>({background:getComputedStyle(element).backgroundColor,ink:getComputedStyle(element).color}));
      assert.equal(color.background,rgb(palette.surface),layout+' '+key);
      assert.equal(color.ink,rgb(palette.ink),layout+' '+key);
      if(layout==='utility'&&key==='lime-charcoal') {
        await page.waitForTimeout(220);
        const action=await page.locator('#wc-toasts-editor-preview .wc-toast-action-btn').evaluate(e=>({background:getComputedStyle(e).backgroundColor,color:getComputedStyle(e).color}));
        assert.deepEqual(action,{background:rgb('#2B2B2B'),color:rgb('#FFFFFF')});
      }
      combinations++;
    }
  }
  await page.locator('#wc-toasts-layout-select').selectOption('retro-outline');
  await page.locator('#wc-toasts-palette-select').selectOption('sky-aubergine');
  await page.locator('#wc-toasts-token-surface').evaluate(input=>{input.value='#aabbcc';input.dispatchEvent(new Event('input',{bubbles:true}));});
  assert.equal(await page.locator('#wc-toasts-editor-preview .wc-toast').evaluate(e=>getComputedStyle(e).backgroundColor),rgb('#aabbcc'));
  await page.locator('#wc-toasts-token-action-hover-bg').evaluate(input=>{input.value='#123456';input.dispatchEvent(new Event('input',{bubbles:true}));});
  await page.locator('#wc-toasts-editor-preview .wc-toast-action-btn').hover();
  await page.waitForTimeout(220);
  assert.equal(await page.locator('#wc-toasts-editor-preview .wc-toast-action-btn').evaluate(e=>getComputedStyle(e).backgroundColor),rgb('#123456'));
  await Promise.all([page.waitForNavigation({waitUntil:'domcontentloaded'}),page.locator('button[name=wc_toasts_save_settings]').click()]);
  await page.locator('#wc-toasts-editor-preview .wc-toast').waitFor();
  assert.equal(await page.locator('#wc-toasts-token-surface').inputValue(),'#aabbcc');
  assert.equal(await page.locator('#wc-toasts-layout-select').inputValue(),'retro-outline');
  assert.equal(await page.locator('#wc-toasts-palette-select').inputValue(),'sky-aubergine');
  await page.locator('#wc-toasts-reset-colors').click();
  assert.equal(await page.locator('#wc-toasts-token-surface').inputValue(),'');
  assert.equal(await page.locator('#wc-toasts-editor-preview .wc-toast').evaluate(e=>getComputedStyle(e).backgroundColor),rgb(data.palettes['sky-aubergine'].surface));
  await Promise.all([page.waitForNavigation({waitUntil:'domcontentloaded'}),page.locator('button[name=wc_toasts_save_settings]').click()]);
  await page.goto(base+'/');
  await page.waitForFunction(()=>!!window.WCToasts);
  const security=await page.evaluate(()=>{
    const config=window.wc_toasts_frontend_params;config.settings.duration_success=0;config.settings.duration_info=0;config.settings.deduplication=false;
    const toast=window.WCToasts.show({type:'success',message:'Security sample',raw_html:'<strong>Safe content</strong><img src=x onerror="window.__unsafe=true"><a href="javascript:window.__unsafe=true">Unsafe URL</a>',action:{url:'javascript:alert(1)',text:'Do not render'}}).element;
    let renderHasClose=false;document.addEventListener('wc-toasts:render',event=>{renderHasClose=!!event.detail.element.querySelector('.wc-toast-close-btn')},{once:true});
    window.WCToasts.show({message:'Render hook test'});
    document.addEventListener('wc-toasts:before-show',event=>event.preventDefault(),{once:true});
    const canceled=window.WCToasts.show({message:'Should be canceled'});
    return {unsafe:!!window.__unsafe,images:toast.querySelectorAll('img').length,links:toast.querySelectorAll('[href^="javascript:"]').length,buttons:toast.querySelectorAll('.wc-toast-action-btn').length,bold:toast.querySelector('strong').textContent,canceled:canceled===null,renderHasClose};
  });
  assert.deepEqual(security,{unsafe:false,images:0,links:0,buttons:0,bold:'Safe content',canceled:true,renderHasClose:true});
  const suppression=await page.evaluate(async()=>{
    const settings=window.wc_toasts_frontend_params.settings;
    settings.hide_default_notices=false;
    const visible=document.createElement('div');visible.className='woocommerce-info';visible.textContent='Original banner stays visible';document.body.appendChild(visible);
    await new Promise(resolve=>setTimeout(resolve,30));const visibleStyle=visible.style.display;visible.remove();
    settings.hide_default_notices=true;
    const hidden=document.createElement('div');hidden.className='woocommerce-info';hidden.textContent='Original banner is suppressed';document.body.appendChild(hidden);
    await new Promise(resolve=>setTimeout(resolve,30));const hiddenStyle=hidden.style.display;hidden.remove();
    return {visibleStyle,hiddenStyle};
  });
  assert.deepEqual(suppression,{visibleStyle:'',hiddenStyle:'none'});
  await page.evaluate(()=>{
    const settings=window.wc_toasts_frontend_params.settings;settings.duration_success=100;settings.pause_on_hover=false;
    const record=window.WCToasts.success('Keyboard focus pause',{action:{url:'/cart/',text:'View cart'}});record.element.id='keyboard-pause-test';record.element.querySelector('a').focus();
  });
  await page.waitForTimeout(180);assert.ok(await page.locator('#keyboard-pause-test:not(.is-dismissing)').count());
  await page.locator('#keyboard-pause-test a').evaluate(e=>e.blur());await page.waitForTimeout(450);assert.equal(await page.locator('#keyboard-pause-test').count(),0);
  const denied=await page.request.post(base+'/wp-admin/admin-ajax.php',{form:{action:'wc_toasts_reset_settings',security:'invalid-review-nonce'}});
  assert.equal(denied.status(),403);
  let productionResponsiveCombinations=0;
  for(const width of [375,320]) {
    await page.setViewportSize({width,height:900});
    for(const layout of ['editorial','utility','retro-outline','expressive'])for(const palette of Object.keys(data.palettes)){
      const result=await page.evaluate(({layout,palette})=>{
        window.WCToasts.clearAll();document.getElementById('wc-toasts-container')?.remove();
        const settings=window.wc_toasts_frontend_params.settings;settings.layout=layout;settings.palette=palette;settings.duration_error=0;
        const {element}=window.WCToasts.show({type:'error',category:'checkout',title:'Payment needs attention',message:'Check your payment details.',icon:'alert_triangle',action:{text:'Review payment',url:'/checkout/'}});
        const bounds=element.getBoundingClientRect();
        return {overflow:element.scrollWidth>element.clientWidth+1,inside:[...element.children].every(node=>{const rect=node.getBoundingClientRect();return rect.left>=bounds.left-1&&rect.right<=bounds.right+1})};
      },{layout,palette});
      assert.ok(!result.overflow&&result.inside,JSON.stringify({width,layout,palette,result}));productionResponsiveCombinations++;
    }
  }
  await page.setViewportSize({width:1600,height:1100});
  await page.screenshot({path:'test-results/storefront.png',fullPage:true});
  await page.goto(base+'/wp-content/plugins/woocommerce-toasts/dev-preview.html?concept=retro-outline&palette=default-dark');
  await page.locator('#toast-host .concept-toast').waitFor();
  assert.equal(await page.locator('[data-palette]').count(),4);
  assert.equal(await page.locator('#token-fields input').count(),24);
  await page.locator('[data-concept=editorial]').click();
  const margin=await page.locator('#toast-host .concept-toast').evaluate(e=>({padding:getComputedStyle(e).padding,close:getComputedStyle(e.querySelector('.toast-close')).backgroundColor,weight:getComputedStyle(e.querySelector('.toast-action')).fontWeight}));
  assert.equal(margin.padding,'20px');assert.equal(margin.weight,'400');assert.notEqual(margin.close,'rgba(0, 0, 0, 0)');
  await page.locator('#toast-host .toast-action').hover();await page.waitForTimeout(220);
  assert.equal(await page.locator('#toast-host .toast-action').evaluate(e=>getComputedStyle(e).backgroundColor),'rgba(0, 0, 0, 0)');
  await page.locator('[data-concept=utility]').click();await page.locator('[data-palette=lime-charcoal]').click();
  const lime=await page.locator('#toast-host .toast-action').evaluate(e=>({background:getComputedStyle(e).backgroundColor,color:getComputedStyle(e).color}));
  assert.deepEqual(lime,{background:rgb('#2B2B2B'),color:rgb('#FFFFFF')});
  await page.locator('[data-concept=retro-outline]').click();await page.locator('[data-palette=default-dark]').click();
  const retro=await page.locator('#toast-host .concept-toast').evaluate(e=>({gap:getComputedStyle(e).gap,border:getComputedStyle(e.querySelector('.utility-symbol')).borderTopWidth,background:getComputedStyle(e.querySelector('.utility-symbol')).backgroundColor,close:getComputedStyle(e.querySelector('.toast-close')).backgroundColor}));
  assert.equal(retro.gap,'8px');assert.equal(retro.border,'1px');assert.equal(retro.background,'rgba(0, 0, 0, 0)');assert.notEqual(retro.close,'rgba(0, 0, 0, 0)');
  await page.locator('[data-concept=expressive]').click();
  const canvas=await page.locator('#toast-host .concept-toast').evaluate(e=>({height:getComputedStyle(e).minHeight,body:getComputedStyle(e.querySelector('.expressive-body')).padding,mark:getComputedStyle(e.querySelector('.expressive-mark')).padding,icon:e.querySelector('.expressive-mark svg').getBoundingClientRect().width}));
  assert.ok(['auto','0px'].includes(canvas.height));assert.equal(canvas.body,'16px');assert.equal(canvas.mark,'18px');assert.ok(canvas.icon<=36);
  await page.locator('[data-concept=retro-outline]').click();
  await page.locator('#token-fields input[data-token=surface]').fill('#112233');
  assert.equal(await page.locator('#toast-host .concept-toast').evaluate(e=>getComputedStyle(e).backgroundColor),rgb('#112233'));
  assert.equal(await page.locator('#token-preview .concept-toast').evaluate(e=>getComputedStyle(e).backgroundColor),rgb('#112233'));
  await page.locator('#reset-token-colors').click();
  assert.equal(await page.locator('#toast-host .concept-toast').evaluate(e=>getComputedStyle(e).backgroundColor),rgb(data.palettes['default-dark'].surface));
  await page.locator('#stage').screenshot({path:'.wordpress-org/screenshot-2.png'});
  const download=page.waitForEvent('download');await page.locator('#export-token-colors').click();assert.equal((await download).suggestedFilename(),'toast-style.json');
  let responsiveCombinations=0;
  for(const width of [375,320]) {
    await page.setViewportSize({width,height:900});
    for(const concept of ['editorial','utility','retro-outline','expressive'])for(const palette of Object.keys(data.palettes))for(const event of ['success','error','info']) {
      const result=await page.evaluate(({concept,palette,event})=>{
        document.querySelector('[data-concept="'+concept+'"]').click();document.querySelector('[data-palette="'+palette+'"]').click();document.querySelector('[data-event="'+event+'"]').click();
        const toast=document.querySelector('#toast-host .concept-toast'),bounds=toast.getBoundingClientRect();
        return {overflow:toast.scrollWidth>toast.clientWidth+1,inside:[...toast.children].every(node=>{const rect=node.getBoundingClientRect();return rect.left>=bounds.left-1&&rect.right<=bounds.right+1}),pageOverflow:document.documentElement.scrollWidth>innerWidth};
      },{concept,palette,event});
      assert.ok(!result.overflow&&result.inside&&!result.pageOverflow,JSON.stringify({width,concept,palette,event,result}));responsiveCombinations++;
    }
  }
  assert.deepEqual(errors,[]);
  const report={combinations,responsiveCombinations,productionResponsiveCombinations,invalidNonce:'rejected',layoutRefinements:'passed',labels:'passed',liveEditor:'passed',savedSettings:'passed',reset:'passed',safeHTML:'passed',extensionEvents:'passed',standalonePreview:'passed',export:'passed',browser:browser.version(),errors};
  fs.writeFileSync('test-results/browser-review.json',JSON.stringify(report,null,2));console.log(JSON.stringify(report));
  fs.mkdirSync('test-results',{recursive:true});
  await page.screenshot({path:'test-results/design-lab.png',fullPage:true});
} finally { await browser.close(); }
