<?php

namespace Oblik\Outsource;

use Kirby;

/**
 * The property key used in blueprints for specifying plugin settings.
 */
const BLUEPRINT_KEY = 'outsource';

/**
 * Blueprint setting for the walked translation.
 */
const BP_LANGUAGE = 'language';

/**
 * Blueprint setting for custom fields artificially added to each Model
 * blueprint.
 */
const BP_BLUEPRINT = 'blueprint';

/**
 * Blueprint setting for supplying options to various field types
 */
const BP_FIELDS = 'fields';

/**
 * Blueprint setting that indicates the current field should be ignored
 */
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
        ]
    ]
]);
