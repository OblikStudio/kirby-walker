<?php

namespace Oblik\Walker\Walker;

use Kirby\Cms\App;
use Kirby\Cms\Page;
use Kirby\Data\Json;
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
		$result = (new Importer())->walk($page, null, $content);

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
		$result = (new Importer())->walk($page, null, $content);

		$this->assertEquals($content, $result);
	}

	public function testTranslateFalse()
	{
		$page = new Page([
			'slug' => 'test',
			'content' => $original = [
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

		$content = ['text' => 'imported'];
		$result = (new Importer())->walk($page, null, $content);

		$this->assertEquals($original, $result);
	}

	public function testImportsBlocks()
	{
		$page = new Page([
			'slug' => 'test',
			'content' => [
				'text' => Json::encode([
					[
						'content' => [
							'text' => '<p>original 1</p>'
						],
						"id" => "c14c1bff-6a9d-4179-9aa9-b9df25dca891",
						"isHidden" => false,
						"type" => "text"
					],
					[
						'content' => [
							'text' => '<p>original 2</p>'
						],
						"id" => "9da43326-6118-4f4a-b4cd-23ca5bd5c35f",
						"isHidden" => false,
						"type" => "text"
					],
					[
						'content' => [
							'text' => '<p>original 3</p>'
						],
						"id" => "fdd25d51-8ac5-42aa-8476-b63dac8c577b",
						"isHidden" => false,
						"type" => "text"
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
					"id" => "fdd25d51-8ac5-42aa-8476-b63dac8c577b"
				],
				[
					'content' => [
						'text' => '<p>outdated block</p>'
					],
					"id" => "bb8ca9fd-d1ee-4567-a130-4946afdee9a8"
				],
				[
					'content' => [
						'text' => '<p>imported 1</p>'
					],
					"id" => "c14c1bff-6a9d-4179-9aa9-b9df25dca891"
				]
			]
		];

		$expected = [
			'text' => [
				[
					'content' => [
						'text' => '<p>imported 1</p>'
					],
					"id" => "c14c1bff-6a9d-4179-9aa9-b9df25dca891",
					"isHidden" => false,
					"type" => "text"
				],
				[
					'content' => [
						'text' => '<p>original 2</p>'
					],
					"id" => "9da43326-6118-4f4a-b4cd-23ca5bd5c35f",
					"isHidden" => false,
					"type" => "text"
				],
				[
					'content' => [
						'text' => '<p>imported 3</p>'
					],
					"id" => "fdd25d51-8ac5-42aa-8476-b63dac8c577b",
					"isHidden" => false,
					"type" => "text"
				]
			]
		];

		$result = (new Importer())->walk($page, null, $import);
		$this->assertEquals($expected, $result);
	}
}
