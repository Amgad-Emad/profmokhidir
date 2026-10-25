/*
 | Brand building blocks for the progress report: colours, type, headings with a
 | brass hairline, numbered figures with captions, tables, bullets and steps.
 | Every figure gets the next number ("Figure 3: ..."), and a screenshot that was
 | not captured is listed in `missing` instead of breaking the build.
 */
import { existsSync, readFileSync } from 'node:fs';
import { join } from 'node:path';
import {
    AlignmentType, BorderStyle, HeadingLevel, ImageRun, LevelFormat, Paragraph, ShadingType,
    Table, TableCell, TableLayoutType, TableRow, TextRun, VerticalAlign, WidthType,
} from 'docx';

export const C = {
    evergreen: '13221C', ivory: 'F3F1EA', cypress: '2F4A3E', brass: 'A8864F', champagne: 'DCCDA8',
    linen: 'E7E5DC', stone: 'D3D1C5', ash: '575A55', bronze: '6F5A34',
};
export const FONT = { display: 'Cormorant Garamond Light', body: 'Hanken Grotesk' };

/** Content width of an A4 page with 2.2 cm side margins, in twentieths of a point. */
export const PAGE = { width: 11906, height: 16838, margin: 1247 };
export const CONTENT_WIDTH = PAGE.width - PAGE.margin * 2;

export const missing = [];
let figureNumber = 0;

export const SHOTS = new URL('../../../docs/client-showcase/screenshots/', import.meta.url).pathname;

export function run(text, options = {}) {
    return new TextRun({ text, font: FONT.body, ...options });
}

export function p(content, options = {}) {
    const children = (Array.isArray(content) ? content : [content]).map((part) => (typeof part === 'string' ? run(part) : part));

    return new Paragraph({ children, spacing: { after: 140, line: 300 }, ...options });
}

export function h1(text, { pageBreak = true } = {}) {
    return new Paragraph({
        heading: HeadingLevel.HEADING_1,
        pageBreakBefore: pageBreak,
        children: [new TextRun({ text })],
    });
}

export function h2(text, options = {}) {
    return new Paragraph({ heading: HeadingLevel.HEADING_2, children: [new TextRun({ text })], ...options });
}

export function h3(text, options = {}) {
    return new Paragraph({ heading: HeadingLevel.HEADING_3, children: [new TextRun({ text })], ...options });
}

/** The small uppercase label: bronze on light paper, tracked out. */
export function label(text, options = {}) {
    return new Paragraph({
        spacing: { before: 200, after: 60 },
        keepNext: true,
        children: [run(text.toUpperCase(), { size: 16, bold: true, color: C.bronze, characterSpacing: 40 })],
        ...options,
    });
}

export function rule() {
    return new Paragraph({ border: { bottom: { style: BorderStyle.SINGLE, size: 4, color: C.brass, space: 1 } }, spacing: { after: 200 } });
}

export function bullets(items, { level = 0 } = {}) {
    return items.map((item) => p(item, { numbering: { reference: 'bullets', level }, spacing: { after: 80, line: 290 } }));
}

let stepInstance = 0;

/** Numbered steps that restart at 1 for every journey. */
export function steps(items) {
    stepInstance += 1;
    const out = [];
    for (const item of items) {
        out.push(p(item.text, { numbering: { reference: 'steps', level: 0, instance: stepInstance }, spacing: { before: 160, after: 80, line: 290 }, keepNext: Boolean(item.fig) }));
        if (item.fig) out.push(...figure(item.fig));
        if (item.figs) out.push(...figureRow(item.figs));
    }

    return out;
}

function pngSize(file) {
    const buffer = readFileSync(file);

    return { width: buffer.readUInt32BE(16), height: buffer.readUInt32BE(20), buffer };
}

