<?php
namespace Memsource;

use Yaml;

class Importer {
    private $lang = null;

    public static function clean ($data) {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = static::clean($value);

                if (count($data[$key]) === 0) {
                    unset($data[$key]);
                }
            } else {
                $hasPrintableChars = preg_match('/[^[:cntrl:]\s]/', $value);

                if (empty($value) || !$hasPrintableChars) {
                    unset($data[$key]);
                }
            }
        }

        return $data;
    }

    public static function revertKirbytagXML ($data) {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = static::revertKirbytagXML($value);
            } else if (is_string($value)) {
                $data[$key] = KirbytagXML::revert($value);
            }
        }

        return $data;
    }

    public function updatePage ($page, $data) {
        // Clean the input data so that empty strings won't overwrite the non-
        // empty default language values later.
        $data = static::clean($data);

        if (count($data) === 0) {
            return; // nothing to update
        }

        // Using defaultLang instead of $this->lang to get the original
        // content of the language. That's because it's not guaranteed that
        // the current translated content will be synced with the default
        // language one, especially with structured fields (proven).
        $currentData = $page->content($this->defaultLang->code)->data();
        $normalizedData = array();

        // Make up an array with the current data and normalize it by parsing
        // YAML and extracting field values.
        foreach ($currentData as $key => $field) {
            $normalizedData[$key] = $field->value();

            if (!empty($data[$key]) && is_array($data[$key])) {
                // If the translated value is an array, that means this
                // field was a structure when it was exported, so it must
                // still be a structure and should be parsed.

                try {
                    $normalizedData[$key] = Yaml::read($normalizedData[$key]);
                } catch (\Exception $e) {}
            }
        }

        $mergedData = array_replace_recursive($normalizedData, $data);

        // Encode all arrays back to YAML because that's how Kirby stores
        // them. If they are not pased, an empty value will be saved.
        foreach ($mergedData as $key => $value) {
            if (is_array($value)) {
                $mergedData[$key] = Yaml::encode($mergedData[$key]);
            }
        }

        $page->update(static::revertKirbytagXML($mergedData), $this->lang);
    }

    public function importPages ($data) {
        foreach ($data as $pageId => $value) {
            $page = null;

            if ($pageId === '$site') {
                $page = site();
            } else {
                $page = site()->children()->find($pageId);
            }

            if ($page) {
                $this->updatePage($page, $value, $this->lang);
            }
        }
    }

    public function importVariables ($data) {
        $dir = kirby()->roots()->languages();

        if (!is_dir($dir)) {
            mkdir($dir);
        }

        $file = $dir . DS . $this->lang . '.yml';
        $encoded = Yaml::encode($data);

        file_put_contents($file, $encoded);
    }

    public function import ($data, $lang) {
        $this->lang = $lang;
        $this->defaultLang = site()->defaultLanguage();

        if (isset($data['content'])) {
            $data = $data['content'];
        }

        if (isset($data['pages'])) {
            $this->importPages($data['pages']);
        }

        if (isset($data['variables'])) {
            $this->importVariables($data['variables']);
        }
    }
}
