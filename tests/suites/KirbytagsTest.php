<?php

namespace KirbyOutsource;

use PHPUnit\Framework\TestCase;

final class KirbytagsTest extends TestCase
{
    public function serialize($input, $expected, $options = [])
    {
        $parsed = KirbytagSerializer::encode($input, $options);
        $this->assertEquals($expected, $parsed);

        $decoded = KirbytagSerializer::decode($parsed);
        $this->assertEquals($input, $decoded);
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

    public function testSelfClosingTag()
    {
        $this->serialize(
            '(link: https://example.com/ text: foo)',
            '<kirby link="https://example.com/" text="foo"/>'
        );
    }

    public function testExternalTags()
    {
        $this->serialize(
            '(link: https://example.com/ text: foo target: _blank)',
            '<kirby text="foo"><value name="link" index="0">https://example.com/</value><value name="target" index="2">_blank</value></kirby>',
            [
                'tags' => ['link', 'target']
            ]
        );
    }

    public function testHtmlTags()
    {
        $this->serialize(
            '(link: #&lt;ha<s"h\' text: <div>is "5\' &lt; <br> &amp; three</div>?)',
            '<kirby link="#&amp;lt;ha&lt;s&quot;h\'"><value name="text" index="1"><div>is "5\' &lt; <br/> &amp; three</div>?</value></kirby>',
            [
                'tags' => ['text'],
                'html' => ['text']
            ]
        );
    }

    public function testAllExternal()
    {
        $this->serialize(
            '(link: #example text: random: yes)',
            '<kirby><value name="link" index="0">#example</value><value name="text" index="1">random: yes</value></kirby>',
            [
                'tags' => ['link', 'text']
            ]
        );
    }

    public function testUtfCharacters()
    {
        $this->serialize(
            '(link: #т§رト text: тест§اختبار テスト)',
            '<kirby link="#т§رト"><value name="text" index="1">тест§اختبار テスト</value></kirby>',
            [
                'tags' => ['text']
            ]
        );
    }


    public function testEmptyValues()
    {
        $this->serialize(
            '(link: text:)',
            '<kirby link=""><value name="text" index="1"></value></kirby>',
            [
                'tags' => ['text']
            ]
        );
    }

    /**
     * @see https://stackoverflow.com/questions/57176724/
     */
    public function testIncorrectNewlineDecode()
    {
        $this->expectException('PHPUnit\Framework\ExpectationFailedException');
        $this->serialize(
            "(link: # text: <div>\ntext<br></div>)",
            "<kirby link=\"#\"><value name=\"text\" index=\"1\"><div>\ntext<br/></div></value></kirby>",
            [
                'tags' => ['text'],
                'html' => ['text']
            ]
        );
    }
}
