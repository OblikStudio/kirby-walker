<?php

use PHPUnit\Framework\TestCase;
use KirbyExporter\Exporter;

$exporter = new Exporter([
  'fieldPredicate' => function ($blueprint) {
    return $blueprint['translate'] ?? true;
  }
]);

$GLOBALS['data'] = $exporter->export();
relog($GLOBALS['data']);

final class ExporterTest extends TestCase {
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

  public function testYamlStructure () {
    $keys = array_keys($GLOBALS['data']['site']['struct'][0]['yamlField']);
    $this->assertEquals($keys, ['text']);
  }

  public function testFieldPredicate () {
    $this->assertArrayNotHasKey('notTranslated', $GLOBALS['data']['site']);
  }
}
