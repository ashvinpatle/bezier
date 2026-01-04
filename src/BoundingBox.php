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
 * Represents a rectangular bounding box in 2D space.
 *
 * A bounding box is defined by minimum and maximum points (top-left and bottom-right corners).
 * This class is immutable and provides methods to access corner points and dimensions.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final readonly class BoundingBox
{
    /**
     * Create a new bounding box.
     *
     * @param Point $min The minimum corner (top-left)
     * @param Point $max The maximum corner (bottom-right)
     *
     * @throws \InvalidArgumentException If min is greater than max in any dimension
     */
    public function __construct(
        public Point $min,
        public Point $max,
    ) {
        if ($min->x > $max->x || $min->y > $max->y) {
            throw new \InvalidArgumentException('Invalid bounding box');
        }
    }

    /**
     * Get the top-left corner point.
     *
     * @return Point The top-left corner
     */
    public function getTopLeft(): Point
    {
        return new Point($this->min->x, $this->min->y);
    }

    /**
     * Get the top-right corner point.
     *
     * @return Point The top-right corner
     */
    public function getTopRight(): Point
    {
        return new Point($this->max->x, $this->min->y);
    }

    /**
     * Get the bottom-left corner point.
     *
     * @return Point The bottom-left corner
     */
    public function getBottomLeft(): Point
    {
        return new Point($this->min->x, $this->max->y);
    }

    /**
     * Get the bottom-right corner point.
     *
     * @return Point The bottom-right corner
     */
    public function getBottomRight(): Point
    {
        return new Point($this->max->x, $this->max->y);
    }

    /**
     * Get the width of the bounding box.
     *
     * @return float The width (max.x - min.x)
     */
    public function getWidth(): float
    {
        return $this->max->x - $this->min->x;
    }

    /**
     * Get the height of the bounding box.
     *
     * @return float The height (max.y - min.y)
     */
    public function getHeight(): float
    {
        return $this->max->y - $this->min->y;
    }

    /**
     * Get the width of the bounding box (alias for getWidth).
     *
     * @return float The width
     */
    public function width(): float
    {
        return $this->getWidth();
    }

    /**
     * Get the height of the bounding box (alias for getHeight).
     *
     * @return float The height
     */
    public function height(): float
    {
        return $this->getHeight();
    }

    /**
     * Get the minimum X coordinate.
     *
     * @return float The minimum X value
     */
    public function minX(): float
    {
        return $this->min->x;
    }

    /**
     * Get the maximum X coordinate.
     *
     * @return float The maximum X value
     */
    public function maxX(): float
    {
        return $this->max->x;
    }

    /**
     * Get the minimum Y coordinate.
     *
     * @return float The minimum Y value
     */
    public function minY(): float
    {
        return $this->min->y;
    }

    /**
     * Get the maximum Y coordinate.
     *
     * @return float The maximum Y value
     */
    public function maxY(): float
    {
        return $this->max->y;
    }
}
