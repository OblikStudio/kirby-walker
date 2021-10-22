<?php

namespace Oblik\Walker\Walker;

use Kirby\Cms\App;
use Kirby\Cms\Page;
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
}
