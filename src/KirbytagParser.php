<?php

namespace KirbyOutsource;

class KirbytagParser
{
    public static function tagToXML($tag)
    {
        $attributes = "$tag->type=\"$tag->value\"";
        $content = null;

        foreach ($tag->attrs as $key => $value) {
            if ($key === 'text') {
                $content = $value;
            } else {
                $value = htmlspecialchars($value);
                $attributes .= " $key=\"$value\"";
            }
        }

        if ($content) {
            return "<kirby $attributes>$content</kirby>";
        } else {
            return "<kirby $attributes/>";
        }
    }

    public static function toXML($text)
    {
        // regex: kirby/src/Text/KirbyTags.php
        return preg_replace_callback('!(?=[^\]])\([a-z0-9_-]+:.*?\)!is', function ($match) {
            try {
                $tag = \Kirby\Text\KirbyTag::parse($match[0], [], []);
                return static::tagToXML($tag);
            } catch (\Exception $e) {
                return $match[0];
            }
        }, $text);
    }

    public static function parse($text)
    {
        return preg_replace_callback('!<kirby.*?(?:<\/kirby>|\/>)!', function ($matches) {
            $tag = $matches[0];

            if (!empty($tag)) {
                // Escape HTML characters in kirby tag content due to self-
                // closing tags like `<br>` that break the parser.
                $tag = preg_replace_callback('!(<kirby[^>]*>)(.*)(<\/kirby>|\/>)!', function ($matches) {
                    return $matches[1] . htmlspecialchars($matches[2]) . $matches[3];
                }, $tag);
            }

            $xml = simplexml_load_string($tag);
            $xmlContent = $xml->__toString();
            $kirbyContent = '';

            foreach ($xml->attributes() as $key => $value) {
                $kirbyContent .= "$key: $value ";
            }

            if (strlen($xmlContent) > 0) {
                $kirbyContent .= "text: $xmlContent ";
            }

            $kirbyContent = rtrim($kirbyContent);

            return "($kirbyContent)";
        }, $text);
    }
}
