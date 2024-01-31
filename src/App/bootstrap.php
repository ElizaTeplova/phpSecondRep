<?php

declare(strict_types=1);

require __DIR__ . "/../../vendor/autoload.php";

use Framework\App;
use App\Config\Paths;

use function App\Config\registerRoutes;
// use App\Controllers\AboutController;

$app = new App(Paths::SOURCE . "App/container-definitions.php");

registerRoutes($app);

return $app;
