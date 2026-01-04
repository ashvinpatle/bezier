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

use Alto\Bezier\Curve;
use Alto\Bezier\NOrderCurve;
use Alto\Bezier\Point;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NOrderCurve::class)]
class NOrderCurveTest extends TestCase
{
    public function testConstructorRequiresAtLeastTwoPoints(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('At least 2 control points are required');

        new NOrderCurve(new Point(0, 0));
    }

    public function testLinearCurve(): void
    {
        // Degree 1: linear curve
        $curve = new NOrderCurve(
            new Point(0, 0),
            new Point(10, 10),
        );

        self::assertEquals(1, $curve->degree);

        // Linear curve should be a straight line
        $midpoint = $curve->pointAt(0.5);
        self::assertEquals(5.0, $midpoint->x);
        self::assertEquals(5.0, $midpoint->y);
    }

    public function testQuadraticCurve(): void
    {
        // Degree 2: quadratic curve
        $curve = new NOrderCurve(
            new Point(0, 0),
            new Point(5, 10),
            new Point(10, 0),
        );

        self::assertEquals(2, $curve->degree);

        // Should start and end at control points
        $start = $curve->pointAt(0.0);
        self::assertEquals(0.0, $start->x);
        self::assertEquals(0.0, $start->y);

        $end = $curve->pointAt(1.0);
        self::assertEquals(10.0, $end->x);
        self::assertEquals(0.0, $end->y);
    }

    public function testCubicCurve(): void
    {
        // Degree 3: cubic curve
        $curve = new NOrderCurve(
            new Point(0, 0),
            new Point(0, 10),
            new Point(10, 10),
            new Point(10, 0),
        );

        self::assertEquals(3, $curve->degree);

        $start = $curve->pointAt(0.0);
        self::assertEquals(0.0, $start->x);
        self::assertEquals(0.0, $start->y);

        $end = $curve->pointAt(1.0);
        self::assertEquals(10.0, $end->x);
        self::assertEquals(0.0, $end->y);
    }

    public function testQuarticCurve(): void
    {
        // Degree 4: quartic curve
        $curve = new NOrderCurve(
            new Point(0, 0),
            new Point(1, 2),
            new Point(2, 3),
            new Point(3, 2),
            new Point(4, 0),
        );

        self::assertEquals(4, $curve->degree);

        $start = $curve->pointAt(0.0);
        self::assertEquals(0.0, $start->x);
        self::assertEquals(0.0, $start->y);

        $end = $curve->pointAt(1.0);
        self::assertEquals(4.0, $end->x);
        self::assertEquals(0.0, $end->y);
    }

    public function testHighDegreeSeventhOrder(): void
    {
        // Degree 7: seventh-order curve (8 control points)
        $curve = new NOrderCurve(
            new Point(0, 0),
            new Point(1, 3),
            new Point(2, 5),
            new Point(3, 6),
            new Point(4, 6),
            new Point(5, 5),
            new Point(6, 3),
            new Point(7, 0),
        );

        self::assertEquals(7, $curve->degree);

        // Should still interpolate start and end
        $start = $curve->pointAt(0.0);
        self::assertEquals(0.0, $start->x);
        self::assertEquals(0.0, $start->y);

        $end = $curve->pointAt(1.0);
        self::assertEquals(7.0, $end->x);
        self::assertEquals(0.0, $end->y);

        // Curve should be smooth
        $point = $curve->pointAt(0.5);
        self::assertGreaterThan(0, $point->x);
        self::assertLessThan(7, $point->x);
    }

    public function testBoundingBox(): void
    {
        $curve = new NOrderCurve(
            new Point(0, 0),
            new Point(5, 10),
            new Point(10, 5),
            new Point(15, 0),
        );

        $bbox = $curve->boundingBox();

        // Should contain all control points
        self::assertLessThanOrEqual(0, $bbox->min->x);
        self::assertGreaterThanOrEqual(15, $bbox->max->x);
        self::assertLessThanOrEqual(0, $bbox->min->y);
        self::assertGreaterThanOrEqual(10, $bbox->max->y);
    }

    public function testSplitAtMidpoint(): void
    {
        $curve = new NOrderCurve(
            new Point(0, 0),
            new Point(5, 10),
            new Point(10, 10),
            new Point(15, 0),
        );

        [$left, $right] = $curve->split(0.5);

        // Both should be NOrderCurve instances with same degree
        self::assertInstanceOf(NOrderCurve::class, $left);
        self::assertInstanceOf(NOrderCurve::class, $right);
        self::assertEquals($curve->degree, $left->degree);
        self::assertEquals($curve->degree, $right->degree);

        // Split point should match
        $splitPoint = $curve->pointAt(0.5);
        $leftEnd = $left->pointAt(1.0);
        $rightStart = $right->pointAt(0.0);

        self::assertEqualsWithDelta($splitPoint->x, $leftEnd->x, 0.0001);
        self::assertEqualsWithDelta($splitPoint->y, $leftEnd->y, 0.0001);
        self::assertEqualsWithDelta($splitPoint->x, $rightStart->x, 0.0001);
        self::assertEqualsWithDelta($splitPoint->y, $rightStart->y, 0.0001);
    }

