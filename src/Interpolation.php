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

namespace Alto\Bezier;

/**
 * Provides interpolation utilities for points.
 *
 * This class contains static methods for various types of interpolation
 * between points in 2D space.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class Interpolation
{
    /**
     * Perform linear interpolation between two points.
     *
     * Calculates a point along the straight line between p1 and p2
     * at parameter t (0 = p1, 1 = p2).
     *
     * @param Point $p1 First point
     * @param Point $p2 Second point
     * @param float $t  Parameter value (typically between 0 and 1)
     *
     * @return Point The interpolated point
     */
    public static function linear(Point $p1, Point $p2, float $t): Point
    {
        $x = $p1->x + ($p2->x - $p1->x) * $t;
        $y = $p1->y + ($p2->y - $p1->y) * $t;

        return new Point($x, $y);
    }
}
