<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

/*
 * When developing inside a Shopware project, resolve Shopware/Symfony framework classes
 * from the project's vendor dir. The project autoloader is intentionally not loaded as
 * a whole, because its phpunit version would clash with the plugin's own.
 */
$projectVendorDir = __DIR__.'/../../../../vendor';
if (is_dir($projectVendorDir)) {
    $prefixes = [
        'Shopware\\Core\\' => $projectVendorDir.'/shopware/core',
        'Symfony\\Bundle\\FrameworkBundle\\' => $projectVendorDir.'/symfony/framework-bundle',
        'Symfony\\Component\\HttpFoundation\\' => $projectVendorDir.'/symfony/http-foundation',
        'Symfony\\Component\\HttpKernel\\' => $projectVendorDir.'/symfony/http-kernel',
        'Symfony\\Component\\Routing\\' => $projectVendorDir.'/symfony/routing',
    ];

    spl_autoload_register(static function (string $class) use ($prefixes): void {
        foreach ($prefixes as $prefix => $dir) {
            if (!str_starts_with($class, $prefix)) {
                continue;
            }

            $file = $dir.'/'.str_replace('\\', '/', substr($class, \strlen($prefix))).'.php';
            if (file_exists($file)) {
                require $file;
            }

            return;
        }
    });
}
