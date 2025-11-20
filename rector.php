<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/test',
    ])
    ->withPhpSets()
    ->withTypeCoverageLevel(9)
    ->withDeadCodeLevel(9)
    ->withCodeQualityLevel(9);
