<?php

use PHPUnit\Framework\TestCase;
use KirbyOutsource\Importer;

// add test for blueprint title

kirby()->impersonate('kirby');

$import = json_decode(file_get_contents(__DIR__ . '/imports/export.json'), true);
$importer = new Importer([
  'language' => 'bg',
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
  'fieldPredicate' => function ($field, $blueprint) {
    return $blueprint['translate'] ?? true;
  }
]);
$importer->import($import);

final class ImporterText extends TestCase {

}
