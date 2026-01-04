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
 * Represents a cubic Bezier curve.
 *
 * A cubic Bezier curve is defined by four control points:
 * - p0: Start point
 * - p1: First control point
 * - p2: Second control point
 * - p3: End point
 *
 * The curve is calculated using the cubic Bezier formula:
 * B(t) = (1-t)³·P0 + 3(1-t)²t·P1 + 3(1-t)t²·P2 + t³·P3
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final readonly class CubicCurve extends Curve
{
    /**
     * Create a new cubic Bezier curve.
     *
     * @param Point $p0 Start point
     * @param Point $p1 First control point
     * @param Point $p2 Second control point
     * @param Point $p3 End point
     */
    public function __construct(
        public Point $p0,
        public Point $p1,
        public Point $p2,
        public Point $p3,
    ) {
        parent::__construct($p0, $p1, $p2, $p3);
    }

    /**
     * Calculate a point on the curve using the cubic Bezier formula.
     *
     * @param float $t Parameter value between 0 and 1
     *
     * @return Point The calculated point on the curve
     */
    public function pointAt(float $t): Point
    {
        [$p0, $p1, $p2, $p3] = $this->points;
        $x = (1 - $t) ** 3 * $p0->x + 3 * (1 - $t) ** 2 * $t * $p1->x + 3 * (1 - $t) * $t ** 2 * $p2->x + $t ** 3 * $p3->x;
        $y = (1 - $t) ** 3 * $p0->y + 3 * (1 - $t) ** 2 * $t * $p1->y + 3 * (1 - $t) * $t ** 2 * $p2->y + $t ** 3 * $p3->y;

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
        [$p0, $p1, $p2, $p3] = $this->points;

        // Start with endpoints
        $xValues = [$p0->x, $p3->x];
        $yValues = [$p0->y, $p3->y];

        // Find extrema in x dimension
        // Derivative: B'(t) = 3(1-t)²(P1-P0) + 6(1-t)t(P2-P1) + 3t²(P3-P2)
        // This gives us a quadratic equation: at² + bt + c = 0
        $a = 3 * ($p3->x - 3 * $p2->x + 3 * $p1->x - $p0->x);
        $b = 6 * ($p0->x - 2 * $p1->x + $p2->x);
        $c = 3 * ($p1->x - $p0->x);

        $roots = $this->solveQuadratic($a, $b, $c);
        foreach ($roots as $t) {
            if ($t > 0 && $t < 1) {
                $xValues[] = $this->pointAt($t)->x;
            }
        }

        // Find extrema in y dimension
        $a = 3 * ($p3->y - 3 * $p2->y + 3 * $p1->y - $p0->y);
        $b = 6 * ($p0->y - 2 * $p1->y + $p2->y);
        $c = 3 * ($p1->y - $p0->y);

        $roots = $this->solveQuadratic($a, $b, $c);
        foreach ($roots as $t) {
            if ($t > 0 && $t < 1) {
                $yValues[] = $this->pointAt($t)->y;
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
     * @return array{0: CubicCurve, 1: CubicCurve} Array containing [leftCurve, rightCurve]
     */
    public function split(float $t): array
    {
        [$p0, $p1, $p2, $p3] = $this->points;

        // First level of interpolation
        $q0 = new Point(
            (1 - $t) * $p0->x + $t * $p1->x,
            (1 - $t) * $p0->y + $t * $p1->y
        );
        $q1 = new Point(
            (1 - $t) * $p1->x + $t * $p2->x,
            (1 - $t) * $p1->y + $t * $p2->y
        );
        $q2 = new Point(
            (1 - $t) * $p2->x + $t * $p3->x,
            (1 - $t) * $p2->y + $t * $p3->y
        );

        // Second level of interpolation
        $r0 = new Point(
            (1 - $t) * $q0->x + $t * $q1->x,
            (1 - $t) * $q0->y + $t * $q1->y
        );
        $r1 = new Point(
            (1 - $t) * $q1->x + $t * $q2->x,
            (1 - $t) * $q1->y + $t * $q2->y
        );

        // Third level of interpolation (split point)
        $s0 = new Point(
            (1 - $t) * $r0->x + $t * $r1->x,
            (1 - $t) * $r0->y + $t * $r1->y
        );

        // Left curve: p0, q0, r0, s0
        $leftCurve = new CubicCurve($p0, $q0, $r0, $s0);

        // Right curve: s0, r1, q2, p3
        $rightCurve = new CubicCurve($s0, $r1, $q2, $p3);

        return [$leftCurve, $rightCurve];
    }

    /**
     * Calculate the derivative (velocity vector) at parameter t.
     *
     * For a cubic Bezier curve, the derivative is:
     * B'(t) = 3(1-t)²(P1-P0) + 6(1-t)t(P2-P1) + 3t²(P3-P2)
     *
     * @param float $t Parameter value between 0 and 1
     *
     * @return Point The derivative vector at parameter t
     */
    public function derivative(float $t): Point
    {
        [$p0, $p1, $p2, $p3] = $this->points;

        $dx = 3 * (1 - $t) ** 2 * ($p1->x - $p0->x)
            + 6 * (1 - $t) * $t * ($p2->x - $p1->x)
            + 3 * $t ** 2 * ($p3->x - $p2->x);

        $dy = 3 * (1 - $t) ** 2 * ($p1->y - $p0->y)
            + 6 * (1 - $t) * $t * ($p2->y - $p1->y)
            + 3 * $t ** 2 * ($p3->y - $p2->y);

        return new Point($dx, $dy);
    }

    /**
     * Solve a quadratic equation ax² + bx + c = 0.
     *
     * @param float $a Coefficient of x²
     * @param float $b Coefficient of x
     * @param float $c Constant term
     *
     * @return array<float> Array of real roots
     */
    private function solveQuadratic(float $a, float $b, float $c): array
    {
        // Handle linear case (a ≈ 0)
        if (abs($a) < 1e-10) {
            if (abs($b) < 1e-10) {
                return [];
            }

            return [-$c / $b];
        }

        $discriminant = $b * $b - 4 * $a * $c;

        // No real roots
        if ($discriminant < 0) {
            return [];
        }

        // One root (discriminant = 0)
        if (abs($discriminant) < 1e-10) {
            return [-$b / (2 * $a)];
        }

        // Two roots
        $sqrtDisc = sqrt($discriminant);

        return [
            (-$b + $sqrtDisc) / (2 * $a),
            (-$b - $sqrtDisc) / (2 * $a),
        ];
    }
}
