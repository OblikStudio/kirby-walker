<?php

namespace Oblik\Walker\Walker;

use Kirby\Cms\Field;
use Oblik\Walker\Serialize\KirbyTags;

class Exporter extends Walker
{
	protected function walkField(Field $field, array $settings, $input)
	{
		if ($field->isNotEmpty() && $settings['translate'] !== false) {
			return parent::walkField($field, $settings, $input);
		}
	}

	protected function walkFieldText($field, $settings, $input)
	{
		return KirbyTags::decode($field->value(), [
			'serialize' => [
				'tags' => ['text']
			]
		]);
	}

	protected function walkFieldTextarea($field, $settings, $input)
	{
		return $this->walkFieldText($field, $settings, $input);
	}

	protected function walkFieldLink($field, $settings, $input)
	{
		$data = parent::walkFieldLink($field, $settings, $input);
		$data = array_intersect_key($data, ['text' => true]);
		return !empty($data) ? $data : null;
	}

	protected function walkFieldToggle($field)
	{
		return null;
	}

	protected function walkFieldDate()
	{
		return null;
	}

	protected function walkFieldPages()
	{
		return null;
	}

	protected function walkFieldFiles()
	{
		return null;
	}

	protected function walkFieldUrl()
	{
		return null;
	}

	protected function walkFieldNumber()
	{
		return null;
	}
}
