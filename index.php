<?php

namespace Oblik\Outsource;

use Exception;
use Kirby;
use Kirby\Cms\Pages;

const BLUEPRINT_KEY = 'outsource';
const BP_BLUEPRINT = 'blueprint';
const BP_FIELDS = 'fields';
const BP_IGNORE = 'ignore';

Kirby::plugin('oblik/outsource', [
    'options' => [
        'variables' => Variables::class,
        BP_BLUEPRINT => [
            'title' => [
                'type' => 'text'
            ]
        ],
        BP_FIELDS => [
            'files' => [
                BP_IGNORE => true
            ],
            'pages' => [
                BP_IGNORE => true
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
                ],
                'export' => [
                    'filter' => [
                        'keys' => ['text']
                    ]
                ]
            ],
            'tags' => [
                'serialize' => [
                    'tags' => true
                ]
            ],
            'editor' => [
                'serialize' => [
                    'json' => true
                ],
                'export' => [
                    'filter' => [
                        'keys' => ['id', 'content']
                    ]
                ],
                'import' => [
                    'merge' => function ($data, $input) {
                        return merge_by_key($data, $input, 'array_replace_recursive');
                    }
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

                    $exporter = new Exporter([
                        'language' => $exportLanguage,
                        'variables' => option(('oblik.outsource.variables')),
                        BP_BLUEPRINT => option('oblik.outsource.' . BP_BLUEPRINT),
                        BP_FIELDS => option('oblik.outsource.' . BP_FIELDS)
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
