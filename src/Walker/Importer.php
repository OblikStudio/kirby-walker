<?php

namespace Oblik\Walker\Walker;

use const Oblik\Walker\KEY;

use Kirby\Cms\Field;
use Kirby\Cms\ModelWithContent;
use Oblik\Variables\Manager;

/**
 * Takes data in the walker model format, updates Kirby models according to it,
 * and returns an array containing the old and new value of each updated field.
 */
class Importer extends Walker
{
	/**
	 * Iterates over two arrays and returns one containing the values of both.
	 */
	protected static function compare(array $old, array $new)
	{
		$keys = array_replace(array_keys($new), array_keys($old));
		$data = null;

		foreach ($keys as $key) {
			$newValue = $new[$key] ?? null;
			$oldValue = $old[$key] ?? null;
			$entry = null;

			if (is_array($newValue) && is_array($oldValue)) {
				$entry = static::compare($oldValue, $newValue);
			} else if ($newValue !== $oldValue) {
				$entry = [
					'$old' => $oldValue,
					'$new' => $newValue
				];
			}

			if ($entry !== null) {
				$data[$key] = $entry;
			}
		}

		return $data;
	}

	protected function fieldHandler(Field $field, array $settings, $input)
	{
		if ($field->value() === null && $input === null) {
			return null;
		}

		$merger = $settings[KEY]['import']['merge'] ?? null;
		$data = static::$formatter::serialize($settings, $field);

		if (is_callable($merger)) {
			$data = $merger($data, $input);
		} else {
			if (is_array($input) && is_array($data)) {
				$data = array_replace_recursive($data, $input);
			} else if ($input !== null) {
				$data = $input;
			}
		}

		if ($data !== null) {
			$data = static::$formatter::deserialize($settings, $data);
		}

		return $data;
	}

	public function importModel(ModelWithContent $model, array $data, string $lang = null)
	{
		$mergedData = $this->walkModel($model, $lang, $data);
		$newModel = $model->update($mergedData, $lang);

		return static::compare(
			$model->content($lang)->data(),
			$newModel->content($lang)->data()
		);
	}

	public function importVariables(array $data, string $lang)
	{
		$oldVariables = Manager::export($lang);
		Manager::import($lang, $data);
		$newVariables = Manager::export($lang);

		return static::compare($oldVariables ?? [], $newVariables ?? []);
	}

	public function import(array $data, string $lang = null)
	{
		$model = new Model($data);
		$result = new Model();
		$site = site();

		$siteResult = $this->importModel($site, $model->site(), $lang);
		$result->setSite($siteResult);

		foreach ($model->pages() as $key => $pageData) {
			$page = $site->page($key);
			$pageResult = $this->importModel($page, $pageData, $lang);
			$result->addPage($key, $pageResult);
		}

		foreach ($model->files() as $key => $fileData) {
			$file = $site->file($key);
			$fileResult = $this->importModel($file, $fileData, $lang);
			$result->addFile($key, $fileResult);
		}

		$variablesResult = $this->importVariables($model->variables(), $lang);
		$result->setVariables($variablesResult);

		return $result->toArray();
	}
}
