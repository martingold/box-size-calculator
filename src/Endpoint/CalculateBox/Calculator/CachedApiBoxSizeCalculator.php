<?php

declare(strict_types=1);

namespace App\Endpoint\CalculateBox\Calculator;

use App\Endpoint\CalculateBox\Body\Product;
use App\Entity\Packaging;
use DateInterval;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

readonly final class CachedApiBoxSizeCalculator implements BoxSizeCalculator
{
    public function __construct(
        private ApiBoxSizeCalculator $apiBoxSizeCalculator,
        private CacheInterface $cache,
        private EntityManager $entityManager,
    ) {
        //
    }

    /**
     * @param non-empty-list<Product> $products
     * @param non-empty-list<Packaging> $packagings
     *
     * @throws UnpackableProducts
     * @throws CalculatorFailure
     */
    public function getPackaging(array $products, array $packagings): Packaging
    {
        $productsHash = $this->calculateProductsHash($products);

        // Cache key is too long or may contain invalid characters (depends on cache driver)
        try {
            $packagingId = $this->cache->get($productsHash);
        } catch (InvalidArgumentException $e) {
            throw new CalculatorFailure('Failed fetching from cache key: \'' . $productsHash . '\'', $e->getCode(), $e);
        }

        // Packaging not found in cache
        if ($packagingId === null) {
            $packaging = $this->apiBoxSizeCalculator->getPackaging($products, $packagings);

            // Unhandled InvalidArgumentException
            // Same key is already validated by getting from cache
            $this->cache->set(
                $productsHash,
                $packaging->getId(),
                new DateInterval('P1D'),
            );

            return $packaging;
        }

        try {
            return $this->entityManager->find(Packaging::class, (int) $packagingId);
        } catch (ORMException $e) {
            throw new CalculatorFailure(
                'Failed fetching packaging with id \'' . $packagingId . '\'',
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param non-empty-list<Product> $products
     * @return string
     */
    private function calculateProductsHash(array $products): string
    {
        return implode('|', array_map(fn (Product $p): int => $p->id, $products));
    }
}
