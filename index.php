<?php

namespace Oblik\Outsource;

use Kirby;

const BLUEPRINT_KEY = 'outsource';
const BP_BLUEPRINT = 'blueprint';
const BP_FIELDS = 'fields';
const BP_IGNORE = 'ignore';

Kirby::plugin('oblik/outsource', [
    'hooks' => include __DIR__ . '/hooks.php',
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
                        $input = array_column($input, null, 'id');

                        foreach ($data as &$block) {
                            $id = $block['id'] ?? null;
                            $inputBlock = $input[$id] ?? null;

                            if ($inputBlock) {
                                $block = array_replace_recursive($block, $inputBlock);
                            }
                        }

                        return $data;
                    }
                ]
            ]
        ],
        'variables' => null
    ]
]);
