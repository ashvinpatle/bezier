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
use Alto\Bezier\Point;

$curve1 = new CubicCurve(
    new Point(0, 40),
    new Point(50, 120),
    new Point(100, -20),
    new Point(150, 60),
);

$curve2 = new CubicCurve(
    new Point(0, 100),
    new Point(50, -40),
    new Point(100, 140),
    new Point(150, 20),
);

$intersections = $curve1->intersections($curve2, 0.25);

foreach ($intersections as $intersection) {
    $point = $intersection['point'];
    echo "Intersection at ({$point->x}, {$point->y}) "
        ."(t1={$intersection['t1']}, t2={$intersection['t2']})".PHP_EOL;
}
