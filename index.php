<?php

include_once 'src/Exporter.php';

Kirby::plugin('oblik/exporter', [
  'api' => [
    'routes' => [
      [
        'pattern' => 'export',
        'action' => function () {
          $exporter = new KirbyExporter\Exporter();
          return $exporter->export();
        }
      ]
    ]
  ]
]);
