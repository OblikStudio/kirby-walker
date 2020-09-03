<?php

namespace Oblik\Walker\Serialize;

use Kirby\Toolkit\A;

class Tags
{
	public static function decode(string $input, array $options)
	{
		return $options['field']->split();
	}

	public static function encode(array $input, array $options)
	{
		return A::join($input);
	}
}
