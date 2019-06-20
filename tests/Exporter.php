<?php

use PHPUnit\Framework\TestCase;
use KirbyExporter\Exporter;

$exporter = new Exporter();
$data = $exporter->export();

final class EmailTest extends TestCase {
  public function testFoo (): void {
    global $data;

    $this->assertEquals($data['pages']['home']['text'], 'testbar');
  }
}
