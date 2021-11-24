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
		$siteData = Exporter::walk(new Site([
			'content' => [
				'title' => 'test'
			]
		]));

		$pageData = Exporter::walk(new Page([
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
		$result = Exporter::walk(new Page([
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

	public function testEditor()
	{
		$result = Exporter::walk(new Page([
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
		$result = Exporter::walk(new Page([
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

		$data = Exporter::walk($page, [
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
