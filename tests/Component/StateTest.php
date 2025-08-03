<?php

namespace Impulse\Core\Tests\Component;

use Impulse\Core\Component\State\State;
use PHPUnit\Framework\TestCase;

class StateTest extends TestCase
{
    public function testAllowedValuesConstraint(): void
    {
        $state = new State('primary', ['primary', 'secondary']);
        $this->assertSame('primary', $state->get());
        $state->set('secondary');
        $this->assertSame('secondary', $state->get());

        $this->expectException(\InvalidArgumentException::class);
        $state->set('danger');
    }
}
