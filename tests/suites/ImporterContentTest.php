<?php

namespace Oblik\Kirby\Outsource;

final class ContentTest extends ImportTestCase
{
    public static $content;

    public static function setUpBeforeClass(): void
    {
        self::$importFile = 'content.json';
        self::$textFile = '3_import-content/content.bg.txt';
        parent::setUpBeforeClass();

        /**
         * Below would be better for testing
         * @see https://github.com/getkirby/kirby/issues/1959
         */
        // $item = kirby()->page('import-content')->content('bg');
    }

    public function testYamlInStructure()
    {
        $entry = self::$items[0];
        $this->assertIsArray($entry['yaml']);
    }
}
