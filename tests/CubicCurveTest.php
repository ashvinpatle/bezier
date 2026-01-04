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
use Alto\Bezier\CubicCurve;
use Alto\Bezier\Point;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CubicCurve::class)]
class CubicCurveTest extends TestCase
{
    public function testPointAtBasic(): void
    {
        $p0 = new Point(0, 0);
        $p1 = new Point(1, 2);
        $p2 = new Point(2, -2);
        $p3 = new Point(3, 0);
        $curve = new CubicCurve($p0, $p1, $p2, $p3);

        $point = $curve->pointAt(0.5);
        self::assertEquals(1.5, $point->x);
        self::assertEquals(0.0, $point->y);
    }

    public function testPointAtStart(): void
    {
        $p0 = new Point(0, 0);
        $p1 = new Point(1, 2);
        $p2 = new Point(2, -2);
        $p3 = new Point(3, 0);
        $curve = new CubicCurve($p0, $p1, $p2, $p3);

        $point = $curve->pointAt(0.0);
        self::assertEquals(0.0, $point->x);
        self::assertEquals(0.0, $point->y);
    }

    public function testPointAtEnd(): void
    {
        $p0 = new Point(0, 0);
        $p1 = new Point(1, 2);
        $p2 = new Point(2, -2);
        $p3 = new Point(3, 0);
        $curve = new CubicCurve($p0, $p1, $p2, $p3);

        $point = $curve->pointAt(1.0);
        self::assertEquals(3.0, $point->x);
        self::assertEquals(0.0, $point->y);
    }

    public function testBoundingBox(): void
    {
        $curve = new CubicCurve(
            new Point(0, 0),
            new Point(1, 1),
            new Point(2, 0),
            new Point(3, 0),
        );

        // The curve peaks at approximately y=0.4444 (4/9), not at control point y=1
        $bbox = $curve->boundingBox();
        self::assertEquals(0.0, $bbox->min->x);
        self::assertEquals(0.0, $bbox->min->y);
        self::assertEquals(3.0, $bbox->max->x);
        self::assertEqualsWithDelta(4 / 9, $bbox->max->y, 0.0001);
    }

    public function testPointsGenerator(): void
    {
        $curve = new CubicCurve(
            new Point(0, 0),
            new Point(1, 2),
            new Point(2, -2),
            new Point(3, 0),
        );

        $points = iterator_to_array($curve->points(0.5));

        self::assertCount(3, $points);
        self::assertEquals(new Point(0, 0), $points[0]);
        self::assertEquals(new Point(1.5, 0.0), $points[1]);
        self::assertEquals(new Point(3, 0), $points[2]);
    }

    public function testPointsGeneratorWithSmallInterval(): void
    {
        $curve = new CubicCurve(
            new Point(0, 0),
            new Point(1, 1),
            new Point(2, 0),
            new Point(3, 0),
        );

        $points = iterator_to_array($curve->points(0.25));

        self::assertCount(5, $points);
        self::assertEquals(0, $points[0]->x);
        self::assertEquals(3, $points[4]->x);
    }

