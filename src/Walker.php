<?php

namespace Oblik\Outsource;

use Kirby\Cms\Model;
use Kirby\Cms\Structure;

class Walker
{
    public $settings = [];

    public static function isFieldIgnored(array $blueprint)
    {
        return $blueprint[BLUEPRINT_KEY][BP_IGNORE] ?? false;
    }

    public function __construct($settings = [])
    {
        $this->settings = array_replace([
            'language' => null,
            BP_BLUEPRINT => [],
            BP_FIELDS => []
        ], $this->settings, $settings);
    }

    /**
     * Merges model blueprint according to specified configuration.
     */
    public function processBlueprint($blueprint, $parent)
    {
        $customBlueprint = $this->settings[BP_BLUEPRINT];
        $customFields = $this->settings[BP_FIELDS];

        $blueprint = array_replace_recursive($blueprint, $customBlueprint);
        $blueprint = array_change_key_case($blueprint, CASE_LOWER);

        $inStructure = is_a($parent, Structure::class);

        foreach ($blueprint as $fieldName => &$data) {
            $config = $data[BLUEPRINT_KEY] ?? [];
            $config['isStructureField'] = $inStructure;

            $fieldType = $data['type'] ?? null;
            $fieldConfig = $customFields[$fieldType] ?? null;

            if ($fieldConfig) {
                $config = array_replace($fieldConfig, $config);
            }

            $data[BLUEPRINT_KEY] = $config;
        }

        return $blueprint;
    }

    /**
     * Determines if a field should be included in the resulting data.
     */
    public function fieldPredicate($blueprint, $field, $input)
    {
        return (!$this::isFieldIgnored($blueprint) && $field->value() !== null);
    }

    /**
     * Determines what data to return for this field in the result.
     */
    public function fieldHandler($blueprint, $field, $input)
    {
        return $field->value();
    }

    public function structureHandler($blueprint, $field, $input)
    {
        $field = $field->toStructure();
        $fieldBlueprints = $this->processBlueprint($blueprint['fields'], $field);
        $sync = $blueprint[BLUEPRINT_KEY]['sync'] ?? null;

        return $this->walkStructure($fieldBlueprints, $field, $input, $sync);
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
                $model->blueprint()->fields(),
                $model
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

    public function walkField($blueprint, $field, $input)
    {
        $data = null;

        if ($this->fieldPredicate($blueprint, $field, $input)) {
            if ($blueprint['type'] === 'structure') {
                $data = $this->structureHandler($blueprint, $field, $input);
            } else {
                $data = $this->fieldHandler($blueprint, $field, $input);
            }
        }

        return $data;
    }

    /**
     * Walks over structure field entries and returns their result.
     */
    public function walkStructure($fieldBlueprints, Structure $structure, $input = [], string $sync = null)
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

            $childData = $this->walk($entry, $inputEntry, $fieldBlueprints);

            if (!empty($childData)) {
                $data[] = $childData;
            }
        }

        return $data;
    }
}
