<?php

/**
 * @todo improve encode options
 * @todo add "index" attribute only when needed
 * @todo replace tags in separate function
 */

namespace KirbyOutsource;

use DOMDocument;
use DOMNode;

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

class KirbyTag extends \Kirby\Text\KirbyTag
{
    public function render(): string
    {
        $htmlTags = $this->option('html', []);
        $externalTags = $this->option('tags', []);

        $parts = array_merge([
            $this->type => $this->value
        ], $this->attrs);

        $document = new DOMDocument('1.0', 'utf-8');
        $element = $document->createElement('kirby');
        $document->appendChild($element);

        $index = 0;
        foreach ($parts as $key => $value) {
            if (in_array($key, $externalTags)) {
                $child = $document->createElement('value');
                $child->setAttribute('name', $key);
                $child->setAttribute('index', $index);

                if (in_array($key, $htmlTags)) {
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

class KirbytagSerializer
{
    public static function encode($text, $options = [])
    {
        return KirbyTags::parse($text, [], $options);
    }

    public static function decode($text)
    {
        $types = KirbyTag::$types;

        return preg_replace_callback('/<kirby(?:[^<]*\/>|.*?<\/kirby>)/s', function ($matches) use ($types) {
            $match = $matches[0];

            // loadHTML() would consume HTML entities, so we escape them. Other
            // characters are not escaped because we expect HTML after all.
            $input = str_replace('&', '&amp;', $match);

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
                return $match;
            }
        }, $text);
    }
}
