<?php

namespace Oblik\Walker\Walker;

use Kirby\Cms\Page;
use Kirby\Cms\Site;
use Kirby\Data\Json;
use Kirby\Data\Yaml;
use Oblik\Walker\TestCase;

final class ExporterTest extends TestCase
{
	public function testTitle()
	{
		$siteData = (new Exporter())->walk(new Site([
			'content' => [
				'title' => 'test'
			]
		]));

		$pageData = (new Exporter())->walk(new Page([
			'slug' => 'test',
			'content' => [
				'title' => 'test'
			]
		]));

		$this->assertEquals('test', $siteData['title']);
		$this->assertEquals('test', $pageData['title']);
	}

	public function testBlocks()
	{
		$result = (new Exporter())->walk(new Page([
			'slug' => 'test',
			'content' => [
				'text' => Json::encode([
					[
						'content' => [
							'text' => ''
						],
						'id' => '0c34d9f1-de9b-456a-818b-fbcb9cefea58',
						'isHidden' => false,
						'type' => 'text'
					],
					[
						'content' => [
							'text' => '<p>hidden block</p>'
						],
						'id' => 'f96cfd81-56df-42a0-a05d-7eebe35f5cb9',
						'isHidden' => true,
						'type' => 'text'
					],
					[
						'content' => [
							'text' => '<p>text</p>'
						],
						'id' => 'c14c1bff-6a9d-4179-9aa9-b9df25dca891',
						'isHidden' => false,
						'type' => 'text'
					]
				])
			],
			'blueprint' => [
				'fields' => [
					'text' => [
						'type' => 'blocks'
					]
				]
			]
		]));

		$expected = [
			'text' => [
				[
					'content' => [
						'text' => '<p>text</p>'
					],
					'id' => 'c14c1bff-6a9d-4179-9aa9-b9df25dca891'
				]
			]
		];

		$this->assertEquals($expected, $result);
	}

	public function testLayout()
	{
		$result = (new Exporter())->walk(new Page([
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
		]));

		$expected = [
			'text' => [
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
										'text' => 'Hello World!',
									],
									'id' => '65841ca6-53db-4079-a506-87c493f7808d'
								],
								[
									'content' => [
										'text' => '<p>Lorem Ipsum!</p>'
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
					'columns' => [
						[
							'blocks' => [
								[
									'content' => [
										'text' => 'Quote',
										'citation' => 'Author'
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

		$this->assertEquals($expected, $result);
	}

	public function testEditor()
	{
		$result = (new Exporter())->walk(new Page([
			'slug' => 'test',
			'content' => [
				'text' => Json::encode([
					[
						'attrs' => [],
						'content' => '',
						'id' => '_rtq8tflwv',
						'type' => 'paragraph'
					],
					[
						'attrs' => [],
						'content' => 'text',
						'id' => '_zyusghhiw',
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
		]));

		$expected = [
			'text' => [
				[
					'content' => 'text',
					'id' => '_zyusghhiw'
				]
			]
		];

		$this->assertEquals($expected, $result);
	}

	public function testLink()
	{
		$result = (new Exporter())->walk(new Page([
			'slug' => 'test',
			'content' => [
				'link' => Yaml::encode([
					'type' => 'url',
					'value' => 'https://example.com',
					'text' => 'test'
				]),
				'link2' => Yaml::encode([
					'type' => 'url',
					'value' => 'https://example.com',
					'text' => 'https://example.com'
				])
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
		]));

		$expected = [
			'link' => [
				'text' => 'test'
			]
		];

		$this->assertEquals($expected, $result);
	}

	public function testSerialization()
	{
		$page = new Page([
			'slug' => 'test',
			'content' => [
				'text' => '{{ test1 }}(link: https://example.com rel: {{ test2 }} text: {{ test3 }})',
				'text2' => <<<END
				# <strong>bold</strong> **bold2**
				
				- {{ var }}
				- _underlined_ `code`
				- (link: https://example.com text: example)
				END
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

		$data = (new Exporter())->walk($page, [
			'options' => [
				'parseMarkdown' => true,
				'parseTemplates' => true,
				'parseKirbyTags' => [
					'externalAttributes' => ['text']
				]
			]
		]);

		$this->assertEquals('<meta template=" test1 "/><kirby link="https://example.com" rel="&lt;meta template=&quot; test2 &quot;/&gt;"><value name="text"><meta template=" test3 "/></value></kirby>', $data['text']);
		$this->assertEquals('<h1><strong>bold</strong> <strong>bold2</strong></h1><ul><li><meta template=" var "/></li><li><em>underlined</em> <code>code</code></li><li><kirby link="https://example.com"><value name="text">example</value></kirby></li></ul>', $data['text2']);
	}
}
