<?php

namespace Impulse\Core\Tests\Http;

use Impulse\Core\Http\Response;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
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
}
