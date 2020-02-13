<?php

namespace Oblik\Outsource\Serializer;

use Kirby\Data\Yaml as YamlParser;

class Yaml
{
    public static function decode($input, $options)
    {
        return YamlParser::decode($input);
    }

    public static function encode($input, $options)
    {
        // There's no need to encode data in YAML since that's the format that
        // Kirby uses to store it. In other words, it'll be encoded by Kirby
        // when it's saved. Otherwise, it could get double-encoded.
        return $input;
    }
}
