<?php

namespace KirbyOutsource;

class Walker
{
    public $settings = [
        'language' => null,
        'fieldPredicate' => null,
        'fieldHandler' => null,
        'structureHandler' => null,
        'blueprints' => [],
        'fields' => []
    ];

    public function __construct($settings = [])
    {
        $this->settings = array_replace($this->settings, $settings);
    }

    public static function isFieldIgnored(array $blueprint)
    {
        return $blueprint[BLUEPRINT_KEY][BLUEPRINT_IGNORE_KEY] ?? false;
    }

    public static function fieldPredicate (array $blueprint, $field) {
        return (!self::isFieldIgnored($blueprint) && !$field->isEmpty());
    }

    public function fieldHandler (array $fieldBlueprints, \Kirby\Cms\Field $field) {
        return $field->value();
    }

    public function structureHandler (array $fieldBlueprints, \Kirby\Cms\Structure $structure, $input = null)
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
    public function processBlueprint(array $blueprint)
    {
        $customBlueprints = $this->settings['blueprints'];
        $customFields = $this->settings['fields'];

        $blueprint = array_replace_recursive($blueprint, $customBlueprints);
        $blueprint = array_change_key_case($blueprint, CASE_LOWER);

        foreach ($blueprint as $key => $value) {
            $fieldType = $value['type'] ?? null;
            $fieldData = $customFields[$fieldType] ?? null;

            if ($fieldData) {
                $blueprint[$key] = array_replace_recursive($blueprint[$key], $fieldData);
            }
        }

        return $blueprint;
    }

    public function walkField(array $blueprint, \Kirby\Cms\Field $field, $input = null)
    {
        $data = null;
        $checkField = $this->settings['fieldPredicate'] ?? [$this, 'fieldPredicate'];
        $processField = $this->settings['fieldHandler'] ?? [$this, 'fieldHandler'];
        $processStructure = $this->settings['structureHandler'] ?? [$this, 'structureHandler'];

        if ($checkField($blueprint, $field, $input)) {
            if ($blueprint['type'] === 'structure') {
                $blueprint = $this->processBlueprint($blueprint['fields']);
                $field = $field->toStructure();
                $data = $processStructure($blueprint, $field, $input);
            } else {
                $data = $processField($blueprint, $field, $input);
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
    public function walk(\Kirby\Cms\Model $model, $input = [], array $blueprint = null)
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
