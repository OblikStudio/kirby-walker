<?php

namespace Oblik\Walker\Serialize;

use Oblik\Walker\TestCase;

final class TemplateTest extends TestCase
{
	public function serialize($input, $expected)
	{
		$xml = Template::decode($input);
		$this->assertEquals($expected, $xml);
		$text = Template::encode($xml);
		$this->assertEquals($input, $text);
	}

	public function testSerialize()
	{
		$this->serialize(
			'{{ test }}',
			'<meta template=" test "/>'
		);
	}
}
