<?php

declare(strict_types=1);

namespace ProjectZer0\Pz\RPC\Command;

use ProjectZer0\Pz\RPC\RpcCommandInterface;

class OpenURLRpcCommand implements RpcCommandInterface
{
    public function __construct(private string $url)
    {
    }

    public function getMethodName(): string
    {
        return 'PzApp.OpenURL';
    }

    public function getPayload(): array
    {
        return [
            'url' => $this->url,
        ];
    }
}
