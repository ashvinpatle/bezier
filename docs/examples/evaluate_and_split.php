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

use Alto\Bezier\Point;
use Alto\Bezier\QuadraticCurve;

$curve = new QuadraticCurve(
    Point::fromArray([0, 0]),
    Point::fromArray([50, 100]),
    Point::fromArray([100, 0]),
);

echo 'Midpoint at t=0.5: '.$curve->pointAt(0.5).PHP_EOL;

[$left, $right] = $curve->split(0.5);

echo 'Left curve end: '.$left->pointAt(1.0).PHP_EOL;
echo 'Right curve start: '.$right->pointAt(0.0).PHP_EOL;
