<?php

namespace Oblik\Outsource;

use PHPUnit\Framework\TestCase;
use Kirby\Data\Txt;
use Yaml;

final class ImporterTest extends TestCase
{
    public static $merge;
    public static $mergeItems;
    public static $contentItems;

    public static function import($importFile, $textFile)
    {
        kirby()->impersonate('kirby');

        $importFilePath = realpath(__DIR__ . '/../fixtures/' . $importFile);
        $importData = json_decode(file_get_contents($importFilePath), true);

        $formatter = new Formatter();
        $importer = new Importer($formatter, [
            'language' => 'bg',
            'blueprint' => option('oblik.outsource.blueprints'),
            'fields' => option('oblik.outsource.fields')
        ]);
        $importer->import($importData);

        $textFilePath = realpath(__DIR__ . '/../roots/content/' . $textFile);
        $importResult = file_get_contents($textFilePath);
        return Txt::decode($importResult);
    }

    public static function setUpBeforeClass(): void
    {
        $merge = self::import('merge.json', '2_import-merge/merge.bg.txt');
        $content = self::import('content.json', '3_import-content/content.bg.txt');

        self::$merge = $merge;
        self::$mergeItems = Yaml::decode(self::$merge['items']);
        self::$contentItems = Yaml::decode($content['items']);

        /**
         * Below would be better for testing
         * @see https://github.com/getkirby/kirby/issues/1959
         */
        // $item = kirby()->page('import-content')->content('bg');
    }

    public function testDoesNotContainTitle()
    {
        // The `Title` field is present in the default txt by default but it
        // shouldn't appear in the translation because it's not in the blueprint.
        $this->assertArrayNotHasKey('title', self::$merge);
    }

    public function testDefaultValuePreserved()
    {
        // If a field is not present in the import but exists in the blueprint and
        // in the default txt, it should be preserved.
        $this->assertEquals('default', self::$merge['f1']);
    }

    public function testValueImported()
    {
        // If a field exists in both the default txt and the import, the imported
        // value should override the default one.
        $this->assertEquals('both imported', self::$merge['f2']);
    }

    public function testImportedValueAdded()
    {
        // If a value exists only in the import but the field is listed in the
        // blueprint, that value should be added.
        $this->assertEquals('import', self::$merge['f3']);
    }

    public function testMissingValueIsMissing()
    {
        // The `f4` field is defined in the blueprint, but absent in both the
        // default txt and the import. It should not appear in the translation.
        $this->assertArrayNotHasKey('f4', self::$merge);
    }

    public function testStructureSecondEntryPreserved()
    {
        // Even though the import contains one entry, the second entry from the
        // default txt should be copied.
        $this->assertEquals('default', self::$mergeItems[1]['f1']);
    }

    public function testStructureDefaultValuePreserved()
    {
        $this->assertEquals('default', self::$mergeItems[0]['f1']);
    }

    public function testStructureValueImported()
    {
        $this->assertEquals('both imported', self::$mergeItems[0]['f2']);
    }

    public function testStructureImportedValueAdded()
    {
        $this->assertEquals('import', self::$mergeItems[0]['f3']);
    }

    public function testStructureMissingValueIsMissing()
    {
        $this->assertArrayNotHasKey('f4', self::$mergeItems[0]);
    }

    public function testYamlInStructure()
    {
        $entry = self::$contentItems[0];
        $this->assertIsArray($entry['yaml']);
        $this->assertEquals($entry['yaml']['text'], 'import');
    }
}
