<?php

use Doctrine\ORM\Tools\Console\ConsoleRunner;

$entityManager = require_once __DIR__ . '/src/bootstrap.php';

return ConsoleRunner::createHelperSet($entityManager); // needed by vendor/bin/doctrine

