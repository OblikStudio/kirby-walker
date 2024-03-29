<?php

namespace Oblik\Walker\Serialize;

use DOMDocument;
use DOMElement;
use DOMNode;
use Exception;
use Kirby\Exception\InvalidArgumentException;
use Kirby\Text\KirbyTag as BaseKirbyTag;
use Kirby\Text\KirbyTags as BaseKirbyTags;
use Kirby\Toolkit\Str;

class KirbyTags extends BaseKirbyTags
{
	public static $defaults = [
		/**
		 * Encode HTML entities in external tags?
		 */
		'encodeEntities' => false,

		/**
		 * Array of tags that should be rendered in their own <value> XML tags,
		 * instead of being attributes of the <kirby> tag.
		 */
		'externalAttributes' => []
	];

	protected static $tagClass = KirbyTag::class;

	/**
	 * Replaces all valid kirbytags with their XML representation.
	 */
	public static function decode(string $text, array $options = [])
	{
		$data = [];
		$options = array_replace(static::$defaults, $options);

		// The code below is the same as the base class' `decode()` method. The
		// only difference is that it uses the customized `KirbyTag` class and
		// calls its custom `render()` method.

		$regex = '!
            (?=[^\]])               # positive lookahead that matches a group after the main expression without including ] in the result
            (?=\([a-z0-9_-]+:)      # positive lookahead that requires starts with ( and lowercase ASCII letters, digits, underscores or hyphens followed with : immediately to the right of the current location
            (\(                     # capturing group 1
                (?:[^()]+|(?1))*+   # repetitions of any chars other than ( and ) or the whole group 1 pattern (recursed)
            \))                     # end of capturing group 1
        !isx';

		return preg_replace_callback($regex, function ($match) use ($data, $options) {
			$debug = $options['debug'] ?? false;

			try {
				return KirbyTag::parse($match[0], $data, $options)->render();
			} catch (InvalidArgumentException $e) {
				// stay silent in production and ignore non-existing tags
				if ($debug !== true || Str::startsWith($e->getMessage(), 'Undefined tag type:') === true) {
					return $match[0];
				}

				throw $e;
			} catch (Exception $e) {
				if ($debug === true) {
					throw $e;
				}

				return $match[0];
			}
		}, $text ?? '');
	}

	/**
	 * Turns the XML representation of a kirbytag to a valid kirbytag.
	 */
	protected static function encodeTag(string $xml, array $options)
	{
		if (!$options['encodeEntities']) {
			// If no entities are placed by encode(), entities in the original
			// content must be escaped, otherwise the parser will consume them.
			$xml = str_replace('&', '&amp;', $xml);
		}

		$element = DOM::loadText($xml)->firstChild;
		$parts = [];

		foreach ($element->attributes as $attr) {
			$parts[] = [
				'name' => $attr->nodeName,
				'value' => $attr->nodeValue
			];
		}

		foreach ($element->childNodes as $node) {
			$tagName = $node->tagName ?? null;

			if ($tagName === 'value') {
				$name = $node->getAttribute('name');
				$index = $node->getAttribute('index');

				if ($name) {
					$content = [
						'name' => $name,
						'value' => DOM::innerHTML($node)
					];

					if (is_numeric($index)) {
						array_splice($parts, (int)$index, 0, [$content]);
					} else {
						$parts[] = $content;
					}
				}
			}
		}

		// First key of $parts should be the tag type. It should be a
		// registered tag, otherwise do nothing.
		$tagType = $parts[0]['name'] ?? null;

		if (isset(KirbyTag::$types[$tagType])) {
			$text = '';

			foreach ($parts as $pair) {
				$name = $pair['name'];
				$value = $pair['value'];
				$text .=  "$name: ";

				if ($value) {
					$text .= "$value ";
				}
			}

			$text = htmlspecialchars_decode($text);
			$text = rtrim($text);

			return "($text)";
		} else {
			return null;
		}
	}

	/**
	 * Turns all kirbytags to their text form.
	 */
	public static function encode(string $text, array $options = [])
	{
		$options = array_replace(static::$defaults, $options);

		return preg_replace_callback(
			'/<kirby(?:[^<]*\/>|.*?<\/kirby>)/s',
			function ($matches) use ($options) {
				$text = $matches[0];

				if ($tag = static::encodeTag($text, $options)) {
					return $tag;
				} else {
					return $text;
				}
			},
			$text
		);
	}
}

class KirbyTag extends BaseKirbyTag
{
	/**
	 * Adds `index` attributes to <value> tags inside <kirby> to ensure that
	 * upon decoding, the tag can preserve the initial order of its settings.
	 */
	public static function index(DOMElement $kirby, array $orderedNames)
	{
		// Since attribute values are decoded first, indices of <value> tags
		// start after the length of all present attributes.
		$index = $kirby->attributes->length;

		foreach ($kirby->childNodes as $value) {
			$name = $value->getAttribute('name');
			$originalIndex = array_search($name, $orderedNames);

			if ($originalIndex !== $index) {
				$value->setAttribute('index', $originalIndex);
			}

			$index++;
		}
	}

	public function render(): string
	{
		$parts = array_merge([
			$this->type => $this->value
		], $this->attrs);

		$document = new DOMDocument('1.0', 'utf-8');
		$element = $document->createElement('kirby');
		$document->appendChild($element);

		foreach ($parts as $key => $value) {
			if (in_array($key, $this->options['externalAttributes'])) {
				$child = $document->createElement('value');
				$child->setAttribute('name', $key);

				if ($this->options['encodeEntities']) {
					// Entities in text nodes are automatically encoded.
					$text = $document->createTextNode($value);
					$child->appendChild($text);
				} else {
					DOM::appendHTML($child, $value);
				}

				$element->appendChild($child);
			} else {
				// Entities in attributes are automatically encoded by saveXML,
				// so $encodeEntities is irrelevant.
				$element->setAttribute($key, $value);
			}
		}

		static::index($element, array_keys($parts));

		// saveXML() is used instead of saveHTML() because it saves empty tags
		// as self-closing tags.
		$content = $document->saveXML($document->documentElement);

		return $content;
	}
}

class DOM
{
	public static function loadText(string $text)
	{
		$document = new DOMDocument();
		$flag = libxml_use_internal_errors(true);
		$document->loadHTML('<?xml version="1.0" encoding="utf-8" ?><body>' . $text . '</body>');
		libxml_use_internal_errors($flag);
		return $document->getElementsByTagName('body')->item(0);
	}

	public static function appendHTML(DOMNode $node, string $source)
	{
		$body = static::loadText($source);

		foreach ($body->childNodes as $child) {
			$child = $node->ownerDocument->importNode($child, true);

			if ($child) {
				$node->appendChild($child);
			}
		}
	}

	public static function innerHTML(DOMNode $node)
	{
		$html = '';

		foreach ($node->childNodes as $child) {
			$html .= $child->ownerDocument->saveHTML($child);
		}

		return $html;
	}
}
