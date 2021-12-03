<?php

namespace Oblik\Walker;

use Kirby\Cms\App;

load([
	'Oblik\\Walker\\Serialize\\KirbyTags' => 'Serialize/KirbyTags.php',
	'Oblik\\Walker\\Serialize\\Markdown' => 'Serialize/Markdown.php',
	'Oblik\\Walker\\Serialize\\Template' => 'Serialize/Template.php',
	'Oblik\\Walker\\Walker\\Exporter' => 'Walker/Exporter.php',
	'Oblik\\Walker\\Walker\\Importer' => 'Walker/Importer.php',
	'Oblik\\Walker\\Walker\\Walker' => 'Walker/Walker.php'
], __DIR__ . '/src');

App::plugin('oblik/walker', []);
