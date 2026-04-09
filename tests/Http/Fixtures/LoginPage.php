<?php

declare(strict_types=1);

namespace Impulse\Core\Tests\Http\Fixtures;

use Impulse\Core\Attributes\PageProperty;
use Impulse\Core\Component\AbstractPage;

#[PageProperty(route: '/login', name: 'login', title: 'Connexion')]
final class LoginPage extends AbstractPage
{
    public function template(): string
    {
        return '<div>Login</div>';
    }
}
