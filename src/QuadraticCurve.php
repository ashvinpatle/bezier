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
 * Represents a quadratic Bezier curve.
 *
 * A quadratic Bezier curve is defined by three control points:
 * - p0: Start point
 * - p1: Control point
 * - p2: End point
 *
 * The curve is calculated using the quadratic Bezier formula:
 * B(t) = (1-t)²·P0 + 2(1-t)t·P1 + t²·P2
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final readonly class QuadraticCurve extends Curve
{
    /**
     * Create a new quadratic Bezier curve.
     *
     * @param Point $p0 Start point
     * @param Point $p1 Control point
     * @param Point $p2 End point
     */
    public function __construct(
        public Point $p0,
        public Point $p1,
        public Point $p2,
    ) {
        parent::__construct($p0, $p1, $p2);
    }

    /**
     * Calculate a point on the curve using the quadratic Bezier formula.
     *
     * @param float $t Parameter value between 0 and 1
     *
     * @return Point The calculated point on the curve
     */
    public function pointAt(float $t): Point
    {
        [$p0, $p1, $p2] = $this->points;
        $x = (1 - $t) ** 2 * $p0->x + 2 * (1 - $t) * $t * $p1->x + $t ** 2 * $p2->x;
        $y = (1 - $t) ** 2 * $p0->y + 2 * (1 - $t) * $t * $p1->y + $t ** 2 * $p2->y;

        return new Point($x, $y);
    }

    /**
     * Calculate the bounding box containing the curve.
     *
     * This implementation finds the true tight bounding box by calculating
     * derivative roots to find curve extrema in both x and y dimensions.
     *
     * @return BoundingBox The bounding box
     */
    public function boundingBox(): BoundingBox
    {
        [$p0, $p1, $p2] = $this->points;

        // Start with endpoints
        $xValues = [$p0->x, $p2->x];
        $yValues = [$p0->y, $p2->y];

        // Find extrema in x dimension
        // Derivative: B'(t) = 2(1-t)(P1-P0) + 2t(P2-P1)
        // Set to 0: t = (P0-P1)/(P0-2P1+P2)
        $denomX = $p0->x - 2 * $p1->x + $p2->x;
        if (abs($denomX) > 1e-10) {
            $tX = ($p0->x - $p1->x) / $denomX;
            if ($tX > 0 && $tX < 1) {
                $xValues[] = $this->pointAt($tX)->x;
            }
        }

        // Find extrema in y dimension
        $denomY = $p0->y - 2 * $p1->y + $p2->y;
        if (abs($denomY) > 1e-10) {
            $tY = ($p0->y - $p1->y) / $denomY;
            if ($tY > 0 && $tY < 1) {
                $yValues[] = $this->pointAt($tY)->y;
            }
        }

        $minX = min($xValues);
        $maxX = max($xValues);
        $minY = min($yValues);
        $maxY = max($yValues);

        return new BoundingBox(new Point($minX, $minY), new Point($maxX, $maxY));
    }

    /**
     * Split the curve at parameter t using De Casteljau's algorithm.
     *
     * @param float $t Parameter value between 0 and 1 where to split the curve
     *
     * @return array{0: QuadraticCurve, 1: QuadraticCurve} Array containing [leftCurve, rightCurve]
     */
    public function split(float $t): array
    {
        [$p0, $p1, $p2] = $this->points;

        // First level of interpolation
        $q0 = new Point(
            (1 - $t) * $p0->x + $t * $p1->x,
            (1 - $t) * $p0->y + $t * $p1->y
        );
        $q1 = new Point(
            (1 - $t) * $p1->x + $t * $p2->x,
            (1 - $t) * $p1->y + $t * $p2->y
        );

        // Second level of interpolation (split point)
        $r0 = new Point(
            (1 - $t) * $q0->x + $t * $q1->x,
            (1 - $t) * $q0->y + $t * $q1->y
        );

        // Left curve: p0, q0, r0
        $leftCurve = new QuadraticCurve($p0, $q0, $r0);

        // Right curve: r0, q1, p2
        $rightCurve = new QuadraticCurve($r0, $q1, $p2);

        return [$leftCurve, $rightCurve];
    }

    /**
     * Calculate the derivative (velocity vector) at parameter t.
     *
     * For a quadratic Bezier curve, the derivative is:
     * B'(t) = 2(1-t)(P1-P0) + 2t(P2-P1)
     *
     * @param float $t Parameter value between 0 and 1
     *
     * @return Point The derivative vector at parameter t
     */
    public function derivative(float $t): Point
    {
        [$p0, $p1, $p2] = $this->points;

        $dx = 2 * (1 - $t) * ($p1->x - $p0->x) + 2 * $t * ($p2->x - $p1->x);
        $dy = 2 * (1 - $t) * ($p1->y - $p0->y) + 2 * $t * ($p2->y - $p1->y);

        return new Point($dx, $dy);
    }
}
