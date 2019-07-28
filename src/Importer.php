<?php

namespace Oblik\Kirby\Outsource;

class Importer extends Walker
{
    public $formatter;

    public function __construct(Formatter $formatter, $settings = [])
    {
        parent::__construct($settings);
        $this->formatter = $formatter;
    }

    public function fieldPredicate($blueprint, $field, $input)
    {
        return !$this::isFieldIgnored($blueprint);
    }

    public function fieldHandler($blueprint, $field, $input)
    {
        if ($field->value() === null && !$input) {
            return null;
        }

        $data = $this->formatter->serialize($blueprint, $field);

        if (is_array($input) && is_array($data)) {
            $data = array_replace_recursive($data, $input);
        } else if ($input) {
            $data = $input;
        }

        if ($data !== null) {
            $data = $this->formatter->deserialize($blueprint, $data);
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
