<?php

namespace Oblik\Outsource;

use Oblik\Variables\Manager;

class Importer extends Walker
{
    public static $formatter = Formatter::class;
    public static $variables = Manager::class;

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

    public function fieldPredicate($field, $blueprint, $input)
    {
        return !$this::isFieldIgnored($blueprint);
    }

    public function fieldHandler($field, $blueprint, $input)
    {
        if ($field->value() === null && $input === null) {
            return null;
        }

        $merger = $blueprint[KEY]['import']['merge'] ?? null;
        $data = static::$formatter::serialize($blueprint, $field);

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
            $data = static::$formatter::deserialize($blueprint, $data);
        }

        return $data;
    }

    public function processModel($model, $data, string $lang)
    {
        $content = $model->content($lang);
        $fields = $model->blueprint()->fields();
        $blueprint = $this->processBlueprint($fields);
        $mergedData = $this->walk($content, $blueprint, $data);
        $newModel = $model->update($mergedData, $lang);

        return self::compare(
            $model->content($lang)->data(),
            $newModel->content($lang)->data()
        );
    }

    public function processVariables($data, string $lang)
    {
        $oldVariables = static::$variables::export($lang);
        static::$variables::import($lang, $data);
        $newVariables = static::$variables::export($lang);

        return self::compare($oldVariables ?? [], $newVariables ?? []);
    }

    public function process($data = [], string $lang)
    {
        $result = [];
        $site = site();

        if (!empty($data['site'])) {
            $result['site'] = $this->processModel($site, $data['site'], $lang);
        }

        if (!empty($data['pages'])) {
            $result['pages'] = [];

            foreach ($data['pages'] as $id => $pageData) {
                if ($page = $site->page($id)) {
                    $result['pages'][$id] = $this->processModel($page, $pageData, $lang);
                }
            }
        }

        if (!empty($data['files'])) {
            $result['files'] = [];

            foreach ($data['files'] as $id => $fileData) {
                if ($file = $site->file($id)) {
                    $result['files'][$id] = $this->processModel($file, $fileData, $lang);
                }
            }
        }

        if (!empty($data['variables'])) {
            $result['variables'] = $this->processVariables($data['variables'], $lang);
        }

        return $result;
    }
}
