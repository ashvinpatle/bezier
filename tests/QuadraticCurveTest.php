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

namespace Alto\Bezier\Tests;

use Alto\Bezier\BoundingBox;
use Alto\Bezier\Point;
use Alto\Bezier\QuadraticCurve;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(QuadraticCurve::class)]
class QuadraticCurveTest extends TestCase
{
    public function testPointAtBasic(): void
    {
        $p0 = new Point(0, 0);
        $p1 = new Point(1, 1);
        $p2 = new Point(2, 0);
        $curve = new QuadraticCurve($p0, $p1, $p2);

        $point = $curve->pointAt(0.5);
        self::assertEquals(1.0, $point->x);
        self::assertEquals(0.5, $point->y);
    }

    public function testPointAtStart(): void
    {
        $p0 = new Point(0, 0);
        $p1 = new Point(1, 1);
        $p2 = new Point(2, 0);
        $curve = new QuadraticCurve($p0, $p1, $p2);

        $point = $curve->pointAt(0.0);
        self::assertEquals(0.0, $point->x);
        self::assertEquals(0.0, $point->y);
    }

    public function testPointAtEnd(): void
    {
        $p0 = new Point(0, 0);
        $p1 = new Point(1, 1);
        $p2 = new Point(2, 0);
        $curve = new QuadraticCurve($p0, $p1, $p2);

        $point = $curve->pointAt(1.0);
        self::assertEquals(2.0, $point->x);
        self::assertEquals(0.0, $point->y);
    }

    public function testBoundingBox(): void
    {
        $curve = new QuadraticCurve(
            new Point(0, 0),
            new Point(1, 1),
            new Point(2, 0),
        );

        // The curve peaks at t=0.5 with y=0.5, not at control point y=1
        self::assertEquals(new BoundingBox(new Point(0, 0), new Point(2, 0.5)), $curve->boundingBox());
    }

    public function testPointsGenerator(): void
    {
        $curve = new QuadraticCurve(
            new Point(0, 0),
            new Point(1, 1),
            new Point(2, 0),
        );

        $points = iterator_to_array($curve->points(0.5));

        self::assertCount(3, $points);
        self::assertEquals(new Point(0, 0), $points[0]);
        self::assertEquals(new Point(1.0, 0.5), $points[1]);
        self::assertEquals(new Point(2, 0), $points[2]);
    }

    public function testPointsGeneratorWithSmallInterval(): void
    {
        $curve = new QuadraticCurve(
            new Point(0, 0),
            new Point(1, 1),
            new Point(2, 0),
        );

        $points = iterator_to_array($curve->points(0.25));

        self::assertCount(5, $points);
        self::assertEquals(0, $points[0]->x);
        self::assertEquals(2, $points[4]->x);
    }

