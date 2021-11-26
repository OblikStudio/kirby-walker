<?php

namespace Oblik\Walker\Walker;

use Kirby\Cms\Field;
use Oblik\Walker\Serialize\KirbyTags;
use Oblik\Walker\Serialize\Markdown;
use Oblik\Walker\Serialize\Template;

class Importer extends Walker
{
	protected static function walkText(string $text, $context)
	{
		$text = parent::walkText($text, $context);

		if ($option = $context['options']['parseKirbyTags'] ?? null) {
			$text = KirbyTags::encode($text, is_array($option) ? $option : []);
		}

		if ($context['options']['parseTemplates'] ?? null) {
			$text = Template::encode($text);
		}

		if (
			($context['options']['parseMarkdown'] ?? null) &&
			($context['blueprint']['type'] ?? null) === 'textarea'
		) {
			$text = Markdown::encode($text);
		}

		return $text;
	}

	protected static function walkField(Field $field, $context)
	{
		if ($context['blueprint']['translate'] ?? true) {
			return parent::walkField($field, $context);
		} else {
			// Technically, the default field value should be returned when
			// `translate` is `false`, but Kirby will delete it anyways on save.
			// https://github.com/getkirby/kirby/issues/2790
			return null;
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
