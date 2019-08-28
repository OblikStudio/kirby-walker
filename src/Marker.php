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

    public function fieldPredicate($field, $input)
    {
        return $this->blueprint('type') === 'structure';
    }

    public function structureHandler($structure, $input, $sync)
    {
        // Avoid checking top-level desync structures. Even if any nested
        // structures have IDs, they would be useless since the top-level
        // structures are not identified.
        if (!$this->inStructure() && !$sync) {
            return null;
        }

        $data = null;

        foreach ($structure as $entry) {
            $fields = $entry->content()->toArray();

            if ($nestedStructures = $this->walk($entry)) {
                $fields = array_replace($fields, $nestedStructures);
            }

            $data[] = $fields;
        }

        if ($data && $sync) {
            $data = self::addIds($data, $sync);
        }

        return $data;
    }
}
