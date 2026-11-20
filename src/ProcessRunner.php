<?php

declare(strict_types=1);

namespace DbCms\Installer;

interface ProcessRunner
{
    /** @param non-empty-list<string> $command */
    public function run(array $command, string $directory): string;
}
