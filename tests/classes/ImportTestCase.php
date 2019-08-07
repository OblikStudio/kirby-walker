<?php

namespace Oblik\Outsource;

use PHPUnit\Framework\TestCase;
use Kirby\Data\Txt;
use Yaml;

abstract class ImportTestCase extends TestCase
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

        $formatter = new Formatter();
        $importer = new Importer($formatter, [
            'language' => 'bg',
            'blueprint' => option('oblik.outsource.blueprints'),
            'fields' => option('oblik.outsource.fields')
        ]);
        $importer->import($importData);

        $textFilePath = realpath(__DIR__ . './../roots/content/' . self::$textFile);
        $importResult = file_get_contents($textFilePath);
        self::$data = Txt::decode($importResult);

        $items = self::$data['items'] ?? null;
        if ($items) {
            self::$items = Yaml::decode($items);
        }
    }
}
