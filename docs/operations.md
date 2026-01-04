# Curve Operations

## Sampling and Evaluation

```php
$point = $curve->pointAt(0.25);
foreach ($curve->points(0.1) as $sample) {
    // consume samples
}
```

## Derivatives and Orientation

```php
$derivative = $curve->derivative(0.5);
$tangent = $curve->tangent(0.5);
$normal = $curve->normal(0.5);
```

Use derivatives for velocity vectors and tangents/normals for drawing or physics integration.

## Splitting Curves

```php
[$left, $right] = $curve->split(0.5);
```

Splitting returns two curve instances that perfectly reconstruct the original segment.

## Length and Arc-Length Parameterization

```php
$length = $curve->length(200);
$t = $curve->parameterAtDistance($length / 3);
$point = $curve->pointAtDistance(50);
```

Increase sample counts for higher accuracy when dealing with long or highly curved paths.

## Intersections

```php
$hits = $curve->intersections($otherCurve);
foreach ($hits as $hit) {
    $point = $hit['point'];
    $tOnFirst = $hit['t1'];
}
```

Intersection checks use recursive subdivision; adjust tolerance for performance vs accuracy.

## Bounding Box Utilities

```php
$box = $curve->boundingBox();
if ($box->maxX() < $viewportMinX) {
    // curve is completely left of the viewport
}
```

Bounding boxes are inexpensive filters before running more costly geometry tests.
