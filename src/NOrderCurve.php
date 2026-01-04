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
 * Represents a Bezier curve of arbitrary order/degree.
 *
 * An N-order Bezier curve is defined by (N+1) control points, where N is the degree.
 * For example:
 * - Linear (degree 1): 2 control points
 * - Quadratic (degree 2): 3 control points
 * - Cubic (degree 3): 4 control points
 * - Quartic (degree 4): 5 control points
 * - And so on...
 *
 * This generic implementation uses De Casteljau's algorithm for all operations,
 * which is numerically stable for curves of any degree.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final readonly class NOrderCurve extends Curve
{
    /**
     * @var int The degree of the curve (number of control points - 1)
     */
    public int $degree;

    /**
     * Create a new N-order Bezier curve.
     *
     * @param Point ...$points Control points (minimum 2 required)
     *
     * @throws \InvalidArgumentException If fewer than 2 points are provided
     */
    public function __construct(Point ...$points)
    {
        if (count($points) < 2) {
            throw new \InvalidArgumentException('At least 2 control points are required for a Bezier curve');
        }

        parent::__construct(...$points);
        $this->degree = count($points) - 1;
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
        // Use De Casteljau's algorithm for numerical stability
        return $this->deCasteljau($this->points, $t);
    }

    /**
     * Calculate the bounding box containing the curve.
     *
     * For arbitrary-order curves, finding exact extrema requires solving
     * high-degree polynomial equations, so we use sampling for a tight approximation.
     *
     * @return BoundingBox The bounding box
     */
    public function boundingBox(): BoundingBox
    {
        // Start with control points
        $xValues = array_map(fn ($p) => $p->x, $this->points);
        $yValues = array_map(fn ($p) => $p->y, $this->points);

        // Sample the curve for better bounds
        // Use more samples for higher-degree curves
        $samples = min(50 + $this->degree * 10, 200);

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
     * @return array{0: NOrderCurve, 1: NOrderCurve} Array containing [leftCurve, rightCurve]
     */
    public function split(float $t): array
    {
        [$left, $right] = $this->deCasteljauSplit($this->points, $t);

        return [
            new NOrderCurve(...$left),
            new NOrderCurve(...$right),
        ];
    }

    /**
     * Calculate the derivative (velocity vector) at parameter t.
     *
     * The derivative of a degree n curve is a degree n-1 curve.
     * The derivative control points are: n * (P[i+1] - P[i]) for i = 0 to n-1.
     *
     * @param float $t Parameter value between 0 and 1
     *
     * @return Point The derivative vector at parameter t
     */
    public function derivative(float $t): Point
    {
        $n = count($this->points);

        // Special case: linear curve (degree 1) has constant derivative
        if (2 === $n) {
            return new Point(
                $this->points[1]->x - $this->points[0]->x,
                $this->points[1]->y - $this->points[0]->y
            );
        }

        // Calculate derivative control points
        // For a degree n curve, derivative has degree n-1 with n control points
        $derivativePoints = [];
        $degree = $this->degree;

        for ($i = 0; $i < $n - 1; ++$i) {
            $derivativePoints[] = new Point(
                $degree * ($this->points[$i + 1]->x - $this->points[$i]->x),
                $degree * ($this->points[$i + 1]->y - $this->points[$i]->y)
            );
        }

        // Evaluate the derivative curve at t
        return $this->deCasteljau($derivativePoints, $t);
    }

    /**
     * De Casteljau's algorithm for evaluating a Bezier curve.
     *
     * This is a numerically stable recursive algorithm that works for
     * Bezier curves of any degree.
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
     * This recursively subdivides the curve at parameter t, returning
     * the control points for both the left and right sub-curves.
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
