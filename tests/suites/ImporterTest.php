<?php

namespace Oblik\Outsource;

use PHPUnit\Framework\TestCase;
use Kirby\Data\Json;
use Kirby\Data\Txt;
use Yaml;

final class ImporterTest extends TestCase
{
    public static $data;
    public static $items;
    public static $contentItems;

    public static function import($importFile, $textFile)
    {
        kirby()->impersonate('kirby');

        $importFilePath = realpath(__DIR__ . '/../fixtures/' . $importFile);
        $importData = json_decode(file_get_contents($importFilePath), true);

        $importer = new Importer(testWalkerSettings([
            'language' => 'bg'
        ]));
        $importer->process($importData);

        $textFilePath = realpath(__DIR__ . '/../roots/content/' . $textFile);
        $importResult = file_get_contents($textFilePath);
        return Txt::decode($importResult);
    }

    public static function setUpBeforeClass(): void
    {
        self::$data = self::import('import.json', '2_import/import.bg.txt');
        self::$items = Yaml::decode(self::$data['items']);
        self::$contentItems = Yaml::decode(self::$data['contentitems']);

        /**
         * Below would be better for testing
         * @see https://github.com/getkirby/kirby/issues/1959
         */
        // $item = kirby()->page('import-content')->content('bg');
    }

    public function testImportedArtificialField()
    {
        // The `title` field is artificially added to the blueprint via the
        // `blueprint` setting. It should be imported too.
        $this->assertEquals('Imported', self::$data['title']);
    }

    public function testDefaultValuePreserved()
    {
        // If a field is not present in the import but exists in the blueprint and
        // in the default txt, it should be preserved.
        $this->assertEquals('default', self::$data['f1']);
    }

    public function testValueImported()
    {
        // If a field exists in both the default txt and the import, the imported
        // value should override the default one.
        $this->assertEquals('both imported', self::$data['f2']);
    }

    public function testImportedValueAdded()
    {
        // If a value exists only in the import but the field is listed in the
        // blueprint, that value should be added.
        $this->assertEquals('import', self::$data['f3']);
    }

    public function testStructureSecondEntryPreserved()
    {
        // Even though the import contains one entry, the second entry from the
        // default txt should be copied.
        $this->assertEquals('default', self::$items[1]['f1']);
    }

    public function testStructureDefaultValuePreserved()
    {
        $this->assertEquals('default', self::$items[0]['f1']);
    }

    public function testStructureValueImported()
    {
        $this->assertEquals('both imported', self::$items[0]['f2']);
    }

    public function testStructureImportedValueAdded()
    {
        $this->assertEquals('import', self::$items[0]['f3']);
    }

    public function testYaml()
    {
        $data = Yaml::decode(self::$data['yaml']);
        $this->assertEquals($data['text'], 'import');
    }

    public function testYamlInStructure()
    {
        $entry = self::$contentItems[0];
        $this->assertIsArray($entry['yaml']);
        $this->assertEquals($entry['yaml']['text'], 'import');
    }

    public function testKirbytagInHtml()
    {
        $this->assertEquals(
            '<div><span>(link: # text: import)</span></div>',
            self::$data['html']
        );
    }

    public function testMarkdownInHtml()
    {
        $this->assertEquals(
            "# Import\n\nMarkdown converted to __HTML__ and a _(link: # text: kirbytag)_ here.",
            self::$data['markdown']
        );
    }

    /**
     * The imported items are at different indices compared to the default ones.
     * They should be merged by their IDs, not by their array position.
     */
    public function testCustomMerger()
    {
        $data = Json::decode(self::$data['text']);
        $this->assertEquals('Imported heading', $data[0]['content']);
        $this->assertEquals('Imported content', $data[1]['content']);
    }

    public function testSyncedStructures()
    {
        $data = Yaml::decode(self::$data['itemssync']);
        $this->assertEquals([
            [
                'id' => 'b',
                'content' => 'second imported'
            ],
            [
                'id' => 'c',
                'content' => 'third'
            ],
            [
                'id' => 'a',
                'content' => 'first imported'
            ]
        ], $data);
    }

    public function testVariablesImported()
    {
        $this->assertFileExists(__DIR__ . '/../roots/languages/bg.yml');
    }
}
