import fs from 'node:fs';
import path from 'node:path';
import os from 'node:os';
import { chromium } from '@playwright/test';
const cached=path.join(os.homedir(),'AppData/Local/ms-playwright/chromium_headless_shell-1228/chrome-headless-shell-win64/chrome-headless-shell.exe');
const browser=await chromium.launch({headless:true,executablePath:process.env.WC_TOASTS_CHROMIUM_PATH||(fs.existsSync(cached)?cached:undefined)});
const page=await browser.newPage();
const banner=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1544 500" width="1544" height="500">
<rect width="1544" height="500" fill="#F8F7F2"/>
<path d="M1090 0h454v500h-620z" fill="#D7EBFF"/>
<g font-family="Arial,sans-serif" fill="#1F2328">
<text x="72" y="154" font-size="62" font-weight="700">Dynamic Notice Toasts</text>
<text x="74" y="214" font-size="38">for WooCommerce</text>
<text x="75" y="293" font-size="28">Four layouts. Your colors.</text>
<g transform="translate(76 339)"><rect width="42" height="22" rx="4" fill="#D7EBFF"/><rect x="52" width="42" height="22" rx="4" fill="#D9F752"/><rect x="104" width="42" height="22" rx="4" fill="#D8DDE6"/><rect x="156" width="42" height="22" rx="4" fill="#1E222A"/></g>
</g>
<rect x="908" y="201" width="552" height="126" rx="8" fill="#3B2350" opacity=".24"/>
<rect x="900" y="193" width="552" height="126" rx="8" fill="#1E222A" stroke="#F4F1E8" stroke-width="2"/>
<g font-family="Arial,sans-serif" fill="#F4F1E8"><text x="982" y="244" font-size="25" font-weight="600">Added to your bag</text><text x="982" y="277" font-size="20">Ready when you are.</text></g>
<rect x="916" y="223" width="48" height="48" rx="6" fill="none" stroke="#F4F1E8" stroke-width="2"/>
<path d="m929 246 8 8 15-19" fill="none" stroke="#F4F1E8" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
<rect x="1283" y="233" width="102" height="44" rx="6" fill="none" stroke="#F4F1E8"/>
<text x="1301" y="261" fill="#F4F1E8" font-family="Arial,sans-serif" font-size="18">View bag</text>
<rect x="1396" y="233" width="40" height="44" rx="6" fill="#4B7BFF"/>
<path d="m1409 249 14 14m0-14-14 14" stroke="#F4F1E8" stroke-width="2"/>
</svg>`;
try {
  for(const [width,height] of [[1544,500],[772,250]]){
    await page.setViewportSize({width,height});
    await page.setContent('<style>body{margin:0;overflow:hidden}svg{display:block;width:100vw;height:100vh}</style>'+banner);
    await page.screenshot({path:'.wordpress-org/banner-'+width+'x'+height+'.png'});
  }
  const icon=fs.readFileSync('.wordpress-org/icon.svg','utf8');
  for(const width of [128,256]){
    await page.setViewportSize({width,height:width});
    await page.setContent('<style>body{margin:0}svg{display:block;width:100vw;height:100vh}</style>'+icon);
    await page.screenshot({path:'.wordpress-org/icon-'+width+'x'+width+'.png',omitBackground:true});
  }
  console.log('Directory banners and icons generated.');
}finally{await browser.close()}
