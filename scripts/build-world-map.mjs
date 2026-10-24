/*
 | Builds resources/data/world-map.json, the country outlines of the admin
 | analytics map (App\Support\WorldMap), from @svg-maps/world (CC BY 4.0).
 | Paths are rounded to whole units of the 1010 × 666 view box and repeated
 | points are dropped, which shrinks the data from ~1.2 MB to a size that can be
 | printed inline. Run by hand after updating the package: npm run world-map.
 */
import { writeFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import world from '@svg-maps/world';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const target = resolve(root, 'resources/data/world-map.json');

const TOLERANCE = 0.9;
const MIN_RING_AREA = 3;

// Ramer–Douglas–Peucker on an open polyline.
function reduce(points) {
    if (points.length < 3) return points;
    const [ax, ay] = points[0];
    const [bx, by] = points[points.length - 1];
    const length = Math.hypot(bx - ax, by - ay) || 1;
    let worst = 0;
    let index = 0;
    for (let i = 1; i < points.length - 1; i++) {
        const [px, py] = points[i];
        const distance = Math.abs((bx - ax) * (ay - py) - (ax - px) * (by - ay)) / length;
        if (distance > worst) {
            worst = distance;
            index = i;
        }
    }
    if (worst <= TOLERANCE) return [points[0], points[points.length - 1]];
    return [...reduce(points.slice(0, index + 1)).slice(0, -1), ...reduce(points.slice(index))];
}

function area(points) {
    let sum = 0;
    for (let i = 0; i < points.length; i++) {
        const [x1, y1] = points[i];
        const [x2, y2] = points[(i + 1) % points.length];
        sum += x1 * y2 - x2 * y1;
    }
    return Math.abs(sum) / 2;
}

// Every path is "m x,y dx,dy … z" (relative moveto, implicit relative lineto) repeated per ring.
function simplify(path) {
    const rings = [];
    let x = 0;
    let y = 0;

    for (const ring of path.split(/z/i)) {
        const numbers = ring.replace(/^\s*m/i, '').match(/-?\d*\.?\d+(?:e-?\d+)?/gi);
        if (!numbers || numbers.length < 2) continue;

        const points = [];
        for (let i = 0; i + 1 < numbers.length; i += 2) {
            x += Number(numbers[i]);
            y += Number(numbers[i + 1]);
            const point = [Math.round(x), Math.round(y)];
            const last = points[points.length - 1];
            if (!last || last[0] !== point[0] || last[1] !== point[1]) points.push(point);
        }
        // Split the closed ring in two halves so the simplification keeps its shape.
        const half = Math.floor(points.length / 2);
        rings.push(points.length > 4 ? [...reduce(points.slice(0, half + 1)).slice(0, -1), ...reduce([...points.slice(half), points[0]]).slice(0, -1)] : points);
    }

    // Keep rings with an area; a country made only of specks keeps its largest speck as a dot.
    let kept = rings.filter((points) => points.length >= 3 && area(points) >= MIN_RING_AREA);
    if (kept.length === 0 && rings.length > 0) {
        const [px, py] = rings.sort((a, b) => b.length - a.length)[0][0];
        kept = [[[px, py], [px + 1, py], [px + 1, py + 1], [px, py + 1]]];
    }

    return kept.map((points) => 'M' + points.map(([px, py]) => `${px} ${py}`).join('L') + 'Z').join('');
}

const countries = {};
for (const location of world.locations) {
    countries[location.id.toUpperCase()] = { name: location.name, d: simplify(location.path) };
}

const data = {
    viewBox: world.viewBox,
    attribution: 'Country outlines: @svg-maps/world (CC BY 4.0), simplified.',
    countries,
};

writeFileSync(target, JSON.stringify(data) + '\n');
console.log(`world-map.json: ${Object.keys(countries).length} countries, ${JSON.stringify(data).length} bytes`);
