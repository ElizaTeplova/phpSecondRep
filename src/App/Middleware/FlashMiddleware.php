<?php

declare(strict_types=1);

namespace App\Middleware;

use Framework\TemplateEngine;
use Framework\Contracts\MiddlewareInterface;

class FlashMiddleware implements MiddlewareInterface
{
    public function __construct(private TemplateEngine $view)
    {
    }

    public function process(callable $next)
    {
        $this->view->addGlobal('errors', $_SESSION['errors'] ?? []); // ?? nullish operator
        unset($_SESSION['errors']);
        $this->view->addGlobal('oldFormData', $_SESSION['oldFormData'] ?? []);
        unset($_SESSION['oldFormData']);
        $next();
    }
}
