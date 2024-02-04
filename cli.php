<?php

require __DIR__ . "/vendor/autoload.php";
include __DIR__ . "/vendor/vlucas/phpdotenv/src/Dotenv.php";

use Framework\Database;
use Dotenv\Dotenv;
use App\Services\LogAPIService;
use App\Config\RequestParams;

$logApi = new LogAPIService();
$dotenv = Dotenv::createImmutable('./');
$dotenv->load();

// $db = new Database($_ENV['DB_DRIVER'], [
//     'host' => $_ENV['DB_HOST'],
//     'port' => $_ENV['DB_PORT'],
//     'dbname' => $_ENV['DB_NAME']
// ], $_ENV['DB_USER'], $_ENV['DB_PASS']);


// $sqlFile = file_get_contents('./database.sql');

// $db->query($sqlFile);

$url = "https://api-metrika.yandex.net/management/v1/counter/{$_ENV['COUNTER_ID']}/logrequests";
$authorization = "Authorization: Bearer " . $_ENV['TOKEN'];
$params = new RequestParams(date1: date('Y-m-d', strtotime('-91 days')));

// $logApi->downloadParts($_ENV['COUNTER_ID'], $_ENV['TOKEN'], "33774617", 2);
$logApi->getCsvData($_ENV['COUNTER_ID'], $_ENV['TOKEN'], $params->getParams(), false);
// $logApi->evaluateRequest($counterId, $token, $params->getParams());
// $logApi->createLogs($_ENV['COUNTER_ID'], $_ENV['TOKEN'], $params->getParams());
// $logApi->getPartNumbers($_ENV['COUNTER_ID'], $_ENV['TOKEN'], requestId: '');
// $logApi->downloadParts($_ENV['COUNTER_ID'], $_ENV['TOKEN'], requestId: '33774523', partNums: 2);
