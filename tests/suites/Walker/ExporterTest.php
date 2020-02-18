<?php

namespace Oblik\Outsource\Walker;

use Kirby\Cms\App;
use Kirby\Cms\Page;
use Kirby\Data\Yaml;
use Oblik\Variables\Manager;
use PHPUnit\Framework\TestCase;

class TranslateExporter extends Exporter
{
    public function fieldPredicate($field, $blueprint, $input)
    {
        $translate = $blueprint['translate'] ?? true;
        $predicate = parent::fieldPredicate($field, $blueprint, $input);

        return $translate && $predicate;
    }
}

final class ExporterTest extends TestCase
{
    public function testArtificialFields()
    {
        $exporter = new Exporter([
            'blueprint' => [
                'title' => [
                    'type' => 'text'
                ]
            ]
        ]);

        $data = new Page([
            'slug' => 'test',
            'translations' => [
                [
                    'code' => 'en',
                    'content' => [
                        'title' => 'test'
                    ]
                ]
            ]
        ]);

        $exporter->export($data, 'en');
        $data = $exporter->data();

        $this->assertArrayHasKey('title', $data['pages']['test']);
    }

    public function testSerialization()
    {
        $exporter = new Exporter();
        $data = $exporter->exportModel(new Page([
            'slug' => 'test',
            'content' => [
                'text' => '(link: https://example.com)'
            ],
            'blueprint' => [
                'fields' => [
                    'text' => [
                        'type' => 'text',
                        'outsource' => [
                            'serialize' => [
                                'kirbytags' => true
                            ]
                        ]
                    ]
                ]
            ]
        ]));

        $this->assertEquals('<kirby link="https://example.com"/>', $data['text']);
    }

    public function testIgnoredField()
    {
        $exporter = new Exporter();
        $data = $exporter->exportModel(new Page([
            'slug' => 'test',
            'content' => [
                'text' => 'foo',
                'ignoredText' => 'bar'
            ],
            'blueprint' => [
                'fields' => [
                    'text' => [
                        'type' => 'text'
                    ],
                    'ignoredText' => [
                        'type' => 'text',
                        'outsource' => [
                            'ignore' => true
                        ]
                    ]
                ]
            ]
        ]));

        $this->assertArrayHasKey('text', $data);
        $this->assertArrayNotHasKey('ignoredText', $data);
    }

    public function testFieldPredicate()
    {
        $exporter = new TranslateExporter();
        $data = $exporter->exportModel(new Page([
            'slug' => 'test',
            'content' => [
                'text' => 'foo',
                'notTranslatedText' => 'bar'
            ],
            'blueprint' => [
                'fields' => [
                    'text' => [
                        'type' => 'text'
                    ],
                    'notTranslatedText' => [
                        'type' => 'text',
                        'translate' => false
                    ]
                ]
            ]
        ]));

        $this->assertArrayHasKey('text', $data);
        $this->assertArrayNotHasKey('notTranslatedText', $data);
    }

    public function testEmptyStructure()
    {
        $exporter = new Exporter();
        $data = $exporter->exportModel(new Page([
            'slug' => 'test',
            'content' => [
                'items' => Yaml::encode([
                    [
                        'ignoredText' => 'foo'
                    ]
                ]),
            ],
            'blueprint' => [
                'fields' => [
                    'items' => [
                        'type' => 'structure',
                        'fields' => [
                            'ignoredText' => [
                                'type' => 'text',
                                'outsource' => [
                                    'ignore' => true
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]));

        $this->assertEquals(null, $data);
    }

    public function testFiltering()
    {
        $exporter = new Exporter();
        $data = $exporter->exportModel(new Page([
            'slug' => 'test',
            'content' => [
                'test' => Yaml::encode([
                    'foo' => 'a',
                    'bar' => 'b'
                ])
            ],
            'blueprint' => [
                'fields' => [
                    'test' => [
                        'type' => 'text',
                        'outsource' => [
                            'serialize' => [
                                'yaml' => true
                            ],
                            'export' => [
                                'filter' => [
                                    'keys' => ['foo']
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]));

        $this->assertArrayHasKey('foo', $data['test']);
        $this->assertArrayNotHasKey('bar', $data['test']);
    }

    public function testFalsyValues()
    {
        $exporter = new Exporter();
        $data = $exporter->exportModel(new Page([
            'slug' => 'test',
            'content' => [
                'testNum' => 0,
                'testBool' => false,
                'testString' => '',
                'testNull' => null
            ],
            'blueprint' => [
                'fields' => [
                    'testNum' => ['type' => 'text'],
                    'testBool' => ['type' => 'text'],
                    'testString' => ['type' => 'text'],
                    'testNull' => ['type' => 'text']
                ]
            ]
        ]));

        $this->assertEquals(0, $data['testnum']);
        $this->assertEquals(false, $data['testbool']);
        $this->assertEquals('', $data['teststring']);
        $this->assertArrayNotHasKey('testnull', $data);
    }

    public function testExportsFiles()
    {
        $exporter = new Exporter();
        $exporter->exportModel(new Page([
            'slug'  => 'test',
            'files' => [
                [
                    'filename' => 'test.jpg',
                    'content' => [
                        'foo' => 'test'
                    ],
                    'blueprint' => [
                        'fields' => [
                            'foo' => [
                                'type' => 'text'
                            ]
                        ]
                    ]
                ]
            ]
        ]));

        $data = $exporter->data();
        $this->assertEquals('test', $data['files']['test/test.jpg']['foo']);
    }

    public function testSyncStructureIds()
    {
        $exporter = new Exporter();
        $data = $exporter->exportModel(new Page([
            'slug' => 'test',
            'content' => [
                'items' => Yaml::encode([
                    [
                        'id' => 'asdf',
                        'foo' => 'one'
                    ],
                    [
                        'foo' => 'two'
                    ]
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
        ]));

        $this->assertEquals('asdf', $data['items'][0]['id']);
        $this->assertEquals(1, $data['items'][1]['id']);
    }

    public function testVariablesExported()
    {
        new App([
            'roots' => [
                'languages' => __DIR__ . '/fixtures/ExporterTest'
            ],
            'languages' => [
                ['code' => 'en']
            ]
        ]);

        Manager::loadTranslations();

        $exporter = new Exporter();
        $data = $exporter->exportVariables('en');

        $this->assertEquals('test', $data['foo']);
    }
}
