<?php

declare(strict_types=1);

namespace App\Config;

use Framework\App;
use App\Controllers\{
    HomeController,
    AboutController,
    AuthController,
    TransactionController,
    ReceiptController
};

use App\Middleware\{AuthRequiredMiddleware, GuestOnlyMiddleware};

function registerRoutes(App $app)
{
    $app->get('/', [HomeController::class, 'home'])->add(AuthRequiredMiddleware::class);
    $app->get('/about', [AboutController::class, 'about']);
    $app->get('/register', [AuthController::class, 'registerView'])->add(GuestOnlyMiddleware::class);
    $app->post('/register', [AuthController::class, 'register'])->add(GuestOnlyMiddleware::class);
    $app->get('/login', [AuthController::class, 'loginView'])->add(GuestOnlyMiddleware::class);
    $app->post('/login', [AuthController::class, 'login'])->add(GuestOnlyMiddleware::class);
    $app->get('logout', [AuthController::class, 'logout'])->add(AuthrequiredMiddleware::class);
    $app->get('/transaction', [TransactionController::class, 'createView'])->add(AuthrequiredMiddleware::class);;
    $app->post('/transaction', [TransactionController::class, 'create'])->add(AuthrequiredMiddleware::class);;
    $app->get('/transaction/{transaction}', [TransactionController::class, 'editView'])->add(AuthrequiredMiddleware::class);;
    $app->post('/transaction/{transaction}', [TransactionController::class, 'edit'])->add(AuthrequiredMiddleware::class);;
    $app->delete("/transaction/{transaction}", [TransactionController::class, 'delete'])->add(AuthrequiredMiddleware::class);;
    $app->get('/transaction/{transaction}/receipt', [ReceiptController::class, 'uploadView'])->add(AuthrequiredMiddleware::class);;
    $app->post('/transaction/{transaction}/receipt', [ReceiptController::class, 'upload'])->add(AuthrequiredMiddleware::class);;
    $app->get('/transaction/{transaction}/receipt/{receipt}', [ReceiptController::class, 'download'])->add(AuthrequiredMiddleware::class);;
    $app->delete('/transaction/{transaction}/receipt/{receipt}', [ReceiptController::class, 'delete'])->add(AuthrequiredMiddleware::class);;
}
