<?php

namespace Oblik\Walker\Walker;

use const Oblik\Walker\KEY;

use Kirby\Cms\Site;
use Kirby\Cms\Pages;
use Kirby\Cms\ModelWithContent;
use Oblik\Variables\Manager;

/**
 * Walks over Site, Page, and Pages objects, returns their data, and their
 * files' data. Can also export language variables.
 */
class Exporter extends Walker
{
	/**
	 * Object that holds the exported data.
	 */
	private $model;

	public function __construct($config = [])
	{
		parent::__construct($config);
		$this->model = new Model();
	}

	/**
	 * Removes keys from fields that have array values, based on a field's
	 * walker settings.
	 */
	protected static function filter(array $data, array $settings)
	{
		$keys = $settings['keys'];
		$numeric = $settings['numeric'] ?? true;
		$inclusive = $settings['inclusive'] ?? true;
		$recursive = $settings['recursive'] ?? true;

		foreach ($data as $key => &$value) {
			$matched = in_array($key, $keys, true);
			$unset = $inclusive !== $matched;

			if ($recursive && is_array($value)) {
				$value = static::filter($value, $settings);
				$unset = $value === null;
			}

			if (is_int($key) && $numeric) {
				$unset = false;

				if ($value === null) {
					$value = [];
				}
			}

			if ($unset) {
				unset($data[$key]);
			}
		}

		if (count($data) === 0) {
			$data = null;
		}

		return $data;
	}

	protected function fieldHandler($field, $settings, $input)
	{
		$data = static::$formatter::serialize($settings, $field);
		$filter = $settings[KEY]['export']['filter'] ?? null;

		if (is_array($data) && is_array($filter)) {
			$data = static::filter($data, $filter);
		}

		return $data;
	}

	public function exportModel(ModelWithContent $model, string $lang = null, bool $children = true)
	{
		$data = $this->walkModel($model, $lang);

		if (is_a($model, Site::class)) {
			$this->model->setSite($data);
		} else {
			$this->model->addPage($model->id(), $data);
		}

		if (method_exists($model, 'children') && $children) {
			foreach ($model->children() as $page) {
				$this->exportModel($page, $lang);
			}
		}

		if (method_exists($model, 'files')) {
			foreach ($model->files() as $file) {
				$fileData = $this->walkModel($file, $lang);
				$this->model->addFile($file->id(), $fileData);
			}
		}

		return $data;
	}

	public function exportVariables(string $lang)
	{
		$data = Manager::export($lang);
		$this->model->setVariables($data);
		return $data;
	}

	public function export($input, string $lang = null, bool $children = true)
	{
		if (is_subclass_of($input, ModelWithContent::class)) {
			$this->exportModel($input, $lang, $children);
		} else if (is_a($input, Pages::class)) {
			foreach ($input as $page) {
				$this->exportModel($page, $lang, $children);
			}
		}
	}

	/**
	 * Returns the accumulated export data.
	 */
	public function data()
	{
		return $this->model->toArray();
	}
}
