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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Point::class)]
class PointTest extends TestCase
{
    public function testConstructor(): void
    {
        $point = new Point(1, 2);

        self::assertEquals(1, $point->x);
        self::assertEquals(2, $point->y);
    }

    #[DataProvider('provideGetDistanceCases')]
    public function testGetDistance(Point $point1, Point $point2, float $distance): void
    {
        self::assertEquals($distance, $point1->getDistance($point2));
    }

    #[DataProvider('provideGetDistanceCases')]
    public function testGetDistanceIsReflexive(Point $point1, Point $point2, float $distance): void
    {
        self::assertEquals($point1->getDistance($point2), $point2->getDistance($point1));
    }

    public function testToString(): void
    {
        $point = new Point(1.5, 2.5);

        self::assertEquals('(1.5, 2.5)', (string) $point);
    }

    public function testToStringWithNegativeValues(): void
    {
        $point = new Point(-1, -2);

        self::assertEquals('(-1, -2)', (string) $point);
    }

    public function testToStringWithZero(): void
    {
        $point = new Point(0, 0);

        self::assertEquals('(0, 0)', (string) $point);
    }

    public function testFromArrayCreatesPoint(): void
    {
        $point = Point::fromArray([1.2, 3.4]);

        self::assertSame(1.2, $point->x);
        self::assertSame(3.4, $point->y);
    }

    public function testFromArrayRequiresTwoValues(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Point::fromArray([1.0]);
    }

    public function testFromArrayRequiresNumericValues(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        /* @phpstan-ignore-next-line intentionally invalid for test */
        Point::fromArray(['a', 2]);
    }

    /**
     * @return array<array{Point, Point, float}>
     */
    public static function provideGetDistanceCases(): array
    {
        return [
            [new Point(0, 0), new Point(3, 4), 5],
            [new Point(0, 0), new Point(0, 0), 0],
            [new Point(0, 0), new Point(0, 1), 1],
            [new Point(0, 0), new Point(1, 0), 1],
            [new Point(0, 0), new Point(1, 1), sqrt(2)],
        ];
    }
}
