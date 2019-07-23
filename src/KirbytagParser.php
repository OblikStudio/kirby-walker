<?php

namespace KirbyOutsource;

use DOMDocument;
use DOMNode;

/**
 * @todo parsing <kirby link="#"><text><div>text<br></div></text></kirby>
 * causes a `\n` to appear after `<br>`
 */

class DOM {
    public static function loadText($text)
    {
        $document = new DOMDocument();
        $flag = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8" ?><body>' . $text . '</body>');
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

class KirbyTag extends \Kirby\Text\KirbyTag
{
    public function render(): string
    {
        $document = new DOMDocument();
        $tags = ['target', 'text'];
        $htmltags = ['text', 'block'];
        $parts = array_merge([
            $this->type => $this->value
        ], $this->attrs);
        $index = 0;

        $element = $document->createElement('kirby');
        $document->appendChild($element);

        foreach ($parts as $key => $value) {
            if (in_array($key, $tags)) {
                $child = $document->createElement('value');
                $child->setAttribute('name', $key);
                $child->setAttribute('index', $index);

                if (in_array($key, $htmltags)) {
                    DOM::appendHTML($child, $value);
                } else {
                    $text = $document->createTextNode($value);
                    $child->appendChild($text);
                }

                $element->appendChild($child);
            } else {
                $element->setAttribute($key, $value);
            }

            $index++;
        }

        // saveXML() is used instead of saveHTML() because it saves empty tags
        // as self-closing tags.
        $content = $document->saveXML($document->documentElement);

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
        $parsed = $text;
        $parsed = KirbyTags::parse($text, [], $options);
        $parsed = self::decode($parsed);
        return $parsed;
    }

    public static function decode($text, $options = [])
    {
        $types = KirbyTag::$types;

        return preg_replace_callback('/<kirby(?:[^<]*\/>|.*?<\/kirby>)/', function ($matches) use ($types, $options) {
            $input = $matches[0];

            $element = DOM::loadText($input)->firstChild;
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
            $type = $parts[0]['name'] ?? null;

            if (isset($types[$type])) {
                $text = '';

                foreach ($parts as $pair) {
                    $name = $pair['name'];
                    $value = $pair['value'];
                    $text .=  "$name: $value ";
                }

                // saveXML() from encode() will encode HTML entities.
                $text = htmlspecialchars_decode($text);
                $text = rtrim($text);
                return "($text)";
            } else {
                return $input;
            }
        }, $text);
    }
}
