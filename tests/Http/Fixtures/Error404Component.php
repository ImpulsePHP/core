<?php
namespace App\Component;

use Impulse\Core\Attributes\PageProperty;
use Impulse\Core\Component\AbstractPage;

#[PageProperty(name: 'error404')]
final class Error404Component extends AbstractPage
{
    public function template(): string
    {
        return '<h1>Not Found</h1>';
    }
}
