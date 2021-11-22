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

/**
 * Base class for walkers that need to recursively iterate over Kirby models.
 */
class Walker
{
	/**
	 * Walks over the content of a model in a certain language.
	 */
	public static function walk(ModelWithContent $model, $context = [])
	{
		$content = $model->content($content['lang'] ?? null);
		$fields = $model->blueprint()->fields();

		if (is_a($model, Page::class) || is_a($model, Site::class)) {
			if (empty($fields['title'])) {
				$fields['title'] = [
					'type' => 'text'
				];
			}
		}

		try {
			$context['fields'] = $fields;
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
	public static function walkContent(Content $content, $context)
	{
		$data = null;

		if (empty($context['fields'])) {
			throw new Error('Missing fields context');
		}

		foreach ($context['fields'] as $key => $blueprint) {
			$field = $content->get($key);

			if (!isset($blueprint['translate'])) {
				$blueprint['translate'] = true;
			}

			$fieldContext = $context;
			$fieldContext['blueprint'] = $blueprint;
			$fieldContext['input'] = $context['input'][$key] ?? null;

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
	 * How to walk over the currently iterated field.
	 */
	protected static function walkField(Field $field, $context)
	{
		$method = 'walkField' . ucfirst($context['blueprint']['type']);
		$method = method_exists(static::class, $method) ? $method : 'walkFieldDefault';
		return static::$method($field, $context);
	}

	/**
	 * How to handle text in all fields. Can be extended to parse KirbyTags.
	 */
	protected static function walkText($text)
	{
		return $text;
	}

	protected static function walkFieldDefault($field, $context)
	{
		return static::walkText($field->value());
	}

	protected static function walkFieldStructure($field, $context)
	{
		$data = null;
		$context['fields'] = $context['blueprint']['fields'];

		foreach ($field->toStructure() as $entry) {
			$data[] = static::walkContent($entry->content(), $context);
		}

		return $data;
	}

	protected static function walkFieldBlocks($field, $context)
	{
		$data = [];
		$blocks = $field->toBlocks();
		$sets = FormField::factory('blocks', $context['blueprint'])->fieldsets();

		foreach ($blocks as $block) {
			$set = $sets->get($block->type());

			if (empty($set)) {
				throw new Error('Missing fieldset for block type: "' . $block->type() . '"');
			}

			$childContext = $context;
			$childContext['fields'] = $set->fields();

			$childData = $block->toArray();
			$childData['content'] = static::walkContent($block->content(), $childContext);
			$data[] = $childData;
		}

		return $data;
	}

	protected static function walkFieldEditor($field, $context)
	{
		$blocks = Json::decode($field->value());

		foreach ($blocks as &$block) {
			if (!empty($block['content'])) {
				$block['content'] = static::walkText($block['content']);
			}
		}

		return $blocks;
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

		if (!empty($data['text'])) {
			$data['text'] = static::walkText($data['text']);
		}

		return $data;
	}

	protected static function walkFieldJson($field, $context)
	{
		return Json::decode($field->value());
	}
}
