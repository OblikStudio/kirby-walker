<?php

namespace Oblik\Outsource;

class Importer extends Walker
{
    public $settings = [
        'formatter' => Formatter::class
    ];

    public function fieldPredicate($blueprint, $field, $input)
    {
        return !$this::isFieldIgnored($blueprint);
    }

    public function fieldHandler($blueprint, $field, $input)
    {
        if ($field->value() === null && $input === null) {
            return null;
        }

        $merger = $blueprint[BLUEPRINT_KEY]['import']['merge'] ?? null;
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

    public function update($model, $data)
    {
        $mergedData = $this->walk($model, $data);
        $model->writeContent($mergedData, $this->settings['language']);
    }

    public function import($data)
    {
        $site = site();

        if (!empty($data['site'])) {
            $this->update($site, $data['site']);
        }

        if (!empty($data['pages'])) {
            foreach ($data['pages'] as $id => $pageData) {
                $page = $site->page($id);

                if ($page) {
                    $this->update($page, $pageData);
                }
            }
        }

        if (!empty($data['files'])) {
            foreach ($data['files'] as $id => $fileData) {
                $file = $site->file($id);

                if ($file) {
                    $this->update($file, $fileData);
                }
            }
        }

        if (!empty($data['variables'])) {
            Variables::update($this->settings['language'], $data['variables']);
        }

        return true;
    }
}
