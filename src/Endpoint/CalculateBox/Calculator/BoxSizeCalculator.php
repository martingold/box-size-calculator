<?php

declare(strict_types=1);

namespace App\Endpoint\CalculateBox\Calculator;

use App\Endpoint\CalculateBox\Body\Product;
use App\Entity\Packaging;

interface BoxSizeCalculator
{
    /**
     * @param list<Product> $products
     * @param list<Packaging> $packagings
     *
     * @throws CalculatorFailure
     * @throws UnpackableProducts
     */
    public function getPackaging(array $products, array $packagings): Packaging;
}
