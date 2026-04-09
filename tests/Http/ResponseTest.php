<?php

namespace Impulse\Core\Tests\Http;

use Impulse\Core\Exceptions\ImpulseException;
use Impulse\Core\Http\Response;
use Impulse\Core\Http\Router\PageRouter;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_write_close();
        }

        parent::tearDown();
    }

    public function testJsonResponse(): void
    {
        $resp = Response::json(['ok' => true], 201);
        $this->assertSame(201, $resp->getStatusCode());
        $this->assertSame(['Content-Type' => 'application/json'], $resp->getHeaders());
        $this->assertSame('{"ok":true}', $resp->getContent());
    }

    public function testRedirectResponse(): void
    {
        $resp = Response::redirect('/foo');
        $this->assertSame(302, $resp->getStatusCode());
        $this->assertSame(['Location' => '/foo'], $resp->getHeaders());
        $this->assertSame('', $resp->getContent());
    }

    /**
     * @throws \JsonException
     */
    public function testRedirectToPageResponse(): void
    {
        new PageRouter(__DIR__ . '/Fixtures');

        $resp = Response::redirectToPage('login');

        $this->assertSame(302, $resp->getStatusCode());
        $this->assertSame(['Location' => '/login'], $resp->getHeaders());
    }

    /**
     * @throws \JsonException
     */
    public function testRedirectToUnknownPageThrowsException(): void
    {
        new PageRouter(__DIR__ . '/Fixtures');

        $this->expectException(ImpulseException::class);
        $this->expectExceptionMessage('La route "unknown" est inexistante');

        Response::redirectToPage('unknown');
    }

    public function testResponseCanStoreFlashMessage(): void
    {
        $response = Response::redirect('/login')->withFlash('registered', '1');

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('1', $_SESSION['_impulse_flash']['registered'] ?? null);
    }
}
