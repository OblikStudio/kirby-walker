<?php

namespace Oblik\Outsource;

use Kirby\Cms\Site;
use Kirby\Cms\Pages;
use Kirby\Cms\ModelWithContent;
use Oblik\Variables\Manager;

/**
 * Recursively walks the content of a Model, serializes it, and returns it.
 */
class Exporter extends Walker
{
    public static $formatter = Formatter::class;
    public static $variables = Manager::class;

    public static function filter(array $data, array $settings)
    {
        $keys = $settings['keys'];
        $numeric = $settings['numeric'] ?? true;
        $inclusive = $settings['inclusive'] ?? true;
        $recursive = $settings['recursive'] ?? true;

        foreach ($data as $key => &$value) {
            $matched = in_array($key, $keys, true);
            $unset = $inclusive !== $matched;

            if ($recursive && is_array($value)) {
                $value = self::filter($value, $settings);
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

    public function fieldHandler($field, $input)
    {
        $data = static::$formatter::serialize($this->blueprint(), $field);
        $filter = $this->blueprintSetting('export')['filter'] ?? null;

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

    /**
     * @param ModelWithContent|Pages|array $input
     */
    public function export($input)
    {
        $data = [];
        $targets = [];

        if (is_a($input, ModelWithContent::class)) {
            $targets[] = $input;
            $input = $input->index();
        }

        if (is_a($input, Pages::class)) {
            $targets = array_merge($targets, $input->values());
        } else if (is_array($input)) {
            $targets = array_merge($targets, $input);
        }

        $site = null;
        $pages = [];
        $files = [];

        foreach ($targets as $model) {
            if (is_subclass_of($model, ModelWithContent::class)) {
                $modelData = $this->extractModel($model);

                if (!empty($modelData['content'])) {
                    if (is_a($model, Site::class)) {
                        $site = $modelData['content'];
                    } else {
                        $pages[$model->id()] = $modelData['content'];
                    }
                }

                $files = array_replace($files, $modelData['files']);
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

        if ($lang = $this->settings[BP_LANGUAGE] ?? null) {
            if ($variables = static::$variables::export($lang)) {
                $data['variables'] = $variables;
            }
        }

        return $data;
    }
}
