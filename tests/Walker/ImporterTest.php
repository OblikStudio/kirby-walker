<?php

namespace Oblik\Outsource\Walker;

use Oblik\Outsource\Util\Diff;
use Oblik\Outsource\TestCase;

use Kirby\Cms\App;
use Kirby\Cms\Page;
use Kirby\Data\Json;
use Kirby\Data\Yaml;
use Kirby\Toolkit\F;
use Oblik\Variables\Manager;

final class ImporterTest extends TestCase
{
    public function testReportsChanges()
    {
        $importer = new Importer();
        $data = $importer->importModel(new Page([
            'slug' => 'test',
            'content' => [
                'foo' => 'a',
                'bar' => 'a'
            ],
            'blueprint' => [
                'fields' => [
                    'foo' => ['type' => 'text'],
                    'bar' => ['type' => 'text'],
                    'baz' => ['type' => 'text']
                ]
            ]
        ]), [
            'bar' => 'b',
            'baz' => 'b'
        ]);

        $this->assertArrayNotHasKey('foo', $data);
        $this->assertEquals([
            'bar' => [
                '$old' => 'a',
                '$new' => 'b'
            ],
            'baz' => [
                '$old' => null,
                '$new' => 'b'
            ]
        ], $data);
    }

    public function testValueSerialization()
    {
        $importer = new Importer();
        $data = $importer->importModel(new Page([
            'slug' => 'test',
            'content' => [
                'tagfield' => 'foo, bar'
            ],
            'blueprint' => [
                'fields' => [
                    'tagfield' => [
                        'type' => 'tags',
                        'outsource' => [
                            'serialize' => [
                                'tags' => true
                            ]
                        ]
                    ]
                ]
            ]
        ]), [
            'tagfield' => ['baz']
        ]);

        $this->assertEquals('baz, bar', $data['tagfield']['$new']);
    }

    public function testStructureEntriesMerge()
    {
        $importer = new Importer();
        $data = $importer->importModel(new Page([
            'slug' => 'test',
            'content' => [
                'items' => Yaml::encode([
                    ['foo' => 'a', 'bar' => 'a'],
                    ['foo' => 'a']
                ])
            ],
            'blueprint' => [
                'fields' => [
                    'items' => [
                        'type' => 'structure',
                        'fields' => [
                            'foo' => ['type' => 'text'],
                            'bar' => ['type' => 'text'],
                            'baz' => ['type' => 'text']
                        ]
                    ]
                ]
            ]
        ]), [
            'items' => [
                ['bar' => 'b', 'baz' => 'b']
            ]
        ]);

        $this->assertEquals(Yaml::encode([
            ['foo' => 'a', 'bar' => 'b', 'baz' => 'b'],
            ['foo' => 'a', 'bar' => '', 'baz' => '']
        ]), $data['items']['$new']);
    }

    public function testStructureImportSync()
    {
        $importer = new Importer();
        $data = $importer->importModel(new Page([
            'slug' => 'test',
            'content' => [
                'items' => Yaml::encode([
                    ['id' => 'one', 'foo' => 'a'],
                    ['id' => 'two', 'foo' => 'a']
                ])
            ],
            'blueprint' => [
                'fields' => [
                    'items' => [
                        'type' => 'structure',
                        'fields' => [
                            'foo' => [
                                'type' => 'text'
                            ]
                        ],
                        'outsource' => [
                            'sync' => true
                        ]
                    ]
                ]
            ]
        ]), [
            'items' => [
                ['id' => 'two', 'foo' => 'b'],
                ['id' => 'three', 'foo' => 'b']
            ]
        ]);

        // The field order is changed because `id` is not in the blueprint and
        // Kirby saves such structure fields last.
        $this->assertEquals(Yaml::encode([
            ['foo' => 'a', 'id' => 'one'],
            ['foo' => 'b', 'id' => 'two']
        ]), $data['items']['$new']);
    }

    public function testStructureNonSyncIds()
    {
        $importer = new Importer();
        $data = $importer->importModel(new Page([
            'slug' => 'test',
            'content' => [
                'items' => Yaml::encode([
                    ['id' => 'a', 'foo' => 'a'],
                    ['foo' => 'a']
                ])
            ],
            'blueprint' => [
                'fields' => [
                    'items' => [
                        'type' => 'structure',
                        'fields' => [
                            'foo' => ['type' => 'text']
                        ]
                    ]
                ]
            ]
        ]), [
            'items' => [
                ['foo' => 'b'],
                ['foo' => 'b']
            ]
        ]);

        // The first entry should not be merged because it originally has an
        // `id` value of `a`. If an entry has an `id`, Kirby will use _it_ as a
        // key instead of its actual numeric key. This means the input data is
        // expected to have an entry with id `a`, which it doesn't.
        $this->assertEquals(Yaml::encode([
            ['foo' => 'a'],
            ['foo' => 'b']
        ]), $data['items']['$new']);
    }

    public function testCustomMergers()
    {
        $importer = new Importer([
            'fields' => [
                'editor' => [
                    'serialize' => [
                        'json' => true
                    ],
                    'import' => [
                        'merge' => function ($data, $input) {
                            return Diff::processKeyedArray($data, $input, 'array_replace_recursive');
                        }
                    ]
                ]
            ]
        ]);

        $page = new Page([
            'slug' => 'test',
            'content' => [
                'text' => Json::encode([
                    [
                        'attrs' => [],
                        'content' => 'Hello!',
                        'id' => '_3i2q91mx6',
                        'type' => 'h1'
                    ],
                    [
                        'attrs' => [],
                        'content' => 'This is the Kirby editor!',
                        'id' => '_ml7ufpb8n',
                        'type' => 'paragraph'
                    ]
                ])
            ],
            'blueprint' => [
                'fields' => [
                    'text' => [
                        'type' => 'editor'
                    ]
                ]
            ]
        ]);

        $import = [
            'text' => [
                [
                    'id' => '_ml7ufpb8n',
                    'content' => 'Imported content'
                ],
                [
                    'id' => '_3i2q91mx6',
                    'content' => 'Imported heading'
                ]
            ]
        ];

        $expected = [
            [
                'attrs' => [],
                'content' => 'Imported heading',
                'id' => '_3i2q91mx6',
                'type' => 'h1'
            ],
            [
                'attrs' => [],
                'content' => 'Imported content',
                'id' => '_ml7ufpb8n',
                'type' => 'paragraph'
            ]
        ];

        $data = $importer->importModel($page, $import);
        $this->assertEquals($expected, Json::decode($data['text']['$new']));
    }

    public function testImportsVariables()
    {
        F::write(
            $this->fixtures . '/site/languages/en.yml',
            'foo: a'
        );

        $this->app = new App([
            'roots' => [
                'index' => $this->fixtures
            ],
            'languages' => [
                ['code' => 'en']
            ]
        ]);

        Manager::loadTranslations();

        $importer = new Importer();
        $data = $importer->importVariables([
            'foo' => 'b',
            'bar' => 'b'
        ], 'en');

        $this->assertEquals([
            'foo' => [
                '$old' => 'a',
                '$new' => 'b'
            ],
            'bar' => [
                '$old' => null,
                '$new' => 'b'
            ]
        ], $data);
    }
}
