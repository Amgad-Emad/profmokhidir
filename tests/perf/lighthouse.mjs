/*
 | Lighthouse (mobile, simulated throttling) on the key public pages. Prints the
 | four category scores per page and the slowest audits; exits 1 when any
 | category is below the target. Needs Google Chrome on this machine.
 |
 |   npm run perf                      # https://profmokhidir.test
 |   PERF_BASE_URL=https://example.org npm run perf
 |   PERF_PAGES=/,/about npm run perf
 */
import { spawnSync } from 'node:child_process';
import { readFileSync, mkdirSync } from 'node:fs';
import { createRequire } from 'node:module';

const BASE = (process.env.PERF_BASE_URL ?? 'https://profmokhidir.test').replace(/\/$/, '');
const PAGES = (process.env.PERF_PAGES ?? '/,/about,/publications,/pictures').split(',');
const TARGET = Number(process.env.PERF_TARGET ?? 90);
const OUT_DIR = process.env.PERF_OUT ?? 'storage/app/lighthouse';
const CATEGORIES = ['performance', 'accessibility', 'best-practices', 'seo'];

const cli = createRequire(import.meta.url).resolve('lighthouse/cli/index.js');
mkdirSync(OUT_DIR, { recursive: true });

let failures = 0;
const rows = [];

for (const path of PAGES) {
    const out = `${OUT_DIR}/${path === '/' ? 'home' : path.replace(/^\//, '').replace(/\//g, '-')}.json`;
    const run = spawnSync(process.execPath, [
        cli, `${BASE}${path}`, '--output=json', `--output-path=${out}`, '--quiet',
        '--only-categories=' + CATEGORIES.join(','),
        '--chrome-flags=--headless=new --ignore-certificate-errors --no-sandbox',
    ], { stdio: ['ignore', 'inherit', 'inherit'] });

    if (run.status !== 0) {
        console.log(`FAIL ${path}: lighthouse exited with ${run.status}`);
        failures += 1;
        continue;
    }

    const report = JSON.parse(readFileSync(out, 'utf8'));
    const scores = Object.fromEntries(CATEGORIES.map((c) => [c, Math.round((report.categories[c]?.score ?? 0) * 100)]));
    const below = CATEGORIES.filter((c) => scores[c] < TARGET);
    if (below.length) failures += 1;
    rows.push({ page: path, ...scores, ok: below.length === 0 });

    const metrics = ['first-contentful-paint', 'largest-contentful-paint', 'total-blocking-time', 'cumulative-layout-shift', 'speed-index']
        .map((id) => `${id.replace(/-/g, ' ')}: ${report.audits[id]?.displayValue ?? '?'}`).join(' · ');
    console.log(`${path.padEnd(16)} ${CATEGORIES.map((c) => `${c} ${scores[c]}`).join('  ')}  (${metrics})`);

    const diagnostics = Object.values(report.audits)
        .filter((a) => a.score !== null && a.score < 0.9 && a.scoreDisplayMode !== 'informative' && a.scoreDisplayMode !== 'notApplicable')
        .sort((a, b) => a.score - b.score)
        .slice(0, 8);
    for (const audit of diagnostics) console.log(`   - ${audit.id} (${Math.round(audit.score * 100)}): ${audit.title}${audit.displayValue ? ' — ' + audit.displayValue : ''}`);
}

console.log(`\n${rows.filter((r) => r.ok).length}/${PAGES.length} pages at ${TARGET}+ in every category. Reports in ${OUT_DIR}/`);
process.exit(failures === 0 ? 0 : 1);
