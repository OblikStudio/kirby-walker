<?php

namespace Oblik\Walker\Walker;

use Kirby\Cms\Field;
use Kirby\Cms\Structure;

/**
 * Updates structure fields' entries based on their own IDs and the IDs inside
 * the input entries.
 */
class Synchronizer extends Walker
{
    protected function fieldHandler(Field $field, array $settings, $input)
    {
        if ($settings['translate'] ?? true) {
            return null; // do not update
        } else {
            return $input; // use the original value
        }
    }

    protected function structureHandler(Structure $structure, array $blueprint, $input, $sync)
    {
        $data = null;

        if ($sync && is_array($input)) {
            foreach ($input as $inputEntry) {
                $id = $inputEntry['id'] ?? null;
                $entry = $structure->findBy('id', $id);

                if ($entry) {
                    // Get all unaltered child data.
                    $content = $entry->content();
                    $childData = $content->toArray();

                    // Get data from nested structures.
                    $nestedData = $this->walk($content, $blueprint, $inputEntry);

                    if (is_array($nestedData)) {
                        $childData = array_replace($childData, $nestedData);
                    }
                } else {
                    $childData = $inputEntry;
                }

                $data[] = $childData;
            }
        } else {
            // There's nothing to sync, avoid triggering an update. Even if
            // there are any deeply nested structures with $sync set to `true`,
            // they can't be synced because the current one isn't.
            return null;
        }

        return $data;
    }
}
