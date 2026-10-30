import fs from 'node:fs';
const data = JSON.parse(fs.readFileSync(new URL('../assets/config/styling.json', import.meta.url), 'utf8'));
fs.writeFileSync(new URL('../assets/js/wc-toasts-style-data.js', import.meta.url), '/** Generated from assets/config/styling.json. Run node tools/generate-style-data.mjs. */\nwindow.WCToastStyleData = ' + JSON.stringify(data, null, '\t') + ';\n');
