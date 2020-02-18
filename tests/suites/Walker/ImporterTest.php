<?php

namespace Oblik\Outsource\Walker;

use Kirby\Cms\App;
use Kirby\Cms\Dir;
use Kirby\Cms\Page;
use Kirby\Data\Yaml;
use Kirby\Toolkit\F;
use Oblik\Variables\Manager;
use PHPUnit\Framework\TestCase;

final class ImporterTest extends TestCase
{
    protected $fixtures = __DIR__ . '/fixtures/ImporterTest';

    public function setUp(): void
    {
        $app = new App([
            'roots' => [
                'index' => $this->fixtures
            ]
        ]);

        $app->impersonate('kirby');

        Dir::make($this->fixtures);
    }

    public function tearDown(): void
    {
        Dir::remove($this->fixtures);
    }

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

    public function testImportsVariables()
    {
        F::write(
            $this->fixtures . '/site/languages/en.yml',
            'foo: a'
        );

        new App([
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
