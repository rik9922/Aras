'use strict';

/**
 * After @capacitor/assets generates iOS splash, Capacitor may leave luminosity-dark
 * entries in Splash.imageset. This removes dark variants so the same splash is used
 * regardless of Light/Dark appearance.
 */
const fs = require('fs');
const path = require('path');

const splashDir = path.join(__dirname, '..', 'ios', 'App', 'App', 'Assets.xcassets', 'Splash.imageset');
const contentsPath = path.join(splashDir, 'Contents.json');

if (!fs.existsSync(contentsPath)) {
  console.warn('ios-splash-single-mode: Splash.imageset not found, skipping');
  process.exit(0);
}

for (const f of fs.readdirSync(splashDir)) {
  if (f.endsWith('-dark.png')) {
    fs.unlinkSync(path.join(splashDir, f));
    console.log('ios-splash-single-mode: removed', f);
  }
}

const parsed = JSON.parse(fs.readFileSync(contentsPath, 'utf8'));
parsed.images = parsed.images.filter((entry) => !entry.appearances);
fs.writeFileSync(contentsPath, JSON.stringify(parsed, null, 2) + '\n');
console.log('ios-splash-single-mode: Splash uses assets/splash.png for all appearances');
