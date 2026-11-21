<?php

declare(strict_types=1);

namespace DbCms\Installer;

final class ProcessFailure extends \RuntimeException
{
    public function __construct(string $message, public readonly bool $packageMissing = false)
    {
        parent::__construct($message);
    }
}
