<?php

namespace Oblik\Outsource;

class Diff
{
    /**
     * Invokes a callback to replace entries in input A using any matching ones
     * in input B. Missing entries are left as-is.
     */
    public static function processIdentified (
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
                $entryA = $callback($entryA, $entryB, $key);

                if (is_array($entryA)) {
                    $entryA[$key] = $id;
                }
            }
        }

        return $inputA;
    }

    public function process($data, $snapshot, $ignoreKey = null)
    {
        $result = null;

        if (is_array($data) && is_array($snapshot)) {
            $ids = array_column($data, 'id');

            if (count($ids) > 0) {
                $result = self::processIdentified($data, $snapshot, [$this, 'process']);
                $result = array_filter($result);

                if (count($result) === 0) {
                    $result = null;
                }
            } else {
                foreach ($data as $key => $entry) {
                    $snapshotEntry = $snapshot[$key] ?? null;
                    $diff = $this->process($entry, $snapshotEntry);

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
