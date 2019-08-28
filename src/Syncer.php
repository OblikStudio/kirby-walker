<?php

namespace Oblik\Outsource;

/**
 * Synchronizes structures based on input data by comparing entry IDs.
 */
class Syncer extends Walker
{
    public function fieldPredicate($blueprint, $field, $input)
    {
        return $blueprint['type'] === 'structure';
    }

    public function walkStructure($fieldBlueprints, $structure, $input = [], string $sync = null)
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
                        $nestedData = $this->walk($entry, $inputEntry, $fieldBlueprints);

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
            // If the structure should not be synchronized, simply copy all of
            // its entries and leave them unchanged.
            foreach ($structure as $entry) {
                $data[] = $entry->content()->toArray();
            }
        }

        return $data;
    }
}
