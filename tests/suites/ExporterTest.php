<?php

use PHPUnit\Framework\TestCase;
use KirbyOutsource\Exporter;
use Kirby\Cms\Pages;

final class ExporterTest extends TestCase {
  public static $data;

  public static function setUpBeforeClass (): void {
    $exporter = new Exporter([
      'blueprints' => option('oblik.exporter.blueprints'),
      'fields' => option('oblik.exporter.fields'),
      'fieldPredicate' => function ($field, $blueprint) {
        return $blueprint['translate'] ?? true;
      }
    ]);

    $models = new Pages();
    $models->prepend(site());
    $exportedData = $exporter->export($models);
    self::$data = $exportedData['site'];
  }

  public function testEmptyBlueprint () {
    // Kirby should convert empty blueprints to valid ones with default values
    // on which the exporter relies.
    $this->assertArrayHasKey('text', self::$data);
  }

  public function testIgnoredField () {
    $this->assertArrayNotHasKey('ignoredfield', self::$data);
  }

  public function testFieldPredicate () {
    $this->assertArrayNotHasKey('nottranslated', self::$data);
  }

  public function testEmptyStructure () {
    $this->assertArrayNotHasKey('emptystruct', self::$data);
  }

  public function testYamlWhitelist () {
    $keys = array_keys(site()->yamlField()->yaml());
    $whitelist = site()->blueprint()->fields()['yamlField']['exporter']['yaml'];
    $data = self::$data['yamlfield'];

    foreach ($keys as $key) {
      if (in_array($key, $whitelist)) {
        $this->assertArrayHasKey($key, $data);
      } else {
        $this->assertArrayNotHasKey($key, $data);
      }
    }
  }

  public function testYamlAllKeys () {
    $keys = site()->yamlFieldAll()->yaml();
    $this->assertEquals(self::$data['yamlfieldall'], $keys);
  }

  public function testYamlInStructure () {
    $keys = array_keys(self::$data['struct'][0]['yamlfield']);
    $this->assertEquals($keys, ['text']);
  }

  public function testFalsyValueInStructure () {
    $this->assertArrayHasKey('falsystructurefield', self::$data['struct'][0]);
  }
}
