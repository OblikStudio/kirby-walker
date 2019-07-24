<?php

/**
 * @todo replace kirbytags only fields where it's needed
 */

namespace KirbyOutsource;

use KirbyOutsource\KirbytagSerializer;

class Formatter
{
    public static function mutate($blueprint, $data)
    {
        $whitelist = $blueprint[BLUEPRINT_KEY]['yaml'] ?? null;

        if (is_array($data) && is_array($whitelist)) {
            foreach ($data as $key => $value) {
                if (!in_array($key, $whitelist)) {
                    unset($data[$key]);
                }
            }
        }

        return $data;
    }

    public static function decode(array $blueprint, $field)
    {
        $options = $blueprint[BLUEPRINT_KEY] ?? null;
        $parseYaml = $options['yaml'] ?? false;

        if ($parseYaml) {
            $data = $field->yaml();
        } else {
            $data = $field->value();
        }

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = KirbytagSerializer::encode($value);
            }
        } else {
            $data = KirbytagSerializer::encode($data, [
                'tags' => ['target', 'link']
            ]);
        }

        return $data;
    }

    public static function encode(array $blueprint, $data)
    {
        $data = KirbytagSerializer::decode($data);
        return $data;
    }

    public static function extract($blueprint, $field)
    {
        $decoded = self::decode($blueprint, $field);
        $data = self::mutate($blueprint, $decoded);

        return $data;
    }
}
