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
 * Represents a 2D point with x and y coordinates.
 *
 * This class is immutable (readonly) and represents a point in 2D space.
 * It provides utilities for distance calculation and string representation.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final readonly class Point implements \Stringable
{
    /**
     * Create a new point.
     *
     * @param float $x The x-coordinate
     * @param float $y The y-coordinate
     */
    public function __construct(
        public float $x,
        public float $y,
    ) {
    }

    /**
     * Create a point from a two-element array [x, y].
     *
     * @param array<int, mixed> $coordinates
     */
    public static function fromArray(array $coordinates): self
    {
        if (2 !== count($coordinates) || !array_key_exists(0, $coordinates) || !array_key_exists(1, $coordinates)) {
            throw new \InvalidArgumentException('Point::fromArray expects exactly two numeric values');
        }

        if (!is_numeric($coordinates[0]) || !is_numeric($coordinates[1])) {
            throw new \InvalidArgumentException('Point::fromArray expects numeric values');
        }

        return new self((float) $coordinates[0], (float) $coordinates[1]);
    }

    /**
     * Calculate the Euclidean distance to another point.
     *
     * @param Point $point The target point
     *
     * @return float The distance between this point and the target point
     */
    public function getDistance(Point $point): float
    {
        return sqrt(($this->x - $point->x) ** 2 + ($this->y - $point->y) ** 2);
    }

    /**
     * Get string representation of the point.
     *
     * @return string Point formatted as "(x, y)"
     */
    public function __toString(): string
    {
        return '('.$this->x.', '.$this->y.')';
    }
}
