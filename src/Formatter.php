<?php

/**
 * @todo add `with`/`without` configs to serializers, which would
 * whitelist/blacklist keys if the serializer returns an array, also a recursive
 * option
 * @todo add before/after decode/encode hooks
 * @todo if `serialize` option is a function, use it as a serializer
 * @todo rename decode/encode in Formatter to serialize/deserialize
 */

namespace Oblik\Kirby\Outsource;

use Kirby\Data\Yaml;

class Formatter
{
    public $settings = [];

    public function __construct($settings = [])
    {
        $settings = [
            'serializers' => [
                'markdown' => Serializer\Markdown::class,
                'kirbytags' => Serializer\KirbyTags::class,
                'yaml' => Yaml::class
            ]
        ];
        $this->settings = $settings;
        $this->serializers = $settings['serializers'] ?? [];
    }

    public function serialize(array $blueprint, $field)
    {
        $options = $blueprint[BLUEPRINT_KEY] ?? null;
        $serialize = $options['serialize'] ?? [];
        $content = $field->value();

        if ($content === null) {
            return null;
        }

        foreach ($serialize as $key => $config) {
            $serializer = $this->serializers[$key] ?? null;

            if ($serializer) {
                $content = $serializer::decode($content);
            }
        }

        return $content;
    }

    public static function deserialize(array $blueprint, $data)
    {
        return Serializer\KirbyTags::encode($data);
    }
}
