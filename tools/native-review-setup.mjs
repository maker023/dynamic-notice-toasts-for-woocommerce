import fs from 'node:fs';
import path from 'node:path';
import { unzipSync } from 'fflate';
import { execFileSync } from 'node:child_process';
import { packageName } from './release-config.mjs';
const root=path.resolve(import.meta.dirname,'..');
const runtime=path.join(root,'.runtime');fs.mkdirSync(runtime,{recursive:true});
async function download(url,destination){
  if(fs.existsSync(destination))return fs.readFileSync(destination);
  console.log('Downloading '+url);
  const response=await fetch(url);if(!response.ok)throw new Error(url+' returned '+response.status);
  const bytes=new Uint8Array(await response.arrayBuffer());fs.writeFileSync(destination,bytes);return bytes;
}
function extract(bytes,destination){
  for(const [name,data] of Object.entries(unzipSync(bytes))){
    const target=path.resolve(destination,name);
    if(!target.startsWith(path.resolve(destination)+path.sep))throw new Error('Unsafe archive path');
    if(name.endsWith('/')){fs.mkdirSync(target,{recursive:true});continue;}
    fs.mkdirSync(path.dirname(target),{recursive:true});fs.writeFileSync(target,data);
  }
}
const releases=await(await fetch('https://windows.php.net/downloads/releases/releases.json')).json();
const phpRelease=releases['8.3'];
const packageInfo=phpRelease['nts-vs16-x64']||phpRelease['nts-vs17-x64'];
if(!packageInfo)throw new Error('Supported PHP Windows package was not found');
const phpDir=path.join(runtime,'php');
if(!fs.existsSync(path.join(phpDir,'php.exe')))extract(await download('https://windows.php.net/downloads/releases/'+packageInfo.zip.path,path.join(runtime,'php.zip')),phpDir);
const site=path.join(runtime,'wordpress');
if(!fs.existsSync(path.join(site,'wp-load.php')))extract(await download('https://wordpress.org/latest.zip',path.join(runtime,'wordpress.zip')),runtime);
for(const slug of ['sqlite-database-integration','woocommerce','plugin-check']){
  const plugins=path.join(site,'wp-content/plugins');
  if(!fs.existsSync(path.join(plugins,slug)))extract(await download('https://downloads.wordpress.org/plugin/'+slug+'.latest-stable.zip',path.join(runtime,slug+'.zip')),plugins);
}
fs.copyFileSync(path.join(site,'wp-content/plugins/sqlite-database-integration/db.copy'),path.join(site,'wp-content/db.php'));
const config=`<?php
define('DB_NAME','wordpress');define('DB_USER','');define('DB_PASSWORD','');define('DB_HOST','localhost');
define('DB_CHARSET','utf8');define('DB_COLLATE','');define('WP_DEBUG',true);define('WP_DEBUG_LOG',true);define('WP_DEBUG_DISPLAY',false);
define('WP_ENVIRONMENT_TYPE','local');define('WP_AUTO_UPDATE_CORE',false);define('AUTOMATIC_UPDATER_DISABLED',true);
$table_prefix='wp_';if(!defined('ABSPATH'))define('ABSPATH',__DIR__.'/');require_once ABSPATH.'wp-settings.php';
`;
fs.writeFileSync(path.join(site,'wp-config.php'),config);
const phar=path.join(runtime,'wp-cli.phar');await download('https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar',phar);
const php=path.join(phpDir,'php.exe');
const args=['-d','extension_dir='+path.join(phpDir,'ext'),'-d','extension=php_curl.dll','-d','extension=php_mbstring.dll','-d','extension=php_pdo_sqlite.dll','-d','extension=php_sqlite3.dll','-d','extension=php_openssl.dll','-d','extension=php_zip.dll','-d','memory_limit=1024M',phar,'--path='+site];
function wp(command){return execFileSync(php,[...args,...command],{encoding:'utf8',windowsHide:true,maxBuffer:10*1024*1024});}
try{wp(['core','is-installed']);}catch{console.log(wp(['core','install','--url=http://127.0.0.1:9412','--title=Toast Review','--admin_user=admin','--admin_password=password','--admin_email=review@example.test','--skip-email']));}
const plugin=path.join(site,'wp-content/plugins/dynamic-notice-toasts');fs.cpSync(path.join(root,'dist',packageName),plugin,{recursive:true});
console.log(wp(['plugin','activate','woocommerce','dynamic-notice-toasts','plugin-check']));
console.log(wp(['core','version']));
fs.writeFileSync(path.join(runtime,'php-command.json'),JSON.stringify({php,args,site},null,2));
console.log('Native review environment prepared.');
