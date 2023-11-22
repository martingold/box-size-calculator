<?php

declare(strict_types=1);

namespace App\Endpoint\CalculateBox\Body;

final readonly class Product
{
    public int $id;
    public float $width;
    public float $height;
    public float $length;
    public float $weight;

    // TODO: Handle non-positive weight and product dimensions
}
