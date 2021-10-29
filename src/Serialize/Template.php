<?php

namespace Oblik\Walker\Serialize;

class Template
{
	/**
	 * Turns all templates from text to XML.
	 */
	public static function decode(string $text)
	{
		return preg_replace_callback('/{{(.*?)}}/', function ($matches) {
			return '<template value="' . $matches[1] . '"/>';
		}, $text);
	}

	/**
	 * Turns all templates from XML to text.
	 */
	public static function encode(string $text)
	{
		return preg_replace_callback('/<template value="(.*?)"\\/>/', function ($matches) {
			return '{{' . $matches[1] . '}}';
		}, $text);
	}
}
