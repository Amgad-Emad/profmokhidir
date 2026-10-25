/*
 | Builds the client progress report from the screenshots in
 | docs/client-showcase/screenshots/ and the measured numbers in quality.json:
 |
 |   npm run showcase            # from the project root
 |
 | Writes docs/client-showcase/profmokhidir-progress-report-YYYY-MM-DD.docx with
 | the brand fonts embedded (Georgia and Arial named as fallbacks), and a PDF
 | beside it when LibreOffice (soffice) is installed. Run
 | `npm run showcase:capture` first to refresh the screenshots.
 */
import { execFileSync } from 'node:child_process';
import { existsSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';
import JSZip from 'jszip';
import {
    AlignmentType, BorderStyle, CharacterSet, Document, Footer, HeightRule, ImageRun, Packer, PageNumber,
    Paragraph, ShadingType, Table, TableCell, TableLayoutType, TableOfContents, TableRow, TabStopType,
    TextRun, VerticalAlign, WidthType,
} from 'docx';
import { chromium } from 'playwright';
import { C, CONTENT_WIDTH, FONT, PAGE, bullets, missing, numbering, p, run, styles } from './lib/docx-kit.mjs';
import personas from './content/personas.mjs';
import journeysPublic from './content/journeys-public.mjs';
import journeysCommunity from './content/journeys-community.mjs';
import journeysStaff from './content/journeys-staff.mjs';
import { features, traceability } from './content/overview.mjs';
import { appendix, decisions, nextSteps, qualitySummary } from './content/closing.mjs';

const HERE = new URL('./', import.meta.url).pathname;
const OUT_DIR = join(HERE, '../../docs/client-showcase');
const VERSION = JSON.parse(readFileSync(join(HERE, 'package.json'), 'utf8')).version.split('.').slice(0, 2).join('.');
const today = new Date();
const iso = today.toISOString().slice(0, 10);
const longDate = today.toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });
const TITLE = 'Professor Mohamed Osman Khidir · Website progress report';

const font = (name) => readFileSync(join(HERE, 'fonts', name));

/** The MK mark: Cormorant Garamond Light inside a 1 px brass hairline ring, with one sixth of clear space. */
async function mark() {
    const file = join(HERE, 'assets/mk-mark.png');
    if (existsSync(file)) return readFileSync(file);
    mkdirSync(join(HERE, 'assets'), { recursive: true });
    const browser = await chromium.launch({ channel: 'chrome' });
    const page = await browser.newPage({ viewport: { width: 240, height: 240 }, deviceScaleFactor: 4 });
    await page.setContent(`<style>
        @font-face { font-family: Cormorant; src: url(data:font/ttf;base64,${font('CormorantGaramond-Light.ttf').toString('base64')}); }
        html, body { margin: 0; background: transparent; }
        .ring { box-sizing: border-box; margin: 30px; width: 180px; height: 180px; border: 1px solid #${C.brass}; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; font: 300 78px/1 Cormorant; color: #${C.evergreen}; letter-spacing: -0.02em; padding-top: 6px; }
    </style><div class="ring">MK</div>`);
    await page.evaluate(() => document.fonts.ready);
    await page.screenshot({ path: file, omitBackground: true });
    await browser.close();

    return readFileSync(file);
}

