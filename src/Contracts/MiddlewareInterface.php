<?php

declare(strict_types=1);

namespace Impulse\Core\Contracts;

use Impulse\Core\Http\Request;
use Impulse\Core\Http\Response;

interface MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response;
}
