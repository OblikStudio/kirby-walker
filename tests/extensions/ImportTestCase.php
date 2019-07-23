<?php

namespace KirbyOutsource;

use PHPUnit\Framework\TestCase;
use Kirby\Data\Txt;
use Yaml;

class ImportTestCase extends TestCase
{
    protected static $importFile;
    protected static $textFile;
    protected static $data;
    protected static $items;

    public static function setUpBeforeClass(): void
    {
        kirby()->impersonate('kirby');

        $importFilePath = realpath(__DIR__ . './../fixtures/' . self::$importFile);
        $importData = json_decode(file_get_contents($importFilePath), true);
        $importer = new Importer(['language' => 'bg']);
        $importer->import($importData);

        $textFilePath = realpath(__DIR__ . './../kirby/content/' . self::$textFile);
        $importResult = file_get_contents($textFilePath);
        self::$data = Txt::decode($importResult);

        $items = self::$data['items'] ?? null;
        if ($items) {
            self::$items = Yaml::decode($items);
        }
    }
}