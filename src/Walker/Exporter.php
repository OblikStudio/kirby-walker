<?php

namespace Oblik\Walker\Walker;

use Kirby\Cms\Field;
use Oblik\Walker\Serialize\KirbyTags;
use Oblik\Walker\Serialize\Template;

class Exporter extends Walker
{
	protected static function walkText(string $text, $context)
	{
		$text = Template::decode($text);
		$text = KirbyTags::decode($text);

		return $text;
	}

	protected static function walkField(Field $field, $context)
	{
		if ($field->isNotEmpty() && $context['blueprint']['translate'] !== false) {
			$data = parent::walkField($field, $context);

			if (!empty($data)) {
				return $data;
			}
		}
	}

	protected static function walkFieldStructure($field, $context)
	{
		$data = parent::walkFieldStructure($field, $context);

		if (!empty(array_filter($data))) {
			return $data;
		}
	}

	protected static function walkFieldBlocksBlock($block, $context)
	{
		$block = parent::walkFieldBlocksBlock($block, $context);

		if (empty($block['content'] ?? null)) {
			return null;
		}

		unset($block['isHidden']);
		unset($block['type']);

		return $block;
	}

	protected static function walkFieldEditorBlock($block, $context)
	{
		if (empty($block['content'] ?? null)) {
			return null;
		}

		unset($block['attrs']);
		unset($block['type']);

		return $block;
	}

	protected static function walkFieldLink($field, $context)
	{
		$data = parent::walkFieldLink($field, $context);
		$text = $data['text'] ?? null;

		if (!empty($text) && !preg_match('/^[a-z]+:\/\//', $text)) {
			return ['text' => $text];
		}
	}

	protected static function walkFieldToggle($field, $context)
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
