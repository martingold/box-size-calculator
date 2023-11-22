<?php

declare(strict_types=1);

namespace App\Endpoint\CalculateBox\Calculator;

use App\Endpoint\CalculateBox\Body\Product;
use App\Entity\Packaging;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;

readonly final class ApiBoxSizeCalculator implements BoxSizeCalculator
{
    public function __construct(
        private Client $client,
        private EntityManager $entityManager,
    ) {
        //
    }

    /**
     * @param non-empty-list<Product> $products
     * @param non-empty-list<Packaging> $packagings
     *
     * @throws CalculatorFailure
     * @throws UnpackableProducts
     */
    public function getPackaging(array $products, array $packagings): Packaging
    {
        $packagings = array_slice($packagings, 5, 1);

        $bins = array_map(function (Packaging $packaging): array {
            return [
                'w' => $packaging->getWidth(),
                'h' => $packaging->getHeight(),
                'd' => $packaging->getLength(),
                'id' => (string) $packaging->getId(),
            ];
        }, $packagings);

        $items = array_map(function (Product $product): array {
            return [
                'w' => $product->width,
                'h' => $product->height,
                'd' => $product->length,
                'id' => (string) $product->id,
                'vr' => 1, // All items can be vertically rotated
                'q' => 1,
            ];
        }, $products);

        $baseUrl = $_ENV['API_BASE_URL'];
        try {
            var_dump(json_encode([
                'bins' => $bins,
                'items' => $items,
                'username' => $_ENV['API_USERNAME'],
                'api_key' => $_ENV['API_KEY'],
            ]));exit;
            $response = $this->client->post($baseUrl . '/packer/findBinSize', [
                'http_errors' => true,
                'json' => [
                    'bins' => $bins,
                    'items' => $items,
                    'username' => $_ENV['API_USERNAME'],
                    'api_key' => $_ENV['API_KEY'],
                ]
            ]);

            $responseBody = json_decode(
                json: $response->getBody()->getContents(),
                associative: true,
                flags: JSON_THROW_ON_ERROR
            );
        } catch (GuzzleException|JsonException $exception) {
            throw new CalculatorFailure(
                'Fetching from API failed: ' . $exception->getMessage(),
                $exception->getCode(),
                $exception->getPrevious()
            );
        }

        if (count($responseBody['response']['errors'] ?? []) > 0) {
            throw new CalculatorFailure('API returned following errors: ' . json_encode($responseBody['response']['errors']));
        }

        $exactlyOnePackaging = count($responseBody['response']['bins_packed'] ?? []) === 1;
        $allProductsPacked = count ($responseBody['response']['not_packed_items']) === 0;

        // API could not fit all products into one packaging
        if (!$exactlyOnePackaging || !$allProductsPacked) {
            throw new UnpackableProducts('Cannot find suitable packaging.');
        }

        // Response body does not contain packaging ID
        $packagingId = $responseBody['response']['bins_packed'][0]['bin_data']['id'] ??
            throw new CalculatorFailure('API Response does not contain packaging ID');

        // Response body contains unknown packaging ID
        try {
            $packaging = $this->entityManager->find(Packaging::class, $packagingId);
        } catch (ORMException $e) {
            throw new CalculatorFailure('Failed fetching packaging with id: \'' . $packagingId . '\'', $e->getCode(), $e);
        }

        if (!$packaging instanceof Packaging) {
            throw new CalculatorFailure('Cannot find packaging with id: \'' . $packagingId . '\'');
        }

        // Calculate total products weight
        $totalWeight = array_sum(array_map(fn(Product $product): float => $product->weight, $products));

        // Products weight exceeds packaging max weight
        // May rather throw CalculatorFailure in case we would want to use different BoxSizeCalculator, because
        // this one does not accept packaging max weight into account when selection correct packaging
        if ($totalWeight > $packaging->getMaxWeight()) {
            throw new UnpackableProducts('Products weight sum exceeds maximum package weight.');
        }

        return $packaging;
    }
}
