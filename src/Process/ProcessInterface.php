<?php

declare(strict_types=1);

namespace ProjectZer0\Pz\Process;

/**
 * @author Aurimas Niekis <aurimas@niekis.lt>
 */
interface ProcessInterface
{
    public function execute(): int;
}
