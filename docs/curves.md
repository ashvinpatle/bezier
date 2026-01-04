# Curve Types and Data Structures

## Point

`Point` is an immutable value object with `x` and `y` floats.

```php
$point = new Point(10.5, -2.3);
$distance = $point->getDistance(new Point(0, 0));
```

## Specialized Curves

| Class            | Control Points | Notes                                        |
|------------------|----------------|----------------------------------------------|
| `QuadraticCurve` | 3              | Fast calculations, ideal for simple shapes   |
| `CubicCurve`     | 4              | Matches SVG path `C` segments                |
| `QuarticCurve`   | 5              | Adds an extra handle for tight control       |
| `QuinticCurve`   | 6              | Useful for precision drawing or motion paths |

Each curve stores its control points and reuses shared logic from the abstract `Curve` base class.

## Arbitrary Order Curves

`NOrderCurve` accepts any number of control points ≥ 2 and uses De Casteljau's algorithm for evaluation.

```php
$curve = NOrderCurve::fromPoints([
    [0, 0],
    [20, 80],
    [40, -10],
    [60, 60],
    [80, 20],
]);
```

## Bounding Boxes

`BoundingBox` wraps the min/max corners of any curve sample or calculation.

```php
$box = $curve->boundingBox();
$width = $box->width();
$height = $box->height();
```

Use bounding boxes for hit testing or view-port fitting.
