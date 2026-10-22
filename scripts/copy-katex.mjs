/*
 | Copies the KaTeX stylesheet and its fonts out of node_modules into
 | public/vendor/katex, where the chat page loads them only when a message
 | contains maths (App\Constants\ChatSettings::KATEX_STYLESHEET). Run as part of
 | `npm run build`; the folder is gitignored like public/build.
 */
import { cpSync, mkdirSync, existsSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const source = resolve(root, 'node_modules/katex/dist');
const target = resolve(root, 'public/vendor/katex');

if (!existsSync(source)) {
    console.error('katex is not installed; run npm install first.');
    process.exit(1);
}

mkdirSync(target, { recursive: true });
cpSync(resolve(source, 'katex.min.css'), resolve(target, 'katex.min.css'));
cpSync(resolve(source, 'fonts'), resolve(target, 'fonts'), { recursive: true });
console.log('KaTeX assets copied to public/vendor/katex');
