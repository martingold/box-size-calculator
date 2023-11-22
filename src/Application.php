<?php

namespace App;

use App\Endpoint\CalculateBox\Body\Products;
use App\Endpoint\CalculateBox\Calculator\ApiBoxSizeCalculator;
use App\Endpoint\CalculateBox\Calculator\UnpackableProducts;
use App\Endpoint\CalculateBox\PackagingFinder;
use App\Endpoint\InvalidRequest;
use App\Endpoint\RequestBodyFactory;
use App\Entity\Packaging;
use Doctrine\ORM\EntityManager;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

readonly class Application
{
    public function __construct(
        private RequestBodyFactory $requestBodyFactory,
        private EntityManager $entityManager,
        private PackagingFinder $packagingFinder,
    ) {
        //
    }

    public function run(): ResponseInterface
    {
        try {
            $products = $this->requestBodyFactory->create(Products::class)->products;
        } catch (InvalidRequest $invalidRequest) {
            return $invalidRequest->response;
        }

        $packagings = $this->entityManager->getRepository(Packaging::class)->findAll();

        try {
            $packaging = $this->packagingFinder->getPackaging($products, $packagings);
        } catch (UnpackableProducts) {
            return new Response(
                status: 404,
                body: 'Cannot find suitable packaging size',
            );
        }

        return new Response(
            status: 200,
            body: json_encode([
                'id' => $packaging->getId(),
                'maxWeight' => $packaging->getMaxWeight(),
                'width' => $packaging->getWidth(),
                'height' => $packaging->getHeight(),
                'length' => $packaging->getLength(),
            ], JSON_PRETTY_PRINT),
        );
    }

}
