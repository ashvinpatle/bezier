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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(BoundingBox::class)]
class BoundingBoxTest extends TestCase
{
    public function testConstructor(): void
    {
        $boundingBox = new BoundingBox(new Point(0, 0), new Point(1, 1));

        self::assertEquals(new Point(0, 0), $boundingBox->min);
        self::assertEquals(new Point(1, 1), $boundingBox->max);
    }

    public function testConstructorWithNegativeValues(): void
    {
        $boundingBox = new BoundingBox(new Point(-1, -1), new Point(1, 1));

        self::assertEquals(new Point(-1, -1), $boundingBox->min);
        self::assertEquals(new Point(1, 1), $boundingBox->max);
    }

    public function testConstructorWithMaxLessThanMin(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid bounding box');

        new BoundingBox(new Point(1, 1), new Point(0, 0));
    }

    #[DataProvider('provideGetWidthCases')]
    public function testGetWidth(Point $min, Point $max, float $width): void
    {
        self::assertEquals($width, (new BoundingBox($min, $max))->getWidth());
    }

    /**
     * @return array<array{Point, Point, float}>
     */
    public static function provideGetWidthCases(): array
    {
        return [
            [new Point(0, 0), new Point(1, 1), 1],
            [new Point(0, 0), new Point(0, 1), 0],
            [new Point(0, 0), new Point(1, 0), 1],
            [new Point(0, 0), new Point(1, 1), 1],
        ];
    }

    #[DataProvider('provideGetHeightCases')]
    public function testGetHeight(Point $min, Point $max, float $height): void
    {
        self::assertEquals($height, (new BoundingBox($min, $max))->getHeight());
    }

    /**
     * @return array<array{Point, Point, float}>
     */
    public static function provideGetHeightCases(): array
    {
        return [
            [new Point(0, 0), new Point(1, 1), 1],
            [new Point(0, 0), new Point(0, 1), 1],
            [new Point(0, 0), new Point(1, 0), 0],
            [new Point(0, 0), new Point(1, 1), 1],
        ];
    }

    public function testGetBottomLeft(): void
    {
        $boundingBox = new BoundingBox(new Point(0, 0), new Point(1, 1));

        self::assertEquals(new Point(0, 1), $boundingBox->getBottomLeft());
    }

    public function testGetBottomRight(): void
    {
        $boundingBox = new BoundingBox(new Point(0, 0), new Point(1, 1));

        self::assertEquals(new Point(1, 1), $boundingBox->getBottomRight());
    }

    public function testGetTopRight(): void
    {
        $boundingBox = new BoundingBox(new Point(0, 0), new Point(1, 1));

        self::assertEquals(new Point(1, 0), $boundingBox->getTopRight());
    }

    public function testGetTopLeft(): void
    {
        $boundingBox = new BoundingBox(new Point(0, 0), new Point(1, 1));

        self::assertEquals(new Point(0, 0), $boundingBox->getTopLeft());
    }

    public function testWidthAlias(): void
    {
        $boundingBox = new BoundingBox(new Point(0, 0), new Point(5, 10));

        self::assertEquals(5, $boundingBox->width());
    }

    public function testHeightAlias(): void
    {
        $boundingBox = new BoundingBox(new Point(0, 0), new Point(5, 10));

        self::assertEquals(10, $boundingBox->height());
    }

    public function testMinX(): void
    {
        $boundingBox = new BoundingBox(new Point(2, 3), new Point(5, 7));

        self::assertEquals(2, $boundingBox->minX());
    }

    public function testMaxX(): void
    {
        $boundingBox = new BoundingBox(new Point(2, 3), new Point(5, 7));

        self::assertEquals(5, $boundingBox->maxX());
    }

    public function testMinY(): void
    {
        $boundingBox = new BoundingBox(new Point(2, 3), new Point(5, 7));

        self::assertEquals(3, $boundingBox->minY());
    }

    public function testMaxY(): void
    {
        $boundingBox = new BoundingBox(new Point(2, 3), new Point(5, 7));

        self::assertEquals(7, $boundingBox->maxY());
    }
}
