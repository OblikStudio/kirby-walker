<?php

namespace Oblik\Outsource;

use PHPUnit\Framework\TestCase;
use Kirby\Data\Txt;
use Yaml;

final class SynchronizerTest extends TestCase
{
    public static $default;
    public static $translation;

    public static function getFile($file)
    {
        $path = realpath(__DIR__ . '/../roots/content/1_struct/' . $file);
        $txtContents = file_get_contents($path);
        $data = Txt::decode($txtContents);
        return Yaml::decode($data['itemssync']);
    }

    public static function setUpBeforeClass(): void
    {
        kirby()->impersonate('kirby');

        $dataFile = realpath(__DIR__ . '/../fixtures/struct.en.yml');
        $saveData = Yaml::decode(file_get_contents($dataFile));

        page('struct')->update([
            'itemssync' => $saveData
        ], 'en');

        self::$default = self::getFile('struct.en.txt');
        self::$translation = self::getFile('struct.bg.txt');
    }

    public function testNewEntryAdded()
    {
        $this->assertEquals(count(self::$default), 2);
        $this->assertEquals(count(self::$translation), 2);
    }

    public function testNewEntryIds()
    {
        $this->assertArrayHasKey('id', self::$default[0]);
        $this->assertArrayHasKey('id', self::$default[0]['struct'][0]);
    }

    public function testNewEntryIdentical()
    {
        $this->assertEquals(self::$default[0], self::$translation[0]);
    }

    public function testOldEntryIdsUnchanged()
    {
        $this->assertEquals('djzw', self::$default[1]['id']);
        $this->assertEquals('pajt', self::$default[1]['struct'][0]['id']);
    }

    public function testOldEntryNewContent()
    {
        $this->assertEquals('en1mod', self::$default[1]['struct'][0]['text']);
    }

    public function testTranslationContentNotOverwritten()
    {
        $this->assertEquals('bg1', self::$translation[1]['struct'][0]['text']);
    }
}
