<?php

use PHPUnit\Framework\TestCase;
use KirbyExporter\Importer;

// add test for blueprint title

$import = json_decode(file_get_contents(__DIR__ . '/imports/export.json'), true);
$importer = new Importer();
$importer->import($import);

final class ImporterText extends TestCase {

}
