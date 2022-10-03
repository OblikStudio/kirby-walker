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
	public $context;

	public function __construct(array $context = [])
	{
		$this->context = $context;
	}

	/**
	 * Walks over the content of a model in a certain language.
	 */
	public function walk(ModelWithContent $model, array $context = [])
	{
		$context = array_replace($this->context, $context);
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
			return $this->walkContent($content, $context);
		} catch (Throwable $e) {
			throw new Exception('Could not walk model ' . $model->id(), 1, $e);
		}
	}

	/**
	 * Iterates over the fields in a Content object based on a blueprint array and
	 * returns data for each field.
	 */
	protected function walkContent(Content $content, array $context)
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

			$fieldContext = $this->subcontext('key', $key, $context);
			$fieldContext['blueprint'] = $blueprint;

			if (is_array($blueprintOptions)) {
				$fieldContext['options'] = array_replace_recursive(
					$fieldContext['options'] ?? [],
					$blueprintOptions
				);
			}

			try {
				$fieldData = $this->walkField($field, $fieldContext);
			} catch (Throwable $e) {
				throw new Exception('Could not walk field ' . $field->key(), 2, $e);
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
	protected function subcontext($strategy, $key, $context)
	{
		$input = $context['input'] ?? null;

		if (is_array($input)) {
			$context['input'] = $this->findMatchingEntry($strategy, $key, $input);
		}

		return $context;
	}

	/**
	 * Attempts to find a key in an array of data using different strategies.
	 */
	protected function findMatchingEntry($strategy, $key, array $data)
	{
		if ($strategy === 'key') {
			return $data[$key] ?? null;
		}

		if ($strategy === 'id') {
			foreach ($data as $entry) {
				if (($entry['id'] ?? null) === $key) {
					return $entry;
				}
			}
		}
	}

	/**
	 * How to walk over the currently iterated field.
	 */
	protected function walkField(Field $field, $context)
	{
		$method = 'walkField' . ucfirst($context['blueprint']['type']);
		$method = method_exists($this, $method) ? $method : 'walkFieldDefault';
		return $this->$method($field, $context);
	}

	protected function walkFieldDefault($field, $context)
	{
		return is_string($value = $field->value()) ? $this->walkText($value, $context) : $value;
	}

	/**
	 * How to handle text in all fields. Can be extended to parse KirbyTags.
	 */
	protected function walkText(string $text, $context)
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

	protected function walkFieldStructure($field, $context)
	{
		$data = null;
		$context['fields'] = $context['blueprint']['fields'];
		$idField = $context['blueprint']['fields']['id'] ?? null;

		foreach ($field->toStructure() as $key => $object) {
			// `$key` is either an integer or a string, depending on whether the
			// structure object has an `id` field or not.
			$objectContext = $this->subcontext($idField ? 'id' : 'key', $key, $context);
			$data[] = $this->walkFieldStructureObject($object, $objectContext);
		}

		return $data;
	}

	protected function walkFieldStructureObject($object, $context)
	{
		return $this->walkContent($object->content(), $context);
	}

	protected function walkFieldBlocks($field, $context)
	{
		$data = [];
		$blocks = $field->toBlocks();
		$sets = FormField::factory('blocks', $context['blueprint'])->fieldsets();

		foreach ($blocks as $id => $block) {
			$set = $sets->get($block->type());

			if (empty($set)) {
				throw new Error('Missing fieldset for block type ' . $block->type());
			}

			$blockContext = $this->subcontext('id', $id, $context);
			$blockContentContext = $this->subcontext('key', 'content', $blockContext);
			$blockContentContext['fields'] = $set->fields();
			$blockData = $this->walkFieldBlocksBlock($block, $blockContentContext);

			if (!empty($blockData)) {
				$data[] = $blockData;
			}
		}

		return $data;
	}

	protected function walkFieldBlocksBlock($block, $context)
	{
		$data = $block->toArray();
		$data['content'] = $this->walkContent($block->content(), $context);
		return $data;
	}

	protected function walkFieldLayout($field, $context)
	{
		$data = [];
		$layouts = $field->toLayouts();

		$formField = FormField::factory('layout', $context['blueprint']);
		$settingsFieldsets = $formField->settings();
		$blocksFieldsets = $formField->fieldsets();

		foreach ($layouts as $idLayout => $layout) {
			$layoutContext = $this->subcontext('id', $idLayout, $context);
			$columnsContext = $this->subcontext('key', 'columns', $layoutContext);
			$columnsData = [];

			foreach ($layout->columns() as $idColumn => $column) {
				$columnContext = $this->subcontext('id', $idColumn, $columnsContext);
				$blocksContext = $this->subcontext('key', 'blocks', $columnContext);
				$blocksData = [];

				foreach ($column->blocks() as $idBlock => $block) {
					$set = $blocksFieldsets->get($block->type());

					if (empty($set)) {
						throw new Error('Missing fieldset for block type ' . $block->type());
					}

					$blockContext = $this->subcontext('id', $idBlock, $blocksContext);
					$blockContentContext = $this->subcontext('key', 'content', $blockContext);
					$blockContentContext['fields'] = $set->fields();
					$blockData = $this->walkFieldBlocksBlock($block, $blockContentContext);

					if (!empty($blockData)) {
						$blocksData[] = $blockData;
					}
				}

				$columnEntry = $column->toArray();
				$columnEntry['blocks'] = $blocksData;
				$columnsData[] = $columnEntry;
			}

			$attrsContext = $this->subcontext('key', 'attrs', $layoutContext);
			$attrsContext['fields'] = $settingsFieldsets->fields();

			$layoutEntry = $layout->toArray();
			$layoutEntry['attrs'] = $this->walkContent($layout->attrs(), $attrsContext);
			$layoutEntry['columns'] = $columnsData;

			$data[] = array_filter($layoutEntry); // remove empty attrs/columns keys
		}

		return $data;
	}

	protected function walkFieldEditor($field, $context)
	{
		$data = [];
		$blocks = Json::decode($field->value());

		foreach ($blocks as $block) {
			if (is_string($id = $block['id'] ?? null)) {
				$blockContext = $this->subcontext('id', $id, $context);
				$blockData = $this->walkFieldEditorBlock($block, $blockContext);

				if (!empty($blockData)) {
					$data[] = $blockData;
				}
			}
		}

		return $data;
	}

	protected function walkFieldEditorBlock($block, $context)
	{
		if (is_string($content = $block['content'] ?? null)) {
			$block['content'] = $this->walkText($content, $context);
		}

		return $block;
	}

	protected function walkFieldTags($field, $context)
	{
		return $field->split();
	}

	protected function walkFieldToggle($field, $context)
	{
		return $field->toBool();
	}

	protected function walkFieldEntity($field, $context)
	{
		$context['fields'] = $context['blueprint']['fields'];
		return $this->walkContent($field->toEntity(), $context);
	}

	protected function walkFieldLink($field, $context)
	{
		$data = Yaml::decode($field->value());

		if (is_string($text = $data['text'] ?? null)) {
			$data['text'] = $this->walkText($text, $context);
		}

		// If the field has value `null` (not the string `"null"`), the result
		// from Yaml::decode() is an empty array, which is undesirable.
		return !empty($data) ? $data : null;
	}

	protected function walkFieldJson($field, $context)
	{
		return Json::decode($field->value());
	}
}
