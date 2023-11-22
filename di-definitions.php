<?php

declare(strict_types=1);

use CuyZ\Valinor\Mapper\TreeMapper;
use CuyZ\Valinor\MapperBuilder;
use Doctrine\ORM\EntityManager;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;

$request = new Request(
    'POST',
    new Uri('http://localhost/pack'),
    ['Content-Type' => 'application/json'],
    $argv[1]
);

$cache = new Psr16Cache(new FilesystemAdapter(
    directory: __DIR__ . '/tmp/cache',
));

return [
    RequestInterface::class => $request,
    EntityManager::class => require __DIR__ . '/src/bootstrap.php',
    TreeMapper::class => (new MapperBuilder())->mapper(),
    CacheInterface::class => $cache,
];