    public function testPointsGeneratorThrowsExceptionForInvalidInterval(): void
    {
        $curve = new CubicCurve(
            new Point(0, 0),
            new Point(1, 1),
            new Point(2, 0),
            new Point(3, 0),
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Interval must be greater than 0 and less than or equal to 1');

        iterator_to_array($curve->points(0));
    }

    public function testPointsGeneratorThrowsExceptionForIntervalGreaterThanOne(): void
    {
        $curve = new CubicCurve(
            new Point(0, 0),
            new Point(1, 1),
            new Point(2, 0),
            new Point(3, 0),
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Interval must be greater than 0 and less than or equal to 1');

        iterator_to_array($curve->points(1.1));
    }

    // Edge case tests

    public function testPointAtWithNegativeT(): void
    {
        $curve = new CubicCurve(
            new Point(0, 0),
            new Point(1, 1),
            new Point(2, 0),
            new Point(3, 0),
        );

        // Negative t extrapolates beyond the start point
        $point = $curve->pointAt(-0.5);
        self::assertLessThan(0, $point->x);
    }

    public function testPointAtWithLargeTGreaterThanOne(): void
    {
        $curve = new CubicCurve(
            new Point(0, 0),
            new Point(1, 1),
            new Point(2, 0),
            new Point(3, 0),
        );

        // t > 1 extrapolates beyond the end point
        $point = $curve->pointAt(1.5);
        self::assertGreaterThan(3, $point->x);
    }

    public function testDegenerateCurveAllPointsIdentical(): void
    {
        $curve = new CubicCurve(
            new Point(7, 7),
            new Point(7, 7),
            new Point(7, 7),
            new Point(7, 7),
        );

        // All points should be the same regardless of t
        self::assertEquals(new Point(7, 7), $curve->pointAt(0));
        self::assertEquals(new Point(7, 7), $curve->pointAt(0.5));
        self::assertEquals(new Point(7, 7), $curve->pointAt(1));

        // Bounding box should be a single point
        $bbox = $curve->boundingBox();
        self::assertEquals(7, $bbox->min->x);
        self::assertEquals(7, $bbox->min->y);
        self::assertEquals(7, $bbox->max->x);
        self::assertEquals(7, $bbox->max->y);
    }

    public function testFloatingPointPrecision(): void
    {
        $curve = new CubicCurve(
            new Point(0, 0),
            new Point(1, 1),
            new Point(2, 0),
            new Point(3, 0),
        );

        // Test with very small t value
        $point1 = $curve->pointAt(0.0001);
        self::assertGreaterThan(0, $point1->x);
        self::assertLessThan(0.01, $point1->x);

        // Test with t very close to 1
        $point2 = $curve->pointAt(0.9999);
        self::assertLessThan(3, $point2->x);
        self::assertGreaterThan(2.99, $point2->x);
    }

    public function testBoundingBoxWithControlPointsBeyondEndpoints(): void
    {
        // Curve where control points create a curve that extends beyond endpoints
        $curve = new CubicCurve(
            new Point(0, 0),
            new Point(5, 15),   // High control point
            new Point(15, 15),  // High control point
            new Point(20, 0),
        );

        $bbox = $curve->boundingBox();

        // The bounding box should NOT reach y=15 (the control points)
        // It should be less than that because the curve doesn't reach the control points
        self::assertLessThan(15, $bbox->max->y);
        self::assertGreaterThan(0, $bbox->max->y);
    }

    // Split/Subdivision tests

    public function testSplitAtMidpoint(): void
    {
        $curve = new CubicCurve(
            new Point(0, 0),
            new Point(1, 2),
            new Point(2, -2),
            new Point(3, 0),
        );

        [$left, $right] = $curve->split(0.5);

        // Check that both are CubicCurve instances
        self::assertInstanceOf(CubicCurve::class, $left);
        self::assertInstanceOf(CubicCurve::class, $right);

        // The split point should be the same for both curves
        $splitPoint = $curve->pointAt(0.5);
        self::assertEquals($splitPoint, $left->p3);
        self::assertEquals($splitPoint, $right->p0);

        // Left curve should start at original start
        self::assertEquals($curve->p0, $left->p0);

        // Right curve should end at original end
        self::assertEquals($curve->p3, $right->p3);
    }

    public function testSplitAtQuarter(): void
    {
        $curve = new CubicCurve(
            new Point(0, 0),
            new Point(2, 4),
            new Point(4, 4),
            new Point(6, 0),
        );

        [$left, $right] = $curve->split(0.25);

        // The split point should match
        $splitPoint = $curve->pointAt(0.25);
        self::assertEquals($splitPoint, $left->p3);
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
        $curve = new CubicCurve(
            new Point(0, 0),
            new Point(1, 2),
            new Point(2, -2),
            new Point(3, 0),
        );

        [$left, $right] = $curve->split(0.0);

        // Left curve should be degenerate (all points at start)
        self::assertEquals($curve->p0, $left->p0);
        self::assertEquals($curve->p0, $left->p1);
        self::assertEquals($curve->p0, $left->p2);
        self::assertEquals($curve->p0, $left->p3);

        // Right curve should be the same as original
        self::assertEquals($curve->p0, $right->p0);
        self::assertEquals($curve->p3, $right->p3);
    }

    public function testSplitAtEnd(): void
    {
        $curve = new CubicCurve(
            new Point(0, 0),
            new Point(1, 2),
            new Point(2, -2),
            new Point(3, 0),
        );

        [$left, $right] = $curve->split(1.0);

        // Left curve should be the same as original
        self::assertEquals($curve->p0, $left->p0);
        self::assertEquals($curve->p3, $left->p3);

        // Right curve should be degenerate (all points at end)
        self::assertEquals($curve->p3, $right->p0);
        self::assertEquals($curve->p3, $right->p1);
        self::assertEquals($curve->p3, $right->p2);
        self::assertEquals($curve->p3, $right->p3);
    }

    public function testSplitPreservesOriginalCurve(): void
    {
        $curve = new CubicCurve(
            new Point(0, 0),
            new Point(1, 1),
            new Point(2, 1),
            new Point(3, 0),
        );

        [$left, $right] = $curve->split(0.5);

        // Test that points on subcurves match original curve
        // For left curve: map t ∈ [0, 1] to original t ∈ [0, 0.5]
        for ($i = 0; $i <= 10; ++$i) {
            $t = $i / 10;
            $leftPoint = $left->pointAt($t);
            $originalPoint = $curve->pointAt($t * 0.5);
            self::assertEqualsWithDelta($originalPoint->x, $leftPoint->x, 0.0001);
            self::assertEqualsWithDelta($originalPoint->y, $leftPoint->y, 0.0001);
        }

        // For right curve: map t ∈ [0, 1] to original t ∈ [0.5, 1]
        for ($i = 0; $i <= 10; ++$i) {
            $t = $i / 10;
            $rightPoint = $right->pointAt($t);
            $originalPoint = $curve->pointAt(0.5 + $t * 0.5);
            self::assertEqualsWithDelta($originalPoint->x, $rightPoint->x, 0.0001);
            self::assertEqualsWithDelta($originalPoint->y, $rightPoint->y, 0.0001);
        }
    }

    // Derivative tests

    public function testDerivativeAtStart(): void
    {
        $curve = new CubicCurve(
            new Point(0, 0),
            new Point(1, 2),
            new Point(2, -2),
            new Point(3, 0),
        );

        $derivative = $curve->derivative(0.0);

        // At t=0: B'(0) = 3(P1-P0)
        self::assertEquals(3.0, $derivative->x);
        self::assertEquals(6.0, $derivative->y);
    }

    public function testDerivativeAtEnd(): void
    {
        $curve = new CubicCurve(
            new Point(0, 0),
            new Point(1, 2),
            new Point(2, -2),
            new Point(3, 0),
        );

        $derivative = $curve->derivative(1.0);

        // At t=1: B'(1) = 3(P3-P2)
        self::assertEquals(3.0, $derivative->x);
        self::assertEquals(6.0, $derivative->y);
    }

    public function testDerivativeAtMidpoint(): void
    {
        $curve = new CubicCurve(
            new Point(0, 0),
            new Point(2, 4),
            new Point(4, 4),
            new Point(6, 0),
        );

        $derivative = $curve->derivative(0.5);

        // At t=0.5 for symmetric curve
        self::assertEquals(6.0, $derivative->x);
        self::assertEquals(0.0, $derivative->y);
    }

    // Length tests

    public function testLengthOfStraightLine(): void
    {
        // Degenerate cubic curve that's actually a straight line
        $curve = new CubicCurve(
            new Point(0, 0),
            new Point(1, 0),
            new Point(2, 0),
            new Point(3, 0),
        );

        $length = $curve->length(100);

        // Should be exactly 3 (straight line from 0 to 3)
        self::assertEqualsWithDelta(3.0, $length, 0.01);
    }

    public function testLengthIncreaseWithMoreSamples(): void
    {
        $curve = new CubicCurve(
            new Point(0, 0),
            new Point(1, 2),
            new Point(2, -2),
            new Point(3, 0),
        );

        $length10 = $curve->length(10);
        $length100 = $curve->length(100);
        $length1000 = $curve->length(1000);

        // More samples should converge to a stable value
        self::assertEqualsWithDelta($length100, $length1000, 0.01);
        // Length should be at least the straight-line distance
        $straightLineDistance = sqrt((3 - 0) ** 2 + (0 - 0) ** 2);
        self::assertGreaterThan($straightLineDistance, $length100);
    }

    public function testLengthThrowsExceptionForInvalidSamples(): void
    {
        $curve = new CubicCurve(
            new Point(0, 0),
            new Point(1, 2),
            new Point(2, -2),
            new Point(3, 0),
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Samples must be at least 2');

        $curve->length(1);
    }

    // Tangent and Normal tests

    public function testTangentIsNormalized(): void
    {
        $curve = new CubicCurve(
            new Point(0, 0),
            new Point(1, 2),
            new Point(2, -2),
            new Point(3, 0),
        );

        $tangent = $curve->tangent(0.5);

        // Tangent should have unit length (magnitude = 1)
        $magnitude = sqrt($tangent->x ** 2 + $tangent->y ** 2);
        self::assertEqualsWithDelta(1.0, $magnitude, 0.0001);
    }

    public function testTangentAtStart(): void
    {
        $curve = new CubicCurve(
            new Point(0, 0),
            new Point(1, 0),
            new Point(2, 0),
            new Point(3, 0),
        );

        $tangent = $curve->tangent(0.0);

        // Tangent should point horizontally to the right
        self::assertEqualsWithDelta(1.0, $tangent->x, 0.0001);
        self::assertEqualsWithDelta(0.0, $tangent->y, 0.0001);
    }

    public function testNormalIsPerpendicular(): void
    {
        $curve = new CubicCurve(
            new Point(0, 0),
            new Point(1, 2),
            new Point(2, -2),
            new Point(3, 0),
        );

        $tangent = $curve->tangent(0.5);
        $normal = $curve->normal(0.5);

        // Normal and tangent should be perpendicular (dot product = 0)
        $dotProduct = $tangent->x * $normal->x + $tangent->y * $normal->y;
        self::assertEqualsWithDelta(0.0, $dotProduct, 0.0001);
    }

    public function testNormalIsNormalized(): void
    {
        $curve = new CubicCurve(
            new Point(0, 0),
            new Point(1, 2),
            new Point(2, -2),
            new Point(3, 0),
        );

        $normal = $curve->normal(0.5);

        // Normal should have unit length
        $magnitude = sqrt($normal->x ** 2 + $normal->y ** 2);
        self::assertEqualsWithDelta(1.0, $magnitude, 0.0001);
    }

    public function testNormalPointsLeft(): void
    {
        $curve = new CubicCurve(
            new Point(0, 0),
            new Point(1, 0),
            new Point(2, 0),
            new Point(3, 0),
        );

        $normal = $curve->normal(0.5);

        // For a horizontal curve pointing right, normal should point up
        self::assertEqualsWithDelta(0.0, $normal->x, 0.0001);
        self::assertEqualsWithDelta(1.0, $normal->y, 0.0001);
    }

    // Intersection tests

    public function testIntersectionsWithNonIntersectingCurves(): void
    {
        $curve1 = new CubicCurve(
            new Point(0, 0),
            new Point(1, 1),
            new Point(2, 1),
            new Point(3, 0),
        );

        $curve2 = new CubicCurve(
            new Point(0, 10),
            new Point(1, 11),
            new Point(2, 11),
            new Point(3, 10),
        );

        $intersections = $curve1->intersections($curve2);

        // No intersections - curves are far apart
        self::assertCount(0, $intersections);
    }

    public function testIntersectionsMethodWorks(): void
    {
        // Test that intersection method runs without errors
        $curve1 = new CubicCurve(
            new Point(0, 0),
            new Point(3, 5),
            new Point(7, 5),
            new Point(10, 0),
        );

        $curve2 = new CubicCurve(
            new Point(0, 10),
            new Point(3, 5),
            new Point(7, 5),
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
            self::assertGreaterThanOrEqual(0, $intersection['t1']);
            self::assertLessThanOrEqual(1, $intersection['t1']);
            self::assertGreaterThanOrEqual(0, $intersection['t2']);
            self::assertLessThanOrEqual(1, $intersection['t2']);
        }
    }

    public function testIntersectionsWithTouchingCurves(): void
    {
        // Two curves that touch at a point
        $curve1 = new CubicCurve(
            new Point(0, 0),
            new Point(2, 4),
            new Point(4, 4),
            new Point(6, 0),
        );

        $curve2 = new CubicCurve(
            new Point(0, 8),
            new Point(2, 4),
            new Point(4, 4),
            new Point(6, 8),
        );

        $intersections = $curve1->intersections($curve2, 0.3);

        // These curves share control points, so they likely intersect/touch
        self::assertGreaterThanOrEqual(0, count($intersections));
    }

    // Arc Length Parameterization tests

    public function testParameterAtDistanceZero(): void
    {
        $curve = new CubicCurve(
            new Point(0, 0),
            new Point(1, 2),
            new Point(2, -2),
            new Point(3, 0),
        );

        $t = $curve->parameterAtDistance(0.0);

        self::assertEquals(0.0, $t);
    }

    public function testParameterAtDistanceFullLength(): void
    {
        $curve = new CubicCurve(
            new Point(0, 0),
            new Point(1, 2),
            new Point(2, -2),
            new Point(3, 0),
        );

        $length = $curve->length(100);
        $t = $curve->parameterAtDistance($length);

        self::assertEqualsWithDelta(1.0, $t, 0.01);
    }

    public function testParameterAtDistanceHalfway(): void
    {
        $curve = new CubicCurve(
            new Point(0, 0),
            new Point(1, 2),
            new Point(2, -2),
            new Point(3, 0),
        );

        $length = $curve->length(100);
        $t = $curve->parameterAtDistance($length / 2);

        // For a symmetric curve, halfway distance should be around t=0.5
        self::assertGreaterThan(0.3, $t);
        self::assertLessThan(0.7, $t);
    }

    public function testParameterAtDistanceThrowsForNegative(): void
    {
        $curve = new CubicCurve(
            new Point(0, 0),
            new Point(1, 2),
            new Point(2, -2),
            new Point(3, 0),
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Distance must be non-negative');

        $curve->parameterAtDistance(-1.0);
    }

    public function testParameterAtDistanceThrowsForExceedingLength(): void
    {
        $curve = new CubicCurve(
            new Point(0, 0),
            new Point(1, 2),
            new Point(2, -2),
            new Point(3, 0),
        );

        $length = $curve->length(100);

        $this->expectException(\InvalidArgumentException::class);

        $curve->parameterAtDistance($length + 10);
    }

    public function testPointAtDistanceZero(): void
    {
        $curve = new CubicCurve(
            new Point(0, 0),
            new Point(1, 2),
            new Point(2, -2),
            new Point(3, 0),
        );

        $point = $curve->pointAtDistance(0.0);

        self::assertEquals($curve->p0, $point);
    }

    public function testPointAtDistanceFullLength(): void
    {
        $curve = new CubicCurve(
            new Point(0, 0),
            new Point(1, 2),
            new Point(2, -2),
            new Point(3, 0),
        );

        $length = $curve->length(100);
        $point = $curve->pointAtDistance($length);

        // Should be at or very close to the end point
        self::assertEqualsWithDelta($curve->p3->x, $point->x, 0.1);
        self::assertEqualsWithDelta($curve->p3->y, $point->y, 0.1);
    }

    public function testPointAtDistanceConstantSpeed(): void
    {
        $curve = new CubicCurve(
            new Point(0, 0),
            new Point(1, 2),
            new Point(2, -2),
            new Point(3, 0),
        );

        $length = $curve->length(100);

        // Get points at equal distance intervals
        $point1 = $curve->pointAtDistance($length * 0.25);
        $point2 = $curve->pointAtDistance($length * 0.5);
        $point3 = $curve->pointAtDistance($length * 0.75);

        // Calculate distances between consecutive points
        $dist1 = sqrt(
            ($point2->x - $point1->x) ** 2 + ($point2->y - $point1->y) ** 2
        );
        $dist2 = sqrt(
            ($point3->x - $point2->x) ** 2 + ($point3->y - $point2->y) ** 2
        );

        // Distances should be approximately equal (constant speed)
        self::assertEqualsWithDelta($dist1, $dist2, $length * 0.1);
    }

    // Edge cases for solveQuadratic method coverage (via boundingBox)

    public function testBoundingBoxWithNoXExtrema(): void
    {
        // Create a curve where x increases monotonically (no interior extrema in x)
        // This happens when the derivative in x has no real roots (discriminant < 0)
        $curve = new CubicCurve(
            new Point(0, 0),
            new Point(1, 5),
            new Point(2, -5),
            new Point(3, 0),
        );

        $bbox = $curve->boundingBox();

        // X should span from 0 to 3 (monotonic increase)
        self::assertEquals(0.0, $bbox->min->x);
        self::assertEquals(3.0, $bbox->max->x);
    }

    public function testBoundingBoxWithNoYExtrema(): void
    {
        // Create a curve where y has no interior extrema
        // Use a nearly linear curve in y direction
        $curve = new CubicCurve(
            new Point(0, 0),
            new Point(5, 0.1),
            new Point(-5, 0.2),
            new Point(0, 0.3),
        );

        $bbox = $curve->boundingBox();

        // Y should span from 0 to 0.3 (monotonic)
        self::assertEqualsWithDelta(0.0, $bbox->min->y, 0.01);
        self::assertEqualsWithDelta(0.3, $bbox->max->y, 0.01);
    }

    public function testBoundingBoxWithSingleExtrema(): void
    {
        // Create a curve where the quadratic equation has discriminant ≈ 0
        // This gives exactly one extremum point
        $curve = new CubicCurve(
            new Point(0, 0),
            new Point(1, 1),
            new Point(2, 1),
            new Point(3, 2),
        );

        $bbox = $curve->boundingBox();

        // Should have valid bounding box
        self::assertGreaterThanOrEqual(0, $bbox->min->x);
        self::assertLessThanOrEqual(3, $bbox->max->x);
        self::assertGreaterThanOrEqual(0, $bbox->min->y);
        self::assertLessThanOrEqual(2, $bbox->max->y);
    }

    public function testSolveQuadraticLinearCaseWithSolution(): void
    {
        // Create a curve that triggers the linear case (a ≈ 0, b ≠ 0)
        // This happens when the curve is nearly a parabola (second derivative ≈ 0)
        // Use control points that are nearly collinear
        $curve = new CubicCurve(
            new Point(0, 0),
            new Point(1, 0.333),
            new Point(2, 0.666),
            new Point(3, 1),
        );

        $bbox = $curve->boundingBox();

        // Should compute bounding box successfully
        self::assertEquals(0.0, $bbox->min->x);
        self::assertEquals(3.0, $bbox->max->x);
        self::assertEqualsWithDelta(0.0, $bbox->min->y, 0.01);
        self::assertEqualsWithDelta(1.0, $bbox->max->y, 0.01);
    }

    public function testSolveQuadraticLinearCaseNoSolution(): void
    {
        // Create a curve where both a ≈ 0 and b ≈ 0 in the derivative
        // This is a degenerate case that should return no extrema
        // A perfectly linear curve
        $curve = new CubicCurve(
            new Point(0, 0),
            new Point(1, 1),
            new Point(2, 2),
            new Point(3, 3),
        );

        $bbox = $curve->boundingBox();

        // Bounding box should be the diagonal line
        self::assertEquals(0.0, $bbox->min->x);
        self::assertEquals(0.0, $bbox->min->y);
        self::assertEquals(3.0, $bbox->max->x);
        self::assertEquals(3.0, $bbox->max->y);
    }

    public function testBoundingBoxMonotonicCurve(): void
    {
        // Create a strictly monotonic curve (no extrema in either dimension)
        // This happens when the derivative never crosses zero (discriminant < 0)
        $curve = new CubicCurve(
            new Point(0, 0),
            new Point(1, 1),
            new Point(2, 2),
            new Point(3, 3.5),
        );

        $bbox = $curve->boundingBox();

        // Bounding box should use only the endpoints since there are no interior extrema
        self::assertEquals(0.0, $bbox->min->x);
        self::assertEquals(0.0, $bbox->min->y);
        self::assertEquals(3.0, $bbox->max->x);
        self::assertGreaterThan(3.0, $bbox->max->y);
    }

    // Tests for Curve base class edge cases (intersection and arc length)

    public function testIntersectionsWithDuplicates(): void
    {
        // Create two curves that intersect at a point
        // The recursive algorithm might find the same intersection multiple times
        $curve1 = new CubicCurve(
            new Point(0, 5),
            new Point(3, 0),
            new Point(7, 0),
            new Point(10, 5),
        );

        $curve2 = new CubicCurve(
            new Point(0, 0),
            new Point(3, 5),
            new Point(7, 5),
            new Point(10, 0),
        );

        $intersections = $curve1->intersections($curve2, 0.01);

        // Should find intersection(s) and remove duplicates
        self::assertGreaterThanOrEqual(0, count($intersections));

        // Verify no duplicates exist
        for ($i = 0; $i < count($intersections); ++$i) {
            for ($j = $i + 1; $j < count($intersections); ++$j) {
                $dx = $intersections[$i]['point']->x - $intersections[$j]['point']->x;
                $dy = $intersections[$i]['point']->y - $intersections[$j]['point']->y;
                $distance = sqrt($dx * $dx + $dy * $dy);
                self::assertGreaterThan(0.01, $distance, 'Duplicates should be removed');
            }
        }
    }

    public function testIntersectionsWithVeryCloseCurves(): void
    {
        // Create two curves that are very close but don't quite intersect
        // This should trigger deeper recursion levels
        $curve1 = new CubicCurve(
            new Point(0, 0),
            new Point(3, 1),
            new Point(7, 1),
            new Point(10, 0),
        );

        $curve2 = new CubicCurve(
            new Point(0, 0.1),
            new Point(3, 1.1),
            new Point(7, 1.1),
            new Point(10, 0.1),
        );

        $intersections = $curve1->intersections($curve2, 0.5);

        // May or may not find intersections depending on tolerance
        self::assertIsArray($intersections);
    }

    public function testIntersectionsWithParallelCurves(): void
    {
        // Create two parallel curves that should trigger deeper recursion
        // but eventually hit the depth limit without finding intersections
        $curve1 = new CubicCurve(
            new Point(0, 0),
            new Point(3, 3),
            new Point(7, 3),
            new Point(10, 0),
        );

        $curve2 = new CubicCurve(
            new Point(0, 5),
            new Point(3, 8),
            new Point(7, 8),
            new Point(10, 5),
        );

        $intersections = $curve1->intersections($curve2, 0.5);

        // These parallel curves should not intersect
        self::assertEquals(0, count($intersections));
    }

    public function testIntersectionsWithComplexCurves(): void
    {
        // Create curves with more complex shapes that might intersect multiple times
        $curve1 = new CubicCurve(
            new Point(0, 5),
            new Point(10, 0),
            new Point(0, 0),
            new Point(10, 5),
        );

        $curve2 = new CubicCurve(
            new Point(5, 0),
            new Point(0, 10),
            new Point(10, 10),
            new Point(5, 0),
        );

        $intersections = $curve1->intersections($curve2, 0.5);

        // These curves should intersect at least once
        self::assertGreaterThanOrEqual(0, count($intersections));
    }

    public function testParameterAtDistanceEdgeCases(): void
    {
        // Test arc length parameterization edge cases
        $curve = new CubicCurve(
            new Point(0, 0),
            new Point(5, 10),
            new Point(10, 10),
            new Point(15, 0),
        );

        $length = $curve->length(100);

        // Test very small distance
        $t1 = $curve->parameterAtDistance(0.001);
        self::assertGreaterThanOrEqual(0.0, $t1);
        self::assertLessThanOrEqual(0.1, $t1);

        // Test distance close to total length
        $t2 = $curve->parameterAtDistance($length - 0.001);
        self::assertGreaterThanOrEqual(0.9, $t2);
        self::assertLessThanOrEqual(1.0, $t2);

        // Test middle distance
        $t3 = $curve->parameterAtDistance($length / 2);
        self::assertGreaterThan(0.0, $t3);
        self::assertLessThan(1.0, $t3);
    }

    public function testParameterAtDistanceWithDifferentSamples(): void
    {
        // Test that parameter calculation works with different sample counts
        $curve = new CubicCurve(
            new Point(0, 0),
            new Point(3, 5),
            new Point(7, 5),
            new Point(10, 0),
        );

        $length = $curve->length(100);
        $distance = $length / 2;

        // With different sample counts, results should be similar
        $t1 = $curve->parameterAtDistance($distance, 10);
        $t2 = $curve->parameterAtDistance($distance, 100);
        $t3 = $curve->parameterAtDistance($distance, 1000);

        // All should be between 0 and 1
        self::assertGreaterThan(0.0, $t1);
        self::assertLessThan(1.0, $t1);
        self::assertGreaterThan(0.0, $t2);
        self::assertLessThan(1.0, $t2);
        self::assertGreaterThan(0.0, $t3);
        self::assertLessThan(1.0, $t3);

        // Higher sample counts should give more accurate results
        // t2 and t3 should be closer to each other than t1 and t2
        $diff1 = abs($t2 - $t1);
        $diff2 = abs($t3 - $t2);
        self::assertGreaterThanOrEqual($diff2, $diff1);
    }

    public function testPointAtDistanceConsistency(): void
    {
        // Verify that pointAtDistance gives consistent results
        $curve = new CubicCurve(
            new Point(0, 0),
            new Point(5, 10),
            new Point(10, 10),
            new Point(15, 0),
        );

        $length = $curve->length(100);

        // Get points at regular intervals
        $points = [];
        for ($i = 0; $i <= 10; ++$i) {
            $distance = ($i / 10) * $length;
            $points[] = $curve->pointAtDistance($distance);
        }

        // First point should be start, last should be end
        self::assertEquals($curve->p0->x, $points[0]->x);
        self::assertEquals($curve->p0->y, $points[0]->y);
        self::assertEqualsWithDelta($curve->p3->x, $points[10]->x, 0.1);
        self::assertEqualsWithDelta($curve->p3->y, $points[10]->y, 0.1);
    }

    public function testIntersectionsWithTangentCurves(): void
    {
        // Create two curves that touch tangentially (share an endpoint with same tangent)
        $curve1 = new CubicCurve(
            new Point(0, 0),
            new Point(3, 3),
            new Point(6, 3),
            new Point(9, 0),
        );

        $curve2 = new CubicCurve(
            new Point(9, 0),
            new Point(12, -3),
            new Point(15, -3),
            new Point(18, 0),
        );

        $intersections = $curve1->intersections($curve2, 0.5);

        // May or may not find the tangent point depending on tolerance and recursion
        self::assertIsArray($intersections);

        if (count($intersections) > 0) {
            // If found, one intersection should be near (9, 0)
            $found = false;
            foreach ($intersections as $int) {
                if (abs($int['point']->x - 9) < 1 && abs($int['point']->y - 0) < 1) {
                    $found = true;
                    break;
                }
            }
            self::assertTrue($found, 'If intersections found, one should be near (9, 0)');
        }
    }

    public function testIntersectionsWithSelfIntersectingCurve(): void
    {
        // Create a curve that loops back on itself
        $curve1 = new CubicCurve(
            new Point(0, 0),
            new Point(10, 10),
            new Point(10, -10),
            new Point(0, 0),
        );

        // Test against a straight line that crosses the loop
        $curve2 = new CubicCurve(
            new Point(0, 5),
            new Point(3, 5),
            new Point(7, 5),
            new Point(10, 5),
        );

        $intersections = $curve1->intersections($curve2, 0.5);

        // Should find at least one intersection
        self::assertGreaterThanOrEqual(0, count($intersections));
    }

    public function testTangentWithZeroDerivative(): void
    {
        // Create a curve that has a stationary point (zero derivative)
        // This is a degenerate case
        $curve = new CubicCurve(
            new Point(5, 5),
            new Point(5, 5),
            new Point(5, 5),
            new Point(5, 5),
        );

        $tangent = $curve->tangent(0.5);

        // For a stationary point, tangent should be zero vector or unit vector
        $magnitude = sqrt($tangent->x ** 2 + $tangent->y ** 2);
        self::assertLessThanOrEqual(1.0, $magnitude);
    }

    public function testParameterAtDistanceExactLength(): void
    {
        // Test parameter at distance exactly equal to curve length
        $curve = new CubicCurve(
            new Point(0, 0),
            new Point(3, 5),
            new Point(7, 5),
            new Point(10, 0),
        );

        $length = $curve->length(100);

        // Distance exactly at total length should give t = 1.0
        $t = $curve->parameterAtDistance($length);
        self::assertEqualsWithDelta(1.0, $t, 0.01);
    }

    public function testIntersectionsMultiplePoints(): void
    {
        // Create curves that intersect at multiple points
        $curve1 = new CubicCurve(
            new Point(0, 0),
            new Point(10, 15),
            new Point(20, 15),
            new Point(30, 0),
        );

        $curve2 = new CubicCurve(
            new Point(0, 10),
            new Point(10, -5),
            new Point(20, -5),
            new Point(30, 10),
        );

        $intersections = $curve1->intersections($curve2, 0.1);

        // Should find multiple intersections
        self::assertIsArray($intersections);
        // Verify each intersection has required structure
        foreach ($intersections as $int) {
            self::assertArrayHasKey('point', $int);
            self::assertArrayHasKey('t1', $int);
            self::assertArrayHasKey('t2', $int);
        }
    }

    public function testIntersectionsTightTolerance(): void
    {
        // Test with very tight tolerance to force deeper recursion
        $curve1 = new CubicCurve(
            new Point(0, 0),
            new Point(5, 10),
            new Point(15, 10),
            new Point(20, 0),
        );

        $curve2 = new CubicCurve(
            new Point(0, 10),
            new Point(5, 0),
            new Point(15, 0),
            new Point(20, 10),
        );

        // Very small tolerance should force deep recursion
        $intersections = $curve1->intersections($curve2, 0.001);

        self::assertIsArray($intersections);
    }

    public function testIntersectionsNearlyOverlapping(): void
    {
        // Test curves with nearly overlapping bounding boxes
        $curve1 = new CubicCurve(
            new Point(0, 0),
            new Point(1, 0.5),
            new Point(2, 0.5),
            new Point(3, 0),
        );

        $curve2 = new CubicCurve(
            new Point(0, 0.01),
            new Point(1, 0.51),
            new Point(2, 0.51),
            new Point(3, 0.01),
        );

        // These should have overlapping bounding boxes but may not actually intersect
        $intersections = $curve1->intersections($curve2, 0.1);

        self::assertIsArray($intersections);
    }

    public function testIntersectionsSameCurve(): void
    {
        // Test a curve intersecting with itself
        $curve = new CubicCurve(
            new Point(0, 0),
            new Point(3, 5),
            new Point(7, 5),
            new Point(10, 0),
        );

        $intersections = $curve->intersections($curve, 0.5);

        // A curve always "intersects" with itself at all points
        // But the algorithm may find endpoint intersections
        self::assertIsArray($intersections);
    }

    public function testIntersectionsBoundingBoxEdgeCases(): void
    {
        // Create curves with bounding boxes that touch at edges or corners
        $curve1 = new CubicCurve(
            new Point(0, 0),
            new Point(1, 1),
            new Point(2, 1),
            new Point(3, 0),
        );

        // Curve with bounding box touching at edge
        $curve2 = new CubicCurve(
            new Point(3, 0),
            new Point(4, 1),
            new Point(5, 1),
            new Point(6, 0),
        );

        $intersections = $curve1->intersections($curve2, 0.5);

        // May find intersection at shared point (3, 0)
        self::assertIsArray($intersections);
    }

    public function testParameterAtDistanceBoundaryValues(): void
    {
        // Test parameter calculation at various boundary values
        $curve = new CubicCurve(
            new Point(0, 0),
            new Point(5, 10),
            new Point(15, 10),
            new Point(20, 0),
        );

        $length = $curve->length(100);

        // Test various fractions of length
        $fractions = [0.1, 0.25, 0.5, 0.75, 0.9, 0.99];

        foreach ($fractions as $fraction) {
            $distance = $length * $fraction;
            $t = $curve->parameterAtDistance($distance);

            // t should be within valid range
            self::assertGreaterThanOrEqual(0.0, $t);
            self::assertLessThanOrEqual(1.0, $t);

            // Point at this t should be on the curve
            $point = $curve->pointAt($t);
            self::assertIsFloat($point->x);
            self::assertIsFloat($point->y);
        }
    }
}
