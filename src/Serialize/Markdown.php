<?php

namespace Oblik\Walker\Serialize;

use Kirby\Text\Markdown as MarkdownParser;
use League\HTMLToMarkdown\HtmlConverter;

class Markdown
{
	protected static $decodeOptions = [
		'header_style' => 'atx',
		'suppress_errors' => true,
		'strip_tags' => false,
		'bold_style' => '**',
		'italic_style' => '*',
		'remove_nodes' => '',
		'hard_break' => true,
		'list_item_style' => '-'
	];

	public static function decode(string $text, array $options = [])
	{
		$parser = new MarkdownParser($options);
		$output = $parser->parse($text);
		return str_replace(">\n<", '><', $output);
	}

	public static function encode(string $text, array $options = [])
	{
		$options = array_merge(static::$decodeOptions, $options);
		$converter = new HtmlConverter($options);
		return $converter->convert($text);
	}
}
