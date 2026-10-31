// Shared helpers for tools that drive the built plugin in a disposable Playground site.
import { chromium } from '@playwright/test';
import { spawn, spawnSync } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import assert from 'node:assert/strict';
import { packageName, pluginSlug } from './release-config.mjs';

export const root = path.resolve(import.meta.dirname, '..');
export const results = path.join(root, 'test-results');

export function buildPackage() {
  const build = spawnSync(process.execPath, [path.join(import.meta.dirname, 'build-release.mjs')], { cwd: root, stdio: 'inherit' });
  assert.equal(build.status, 0, 'Release build failed.');
}

/**
 * Write the directory demo blueprint with the mounted build activated instead of the WordPress.org download.
 * The `blocks` variant keeps the default block theme and switches Cart and Checkout to their blocks.
 */
export function writeDemoBlueprint({ blocks = false } = {}) {
  const blueprint = JSON.parse(fs.readFileSync(path.join(root, '.wordpress-org/blueprints/blueprint.json'), 'utf8'));
  blueprint.steps = blueprint.steps
    .filter(step => !(blocks && step.step === 'installTheme'))
    .map(step => step.pluginData && step.pluginData.slug === pluginSlug
      ? { step: 'activatePlugin', pluginPath: pluginSlug + '/woocommerce-toasts.php' }
      : step);
  if (blocks) {
    blueprint.steps.push({
      step: 'runPHP',
      // WooCommerce's own default block markup; the blocks render nothing without their inner blocks.
      code: "<?php require_once '/wordpress/wp-load.php'; foreach ( array( 'cart', 'checkout' ) as $page ) { $content = new ReflectionMethod( 'WC_Install', 'get_' . $page . '_block_content' ); $content->setAccessible( true ); wp_update_post( array( 'ID' => wc_get_page_id( $page ), 'post_content' => $content->invoke( null ) ) ); }"
    });
  }
  fs.mkdirSync(results, { recursive: true });
  const file = path.join(results, 'demo-blueprint' + (blocks ? '-blocks' : '') + '.json');
  fs.writeFileSync(file, JSON.stringify(blueprint));
  return file;
}

export async function startPlayground({ port, blueprint }) {
  const cli = path.join(root, 'node_modules/@wp-playground/cli/wp-playground.js');
  const server = spawn(process.execPath, [cli, 'server', '--blueprint=' + blueprint, '--port=' + port, '--mount-dir', path.join(root, 'dist', packageName), '/wordpress/wp-content/plugins/' + pluginSlug], { cwd: root });
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
  return { url: 'http://127.0.0.1:' + port, stop: () => server.kill() };
}

export function launchBrowser() {
  const cached = path.join(os.homedir(), 'AppData/Local/ms-playwright/chromium_headless_shell-1228/chrome-headless-shell-win64/chrome-headless-shell.exe');
  return chromium.launch({ headless: true, executablePath: process.env.WC_TOASTS_CHROMIUM_PATH || (fs.existsSync(cached) ? cached : undefined) });
}

// The local server can answer a navigation with a cookie redirect; wait for the resulting page instead.
export async function visit(page, url) {
  await page.goto(url).catch(() => {});
  await page.waitForLoadState('load');
}
