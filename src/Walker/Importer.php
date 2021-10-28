<?php

namespace Oblik\Walker\Walker;

use Error;
use Kirby\Cms\Field;
use Kirby\Cms\ModelWithContent;
use Kirby\Data\Json;
use Kirby\Form\Field as FormField;

class Importer extends Walker
{
	public static function walk(ModelWithContent $model, string $lang = null, $input = [])
	{
		if (kirby()->multilang()) {
			$lang = kirby()->defaultLanguage()->code();
		}

		return parent::walk($model, $lang, $input);
	}

	protected static function walkField(Field $field, array $settings, $input)
	{
		if ($settings['translate'] !== false) {
			return parent::walkField($field, $settings, $input);
		} else {
			return $field->value();
		}
	}

	protected static function walkFieldDefault($field, $settings, $input)
	{
		return !empty($input) ? $input : $field->value();
	}

	protected static function walkFieldStructure($field, $settings, $input)
	{
		$data = null;

		if (is_array($input)) {
			$input = array_column($input, null, 'id');
		}

		foreach ($field->toStructure() as $key => $entry) {
			// `$key` is either an integer or a string, depending on whether the
			// structure entry has an `id` field or not.
			$data[] = static::walkContent($entry->content(), $settings['fields'], $input[$key] ?? null);
		}

		return $data;
	}

	protected static function walkFieldBlocks($field, $settings, $input)
	{
		$data = [];

		if (is_array($input)) {
			$input = array_column($input, null, 'id');
		}

		$blocks = $field->toBlocks();
		$sets = FormField::factory('blocks', $settings)->fieldsets();

		foreach ($blocks as $id => $block) {
			$set = $sets->get($block->type());

			if (empty($set)) {
				throw new Error('Missing fieldset for block type: "' . $block->type() . '"');
			}

			$childData = $block->toArray();
			$childData['content'] = static::walkContent($block->content(), $set->fields(), $input[$id]['content'] ?? null);
			$data[] = $childData;
		}

		return $data;
	}

	protected static function walkFieldEditor($field, $settings, $input)
	{
		$data = Json::decode($field->value());

		if (is_array($input)) {
			$input = array_column($input, null, 'id');
		}

		foreach ($data as &$block) {
			$id = $block['id'];
			$inputContent = $input[$id]['content'] ?? null;

			if (!empty($inputContent)) {
				$block['content'] = $inputContent;
			}
		}

		return $data;
	}

	protected static function walkFieldLink($field, $settings, $input)
	{
		$data = parent::walkFieldLink($field, $settings, $input);
		$text = $input['text'] ?? null;

		if (!empty($text)) {
			$data['text'] = $text;
		}

		return $data;
	}
}
