<?php

namespace KirbyOutsource;

use Kirby\Cms\ModelWithContent;

/**
 * Recursively walks the content of a Model, serializes it, and returns it.
 */
class Exporter
{
    public $settings = [
        'language' => null,
        'variables' => true,
        'blueprints' => [],
        'fields' => [],
        'fieldPredicate' => null,
    ];

    public function __construct(array $settings = [])
    {
        $this->settings = array_replace($this->settings, $settings);
        $this->walker = new Walker($this->settings, ['KirbyOutsource\Formatter', 'extract']);
    }

    /**
     * Extracts all content of a Model. Can be used by its own in case you need to
     * export a single page.
     */
    public function extractModel(ModelWithContent $model): array
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
        $isPages = is_a($model, 'Kirby\Cms\Pages');

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
            $isModel = is_subclass_of($childModel, 'Kirby\Cms\ModelWithContent');
            $isSite = is_a($childModel, 'Kirby\Cms\Site');

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
