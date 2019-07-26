<?php

namespace Oblik\Kirby\Outsource;

use Kirby\Cms\Site;
use Kirby\Cms\Pages;
use Kirby\Cms\ModelWithContent;

/**
 * Recursively walks the content of a Model, serializes it, and returns it.
 */
class Exporter extends Walker
{
    public $formatter;
    public $settings = [
        'variables' => true
    ];

    public function __construct(Formatter $formatter, $settings = [])
    {
        parent::__construct($settings);
        $this->formatter = $formatter;
    }

    public function fieldHandler($blueprint, $field, $input)
    {
        return $this->formatter->serialize($blueprint, $field);
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
