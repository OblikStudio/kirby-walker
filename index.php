<?php

namespace Oblik\Outsource;

use Kirby;

const BLUEPRINT_KEY = 'outsource';
const BP_BLUEPRINT = 'blueprint';
const BP_FIELDS = 'fields';
const BP_IGNORE = 'ignore';

require_once 'vendor/autoload.php';

Kirby::plugin('oblik/outsource', [
    'hooks' => include 'hooks.php',
    'options' => [
        BP_BLUEPRINT => [
            'title' => [
                'type' => 'text'
            ]
        ],
        BP_FIELDS => [
            'text' => [
                'serialize' => [
                    'kirbytags' => true
                ]
            ],
            'textarea' => [
                'serialize' => [
                    'markdown' => true,
                    'kirbytags' => true
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
                        return Diff::processKeyedArray($data, $input, 'array_replace_recursive');
                    }
                ]
            ]
        ],
        'variables' => null
    ]
]);
