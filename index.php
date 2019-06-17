<?php
namespace KirbyExporter;

include_once 'src/Exporter.php';
include_once 'src/Importer.php';
include_once 'src/KirbytagParser.php';
include_once 'src/Variables.php';

\Kirby::plugin('oblik/exporter', [
  'api' => [
    'routes' => [
      [
        'pattern' => 'export',
        'method' => 'GET',
        'action' => function () use ($kirby) {
          $exporter = new Exporter($kirby->defaultLanguage()->code(), [
            'page' => $_GET['page'] ?? null,
            'variables' => ($_GET['variables'] ?? 'true') != 'false'
          ]);

          return $exporter->export();
        }
      ],
      [
        'pattern' => 'import',
        'method' => 'POST',
        'action' => function () {
          $postData = file_get_contents('php://input');
          // $postData = file_get_contents(__DIR__ . DS . 'import.json');
          $input = json_decode($postData, true);

          if (empty($input['language'])) {
            throw new \Exception('Missing language', 400);
          }

          if (empty($input['content'])) {
            throw new \Exception('Missing content', 400);
          }

          $importer = new Importer($input['language']);
          return $importer->import($input['content']);
        }
      ]
    ]
  ]
]);
