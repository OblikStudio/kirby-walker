<?php

namespace Oblik\Walker\Serialize;

use Kirby\Data\Yaml as YamlParser;

class Yaml
{
    public static function decode($input, array $options)
    {
        if (is_string($input)) {
            return YamlParser::decode($input);
        } else {
            // Value is probably in a structure field and its value was already
            // decoded along with the structure itself.
            return $input;
        }
    }

    public static function encode(array $input, array $options)
    {
        // There's no need to encode data in YAML since that's the format that
        // Kirby uses to store it. In other words, it'll be encoded by Kirby
        // when it's saved. Otherwise, it could get double-encoded.
        return $input;
    }
}
