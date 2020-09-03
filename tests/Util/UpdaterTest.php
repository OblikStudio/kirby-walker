<?php

namespace Oblik\Walker\Util;

use Oblik\Walker\TestCase;

use Kirby\Cms\App;
use Kirby\Cms\Page;
use Kirby\Data\Yaml;

final class UpdaterTest extends TestCase
{
	public function testStructureImportSync()
	{
		$this->app = new App([
			'roots' => [
				'index' => $this->fixtures
			],
			'hooks' => Updater::getHooks()
		]);

		$this->app->impersonate('kirby');

		$page = new Page([
			'slug' => 'test',
			'content' => [
				'items' => Yaml::encode([
					[
						'nested' => [
							[
								'foo' => 'a',
								'id' => 'pajt'
							]
						],
						'id' => 'djzw'
					]
				])
			],
			'blueprint' => [
				'fields' => [
					'items' => [
						'type' => 'structure',
						'fields' => [
							'nested' => [
								'type' => 'structure',
								'fields' => [
									'foo' => [
										'type' => 'text'
									]
								],
								'walker' => [
									'sync' => true
								]
							]
						],
						'walker' => [
							'sync' => true
						]
					]
				]
			]
		]);

		$input = [
			'items' => [
				[
					'nested' => [
						[
							'foo' => 'new'
						]
					]
				],
				[
					'nested' => [
						[
							'foo' => 'new'
						],
						[
							'foo' => 'b',
							'id' => 'pajt'
						]
					],
					'id' => 'djzw'
				]
			]
		];

		$page->update($input);
		$updatedPage = $this->app->page('test');
		$updatedPageData = $updatedPage->content()->toArray();
		$data = Yaml::decode($updatedPageData['items']);

		$this->assertArrayHasKey('id', $data[0]);
		$this->assertArrayHasKey('id', $data[0]['nested'][0]);
		$this->assertEquals('new', $data[0]['nested'][0]['foo']);

		$this->assertArrayHasKey('id', $data[1]['nested'][0]);
		$this->assertEquals('new', $data[1]['nested'][0]['foo']);

		$this->assertEquals('djzw', $data[1]['id']);
		$this->assertEquals(['foo' => 'b', 'id' => 'pajt'], $data[1]['nested'][1]);
	}

	public function testNonSyncStructureUnchanged()
	{
		$this->app = new App([
			'roots' => [
				'index' => $this->fixtures
			],
			'hooks' => Updater::getHooks()
		]);

		$this->app->impersonate('kirby');

		$page = new Page([
			'slug' => 'test',
			'content' => [
				'items' => Yaml::encode([
					[
						'foo' => 'a'
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
						]
					]
				]
			]
		]);

		$input = [
			'items' => [
				[
					'foo' => 'b'
				],
				[
					'foo' => 'new'
				]
			]
		];

		$page->update($input);
		$updatedPage = $this->app->page('test');
		$updatedPageData = $updatedPage->content()->toArray();
		$data = Yaml::decode($updatedPageData['items']);

		$this->assertEquals([
			[
				'foo' => 'b'
			],
			[
				'foo' => 'new'
			]
		], $data);
	}
}
