<?php

namespace Oblik\Outsource;

use Oblik\Outsource\Util\Diff;
use Oblik\Outsource\Util\Updater;

use Kirby\Cms\App;

const KEY = 'outsource';

require_once 'vendor/autoload.php';

App::plugin('oblik/outsource', [
    'hooks' => Updater::getHooks(),
    'options' => [
        'blueprint' => [
            'title' => [
                'type' => 'text'
            ]
        ],
        'fields' => [
            'text' => [
                'serialize' => [
                    'kirbytags' => true
                ]
            ],
            'textarea' => [
                'serialize' => [
                    'kirbytags' => true,
                    'markdown' => true
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
                        return Diff::processKeyedArray(
                            $data ?? [],
                            $input ?? [],
                            'array_replace_recursive'
                        );
                    }
                ]
            ]
        ]
    ]
]);
