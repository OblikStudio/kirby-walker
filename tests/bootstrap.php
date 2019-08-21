<?php

use Kirby\Toolkit\F;

return [
    'beforeInit' => function () {
        foreach (glob(__DIR__ . '/roots/content/*/*.bg.txt') as $file) {
            F::remove($file);
        }
    }
];
