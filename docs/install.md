# Getting Started

## Requirements

- PHP 8.3 or newer
- Composer for dependency management

## Installation

```bash
composer require alto/bezier
```

## Basic Usage

```php
use Alto\Bezier\Point;
use Alto\Bezier\CubicCurve;

$curve = new CubicCurve(
    new Point(0, 0),
    new Point(25, 60),
    new Point(75, 40),
    new Point(100, 100),
);

$point = $curve->pointAt(0.5);
$length = $curve->length();
$box = $curve->boundingBox();
```

## Common Tasks

| Task               | Method                                  |
|--------------------|-----------------------------------------|
| Sample points      | `points(float $interval)`               |
| Measure length     | `length(int $samples = 100)`            |
| Split curve        | `split(float $t)`                       |
| Get tangent/normal | `tangent(float $t)`, `normal(float $t)` |

## Error Handling

The library throws `InvalidArgumentException` for invalid parameters (e.g., insufficient control points, invalid
sampling intervals). Catch these exceptions to keep applications resilient.
