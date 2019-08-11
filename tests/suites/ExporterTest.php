<?php

namespace Oblik\Outsource;

use PHPUnit\Framework\TestCase;
use Kirby\Cms\Pages;

class TestExporter extends Exporter
{
    public function fieldPredicate($blueprint, $field, $input)
    {
        return ($blueprint['translate'] ?? true) && parent::fieldPredicate($blueprint, $field, $input);
    }
}

final class ExporterTest extends TestCase
{
    public static $data;

    public static function setUpBeforeClass(): void
    {
        $exporter = new TestExporter([
            'language' => 'en',
            BP_BLUEPRINT => option('oblik.outsource.' . BP_BLUEPRINT),
            BP_FIELDS => option('oblik.outsource.' . BP_FIELDS)
        ]);

        $models = new Pages();
        $models->prepend(site());
        $exportedData = $exporter->export($models);
        self::$data = $exportedData['site'];
    }

    /**
     * Pages have a title field without specifying it in the blueprint. The
     * `blueprints` option of the plugin should artificially add the title.
     */
    public function testHasTitle()
    {
        $this->assertArrayHasKey('title', self::$data);
    }

    /**
     * Kirby should convert empty blueprints to valid ones with default values
     * on which the exporter relies.
     */
    public function testEmptyBlueprint()
    {
        $this->assertArrayHasKey('text', self::$data);
    }

    public function testKirbytagsConverted()
    {
        $this->assertRegExp('/<kirby.*>/', self::$data['text']);
    }

    public function testIgnoredField()
    {
        $this->assertArrayNotHasKey('ignoredfield', self::$data);
    }

    public function testFieldPredicate()
    {
        $this->assertArrayNotHasKey('nottranslated', self::$data);
    }

    public function testEmptyStructure()
    {
        $this->assertArrayNotHasKey('emptystruct', self::$data);
    }

    public function testFiltering()
    {
        $keys = array_keys(self::$data['yamlfield']);
        $this->assertEquals(['text'], $keys);
    }

    public function testFalsyValueInStructure()
    {
        $this->assertArrayHasKey('falsystructurefield', self::$data['struct'][0]);
    }
}
