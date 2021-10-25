<?php

namespace Oblik\Walker\Walker;

use Kirby\Cms\Page;
use Kirby\Data\Json;
use Kirby\Data\Yaml;
use Oblik\Walker\TestCase;

final class ExporterTest extends TestCase
{
	public function testTitleField()
	{
		$exporter = new Exporter();

		$data = $exporter->walk(new Page([
			'slug' => 'test',
			'content' => [
				'title' => 'test'
			]
		]));

		$this->assertEquals('test', $data['title']);
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
						"id" => "0c34d9f1-de9b-456a-818b-fbcb9cefea58",
						"isHidden" => false,
						"type" => "text"
					],
					[
						'content' => [
							'text' => '<p>hidden block</p>'
						],
						"id" => "f96cfd81-56df-42a0-a05d-7eebe35f5cb9",
						"isHidden" => true,
						"type" => "text"
					],
					[
						'content' => [
							'text' => '<p>text</p>'
						],
						"id" => "c14c1bff-6a9d-4179-9aa9-b9df25dca891",
						"isHidden" => false,
						"type" => "text"
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
					"id" => "c14c1bff-6a9d-4179-9aa9-b9df25dca891"
				]
			]
		];

		$this->assertEquals($expected, $result);
	}

	public function testLinkField()
	{
		$result = (new Exporter())->walk(new Page([
			'slug' => 'test',
			'content' => [
				'link' => Yaml::encode([
					'type' => 'url',
					'value' => 'https://example.com',
					'text' => 'test'
				])
			],
			'blueprint' => [
				'fields' => [
					'link' => [
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
		$exporter = new Exporter();
		$data = $exporter->walk(new Page([
			'slug' => 'test',
			'content' => [
				'text' => '(link: https://example.com)'
			],
			'blueprint' => [
				'fields' => [
					'text' => [
						'type' => 'text'
					]
				]
			]
		]));

		$this->assertEquals('<kirby link="https://example.com"/>', $data['text']);
	}
}
