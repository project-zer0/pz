<?php

declare(strict_types=1);

namespace ProjectZer0\Pz\RPC;

/**
 * Interface RpcCommandInterface.
 */
interface RpcCommandInterface
{
    public function getMethodName(): string;

    public function getPayload(): array;
}
