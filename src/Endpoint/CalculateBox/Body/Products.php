<?php

declare(strict_types=1);

namespace App\Endpoint\CalculateBox\Body;

final readonly class Products
{
    /** @var non-empty-list<Product> */
    public array $products;
}
