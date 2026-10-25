/*
 | Renders one user journey: persona, goal, starting point, numbered steps with
 | their screenshots, what happens behind the scenes, and the result.
 */
import { bullets, facts, h2, label, p, steps } from '../lib/docx-kit.mjs';

export default function journey(j, { first = false } = {}) {
    return [
        h2(j.title, { pageBreakBefore: !first }),
        facts([
            ['Persona', j.persona],
            ['Goal', j.goal],
            ['Starting point', j.start],
        ]),
        label('Steps'),
        ...steps(j.steps),
        label('Behind the scenes'),
        ...bullets(j.behind),
        label('Result'),
        p(j.result),
    ];
}
