<?php

declare(strict_types=1);

/*
 * This file is part of the ALTO library.
 *
 * © 2025–present Simon André
 *
 * For full copyright and license information, please see
 * the LICENSE file distributed with this source code.
 */

require __DIR__.'/../../vendor/autoload.php';

use Alto\Bezier\CubicCurve;
use Alto\Bezier\Curve;
use Alto\Bezier\NOrderCurve;
use Alto\Bezier\Point;
use Alto\Bezier\QuadraticCurve;
use Alto\Bezier\QuarticCurve;
use Alto\Bezier\QuinticCurve;

/**
 * Trim trailing zeros from decimal values for cleaner SVG output.
 */
function fmt(float $value): string
{
    $formatted = number_format($value, 2, '.', '');
    $trimmed = rtrim(rtrim($formatted, '0'), '.');

    return '' === $trimmed ? '0' : $trimmed;
}

/**
 * Sample any curve into a poly-bezier path expressed with move/line commands.
 */
function sampledPath(Curve $curve, float $step = 0.04): string
{
    if ($step <= 0.0 || $step > 1.0) {
        throw new InvalidArgumentException('Step must be between 0 and 1.');
    }

    $steps = (int) ceil(1.0 / $step);
    $points = [];
    for ($i = 0; $i <= $steps; ++$i) {
        $t = min(1.0, $i * $step);
        $points[] = $curve->pointAt($t);
    }

    $commands = [sprintf('M %s %s', fmt($points[0]->x), fmt($points[0]->y))];
    for ($i = 1, $max = count($points); $i < $max; ++$i) {
        $commands[] = sprintf('L %s %s', fmt($points[$i]->x), fmt($points[$i]->y));
    }

    return implode(' ', $commands);
}

function svgSnippet(string $path): string
{
    return <<<SVG
<svg viewBox="0 0 240 200" width="240" height="200" xmlns="http://www.w3.org/2000/svg">
  <path d="$path" fill="none" stroke="#2563eb" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />
</svg>
SVG;
}

$examples = [
    'QuadraticCurve' => [
        'curve' => new QuadraticCurve(
            new Point(10, 170),
            new Point(140, 20),
            new Point(230, 170),
        ),
        'builder' => static function (QuadraticCurve $curve): string {
            return sprintf(
                'M %s %s Q %s %s %s %s',
                fmt($curve->p0->x),
                fmt($curve->p0->y),
                fmt($curve->p1->x),
                fmt($curve->p1->y),
                fmt($curve->p2->x),
                fmt($curve->p2->y),
            );
        },
        'notes' => 'Maps directly to an SVG Q command.',
    ],
    'CubicCurve' => [
        'curve' => new CubicCurve(
            new Point(10, 160),
            new Point(60, 20),
            new Point(180, 20),
            new Point(230, 160),
        ),
        'builder' => static function (CubicCurve $curve): string {
            return sprintf(
                'M %s %s C %s %s %s %s %s %s',
                fmt($curve->p0->x),
                fmt($curve->p0->y),
                fmt($curve->p1->x),
                fmt($curve->p1->y),
                fmt($curve->p2->x),
                fmt($curve->p2->y),
                fmt($curve->p3->x),
                fmt($curve->p3->y),
            );
        },
        'notes' => 'Matches the SVG C command syntax.',
    ],
    'QuarticCurve' => [
        'curve' => new QuarticCurve(
            new Point(10, 170),
            new Point(60, 10),
            new Point(120, 190),
            new Point(180, 20),
            new Point(230, 170),
        ),
        'builder' => static fn (Curve $curve): string => sampledPath($curve),
        'notes' => 'Sampled because SVG has no native quartic command.',
    ],
    'QuinticCurve' => [
        'curve' => new QuinticCurve(
            new Point(10, 165),
            new Point(50, 40),
            new Point(90, 10),
            new Point(150, 190),
            new Point(200, 20),
            new Point(230, 160),
        ),
        'builder' => static fn (Curve $curve): string => sampledPath($curve),
        'notes' => 'Sampled path approximates the quintic segment.',
    ],
    'NOrderCurve (degree 6)' => [
        'curve' => new NOrderCurve(
            new Point(10, 150),
            new Point(30, 20),
            new Point(70, 180),
            new Point(120, 10),
            new Point(170, 190),
            new Point(210, 40),
            new Point(230, 150),
        ),
        'builder' => static fn (Curve $curve): string => sampledPath($curve),
        'notes' => 'Arbitrary order curve sampled into line segments.',
    ],
];

foreach ($examples as $label => $example) {
    $curve = $example['curve'];
    $builder = $example['builder'];
    $path = $builder($curve);

    echo '=== '.$label." ===\n";
    echo $example['notes']."\n";
    echo 'Path: '.$path."\n";
    echo svgSnippet($path)."\n\n";
}
