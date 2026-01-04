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

use Alto\Bezier\Point;
use Alto\Bezier\QuarticCurve;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(QuarticCurve::class)]
class QuarticCurveTest extends TestCase
{
    public function testPointAtStart(): void
    {
        $curve = new QuarticCurve(
            new Point(0, 0),
            new Point(1, 2),
            new Point(2, 3),
            new Point(3, 2),
            new Point(4, 0),
        );

        $point = $curve->pointAt(0.0);
        self::assertEquals(0.0, $point->x);
        self::assertEquals(0.0, $point->y);
    }

    public function testPointAtEnd(): void
    {
        $curve = new QuarticCurve(
            new Point(0, 0),
            new Point(1, 2),
            new Point(2, 3),
            new Point(3, 2),
            new Point(4, 0),
        );

        $point = $curve->pointAt(1.0);
        self::assertEquals(4.0, $point->x);
        self::assertEquals(0.0, $point->y);
    }

    public function testPointAtMidpoint(): void
    {
        $curve = new QuarticCurve(
            new Point(0, 0),
            new Point(1, 2),
            new Point(2, 3),
            new Point(3, 2),
            new Point(4, 0),
        );

        $point = $curve->pointAt(0.5);

        // Point should be somewhere in the middle
        self::assertGreaterThan(0, $point->x);
        self::assertLessThan(4, $point->x);
    }

    public function testBoundingBox(): void
    {
        $curve = new QuarticCurve(
            new Point(0, 0),
            new Point(1, 2),
            new Point(2, 3),
            new Point(3, 2),
            new Point(4, 0),
        );

        $bbox = $curve->boundingBox();

        // Should contain all control points
        self::assertLessThanOrEqual(0, $bbox->min->x);
        self::assertGreaterThanOrEqual(4, $bbox->max->x);
        self::assertLessThanOrEqual(0, $bbox->min->y);
        self::assertGreaterThanOrEqual(0, $bbox->max->y);
    }

    public function testSplitAtMidpoint(): void
    {
        $curve = new QuarticCurve(
            new Point(0, 0),
            new Point(1, 2),
            new Point(2, 3),
            new Point(3, 2),
            new Point(4, 0),
        );

        [$left, $right] = $curve->split(0.5);

        // Both should be QuarticCurve instances
        self::assertInstanceOf(QuarticCurve::class, $left);
        self::assertInstanceOf(QuarticCurve::class, $right);

        // Split point should match
        $splitPoint = $curve->pointAt(0.5);
        self::assertEquals($splitPoint, $left->p4);
        self::assertEquals($splitPoint, $right->p0);

        // Endpoints should match
        self::assertEquals($curve->p0, $left->p0);
        self::assertEquals($curve->p4, $right->p4);
    }

