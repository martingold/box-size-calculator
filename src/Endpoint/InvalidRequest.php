<?php

declare(strict_types=1);

namespace App\Endpoint;

use Exception;
use Psr\Http\Message\ResponseInterface;

final class InvalidRequest extends Exception
{
    public function __construct(
        public readonly ResponseInterface $response,
        private readonly Exception $previous
    ) {
        parent::__construct($this->previous->message, $this->previous->code, $this->previous);
    }
}
