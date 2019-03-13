<?php
namespace Memsource;

use kirbytext;

class KirbytagXML {
    public static function pairsToXML($pairs) {
        $attributes = '';
        $content = '';

        foreach ($pairs as $key => $value) {
            if ($key === 'text') {
                $content = $value;
                unset($pairs[$key]);
            } else {
                $value = htmlspecialchars($value);
                $attributes .= " $key=\"$value\"";
            }
        }

        if (strlen($content) > 0) {
            return "<kirby$attributes>$content</kirby>";
        } else {
            return "<kirby$attributes />";
        }
    }

    public static function replace ($text) {
        // kirby/core/kirbytext.php:49
        // kirby/core/kirbytext.php:69
        // kirby/core/kirbytag.php:30

        return preg_replace_callback('!(?=[^\]])\([a-z0-9_-]+:.*?\)!is', function ($input) {
            $tag  = trim(rtrim(ltrim($input[0], '('), ')'));
            $name = trim(substr($tag, 0, strpos($tag, ':')));

            // if the tag is not installed return the entire string
            if(!isset(kirbytext::$tags[$name])) return $input[0];

            // get a list with all attributes
            $attributes = isset(kirbytext::$tags[$name]['attr']) ? (array)kirbytext::$tags[$name]['attr'] : array();

            // add the name as first attribute
            array_unshift($attributes, $name);

            // extract all attributes
            $search = preg_split('!(' . implode('|', $attributes) . '):!i', $tag, false, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
            $num    = 0;
            $pairs  = [
                $name => '' // make sure the name is always an attribute so we can extract it in the import later
            ];

            foreach($search as $key) {

                if(!isset($search[$num+1])) break;

                $key   = trim($search[$num]);
                $value = trim($search[$num+1]);

                $pairs[$key] = $value;
                $num = $num+2;

            }

            return static::pairsToXML($pairs);
        }, $text);
    }

    public static function revert ($text) {
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
