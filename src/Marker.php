<?php

namespace Oblik\Outsource;

use Kirby\Toolkit\Str;

/**
 * Adds IDs to structure entries marked as synced.
 */
class Marker extends Walker
{
    public static function generateId(array $ids)
    {
        do {
            $id = Str::random(4, 'alphaLower');
        } while (in_array($id, $ids));

        return $id;
    }

    public static function addIds(array $content, string $key)
    {
        $ids = array_column($content, $key);

        foreach ($content as &$entry) {
            if (empty($entry[$key])) {
                $entry[$key] = self::generateId($ids);
            }
        }

        return $content;
    }

    public function fieldPredicate($field, $blueprint, $input)
    {
        return $blueprint['type'] === 'structure';
    }

    public function structureHandler($structure, $blueprint, $input)
    {
        $data = null;

        $sync = $blueprint[BLUEPRINT_KEY]['sync'] ?? false;
        $fields = $blueprint['fields'] ?? [];
        $fieldsBlueprint = $this->processBlueprint($fields);

        if ($sync) {
            foreach ($structure as $entry) {
                $content = $entry->content();
                $fields = $content->toArray();

                if ($nestedStructures = $this->walk($content, $fieldsBlueprint)) {
                    $fields = array_replace($fields, $nestedStructures);
                }

                $data[] = $fields;
            }

            if ($data) {
                $data = self::addIds($data, $sync);
            }
        } else {
            // If the current structure is not synced, it's impossible to sync
            // any nested structures, so return `null` and don't bother
            // recursing further.
        }

        return $data;
    }
}
