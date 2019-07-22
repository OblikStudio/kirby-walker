<?php

require_once __DIR__ . './../vendor/autoload.php';

require_once __DIR__ . '/../../../../kirby/bootstrap.php';
require_once __DIR__ . './extensions/ImportTestCase.php';

return new Kirby([
    'roots' => [
        'config' => __DIR__ . '/kirby/config',
        'blueprints' => __DIR__ . '/kirby/blueprints',
        'content' => __DIR__ . '/kirby/content',
        'languages' => __DIR__ . '/kirby/languages'
    ]
]);
