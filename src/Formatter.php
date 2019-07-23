<?php

namespace KirbyOutsource;

use KirbyOutsource\KirbytagParser;

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
                $data[$key] = KirbytagParser::encode($value); // todo: recursive replace tags
            }
        } else {
            $data = KirbytagParser::encode($data, [
                'encode' => false
            ]);
        }

        return $data;
    }

    public static function encode(array $blueprint, $data)
    {
        $data = KirbytagParser::decode($data);
        return $data;
    }

    public static function extract($blueprint, $field)
    {
        $decoded = self::decode($blueprint, $field);
        $data = self::mutate($blueprint, $decoded);

        return $data;
    }
}
