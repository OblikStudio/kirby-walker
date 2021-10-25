<?php

namespace Oblik\Walker\Walker;

use Error;
use Kirby\Cms\Field;
use Kirby\Cms\ModelWithContent;
use Kirby\Data\Json;
use Kirby\Form\Field as FormField;

class Importer extends Walker
{
	public function walk(ModelWithContent $model, string $lang = null, $input = [])
	{
		if (kirby()->multilang()) {
			$lang = kirby()->defaultLanguage()->code();
		}

		return parent::walk($model, $lang, $input);
	}

	protected function walkField(Field $field, array $settings, $input)
	{
		if ($settings['translate'] !== false) {
			return parent::walkField($field, $settings, $input);
		} else {
			return $field->value();
		}
	}

	protected function walkFieldDefault($field, $settings, $input)
	{
		return !empty($input) ? $input : $field->value();
	}

	protected function walkFieldBlocks($field, $settings, $input)
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
			$childData['content'] = $this->walkContent($block->content(), $set->fields(), $input[$id]['content'] ?? null);

			if (!empty($childData['content'])) {
				$data[] = $childData;
			}
		}

		return $data;
	}

	protected function walkFieldEditor($field, $settings, $input)
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
}
