<?php

namespace Oblik\Walker\Walker;

use Kirby\Cms\Field;
use Oblik\Walker\Serialize\KirbyTags;
use Oblik\Walker\Serialize\Markdown;
use Oblik\Walker\Serialize\Template;

class Exporter extends Walker
{
	protected function walkText(string $text, $context)
	{
		$text = parent::walkText($text, $context);

		if ($context['options']['parseTemplates'] ?? null) {
			$text = Template::decode($text);
		}

		if ($option = $context['options']['parseKirbyTags'] ?? null) {
			$text = KirbyTags::decode($text, is_array($option) ? $option : []);
		}

		if (
			($context['options']['parseMarkdown'] ?? null) &&
			($context['blueprint']['type'] ?? null) === 'textarea'
		) {
			$text = Markdown::decode($text);
		}

		return $text;
	}

	protected function walkField(Field $field, $context)
	{
		if ($field->isNotEmpty() && ($context['blueprint']['translate'] ?? true)) {
			$data = parent::walkField($field, $context);

			if (!empty($data)) {
				return $data;
			}
		}
	}

	protected function walkFieldStructure($field, $context)
	{
		$data = parent::walkFieldStructure($field, $context);

		if (is_array($data) && !empty(array_filter($data))) {
			// If *all* resulting values are empty, avoid returning an array of
			// empty values. Otherwise, if there's at least one non-empty value,
			// leave empty values as-is so they keep their position in the array
			// and make sure the non-empty ones remain at the correct index.
			return $data;
		}
	}

	protected function walkFieldBlocksBlock($block, $context)
	{
		$block = parent::walkFieldBlocksBlock($block, $context);

		if (empty($block['content'] ?? null)) {
			return null;
		}

		unset($block['isHidden']);
		unset($block['type']);

		return $block;
	}

	protected function walkFieldEditorBlock($block, $context)
	{
		$block = parent::walkFieldEditorBlock($block, $context);

		if (empty($block['content'] ?? null)) {
			return null;
		}

		unset($block['attrs']);
		unset($block['type']);

		return $block;
	}

	protected function walkFieldLink($field, $context)
	{
		$data = parent::walkFieldLink($field, $context);
		$text = $data['text'] ?? null;

		if (!empty($text) && !preg_match('/^[a-z]+:\/\//', $text)) {
			return ['text' => $text];
		}
	}

	protected function walkFieldToggle($field, $context)
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
