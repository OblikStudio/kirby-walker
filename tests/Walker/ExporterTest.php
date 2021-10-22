<?php

namespace Oblik\Walker\Walker;

use Kirby\Cms\Page;
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

	public function testLinkFieldFilter()
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
