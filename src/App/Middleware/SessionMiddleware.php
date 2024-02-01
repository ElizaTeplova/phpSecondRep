<?php

declare(strict_types=1);

namespace App\Middleware;

use Framework\Contracts\MiddlewareInterface;
use App\Exceptions\SessionException;

class SessionMiddleware implements MiddlewareInterface
{

    public function process(callable $next)
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            throw new SessionException("Session is already active");
        }

        if (headers_sent($filename, $line)) { //function headers_sent(&$filename, &$line): bool
            throw new SessionException("Headers have already sent. Consider enabling output buffering. Data outputted from {$filename} - Line: {$line}");
        }

        session_start();
        $next();

        session_write_close();
    }
}
