<?php

namespace Oblik\Walker\Walker;

use Kirby\Cms\App;
use Kirby\Cms\Page;
use Kirby\Data\Json;
use Kirby\Data\Yaml;
use Oblik\Walker\TestCase;

final class ImporterTest extends TestCase
{
	public function testSingleLanguage()
	{
		$page = new Page([
			'slug' => 'test',
			'content' => [
				'text' => 'original'
			],
			'blueprint' => [
				'fields' => [
					'text' => [
						'type' => 'text'
					]
				]
			]
		]);

		$content = ['text' => 'imported'];
		$result = (new Importer())->walk($page, [
			'input' => $content
		]);

		$this->assertEquals($content, $result);
	}

	public function testMultilang()
	{
		new App([
			'languages' => [
				[
					'code'    => 'en',
					'default' => true,
				],
				[
					'code'    => 'bg',
				]
			]
		]);

		$page = new Page([
			'slug' => 'test',
			'content' => [
				'text' => 'original'
			],
			'blueprint' => [
				'fields' => [
					'text' => [
						'type' => 'text'
					]
				]
			]
		]);

		$content = ['text' => 'imported'];
		$result = (new Importer())->walk($page, [
			'input' => $content
		]);

		$this->assertEquals($content, $result);
	}

	public function testTranslateFalse()
	{
		$page = new Page([
			'slug' => 'test',
			'content' => [
				'text' => 'original'
			],
			'blueprint' => [
				'fields' => [
					'text' => [
						'type' => 'text',
						'translate' => false
					]
				]
			]
		]);

		$result = (new Importer())->walk($page, [
			'input' => [
				'text' => 'imported'
			]
		]);

		$expected = [
			'text' => 'original'
		];

		$this->assertEquals($expected, $result);
	}

	public function testStructure()
	{
		$page = new Page([
			'slug' => 'test',
			'content' => [
				'items' => Yaml::encode([
					[
						'text' => 'original 1'
					],
					[
						'text' => 'original 2'
					]
				])
			],
			'blueprint' => [
				'fields' => [
					'items' => [
						'type' => 'structure',
						'fields' => [
							'text' => [
								'type' => 'text'
							]
						]
					]
				]
			]
		]);

		$import = [
			'items' => [
				null,
				[
					'text' => 'imported 2'
				]
			]
		];

		$expected = [
			'items' => [
				[
					'text' => 'original 1'
				],
				[
					'text' => 'imported 2'
				]
			]
		];

		$result = (new Importer())->walk($page, [
			'input' => $import
		]);

		$this->assertEquals($expected, $result);
	}

	public function testStructureWithIds()
	{
		$page = new Page([
			'slug' => 'test',
			'content' => [
				'items' => Yaml::encode([
					[
						'id' => 'a',
						'text' => 'original 1'
					],
					[
						'text' => 'original 2'
					]
				])
			],
			'blueprint' => [
				'fields' => [
					'items' => [
						'type' => 'structure',
						'fields' => [
							'id' => [
								'type' => 'text'
							],
							'text' => [
								'type' => 'text'
							]
						]
					]
				]
			]
		]);

		$import = [
			'items' => [
				null,
				[
					'id' => 'a',
					'text' => 'imported 1'
				]
			]
		];

		$expected = [
			'items' => [
				[
					'id' => 'a',
					'text' => 'imported 1'
				],
				[
					'text' => 'original 2'
				]
			]
		];

		$result = (new Importer())->walk($page, [
			'input' => $import
		]);

		$this->assertEquals($expected, $result);
	}

