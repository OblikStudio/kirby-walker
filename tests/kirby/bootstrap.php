<?php

require_once __DIR__ . '/../../../../../kirby/bootstrap.php';

return new Kirby([
  'roots' => [
    'content' => __DIR__ . DS . 'content',
    'blueprints' => __DIR__ . DS . 'blueprints'
  ]
]);
