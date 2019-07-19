<?php

namespace KirbyOutsource;

use KirbyOutsource\KirbytagParser;

class Formatter
{
    public static function mutate($data, $blueprint)
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

    public static function decode($field, $blueprint)
    {
        $parseYaml = $blueprint[BLUEPRINT_KEY]['yaml'] ?? false;

        if ($parseYaml) {
            $data = $field->yaml();
        } else {
            $data = $field->value();
        }

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = KirbytagParser::toXML($value); // todo: recursive replace tags
            }
        } else {
            $data = KirbytagParser::toXML($data);
        }

        return $data;
    }

    public static function extract($field, $blueprint)
    {
        $decoded = self::decode($field, $blueprint);
        $data = self::mutate($decoded, $blueprint);

        return $data;
    }
}