	public function testBlocks()
	{
		$page = new Page([
			'slug' => 'test',
			'content' => [
				'text' => Json::encode([
					[
						'content' => [
							'text' => '<p>original 1</p>'
						],
						'id' => 'c14c1bff-6a9d-4179-9aa9-b9df25dca891',
						'isHidden' => false,
						'type' => 'text'
					],
					[
						'content' => [
							'text' => '<p>original 2</p>'
						],
						'id' => '9da43326-6118-4f4a-b4cd-23ca5bd5c35f',
						'isHidden' => false,
						'type' => 'text'
					],
					[
						'content' => [
							'text' => '<p>original 3</p>'
						],
						'id' => 'fdd25d51-8ac5-42aa-8476-b63dac8c577b',
						'isHidden' => false,
						'type' => 'text'
					]
				])
			],
			'blueprint' => [
				'fields' => [
					'text' => [
						'type' => 'blocks',
						'fieldsets' => [
							'text'
						]
					]
				]
			]
		]);

		$import = [
			'text' => [
				[
					'content' => [
						'text' => '<p>imported 3</p>'
					],
					'id' => 'fdd25d51-8ac5-42aa-8476-b63dac8c577b'
				],
				[
					'content' => [
						'text' => '<p>outdated block</p>'
					],
					'id' => 'bb8ca9fd-d1ee-4567-a130-4946afdee9a8'
				],
				[
					'content' => [
						'text' => '<p>imported 1</p>'
					],
					'id' => 'c14c1bff-6a9d-4179-9aa9-b9df25dca891'
				]
			]
		];

		$expected = [
			'text' => [
				[
					'content' => [
						'text' => '<p>imported 1</p>'
					],
					'id' => 'c14c1bff-6a9d-4179-9aa9-b9df25dca891',
					'isHidden' => false,
					'type' => 'text'
				],
				[
					'content' => [
						'text' => '<p>original 2</p>'
					],
					'id' => '9da43326-6118-4f4a-b4cd-23ca5bd5c35f',
					'isHidden' => false,
					'type' => 'text'
				],
				[
					'content' => [
						'text' => '<p>imported 3</p>'
					],
					'id' => 'fdd25d51-8ac5-42aa-8476-b63dac8c577b',
					'isHidden' => false,
					'type' => 'text'
				]
			]
		];

		$result = (new Importer())->walk($page, [
			'input' => $import
		]);

		$this->assertEquals($expected, $result);
	}

	public function testLayout()
	{
		$page = new Page([
			'slug' => 'test',
			'content' => [
				'text' => Json::encode([
					[
						'attrs' => [
							'anchor' => 'hello-world'
						],
						'columns' => [
							[
								'blocks' => [
									[
										'content' => [
											'level' => 'h2',
											'text' => 'Hello World!'
										],
										'id' => '65841ca6-53db-4079-a506-87c493f7808d',
										'isHidden' => false,
										'type' => 'heading'
									],
									[
										'content' => [
											'text' => '<p>Lorem Ipsum!</p>'
										],
										'id' => 'f21cb883-45d5-42ea-8292-ea6126563ad9',
										'isHidden' => false,
										'type' => 'text'
									]
								],
								'id' => '806a3187-e111-49c4-84e4-f6c37dc47600',
								'width' => '1/1'
							]
						],
						'id' => 'f7799dc1-0a65-461e-8a23-f59bdcf11ec0'
					],
					[
						'attrs' => [
							'anchor' => ''
						],
						'columns' => [
							[
								'blocks' => [
									[
										'content' => [
											'text' => 'Quote',
											'citation' => 'Author'
										],
										'id' => '7a8fb7c8-4a90-46db-9899-2a85938cc456',
										'isHidden' => false,
										'type' => 'quote'
									]
								],
								'id' => 'f1581d2a-081a-4b0b-bd1c-9fb8c908278e',
								'width' => '1/1'
							]
						],
						'id' => 'f1300976-63e9-4b3d-a452-53b57703e268'
					]
				])
			],
			'blueprint' => [
				'fields' => [
					'text' => [
						'type' => 'layout',
						'settings' => [
							'fields' => [
								'anchor' => [
									'type' => 'text'
								]
							]
						]
					]
				]
			]
		]);

		$import = [
			'text' => [
				[
					'attrs' => [
						'anchor' => 'hello-world-imported'
					],
					'columns' => [
						[
							'blocks' => [
								[
									'content' => [
										'text' => 'Hello World Imported!'
									],
									'id' => '65841ca6-53db-4079-a506-87c493f7808d'
								],
								[
									'content' => [
										'text' => '<p>Lorem Ipsum Imported!</p>'
									],
									'id' => 'f21cb883-45d5-42ea-8292-ea6126563ad9'
								]
							],
							'id' => '806a3187-e111-49c4-84e4-f6c37dc47600'
						]
					],
					'id' => 'f7799dc1-0a65-461e-8a23-f59bdcf11ec0'
				],
				[
					'attrs' => [
						'anchor' => 'imported'
					],
					'columns' => [
						[
							'blocks' => [
								[
									'content' => [
										'text' => 'Quote Imported'
									],
									'id' => '7a8fb7c8-4a90-46db-9899-2a85938cc456'
								]
							],
							'id' => 'f1581d2a-081a-4b0b-bd1c-9fb8c908278e'
						]
					],
					'id' => 'f1300976-63e9-4b3d-a452-53b57703e268'
				]
			]
		];

		$expected = [
			'text' => [
				[
					'attrs' => [
						'anchor' => 'hello-world-imported'
					],
					'columns' => [
						[
							'blocks' => [
								[
									'content' => [
										'level' => 'h2',
										'text' => 'Hello World Imported!'
									],
									'id' => '65841ca6-53db-4079-a506-87c493f7808d',
									'isHidden' => false,
									'type' => 'heading'
								],
								[
									'content' => [
										'text' => '<p>Lorem Ipsum Imported!</p>'
									],
									'id' => 'f21cb883-45d5-42ea-8292-ea6126563ad9',
									'isHidden' => false,
									'type' => 'text'
								]
							],
							'id' => '806a3187-e111-49c4-84e4-f6c37dc47600',
							'width' => '1/1'
						]
					],
					'id' => 'f7799dc1-0a65-461e-8a23-f59bdcf11ec0'
				],
				[
					'attrs' => [
						'anchor' => 'imported'
					],
					'columns' => [
						[
							'blocks' => [
								[
									'content' => [
										'text' => 'Quote Imported',
										'citation' => 'Author'
									],
									'id' => '7a8fb7c8-4a90-46db-9899-2a85938cc456',
									'isHidden' => false,
									'type' => 'quote'
								]
							],
							'id' => 'f1581d2a-081a-4b0b-bd1c-9fb8c908278e',
							'width' => '1/1'
						]
					],
					'id' => 'f1300976-63e9-4b3d-a452-53b57703e268'
				]
			]
		];

		$result = (new Importer())->walk($page, [
			'input' => $import
		]);

		$this->assertEquals($expected, $result);
	}

