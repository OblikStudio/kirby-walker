<?php

namespace Oblik\Outsource\Serializer;

use DOMDocument;
use DOMNode;
use Kirby\Text\KirbyTag as NativeTag;
use Kirby\Text\KirbyTags as NativeTags;

class DOM
{
    public static function loadText($text)
    {
        $document = new DOMDocument();
        $flag = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml version="1.0" encoding="utf-8" ?><body>' . $text . '</body>');
        libxml_use_internal_errors($flag);
        return $document->getElementsByTagName('body')->item(0);
    }

    public static function appendHTML(DOMNode $node, $source)
    {
        $body = self::loadText($source);

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

class KirbyTag extends NativeTag
{
    public function render(): string
    {
        $externalTags = $this->option('tags', []);
        $encodeEntities = $this->option('entities', false);

        $parts = array_merge([
            $this->type => $this->value
        ], $this->attrs);

        $document = new DOMDocument('1.0', 'utf-8');
        $element = $document->createElement('kirby');
        $document->appendChild($element);

        foreach ($parts as $key => $value) {
            if (in_array($key, $externalTags)) {
                $child = $document->createElement('value');
                $child->setAttribute('name', $key);

                if ($encodeEntities) {
                    // HTML in text nodes are automatically encoded.
                    $text = $document->createTextNode($value);
                    $child->appendChild($text);
                } else {
                    DOM::appendHTML($child, $value);
                }

                $element->appendChild($child);
            } else {
                // $encodeEntities is irrelevant because HTML characters in
                // attributes are always encoded.
                $element->setAttribute($key, $value);
            }
        }

        // Since attribute values are decoded first, indices of values in the
        // content start after the length of all present attributes.
        $index = $element->attributes->length;
        $keys = array_keys($parts);

        foreach ($element->childNodes as $valueTag) {
            $valueName = $valueTag->getAttribute('name');
            $sourceIndex = array_search($valueName, $keys);

            if ($sourceIndex !== $index) {
                // Save the initial index of the value so it can be spliced in
                // the right spot during decoding.
                $valueTag->setAttribute('index', $sourceIndex);
            }

            $index++;
        }

        // saveXML() is used instead of saveHTML() because it saves empty tags
        // as self-closing tags.
        $content = $document->saveXML($document->documentElement);

        return $content;
    }
}

class KirbyTagsParser extends NativeTags
{
    protected static $tagClass = KirbyTag::class;
}

class KirbyTags
{
    /**
     * Replaces all valid kirbytags with their XML representation.
     */
    public static function decode(string $text, $options = [])
    {
        return KirbyTagsParser::parse($text, [], $options);
    }

    /**
     * Turns the XML representation of a kirbytag to a valid kirbytag.
     */
    public static function encodeTag(string $input, $options = [])
    {
        $xml = $input;
        $encodedEntities = $options['entities'] ?? false;

        if (!$encodedEntities) {
            // If no entities are placed by encode(), entities in the original
            // content must be escaped, otherwise the parser will consume them.
            $xml = str_replace('&', '&amp;', $input);
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
                        array_splice($parts, (int) $index, 0, [$content]);
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

            // saveXML() from encode() will encode HTML entities.
            $text = htmlspecialchars_decode($text);
            $text = rtrim($text);
            return "($text)";
        } else {
            return $input;
        }
    }

    /**
     * Turns all kirbytags to their text form.
     * @return string
     */
    public static function encode(string $text, $options = [])
    {
        return preg_replace_callback('/<kirby(?:[^<]*\/>|.*?<\/kirby>)/s', function ($matches) use ($options) {
            return self::encodeTag($matches[0], $options);
        }, $text);
    }
}
