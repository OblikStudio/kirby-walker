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
    public function fieldPredicate($field, $blueprint, $input)
    {
        $ignored = $this::isFieldIgnored($blueprint);
        $undefined = $field->value() === null;
        return (!$ignored && !$undefined);
    }

    /**
     * Determines what data to return for this field in the result.
     */
    public function fieldHandler($field, $blueprint, $input)
    {
        return $field->value();
    }

    /**
     * Determines what data to return for this structure in the result.
     */
    public function structureHandler($structure, $blueprint, $input)
    {
        $data = null;

        $sync = $blueprint[BLUEPRINT_KEY]['sync'] ?? false;
        $fields = $blueprint['fields'] ?? [];
        $fieldsBlueprint = $this->processBlueprint($fields);

        if ($sync && is_array($input)) {
            $input = array_column($input, null, $sync);
        }

        foreach ($structure as $id => $entry) {
            if ($sync) {
                $inputEntry = $input[$entry->$sync()] ?? null;
            } else {
                $inputEntry = $input[$id] ?? null;
            }

            $childData = $this->walk($entry, $fieldsBlueprint, $inputEntry);

            if (!empty($childData)) {
                if ($sync) {
                    $childData[$sync] = $id;
                }

                $data[] = $childData;
            }
        }

        return $data;
    }

    public function walk(Model $model, array $fieldsBlueprint = [], $input = [])
    {
        $data = null;
        $content = $model->content($this->settings[BP_LANGUAGE]);

        foreach ($fieldsBlueprint as $key => $fieldBlueprint) {
            $field = $content->$key();
            $inputData = $input[$key] ?? null;
            $fieldBlueprint = $this->processFieldBlueprint($fieldBlueprint);

            try {
                $fieldData = $this->walkField($field, $fieldBlueprint, $inputData);
            } catch (Throwable $e) {
                $modelName = is_a($model, Site::class) ? 'site' : $model->id();
                $fieldName = $field->key();
                $errorName = $e->getMessage();

                throw new Exception("Could not process $fieldName in $modelName: $errorName");
            }

            if ($fieldData !== null) {
                $data[$key] = $fieldData;
            }
        }

        return $data;
    }

    public function walkField($field, $blueprint, $input)
    {
        $data = null;

        if ($this->fieldPredicate($field, $blueprint, $input)) {
            if ($blueprint['type'] === 'structure') {
                $data = $this->structureHandler($field->toStructure(), $blueprint, $input);
            } else {
                $data = $this->fieldHandler($field, $blueprint, $input);
            }
        }

        return $data;
    }
}
