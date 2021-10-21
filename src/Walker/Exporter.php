<?php

namespace Oblik\Walker\Walker;

use Kirby\Cms\Field;

class Exporter extends Walker
{
	protected function walkField(Field $field, array $settings, $input)
	{
		if ($field->isNotEmpty()) {
			return parent::walkField($field, $settings, $input);
		}
	}

	protected function walkFieldToggle($field)
	{
		return $field->toBool();
	}
}
