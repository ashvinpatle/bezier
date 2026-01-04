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

$curve = new CubicCurve(
    new Point(0, 0),
    new Point(25, 120),
    new Point(75, 80),
    new Point(100, 0),
);

$totalLength = $curve->length();

echo "Total length (samples=100): {$totalLength}".PHP_EOL;
echo 'Point halfway along curve: '.$curve->pointAtDistance($totalLength / 2, 200).PHP_EOL;
echo 'Parameter at quarter distance: '.$curve->parameterAtDistance($totalLength / 4, 200).PHP_EOL;
