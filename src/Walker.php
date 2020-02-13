<?php

namespace Oblik\Outsource;

use Exception;
use Throwable;
use Kirby\Cms\Site;
use Kirby\Cms\Model;

class Walker
{
    private static $defaults = [
        BP_LANGUAGE => null,
        BP_BLUEPRINT => [],
        BP_FIELDS => []
    ];

    public $settings = [];

    /**
     * Recursion level.
     */
    protected $level = 0;

    /**
     * Levels at which the model is a structure.
     */
    protected $structureLevels = [];

    /**
     * The blueprints stack, consisting of model, structure or field blueprints.
     */
    protected $blueprints = [];

    /**
     * According to its blueprint, checks whether a field should be ignored by
     * the plugin
     */
    public static function isFieldIgnored(array $blueprint)
    {
        return $blueprint[BLUEPRINT_KEY][BP_IGNORE] ?? false;
    }

    public function __construct($settings = [])
    {
        $this->settings = array_replace(
            self::$defaults,
            $this->settings,
            $settings
        );
    }

    /**
     * Checks if the currently walked model is inside a structure.
     */
    public function inStructure()
    {
        $last = end($this->structureLevels);
        return ($last !== false && $last !== $this->level);
    }

    /**
     * Returns the last blueprint in the stack or a key from it.
     */
    public function blueprint($key = null)
    {
        $data = $this->blueprints[array_key_last($this->blueprints)];

        if ($key) {
            $data = $data[$key] ?? null;
        }

        return $data;
    }

    /**
     * Returns a setting from the `outsource` property in the current blueprint.
     */
    public function blueprintSetting($key)
    {
        return $this->blueprint(BLUEPRINT_KEY)[$key] ?? null;
    }

    /**
     * Formats a blueprint by adding custom fields and changing the keys to
     * lowercase since that's what Kirby internally uses.
     */
    public function processBlueprint($blueprint)
    {
        $customBlueprint = $this->settings[BP_BLUEPRINT];
        $blueprint = array_replace_recursive($blueprint, $customBlueprint);
        $blueprint = array_change_key_case($blueprint, CASE_LOWER);
        return $blueprint;
    }

    /**
     * Merges blueprint settings in `outsource` from the Walker instance with
     * those in the field blueprint.
     */
    public function processFieldBlueprint($blueprint)
    {
        $config = $blueprint[BLUEPRINT_KEY] ?? [];

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
        $ignored = $this::isFieldIgnored($this->blueprint());
        $undefined = $field->value() === null;
        return (!$ignored && !$undefined);
    }

    /**
     * Determines what data to return for this field in the result.
     */
    public function fieldHandler($field, $input)
    {
        return $field->value();
    }

    /**
     * Determines what data to return for this structure in the result. For
     * performance, the current blueprint scope would be that of the fields so
     * each entry can share it.
     */
    public function structureHandler($structure, $input, $sync)
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
                if ($sync) {
                    $childData[$sync] = $id;
                }

                $data[] = $childData;
            }
        }

        return $data;
    }

    /**
     * @return array|null
     */
    public function walk(Model $model, $input = [])
    {
        $this->level++;

        // If the model has a blueprint, its fields should be pushed on the
        // blueprint stack.
        if (method_exists($model, 'blueprint')) {
            $fieldsBlueprint = $model->blueprint()->fields();
            $fieldsBlueprint = $this->processBlueprint($fieldsBlueprint);
            array_push($this->blueprints, $fieldsBlueprint);
        }

        $data = null;
        $content = $model->content($this->settings[BP_LANGUAGE]);

        foreach ($this->blueprint() as $key => $fieldBlueprint) {
            $field = $content->$key();
            $inputData = $input[$key] ?? null;
            $fieldBlueprint = $this->processFieldBlueprint($fieldBlueprint);

            array_push($this->blueprints, $fieldBlueprint);

            try {
                $fieldData = $this->walkField($field, $inputData);
            } catch (Throwable $e) {
                $modelName = is_a($model, Site::class) ? 'site' : $model->id();
                $fieldName = $field->key();
                $errorName = $e->getMessage();

                throw new Exception("Could not process $fieldName in $modelName: $errorName");
            }

            array_pop($this->blueprints);

            if ($fieldData !== null) {
                $data[$key] = $fieldData;
            }
        }

        $this->level--;
        return $data;
    }

    public function walkField($field, $input)
    {
        $data = null;

        if ($this->fieldPredicate($field, $input)) {
            if ($this->blueprint('type') === 'structure') {
                $data = $this->walkStructure($field->toStructure(), $input);
            } else {
                $data = $this->fieldHandler($field, $input);
            }
        }

        return $data;
    }

    public function walkStructure($structure, $input)
    {
        $sync = $this->blueprintSetting('sync');
        $fieldsBlueprint = $this->processBlueprint($this->blueprint('fields'));

        array_push($this->structureLevels, $this->level);
        array_push($this->blueprints, $fieldsBlueprint);
        $data = $this->structureHandler($structure, $input, $sync);
        array_pop($this->blueprints);
        array_pop($this->structureLevels);

        return $data;
    }
}
