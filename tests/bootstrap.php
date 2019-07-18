<?php

require_once __DIR__ . '/../../../../kirby/bootstrap.php';

return new Kirby([
    'roots' => [
        'config' => __DIR__ . '/kirby/config',
        'blueprints' => __DIR__ . '/kirby/blueprints',
        'content' => __DIR__ . '/kirby/content',
        'languages' => __DIR__ . '/kirby/languages'
    ]
]);
