<?php

namespace Oblik\Outsource;

class Formatter
{
    public $settings = [];

    public function __construct($settings = [])
    {
        $settings = [
            'serializers' => [
                'markdown' => Serializer\Markdown::class,
                'kirbytags' => Serializer\KirbyTags::class,
                'yaml' => Serializer\Yaml::class
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
            // Field has no value.
            return null;
        }

        foreach ($serialize as $key => $config) {
            $serializer = $this->serializers[$key] ?? null;

            if ($serializer) {
                $content = $serializer::decode($content, [
                    'blueprint' => $blueprint,
                    'config' => $config
                ]);
            }
        }

        return $content;
    }

    public function deserialize(array $blueprint, $data)
    {
        $options = $blueprint[BLUEPRINT_KEY] ?? null;
        $serializers = $options['deserialize'] ?? null;

        if (!is_array($serializers)) {
            $serializers = array_reverse($options['serialize'] ?? [], true);
        }

        foreach ($serializers as $key => $config) {
            $serializer = $this->serializers[$key] ?? null;

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
