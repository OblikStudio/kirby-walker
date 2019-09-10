<?php

namespace Oblik\Outsource;

class Importer extends Walker
{
    use SiteData;

    public $settings = [
        'formatter' => Formatter::class
    ];

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

    public function processModel($model, $data)
    {
        $mergedData = $this->walk($model, $data);
        $model->update($mergedData, $this->settings['language']);
    }

    public function processVariables($data, string $driver)
    {
        $driver::import($this->settings['language'], $data);
    }
}
