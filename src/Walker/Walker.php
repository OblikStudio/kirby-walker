<?php

namespace Oblik\Walker\Walker;

use Error;
use Exception;
use Throwable;
use Kirby\Cms\Content;
use Kirby\Cms\Field;
use Kirby\Cms\ModelWithContent;
use Kirby\Cms\Page;
use Kirby\Cms\Site;
use Kirby\Data\Json;
use Kirby\Data\Yaml;
use Kirby\Form\Field as FormField;
use Kirby\Toolkit\Str;

/**
 * Base class for walkers that need to recursively iterate over Kirby models.
 */
class Walker
{
	/**
	 * Walks over the content of a model in a certain language.
	 */
	public static function walk(ModelWithContent $model, array $context = [])
	{
		$content = $model->content($context['lang'] ?? null);
		$fields = $model->blueprint()->fields();

		if (is_a($model, Page::class) || is_a($model, Site::class)) {
			// Site and pages have a title field by default, regardless of
			// whether it's present in the blueprint. Also, array_replace() is
			// used to make sure it's the first field in the result.
			$fields = array_replace([
				'title' => [
					'type' => 'text'
				]
			], $fields);
		}

		$context['fields'] = $fields;
		$context['model'] = $model;

		try {
			return static::walkContent($content, $context);
		} catch (Throwable $e) {
			$id = $model->id();
			$error = $e->getMessage();

			throw new Exception("Model \"$id\": $error");
		}
	}

	/**
	 * Iterates over the fields in a Content object based on a blueprint array and
	 * returns data for each field.
	 */
	public static function walkContent(Content $content, array $context)
	{
		$data = null;

		// Needed when walking over structure or block entries. Otherwise, the
		// entry's fields are treated as structure/block entries in
		// `findMatchingEntry()` and expected to have `id` fields.
		unset($context['blueprint']);

		if (empty($context['fields'])) {
			throw new Error('Missing fields context');
		}

		foreach ($context['fields'] as $key => $blueprint) {
			$field = $content->get($key);
			$blueprintOptions = $blueprint['walker'] ?? null;

			if ($blueprintOptions === false) {
				continue;
			}

			if (!isset($blueprint['translate'])) {
				$blueprint['translate'] = true;
			}

			$fieldContext = static::subcontext($key, $context);
			$fieldContext['blueprint'] = $blueprint;

			if (is_array($blueprintOptions)) {
				$fieldContext['options'] = array_replace_recursive(
					$fieldContext['options'] ?? [],
					$blueprintOptions
				);
			}

			try {
				$fieldData = static::walkField($field, $fieldContext);
			} catch (Throwable $e) {
				$fieldName = $field->key();
				$errorName = $e->getMessage();

				throw new Exception("Field \"$fieldName\": $errorName");
			}

			if ($fieldData !== null) {
				$data[$key] = $fieldData;
			}
		}

		return $data;
	}

	/**
	 * Prepares the context for that of a child entry, such as an item in a
	 * structure field or a block in a blocks field.
	 */
	protected static function subcontext($key, $context)
	{
		$input = $context['input'] ?? null;

		if (is_array($input)) {
			$context['input'] = static::findMatchingEntry($key, $input, $context);
		}

		return $context;
	}

	/**
	 * Attempts to find a key in an array of data using different strategies,
	 * depending on the field's blueprint type, as given by the context.
	 */
	protected static function findMatchingEntry($key, array $data, array $context)
	{
		$type = $context['blueprint']['type'] ?? null;
		$idField = $context['blueprint']['fields']['id'] ?? null;

		if ($type === 'blocks' || ($type === 'structure' && $idField) || $type === 'editor') {
			foreach ($data as $entry) {
				if (($entry['id'] ?? null) === $key) {
					if ($type === 'blocks') {
						return $entry['content'] ?? null;
					} else {
						return $entry;
					}
				}
			}
		} else {
			return $data[$key] ?? null;
		}
	}

	/**
	 * How to walk over the currently iterated field.
	 */
	protected static function walkField(Field $field, $context)
	{
		$method = 'walkField' . ucfirst($context['blueprint']['type']);
		$method = method_exists(static::class, $method) ? $method : 'walkFieldDefault';
		return static::$method($field, $context);
	}

	protected static function walkFieldDefault($field, $context)
	{
		return is_string($value = $field->value()) ? static::walkText($value, $context) : $value;
	}

	/**
	 * How to handle text in all fields. Can be extended to parse KirbyTags.
	 */
	protected static function walkText(string $text, $context)
	{
		if ($context['options']['replace'] ?? null) {
			$data = [
				'kirby' => kirby(),
				'site' => site()
			];

			if (is_a($context['model'], Page::class)) {
				$data['page'] = $context['model'];
			}

			$text = Str::template($text, $data);
		}

		return $text;
	}

	protected static function walkFieldStructure($field, $context)
	{
		$data = null;
		$context['fields'] = $context['blueprint']['fields'];

		foreach ($field->toStructure() as $key => $entry) {
			// `$key` is either an integer or a string, depending on whether the
			// structure entry has an `id` field or not.
			$entryContext = static::subcontext($key, $context);
			$data[] = static::walkContent($entry->content(), $entryContext);
		}

		return $data;
	}

	protected static function walkFieldBlocks($field, $context)
	{
		$data = [];
		$blocks = $field->toBlocks();
		$sets = FormField::factory('blocks', $context['blueprint'])->fieldsets();

		foreach ($blocks as $id => $block) {
			$set = $sets->get($block->type());

			if (empty($set)) {
				throw new Error('Missing fieldset for block type: "' . $block->type() . '"');
			}

			$blockContext = static::subcontext($id, $context);
			$blockContext['fields'] = $set->fields();
			$blockData = static::walkFieldBlocksBlock($block, $blockContext);

			if (!empty($blockData)) {
				$data[] = $blockData;
			}
		}

		return $data;
	}

	protected static function walkFieldBlocksBlock($block, $context)
	{
		$data = $block->toArray();
		$data['content'] = static::walkContent($block->content(), $context);
		return $data;
	}

	protected static function walkFieldEditor($field, $context)
	{
		$data = [];
		$blocks = Json::decode($field->value());

		foreach ($blocks as $block) {
			if (is_string($id = $block['id'] ?? null)) {
				$blockContext = static::subcontext($id, $context);
				$blockData = static::walkFieldEditorBlock($block, $blockContext);

				if (!empty($blockData)) {
					$data[] = $blockData;
				}
			}
		}

		return $data;
	}

	protected static function walkFieldEditorBlock($block, $context)
	{
		if (is_string($content = $block['content'] ?? null)) {
			$block['content'] = static::walkText($content, $context);
		}

		return $block;
	}

	protected static function walkFieldTags($field, $context)
	{
		return $field->split();
	}

	protected static function walkFieldToggle($field, $context)
	{
		return $field->toBool();
	}

	protected static function walkFieldEntity($field, $context)
	{
		$context['fields'] = $context['blueprint']['fields'];
		return static::walkContent($field->toEntity(), $context);
	}

	protected static function walkFieldLink($field, $context)
	{
		$data = Yaml::decode($field->value());

		if (is_string($text = $data['text'] ?? null)) {
			$data['text'] = static::walkText($text, $context);
		}

		return $data;
	}

	protected static function walkFieldJson($field, $context)
	{
		return Json::decode($field->value());
	}
}
