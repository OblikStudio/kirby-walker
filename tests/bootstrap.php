<?php

use Kirby\Toolkit\F;

return [
    'beforeInit' => function () {
        F::remove(__DIR__ . '/roots/languages/bg.yml');
        foreach (glob(__DIR__ . '/roots/content/*/*.bg.txt') as $file) {
            F::remove($file);
        }

        F::copy(__DIR__ . '/fixtures/struct.en.txt', __DIR__ . '/roots/content/1_struct/struct.en.txt', true);
        F::copy(__DIR__ . '/fixtures/struct.bg.txt', __DIR__ . '/roots/content/1_struct/struct.bg.txt', true);
    }
];
