<?php
declare(strict_types=1);

/**
 * Minimal PSR-4 autoloader for the module's own namespace — deliberately NOT the module's root
 * composer.json autoload, because that declares magento/module-* and endroid/qr-code
 * dependencies that require repo.magento.com credentials / a real vendor install and aren't
 * needed to unit-test plain-PHP classes. The classes under test (Model\QrCode\SizeValidator,
 * Model\QrCode\FilenameSanitizer) have zero Magento framework and zero third-party QR library
 * dependency, so unlike module-smtp-config's/module-admin-audit-log's test bootstraps, no stub
 * classes/functions are needed here (mirrors module-email-otp-two-factor-auth's
 * tests/bootstrap.php, which is in the exact same position).
 */
spl_autoload_register(static function (string $class): void {
    $prefix  = 'TVTCommerce\\QrCodeGenerator\\';
    $baseDir = dirname(__DIR__) . '/';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file     = $baseDir . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

require __DIR__ . '/vendor/autoload.php';
