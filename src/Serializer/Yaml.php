<?php

namespace Oblik\Outsource\Serializer;

use const Oblik\Outsource\BLUEPRINT_KEY;
use Kirby\Data\Yaml as YamlParser;

class Yaml
{
    public static function decode($input, $options)
    {
        $inStructure = $options['blueprint'][BLUEPRINT_KEY]['isStructureField'];

        if (is_string($input) && !$inStructure) {
            return YamlParser::decode($input);
        }

        return $input;
    }

    public static function encode($input, $options)
    {
        $inStructure = $options['blueprint'][BLUEPRINT_KEY]['isStructureField'];

        // Structure data is encoded as YAML. If any fields are encoded prior to
        // that, they would be double encoded. This must be avoided.
        if (!$inStructure) {
            return YamlParser::encode($input);
        }

        return $input;
    }
}
