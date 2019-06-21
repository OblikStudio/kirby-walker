<?php

use PHPUnit\Framework\TestCase;
use KirbyExporter\Exporter;

// add test for blueprint title

$exporter = new Exporter([
  'blueprints' => [
    'title' => [
      'type' => 'text'
    ]
  ],
  'fields' => [
    'pages' => [
      'exporter' => [
        'ignore' => true
      ]
    ]
  ],
  'fieldPredicate' => function ($blueprint) {
    return $blueprint['translate'] ?? true;
  }
]);

$GLOBALS['data'] = $exporter->export();
relog($GLOBALS['data']);

final class ExporterTest extends TestCase {
  public function testEmptyBlueprint () {
    // Kirby should convert empty blueprints to valid ones with default values
    // on which the exporter relies.
    $this->assertArrayHasKey('text', $GLOBALS['data']['site']);
  }

  public function testIgnoredField () {
    $this->assertArrayNotHasKey('ignoredField', $GLOBALS['data']['site']);
  }

  public function testFieldPredicate () {
    $this->assertArrayNotHasKey('notTranslated', $GLOBALS['data']['site']);
  }

  public function testEmptyStructure () {
    $this->assertArrayNotHasKey('emptyStruct', $GLOBALS['data']['site']);
  }

  public function testYamlWhitelist () {
    $keys = array_keys(site()->yamlField()->yaml());
    $whitelist = site()->blueprint()->fields()['yamlField']['exporter']['yaml'];
    $data = $GLOBALS['data']['site']['yamlField'];

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
    $this->assertEquals($GLOBALS['data']['site']['yamlFieldAll'], $keys);
  }

  public function testYamlInStructure () {
    $keys = array_keys($GLOBALS['data']['site']['struct'][0]['yamlField']);
    $this->assertEquals($keys, ['text']);
  }

  public function testFalsyValueInStructure () {
    $this->assertArrayHasKey('falsyStructureField', $GLOBALS['data']['site']['struct'][0]);
  }
}
