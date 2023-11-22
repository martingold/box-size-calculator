<?php

declare(strict_types=1);

namespace App\Endpoint\CalculateBox;

use App\Endpoint\CalculateBox\Calculator\BoxSizeCalculator;
use App\Endpoint\CalculateBox\Calculator\CachedApiBoxSizeCalculator;
use App\Endpoint\CalculateBox\Calculator\CalculatorFailure;
use App\Endpoint\CalculateBox\Calculator\NaiveBoxSizeCalculator;
use App\Endpoint\CalculateBox\Calculator\UnpackableProducts;
use App\Entity\Packaging;

readonly final class PackagingFinder implements BoxSizeCalculator
{
    public function __construct(
        private CachedApiBoxSizeCalculator $cachedBoxSizeCalculator,
        private NaiveBoxSizeCalculator $naiveBoxSizeCalculator,
    ) {
        //
    }

    /**
     * @throws UnpackableProducts
     */
    public function getPackaging(array $products, array $packagings): Packaging
    {
        try {
            return $this->cachedBoxSizeCalculator->getPackaging($products, $packagings);
        } catch (CalculatorFailure) {
            return $this->naiveBoxSizeCalculator->getPackaging($products, $packagings);
        }
    }
}
