<?php

namespace Oblik\Walker\Walker;

use Kirby\Cms\Field;
use Kirby\Cms\ModelWithContent;

class Importer extends Walker
{
	public function walk(ModelWithContent $model, string $lang = null, $input = [])
	{
		if (kirby()->multilang()) {
			$lang = kirby()->defaultLanguage()->code();
		}

		return parent::walk($model, $lang, $input);
	}

	protected function walkField(Field $field, array $settings, $input)
	{
		if ($settings['translate'] !== false) {
			return parent::walkField($field, $settings, $input);
		} else {
			return $field->value();
		}
	}

	protected function walkFieldDefault($field, $settings, $input)
	{
		return !empty($input) ? $input : $field->value();
	}
}
