<?php

namespace Oblik\Outsource;

use Kirby\Cms\Model;
use Kirby\Cms\Structure;
use Kirby\Cms\StructureObject;

class Walker
{
    /**
     * Recursion level.
     */
    public $level = 0;

    /**
     * Levels at which the model is a structure entry.
     */
    public $structureLevels = [];

    public $blueprints = [];

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
     * Checks if the currently walked model is inside a structure.
     */
    public function inStructure()
    {
        return count($this->structureLevels) > 0;
    }

    public function blueprint($key = null)
    {
        $data = $this->blueprints[array_key_last($this->blueprints)];

        if ($key) {
            $data = $data[$key] ?? null;
        }

        return $data;
    }

    /**
     * Merges model blueprint according to specified configuration.
     */
    public function processBlueprint($blueprint)
    {
        $customBlueprint = $this->settings[BP_BLUEPRINT];
        $blueprint = array_replace_recursive($blueprint, $customBlueprint);
        $blueprint = array_change_key_case($blueprint, CASE_LOWER);
        return $blueprint;
    }

    public function processFieldBlueprint($blueprint)
    {
        $config = $blueprint[BLUEPRINT_KEY] ?? [];
        $config['isStructureField'] = $this->inStructure();

        $customFields = $this->settings[BP_FIELDS];
        $fieldType = $blueprint['type'] ?? null;
        $fieldConfig = $customFields[$fieldType] ?? null;

        if ($fieldConfig) {
            $config = array_replace($fieldConfig, $config);
        }

        $blueprint[BLUEPRINT_KEY] = $config;
        return $blueprint;
    }

    /**
     * Determines if a field should be included in the resulting data.
     */
    public function fieldPredicate($field, $input)
    {
        return (!$this::isFieldIgnored($this->blueprint()) && $field->value() !== null);
    }

    /**
     * Determines what data to return for this field in the result.
     */
    public function fieldHandler($field, $input)
    {
        return $field->value();
    }

    public function structureHandler($field, $input)
    {
        $structure = $field->toStructure();
        $fields = $this->processBlueprint($this->blueprint('fields'));
        $sync = $this->blueprint()[BLUEPRINT_KEY]['sync'] ?? null;

        array_push($this->blueprints, $fields);
        $data = $this->walkStructure(null, $structure, $input, $sync);
        array_pop($this->blueprints);
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
        $this->level++;

        if ($isStructureObject = is_a($model, StructureObject::class)) {
            array_push($this->structureLevels, $this->level);
        } else {
            array_push($this->blueprints, $this->processBlueprint($model->blueprint()->fields()));
        }

        $data = null;
        $content = $model->content($this->settings['language']);

        foreach ($this->blueprint() as $key => $fieldBlueprint) {
            $field = $content->$key();
            $inputData = $input[$key] ?? null;

            $fieldBlueprint = $this->processFieldBlueprint($fieldBlueprint);
            array_push($this->blueprints, $fieldBlueprint);
            $fieldData = $this->walkField($field, $inputData);
            array_pop($this->blueprints);

            if ($fieldData !== null) {
                $data[$key] = $fieldData;
            }
        }

        if ($isStructureObject) {
            array_pop($this->structureLevels);
        }

        $this->level--;
        return $data;
    }

    public function walkField($field, $input)
    {
        $data = null;

        if ($this->fieldPredicate($field, $input)) {
            if ($this->blueprint('type') === 'structure') {
                $data = $this->structureHandler($field, $input);
            } else {
                $data = $this->fieldHandler($field, $input);
            }
        }

        return $data;
    }

    /**
     * Walks over structure field entries and returns their result.
     */
    public function walkStructure($obsolete, $structure, $input = [], $sync = null)
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

            $childData = $this->walk($entry, $inputEntry);

            if (!empty($childData)) {
                $data[] = $childData;
            }
        }

        return $data;
    }
}
