<?php

declare(strict_types=1);

require __DIR__ . "/../../vendor/autoload.php";

use Framework\App;
use App\Config\Paths;

use function App\Config\{registerRoutes, registerMiddleware};
// use App\Controllers\AboutController;

$app = new App(Paths::SOURCE . "App/container-definitions.php");

registerRoutes($app);
registerMiddleware($app);

return $app;
