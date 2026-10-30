import fs from 'node:fs';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import { packageName, pluginSlug } from './release-config.mjs';
const root=path.resolve(import.meta.dirname,'..');
const {php,args,site}=JSON.parse(fs.readFileSync(path.join(root,'.runtime/php-command.json'),'utf8'));
const reports=path.join(root,'test-results');fs.mkdirSync(reports,{recursive:true});
// Keep the existing local fixture path; check against the actual submission slug below.
const pluginDestination=path.resolve(site,'wp-content/plugins/dynamic-notice-toasts');
if(!pluginDestination.startsWith(path.resolve(root,'.runtime')+path.sep)||!pluginDestination.endsWith(path.join('plugins','dynamic-notice-toasts')))throw new Error('Unsafe review destination');
fs.rmSync(pluginDestination,{recursive:true,force:true});
fs.cpSync(path.join(root,'dist',packageName),pluginDestination,{recursive:true});
function run(label,command,options={}){
  const result=spawnSync(php,command,{encoding:'utf8',windowsHide:true,maxBuffer:30*1024*1024,...options});
  fs.writeFileSync(path.join(reports,label+'.txt'),result.stdout+'\n'+result.stderr);
  console.log(label+': '+result.stdout+' '+result.stderr);
  if(result.status!==0)throw new Error(label+' failed with exit '+result.status);
  return result.stdout;
}
run('runtime-review',[...args.slice(0,-2),path.join(root,'tests/runtime-review.php')],{env:{...process.env,WC_TOASTS_TEST_ROOT:site}});
run('plugin-check',[...args,'plugin','check','dynamic-notice-toasts','--slug='+pluginSlug,'--format=strict-json','--include-experimental','--require='+path.join(site,'wp-content/plugins/plugin-check/cli.php')]);
run('pot-generation',[...args,'i18n','make-pot',path.join(root,'dist',packageName),path.join(root,'languages',pluginSlug+'.pot'),'--domain='+pluginSlug]);
