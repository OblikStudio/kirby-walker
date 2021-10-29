<?php

namespace Oblik\Walker\Serialize;

use PHPUnit\Framework\TestCase;

final class KirbyTagsTest extends TestCase
{
	public function serialize($input, $expected)
	{
		$xml = KirbyTags::decode($input);
		$this->assertEquals($expected, $xml);
		$text = KirbyTags::encode($xml);
		$this->assertEquals($input, $text);

		KirbyTags::$externalAttrs = ['text'];
		KirbyTags::$encodeEntities = false;
	}

	public function testInvalidTag()
	{
		$this->serialize(
			'(foo: bar link: #)',
			'(foo: bar link: #)'
		);
	}

	public function testInvalidAttributes()
	{
		$this->serialize(
			'(link: foo: no bar: nope)',
			'<kirby link="foo: no bar: nope"/>'
		);
	}

	public function testEmptyValues()
	{
		$this->serialize(
			'(link: text:)',
			'<kirby link=""><value name="text"/></kirby>'
		);
	}

	public function testSelfClosingTag()
	{
		$this->serialize(
			'(link: https://example.com/ rel: nofollow)',
			'<kirby link="https://example.com/" rel="nofollow"/>'
		);
	}

	public function testExternalTag()
	{
		$this->serialize(
			'(link: https://example.com/ text: hello text)',
			'<kirby link="https://example.com/"><value name="text">hello text</value></kirby>'
		);
	}

	public function testTagsOrder()
	{
		KirbyTags::$externalAttrs = ['link', 'target'];

		$this->serialize(
			'(link: https://example.com/ text: foo target: _blank)',
			'<kirby text="foo"><value name="link" index="0">https://example.com/</value><value name="target">_blank</value></kirby>'
		);

		KirbyTags::$externalAttrs = ['link', 'text'];

		$this->serialize(
			'(link: https://example.com/ text: foo target: _blank)',
			'<kirby target="_blank"><value name="link" index="0">https://example.com/</value><value name="text" index="1">foo</value></kirby>'
		);
	}

	public function testAllExternal()
	{
		KirbyTags::$externalAttrs = ['link', 'text'];

		$this->serialize(
			'(link: #example text: random: yes)',
			'<kirby><value name="link">#example</value><value name="text">random: yes</value></kirby>'
		);
	}

	public function testUtfCharacters()
	{
		$this->serialize(
			'(link: #т§رト text: тест§اختبار テスト)',
			'<kirby link="#т§رト"><value name="text">тест§اختبار テスト</value></kirby>'
		);
	}

	public function testEntitiesEncode()
	{
		KirbyTags::$encodeEntities = true;

		$this->serialize(
			'(link: ">\' text: \'"<>&&gt;)',
			'<kirby link="&quot;&gt;\'"><value name="text">\'"&lt;&gt;&amp;&amp;gt;</value></kirby>'
		);
	}

	/**
	 * An older version of libxml (2.9.7) caused a strange newline to appear.
	 * @see https://stackoverflow.com/questions/57176724/
	 */
	public function testNewlineDecode()
	{
		$this->serialize(
			"(link: # text: <div>text<br></div>)",
			"<kirby link=\"#\"><value name=\"text\"><div>text<br/></div></value></kirby>"
		);
	}
}
