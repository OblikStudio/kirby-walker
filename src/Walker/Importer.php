<?php

namespace Oblik\Walker\Walker;

use Kirby\Cms\Field;
use Kirby\Data\Json;
use Oblik\Walker\Serialize\KirbyTags;
use Oblik\Walker\Serialize\Template;

class Importer extends Walker
{
	protected static function walkText(string $text)
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
		$input = $context['input'] ?? null;

		if (!empty($input)) {
			return is_string($input) ? static::walkText($input) : $input;
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

			if (!empty($inputContent)) {
				$block['content'] = static::walkText($inputContent);
			}
		}

		return $data;
	}

	protected static function walkFieldLink($field, $context)
	{
		$data = parent::walkFieldLink($field, $context);
		$text = $context['input']['text'] ?? null;

		if (!empty($text)) {
			$data['text'] = static::walkText($text);
		}

		return $data;
	}
}
