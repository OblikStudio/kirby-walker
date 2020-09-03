<?php

namespace Oblik\Walker\Serialize;

use const Oblik\Walker\KEY;

use Kirby\Cms\Field;
use Kirby\Data\Json;

/**
 * Serializes and deserializes data formats, optionally chaining them.
 */
class Formatter
{
	/**
	 * Array of classes that can be used for serialization.
	 */
	protected static $serializers = [
		'kirbytags' => KirbyTags::class,
		'markdown' => Markdown::class,
		'yaml' => Yaml::class,
		'tags' => Tags::class,
		'json' => Json::class
	];

	public static function serialize(array $settings, Field $field)
	{
		$serializers = $settings[KEY]['serialize'] ?? null;
		$data = $field->value();

		if (is_array($serializers) && !empty($data)) {
			// Filter out serializers set to `false`.
			$serializers = array_filter($serializers);

			foreach ($serializers as $key => $config) {
				$serializer = static::$serializers[$key] ?? null;

				if ($serializer) {
					$data = $serializer::decode($data, [
						'field' => $field,
						'settings' => $settings,
						'serialize' => $config
					]);
				}
			}
		}

		return $data;
	}

	public static function deserialize(array $settings, $data)
	{
		$serializers = $settings[KEY]['deserialize'] ?? null;

		if (!is_array($serializers)) {
			$serializers = $settings[KEY]['serialize'] ?? null;

			if (is_array($serializers)) {
				$serializers = array_reverse($serializers);
			}
		}

		if (is_array($serializers)) {
			$serializers = array_filter($serializers);

			foreach ($serializers as $key => $config) {
				$serializer = static::$serializers[$key] ?? null;

				if ($serializer) {
					$data = $serializer::encode($data, [
						'settings' => $settings,
						'serialize' => $config
					]);
				}
			}
		}

		return $data;
	}
}
