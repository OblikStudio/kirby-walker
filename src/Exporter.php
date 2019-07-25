<?php

namespace Oblik\Kirby\Outsource;

use Kirby\Cms\Site;
use Kirby\Cms\Pages;
use Kirby\Cms\ModelWithContent;

/**
 * Recursively walks the content of a Model, serializes it, and returns it.
 */
class Exporter
{
    public $settings = [
        'variables' => true,
    ];

    public function __call($name, $arguments)
    {
        return $this->settings[$name] ?? null;
    }

    public function __construct(array $settings = [])
    {
        $this->settings = array_replace($this->settings, $settings);
        $this->formatter = new Formatter();
        $this->walker = new Walker([
            'language' => $this->language(),
            'blueprints' => $this->blueprints(),
            'fields' => $this->fields(),
            'fieldPredicate' => $this->fieldPredicate(),
            'fieldHandler' => [$this->formatter, 'serialize']
        ]);
    }

    /**
     * Extracts all content of a Model. Can be used by its own in case you need to
     * export a single page.
     */
    public function extractModel(ModelWithContent $model)
    {
        $data = $this->walker->walk($model);
        $files = [];

        foreach ($model->files() as $file) {
            $fileId = $file->id();
            $fileData = $this->walker->walk($file);

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
            $variables = Variables::get($this->language());

            if (!empty($variables)) {
                $data['variables'] = $variables;
            }
        }

        return $data;
    }
}
