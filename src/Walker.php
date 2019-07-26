<?php

namespace Oblik\Kirby\Outsource;

use Kirby\Cms\Model;

class Walker
{
    public $settings = [];

    public function __construct($settings = [])
    {
        $this->settings = array_replace([
            'language' => null,
            'blueprints' => [],
            'fields' => []
        ], $this->settings, $settings);
    }

    public static function isFieldIgnored(array $blueprint)
    {
        return $blueprint[BLUEPRINT_KEY][BLUEPRINT_IGNORE_KEY] ?? false;
    }

    /**
     * Determines if a field should be included in the resulting data.
     */
    public function fieldPredicate($blueprint, $field, $input)
    {
        return (!$this::isFieldIgnored($blueprint) && !$field->isEmpty());
    }

    /**
     * Determines what data to return for this field in the result.
     */
    public function fieldHandler($blueprint, $field, $input)
    {
        return $field->value();
    }

    /**
     * Walks over structure field entries and returns their result.
     */
    public function structureHandler($fieldBlueprints, $structure, $input = [])
    {
        $data = null;

        foreach ($structure as $index => $entry) {
            $inputData = $input[$index] ?? null;
            $childData = $this->walk($entry, $inputData, $fieldBlueprints);

            if (!empty($childData)) {
                $data[] = $childData;
            }
        }

        return $data;
    }

    /**
     * Merges model blueprint according to specified configuration.
     */
    public function processBlueprint($blueprint)
    {
        $customBlueprints = $this->settings['blueprints'];
        $customFields = $this->settings['fields'];

        $blueprint = array_replace_recursive($blueprint, $customBlueprints);
        $blueprint = array_change_key_case($blueprint, CASE_LOWER);

        foreach ($blueprint as $fieldName => $data) {
            $fieldType = $data['type'] ?? null;
            $currentConfig = $data[BLUEPRINT_KEY] ?? [];
            $fieldConfig = $customFields[$fieldType] ?? null;

            if ($fieldConfig) {
                $blueprint[$fieldName][BLUEPRINT_KEY] = array_replace($fieldConfig, $currentConfig);
            }
        }

        return $blueprint;
    }

    public function walkField($blueprint, $field, $input)
    {
        $data = null;

        if ($this->fieldPredicate($blueprint, $field, $input)) {
            if ($blueprint['type'] === 'structure') {
                $blueprint = $this->processBlueprint($blueprint['fields']);
                $field = $field->toStructure();
                $data = $this->structureHandler($blueprint, $field, $input);
            } else {
                $data = $this->fieldHandler($blueprint, $field, $input);
            }
        }

        return $data;
    }

    /**
     * Recursively walks over a Model.
     * @param Kirby\Cms\Model $model
     * @param array $input optional input for each with the same structure as
     * the model
     * @param array|null $blueprint used for models without a blueprint() method
     * like StructureObject
     * @return array|null
     */
    public function walk(Model $model, $input = [], $blueprint = null)
    {
        if (!$blueprint) {
            $blueprint = $this->processBlueprint(
                $model->blueprint()->fields()
            );
        }

        $data = null;
        $content = $model->content($this->settings['language']);

        foreach ($blueprint as $key => $fieldBlueprint) {
            $field = $content->$key();
            $inputData = $input[$key] ?? null;
            $fieldData = $this->walkField($fieldBlueprint, $field, $inputData);

            if ($fieldData !== null) {
                $data[$key] = $fieldData;
            }
        }

        return $data;
    }
}
