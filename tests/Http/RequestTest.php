<?php

declare(strict_types=1);

namespace Impulse\Core\Tests\Http;

use Impulse\Core\Http\Request;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_write_close();
        }

        parent::tearDown();
    }

    public function testFlashMessageCanBeReadOnNextRequest(): void
    {
        $request = new Request('/register');
        $request->flash('registered', '1');

        $nextRequest = new Request('/login');

        $this->assertTrue($nextRequest->hasFlash('registered'));
        $this->assertSame('1', $nextRequest->peekFlash('registered'));
        $this->assertSame('1', $nextRequest->getFlash('registered'));
        $this->assertFalse($nextRequest->hasFlash('registered'));
    }

    public function testAllFlashesCanBeConsumedAtOnce(): void
    {
        $request = new Request('/register');
        $request->flash('success', 'Compte cree');
        $request->flash('type', 'success');

        $nextRequest = new Request('/login');

        $this->assertSame([
            'success' => 'Compte cree',
            'type' => 'success',
        ], $nextRequest->allFlashes());
        $this->assertSame([], $nextRequest->allFlashes(false));
    }
}
