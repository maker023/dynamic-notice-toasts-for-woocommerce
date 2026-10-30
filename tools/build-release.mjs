import fs from 'node:fs';
import path from 'node:path';
import { zipSync } from 'fflate';
import { createHash } from 'node:crypto';
import './generate-style-data.mjs';
import { packageName } from './release-config.mjs';
const root = path.resolve(import.meta.dirname, '..');
const destination = path.join(root, 'dist', packageName);
const license = path.join(root, 'LICENSE');
if (!fs.existsSync(license)) {
  const response = await fetch('https://raw.githubusercontent.com/WordPress/WordPress/master/license.txt');
  if (!response.ok) throw new Error('Could not download the GPL license');
  fs.writeFileSync(license, await response.text());
}
const releaseRoot = path.resolve(root, 'dist');
if (destination !== path.join(releaseRoot, packageName) || !destination.startsWith(releaseRoot + path.sep)) throw new Error('Unsafe release destination');
fs.rmSync(destination, { recursive: true, force: true });
fs.mkdirSync(destination, { recursive: true });
const files = ['assets', 'includes', 'languages', 'templates', 'woocommerce-toasts.php', 'uninstall.php', 'readme.txt', 'LICENSE'];
for (const file of files) fs.cpSync(path.join(root, file), path.join(destination, file), { recursive: true });
const zip = path.join(root, 'dist', packageName + '.zip');
const archive = {};
function collect(source, relative) {
  if (fs.statSync(source).isDirectory()) {
    for (const child of fs.readdirSync(source)) collect(path.join(source, child), relative + '/' + child);
  } else archive[relative] = fs.readFileSync(source);
}
for (const file of files) collect(path.join(root, file), file);
fs.writeFileSync(zip, zipSync(archive, { level: 9 }));
fs.writeFileSync(path.join(root,'dist/SHA256SUMS.txt'),createHash('sha256').update(fs.readFileSync(zip)).digest('hex')+'  '+packageName+'.zip\n');
console.log('Release package: ' + zip);
