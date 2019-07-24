<?php

namespace KirbyOutsource;

const BLUEPRINT_KEY = 'outsource';
const BLUEPRINT_IGNORE_KEY = 'ignore';

include_once 'src/Exporter.php';
include_once 'src/Importer.php';
include_once 'src/KirbytagSerializer.php';
include_once 'src/Variables.php';
include_once 'src/Formatter.php';
include_once 'src/Walker.php';

\Kirby::plugin('oblik/outsource', [
    'tags' => [
        'testbar' => [
            'attr' => [
                'type'
            ],
            'html' => function ($tag) {
                return snippet('test', [], true);
            }
        ]
    ],
    'options' => [
        'variables' => true,
        'blueprints' => [
            'title' => [
                'type' => 'text'
            ]
        ],
        'fields' => [
            'files' => [
                BLUEPRINT_KEY => [
                    'ignore' => true
                ]
            ],
            'pages' => [
                BLUEPRINT_KEY => [
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
                    $pagesQuery = $_GET['page'] ?? null;
                    $exportLanguage = null;

                    if ($kirby->multilang()) {
                        $exportLanguage = $kirby->defaultLanguage()->code();
                    }

                    $exporter = new Exporter([
                        'language' => $exportLanguage,
                        'variables' => $_GET['variables'] ?? option('oblik.outsource.variables'),
                        'blueprints' => option('oblik.outsource.blueprints'),
                        'fields' => option('oblik.outsource.fields')
                    ]);

                    $models = new \Kirby\Cms\Pages();
                    $models->append(site());
                    $models->add(site()->index()->filter(function ($page) use ($pagesQuery) {
                        return (!$pagesQuery || strpos($page->id(), $pagesQuery) !== false);
                    }));

                    return $exporter->export($models);
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
