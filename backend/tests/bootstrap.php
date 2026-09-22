<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Env;

Env::load(dirname(__DIR__), '.env.testing');
