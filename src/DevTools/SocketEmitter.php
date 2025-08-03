<?php

namespace Impulse\Core\DevTools;

final class SocketEmitter implements DevToolsEmitterInterface
{
    private string $address;

    public function __construct(?string $address = null)
    {
        if ($address === null) {
            $address = file_exists('/tmp/impulse-devtools.sock')
                ? 'unix:///tmp/impulse-devtools.sock'
                : 'tcp://127.0.0.1:9567';
        }

        if (!str_contains($address, '://')) {
            $address = str_starts_with($address, '/')
                ? 'unix://' . $address
                : 'tcp://' . $address;
        }

        $this->address = $address;
    }

    /**
     * @throws \JsonException
     */
    public function emit(array $event): void
    {
        $payload = json_encode($event, JSON_THROW_ON_ERROR);
        if ($payload === false) {
            return;
        }

        $errno = 0;
        $errstr = '';
        $stream = @stream_socket_client($this->address, $errno, $errstr, 0.1);
        if (!$stream) {
            return;
        }
        fwrite($stream, $payload . "\n");
        fclose($stream);
    }
}
