<?php

namespace Oblik\Outsource\Walker;

use Kirby\Cms\Field;
use Kirby\Cms\Structure;
use Kirby\Toolkit\Str;

/**
 * Recursively walks over structure fields in a model marked as synced and adds
 * IDs to their entries.
 */
class Marker extends Walker
{
    protected static function uid(array $blacklist)
    {
        do {
            $id = Str::random(4, 'alphaLower');
        } while (in_array($id, $blacklist));

        return $id;
    }

    protected static function addIds(array $data, string $key)
    {
        $ids = array_column($data, $key);

        foreach ($data as &$entry) {
            if (empty($entry[$key])) {
                $entry[$key] = static::uid($ids);
            }
        }

        return $data;
    }

    protected function fieldPredicate(Field $field, array $settings, $input)
    {
        return $settings['type'] === 'structure';
    }

    protected function structureHandler(Structure $structure, array $blueprint, $input, $sync)
    {
        $data = null;

        if ($sync) {
            foreach ($structure as $entry) {
                $content = $entry->content();
                $fields = $content->toArray();

                if ($nestedStructures = $this->walk($content, $blueprint)) {
                    $fields = array_replace($fields, $nestedStructures);
                }

                $data[] = $fields;
            }

            if ($data) {
                $data = static::addIds($data, $sync);
            }
        } else {
            // If the current structure is not synced, it's impossible to sync
            // any nested structures, so return `null` and don't bother
            // recursing further.
        }

        return $data;
    }
}
