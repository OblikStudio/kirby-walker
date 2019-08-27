<?php

namespace Oblik\Outsource;

use Kirby\Toolkit\Str;

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

    public function fieldPredicate($blueprint, $field, $input)
    {
        return $blueprint['type'] === 'structure';
    }

    public function walkStructure($fieldBlueprints, $structure, $input = [], string $sync = null)
    {
        $data = null;

        foreach ($structure as $entry) {
            $fields = $entry->content()->toArray();

            if ($nestedStructures = $this->walk($entry, [], $fieldBlueprints)) {
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
