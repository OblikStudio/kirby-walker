<?php

namespace Oblik\Outsource;

use Oblik\Variables\Manager;

class Importer extends Walker
{
    public $settings = [
        'formatter' => Formatter::class,
        'variables' => Manager::class
    ];

    public static function compare(array $old, array $new)
    {
        $keys = array_replace(array_keys($new), array_keys($old));
        $data = null;

        foreach ($keys as $key) {
            $newValue = $new[$key] ?? null;
            $oldValue = $old[$key] ?? null;
            $entry = null;

            if (is_array($newValue) && is_array($oldValue)) {
                $entry = self::compare($oldValue, $newValue);
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

    public function fieldPredicate($field, $input)
    {
        return !$this::isFieldIgnored($this->blueprint());
    }

    public function fieldHandler($field, $input)
    {
        if ($field->value() === null && $input === null) {
            return null;
        }

        $blueprint = $this->blueprint();
        $merger = $this->blueprintSetting('import')['merge'] ?? null;
        $data = $this->settings['formatter']::serialize($blueprint, $field);

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
            $data = $this->settings['formatter']::deserialize($blueprint, $data);
        }

        return $data;
    }

    /**
     * @param \Kirby\Cms\Page $model
     */
    public function processModel($model, $data)
    {
        $lang = $this->settings[BP_LANGUAGE];

        $mergedData = $this->walk($model, $data);
        $newModel = $model->update($mergedData, $lang);

        return self::compare(
            $model->content($lang)->data(),
            $newModel->content($lang)->data()
        );
    }

    public function processVariables($data)
    {
        $lang = $this->settings[BP_LANGUAGE];

        $oldVariables = $this->settings['variables']::export($lang);
        $this->settings['variables']::import($lang, $data);
        $newVariables = $this->settings['variables']::export($lang);

        return self::compare($oldVariables ?? [], $newVariables ?? []);
    }

    public function process($data = [])
    {
        $result = [];
        $site = site();

        if (!empty($data['site'])) {
            $result['site'] = $this->processModel($site, $data['site'], 'site');
        }

        if (!empty($data['pages'])) {
            $result['pages'] = [];

            foreach ($data['pages'] as $id => $pageData) {
                if ($page = $site->page($id)) {
                    $result['pages'][$id] = $this->processModel($page, $pageData, 'page');
                }
            }
        }

        if (!empty($data['files'])) {
            $result['files'] = [];

            foreach ($data['files'] as $id => $fileData) {
                if ($file = $site->file($id)) {
                    $result['files'][$id] = $this->processModel($file, $fileData, 'file');
                }
            }
        }

        if (!empty($data['variables'])) {
            $result['variables'] = $this->processVariables($data['variables']);
        }

        return $result;
    }
}
