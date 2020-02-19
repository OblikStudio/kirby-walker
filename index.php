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

            /**
             * Support for Entity Field.
             * @see https://github.com/OblikStudio/kirby-entity-field
             */
            'entity' => [
                'walk' => function ($walker, $field, $settings, $input) {
                    return $walker->walk($field->toEntity(), $settings['fields'], $input);
                }
            ],

            /**
             * Support for Kirby Editor.
             * @see https://github.com/getkirby/editor
             */
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
