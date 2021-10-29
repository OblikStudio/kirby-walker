<?php

namespace Oblik\Walker\Walker;

use Kirby\Cms\Field;
use Oblik\Walker\Serialize\KirbyTags;
use Oblik\Walker\Serialize\Template;

class Exporter extends Walker
{
	protected static function walkField(Field $field, array $settings, $input)
	{
		if ($field->isNotEmpty() && $settings['translate'] !== false) {
			$data = parent::walkField($field, $settings, $input);

			if (!empty($data)) {
				return $data;
			}
		}
	}

	protected static function walkFieldStructure($field, $settings, $input)
	{
		$data = parent::walkFieldStructure($field, $settings, $input);

		if (!empty(array_filter($data))) {
			return $data;
		}
	}

	protected static function walkFieldBlocks($field, $settings, $input)
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

	protected static function walkFieldEditor($field, $settings, $input)
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

	protected static function walkFieldText($field, $settings, $input)
	{
		$text = $field->value();

		if (is_string($text)) {
			$text = Template::decode($text);
			$text = KirbyTags::decode($text, [
				'serialize' => [
					'tags' => [
						'text',
						'tooltip'
					]
				]
			]);
		}

		return $text;
	}

	protected static function walkFieldTextarea($field, $settings, $input)
	{
		return static::walkFieldText($field, $settings, $input);
	}

	protected static function walkFieldLink($field, $settings, $input)
	{
		$data = parent::walkFieldLink($field, $settings, $input);
		$text = $data['text'] ?? null;

		if (!empty($text) && !preg_match('/^[a-z]+:\/\//', $text)) {
			return ['text' => $text];
		}
	}

	protected static function walkFieldToggle($field)
	{
		return null;
	}

	protected static function walkFieldDate()
	{
		return null;
	}

	protected static function walkFieldPages()
	{
		return null;
	}

	protected static function walkFieldFiles()
	{
		return null;
	}

	protected static function walkFieldUrl()
	{
		return null;
	}

	protected static function walkFieldNumber()
	{
		return null;
	}
}
