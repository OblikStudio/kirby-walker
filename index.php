<?php

namespace Oblik\Kirby\Outsource;

use Exception;
use Kirby;
use Kirby\Cms\Pages;

const BLUEPRINT_KEY = 'outsource';
const BLUEPRINT_IGNORE_KEY = 'ignore';

include_once 'src/Serializer/KirbyTags.php';
include_once 'src/Serializer/Markdown.php';
include_once 'src/Serializer/Yaml.php';
include_once 'src/Formatter.php';
include_once 'src/Walker.php';
include_once 'src/Exporter.php';
include_once 'src/Importer.php';
include_once 'src/Variables.php';

Kirby::plugin('oblik/outsource', [
    'options' => [
        'variables' => true,
        'blueprints' => [
            'title' => [
                'type' => 'text'
            ]
        ],
        'fields' => [
            'files' => [
                'ignore' => true
            ],
            'pages' => [
                'ignore' => true
            ],
            'textarea' => [
                'serialize' => [
                    'markdown' => true,
                    'kirbytags' => true
                ]
            ],
            'text' => [
                'serialize' => [
                    'kirbytags' => true
                ]
            ],
            'link' => [
                'serialize' => [
                    'yaml' => true
                ]
            ]
        ]
    ],
    'api' => [
        'routes' => [
            [
                'pattern' => 'export',
                'method' => 'GET',
                'auth' => false,
                'action' => function () use ($kirby) {
                    $pagesQuery = $_GET['page'] ?? null;
                    $exportLanguage = null;

                    if ($kirby->multilang()) {
                        $exportLanguage = $kirby->defaultLanguage()->code();
                    }

                    $formatter = new Formatter();
                    $exporter = new Exporter($formatter, [
                        'language' => $exportLanguage,
                        'blueprints' => option('oblik.outsource.blueprints'),
                        'fields' => option('oblik.outsource.fields')
                    ]);

                    $models = new Pages();
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
                        throw new Exception('Missing language', 400);
                    }

                    if (empty($input['content'])) {
                        throw new Exception('Missing content', 400);
                    }

                    $importer = new Importer($input['language']);
                    return $importer->import($input['content']);
                }
            ]
        ]
    ]
]);
