# Practical Recipes

## Converting from SVG Paths

```php
use Alto\Bezier\Point;
use Alto\Bezier\CubicCurve;

function fromSvgCommand(array $cmd): CubicCurve {
    return new CubicCurve(
        new Point($cmd['x1'], $cmd['y1']),
        new Point($cmd['c1x'], $cmd['c1y']),
        new Point($cmd['c2x'], $cmd['c2y']),
        new Point($cmd['x2'], $cmd['y2']),
    );
}
```

Map your own SVG parser output into the provided curve classes.

## Evenly Spaced Points for Animation

```php
$frames = [];
$total = $curve->length();
$step = $total / 20;
for ($d = 0; $d <= $total; $d += $step) {
    $frames[] = $curve->pointAtDistance($d);
}
```

Use arc-length sampling to keep motion speed constant.

## Collision Checks

```php
if ($curve->boundingBox()->maxX() < $other->boundingBox()->minX()) {
    return false; // quick reject
}
return !empty($curve->intersections($other));
```

Combine cheap bounding box tests with detailed intersection checks.

## Numerical Stability Tips

- Keep coordinates in similar ranges to reduce floating-point error.
- Increase sampling counts for `length()` and `boundingBox()` when curves fold tightly.
- Handle `InvalidArgumentException` when supplying dynamic input.