function caption(text) {
    figureNumber += 1;

    return new Paragraph({
        alignment: AlignmentType.LEFT,
        spacing: { before: 80, after: 260 },
        children: [run(`Figure ${figureNumber}: `, { size: 17, bold: true, color: C.cypress }), run(text, { size: 17, color: C.ash })],
    });
}

function image(id, widthPx) {
    const file = join(SHOTS, `${id}.png`);
    if (!existsSync(file)) {
        missing.push(id);
        return run(`[Screenshot ${id} was not captured]`, { color: C.bronze, italics: true });
    }
    const { width, height, buffer } = pngSize(file);

    return new ImageRun({
        type: 'png',
        data: buffer,
        transformation: { width: widthPx, height: Math.round((height / width) * widthPx) },
        altText: { title: id, description: id, name: id },
    });
}

/** One screenshot across the text column, with its numbered caption. */
export function figure({ id, caption: text, width = 600 }) {
    return [
        new Paragraph({
            keepNext: true,
            spacing: { before: 80, after: 0 },
            border: frameBorder(),
            children: [image(id, width)],
        }),
        caption(text),
    ];
}

function frameBorder() {
    const side = { style: BorderStyle.SINGLE, size: 4, color: C.stone, space: 0 };

    return { top: side, bottom: side, left: side, right: side };
}

