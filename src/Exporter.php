<?php

namespace Oblik\Outsource;

use Kirby\Cms\Site;
use Kirby\Cms\Pages;
use Kirby\Cms\ModelWithContent;

/**
 * Recursively walks the content of a Model, serializes it, and returns it.
 */
class Exporter extends Walker
{
    public $settings = [
        'formatter' => Formatter::class,
        'variables' => true
    ];

    public static function filter(array $data, array $settings)
    {
        $keys = $settings['keys'];
        $inclusive = $settings['inclusive'] ?? true;
        $recursive = $settings['recursive'] ?? true;

        foreach ($data as $key => &$value) {
            $matched = in_array($key, $keys, true);
            $unset = $inclusive !== $matched;

            if ($recursive && is_array($value)) {
                $value = self::filter($value, $settings);
                $unset = count($value) <= 0;
            }

            if ($unset) {
                unset($data[$key]);
            }
        }

        return $data;
    }

    public function fieldHandler($blueprint, $field, $input)
    {
        $data = $this->settings['formatter']::serialize($blueprint, $field);
        $filter = $blueprint[BLUEPRINT_KEY]['export']['filter'] ?? null;

        if (is_array($data) && is_array($filter)) {
            $data = self::filter($data, $filter);
        }

        return $data;
    }

    /**
     * Extracts all content of a Model. Can be used by its own in case you need to
     * export a single page.
     */
    public function extractModel(ModelWithContent $model)
    {
        $data = $this->walk($model);
        $files = [];

        foreach ($model->files() as $file) {
            $fileId = $file->id();
            $fileData = $this->walk($file);

            if (!empty($fileData)) {
                $files[$fileId] = $fileData;
            }
        }

        return [
            'content' => $data,
            'files' => $files,
        ];
    }

    public function export($model)
    {
        $data = [];
        $isPages = is_a($model, Pages::class);

        if (!$isPages) {
            // prepend() to include a page's own data in the export.
            $modelPages = $model->index()->prepend($model);
        } else {
            $modelPages = $model;
        }

        $site = null;
        $pages = [];
        $files = [];

        foreach ($modelPages as $childModel) {
            $isModel = is_subclass_of($childModel, ModelWithContent::class);
            $isSite = is_a($childModel, Site::class);

            if ($isModel) {
                $childModelData = $this->extractModel($childModel);

                if ($isSite) {
                    $site = $childModelData['content'];
                } else {
                    $pageId = $childModel->id();

                    if (!empty($childModelData['content'])) {
                        $pages[$pageId] = $childModelData['content'];
                    }
                }

                $files = array_replace($files, $childModelData['files']);
            }
        }

        if ($site) {
            $data['site'] = $site;
        }

        if (!empty($pages)) {
            $data['pages'] = $pages;
        }

        if (!empty($files)) {
            $data['files'] = $files;
        }

        if ($this->settings['variables']) {
            $variables = Variables::get($this->settings['language']);

            if (!empty($variables)) {
                $data['variables'] = $variables;
            }
        }

        return $data;
    }
}
