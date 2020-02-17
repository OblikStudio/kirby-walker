<?php

namespace Oblik\Outsource;

use Oblik\Outsource\Serializer\KirbyTags;
use Oblik\Outsource\Serializer\Markdown;
use Oblik\Outsource\Serializer\Yaml;
use Oblik\Outsource\Serializer\Tags;
use Kirby\Data\Json;

class Formatter
{
    protected static $serializers = [
        'kirbytags' => KirbyTags::class,
        'markdown' => Markdown::class,
        'yaml' => Yaml::class,
        'tags' => Tags::class,
        'json' => Json::class
    ];

    public static function serialize(array $blueprint, $field)
    {
        $options = $blueprint[KEY] ?? null;
        $serialize = $options['serialize'] ?? [];
        $content = $field->value();

        if ($content === null || $content === '') {
            // Field has no value.
            return null;
        }

        foreach ($serialize as $key => $config) {
            $serializer = self::$serializers[$key] ?? null;

            if ($serializer) {
                $content = $serializer::decode($content, [
                    'field' => $field,
                    'blueprint' => $blueprint,
                    'config' => $config
                ]);
            }
        }

        return $content;
    }

    public static function deserialize(array $blueprint, $data)
    {
        $options = $blueprint[KEY] ?? null;
        $serializers = $options['deserialize'] ?? null;

        if (!is_array($serializers)) {
            $serializers = array_reverse($options['serialize'] ?? [], true);
        }

        foreach ($serializers as $key => $config) {
            $serializer = self::$serializers[$key] ?? null;

            if ($serializer) {
                $data = $serializer::encode($data, [
                    'blueprint' => $blueprint,
                    'config' => $config
                ]);
            }
        }

        return $data;
    }
}
