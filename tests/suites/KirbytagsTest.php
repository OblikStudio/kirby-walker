<?php

namespace KirbyOutsource;

use PHPUnit\Framework\TestCase;

final class KirbytagsTest extends TestCase
{
    public function testDecode()
    {
        $parsed = KirbytagParser::encode('(link: https://example.com/ text: foo)');
        $this->assertEquals($parsed, '<kirby></kirby>');
    }
}
