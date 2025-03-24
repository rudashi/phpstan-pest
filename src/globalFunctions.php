<?php

declare(strict_types=1);

use PHPStan\DependencyInjection\ParameterNotFoundException;

try {
    /**
     * @var \PHPStan\DependencyInjection\Container $container
     */

    $directory = $container->getParameter('currentWorkingDirectory');

    $additionalFiles = glob($directory . '/**/Pest.php', GLOB_BRACE);

    foreach ($additionalFiles as $file) {
        require_once $file;
    }
} catch (ParameterNotFoundException $e) {

}



