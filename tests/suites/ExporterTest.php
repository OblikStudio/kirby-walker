<?php

namespace Oblik\Outsource;

use Oblik\Outsource\Walker\Exporter;

use PHPUnit\Framework\TestCase;

class TestExporter extends Exporter
{
    public function fieldPredicate($field, $blueprint, $input)
    {
        $translate = $blueprint['translate'] ?? true;
        $predicate = parent::fieldPredicate($field, $blueprint, $input);

        return $translate && $predicate;
    }
}

final class ExporterTest extends TestCase
{
    public static $data;
    public static $site;

    public static function setUpBeforeClass(): void
    {
        $lang = kirby()->defaultLanguage()->code();

        $exporter = new TestExporter(testWalkerSettings());
        $exporter->export(site(), $lang);
        $exporter->exportVariables($lang);

        self::$data = $exporter->data();
        self::$site = self::$data['site'];
    }

    /**
     * Pages have a title field without specifying it in the blueprint. The
     * `blueprints` option of the plugin should artificially add the title.
     */
    public function testHasTitle()
    {
        $this->assertArrayHasKey('title', self::$site);
    }

    /**
     * Kirby should convert empty blueprints to valid ones with default values
     * on which the exporter relies.
     */
    public function testEmptyBlueprint()
    {
        $this->assertArrayHasKey('text', self::$site);
    }

    public function testKirbytagsConverted()
    {
        $this->assertRegExp('/<kirby.*>/', self::$site['text']);
    }

    public function testIgnoredField()
    {
        $this->assertArrayNotHasKey('ignoredfield', self::$site);
    }

    public function testFieldPredicate()
    {
        $this->assertArrayNotHasKey('nottranslated', self::$site);
    }

    public function testEmptyStructure()
    {
        $this->assertArrayNotHasKey('emptystruct', self::$site);
    }

    public function testFiltering()
    {
        $keys = array_keys(self::$site['yamlfield']);
        $this->assertEquals(['text'], $keys);
    }

    public function testFalsyValueInStructure()
    {
        $this->assertArrayHasKey('falsystructurefield', self::$site['struct'][0]);
    }

    public function testSiteFile()
    {
        $this->assertArrayHasKey('file.svg', self::$data['files']);
    }

    public function testPageFile()
    {
        $this->assertArrayHasKey('home/sample.svg', self::$data['files']);
    }

    public function testVariablesExported()
    {
        $this->assertEquals('foo', self::$data['variables']['test']['var']);
    }

    public function testSyncFieldExported()
    {
        $this->assertArrayHasKey('id', self::$data['pages']['struct']['itemssync'][0]);
        $this->assertArrayHasKey('id', self::$data['pages']['struct']['itemssync'][0]['struct'][0]);
    }
}
