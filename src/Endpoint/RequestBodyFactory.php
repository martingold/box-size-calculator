<?php

declare(strict_types=1);

namespace App\Endpoint;

use App\Endpoint\CalculateBox\Body\Products;
use CuyZ\Valinor\Mapper\MappingError;
use CuyZ\Valinor\Mapper\Source\Exception\InvalidSource;
use CuyZ\Valinor\Mapper\Source\Source;
use CuyZ\Valinor\Mapper\Tree\Message\Messages;
use CuyZ\Valinor\Mapper\TreeMapper;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

final readonly class RequestBodyFactory
{

    public function __construct(
        private TreeMapper $mapper,
        private RequestInterface $request
    ) {
        //
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T
     * @throws InvalidRequest
     */
    public function create(string $class): object
    {
        try {
            return $this->mapper->map(
                Products::class,
                Source::json($this->request->getBody()->getContents()),
            );
        } catch (InvalidSource $invalidSource) {
            throw new InvalidRequest(new Response(
                status: 400,
                reason: 'Invalid JSON body provided.'
            ), $invalidSource);
        } catch (MappingError $mappingError) {
            $errors = Messages::flattenFromNode($mappingError->node())->errors();

            $messages = [];
            foreach ($errors as $error) {
                $messages[$error->node()->path()] = $error->toString();
            }

            throw new InvalidRequest(new Response(
                status: 400,
                body: json_encode($messages),
                reason: 'Invalid request body.',
            ), $mappingError);
        }
    }

}
