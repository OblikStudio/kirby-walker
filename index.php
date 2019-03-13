<?php
namespace KirbyExporter;

include_once 'src/Exporter.php';
include_once 'src/KirbytagParser.php';

\Kirby::plugin('oblik/exporter', [
  'api' => [
    'routes' => [
      [
        'pattern' => 'export',
        'action' => function () {
          $exporter = new Exporter();
          return $exporter->export();
        }
      ]
    ]
  ]
]);