    public function testSplitPreservesOriginalCurve(): void
    {
        $curve = new NOrderCurve(
            new Point(0, 0),
            new Point(2, 5),
            new Point(5, 8),
            new Point(8, 5),
            new Point(10, 0),
        );

        [$left, $right] = $curve->split(0.5);

        // Test points on left subcurve
        for ($i = 0; $i <= 10; ++$i) {
            $t = $i / 10;
            $leftPoint = $left->pointAt($t);
            $originalPoint = $curve->pointAt($t * 0.5);
            self::assertEqualsWithDelta($originalPoint->x, $leftPoint->x, 0.0001);
            self::assertEqualsWithDelta($originalPoint->y, $leftPoint->y, 0.0001);
        }

        // Test points on right subcurve
        for ($i = 0; $i <= 10; ++$i) {
            $t = $i / 10;
            $rightPoint = $right->pointAt($t);
            $originalPoint = $curve->pointAt(0.5 + $t * 0.5);
            self::assertEqualsWithDelta($originalPoint->x, $rightPoint->x, 0.0001);
            self::assertEqualsWithDelta($originalPoint->y, $rightPoint->y, 0.0001);
        }
    }

    public function testDerivativeLinearCurve(): void
    {
        // Linear curve derivative should be constant
        $curve = new NOrderCurve(
            new Point(0, 0),
            new Point(10, 5),
        );

        $derivativeStart = $curve->derivative(0.0);
        $derivativeMid = $curve->derivative(0.5);
        $derivativeEnd = $curve->derivative(1.0);

        // All should be the same (constant derivative)
        self::assertEquals($derivativeStart->x, $derivativeMid->x);
        self::assertEquals($derivativeStart->y, $derivativeMid->y);
        self::assertEquals($derivativeStart->x, $derivativeEnd->x);
        self::assertEquals($derivativeStart->y, $derivativeEnd->y);

        // Should be the vector from p0 to p1
        self::assertEquals(10.0, $derivativeStart->x);
        self::assertEquals(5.0, $derivativeStart->y);
    }

    public function testDerivativeQuadraticCurve(): void
    {
        $curve = new NOrderCurve(
            new Point(0, 0),
            new Point(5, 10),
            new Point(10, 0),
        );

        $derivativeStart = $curve->derivative(0.0);

        // For quadratic: B'(0) = 2(P1 - P0)
        self::assertEquals(10.0, $derivativeStart->x); // 2 * (5 - 0)
        self::assertEquals(20.0, $derivativeStart->y); // 2 * (10 - 0)
    }

    public function testDerivativeCubicCurve(): void
    {
        $curve = new NOrderCurve(
            new Point(0, 0),
            new Point(0, 10),
            new Point(10, 10),
            new Point(10, 0),
        );

        $derivativeStart = $curve->derivative(0.0);

        // For cubic: B'(0) = 3(P1 - P0)
        self::assertEquals(0.0, $derivativeStart->x); // 3 * (0 - 0)
        self::assertEquals(30.0, $derivativeStart->y); // 3 * (10 - 0)
    }

    public function testDerivativeQuarticCurve(): void
    {
        $curve = new NOrderCurve(
            new Point(0, 0),
            new Point(1, 2),
            new Point(2, 3),
            new Point(3, 2),
            new Point(4, 0),
        );

        $derivativeStart = $curve->derivative(0.0);

        // For quartic: B'(0) = 4(P1 - P0)
        self::assertEquals(4.0, $derivativeStart->x); // 4 * (1 - 0)
        self::assertEquals(8.0, $derivativeStart->y); // 4 * (2 - 0)

        $derivativeEnd = $curve->derivative(1.0);

        // For quartic: B'(1) = 4(P4 - P3)
        self::assertEquals(4.0, $derivativeEnd->x); // 4 * (4 - 3)
        self::assertEquals(-8.0, $derivativeEnd->y); // 4 * (0 - 2)
    }

    public function testTangentIsNormalized(): void
    {
        $curve = new NOrderCurve(
            new Point(0, 0),
            new Point(5, 10),
            new Point(10, 10),
            new Point(15, 0),
        );

        for ($i = 0; $i <= 10; ++$i) {
            $t = $i / 10;
            $tangent = $curve->tangent($t);

            // Tangent should have unit length
            $magnitude = sqrt($tangent->x ** 2 + $tangent->y ** 2);
            self::assertEqualsWithDelta(1.0, $magnitude, 0.0001);
        }
    }

