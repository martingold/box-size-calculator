<?php

declare(strict_types=1);

namespace App\Endpoint\CalculateBox\Calculator;

use App\Endpoint\CalculateBox\Body\Product;
use App\Entity\Packaging;

readonly final class NaiveBoxSizeCalculator implements BoxSizeCalculator
{
    /**
     * @param non-empty-list<Product> $products
     * @param non-empty-list<Packaging> $packagings
     *
     * @throws UnpackableProducts
     */
    public function getPackaging(array $products, array $packagings): Packaging
    {
        $widthSum = array_sum(array_map(fn (Product $p): float => $p->width, $products));
        $lengthSum = array_sum(array_map(fn (Product $p): float => $p->length, $products));
        $heightSum = array_sum(array_map(fn (Product $p): float => $p->height, $products));
        $weightSum = array_sum(array_map(fn (Product $p): float => $p->weight, $products));

        foreach ($packagings as $packaging) {
            if (
                $packaging->getWidth() > $widthSum
                && $packaging->getLength() > $lengthSum
                && $packaging->getHeight() > $heightSum
                && $packaging->getMaxWeight() > $weightSum
            ) {
                return $packaging;
            }
        }

        throw new UnpackableProducts('Cannot find suitable packaging.');
    }
}
