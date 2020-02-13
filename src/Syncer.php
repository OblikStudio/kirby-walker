<?php

namespace Oblik\Outsource;

/**
 * Synchronizes structures based on input data by comparing entry IDs.
 */
class Syncer extends Walker
{
    public function fieldPredicate($field, $input)
    {
        return $this->blueprint('type') === 'structure';
    }

    public function structureHandler($structure, $input, $sync)
    {
        $data = [];

        if ($sync && is_array($input)) {
            foreach ($input as $inputEntry) {
                $id = $inputEntry[$sync] ?? null;
                $entry = $structure->findBy($sync, $id);

                if ($entry) {
                    // Get all unaltered child data.
                    $childData = $entry->content()->toArray();

                    // Get data from nested structures.
                    $nestedData = $this->walk($entry, $inputEntry);

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
