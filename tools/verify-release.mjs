import fs from 'node:fs';
import path from 'node:path';
import assert from 'node:assert/strict';
import { unzipSync } from 'fflate';
import { packageName, pluginSlug } from './release-config.mjs';
const root=path.resolve(import.meta.dirname,'..');
const zip=fs.readFileSync(path.join(root,'dist',packageName+'.zip'));
const files=unzipSync(zip);
const allowedRoots=new Set(['assets','includes','languages','templates','woocommerce-toasts.php','uninstall.php','readme.txt','LICENSE']);
assert.ok(files['woocommerce-toasts.php'],'The main PHP file must be at the ZIP root, without a containing folder.');
assert.ok(files['assets/config/styling.json'],'The assets directory must be at the ZIP root.');
for(const [name,bytes] of Object.entries(files)){
  assert.ok(allowedRoots.has(name.split('/')[0])&&!name.includes('../'),'Unexpected archive root: '+name);
  if(name.endsWith('/'))continue;
  const relative=name;
  assert.ok(!/^(node_modules|vendor|tools|tests|test-results|\.git|\.runtime|\.wordpress-org)\//.test(relative),'Development file in package: '+relative);
  assert.deepEqual(Buffer.from(bytes),fs.readFileSync(path.join(root,relative)),'Package differs from checked source: '+relative);
}
const decode=name=>new TextDecoder().decode(files[name]);
const main=decode('woocommerce-toasts.php');
const readme=decode('readme.txt');
assert.ok(main.includes('Version:           1.3.0'));
assert.equal(main.match(/^ \* Text Domain:\s*(\S+)/m)?.[1],pluginSlug,'Text Domain must match the WordPress.org submission slug.');
assert.ok(readme.includes('Stable tag: 1.3.0'));
const data=JSON.parse(decode('assets/config/styling.json'));
assert.deepEqual(Object.keys(data.palettes),['sky-aubergine','lime-charcoal','default-light','default-dark']);
for(const name of ['LICENSE','assets/fonts/TikTokSans-OFL.txt','assets/icons/ICONOIR-LICENSE.txt','languages/'+pluginSlug+'.pot','assets/js/wc-toasts-styling.js'])assert.ok(files[name],name+' is missing');
assert.ok(decode('languages/'+pluginSlug+'.pot').includes('X-Domain: '+pluginSlug+'\\n'),'Translation template domain must match the submission slug.');
assert.ok(!files['languages/dynamic-notice-toasts.pot'],'Obsolete translation template in package.');
for(const name of Object.keys(files).filter(name=>name.endsWith('.php'))){
  const source=decode(name);
  assert.ok(!/<(script|style)\b/i.test(source),'Inline script or style tag in '+name+'; use the wp_enqueue functions.');
  assert.ok(!source.includes('wc_toasts[custom_css]')&&!source.includes('wp_add_inline_style'),'Arbitrary CSS setting or output in '+name+'.');
}
assert.ok(!Object.keys(files).some(name=>name.endsWith('.html')),'Standalone HTML pages must not ship in the package.');
const report={package:packageName,slug:pluginSlug,textDomain:pluginSlug,archiveLayout:'flat',files:Object.keys(files).filter(name=>!name.endsWith('/')).length,bytes:zip.length,sourceMatch:true,version:'1.3.0',palettes:4,developmentFilesExcluded:true};
fs.mkdirSync(path.join(root,'test-results'),{recursive:true});
fs.writeFileSync(path.join(root,'test-results/package-review.json'),JSON.stringify(report,null,2));
console.log(JSON.stringify(report));
