<?php

namespace Oblik\Walker\Walker;

use Kirby\Cms\Page;
use Oblik\Walker\TestCase;

final class WalkerTest extends TestCase
{
	public function testLink()
	{
		$result = (new Walker())->walk(new Page([
			'slug' => 'test',
			'content' => [
				'link' => null
			],
			'blueprint' => [
				'fields' => [
					'link' => [
						'type' => 'link'
					]
				]
			]
		]));

		$expected = null;

		$this->assertEquals($expected, $result);
	}
}
