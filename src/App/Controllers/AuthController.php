<?php

declare(strict_types=1);

namespace App\Controllers;

use Framework\TemplateEngine;
use App\Services\{
    ValidatorService,
    UserService
};

class AuthController
{

    public function __construct(
        private TemplateEngine $view,
        private ValidatorService $validatorService,
        private UserService $userService
    ) {
    }

    public function registerView(): void
    {
        echo $this->view->render('/register.php', ['title' => 'Registration']);
    }

    public function register(): void
    {
        $this->validatorService->validateRegister($_POST);
        // dd($_POST);
    }
}
