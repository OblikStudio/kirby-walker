<?php

namespace Oblik\Walker\Walker;

use Error;
use Kirby\Cms\Field;
use Kirby\Cms\ModelWithContent;
use Kirby\Data\Json;
use Kirby\Form\Field as FormField;
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

	protected static function walkFieldStructure($field, $context)
	{
		$data = null;
		$context['fields'] = $context['blueprint']['fields'];
		$input = $context['input'];

		if (is_array($input)) {
			$input = array_column($input, null, 'id');
		}

		foreach ($field->toStructure() as $key => $entry) {
			// `$key` is either an integer or a string, depending on whether the
			// structure entry has an `id` field or not.
			$entryContext = $context;
			$entryContext['input'] = $input[$key] ?? null;

			$data[] = static::walkContent($entry->content(), $entryContext);
		}

		return $data;
	}

	protected static function walkFieldBlocks($field, $context)
	{
		$data = [];
		$input = $context['input'];

		if (is_array($input)) {
			$input = array_column($input, null, 'id');
		}

		$blocks = $field->toBlocks();
		$sets = FormField::factory('blocks', $context['blueprint'])->fieldsets();

		foreach ($blocks as $id => $block) {
			$set = $sets->get($block->type());

			if (empty($set)) {
				throw new Error('Missing fieldset for block type: "' . $block->type() . '"');
			}

			$childContext = $context;
			$childContext['fields'] = $set->fields();
			$childContext['input'] = $input[$id]['content'] ?? null;

			$childData = $block->toArray();
			$childData['content'] = static::walkContent($block->content(), $childContext);
			$data[] = $childData;
		}

		return $data;
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