function cover(markPng) {
    const none = { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' };
    const hair = { style: BorderStyle.SINGLE, size: 4, color: C.brass };
    const centred = (children, spacing = {}) => new Paragraph({ alignment: AlignmentType.CENTER, spacing, children });
    const width = PAGE.width - 2 * 720;

    return new Table({
        layout: TableLayoutType.FIXED,
        width: { size: width, type: WidthType.DXA },
        columnWidths: [width],
        borders: { top: none, bottom: none, left: none, right: none, insideHorizontal: none, insideVertical: none },
        rows: [new TableRow({
            height: { value: PAGE.height - 2 * 720 - 400, rule: HeightRule.EXACT },
            children: [new TableCell({
                width: { size: width, type: WidthType.DXA },
                shading: { fill: C.ivory, type: ShadingType.CLEAR, color: 'auto' },
                borders: { top: hair, bottom: hair, left: hair, right: hair },
                verticalAlign: VerticalAlign.CENTER,
                margins: { left: 900, right: 900 },
                children: [
                    centred([new ImageRun({ type: 'png', data: markPng, transformation: { width: 150, height: 150 }, altText: { title: 'MK', description: 'The MK mark', name: 'MK mark' } })], { after: 360 }),
                    centred([run('PROFESSOR', { size: 20, bold: true, color: C.bronze, characterSpacing: 90 })], { after: 60 }),
                    centred([new TextRun({ text: 'Mohamed Osman Khidir', font: FONT.display, size: 64, color: C.evergreen })], { after: 480 }),
                    new Paragraph({ alignment: AlignmentType.CENTER, border: { bottom: { style: BorderStyle.SINGLE, size: 4, color: C.brass, space: 1 } }, indent: { left: 2600, right: 2600 }, spacing: { after: 480 } }),
                    centred([new TextRun({ text: 'Website progress report', font: FONT.display, size: 80, color: C.evergreen })], { after: 160 }),
                    centred([new TextRun({ text: 'profmokhidir.com: who it serves, how it works, and what is left before launch', font: FONT.display, italics: true, size: 32, color: C.cypress })], { after: 900 }),
                    centred([run(TITLE, { size: 19, color: C.ash })], { after: 80 }),
                    centred([run(`${longDate}  ·  Version ${VERSION}`, { size: 19, color: C.ash })]),
                    centred([new TextRun({ text: '“Knowledge is the light we pass on.”', font: FONT.display, italics: true, size: 30, color: C.bronze })], { before: 1100 }),
                ],
            })],
        })],
    });
}

function contents() {
    return [
        new Paragraph({ children: [new TextRun({ text: 'Contents', font: FONT.display, size: 56, color: C.evergreen })], border: { bottom: { style: BorderStyle.SINGLE, size: 4, color: C.brass, space: 8 } }, spacing: { after: 240 } }),
        new TableOfContents('Contents', { hyperlink: true, headingStyleRange: '1-2' }),
        new Paragraph({ spacing: { before: 400 }, children: [new TextRun({ text: 'About this report', font: FONT.display, size: 38, color: C.evergreen })] }),
        p('This report shows where profmokhidir.com stands today. It describes the six kinds of people who use the site, walks through what each of them does with screenshots taken from the working site, lists every feature with its status, maps the work to the original brief and specification, and sets out what is still needed before launch.'),
        p('At a glance:'),
        ...bullets([
            'All ten sections of the brief are built, together with Tributes, a dark appearance, a page-text editor and visitor analytics (switched off).',
            'The Scientific Community works end to end: application, email confirmation, approval by an administrator, and a private chat with attachments and reporting.',
            'Editors and administrators manage everything from the dashboard; nothing needs a developer to update content.',
            'Every text and image on the site is a placeholder until the real content arrives (section 6).',
        ]),
    ];
}

const footer = new Footer({
    children: [new Paragraph({
        border: { top: { style: BorderStyle.SINGLE, size: 4, color: C.brass, space: 6 } },
        tabStops: [{ type: TabStopType.RIGHT, position: CONTENT_WIDTH }],
        children: [
            run(TITLE, { size: 16, color: C.ash }),
            run('\tPage ', { size: 16, color: C.ash }),
            new TextRun({ children: [PageNumber.CURRENT], font: FONT.body, size: 16, color: C.ash }),
        ],
    })],
});

const body = [
    ...contents(),
    ...personas(),
    ...journeysPublic(),
    ...journeysCommunity(),
    ...journeysStaff(),
    ...features(),
    ...traceability(),
    ...qualitySummary(),
    ...decisions(),
    ...nextSteps(),
    ...appendix(),
];

const doc = new Document({
    creator: 'profmokhidir.com project team',
    title: 'Website progress report',
    description: TITLE,
    features: { updateFields: true },
    styles,
    numbering,
    fonts: [
        { name: FONT.display, data: font('CormorantGaramond-Light.ttf'), italic: font('CormorantGaramond-LightItalic.ttf'), characterSet: CharacterSet.ANSI },
        { name: FONT.body, data: font('HankenGrotesk-Regular.ttf'), bold: font('HankenGrotesk-Bold.ttf'), characterSet: CharacterSet.ANSI },
    ],
    sections: [
        {
            properties: { page: { size: { width: PAGE.width, height: PAGE.height }, margin: { top: 720, bottom: 720, left: 720, right: 720 } } },
            children: [cover(await mark())],
        },
        {
            properties: { page: { size: { width: PAGE.width, height: PAGE.height }, margin: { top: 1134, bottom: 1134, left: PAGE.margin, right: PAGE.margin, footer: 567 }, pageNumbers: { start: 2 } } },
            footers: { default: footer },
            children: body,
        },
    ],
});

/** Names Georgia and Arial as the fallbacks Word uses if the embedded fonts cannot load. */
async function withFallbacks(buffer) {
    const zip = await JSZip.loadAsync(buffer);
    let table = await zip.file('word/fontTable.xml').async('string');
    for (const [name, alt] of [[FONT.display, 'Georgia'], [FONT.body, 'Arial']]) {
        table = table.replace(new RegExp(`(<w:font w:name="${name}"[^>]*>)`), `$1<w:altName w:val="${alt}"/>`);
    }
    zip.file('word/fontTable.xml', table);

    return zip.generateAsync({ type: 'nodebuffer', compression: 'DEFLATE' });
}

mkdirSync(OUT_DIR, { recursive: true });
const target = join(OUT_DIR, `profmokhidir-progress-report-${iso}.docx`);
writeFileSync(target, await withFallbacks(await Packer.toBuffer(doc)));
console.log(`Wrote ${target}`);

if (missing.length) console.warn(`Screenshots not found (shown as notes in the report): ${missing.join(', ')}`);

const soffice = ['soffice', '/Applications/LibreOffice.app/Contents/MacOS/soffice'].find((bin) => {
    try { execFileSync(bin, ['--version'], { stdio: 'ignore' }); return true; } catch { return false; }
});
if (soffice) {
    execFileSync(soffice, ['--headless', '--convert-to', 'pdf', '--outdir', OUT_DIR, target], { stdio: 'inherit' });
    console.log(`Wrote ${target.replace(/\.docx$/, '.pdf')}`);
} else {
    console.log('LibreOffice is not installed, so no PDF was exported.');
}
