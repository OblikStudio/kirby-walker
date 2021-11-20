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
	public static function walk(ModelWithContent $model, string $lang = null, $input = [])
	{
		$content = $model->content($lang);
		$blueprint = $model->blueprint()->fields();

		if (is_a($model, Page::class) || is_a($model, Site::class)) {
			$blueprint = array_replace([
				'title' => [
					'type' => 'text'
				]
			], $blueprint);
		}

		try {
			return static::walkContent($content, $blueprint, $input);
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
	public static function walkContent(Content $content, array $blueprint = [], $input = [])
	{
		$data = null;

		foreach ($blueprint as $key => $settings) {
			$field = $content->get($key);
			$fieldInput = $input[$key] ?? null;

			if (!isset($settings['translate'])) {
				$settings['translate'] = true;
			}

			try {
				$fieldData = static::walkField($field, $settings, $fieldInput);
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
	protected static function walkField(Field $field, array $settings, $input)
	{
		$method = 'walkField' . ucfirst($settings['type']);
		$method = method_exists(static::class, $method) ? $method : 'walkFieldDefault';
		return static::$method($field, $settings, $input);
	}

	/**
	 * How to handle text in all fields. Can be extended to parse KirbyTags.
	 */
	protected static function walkText($text)
	{
		return $text;
	}

	protected static function walkFieldDefault($field, $settings, $input)
	{
		return static::walkText($field->value());
	}

	protected static function walkFieldStructure($field, $settings, $input)
	{
		$data = null;

		foreach ($field->toStructure() as $entry) {
			$data[] = static::walkContent($entry->content(), $settings['fields']);
		}

		return $data;
	}

	protected static function walkFieldBlocks($field, $settings, $input)
	{
		$data = [];
		$blocks = $field->toBlocks();
		$sets = FormField::factory('blocks', $settings)->fieldsets();

		foreach ($blocks as $block) {
			$set = $sets->get($block->type());

			if (empty($set)) {
				throw new Error('Missing fieldset for block type: "' . $block->type() . '"');
			}

			$childData = $block->toArray();
			$childData['content'] = static::walkContent($block->content(), $set->fields());
			$data[] = $childData;
		}

		return $data;
	}

	protected static function walkFieldEditor($field, $settings, $input)
	{
		$blocks = Json::decode($field->value());

		foreach ($blocks as &$block) {
			if (!empty($block['content'])) {
				$block['content'] = static::walkText($block['content']);
			}
		}

		return $blocks;
	}

	protected static function walkFieldTags($field)
	{
		return $field->split();
	}

	protected static function walkFieldToggle($field)
	{
		return $field->toBool();
	}

	protected static function walkFieldEntity($field, $settings, $input)
	{
		return static::walkContent($field->toEntity(), $settings['fields'], $input);
	}

	protected static function walkFieldLink($field, $settings, $input)
	{
		$data = Yaml::decode($field->value());

		if (!empty($data['text'])) {
			$data['text'] = static::walkText($data['text']);
		}

		return $data;
	}

	protected static function walkFieldJson($field, $settings, $input)
	{
		return Json::decode($field->value());
	}
}
