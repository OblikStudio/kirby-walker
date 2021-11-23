<?php

namespace Oblik\Walker\Walker;

use Kirby\Cms\Field;
use Kirby\Data\Json;
use Oblik\Walker\Serialize\KirbyTags;
use Oblik\Walker\Serialize\Template;

class Importer extends Walker
{
	protected static function walkText(string $text, $context)
	{
		$text = KirbyTags::encode($text);
		$text = Template::encode($text);

		return $text;
	}

	protected static function walkField(Field $field, $context)
	{
		if ($context['blueprint']['translate'] !== false) {
			return parent::walkField($field, $context);
		} else {
			return $field->value();
		}
	}

	protected static function walkFieldDefault($field, $context)
	{
		if (!empty($input = $context['input'] ?? null)) {
			return is_string($input) ? static::walkText($input, $context) : $input;
		} else {
			return $field->value();
		}
	}

	protected static function walkFieldEditor($field, $context)
	{
		$data = Json::decode($field->value());
		$input = $context['input'];

		if (is_array($input)) {
			$input = array_column($input, null, 'id');
		}

		foreach ($data as &$block) {
			$id = $block['id'];
			$inputContent = $input[$id]['content'] ?? null;

			if (is_string($inputContent)) {
				$block['content'] = static::walkText($inputContent, $context);
			}
		}

		return $data;
	}

	protected static function walkFieldLink($field, $context)
	{
		$data = parent::walkFieldLink($field, $context);

		if (is_string($text = $context['input']['text'] ?? null)) {
			$data['text'] = static::walkText($text, $context);
		}

		return $data;
	}
}
