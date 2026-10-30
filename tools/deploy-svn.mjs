// Mirrors the verified release build into WordPress.org SVN: trunk, a version tag, and directory assets.
// Dry run by default. `--commit` publishes and reads the SVN password from SVN_PASSWORD.
import fs from 'node:fs';
import path from 'node:path';
import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import { packageName, svnUrl, svnUsername } from './release-config.mjs';

const root = path.resolve(import.meta.dirname, '..');
const build = path.join(root, 'dist', packageName);
const directoryAssets = path.join(root, '.wordpress-org');
const checkout = path.join(root, '.svn-deploy');
const commit = process.argv.includes('--commit');
const mimeTypes = { '.png': 'image/png', '.jpg': 'image/jpeg', '.jpeg': 'image/jpeg', '.gif': 'image/gif', '.svg': 'image/svg+xml' };
const windowsSvn = 'C:\\Program Files\\SlikSvn\\bin\\svn.exe';
const svnBinary = process.env.SVN_BIN || (process.platform === 'win32' && fs.existsSync(windowsSvn) ? windowsSvn : 'svn');

function run(command, args, options = {}) {
  const result = spawnSync(command, args, { cwd: checkout, encoding: 'utf8', stdio: options.input === undefined ? ['ignore', 'pipe', 'inherit'] : ['pipe', 'pipe', 'inherit'], ...options });
  if (result.error) throw result.error;
  if (result.status !== 0) throw new Error(command + ' ' + args[0] + ' failed with exit code ' + result.status);
  return result.stdout;
}
const svn = (...args) => run(svnBinary, args);

function listFiles(directory, relative = '') {
  return fs.readdirSync(path.join(directory, relative), { withFileTypes: true }).flatMap(entry => {
    if (entry.name === '.svn') return [];
    const child = path.posix.join(relative, entry.name);
    return entry.isDirectory() ? listFiles(directory, child) : [child];
  });
}

function mirror(source, destination) {
  const wanted = new Set(listFiles(source));
  for (const file of listFiles(destination)) if (!wanted.has(file)) fs.rmSync(path.join(destination, file));
  for (const file of wanted) fs.cpSync(path.join(source, file), path.join(destination, file));
  for (const file of listFiles(destination)) assert.deepEqual(fs.readFileSync(path.join(destination, file)), fs.readFileSync(path.join(source, file)), 'Mirror mismatch: ' + file);
}

for (const script of ['build-release.mjs', 'verify-release.mjs']) run(process.execPath, [path.join(import.meta.dirname, script)], { cwd: root, stdio: 'inherit' });

const main = fs.readFileSync(path.join(build, 'woocommerce-toasts.php'), 'utf8');
const readme = fs.readFileSync(path.join(build, 'readme.txt'), 'utf8');
const version = (main.match(/^ \* Version:\s*(\S+)/m) || [])[1];
assert.ok(version, 'Plugin header has no Version.');
assert.equal((readme.match(/^Stable tag:\s*(\S+)/m) || [])[1], version, 'readme.txt Stable tag must match the plugin Version.');
const remoteTags = spawnSync(svnBinary, ['ls', svnUrl + '/tags'], { encoding: 'utf8' });
if (remoteTags.error) throw remoteTags.error;
assert.ok(!remoteTags.stdout.split(/\r?\n/).includes(version + '/'), 'Tag ' + version + ' already exists in SVN. Bump the version before releasing.');

if (fs.existsSync(path.join(checkout, '.svn'))) {
  svn('revert', '--recursive', '.');
  svn('cleanup', '--remove-unversioned');
  svn('update');
} else {
  fs.mkdirSync(checkout, { recursive: true });
  svn('checkout', '--depth', 'immediates', svnUrl, '.');
  svn('update', '--set-depth', 'infinity', 'trunk', 'assets');
}

mirror(build, path.join(checkout, 'trunk'));
mirror(directoryAssets, path.join(checkout, 'assets'));
svn('add', '--force', '--parents', 'trunk', 'assets');
const missing = svn('status', 'trunk', 'assets').split(/\r?\n/).filter(line => line.startsWith('!')).map(line => line.slice(8));
if (missing.length) svn('delete', ...missing);
for (const file of listFiles(path.join(checkout, 'assets'))) {
  const type = mimeTypes[path.extname(file).toLowerCase()];
  if (type) svn('propset', 'svn:mime-type', type, path.posix.join('assets', file));
}
svn('copy', 'trunk', 'tags/' + version);

const status = svn('status', '--quiet');
console.log(status || 'No changes to commit.');
if (!commit) {
  console.log('Dry run complete for ' + version + '. Re-run with --commit to publish.');
} else if (status) {
  assert.ok(process.env.SVN_PASSWORD, 'Set SVN_PASSWORD to commit.');
  run(svnBinary, ['commit', '--username', svnUsername, '--password-from-stdin', '--no-auth-cache', '--non-interactive', '-m', 'Release ' + version], { input: process.env.SVN_PASSWORD });
  console.log('Published ' + version + ' to ' + svnUrl);
}