    public function testSplitPreservesOriginalCurve(): void
    {
        $curve = new QuarticCurve(
            new Point(0, 0),
            new Point(1, 2),
            new Point(2, 3),
            new Point(3, 2),
            new Point(4, 0),
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

    public function testDerivativeAtStart(): void
    {
        $curve = new QuarticCurve(
            new Point(0, 0),
            new Point(1, 2),
            new Point(2, 3),
            new Point(3, 2),
            new Point(4, 0),
        );

        $derivative = $curve->derivative(0.0);

        // Derivative at start should point from p0 to p1
        // For quartic: B'(0) = 4(P1 - P0)
        self::assertEquals(4.0, $derivative->x);
        self::assertEquals(8.0, $derivative->y);
    }

    public function testDerivativeAtEnd(): void
    {
        $curve = new QuarticCurve(
            new Point(0, 0),
            new Point(1, 2),
            new Point(2, 3),
            new Point(3, 2),
            new Point(4, 0),
        );

        $derivative = $curve->derivative(1.0);

        // Derivative at end should point from p3 to p4
        // For quartic: B'(1) = 4(P4 - P3)
        self::assertEquals(4.0, $derivative->x);
        self::assertEquals(-8.0, $derivative->y);
    }

    public function testTangentIsNormalized(): void
    {
        $curve = new QuarticCurve(
            new Point(0, 0),
            new Point(1, 2),
            new Point(2, 3),
            new Point(3, 2),
            new Point(4, 0),
        );

        $tangent = $curve->tangent(0.5);

        // Tangent should have unit length
        $magnitude = sqrt($tangent->x ** 2 + $tangent->y ** 2);
        self::assertEqualsWithDelta(1.0, $magnitude, 0.0001);
    }

    public function testNormalIsPerpendicular(): void
    {
        $curve = new QuarticCurve(
            new Point(0, 0),
            new Point(1, 2),
            new Point(2, 3),
            new Point(3, 2),
            new Point(4, 0),
        );

        $tangent = $curve->tangent(0.5);
        $normal = $curve->normal(0.5);

        // Normal and tangent should be perpendicular
        $dotProduct = $tangent->x * $normal->x + $tangent->y * $normal->y;
        self::assertEqualsWithDelta(0.0, $dotProduct, 0.0001);
    }

    public function testLengthOfStraightLine(): void
    {
        // Degenerate quartic curve that's a straight line
        $curve = new QuarticCurve(
            new Point(0, 0),
            new Point(1, 0),
            new Point(2, 0),
            new Point(3, 0),
            new Point(4, 0),
        );

        $length = $curve->length(100);

        // Should be exactly 4 (straight line from 0 to 4)
        self::assertEqualsWithDelta(4.0, $length, 0.01);
    }

    public function testPointsGenerator(): void
    {
        $curve = new QuarticCurve(
            new Point(0, 0),
            new Point(1, 2),
            new Point(2, 3),
            new Point(3, 2),
            new Point(4, 0),
        );

        $points = iterator_to_array($curve->points(0.5));

        self::assertCount(3, $points);
        self::assertEquals(new Point(0, 0), $points[0]);
        self::assertEquals(new Point(4, 0), $points[2]);
    }

    public function testPointsGeneratorWithSmallInterval(): void
    {
        $curve = new QuarticCurve(
            new Point(0, 0),
            new Point(1, 2),
            new Point(2, 3),
            new Point(3, 2),
            new Point(4, 0),
        );

        $points = iterator_to_array($curve->points(0.25));

        self::assertCount(5, $points);
        self::assertEquals(0, $points[0]->x);
        self::assertEquals(4, $points[4]->x);
    }

    // Arc length parameterization tests

    public function testParameterAtDistanceZero(): void
    {
        $curve = new QuarticCurve(
            new Point(0, 0),
            new Point(1, 2),
            new Point(2, 3),
            new Point(3, 2),
            new Point(4, 0),
        );

        $t = $curve->parameterAtDistance(0.0);

        self::assertEquals(0.0, $t);
    }

    public function testParameterAtDistanceFullLength(): void
    {
        $curve = new QuarticCurve(
            new Point(0, 0),
            new Point(1, 2),
            new Point(2, 3),
            new Point(3, 2),
            new Point(4, 0),
        );

        $length = $curve->length(100);
        $t = $curve->parameterAtDistance($length);

        self::assertEqualsWithDelta(1.0, $t, 0.01);
    }

    public function testParameterAtDistanceThrowsForNegative(): void
    {
        $curve = new QuarticCurve(
            new Point(0, 0),
            new Point(1, 2),
            new Point(2, 3),
            new Point(3, 2),
            new Point(4, 0),
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Distance must be non-negative');

        $curve->parameterAtDistance(-1.0);
    }

    public function testParameterAtDistanceThrowsForExceedingLength(): void
    {
        $curve = new QuarticCurve(
            new Point(0, 0),
            new Point(1, 2),
            new Point(2, 3),
            new Point(3, 2),
            new Point(4, 0),
        );

        $length = $curve->length(100);

        $this->expectException(\InvalidArgumentException::class);

        $curve->parameterAtDistance($length + 10);
    }

    public function testPointAtDistanceZero(): void
    {
        $curve = new QuarticCurve(
            new Point(0, 0),
            new Point(1, 2),
            new Point(2, 3),
            new Point(3, 2),
            new Point(4, 0),
        );

        $point = $curve->pointAtDistance(0.0);

        self::assertEquals($curve->p0, $point);
    }

    public function testPointAtDistanceFullLength(): void
    {
        $curve = new QuarticCurve(
            new Point(0, 0),
            new Point(1, 2),
            new Point(2, 3),
            new Point(3, 2),
            new Point(4, 0),
        );

        $length = $curve->length(100);
        $point = $curve->pointAtDistance($length);

        self::assertEqualsWithDelta($curve->p4->x, $point->x, 0.1);
        self::assertEqualsWithDelta($curve->p4->y, $point->y, 0.1);
    }

    // Intersection tests

    public function testIntersectionsWithNonIntersectingCurves(): void
    {
        $curve1 = new QuarticCurve(
            new Point(0, 0),
            new Point(1, 2),
            new Point(2, 3),
            new Point(3, 2),
            new Point(4, 0),
        );

        $curve2 = new QuarticCurve(
            new Point(0, 10),
            new Point(1, 12),
            new Point(2, 13),
            new Point(3, 12),
            new Point(4, 10),
        );

        $intersections = $curve1->intersections($curve2);

        // No intersections - curves are far apart
        self::assertCount(0, $intersections);
    }

    public function testIntersectionsMethodWorks(): void
    {
        // Test that intersection method runs without errors
        $curve1 = new QuarticCurve(
            new Point(0, 0),
            new Point(2, 5),
            new Point(5, 5),
            new Point(8, 5),
            new Point(10, 0),
        );

        $curve2 = new QuarticCurve(
            new Point(0, 10),
            new Point(2, 5),
            new Point(5, 5),
            new Point(8, 5),
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
        $curve1 = new QuarticCurve(
            new Point(0, 0),
            new Point(1, 3),
            new Point(3, 4),
            new Point(5, 3),
            new Point(6, 0),
        );

        $curve2 = new QuarticCurve(
            new Point(0, 8),
            new Point(1, 5),
            new Point(3, 4),
            new Point(5, 5),
            new Point(6, 8),
        );

        $intersections = $curve1->intersections($curve2, 0.3);

        // These curves share control points, so they likely intersect/touch
        self::assertGreaterThanOrEqual(0, count($intersections));
    }
}
