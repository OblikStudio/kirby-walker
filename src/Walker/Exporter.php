<?php

namespace Oblik\Walker\Walker;

use Kirby\Cms\Field;
use Oblik\Walker\Serialize\KirbyTags;

class Exporter extends Walker
{
	protected function walkField(Field $field, array $settings, $input)
	{
		if ($field->isNotEmpty() && $settings['translate'] !== false) {
			$data = parent::walkField($field, $settings, $input);

			if (!empty($data)) {
				return $data;
			}
		}
	}

	protected function walkFieldStructure($field, $settings, $input)
	{
		$data = parent::walkFieldStructure($field, $settings, $input);

		if (!empty(array_filter($data))) {
			return $data;
		}
	}

	protected function walkFieldBlocks($field, $settings, $input)
	{
		$data = parent::walkFieldBlocks($field, $settings, $input);

		foreach ($data as $i => &$block) {
			if (empty($block['content'])) {
				array_splice($data, $i, 1);
				continue;
			}

			unset($block['isHidden']);
			unset($block['type']);
		}

		return $data;
	}

	protected function walkFieldEditor($field, $settings, $input)
	{
		$data = parent::walkFieldEditor($field, $settings, $input);

		foreach ($data as $i => &$block) {
			if (empty($block['content'])) {
				array_splice($data, $i, 1);
				continue;
			}

			unset($block['attrs']);
			unset($block['type']);
		}

		return $data;
	}

	protected function walkFieldText($field, $settings, $input)
	{
		return KirbyTags::decode($field->value(), [
			'serialize' => [
				'tags' => [
					'text',
					'tooltip'
				]
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
		$text = $data['text'] ?? null;

		if (!empty($text) && !preg_match('/^[a-z]+:\/\//', $text)) {
			return ['text' => $text];
		}
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
