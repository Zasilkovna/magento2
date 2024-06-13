<?php

use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../autoload.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

spl_autoload_register(
    static function (string $class) use ($objectManager): void {
        $factorySuffix = 'Factory';
        $expectedFactorySuffixPosition = strlen($class) - strlen($factorySuffix);
        if (strpos($class, $factorySuffix) === $expectedFactorySuffixPosition) {
            $objectManager->get($class);
        }
    }
);
