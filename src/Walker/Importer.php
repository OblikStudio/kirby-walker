<?php

namespace Oblik\Walker\Walker;

use Kirby\Cms\Field;
use Kirby\Cms\ModelWithContent;
use Kirby\Data\Json;
use Oblik\Walker\Serialize\KirbyTags;
use Oblik\Walker\Serialize\Template;

class Importer extends Walker
{
	protected static function walkText($text)
	{
		if (is_string($text)) {
			$text = KirbyTags::encode($text);
			$text = Template::encode($text);
		}

		return $text;
	}

	public static function walk(ModelWithContent $model, $context = [])
	{
		if (kirby()->multilang()) {
			$context['lang'] = kirby()->defaultLanguage()->code();
		}

		return parent::walk($model, $context);
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
		$input = $context['input'];
		return !empty($input) ? static::walkText($input) : $field->value();
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
