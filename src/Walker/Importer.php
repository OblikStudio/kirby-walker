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

	protected static function walkFieldEditorBlock($block, $context)
	{
		if (is_string($content = $context['input']['content'] ?? null)) {
			$block['content'] = static::walkText($content, $context);
		}

		return $block;
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
