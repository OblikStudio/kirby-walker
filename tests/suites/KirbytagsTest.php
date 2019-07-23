<?php

namespace KirbyOutsource;

use PHPUnit\Framework\TestCase;

/**
 * @todo test differently encoded characters
 * @todo parsing <kirby link="#"><text><div>text<br></div></text></kirby>
 * causes a `\n` to appear after `<br>`
 */

final class KirbytagsTest extends TestCase
{
    public function serialize($input, $expected, $options = [])
    {
        $parsed = KirbytagParser::encode($input, $options);
        $this->assertEquals($expected, $parsed);

        $decoded = KirbytagParser::decode($parsed);
        $this->assertEquals($input, $decoded);
    }

    public function testSelfClosing()
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

    public function testNonExistent()
    {
        $this->serialize(
            '(foo: bar link: #)',
            '(foo: bar link: #)'
        );
    }
}
