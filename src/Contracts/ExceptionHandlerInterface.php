<?php

declare(strict_types=1);

namespace Impulse\Core\Contracts;

use Throwable;
use Impulse\Core\Http\Response;

interface ExceptionHandlerInterface
{
    public function render(Throwable $e): Response;
}