/** Two or three phone-width screenshots side by side, sharing one caption. */
export function figureRow(items) {
    const widthPx = items.length === 2 ? 230 : 190;
    const col = Math.floor(CONTENT_WIDTH / items.length);
    const none = { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' };
    const table = new Table({
        layout: TableLayoutType.FIXED,
        width: { size: col * items.length, type: WidthType.DXA },
        columnWidths: items.map(() => col),
        borders: { top: none, bottom: none, left: none, right: none, insideHorizontal: none, insideVertical: none },
        rows: [new TableRow({
            cantSplit: true,
            children: items.map((item) => new TableCell({
                width: { size: col, type: WidthType.DXA },
                verticalAlign: VerticalAlign.TOP,
                children: [
                    new Paragraph({ alignment: AlignmentType.CENTER, children: [image(item.id, widthPx)] }),
                    new Paragraph({ alignment: AlignmentType.CENTER, spacing: { before: 60 }, children: [run(item.label, { size: 16, color: C.ash })] }),
                ],
            })),
        })],
    });

    return [table, caption(items.caption ?? items.map((item) => item.label).join(', '))];
}

const STATUS_COLOURS = { Done: C.cypress, 'Done with placeholder content': C.bronze, 'Waiting on client decision': C.ash };

/**
 * A branded table: evergreen header row with champagne labels, stone hairlines,
 * linen banding. `widths` are fractions of the text column; a cell may be a
 * string, or { text, bold, status } where status colours the text.
 */
export function table(headers, rows, widths) {
    const cols = widths.map((w) => Math.round(w * CONTENT_WIDTH));
    const line = { style: BorderStyle.SINGLE, size: 4, color: C.stone };
    const cell = (content, index, { header = false, band = false } = {}) => {
        const value = typeof content === 'object' && content !== null ? content : { text: String(content ?? '') };
        const colour = header ? C.champagne : value.status ? STATUS_COLOURS[value.text] ?? C.evergreen : C.evergreen;

        return new TableCell({
            width: { size: cols[index], type: WidthType.DXA },
            shading: header ? { fill: C.evergreen, type: ShadingType.CLEAR, color: 'auto' } : band ? { fill: 'F7F6F1', type: ShadingType.CLEAR, color: 'auto' } : undefined,
            margins: { top: 70, bottom: 70, left: 110, right: 110 },
            verticalAlign: VerticalAlign.TOP,
            children: String(value.text).split('\n').map((textLine) => new Paragraph({
                spacing: { after: 20, line: 260 },
                children: [run(header ? textLine.toUpperCase() : textLine, {
                    size: header ? 15 : 18,
                    bold: header || value.bold || Boolean(value.status),
                    color: colour,
                    characterSpacing: header ? 20 : undefined,
                })],
            })),
        });
    };

    return new Table({
        layout: TableLayoutType.FIXED,
        width: { size: cols.reduce((a, b) => a + b, 0), type: WidthType.DXA },
        columnWidths: cols,
        borders: { top: line, bottom: line, left: line, right: line, insideHorizontal: line, insideVertical: line },
        rows: [
            new TableRow({ tableHeader: true, cantSplit: true, children: headers.map((text, i) => cell(text, i, { header: true })) }),
            ...rows.map((row, r) => new TableRow({ cantSplit: true, children: row.map((value, i) => cell(value, i, { band: r % 2 === 1 })) })),
        ],
    });
}

/** A two-column "what / detail" list without visible grid, used on persona pages. */
export function facts(pairs) {
    const cols = [Math.round(CONTENT_WIDTH * 0.26), CONTENT_WIDTH - Math.round(CONTENT_WIDTH * 0.26)];
    const none = { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' };
    const hair = { style: BorderStyle.SINGLE, size: 4, color: C.stone };

    return new Table({
        layout: TableLayoutType.FIXED,
        width: { size: CONTENT_WIDTH, type: WidthType.DXA },
        columnWidths: cols,
        borders: { top: hair, bottom: hair, left: none, right: none, insideHorizontal: hair, insideVertical: none },
        rows: pairs.map(([name, value]) => new TableRow({
            cantSplit: true,
            children: [
                new TableCell({
                    width: { size: cols[0], type: WidthType.DXA },
                    margins: { top: 90, bottom: 90, left: 0, right: 160 },
                    children: [new Paragraph({ children: [run(name.toUpperCase(), { size: 15, bold: true, color: C.bronze, characterSpacing: 30 })] })],
                }),
                new TableCell({
                    width: { size: cols[1], type: WidthType.DXA },
                    margins: { top: 90, bottom: 90, left: 0, right: 0 },
                    children: (Array.isArray(value) ? value : [value]).map((line) => new Paragraph({
                        spacing: { after: 40, line: 270 },
                        numbering: Array.isArray(value) && value.length > 1 ? { reference: 'bullets', level: 0 } : undefined,
                        children: [run(line, { size: 19 })],
                    })),
                }),
            ],
        })),
    });
}

export const numbering = {
    config: [
        {
            reference: 'bullets',
            levels: [
                { level: 0, format: LevelFormat.BULLET, text: '–', alignment: AlignmentType.LEFT, style: { run: { color: C.brass }, paragraph: { indent: { left: 360, hanging: 260 } } } },
                { level: 1, format: LevelFormat.BULLET, text: '·', alignment: AlignmentType.LEFT, style: { run: { color: C.brass }, paragraph: { indent: { left: 720, hanging: 260 } } } },
            ],
        },
        {
            reference: 'steps',
            levels: [
                { level: 0, format: LevelFormat.DECIMAL, text: '%1.', alignment: AlignmentType.LEFT, style: { run: { color: C.bronze, bold: true }, paragraph: { indent: { left: 400, hanging: 400 } } } },
            ],
        },
    ],
};

export const styles = {
    default: {
        document: { run: { font: FONT.body, size: 20, color: C.evergreen }, paragraph: { spacing: { line: 300 } } },
        heading1: {
            run: { font: FONT.display, size: 56, color: C.evergreen },
            paragraph: { spacing: { before: 0, after: 280 }, border: { bottom: { style: BorderStyle.SINGLE, size: 4, color: C.brass, space: 8 } }, keepNext: true },
        },
        heading2: { run: { font: FONT.display, size: 38, color: C.evergreen }, paragraph: { spacing: { before: 360, after: 140 }, keepNext: true } },
        heading3: { run: { font: FONT.display, size: 28, color: C.cypress }, paragraph: { spacing: { before: 240, after: 100 }, keepNext: true } },
    },
};
