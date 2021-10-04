<?php

namespace Oblik\Walker;

use Kirby\Cms\App;
use Kirby\Form\Field;
use Oblik\Walker\Util\Diff;
use Oblik\Walker\Util\Updater;

const KEY = 'walker';

App::plugin('oblik/walker', [
	'hooks' => Updater::getHooks(),
	'options' => [
		'blueprint' => [
			'title' => [
				'type' => 'text'
			]
		],
		'fields' => [
			/**
			 * @see https://getkirby.com/docs/reference/panel/fields/tags
			 */
			'tags' => [
				'serialize' => [
					'tags' => true
				]
			],

			/**
			 * @see https://getkirby.com/docs/reference/panel/fields/blocks
			 */
			'blocks' => [
				'walk' => function ($walker, $field, $settings, $input) {
					$data = [];

					if (is_array($input)) {
						$input = array_column($input, null, 'id');
					}

					$blocks = $field->toBlocks();
					$sets = Field::factory('blocks', $settings)->fieldsets();

					foreach ($blocks as $id => $block) {
						$set = $sets->get($block->type());
						$childData = $block->toArray();
						$childData['content'] = $walker->walk($block->content(), $set->fields(), $input[$id]['content'] ?? null);
						$data[] = $childData;
					}

					return $data;
				}
			],

			/**
			 * @see https://github.com/getkirby/editor
			 */
			'editor' => [
				'serialize' => [
					'json' => true
				],
				'export' => [
					'filter' => [
						'keys' => ['id', 'content']
					]
				],
				'import' => [
					'merge' => function ($data, $input) {
						return Diff::processKeyedArray(
							$data ?? [],
							$input ?? [],
							'array_replace_recursive'
						);
					}
				]
			],

			/**
			 * @see https://github.com/OblikStudio/kirby-link-field
			 */
			'link' => [
				'serialize' => [
					'yaml' => true
				]
			],

			/**
			 * @see https://github.com/OblikStudio/kirby-entity-field
			 */
			'entity' => [
				'walk' => function ($walker, $field, $settings, $input) {
					return $walker->walk($field->toEntity(), $settings['fields'], $input);
				}
			],

			/**
			 * @see https://github.com/OblikStudio/kirby-json
			 */
			'json' => [
				'serialize' => [
					'json' => true
				]
			]
		]
	]
]);
