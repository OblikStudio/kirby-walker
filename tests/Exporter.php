<?php

use PHPUnit\Framework\TestCase;
use KirbyExporter\Exporter;

$exporter = new Exporter('en');
var_dump($exporter->export());

final class EmailTest extends TestCase {
  public function testFoo (): void {
    $this->assertEquals(site()->title()->value(), 'Nexo');
  }
}
