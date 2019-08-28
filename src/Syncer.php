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

        if ($sync) {
            if (is_array($input)) {
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
            }
        } else {
            if ($this->inStructure()) {
                // If the structure should not be synchronized, simply copy all of
                // its entries and leave them unchanged.
                foreach ($structure as $entry) {
                    $data[] = $entry->content()->toArray();
                }
            } else {
                // A top-level structure that is not synchronized should be left
                // as-is. Return null to avoid triggering an update.
                return null;
            }
        }

        return $data;
    }
}
