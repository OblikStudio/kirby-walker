<?php

namespace KirbyOutsource;

use PHPUnit\Framework\TestCase;
use Kirby\Cms\Pages;

final class ExporterTest extends TestCase
{
    public static $data;

    public static function setUpBeforeClass(): void
    {
        $exporter = new Exporter([
            'blueprints' => option('oblik.outsource.blueprints'),
            'fields' => option('oblik.outsource.fields'),
            'fieldPredicate' => function ($blueprint, $field) {
                return (
                    ($blueprint['translate'] ?? true) &&
                    Walker::fieldPredicate($blueprint, $field)
                );
            }
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
        $this->assertRegExp('/<kirby.*>.*<\/kirby>/', self::$data['text']);
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

    public function testYamlWhitelist()
    {
        $keys = array_keys(site()->yamlField()->yaml());
        $whitelist = site()->blueprint()->fields()['yamlField'][BLUEPRINT_KEY]['yaml'];
        $data = self::$data['yamlfield'];

        foreach ($keys as $key) {
            if (in_array($key, $whitelist)) {
                $this->assertArrayHasKey($key, $data);
            } else {
                $this->assertArrayNotHasKey($key, $data);
            }
        }
    }

    public function testYamlAllKeys()
    {
        $keys = site()->yamlFieldAll()->yaml();
        $this->assertEquals(self::$data['yamlfieldall'], $keys);
    }

    public function testYamlInStructure()
    {
        $keys = array_keys(self::$data['struct'][0]['yamlfield']);
        $this->assertEquals($keys, ['text']);
    }

    public function testFalsyValueInStructure()
    {
        $this->assertArrayHasKey('falsystructurefield', self::$data['struct'][0]);
    }
}
