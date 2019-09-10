<?php

namespace Oblik\Outsource;

/**
 * Recursively returns the differences between two input arrays. Attempts to
 * compare arrays with IDs according to those IDs.
 */
class Diff
{
    /**
     * Invokes a callback to replace entries in input A using any matching ones
     * in input B. Missing entries are left as-is.
     */
    public static function processKeyedArray (
        array $inputA,
        array $inputB,
        callable $callback,
        $key = 'id'
    ) {
        $inputB = array_column($inputB, null, $key);

        foreach ($inputA as &$entryA) {
            $id = $entryA[$key] ?? null;
            $entryB = $inputB[$id] ?? null;

            if ($entryB) {
                $entryA = $callback($entryA, $entryB);

                if (is_array($entryA)) {
                    // Make sure the ID sticks with the entry so it still
                    // remains identifiable.
                    $entryA[$key] = $id;
                }
            }
        }

        return $inputA;
    }

    public static function process($data, $snapshot)
    {
        $result = null;

        if (is_array($data) && is_array($snapshot)) {
            $ids = array_column($data, 'id');

            // If all entries have IDs, assume the array is keyed and process
            // entries according to their IDs.
            if (count($ids) === count($data)) {
                $result = self::processKeyedArray($data, $snapshot, [self::class, 'process']);
                $result = array_filter($result);

                if (count($result) === 0) {
                    $result = null;
                }
            } else {
                foreach ($data as $key => $entry) {
                    $snapshotEntry = $snapshot[$key] ?? null;
                    $diff = self::process($entry, $snapshotEntry);

                    if ($diff !== null) {
                        $result[$key] = $diff;
                    }
                }
            }
        } else if ($data !== $snapshot) {
            $result = $data;
        }

        return $result;
    }
}
