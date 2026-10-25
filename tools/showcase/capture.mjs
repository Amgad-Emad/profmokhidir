/*
 | Captures the screenshots for the client progress report by walking every
 | persona's journey in Google Chrome (Playwright) against the local site:
 |
 |   npm run showcase:capture                     # every journey, then teardown
 |   npm run showcase:capture -- --only=visitor   # one journey (comma-separate several)
 |   npm run showcase:capture -- --keep           # skip the teardown
 |
 | Runs tools/showcase/setup.php first (one-run passwords for the seeded accounts,
 | sample files) and its teardown last, which deletes what the journeys created and
 | restores the original passwords. Screenshots go to docs/client-showcase/screenshots/
 | and anything that could not be shown to storage/qa/showcase/issues.json.
 | Needs `npm run build` and, for thumbnails, a queue worker (started here).
 */
import { execFileSync, spawn } from 'node:child_process';
import { mkdirSync, writeFileSync } from 'node:fs';
import { chromium } from 'playwright';
import { captured, issues } from './lib/browser.mjs';
import visitor from './journeys/visitor.mjs';
import tributes from './journeys/tributes.mjs';
import editor from './journeys/editor.mjs';
import community from './journeys/community.mjs';
import admin from './journeys/admin.mjs';

const ROOT = new URL('../../', import.meta.url).pathname;
const args = process.argv.slice(2);
const only = (args.find((arg) => arg.startsWith('--only='))?.slice(7) ?? '').split(',').filter(Boolean);
const keep = args.includes('--keep');

function tinker(env = {}) {
    const output = execFileSync('php', ['artisan', 'tinker', `--execute=require 'tools/showcase/setup.php';`], {
        cwd: ROOT, env: { ...process.env, ...env }, encoding: 'utf8',
    });

    return JSON.parse(output.trim().split('\n').pop());
}

process.chdir(ROOT);
const setup = tinker();
const worker = spawn('php', ['artisan', 'queue:work', '--sleep=1', '--tries=1'], { cwd: ROOT, stdio: 'ignore' });
const browser = await chromium.launch({ channel: process.env.SHOWCASE_CHANNEL ?? 'chrome', headless: true });
const shared = { ...setup };

// Order matters: the editor approves the tribute the author leaves, the member
// reports a message the administrator then handles, and so on.
const journeys = [
    ['visitor', visitor],
    ['tributes', tributes],
    ['editor', editor],
    ['community', community],
    ['admin', admin],
];

try {
    for (const [name, run] of journeys) {
        if (only.length && !only.includes(name)) continue;
        console.log(`\n${name}`);
        await run(browser, shared);
    }
} finally {
    await browser.close();
    worker.kill();
    if (!keep) tinker({ SHOWCASE_TEARDOWN: '1' });
}

mkdirSync('storage/qa/showcase', { recursive: true });
writeFileSync('storage/qa/showcase/issues.json', JSON.stringify({ generated: new Date().toISOString(), captured, issues }, null, 2));
console.log(`\n${captured.length} screenshots, ${issues.length} issue(s). See storage/qa/showcase/issues.json`);