	public function testEditor()
	{
		$page = new Page([
			'slug' => 'test',
			'content' => [
				'text' => Json::encode([
					[
						'attrs' => [],
						'content' => 'original 1',
						'id' => '_zyusghhiw',
						'type' => 'paragraph'
					],
					[
						'attrs' => [],
						'content' => 'original 2',
						'id' => '_rtq8tflwv',
						'type' => 'paragraph'
					],
					[
						'attrs' => [],
						'content' => 'original 3',
						'id' => '_ux4q8wu0c',
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
					'content' => 'imported 3',
					'id' => '_ux4q8wu0c'
				],
				[
					'content' => 'outdated',
					'id' => '_5zzr7jl3x'
				],
				[
					'content' => 'imported 1',
					'id' => '_zyusghhiw'
				]
			]
		];

		$expected = [
			'text' => [
				[
					'attrs' => [],
					'content' => 'imported 1',
					'id' => '_zyusghhiw',
					'type' => 'paragraph'
				],
				[
					'attrs' => [],
					'content' => 'original 2',
					'id' => '_rtq8tflwv',
					'type' => 'paragraph'
				],
				[
					'attrs' => [],
					'content' => 'imported 3',
					'id' => '_ux4q8wu0c',
					'type' => 'paragraph'
				]
			]
		];

		$result = (new Importer())->walk($page, [
			'input' => $import
		]);

		$this->assertEquals($expected, $result);
	}

	public function testLink()
	{
		$page = new Page([
			'slug' => 'test',
			'content' => [
				'link' => Yaml::encode([
					'type' => 'url',
					'value' => 'https://example.com',
					'text' => 'original'
				]),
				'link2' => null
			],
			'blueprint' => [
				'fields' => [
					'link' => [
						'type' => 'link'
					],
					'link2' => [
						'type' => 'link'
					]
				]
			]
		]);

		$import = [
			'link' => [
				'text' => 'imported'
			],
			'link2' => [
				'text' => 'imported'
			]
		];

		$expected = [
			'link' => [
				'type' => 'url',
				'value' => 'https://example.com',
				'text' => 'imported'
			]
		];

		$result = (new Importer())->walk($page, [
			'input' => $import
		]);

		$this->assertEquals($expected, $result);
	}

	public function testSerialization()
	{
		$page = new Page([
			'slug' => 'test',
			'content' => [
				'text' => ''
			],
			'blueprint' => [
				'fields' => [
					'text' => [
						'type' => 'text'
					],
					'text2' => [
						'type' => 'textarea'
					]
				]
			]
		]);

		$import = [
			'text' => '<meta template=" test1 "/><kirby link="https://example.com" rel="&lt;meta template=&quot; test2 &quot;/&gt;"><value name="text"><meta template=" test3 "/></value></kirby>',
			'text2' => '<h1><strong>bold</strong> <strong>bold2</strong></h1><ul><li><meta template=" var "/></li><li><em>underlined</em> <code>code</code></li><li><kirby link="https://example.com"><value name="text">example</value></kirby></li></ul>'
		];

		$expected = [
			'text' => '{{ test1 }}(link: https://example.com rel: {{ test2 }} text: {{ test3 }})',
			'text2' => <<<END
			# **bold** **bold2**
			
			- {{ var }}
			- *underlined* `code`
			- (link: https://example.com text: example)
			END
		];

		$result = (new Importer())->walk($page, [
			'input' => $import,
			'options' => [
				'parseMarkdown' => true,
				'parseKirbyTags' => true,
				'parseTemplates' => true
			]
		]);

		$this->assertEquals($expected, $result);
	}
}
