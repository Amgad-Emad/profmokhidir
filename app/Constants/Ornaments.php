<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Hairline ornaments for section breaks (<x-ornament>). Every ornament is drawn
 * with 1px brass strokes only (vector-effect: non-scaling-stroke) and never a
 * filled shape, so the "dot" is a tiny hairline ring.
 */
final class Ornaments
{
    public const string RULE_DOT = 'rule-dot';

    public const string DOUBLE_RULE = 'double-rule';

    public const string FLEURON = 'fleuron';

    public const string DEFAULT = self::RULE_DOT;

    /** Rendered stroke width in CSS pixels at every size. */
    public const int STROKE_PX = 1;

    /**
     * Ornament => viewBox width, height and the SVG elements (lines, circles and open paths, stroke only).
     *
     * @return array<string, array{label: string, width: int, height: int, shapes: list<array<string, string|float|int>>}>
     */
    public static function definitions(): array
    {
        return [
            self::RULE_DOT => [
                'label' => 'Short rule with a centred dot',
                'width' => 120,
                'height' => 9,
                'shapes' => [
                    ['type' => 'line', 'x1' => 0, 'y1' => 4.5, 'x2' => 52, 'y2' => 4.5],
                    ['type' => 'circle', 'cx' => 60, 'cy' => 4.5, 'r' => 1.75],
                    ['type' => 'line', 'x1' => 68, 'y1' => 4.5, 'x2' => 120, 'y2' => 4.5],
                ],
            ],
            self::DOUBLE_RULE => [
                'label' => 'Double rule',
                'width' => 120,
                'height' => 9,
                'shapes' => [
                    ['type' => 'line', 'x1' => 0, 'y1' => 2.5, 'x2' => 120, 'y2' => 2.5],
                    ['type' => 'line', 'x1' => 18, 'y1' => 6.5, 'x2' => 102, 'y2' => 6.5],
                ],
            ],
            self::FLEURON => [
                'label' => 'Hairline fleuron',
                'width' => 160,
                'height' => 24,
                'shapes' => [
                    ['type' => 'line', 'x1' => 0, 'y1' => 12, 'x2' => 58, 'y2' => 12],
                    ['type' => 'line', 'x1' => 102, 'y1' => 12, 'x2' => 160, 'y2' => 12],
                    // A lozenge in outline, with two open leaves reaching out from it.
                    ['type' => 'path', 'd' => 'M80 4 L88 12 L80 20 L72 12 Z'],
                    ['type' => 'path', 'd' => 'M72 12 C68 6 62 6 58 12 C62 18 68 18 72 12'],
                    ['type' => 'path', 'd' => 'M88 12 C92 6 98 6 102 12 C98 18 92 18 88 12'],
                    ['type' => 'circle', 'cx' => 80, 'cy' => 12, 'r' => 1.5],
                ],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function types(): array
    {
        return array_keys(self::definitions());
    }
}
