<?php

namespace Oblik\Walker\Walker;

use Error;
use Exception;
use Throwable;
use Kirby\Cms\Content;
use Kirby\Cms\Field;
use Kirby\Cms\ModelWithContent;
use Kirby\Cms\Page;
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
	public function walk(ModelWithContent $model, string $lang = null, $input = [])
	{
		$content = $model->content($lang);
		$blueprint = $model->blueprint()->fields();

		if (is_a($model, Page::class)) {
			$blueprint = array_replace([
				'title' => [
					'type' => 'text'
				]
			], $blueprint);
		}

		try {
			return $this->walkContent($content, $blueprint, $input);
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
	public function walkContent(Content $content, array $blueprint = [], $input = [])
	{
		$data = null;

		foreach ($blueprint as $key => $settings) {
			$field = $content->get($key);
			$fieldInput = $input[$key] ?? null;

			try {
				$fieldData = $this->walkField($field, $settings, $fieldInput);
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
	protected function walkField(Field $field, array $settings, $input)
	{
		$method = 'walkField' . ucfirst($settings['type']);
		$method = method_exists($this, $method) ? $method : 'walkFieldDefault';
		return $this->$method($field, $settings, $input);
	}

	protected function walkFieldDefault($field, $settings, $input)
	{
		return $field->value();
	}

	protected function walkFieldStructure($field, $settings, $input)
	{
		$data = null;

		foreach ($field->toStructure() as $entry) {
			$data[] = $this->walkContent($entry->content(), $settings['fields']);
		}

		return $data;
	}

	protected function walkFieldBlocks($field, $settings, $input)
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
			$childData['content'] = $this->walkContent($block->content(), $set->fields());

			if (!empty($childData['content'])) {
				$data[] = $childData;
			}
		}

		return $data;
	}

	protected function walkFieldEntity($field, $settings, $input)
	{
		return $this->walkContent($field->toEntity(), $settings['fields'], $input);
	}

	protected function walkFieldLink($field, $settings, $input)
	{
		return Yaml::decode($field->value());
	}
}
