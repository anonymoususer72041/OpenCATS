<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Class_\AddPropertyToClassRector;
use Rector\Php74\Rector\Property\TypedPropertyRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([__DIR__ . '/src', __DIR__ . '/lib']);

    // Add rules to fix issues
    $rectorConfig->rules([
        AddPropertyToClassRector::class, // Declare dynamic properties
        TypedPropertyRector::class,     // Add type declarations to properties
    ]);
};
