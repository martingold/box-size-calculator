<?php

require __DIR__ . '/vendor/autoload.php';

use App\Application;
use DI\ContainerBuilder;
use GuzzleHttp\Psr7\Message;
use Psr\Http\Message\RequestInterface;

$diDefinitions = require __DIR__ . '/di-definitions.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions($diDefinitions);
$container = $containerBuilder->build();

/** @var Application $application */
$application = $container->get(Application::class);

$response = $application->run();

echo "<<< In:\n" . Message::toString($container->get(RequestInterface::class)) . "\n\n";
echo ">>> Out:\n" . Message::toString($response) . "\n\n";
