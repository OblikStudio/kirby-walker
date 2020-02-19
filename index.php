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
            /**
             * @see https://getkirby.com/docs/reference/panel/fields/tags
             */
            'tags' => [
                'serialize' => [
                    'tags' => true
                ]
            ],

            /**
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
            ],

            /**
             * @see https://github.com/OblikStudio/kirby-link-field
             */
            'link' => [
                'serialize' => [
                    'yaml' => true
                ]
            ],

            /**
             * @see https://github.com/OblikStudio/kirby-entity-field
             */
            'entity' => [
                'walk' => function ($walker, $field, $settings, $input) {
                    return $walker->walk($field->toEntity(), $settings['fields'], $input);
                }
            ],

            /**
             * @see https://github.com/OblikStudio/kirby-json
             */
            'json' => [
                'serialize' => [
                    'json' => true
                ]
            ]
        ]
    ]
]);
