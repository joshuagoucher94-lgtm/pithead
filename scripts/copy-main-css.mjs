import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.join(__dirname, '..');
const distAstro = path.join(root, 'astro', 'dist', '_astro');
const outFile = path.join(root, 'php', 'public', 'assets', 'app.css');

if (!fs.existsSync(distAstro)) {
  console.warn('copy-main-css: astro/dist/_astro missing; run astro build from astro/');
  process.exit(0);
}

const files = fs.readdirSync(distAstro).filter((f) => f.endsWith('.css'));
if (files.length === 0) {
  console.warn('copy-main-css: no css in dist/_astro');
  process.exit(0);
}

// Prefer the largest file (usually the main bundle)
let best = files[0];
let bestSize = 0;
for (const f of files) {
  const s = fs.statSync(path.join(distAstro, f)).size;
  if (s > bestSize) {
    bestSize = s;
    best = f;
  }
}

fs.mkdirSync(path.dirname(outFile), { recursive: true });
fs.copyFileSync(path.join(distAstro, best), outFile);
console.log('copy-main-css:', best, '-> php/public/assets/app.css');