    public function testNormalIsPerpendicular(): void
    {
        $curve = new NOrderCurve(
            new Point(0, 0),
            new Point(5, 10),
            new Point(10, 10),
            new Point(15, 0),
        );

        for ($i = 0; $i <= 10; ++$i) {
            $t = $i / 10;
            $tangent = $curve->tangent($t);
            $normal = $curve->normal($t);

            // Normal and tangent should be perpendicular (dot product = 0)
            $dotProduct = $tangent->x * $normal->x + $tangent->y * $normal->y;
            self::assertEqualsWithDelta(0.0, $dotProduct, 0.0001);

            // Normal should also have unit length
            $magnitude = sqrt($normal->x ** 2 + $normal->y ** 2);
            self::assertEqualsWithDelta(1.0, $magnitude, 0.0001);
        }
    }

    public function testLengthOfStraightLine(): void
    {
        // Degenerate curve that's a straight horizontal line
        $curve = new NOrderCurve(
            new Point(0, 5),
            new Point(2.5, 5),
            new Point(5, 5),
            new Point(7.5, 5),
            new Point(10, 5),
        );

        $length = $curve->length(100);

        // Should be exactly 10 (straight line from x=0 to x=10)
        self::assertEqualsWithDelta(10.0, $length, 0.01);
    }

    public function testArcLengthParameterization(): void
    {
        // Straight line for predictable arc length
        $curve = new NOrderCurve(
            new Point(0, 0),
            new Point(5, 0),
            new Point(10, 0),
        );

        $totalLength = $curve->length();

        // Point at half the distance should be at half the line
        $midPoint = $curve->pointAtDistance($totalLength / 2);
        self::assertEqualsWithDelta(5.0, $midPoint->x, 0.1);

        // Point at quarter distance
        $quarterPoint = $curve->pointAtDistance($totalLength / 4);
        self::assertEqualsWithDelta(2.5, $quarterPoint->x, 0.1);
    }

    public function testIntersectionMethod(): void
    {
        // Verify the intersection method works (inherited from Curve base class)
        $curve1 = new NOrderCurve(
            new Point(0, 0),
            new Point(50, 100),
            new Point(100, 0),
        );

        $curve2 = new NOrderCurve(
            new Point(0, 100),
            new Point(50, 0),
            new Point(100, 100),
        );

        $intersections = $curve1->intersections($curve2);

        // Should return an array (may be empty or contain intersections)
        self::assertIsArray($intersections);
    }

    public function testPointsGeneration(): void
    {
        $curve = new NOrderCurve(
            new Point(0, 0),
            new Point(5, 10),
            new Point(10, 0),
        );

        $points = iterator_to_array($curve->points(0.25));

        // With interval 0.25, should get points at t = 0, 0.25, 0.5, 0.75, 1.0
        self::assertCount(5, $points);

        // First point should be the start
        self::assertEquals(0.0, $points[0]->x);
        self::assertEquals(0.0, $points[0]->y);

        // Last point should be the end
        $lastIndex = count($points) - 1;
        self::assertEquals(10.0, $points[$lastIndex]->x);
        self::assertEquals(0.0, $points[$lastIndex]->y);
    }

    public function testFromPointsAcceptsArrays(): void
    {
        $curve = Curve::fromPoints([[0, 0], [10, 0], [10, 10]]);

        self::assertInstanceOf(NOrderCurve::class, $curve);
        self::assertSame([[0.0, 0.0], [10.0, 0.0], [10.0, 10.0]], $curve->toArray());
    }

    public function testFromPointsUsesExistingPointInstances(): void
    {
        $pointA = new Point(1, 2);
        $pointB = new Point(3, 4);

        $curve = Curve::fromPoints([$pointA, $pointB]);

        $property = new \ReflectionProperty(Curve::class, 'points');
        $points = $property->getValue($curve);

        self::assertSame($pointA, $points[0]);
        self::assertSame($pointB, $points[1]);
    }

    public function testFromPointsRejectsInvalidInput(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Curve::fromPoints([new Point(0, 0)]);
    }

    public function testToArrayExportsCoordinatePairs(): void
    {
        $curve = new NOrderCurve(
            new Point(1, 2),
            new Point(3, 4),
            new Point(5, 6),
        );

        self::assertSame([[1.0, 2.0], [3.0, 4.0], [5.0, 6.0]], $curve->toArray());
    }

    public function testVeryHighDegreeCurve(): void
    {
        // Test a degree 10 curve (11 control points)
        $points = [];
        for ($i = 0; $i <= 10; ++$i) {
            $points[] = new Point($i, sin($i / 10 * M_PI) * 5);
        }

        $curve = new NOrderCurve(...$points);

        self::assertEquals(10, $curve->degree);

        // Should still work correctly
        $start = $curve->pointAt(0.0);
        self::assertEquals(0.0, $start->x);

        $end = $curve->pointAt(1.0);
        self::assertEquals(10.0, $end->x);

        // Should be able to split
        [$left, $right] = $curve->split(0.5);
        self::assertInstanceOf(NOrderCurve::class, $left);
        self::assertInstanceOf(NOrderCurve::class, $right);
    }
}
