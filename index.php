<?php
namespace KirbyExporter;

include_once 'src/Exporter.php';
include_once 'src/Importer.php';
include_once 'src/KirbytagParser.php';
include_once 'src/Variables.php';

\Kirby::plugin('oblik/exporter', [
  'options' => [
    'page' => null,
    'variables' => true,
    'blueprints' => [
      'title' => [
        'type' => 'text'
      ]
    ],
    'fields' => [
      'files' => [
        'exporter' => [
          'ignore' => true
        ]
      ],
      'pages' => [
        'exporter' => [
          'ignore' => true
        ]
      ]
    ]
  ],
  'api' => [
    'routes' => [
      [
        'pattern' => 'export',
        'method' => 'GET',
        'action' => function () use ($kirby) {
          $exportLanguage = null;

          if ($kirby->multilang()) {
            $exportLanguage = $kirby->defaultLanguage()->code();
          }

          $exporter = new Exporter([
            'language' => $exportLanguage,
            'page' => $_GET['page'] ?? option('oblik.exporter.page'),
            'variables' => $_GET['variables'] ?? option('oblik.exporter.variables'),
            'blueprints' => option('oblik.exporter.blueprints'),
            'fields' => option('oblik.exporter.fields'),
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
