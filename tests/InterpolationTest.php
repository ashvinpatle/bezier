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

use Alto\Bezier\Interpolation;
use Alto\Bezier\Point;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Interpolation::class)]
class InterpolationTest extends TestCase
{
    #[DataProvider('provideLinearInterpolationCases')]
    public function testLinear(Point $p1, Point $p2, float $t, Point $expected): void
    {
        $result = Interpolation::linear($p1, $p2, $t);

        self::assertEquals($expected->x, $result->x);
        self::assertEquals($expected->y, $result->y);
    }

    /**
     * @return array<string, array{Point, Point, float, Point}>
     */
    public static function provideLinearInterpolationCases(): array
    {
        return [
            'Start point (t=0)' => [
                new Point(0, 0),
                new Point(100, 100),
                0.0,
                new Point(0, 0),
            ],
            'End point (t=1)' => [
                new Point(0, 0),
                new Point(100, 100),
                1.0,
                new Point(100, 100),
            ],
            'Midpoint (t=0.5)' => [
                new Point(0, 0),
                new Point(100, 100),
                0.5,
                new Point(50, 50),
            ],
            'Quarter point (t=0.25)' => [
                new Point(0, 0),
                new Point(100, 100),
                0.25,
                new Point(25, 25),
            ],
            'Three quarters (t=0.75)' => [
                new Point(0, 0),
                new Point(100, 100),
                0.75,
                new Point(75, 75),
            ],
            'Negative coordinates' => [
                new Point(-50, -50),
                new Point(50, 50),
                0.5,
                new Point(0, 0),
            ],
            'Different X and Y' => [
                new Point(0, 100),
                new Point(100, 0),
                0.5,
                new Point(50, 50),
            ],
            'Same points' => [
                new Point(42, 42),
                new Point(42, 42),
                0.5,
                new Point(42, 42),
            ],
        ];
    }
}
