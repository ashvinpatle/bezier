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
use Alto\Bezier\Curve;
use Alto\Bezier\Point;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Curve::class)]
class CurveTest extends TestCase
{
    public function testPointsGeneratorYieldsExpectedSamples(): void
    {
        $curve = $this->line(new Point(0, 0), new Point(4, 0));

        $points = iterator_to_array($curve->points(0.25));

        self::assertCount(5, $points);
        self::assertEquals(new Point(2.0, 0.0), $points[2]);
        self::assertEquals(new Point(4.0, 0.0), $points[4]);
    }

    public function testPointsThrowsWhenIntervalIsInvalid(): void
    {
        $curve = $this->line(new Point(0, 0), new Point(1, 1));

        $this->expectException(\InvalidArgumentException::class);
        iterator_to_array($curve->points(0.0));
    }

    public function testLengthMatchesLinearDistance(): void
    {
        $curve = $this->line(new Point(0, 0), new Point(3, 4));

        self::assertEqualsWithDelta(5.0, $curve->length(), 1e-6);
    }

    public function testParameterAtDistanceHandlesBounds(): void
    {
        $curve = $this->line(new Point(0, 0), new Point(3, 4));
        $length = $curve->length();

        self::assertSame(0.0, $curve->parameterAtDistance(0.0));
        self::assertEqualsWithDelta(1.0, $curve->parameterAtDistance($length), 1e-10);
    }

    public function testParameterAtDistanceReturnsMidpointParameter(): void
    {
        $curve = $this->line(new Point(0, 0), new Point(3, 4));
        $half = $curve->length() / 2;

        self::assertEqualsWithDelta(0.5, $curve->parameterAtDistance($half), 1e-4);
    }

    public function testPointAtDistanceReturnsExpectedPoint(): void
    {
        $curve = $this->line(new Point(0, 0), new Point(3, 4));
        $point = $curve->pointAtDistance($curve->length() / 2);

        self::assertEqualsWithDelta(1.5, $point->x, 1e-9);
        self::assertEqualsWithDelta(2.0, $point->y, 1e-9);
    }

    public function testTangentNormalizesDerivativeVector(): void
    {
        $curve = $this->line(new Point(0, 0), new Point(4, 0));

        self::assertEquals(new Point(1.0, 0.0), $curve->tangent(0.3));
    }

    public function testNormalReturnsPerpendicularVector(): void
    {
        $curve = $this->line(new Point(0, 0), new Point(0, 5));

        self::assertEquals(new Point(-1.0, 0.0), $curve->normal(0.4));
    }

    public function testIntersectionsReturnEmptyWhenBoundingBoxesDoNotOverlap(): void
    {
        $curve1 = $this->line(new Point(0, 0), new Point(0, 1));
        $curve2 = $this->line(new Point(2, 0), new Point(2, 1));

        self::assertCount(0, $curve1->intersections($curve2));
    }

    public function testIntersectionsDetectCrossingLines(): void
    {
        $curve1 = $this->line(new Point(0, 0), new Point(2, 2));
        $curve2 = $this->line(new Point(0, 2), new Point(2, 0));

        $intersections = $curve1->intersections($curve2, 0.01, 16);

        self::assertCount(1, $intersections);
        self::assertEqualsWithDelta(1.0, $intersections[0]['point']->x, 0.02);
        self::assertEqualsWithDelta(1.0, $intersections[0]['point']->y, 0.02);
        self::assertEqualsWithDelta(0.5, $intersections[0]['t1'], 0.05);
        self::assertEqualsWithDelta(0.5, $intersections[0]['t2'], 0.05);
    }

    public function testIntersectionsRespectMaxDepthLimit(): void
    {
        $curve1 = $this->line(new Point(0, 0), new Point(2, 2));
        $curve2 = $this->line(new Point(0, 2), new Point(2, 0));

        $intersections = $curve1->intersections($curve2, 0.001, 0);

        self::assertCount(0, $intersections);
    }

    public function testIntersectionsDeduplicateCloseResults(): void
    {
        $curve1 = new ShrinkingCurve(new Point(0, 0), 1.0);
        $curve2 = new ShrinkingCurve(new Point(0, 0), 1.0);

        $intersections = $curve1->intersections($curve2, 0.05, 8);

        self::assertCount(1, $intersections);
        self::assertEquals(new Point(0.0, 0.0), $intersections[0]['point']);
    }

    private function line(Point $start, Point $end): TestLineCurve
    {
        return new TestLineCurve($start, $end);
    }
}

final readonly class TestLineCurve extends Curve
{
    public function __construct(private Point $start, private Point $end)
    {
        parent::__construct($start, $end);
    }

    public function pointAt(float $t): Point
    {
        $x = $this->start->x + ($this->end->x - $this->start->x) * $t;
        $y = $this->start->y + ($this->end->y - $this->start->y) * $t;

        return new Point($x, $y);
    }

    public function boundingBox(): BoundingBox
    {
        $minX = min($this->start->x, $this->end->x);
        $minY = min($this->start->y, $this->end->y);
        $maxX = max($this->start->x, $this->end->x);
        $maxY = max($this->start->y, $this->end->y);

        return new BoundingBox(new Point($minX, $minY), new Point($maxX, $maxY));
    }

    public function split(float $t): array
    {
        $mid = $this->pointAt($t);

        return [
            new self($this->start, $mid),
            new self($mid, $this->end),
        ];
    }

    public function derivative(float $t): Point
    {
        return new Point(
            $this->end->x - $this->start->x,
            $this->end->y - $this->start->y,
        );
    }
}

final readonly class ShrinkingCurve extends Curve
{
    public function __construct(private Point $center, private float $size)
    {
        parent::__construct($center);
    }

    public function pointAt(float $t): Point
    {
        return $this->center;
    }

    public function boundingBox(): BoundingBox
    {
        $half = $this->size / 2;

        return new BoundingBox(
            new Point($this->center->x - $half, $this->center->y - $half),
            new Point($this->center->x + $half, $this->center->y + $half),
        );
    }

    public function split(float $t): array
    {
        $childSize = $this->size / 2;

        return [
            new self($this->center, $childSize),
            new self($this->center, $childSize),
        ];
    }

    public function derivative(float $t): Point
    {
        return new Point(0.0, 0.0);
    }
}
