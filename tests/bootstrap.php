<?php

use Kirby\Toolkit\F;
use Oblik\Outsource\Variables;

function testWalkerSettings($settings = [])
{
    return array_replace_recursive([
        'language' => 'en',
        'variables' => Variables::class,
        'blueprint' => option('oblik.outsource.blueprint'),
        'fields' => array_replace_recursive(
            option('oblik.outsource.fields'), [
                'link' => [
                    'serialize' => [
                        'yaml' => true
                    ],
                    'export' => [
                        'filter' => [
                            'keys' => ['text']
                        ]
                    ]
                ]
            ]
        )
    ], $settings);
}

return [
    'beforeLoad' => function () {
        // Must be in `beforeLoad` because the variables Manager reads the file
        // on Kirby init.
        copy(__DIR__ . '/fixtures/bg.yml', __DIR__ . '/roots/languages/bg.yml');
    },
    'beforeInit' => function () {
        foreach (glob(__DIR__ . '/roots/content/*/*.bg.txt') as $file) {
            F::remove($file);
        }

        F::copy(__DIR__ . '/fixtures/struct.en.txt', __DIR__ . '/roots/content/1_struct/struct.en.txt', true);
        F::copy(__DIR__ . '/fixtures/struct.bg.txt', __DIR__ . '/roots/content/1_struct/struct.bg.txt', true);
    }
];
