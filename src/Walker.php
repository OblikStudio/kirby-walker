<?php

namespace Oblik\Outsource;

use Exception;
use Throwable;
use Kirby\Cms\Content;
use Kirby\Cms\Field;
use Kirby\Cms\Structure;

/**
 * Base class for walkers that need to recursively iterate over Kirby models.
 */
class Walker
{
    /**
     * Array with fields that are added artificially to each blueprint as if
     * they were actually in it. Useful for the `title` field that Kirby
     * automatically adds to models.
     */
    private $blueprint;

    /**
     * Array that is used to add default outsource settings for most common
     * field types to avoid having to do that in each blueprint.
     */
    private $fields;

    public function __construct($config = [])
    {
        $this->blueprint = $config['blueprint'] ?? [];
        $this->fields = $config['fields'] ?? [];
    }

    /**
     * Formats a blueprint by adding artificial fields and changing the keys to
     * lowercase since that's what Kirby internally uses.
     */
    public function processBlueprint(array $blueprint)
    {
        $blueprint = array_replace_recursive($blueprint, $this->blueprint);
        $blueprint = array_change_key_case($blueprint, CASE_LOWER);
        return $blueprint;
    }

    /**
     * Adds default outsource values to field settings.
     */
    public function processFieldSettings(array $settings)
    {
        $config = $settings[KEY] ?? [];
        $fieldType = $settings['type'] ?? null;
        $fieldConfig = $this->fields[$fieldType] ?? null;

        if ($fieldConfig) {
            $config = array_replace($fieldConfig, $config);
        }

        $settings[KEY] = $config;
        return $settings;
    }

    /**
     * Whether a field should be included in the resulting data.
     */
    protected function fieldPredicate(Field $field, $settings, $input)
    {
        $ignored = $settings[KEY]['ignore'] ?? false;
        return !$ignored;
    }

    /**
     * What data to return for this field in the result.
     */
    protected function fieldHandler(Field $field, array $settings, $input)
    {
        return $field->value();
    }

    /**
     * What data to return for this structure field in the result.
     */
    protected function structureHandler(Structure $structure, array $blueprint, $input, $sync)
    {
        $data = null;

        if ($sync && is_array($input)) {
            $input = array_column($input, null, $sync);
        }

        foreach ($structure as $id => $entry) {
            if ($sync) {
                $inputEntry = $input[$entry->$sync()] ?? null;
            } else {
                $inputEntry = $input[$id] ?? null;
            }

            $content = $entry->content();
            $childData = $this->walk($content, $blueprint, $inputEntry);

            if (!empty($childData)) {
                if ($sync) {
                    $childData[$sync] = $id;
                }

                $data[] = $childData;
            }
        }

        return $data;
    }

    /**
     * How to walk over the currently iterated field.
     */
    protected function walkField(Field $field, array $settings, $input)
    {
        $data = null;

        if ($this->fieldPredicate($field, $settings, $input)) {
            if ($settings['type'] === 'structure') {
                $data = $this->walkStructure($field, $settings, $input);
            } else {
                $data = $this->fieldHandler($field, $settings, $input);
            }
        }

        return $data;
    }

    /**
     * How to walk over the currently iterated structure field.
     */
    protected function walkStructure(Field $field, array $settings, $input)
    {
        $sync = $settings[KEY]['sync'] ?? false;
        $fields = $settings['fields'] ?? [];
        $blueprint = $this->processBlueprint($fields);

        return $this->structureHandler($field->toStructure(), $blueprint, $input, $sync);
    }

    /**
     * Iterates over the fields in a Content object based on a blueprint array and
     * returns data for each field.
     */
    public function walk(Content $content, array $blueprint = [], $input = [])
    {
        $data = null;

        foreach ($blueprint as $key => $settings) {
            $field = $content->$key();
            $fieldInput = $input[$key] ?? null;
            $settings = $this->processFieldSettings($settings);

            try {
                $fieldData = $this->walkField($field, $settings, $fieldInput);
            } catch (Throwable $e) {
                $fieldName = $field->key();
                $errorName = $e->getMessage();

                throw new Exception("Could not process $fieldName: $errorName");
            }

            if ($fieldData !== null) {
                $data[$key] = $fieldData;
            }
        }

        return $data;
    }
}
