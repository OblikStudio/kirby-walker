<?php

namespace Oblik\Outsource;

use Exception;

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

    public function structureHandler($blueprint, $field, $input)
    {
        $sync = $blueprint[BLUEPRINT_KEY]['sync'] ?? null;
        $field = $field->toStructure();
        $fieldBlueprints = $this->processBlueprint($blueprint['fields'], $field);

        if (is_string($sync)) {
            return $this->walkStructureSync($fieldBlueprints, $field, $input, $sync);
        } else {
            return $this->walkStructure($fieldBlueprints, $field, $input);
        }
    }

    public function walkStructureSync($fieldBlueprints, $structure, $input = [], string $sync)
    {
        $data = null;
        $input = array_column($input, null, $sync);

        foreach ($structure as $entry) {
            $syncValue = $entry->$sync() ?? null;
            $inputEntry = $input[$syncValue] ?? null;

            if ($inputEntry) {
                $entry = $this->walk($entry, $inputEntry, $fieldBlueprints);
            } else {
                $entry = $entry->content()->toArray();
            }

            $data[] = $entry;
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
            $driver = $this->settings['variables'] ?? null;

            if ($driver) {
                $driver::import($this->settings['language'], $data['variables']);
            } else {
                throw new Exception('No variables driver provided');
            }
        }

        return true;
    }
}
