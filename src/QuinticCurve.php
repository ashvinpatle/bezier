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
 * Represents a quintic Bezier curve (degree 5).
 *
 * A quintic Bezier curve is defined by six control points.
 * This is a higher-order curve that provides even more control than quartic curves.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final readonly class QuinticCurve extends Curve
{
    /**
     * Create a new quintic Bezier curve.
     *
     * @param Point $p0 First control point (start)
     * @param Point $p1 Second control point
     * @param Point $p2 Third control point
     * @param Point $p3 Fourth control point
     * @param Point $p4 Fifth control point
     * @param Point $p5 Sixth control point (end)
     */
    public function __construct(
        public Point $p0,
        public Point $p1,
        public Point $p2,
        public Point $p3,
        public Point $p4,
        public Point $p5,
    ) {
        parent::__construct($p0, $p1, $p2, $p3, $p4, $p5);
    }

    /**
     * Calculate a point on the curve using De Casteljau's algorithm.
     *
     * @param float $t Parameter value between 0 and 1
     *
     * @return Point The calculated point on the curve
     */
    public function pointAt(float $t): Point
    {
        // Use De Casteljau's algorithm for stability
        return $this->deCasteljau($this->points, $t);
    }

    /**
     * Calculate the bounding box containing the curve.
     *
     * For higher-order curves, finding exact extrema is complex,
     * so we use sampling for a tight approximation.
     *
     * @return BoundingBox The bounding box
     */
    public function boundingBox(): BoundingBox
    {
        // Start with control points
        $xValues = array_map(fn ($p) => $p->x, $this->points);
        $yValues = array_map(fn ($p) => $p->y, $this->points);

        // Sample the curve for better bounds
        $samples = 50;
        for ($i = 1; $i < $samples; ++$i) {
            $t = $i / $samples;
            $point = $this->pointAt($t);
            $xValues[] = $point->x;
            $yValues[] = $point->y;
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
     * @return array{0: QuinticCurve, 1: QuinticCurve} Array containing [leftCurve, rightCurve]
     */
    public function split(float $t): array
    {
        [$left, $right] = $this->deCasteljauSplit($this->points, $t);

        return [
            new QuinticCurve($left[0], $left[1], $left[2], $left[3], $left[4], $left[5]),
            new QuinticCurve($right[0], $right[1], $right[2], $right[3], $right[4], $right[5]),
        ];
    }

    /**
     * Calculate the derivative (velocity vector) at parameter t.
     *
     * The derivative of a degree n curve is a degree n-1 curve.
     * For quintic, the derivative is a quartic curve.
     *
     * @param float $t Parameter value between 0 and 1
     *
     * @return Point The derivative vector at parameter t
     */
    public function derivative(float $t): Point
    {
        [$p0, $p1, $p2, $p3, $p4, $p5] = $this->points;

        // Derivative control points (quartic curve)
        $d0 = new Point(5 * ($p1->x - $p0->x), 5 * ($p1->y - $p0->y));
        $d1 = new Point(5 * ($p2->x - $p1->x), 5 * ($p2->y - $p1->y));
        $d2 = new Point(5 * ($p3->x - $p2->x), 5 * ($p3->y - $p2->y));
        $d3 = new Point(5 * ($p4->x - $p3->x), 5 * ($p4->y - $p3->y));
        $d4 = new Point(5 * ($p5->x - $p4->x), 5 * ($p5->y - $p4->y));

        // Evaluate the derivative curve (quartic) at t
        return $this->deCasteljau([$d0, $d1, $d2, $d3, $d4], $t);
    }

    /**
     * De Casteljau's algorithm for evaluating a Bezier curve.
     *
     * @param array<Point> $points Control points
     * @param float        $t      Parameter value
     *
     * @return Point The point on the curve
     */
    private function deCasteljau(array $points, float $t): Point
    {
        $n = count($points);

        if (1 === $n) {
            return $points[0];
        }

        $newPoints = [];
        for ($i = 0; $i < $n - 1; ++$i) {
            $newPoints[] = new Point(
                (1 - $t) * $points[$i]->x + $t * $points[$i + 1]->x,
                (1 - $t) * $points[$i]->y + $t * $points[$i + 1]->y
            );
        }

        return $this->deCasteljau($newPoints, $t);
    }

    /**
     * De Casteljau's algorithm for splitting a Bezier curve.
     *
     * @param array<Point> $points Control points
     * @param float        $t      Parameter value where to split
     *
     * @return array{0: array<Point>, 1: array<Point>} Left and right control points
     */
    private function deCasteljauSplit(array $points, float $t): array
    {
        $n = count($points);
        $left = [$points[0]];
        $right = [];

        // Build the pyramid of interpolated points
        $pyramid = [$points];

        for ($level = 1; $level < $n; ++$level) {
            $prevLevel = $pyramid[$level - 1];
            $currentLevel = [];

            for ($i = 0; $i < count($prevLevel) - 1; ++$i) {
                $currentLevel[] = new Point(
                    (1 - $t) * $prevLevel[$i]->x + $t * $prevLevel[$i + 1]->x,
                    (1 - $t) * $prevLevel[$i]->y + $t * $prevLevel[$i + 1]->y
                );
            }

            $pyramid[] = $currentLevel;
            $left[] = $currentLevel[0];
        }

        // Build right curve control points from pyramid (from split point to end)
        for ($i = $n - 1; $i >= 0; --$i) {
            $right[] = $pyramid[$i][count($pyramid[$i]) - 1];
        }

        return [$left, $right];
    }
}
