<?php
spl_autoload_register(function ($class) {
    $prefixes = [
        'Core\\' => __DIR__ . '/../Core/',
        'Auth\\' => __DIR__ . '/../src/Auth/',        // ⚠️ también está en src
        'Controllers\\' => __DIR__ . '/../src/Controllers/', // ⚠️ también está en src
        'Src\\' => __DIR__ . '/../src/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }

        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});