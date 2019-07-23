<?php

namespace KirbyOutsource;

use DOMDocument;
use DOMNode;

/**
 * @todo parsing <kirby link="#"><text><div>text<br></div></text></kirby>
 * causes a `\n` to appear after `<br>`
 * @todo preserve array indices
 */

class KirbyTag extends \Kirby\Text\KirbyTag
{
    public function render(): string
    {
        $document = new DOMDocument();
        $tags = ['text'];
        $parts = array_merge([
            $this->type => $this->value
        ], $this->attrs);

        $element = $document->createElement('kirby');
        $document->appendChild($element);

        foreach ($parts as $key => $value) {
            if (in_array($key, $tags)) {
                $child = $document->createElement('value');
                $child->setAttribute('name', $key);
                $text = $document->createTextNode($value);

                $child->appendChild($text);
                $element->appendChild($child);
            } else {
                $element->setAttribute($key, $value);
            }
        }

        // saveXML() is used instead of saveHTML() because it saves empty tags
        // as self-closing tags.
        $content = $document->saveXML($document->documentElement);

        if (!$this->option('encode', true)) {
            // saveHTML() encodes by default. Decode only if `encode` is falsy.
            $content = htmlspecialchars_decode($content);
        }

        return $content;
    }
}

class KirbyTags extends \Kirby\Text\KirbyTags
{
    protected static $tagClass = KirbyTag::class;
}

class KirbytagParser
{
    public static function encode($text, $options = [])
    {
        $parsed = KirbyTags::parse($text, [], $options);
        // $parsed = self::decode($parsed);
        return $parsed;
    }

    public static function innerHTML(DOMNode $node) {
        $html = '';

        foreach ($node->childNodes as $child) {
            $html .= $child->ownerDocument->saveHTML($child);
        }

        return $html;
    }

    public static function decode($text, $options = [])
    {
        $types = KirbyTag::$types;

        return preg_replace_callback('!<kirby.*?(?:<\/kirby>|\/>)!', function ($matches) use ($types, $options) {
            $input = $matches[0];
            // $input = '<kirby><value name="text">здрасти &nbsp;</value></kirby>';

            if ($options['decode'] ?? true) {
                $input = htmlspecialchars_decode($input);
            }

            $data = new DOMDocument();
            $flag = libxml_use_internal_errors(true);
            $data->loadHTML('<?xml encoding="utf-8" ?>' . $input, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            libxml_use_internal_errors($flag);

            $parts = [];

            foreach ($data->documentElement->attributes as $attr) {
                $parts[$attr->nodeName] = $attr->nodeValue;
            }

            foreach ($data->documentElement->childNodes as $node) {
                $tagName = $node->tagName ?? null;
                $valueName = null;

                if ($tagName === 'value') {
                    $valueName = $node->getAttribute('name');
                }

                if ($valueName) {
                    $parts[$valueName] = self::innerHTML($node);
                }
            }

            // First key of $parts should be the tag type. It should be a
            // registered tag, otherwise do nothing.
            $type = key($parts);

            if (isset($types[$type])) {
                $text = '';
                $last = end($parts);

                foreach ($parts as $key => $value) {
                    $text .= "$key: $value";

                    if ($value !== $last) {
                        $text .= ' ';
                    }
                }

                return "($text)";
            } else {
                return $input;
            }
        }, $text);
    }
}
