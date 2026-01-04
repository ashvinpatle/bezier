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
 * Abstract base class for Bezier curves.
 *
 * Defines the common interface for all Bezier curve implementations.
 * All curves are immutable (readonly) and provide methods to calculate
 * points along the curve and get bounding boxes.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
abstract readonly class Curve
{
    /**
     * @var list<Point> The control points defining the curve
     */
    protected array $points;

    /**
     * Create a new curve with control points.
     *
     * @param Point ...$points The control points
     */
    public function __construct(Point ...$points)
    {
        $this->points = array_values($points);
    }

    /**
     * Create an N-order curve from an array of points or coordinate pairs.
     *
     * @param array<Point|array{0: float|int, 1: float|int}> $points
     */
    public static function fromPoints(array $points): NOrderCurve
    {
        if (count($points) < 2) {
            throw new \InvalidArgumentException('At least 2 control points are required for a Bezier curve');
        }

        $normalized = array_map(
            static function ($point): Point {
                if ($point instanceof Point) {
                    return $point;
                }

                return Point::fromArray($point);
            },
            $points
        );

        return new NOrderCurve(...$normalized);
    }

    /**
     * Export control points as coordinate pairs.
     *
     * @return list<array{float, float}>
     */
    public function toArray(): array
    {
        return array_map(
            static fn (Point $point): array => [$point->x, $point->y],
            $this->points
        );
    }

    /**
     * Calculate a point on the curve at parameter t.
     *
     * @param float $t Parameter value between 0 and 1 (0 = start, 1 = end)
     *
     * @return Point The point on the curve at parameter t
     */
    abstract public function pointAt(float $t): Point;

    /**
     * Calculate the bounding box of the curve.
     *
     * @return BoundingBox The rectangular bounds containing the curve
     */
    abstract public function boundingBox(): BoundingBox;

    /**
     * Split the curve at parameter t into two sub-curves.
     *
     * @param float $t Parameter value between 0 and 1 where to split the curve
     *
     * @return array{0: static, 1: static} Array containing [leftCurve, rightCurve]
     */
    abstract public function split(float $t): array;

    /**
     * Calculate the derivative (velocity vector) at parameter t.
     *
     * @param float $t Parameter value between 0 and 1
     *
     * @return Point The derivative vector at parameter t
     */
    abstract public function derivative(float $t): Point;

    /**
     * Calculate the tangent vector (normalized derivative) at parameter t.
     *
     * The tangent vector points in the direction of the curve and has unit length.
     *
     * @param float $t Parameter value between 0 and 1
     *
     * @return Point The unit tangent vector at parameter t
     */
    public function tangent(float $t): Point
    {
        $derivative = $this->derivative($t);
        $magnitude = sqrt($derivative->x ** 2 + $derivative->y ** 2);

        // Handle zero derivative (stationary point)
        if ($magnitude < 1e-10) {
            return new Point(0, 0);
        }

        return new Point(
            $derivative->x / $magnitude,
            $derivative->y / $magnitude
        );
    }

    /**
     * Calculate the normal vector (perpendicular to tangent) at parameter t.
     *
     * The normal vector is perpendicular to the curve direction and has unit length.
     * It points to the "left" of the curve direction (90° counterclockwise rotation).
     *
     * @param float $t Parameter value between 0 and 1
     *
     * @return Point The unit normal vector at parameter t
     */
    public function normal(float $t): Point
    {
        $tangent = $this->tangent($t);

        // Rotate tangent 90° counterclockwise: (x, y) -> (-y, x)
        return new Point(-$tangent->y, $tangent->x);
    }

    /**
     * Calculate the arc length of the curve using numerical integration.
     *
     * Since there's no closed-form solution for Bezier curve length,
     * this uses numerical integration with the specified number of samples.
     *
     * @param int $samples Number of samples for numerical integration (higher = more accurate)
     *
     * @return float The approximate arc length of the curve
     */
    public function length(int $samples = 100): float
    {
        if ($samples < 2) {
            throw new \InvalidArgumentException('Samples must be at least 2');
        }

        $length = 0.0;
        $dt = 1.0 / $samples;

        // Use trapezoidal rule for numerical integration
        for ($i = 0; $i < $samples; ++$i) {
            $t1 = $i * $dt;
            $t2 = ($i + 1) * $dt;

            $derivative1 = $this->derivative($t1);
            $derivative2 = $this->derivative($t2);

            // Speed at each point (magnitude of derivative)
            $speed1 = sqrt($derivative1->x ** 2 + $derivative1->y ** 2);
            $speed2 = sqrt($derivative2->x ** 2 + $derivative2->y ** 2);

            // Trapezoidal rule: average the speeds and multiply by interval
            $length += ($speed1 + $speed2) * 0.5 * $dt;
        }

        return $length;
    }

    /**
     * Generate points along the curve at specified intervals.
     *
     * @param float $interval Step size between 0 and 1 (must be > 0 and <= 1)
     *
     * @return \Generator<Point> Points along the curve
     *
     * @throws \InvalidArgumentException If interval is not in valid range
     */
    public function points(float $interval): \Generator
    {
        if ($interval <= 0 || $interval > 1) {
            throw new \InvalidArgumentException('Interval must be greater than 0 and less than or equal to 1');
        }

        foreach (range(0, 1, $interval) as $t) {
            yield $this->pointAt($t);
        }
    }

    /**
     * Get the parameter t for a given distance along the curve.
     *
     * This enables arc length parameterization, allowing you to find the
     * parameter t that corresponds to a specific distance from the start of the curve.
     *
     * @param float $distance Distance along the curve (0 to curve length)
     * @param int   $samples  Number of samples for lookup table (higher = more accurate)
     *
     * @return float The parameter t (0 to 1) at the specified distance
     *
     * @throws \InvalidArgumentException If distance is negative or exceeds curve length
     */
    public function parameterAtDistance(float $distance, int $samples = 100): float
    {
        if ($distance < 0) {
            throw new \InvalidArgumentException('Distance must be non-negative');
        }

        $totalLength = $this->length($samples);

        if ($distance > $totalLength) {
            throw new \InvalidArgumentException("Distance {$distance} exceeds curve length {$totalLength}");
        }

        // Handle edge cases
        if (0.0 === $distance) {
            return 0.0;
        }

        if (abs($distance - $totalLength) < 1e-10) {
            return 1.0;
        }

        // Build lookup table of cumulative distances at each sample point
        $lookupTable = $this->buildArcLengthLookupTable($samples);

        // Binary search to find the segment containing the target distance
        $left = 0;
        $right = count($lookupTable) - 1;

        while ($right - $left > 1) {
            $mid = (int) (($left + $right) / 2);

            if ($lookupTable[$mid]['distance'] < $distance) {
                $left = $mid;
            } else {
                $right = $mid;
            }
        }

        // Linear interpolation between the two nearest points
        $d1 = $lookupTable[$left]['distance'];
        $d2 = $lookupTable[$right]['distance'];
        $t1 = $lookupTable[$left]['t'];
        $t2 = $lookupTable[$right]['t'];

        // Interpolate to find exact t value
        $ratio = ($distance - $d1) / ($d2 - $d1);

        return $t1 + $ratio * ($t2 - $t1);
    }

    /**
     * Get a point at a specific distance along the curve.
     *
     * This provides arc length parameterization, useful for:
     * - Constant-speed animation along the curve
     * - Even distribution of points along the curve
     *
     * @param float $distance Distance along the curve (0 to curve length)
     * @param int   $samples  Number of samples for lookup table (higher = more accurate)
     *
     * @return Point The point at the specified distance
     *
     * @throws \InvalidArgumentException If distance is negative or exceeds curve length
     */
    public function pointAtDistance(float $distance, int $samples = 100): Point
    {
        $t = $this->parameterAtDistance($distance, $samples);

        return $this->pointAt($t);
    }

    /**
     * Build a lookup table mapping parameter t to cumulative arc length.
     *
     * @param int $samples Number of samples to use
     *
     * @return array<array{t: float, distance: float}> Lookup table
     */
    private function buildArcLengthLookupTable(int $samples): array
    {
        $table = [['t' => 0.0, 'distance' => 0.0]];
        $cumulativeDistance = 0.0;
        $dt = 1.0 / $samples;

        for ($i = 1; $i <= $samples; ++$i) {
            $t1 = ($i - 1) * $dt;
            $t2 = $i * $dt;

            $derivative1 = $this->derivative($t1);
            $derivative2 = $this->derivative($t2);

            $speed1 = sqrt($derivative1->x ** 2 + $derivative1->y ** 2);
            $speed2 = sqrt($derivative2->x ** 2 + $derivative2->y ** 2);

            // Trapezoidal rule for this segment
            $segmentLength = ($speed1 + $speed2) * 0.5 * $dt;
            $cumulativeDistance += $segmentLength;

            $table[] = ['t' => $t2, 'distance' => $cumulativeDistance];
        }

        return $table;
    }

    /**
     * Find intersection points between this curve and another curve.
     *
     * Uses recursive subdivision and bounding box testing to find intersections.
     * This is computationally expensive and may not find all intersections for
     * complex curves with many crossings.
     *
     * @param Curve $other     The other curve to intersect with
     * @param float $tolerance Tolerance for intersection detection (default: 0.5)
     * @param int   $maxDepth  Maximum recursion depth (default: 16)
     *
     * @return array<array{point: Point, t1: float, t2: float}> Array of intersections with point and parameters
     */
    public function intersections(Curve $other, float $tolerance = 0.5, int $maxDepth = 16): array
    {
        $intersections = [];
        $this->findIntersections($this, $other, $this, $other, 0.0, 1.0, 0.0, 1.0, $tolerance, $maxDepth, 0, $intersections);

        // Remove duplicate intersections
        return $this->removeDuplicateIntersections($intersections, $tolerance);
    }

    /**
     * Recursive helper to find curve intersections using subdivision.
     *
     * @param Curve                                            $original1      Original first curve (for parameter mapping)
     * @param Curve                                            $original2      Original second curve (for parameter mapping)
     * @param Curve                                            $curve1         First curve segment
     * @param Curve                                            $curve2         Second curve segment
     * @param float                                            $t1Min          Parameter range start for curve1
     * @param float                                            $t1Max          Parameter range end for curve1
     * @param float                                            $t2Min          Parameter range start for curve2
     * @param float                                            $t2Max          Parameter range end for curve2
     * @param float                                            $tolerance      Tolerance for intersection detection
     * @param int                                              $maxDepth       Maximum recursion depth
     * @param int                                              $depth          Current recursion depth
     * @param array<array{point: Point, t1: float, t2: float}> &$intersections Output array for intersections
     */
    private function findIntersections(
        Curve $original1,
        Curve $original2,
        Curve $curve1,
        Curve $curve2,
        float $t1Min,
        float $t1Max,
        float $t2Min,
        float $t2Max,
        float $tolerance,
        int $maxDepth,
        int $depth,
        array &$intersections,
    ): void {
        // Get bounding boxes for both curve segments
        $bbox1 = $curve1->boundingBox();
        $bbox2 = $curve2->boundingBox();

        // Check if bounding boxes overlap
        if (!$this->boundingBoxesOverlap($bbox1, $bbox2)) {
            return;
        }

        // If bounding boxes are small enough, record intersection
        $size1 = max($bbox1->width(), $bbox1->height());
        $size2 = max($bbox2->width(), $bbox2->height());

        if ($size1 < $tolerance && $size2 < $tolerance) {
            // Use midpoint of parameter ranges
            $t1 = ($t1Min + $t1Max) / 2;
            $t2 = ($t2Min + $t2Max) / 2;

            // Calculate intersection point using original curves with tracked parameters
            $p1 = $original1->pointAt($t1);
            $p2 = $original2->pointAt($t2);

            $intersections[] = [
                'point' => new Point(($p1->x + $p2->x) / 2, ($p1->y + $p2->y) / 2),
                't1' => $t1,
                't2' => $t2,
            ];

            return;
        }

        // Stop if max depth reached
        if ($depth >= $maxDepth) {
            return;
        }

        // Subdivide both curves and recursively check all combinations
        [$left1, $right1] = $curve1->split(0.5);
        [$left2, $right2] = $curve2->split(0.5);

        $t1Mid = ($t1Min + $t1Max) / 2;
        $t2Mid = ($t2Min + $t2Max) / 2;

        // Check all four combinations
        $this->findIntersections($original1, $original2, $left1, $left2, $t1Min, $t1Mid, $t2Min, $t2Mid, $tolerance, $maxDepth, $depth + 1, $intersections);
        $this->findIntersections($original1, $original2, $left1, $right2, $t1Min, $t1Mid, $t2Mid, $t2Max, $tolerance, $maxDepth, $depth + 1, $intersections);
        $this->findIntersections($original1, $original2, $right1, $left2, $t1Mid, $t1Max, $t2Min, $t2Mid, $tolerance, $maxDepth, $depth + 1, $intersections);
        $this->findIntersections($original1, $original2, $right1, $right2, $t1Mid, $t1Max, $t2Mid, $t2Max, $tolerance, $maxDepth, $depth + 1, $intersections);
    }

    /**
     * Check if two bounding boxes overlap.
     *
     * @param BoundingBox $bbox1 First bounding box
     * @param BoundingBox $bbox2 Second bounding box
     *
     * @return bool True if boxes overlap
     */
    private function boundingBoxesOverlap(BoundingBox $bbox1, BoundingBox $bbox2): bool
    {
        return !($bbox1->maxX() < $bbox2->minX()
                 || $bbox2->maxX() < $bbox1->minX()
                 || $bbox1->maxY() < $bbox2->minY()
                 || $bbox2->maxY() < $bbox1->minY());
    }

    /**
     * Remove duplicate intersection points.
     *
     * @param array<array{point: Point, t1: float, t2: float}> $intersections Array of intersections
     * @param float                                            $tolerance     Distance tolerance for duplicate detection
     *
     * @return array<array{point: Point, t1: float, t2: float}> Filtered intersections
     */
    private function removeDuplicateIntersections(array $intersections, float $tolerance): array
    {
        $unique = [];

        foreach ($intersections as $intersection) {
            $isDuplicate = false;

            foreach ($unique as $existing) {
                $dx = $intersection['point']->x - $existing['point']->x;
                $dy = $intersection['point']->y - $existing['point']->y;
                $distance = sqrt($dx * $dx + $dy * $dy);

                if ($distance < $tolerance) {
                    $isDuplicate = true;
                    break;
                }
            }

            if (!$isDuplicate) {
                $unique[] = $intersection;
            }
        }

        return $unique;
    }
}
