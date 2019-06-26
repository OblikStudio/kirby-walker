<?php

use PHPUnit\Framework\TestCase;
use KirbyOutsource\Importer;

// add test for blueprint title

$import = json_decode(file_get_contents(__DIR__ . '/imports/export.json'), true);
$importer = new Importer('bg');
$importer->import($import);

relog(site()->yamlField()->value());

final class ImporterText extends TestCase {

}
