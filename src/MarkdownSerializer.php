<?php

namespace KirbyOutsource;

use Kirby\Text\Markdown;
use League\HTMLToMarkdown\HtmlConverter;

class MarkdownSerializer
{
    public static $decodeOptions = [
        'header_style' => 'atx',
        'suppress_errors' => true,
        'strip_tags' => false,
        'bold_style' => '__',
        'italic_style' => '_',
        'remove_nodes' => '',
        'hard_break' => true,
        'list_item_style' => '-'
    ];

    public static function encode(string $text, $options = [])
    {
        $parser = new Markdown($options);
        $output = $parser->parse($text);
        return str_replace(">\n<", '><', $output);
    }

    public static function decode(string $text, $options = [])
    {
        $options = array_merge(self::$decodeOptions, $options);
        $converter = new HtmlConverter($options);
        return $converter->convert($text);
    }
}
