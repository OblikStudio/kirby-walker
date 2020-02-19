<?php

namespace Oblik\Outsource\Serialize;

use PHPUnit\Framework\TestCase;

final class KirbyTagsTest extends TestCase
{
    public function serialize($input, $expected, $decodeOptions = [], $encodeOptions = [])
    {
        $xml = KirbyTags::decode($input, [
            'serialize' => $decodeOptions
        ]);

        $this->assertEquals($expected, $xml);

        $text = KirbyTags::encode($xml, [
            'serialize' => $encodeOptions
        ]);

        $this->assertEquals($input, $text);
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
            '<kirby link=""><value name="text"/></kirby>',
            ['tags' => ['text']]
        );
    }

    public function testSelfClosingTag()
    {
        $this->serialize(
            '(link: https://example.com/ text: foo)',
            '<kirby link="https://example.com/" text="foo"/>'
        );
    }

    public function testExternalTag()
    {
        $this->serialize(
            '(link: https://example.com/ text: hello text)',
            '<kirby link="https://example.com/"><value name="text">hello text</value></kirby>',
            ['tags' => ['text']]
        );
    }

    public function testTagsOrder()
    {
        $this->serialize(
            '(link: https://example.com/ text: foo target: _blank)',
            '<kirby text="foo"><value name="link" index="0">https://example.com/</value><value name="target">_blank</value></kirby>',
            ['tags' => ['link', 'target']]
        );

        $this->serialize(
            '(link: https://example.com/ text: foo target: _blank)',
            '<kirby target="_blank"><value name="link" index="0">https://example.com/</value><value name="text" index="1">foo</value></kirby>',
            ['tags' => ['link', 'text']]
        );
    }

    public function testHtmlContent()
    {
        $this->serialize(
            '(link: #&lt;ha<s"h\' text: <div>is "5\' &lt; <br> &amp; three</div>?)',
            '<kirby link="#&amp;lt;ha&lt;s&quot;h\'"><value name="text"><div>is "5\' &lt; <br/> &amp; three</div>?</value></kirby>',
            ['tags' => ['text']]
        );
    }

    public function testAllExternal()
    {
        $this->serialize(
            '(link: #example text: random: yes)',
            '<kirby><value name="link">#example</value><value name="text">random: yes</value></kirby>',
            ['tags' => ['link', 'text']]
        );
    }

    public function testUtfCharacters()
    {
        $this->serialize(
            '(link: #т§رト text: тест§اختبار テスト)',
            '<kirby link="#т§رト"><value name="text">тест§اختبار テスト</value></kirby>',
            ['tags' => ['text']]
        );
    }

    public function testEntitiesEncode()
    {
        $this->serialize(
            '(link: ">\' text: \'"<>&&gt;)',
            '<kirby link="&quot;&gt;\'"><value name="text">\'"&lt;&gt;&amp;&amp;gt;</value></kirby>',
            ['tags' => ['text'], 'entities' => true],
            ['entities' => true]
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
            "<kirby link=\"#\"><value name=\"text\"><div>text<br/></div></value></kirby>",
            ['tags' => ['text']]
        );
    }
}
