<?php
namespace KirbyExporter;

include_once 'src/Exporter.php';
include_once 'src/Importer.php';
include_once 'src/KirbytagParser.php';

\Kirby::plugin('oblik/exporter', [
  'api' => [
    'routes' => [
      [
        'pattern' => 'export',
        'action' => function () {
          $exporter = new Exporter('en', ['page' => 'series']);
          return $exporter->export();
        }
      ],
      [
        'pattern' => 'import',
        'method' => 'GET',
        'action' => function () {
          // $postData = file_get_contents('php://input');
          $postData = file_get_contents(__DIR__ . DS . 'import.json');
          $input = json_decode($postData, true);
          
          $importer = new Importer('bg');
          return $importer->import($input);
        }
      ]
    ]
  ]
]);