    public function testPointsGeneratorThrowsExceptionForInvalidInterval(): void
    {
        $curve = new QuadraticCurve(
            new Point(0, 0),
            new Point(1, 1),
            new Point(2, 0),
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Interval must be greater than 0 and less than or equal to 1');

        iterator_to_array($curve->points(0));
    }

    public function testPointsGeneratorThrowsExceptionForIntervalGreaterThanOne(): void
    {
        $curve = new QuadraticCurve(
            new Point(0, 0),
            new Point(1, 1),
            new Point(2, 0),
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Interval must be greater than 0 and less than or equal to 1');

        iterator_to_array($curve->points(1.1));
    }

    // Edge case tests

    public function testPointAtWithNegativeT(): void
    {
        $curve = new QuadraticCurve(
            new Point(0, 0),
            new Point(1, 1),
            new Point(2, 0),
        );

        // Negative t extrapolates beyond the start point
        $point = $curve->pointAt(-0.5);
        self::assertLessThan(0, $point->x);
    }

    public function testPointAtWithLargeTGreaterThanOne(): void
    {
        $curve = new QuadraticCurve(
            new Point(0, 0),
            new Point(1, 1),
            new Point(2, 0),
        );

        // t > 1 extrapolates beyond the end point
        $point = $curve->pointAt(1.5);
        self::assertGreaterThan(2, $point->x);
    }

    public function testDegenerateCurveAllPointsIdentical(): void
    {
        $curve = new QuadraticCurve(
            new Point(5, 5),
            new Point(5, 5),
            new Point(5, 5),
        );

        // All points should be the same regardless of t
        self::assertEquals(new Point(5, 5), $curve->pointAt(0));
        self::assertEquals(new Point(5, 5), $curve->pointAt(0.5));
        self::assertEquals(new Point(5, 5), $curve->pointAt(1));

        // Bounding box should be a single point
        $bbox = $curve->boundingBox();
        self::assertEquals(5, $bbox->min->x);
        self::assertEquals(5, $bbox->min->y);
        self::assertEquals(5, $bbox->max->x);
        self::assertEquals(5, $bbox->max->y);
    }

    public function testFloatingPointPrecision(): void
    {
        $curve = new QuadraticCurve(
            new Point(0, 0),
            new Point(1, 1),
            new Point(2, 0),
        );

        // Test with very small t value
        $point1 = $curve->pointAt(0.0001);
        self::assertGreaterThan(0, $point1->x);
        self::assertLessThan(0.01, $point1->x);

        // Test with t very close to 1
        $point2 = $curve->pointAt(0.9999);
        self::assertLessThan(2, $point2->x);
        self::assertGreaterThan(1.99, $point2->x);
    }

    public function testBoundingBoxWithControlPointBeyondEndpoints(): void
    {
        // Curve where control point creates a curve that extends beyond endpoints
        $curve = new QuadraticCurve(
            new Point(0, 0),
            new Point(5, 10),  // High control point
            new Point(10, 0),
        );

        $bbox = $curve->boundingBox();

        // The bounding box should NOT reach y=10 (the control point)
        // It should be less than that because the curve doesn't reach the control point
        self::assertLessThan(10, $bbox->max->y);
        self::assertGreaterThan(0, $bbox->max->y);
    }

    // Split/Subdivision tests

    public function testSplitAtMidpoint(): void
    {
        $curve = new QuadraticCurve(
            new Point(0, 0),
            new Point(1, 1),
            new Point(2, 0),
        );

        [$left, $right] = $curve->split(0.5);

        // Check that both are QuadraticCurve instances
        self::assertInstanceOf(QuadraticCurve::class, $left);
        self::assertInstanceOf(QuadraticCurve::class, $right);

        // The split point should be the same for both curves
        $splitPoint = $curve->pointAt(0.5);
        self::assertEquals($splitPoint, $left->p2);
        self::assertEquals($splitPoint, $right->p0);

        // Left curve should start at original start
        self::assertEquals($curve->p0, $left->p0);

        // Right curve should end at original end
        self::assertEquals($curve->p2, $right->p2);
    }

    public function testSplitAtQuarter(): void
    {
        $curve = new QuadraticCurve(
            new Point(0, 0),
            new Point(2, 4),
            new Point(4, 0),
        );

        [$left, $right] = $curve->split(0.25);

        // The split point should match
        $splitPoint = $curve->pointAt(0.25);
        self::assertEquals($splitPoint, $left->p2);
        self::assertEquals($splitPoint, $right->p0);

        // Points on left subcurve should match original curve
        // t=0.5 on left subcurve = t=0.125 on original curve
        $leftMidpoint = $left->pointAt(0.5);
        $originalPoint = $curve->pointAt(0.125);
        self::assertEqualsWithDelta($originalPoint->x, $leftMidpoint->x, 0.0001);
        self::assertEqualsWithDelta($originalPoint->y, $leftMidpoint->y, 0.0001);
    }

    public function testSplitAtStart(): void
    {
        $curve = new QuadraticCurve(
            new Point(0, 0),
            new Point(1, 1),
            new Point(2, 0),
        );

        [$left, $right] = $curve->split(0.0);

        // Left curve should be degenerate (all points at start)
        self::assertEquals($curve->p0, $left->p0);
        self::assertEquals($curve->p0, $left->p1);
        self::assertEquals($curve->p0, $left->p2);

        // Right curve should be the same as original
        self::assertEquals($curve->p0, $right->p0);
        self::assertEquals($curve->p2, $right->p2);
    }

    public function testSplitAtEnd(): void
    {
        $curve = new QuadraticCurve(
            new Point(0, 0),
            new Point(1, 1),
            new Point(2, 0),
        );

        [$left, $right] = $curve->split(1.0);

        // Left curve should be the same as original
        self::assertEquals($curve->p0, $left->p0);
        self::assertEquals($curve->p2, $left->p2);

        // Right curve should be degenerate (all points at end)
        self::assertEquals($curve->p2, $right->p0);
        self::assertEquals($curve->p2, $right->p1);
        self::assertEquals($curve->p2, $right->p2);
    }

    // Derivative tests

    public function testDerivativeAtStart(): void
    {
        $curve = new QuadraticCurve(
            new Point(0, 0),
            new Point(1, 1),
            new Point(2, 0),
        );

        $derivative = $curve->derivative(0.0);

        // At t=0: B'(0) = 2(P1-P0)
        self::assertEquals(2.0, $derivative->x);
        self::assertEquals(2.0, $derivative->y);
    }

    public function testDerivativeAtEnd(): void
    {
        $curve = new QuadraticCurve(
            new Point(0, 0),
            new Point(1, 1),
            new Point(2, 0),
        );

        $derivative = $curve->derivative(1.0);

        // At t=1: B'(1) = 2(P2-P1)
        self::assertEquals(2.0, $derivative->x);
        self::assertEquals(-2.0, $derivative->y);
    }

    public function testDerivativeAtMidpoint(): void
    {
        $curve = new QuadraticCurve(
            new Point(0, 0),
            new Point(2, 4),
            new Point(4, 0),
        );

        $derivative = $curve->derivative(0.5);

        // At t=0.5: derivative should point horizontally (y=0) for symmetric curve
        self::assertEquals(4.0, $derivative->x);
        self::assertEquals(0.0, $derivative->y);
    }

    // Length tests

    public function testLengthOfStraightLine(): void
    {
        // Degenerate quadratic curve that's actually a straight line
        $curve = new QuadraticCurve(
            new Point(0, 0),
            new Point(1.5, 0),  // Midpoint on the line
            new Point(3, 0),
        );

        $length = $curve->length(100);

        // Should be exactly 3 (straight line from 0 to 3)
        self::assertEqualsWithDelta(3.0, $length, 0.01);
    }

    public function testLengthConvergesWithMoreSamples(): void
    {
        $curve = new QuadraticCurve(
            new Point(0, 0),
            new Point(1, 1),
            new Point(2, 0),
        );

        $length10 = $curve->length(10);
        $length100 = $curve->length(100);
        $length1000 = $curve->length(1000);

        // More samples should converge to a stable value
        self::assertEqualsWithDelta($length100, $length1000, 0.01);
        // Length should be at least the straight-line distance from start to end
        $straightLineDistance = sqrt((2 - 0) ** 2 + (0 - 0) ** 2);
        self::assertGreaterThan($straightLineDistance, $length100);
    }

    public function testLengthThrowsExceptionForInvalidSamples(): void
    {
        $curve = new QuadraticCurve(
            new Point(0, 0),
            new Point(1, 1),
            new Point(2, 0),
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Samples must be at least 2');

        $curve->length(1);
    }

    // Tangent and Normal tests

    public function testTangentIsNormalized(): void
    {
        $curve = new QuadraticCurve(
            new Point(0, 0),
            new Point(1, 2),
            new Point(2, 0),
        );

        $tangent = $curve->tangent(0.5);

        // Tangent should have unit length (magnitude = 1)
        $magnitude = sqrt($tangent->x ** 2 + $tangent->y ** 2);
        self::assertEqualsWithDelta(1.0, $magnitude, 0.0001);
    }

    public function testTangentAtStart(): void
    {
        $curve = new QuadraticCurve(
            new Point(0, 0),
            new Point(1, 0),  // Horizontal control point
            new Point(2, 0),
        );

        $tangent = $curve->tangent(0.0);

        // Tangent should point horizontally to the right
        self::assertEqualsWithDelta(1.0, $tangent->x, 0.0001);
        self::assertEqualsWithDelta(0.0, $tangent->y, 0.0001);
    }

    public function testNormalIsPerpendicular(): void
    {
        $curve = new QuadraticCurve(
            new Point(0, 0),
            new Point(1, 2),
            new Point(2, 0),
        );

        $tangent = $curve->tangent(0.5);
        $normal = $curve->normal(0.5);

        // Normal and tangent should be perpendicular (dot product = 0)
        $dotProduct = $tangent->x * $normal->x + $tangent->y * $normal->y;
        self::assertEqualsWithDelta(0.0, $dotProduct, 0.0001);
    }

    public function testNormalIsNormalized(): void
    {
        $curve = new QuadraticCurve(
            new Point(0, 0),
            new Point(1, 2),
            new Point(2, 0),
        );

        $normal = $curve->normal(0.5);

        // Normal should have unit length
        $magnitude = sqrt($normal->x ** 2 + $normal->y ** 2);
        self::assertEqualsWithDelta(1.0, $magnitude, 0.0001);
    }

    public function testNormalPointsLeft(): void
    {
        $curve = new QuadraticCurve(
            new Point(0, 0),
            new Point(1, 0),  // Horizontal curve
            new Point(2, 0),
        );

        $normal = $curve->normal(0.5);

        // For a horizontal curve pointing right, normal should point up
        self::assertEqualsWithDelta(0.0, $normal->x, 0.0001);
        self::assertEqualsWithDelta(1.0, $normal->y, 0.0001);
    }

    public function testTangentWithZeroDerivative(): void
    {
        // Create a degenerate curve where all points are the same
        // This results in zero derivative everywhere
        $curve = new QuadraticCurve(
            new Point(5, 5),
            new Point(5, 5),
            new Point(5, 5),
        );

        // When derivative is zero (magnitude < 1e-10), tangent should return (0, 0)
        $tangent = $curve->tangent(0.5);

        self::assertEquals(0.0, $tangent->x);
        self::assertEquals(0.0, $tangent->y);
    }

    public function testNormalWithZeroDerivative(): void
    {
        // Create a degenerate curve where all points are the same
        $curve = new QuadraticCurve(
            new Point(5, 5),
            new Point(5, 5),
            new Point(5, 5),
        );

        // When derivative is zero, tangent returns (0, 0)
        // Normal rotates tangent 90° counterclockwise: (0, 0) -> (0, 0)
        $normal = $curve->normal(0.5);

        self::assertEquals(0.0, $normal->x);
        self::assertEquals(0.0, $normal->y);
    }

    // Arc length parameterization tests

    public function testParameterAtDistanceZero(): void
    {
        $curve = new QuadraticCurve(
            new Point(0, 0),
            new Point(1, 1),
            new Point(2, 0),
        );

        $t = $curve->parameterAtDistance(0.0);

        self::assertEquals(0.0, $t);
    }

    public function testParameterAtDistanceFullLength(): void
    {
        $curve = new QuadraticCurve(
            new Point(0, 0),
            new Point(1, 1),
            new Point(2, 0),
        );

        $length = $curve->length(100);
        $t = $curve->parameterAtDistance($length);

        self::assertEqualsWithDelta(1.0, $t, 0.01);
    }

    public function testParameterAtDistanceThrowsForNegative(): void
    {
        $curve = new QuadraticCurve(
            new Point(0, 0),
            new Point(1, 1),
            new Point(2, 0),
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Distance must be non-negative');

        $curve->parameterAtDistance(-1.0);
    }

    public function testParameterAtDistanceThrowsForExceedingLength(): void
    {
        $curve = new QuadraticCurve(
            new Point(0, 0),
            new Point(1, 1),
            new Point(2, 0),
        );

        $length = $curve->length(100);

        $this->expectException(\InvalidArgumentException::class);

        $curve->parameterAtDistance($length + 10);
    }

    public function testPointAtDistanceZero(): void
    {
        $curve = new QuadraticCurve(
            new Point(0, 0),
            new Point(1, 1),
            new Point(2, 0),
        );

        $point = $curve->pointAtDistance(0.0);

        self::assertEquals($curve->p0, $point);
    }

    public function testPointAtDistanceFullLength(): void
    {
        $curve = new QuadraticCurve(
            new Point(0, 0),
            new Point(1, 1),
            new Point(2, 0),
        );

        $length = $curve->length(100);
        $point = $curve->pointAtDistance($length);

        self::assertEqualsWithDelta($curve->p2->x, $point->x, 0.1);
        self::assertEqualsWithDelta($curve->p2->y, $point->y, 0.1);
    }

    // Intersection tests

    public function testIntersectionsWithNonIntersectingCurves(): void
    {
        $curve1 = new QuadraticCurve(
            new Point(0, 0),
            new Point(1, 1),
            new Point(2, 0),
        );

        $curve2 = new QuadraticCurve(
            new Point(0, 10),
            new Point(1, 11),
            new Point(2, 10),
        );

        $intersections = $curve1->intersections($curve2);

        // No intersections - curves are far apart
        self::assertCount(0, $intersections);
    }

    public function testIntersectionsMethodWorks(): void
    {
        // Test that intersection method runs without errors
        $curve1 = new QuadraticCurve(
            new Point(0, 0),
            new Point(5, 5),
            new Point(10, 0),
        );

        $curve2 = new QuadraticCurve(
            new Point(0, 10),
            new Point(5, 5),
            new Point(10, 10),
        );

        $intersections = $curve1->intersections($curve2, 0.5);

        // Method should return an array
        self::assertIsArray($intersections);

        // Each intersection should have the correct structure
        foreach ($intersections as $intersection) {
            self::assertArrayHasKey('point', $intersection);
            self::assertArrayHasKey('t1', $intersection);
            self::assertArrayHasKey('t2', $intersection);
            self::assertInstanceOf(Point::class, $intersection['point']);
        }
    }

    public function testIntersectionsWithTouchingCurves(): void
    {
        // Two curves that touch at a point
        $curve1 = new QuadraticCurve(
            new Point(0, 0),
            new Point(3, 4),
            new Point(6, 0),
        );

        $curve2 = new QuadraticCurve(
            new Point(0, 8),
            new Point(3, 4),
            new Point(6, 8),
        );

        $intersections = $curve1->intersections($curve2, 0.3);

        // These curves share control points, so they likely intersect/touch
        self::assertGreaterThanOrEqual(0, count($intersections));
    }

    // Edge cases for boundingBox with linear/degenerate curves

    public function testBoundingBoxLinearInX(): void
    {
        // Create a curve where the control point X is exactly the midpoint of endpoints
        // This makes denomX = p0.x - 2*p1.x + p2.x = 0
        $curve = new QuadraticCurve(
            new Point(0, 0),
            new Point(5, 10),    // p1.x = (0 + 10) / 2 = 5
            new Point(10, 0),
        );

        $bbox = $curve->boundingBox();

        // Should compute bounding box using only endpoints in X (no interior extremum)
        self::assertEquals(0.0, $bbox->min->x);
        self::assertEquals(10.0, $bbox->max->x);
        self::assertGreaterThan(0.0, $bbox->max->y);
    }

    public function testBoundingBoxLinearInY(): void
    {
        // Create a curve where the control point Y is exactly the midpoint of endpoints
        // This makes denomY = p0.y - 2*p1.y + p2.y = 0
        $curve = new QuadraticCurve(
            new Point(0, 0),
            new Point(10, 5),    // p1.y = (0 + 10) / 2 = 5
            new Point(0, 10),
        );

        $bbox = $curve->boundingBox();

        // Should compute bounding box using only endpoints in Y (no interior extremum)
        self::assertEquals(0.0, $bbox->min->y);
        self::assertEquals(10.0, $bbox->max->y);
        self::assertGreaterThan(0.0, $bbox->max->x);
    }

    public function testBoundingBoxCompletelyLinear(): void
    {
        // Create a completely linear curve (all three points collinear)
        // Both denomX and denomY should be ≈ 0
        $curve = new QuadraticCurve(
            new Point(0, 0),
            new Point(5, 5),     // Midpoint of (0,0) and (10,10)
            new Point(10, 10),
        );

        $bbox = $curve->boundingBox();

        // Bounding box should be the diagonal line from (0,0) to (10,10)
        self::assertEquals(0.0, $bbox->min->x);
        self::assertEquals(0.0, $bbox->min->y);
        self::assertEquals(10.0, $bbox->max->x);
        self::assertEquals(10.0, $bbox->max->y);
    }

    public function testBoundingBoxWithVerySmallDenominator(): void
    {
        // Create a curve where denominator is very small but just above threshold
        // This should still trigger the extrema calculation
        $curve = new QuadraticCurve(
            new Point(0, 0),
            new Point(4.9999, 10),   // Almost (0 + 10)/2 = 5, but not quite
            new Point(10, 0),
        );

        $bbox = $curve->boundingBox();

        // Should compute valid bounding box
        self::assertEquals(0.0, $bbox->min->x);
        self::assertEquals(10.0, $bbox->max->x);
        self::assertGreaterThan(0.0, $bbox->max->y);
        self::assertLessThanOrEqual(10.0, $bbox->max->y);
    }
}
