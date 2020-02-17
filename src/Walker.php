<?php

namespace Oblik\Outsource;

use Exception;
use Throwable;
use Kirby\Cms\Content;
use Kirby\Cms\Field;
use Kirby\Cms\Structure;

class Walker
{
    public $blueprint;
    public $fields;

    public function __construct($config = [])
    {
        $this->blueprint = $config['blueprint'] ?? [];
        $this->fields = $config['fields'] ?? [];
    }

    /**
     * Formats a blueprint by adding custom fields and changing the keys to
     * lowercase since that's what Kirby internally uses.
     */
    public function processBlueprint(array $blueprint)
    {
        $blueprint = array_replace_recursive($blueprint, $this->blueprint);
        $blueprint = array_change_key_case($blueprint, CASE_LOWER);
        return $blueprint;
    }

    /**
     * Merges blueprint settings in `outsource` from the Walker instance with
     * those in the field blueprint.
     */
    public function processFieldBlueprint(array $blueprint)
    {
        $config = $blueprint[KEY] ?? [];

        $fieldType = $blueprint['type'] ?? null;
        $fieldConfig = $this->fields[$fieldType] ?? null;

        if ($fieldConfig) {
            $config = array_replace($fieldConfig, $config);
        }

        $blueprint[KEY] = $config;
        return $blueprint;
    }

    /**
     * Determines if a field should be included in the resulting data.
     */
    public function fieldPredicate(Field $field, $blueprint, $input)
    {
        $ignored = $blueprint[KEY]['ignore'] ?? false;
        return !$ignored;
    }

    /**
     * Determines what data to return for this field in the result.
     */
    public function fieldHandler(Field $field, array $blueprint, $input)
    {
        return $field->value();
    }

    /**
     * Determines what data to return for this structure in the result.
     */
    public function structureHandler(Field $field, array $blueprint, $input)
    {
        $structure = $field->toStructure();
        $sync = $blueprint[KEY]['sync'] ?? false;
        $fields = $blueprint['fields'] ?? [];
        $fieldsBlueprint = $this->processBlueprint($fields);

        return $this->walkStructure($structure, $fieldsBlueprint, $input, $sync);
    }

    public function walk(Content $content, array $fieldsBlueprint = [], $input = [])
    {
        $data = null;

        foreach ($fieldsBlueprint as $key => $fieldBlueprint) {
            $field = $content->$key();
            $inputData = $input[$key] ?? null;
            $fieldBlueprint = $this->processFieldBlueprint($fieldBlueprint);

            try {
                $fieldData = $this->walkField($field, $fieldBlueprint, $inputData);
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

    public function walkField(Field $field, array $blueprint, $input)
    {
        $data = null;

        if ($this->fieldPredicate($field, $blueprint, $input)) {
            if ($blueprint['type'] === 'structure') {
                $data = $this->structureHandler($field, $blueprint, $input);
            } else {
                $data = $this->fieldHandler($field, $blueprint, $input);
            }
        }

        return $data;
    }

    public function walkStructure(Structure $structure, array $fieldsBlueprint, $input, $sync)
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
            $childData = $this->walk($content, $fieldsBlueprint, $inputEntry);

            if (!empty($childData)) {
                if ($sync) {
                    $childData[$sync] = $id;
                }

                $data[] = $childData;
            }
        }

        return $data;
    }
}
