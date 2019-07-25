<?php

namespace Oblik\Kirby\Outsource;

final class ContentTest extends ImportTestCase
{
    public static function setUpBeforeClass(): void
    {
        self::$importFile = 'content.json';
        self::$textFile = '3_import-content/content.bg.txt';
        parent::setUpBeforeClass();
    }
}
