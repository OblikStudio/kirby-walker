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
			return '<meta template="' . $matches[1] . '"/>';
		}, $text);
	}

	/**
	 * Turns all templates from XML to text.
	 */
	public static function encode(string $text)
	{
		return preg_replace_callback('/<meta template="([^"]*)"\/?>/', function ($matches) {
			return '{{' . $matches[1] . '}}';
		}, $text);
	}
}
